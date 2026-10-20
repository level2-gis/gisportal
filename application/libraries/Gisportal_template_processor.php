<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * PHPWord TemplateProcessor that keeps "0" values; the base class treats them as empty.
 */
class Gisportal_template_processor extends \PhpOffice\PhpWord\TemplateProcessor
{
    const REL_NS = 'http://schemas.openxmlformats.org/officeDocument/2006/relationships';

    /** @var array Zip entries added in this run (the zip index is not updated until save). */
    private $addedParts = [];

    protected static function ensureUtf8Encoded($subject)
    {
        if ($subject === null || $subject === false || $subject === '') {
            return '';
        }
        return \PhpOffice\PhpWord\Shared\Text::toUTF8((string)$subject);
    }

    /**
     * Delete every table row that contains the placeholder. Occurrences outside tables are left untouched.
     * Returns the number of deleted rows.
     */
    public function deleteRowsContaining($search)
    {
        $macro = '${' . $search . '}';
        $deleted = 0;
        $offset = 0;
        while (($pos = strpos($this->tempDocumentMainPart, $macro, $offset)) !== false) {
            $before = substr($this->tempDocumentMainPart, 0, $pos);
            $rowOpen = max((int)strrpos($before, '<w:tr>'), (int)strrpos($before, '<w:tr '));
            $rowClose = (int)strrpos($before, '</w:tr>');
            if ($rowOpen === 0 || $rowOpen < $rowClose) {
                $offset = $pos + strlen($macro);
                continue;
            }
            $rowEnd = strpos($this->tempDocumentMainPart, '</w:tr>', $pos);
            if ($rowEnd === false) {
                break;
            }
            $rowEnd += strlen('</w:tr>');
            $this->tempDocumentMainPart = substr($this->tempDocumentMainPart, 0, $rowOpen) . substr($this->tempDocumentMainPart, $rowEnd);
            $offset = $rowOpen;
            $deleted++;

            // A table without rows is invalid in Word, so drop it.
            $before = substr($this->tempDocumentMainPart, 0, $rowOpen);
            $tableOpen = max((int)strrpos($before, '<w:tbl>'), (int)strrpos($before, '<w:tbl '));
            $tableClose = strpos($this->tempDocumentMainPart, '</w:tbl>', $rowOpen);
            if ($tableOpen > 0 && $tableClose !== false) {
                $tableXml = substr($this->tempDocumentMainPart, $tableOpen, $tableClose - $tableOpen);
                if (strpos($tableXml, '<w:tr>') === false && strpos($tableXml, '<w:tr ') === false) {
                    $this->tempDocumentMainPart = substr($this->tempDocumentMainPart, 0, $tableOpen)
                        . substr($this->tempDocumentMainPart, $tableClose + strlen('</w:tbl>'));
                    $offset = $tableOpen;
                }
            }
        }
        return $deleted;
    }

    /**
     * Replace the paragraph holding ${$search} with one full-page section per image.
     * Each $pages item: ['path' => PNG file, 'width' => twips, 'height' => twips].
     * Each image is inserted into the page body; map sections use empty headers and footers.
     * Without pages the paragraph is removed. Returns false when the placeholder is not in the document.
     */
    public function replaceWithImagePages($search, array $pages)
    {
        $where = $this->findContainingXmlBlockForMacro($search, 'w:p');
        if (!is_array($where)) {
            return false;
        }
        $xml = $this->tempDocumentMainPart;
        $before = substr($xml, 0, $where['start']);
        $after = substr($xml, $where['end']);
        if (empty($pages)) {
            $this->tempDocumentMainPart = $before . $after;
            return true;
        }

        // The section containing the placeholder is described by the next sectPr in document order.
        $section = $this->findNextSectPr($after);
        if ($section === null) {
            throw new UnexpectedValueException('Template has no section properties');
        }
        $sectPr = $section['xml'];
        $endsDocument = $section['is_body'] && !$this->hasVisibleContent(substr($after, 0, $section['start']));
        $sectionStart = $this->findSectionStart($before);
        $breakBefore = $this->hasVisibleContent(substr($before, $sectionStart))
            ? '<w:p><w:pPr><w:spacing w:before="0" w:after="0" w:line="20" w:lineRule="exact"/><w:rPr><w:sz w:val="2"/></w:rPr>'
                . $sectPr . '</w:pPr></w:p>'
            : '';

        $docPrId = $this->getMaxDocPrId();
        $headerRelationId = $this->addEmptyMapHeader();
        $footerRelationId = $this->addEmptyMapFooter();
        $pagesXml = '';
        $lastSectPr = '';
        foreach (array_values($pages) as $index => $page) {
            $pageHeight = (int)$page['height'];
            $imageRelationId = $this->addMapImageRelation($page['path']);
            $docPrId++;
            $pageSectPr = $this->buildFullPageSectPr(
                $sectPr,
                (int)$page['width'],
                (int)$page['height'],
                $headerRelationId,
                $footerRelationId
            );
            $isLast = $index === count($pages) - 1;
            $pagesXml .= '<w:p><w:pPr><w:spacing w:before="0" w:after="0" w:line="' . $pageHeight . '" w:lineRule="exact"/><w:rPr><w:sz w:val="2"/></w:rPr>'
                . ($isLast && $endsDocument ? '' : $pageSectPr)
                . '</w:pPr><w:r><w:drawing>'
                . $this->buildPageInlineXml($imageRelationId, $docPrId, (int)$page['width'], (int)$page['height'])
                . '</w:drawing></w:r></w:p>';
            $lastSectPr = $pageSectPr;
        }

        if ($endsDocument) {
            // Empty paragraphs after the maps would add a blank page; the body sectPr becomes the last map page.
            $after = $lastSectPr . substr($after, $section['end']);
        }
        $this->tempDocumentMainPart = $before . $breakBefore . $pagesXml . $after;
        return true;
    }

    /**
     * First sectPr in $xml: ['start', 'end', 'xml', 'is_body'] with offsets relative to $xml.
     */
    private function findNextSectPr($xml)
    {
        if (!preg_match('/<w:sectPr(?:\s[^>]*?)?(?:\/>|>.*?<\/w:sectPr>)/s', $xml, $match, PREG_OFFSET_CAPTURE)) {
            return null;
        }
        $start = $match[0][1];
        $end = $start + strlen($match[0][0]);
        $sectPr = $match[0][0];
        if (substr($sectPr, -2) === '/>') {
            $sectPr = substr($sectPr, 0, -2) . '></w:sectPr>';
        }
        $bodyEnd = strpos($xml, '</w:body>', $end);
        return [
            'start' => $start,
            'end' => $end,
            'xml' => $sectPr,
            'is_body' => $bodyEnd !== false && trim(substr($xml, $end, $bodyEnd - $end)) === ''
        ];
    }

    /**
     * Offset in $before where the current section begins: after the last paragraph that ends a section.
     */
    private function findSectionStart($before)
    {
        $lastSectPr = strrpos($before, '</w:sectPr>');
        if ($lastSectPr !== false) {
            $paragraphEnd = strpos($before, '</w:p>', $lastSectPr);
            return $paragraphEnd === false ? strlen($before) : $paragraphEnd + strlen('</w:p>');
        }
        $body = strpos($before, '<w:body>');
        return $body === false ? 0 : $body + strlen('<w:body>');
    }

    /**
     * True when the XML has text, tables, drawings or breaks; empty paragraphs and runs do not count.
     */
    private function hasVisibleContent($xml)
    {
        if (preg_match('/<w:(tbl|drawing|pict|object|br|tab|sym)[\s>\/]/', $xml)) {
            return true;
        }
        return preg_match_all('/<w:t(?:\s[^>]*)?>([^<]*)<\/w:t>/', $xml, $matches) > 0
            && trim(implode('', $matches[1])) !== '';
    }

    /**
     * Copy of the template sectPr for a full-page map with empty headers and footers.
     */
    private function buildFullPageSectPr($sectPr, $widthTwips, $heightTwips, $headerRelationId, $footerRelationId)
    {
        $sectPr = preg_replace([
            '/<w:(type|pgSz|pgMar|pgNumType|titlePg|cols|headerReference|footerReference)\b[^>]*\/>/',
            '/<w:cols\b[^>]*>.*?<\/w:cols>/s'
        ], '', $sectPr);
        $openEnd = strpos($sectPr, '>') + 1;
        $headers = '';
        foreach (['default', 'even', 'first'] as $type) {
            $headers .= '<w:headerReference xmlns:r="' . self::REL_NS . '" w:type="' . $type . '" r:id="' . $headerRelationId . '"/>';
        }
        $footers = '';
        foreach (['default', 'even', 'first'] as $type) {
            $footers .= '<w:footerReference xmlns:r="' . self::REL_NS . '" w:type="' . $type . '" r:id="' . $footerRelationId . '"/>';
        }
        $sectPr = substr($sectPr, 0, $openEnd) . $headers . $footers . substr($sectPr, $openEnd);

        $page = '<w:pgSz w:w="' . $widthTwips . '" w:h="' . $heightTwips . '"'
            . ($widthTwips > $heightTwips ? ' w:orient="landscape"' : '') . '/>'
            . '<w:pgMar w:top="0" w:right="0" w:bottom="0" w:left="0" w:header="0" w:footer="0" w:gutter="0"/>';

        // Schema order: header/footer references and note properties come before pgSz.
        $insertAt = $openEnd;
        if (preg_match_all('/<w:(?:headerReference|footerReference)\b[^>]*\/>|<w:(footnotePr|endnotePr)\b[^>]*>.*?<\/w:\1>/s', $sectPr, $matches, PREG_OFFSET_CAPTURE)) {
            $last = end($matches[0]);
            $insertAt = $last[1] + strlen($last[0]);
        }
        return substr($sectPr, 0, $insertAt) . $page . substr($sectPr, $insertAt);
    }

    private function addEmptyMapHeader()
    {
        $index = 1;
        while ($this->isZipNameTaken('word/gisportal_map_header' . $index . '.xml')) {
            $index++;
        }
        $partName = 'word/gisportal_map_header' . $index . '.xml';
        $this->addedParts[$partName] = true;
        $headerXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<w:hdr xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"><w:p/></w:hdr>';
        $this->zipClass->addFromString($partName, $headerXml);
        $this->tempDocumentContentTypes = str_replace('</Types>', '<Override PartName="/' . $partName
            . '" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.header+xml"/></Types>', $this->tempDocumentContentTypes);

        $mainPart = $this->getMainPartName();
        $relationId = 'rId' . $this->getNextRelationsIndex($mainPart);
        $this->tempDocumentRelations[$mainPart] = str_replace('</Relationships>', '<Relationship Id="' . $relationId . '" Type="'
            . self::REL_NS . '/header" Target="' . basename($partName) . '"/></Relationships>', $this->tempDocumentRelations[$mainPart]);
        return $relationId;
    }

    private function addMapImageRelation($imagePath)
    {
        $mainPart = $this->getMainPartName();
        $relationId = 'rId' . $this->getNextRelationsIndex($mainPart);
        $this->tempDocumentRelations[$mainPart] = str_replace('</Relationships>', '<Relationship Id="' . $relationId . '" Type="'
            . self::REL_NS . '/image" Target="media/' . $this->addPngMedia($imagePath) . '"/></Relationships>', $this->tempDocumentRelations[$mainPart]);
        return $relationId;
    }

    private function addEmptyMapFooter()
    {
        $index = 1;
        while ($this->isZipNameTaken('word/gisportal_map_footer' . $index . '.xml')) {
            $index++;
        }
        $partName = 'word/gisportal_map_footer' . $index . '.xml';
        $this->addedParts[$partName] = true;
        $footerXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<w:ftr xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"><w:p/></w:ftr>';
        $this->zipClass->addFromString($partName, $footerXml);
        $this->tempDocumentContentTypes = str_replace('</Types>', '<Override PartName="/' . $partName
            . '" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.footer+xml"/></Types>', $this->tempDocumentContentTypes);

        $mainPart = $this->getMainPartName();
        $relationId = 'rId' . $this->getNextRelationsIndex($mainPart);
        $this->tempDocumentRelations[$mainPart] = str_replace('</Relationships>', '<Relationship Id="' . $relationId . '" Type="'
            . self::REL_NS . '/footer" Target="' . basename($partName) . '"/></Relationships>', $this->tempDocumentRelations[$mainPart]);
        return $relationId;
    }

    private function buildPageInlineXml($relationId, $docPrId, $widthTwips, $heightTwips)
    {
        // 1 twip = 635 EMU
        $cx = $widthTwips * 635;
        $cy = $heightTwips * 635;
        return '<wp:inline xmlns:wp="http://schemas.openxmlformats.org/drawingml/2006/wordprocessingDrawing" distT="0" distB="0" distL="0" distR="0">'
            . '<wp:extent cx="' . $cx . '" cy="' . $cy . '"/>'
            . '<wp:effectExtent l="0" t="0" r="0" b="0"/>'
            . '<wp:docPr id="' . $docPrId . '" name="Map ' . $docPrId . '"/>'
            . '<wp:cNvGraphicFramePr><a:graphicFrameLocks xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main" noChangeAspect="1"/></wp:cNvGraphicFramePr>'
            . '<a:graphic xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main">'
            . '<a:graphicData uri="http://schemas.openxmlformats.org/drawingml/2006/picture">'
            . '<pic:pic xmlns:pic="http://schemas.openxmlformats.org/drawingml/2006/picture">'
            . '<pic:nvPicPr><pic:cNvPr id="0" name="map' . $docPrId . '.png"/><pic:cNvPicPr/></pic:nvPicPr>'
            . '<pic:blipFill><a:blip xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships" r:embed="' . $relationId . '"/>'
            . '<a:stretch><a:fillRect/></a:stretch></pic:blipFill>'
            . '<pic:spPr><a:xfrm><a:off x="0" y="0"/><a:ext cx="' . $cx . '" cy="' . $cy . '"/></a:xfrm>'
            . '<a:prstGeom prst="rect"><a:avLst/></a:prstGeom></pic:spPr>'
            . '</pic:pic></a:graphicData></a:graphic></wp:inline>';
    }

    private function getMaxDocPrId()
    {
        $max = 0;
        $parts = array_merge([$this->tempDocumentMainPart], $this->tempDocumentHeaders, $this->tempDocumentFooters);
        foreach ($parts as $part) {
            if (preg_match_all('/<wp:docPr\b[^>]*\bid="(\d+)"/', $part, $matches)) {
                $max = max($max, max(array_map('intval', $matches[1])));
            }
        }
        return $max;
    }

    private function isZipNameTaken($name)
    {
        return isset($this->addedParts[$name]) || $this->zipClass->locateName($name) !== false;
    }

    /**
     * Add a PNG to word/media. Returns the media file name.
     */
    private function addPngMedia($path)
    {
        $data = @file_get_contents($path);
        if ($data === false || $data === '') {
            throw new UnexpectedValueException('Map image could not be read');
        }
        $index = 1;
        while ($this->isZipNameTaken('word/media/gisportal_map' . $index . '.png')) {
            $index++;
        }
        $mediaName = 'gisportal_map' . $index . '.png';
        $this->addedParts['word/media/' . $mediaName] = true;
        $this->zipClass->addFromString('word/media/' . $mediaName, $data);

        if (!preg_match('/<Default\s[^>]*Extension="png"/i', $this->tempDocumentContentTypes)) {
            $this->tempDocumentContentTypes = str_replace('</Types>', '<Default Extension="png" ContentType="image/png"/></Types>', $this->tempDocumentContentTypes);
        }
        return $mediaName;
    }
}
