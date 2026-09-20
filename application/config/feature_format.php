<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
| Feature attribute formatting used when rendering feature reports.
*/

$config['feature_format_no_data_value'] = '';
$config['feature_format_true_text'] = 'da';
$config['feature_format_false_text'] = 'ne';
$config['feature_format_files_field'] = 'FILES';
$config['feature_format_decimals'] = 3;
$config['feature_format_decimal_separator'] = ',';
$config['feature_format_thousands_separator'] = '.';
$config['feature_format_date'] = 'Y-m-d';
$config['feature_format_datetime'] = 'Y-m-d H:i:s';

/*
| Per field formatting. Mirrors Eqwc.settings.fieldTemplates from the client.
|   newName     - label shown instead of the attribute name, used as written here
|   template    - value template, supports %VALUE%, %PROJECT% and %CLIENT%
|   url         - lookup URL prefix, the raw-urlencoded value is appended to it
|   urlTemplate - full URL template (used instead of url), supports %VALUE%
|                 (raw-urlencoded), %PROJECT% and %CLIENT%
|   urlValue    - when true, the raw value itself is the full URL (used
|                 instead of url/urlTemplate), e.g. when the field stores a link
|   image       - image URL template, supports %VALUE%, %PROJECT% and %CLIENT%
|   links       - list of ['template' => ..., 'url'|'urlTemplate'|'urlValue' => ...]
|                 to render several hyperlinks (one per line) for a single value
|                 instead of one template/url pair; takes precedence over template/url/image.
*/
$config['feature_format_field_templates'] = [

    'CREATEDBY' => ['newName' => 'USTVARIL'],
    'CREATED' => ['newName' => 'USTVARJENO'],
    'MODIFIEDBY' => ['newName' => 'SPREMENIL'],
    'MODIFIED' => ['newName' => 'SPREMENJENO'],

    'SIF_VRSTE' => ['template' => '%VALUE%', 'url' => 'https://geo-portal.si/wsgi/lookup.wsgi?table=lookup.sif_vrste&code='],
    'CC_KLAS' => ['template' => '%VALUE%', 'url' => 'https://geo-portal.si/wsgi/lookup.wsgi?table=lookup.cc_klas&code='],
    'NAT_YX' => ['template' => '%VALUE%', 'url' => 'https://geo-portal.si/wsgi/lookup.wsgi?table=lookup.nat_yx&code='],
    'NAT_Z' => ['template' => '%VALUE%', 'url' => 'https://geo-portal.si/wsgi/lookup.wsgi?table=lookup.nat_z&code='],

    'VIR' => ['template' => '%VALUE%', 'url' => 'https://geo-portal.si/wsgi/lookup.wsgi?table=lookup.vir&code='],

    'ATR1 - MATERIAL' => ['template' => '%VALUE%', 'url' => 'https://geo-portal.si/wsgi/lookup.wsgi?table=lookup.atr1&category=3101&code='],
    'ATR2 - LEGA OBJEKTA' => ['template' => '%VALUE%', 'url' => 'https://geo-portal.si/wsgi/lookup.wsgi?table=lookup.atr2&category=3111&code='],
    'ATR4 - VRSTA' => ['template' => '%VALUE%', 'url' => 'https://geo-portal.si/wsgi/lookup.wsgi?table=lookup.atr4&category=3101&code='],

    'ATR1 - LEGA TRASE' => ['template' => '%VALUE%', 'url' => 'https://geo-portal.si/wsgi/lookup.wsgi?table=lookup.atr1&category=6121&code='],
    'ATR2 - VRSTA OMREŽJA' => ['template' => '%VALUE%', 'url' => 'https://geo-portal.si/wsgi/lookup.wsgi?table=lookup.atr2&category=6121&code='],

    'TIP_IJSVO' => ['template' => '%VALUE%', 'url' => 'https://geo-portal.si/wsgi/lookup.wsgi?table=lookup.ijsvo_tip_prikljucka&code='],
    'ID_VS' => ['template' => '%VALUE%', 'url' => 'https://geo-portal.si/wsgi/lookup.wsgi?table=lookup.id_vs&code='],

    'MAT_ST' => ['template' => '%VALUE%', 'url' => 'https://www.ajpes.si/prs/rezultati.asp?maticna='],
    'MAT_GJS' => ['template' => '%VALUE%', 'url' => 'https://www.ajpes.si/prs/rezultati.asp?maticna='],
    'MATICNA' => ['template' => '%VALUE%', 'url' => 'https://www.ajpes.si/prs/rezultati.asp?maticna='],

    // KN
    'KO_ID' => ['newName' => 'ŠIFRA K.O.'],
    'IMEKO' => ['newName' => 'IME K.O.'],
    'ST_STAVBE' => ['newName' => 'STAVBA'],
    'LETO_IZGRADNJE' => ['newName' => 'LETO IZGRADNJE'],
    'LETO_OBNOVE_FASADE' => ['newName' => 'LETO OBNOVE FASADE'],
    'LETO_OBNOVE_STREHE' => ['newName' => 'LETO OBNOVE STREHE'],
    'STEVILO_ETAZ' => ['newName' => 'ŠT. ETAŽ'],
    'STEVILO_STANOVANJ' => ['newName' => 'ŠT. STANOVANJ'],
    'STEVILO_POSLOVNIH_PROSTOROV' => ['newName' => 'ŠT. POSLOVNIH PROSTOROV'],
    'BRUTO_TLORISNA_POVRSINA' => ['newName' => 'BRUTO TLORISNA POVRŠINA [m2]'],
    'IMA_HS' => ['newName' => 'IMA HIŠNO ŠT.'],

    'ST_PARCELE' => ['newName' => 'PARCELA'],
    'POVRSINA' => ['newName' => 'POVRŠINA [m2]'],
    'UREJENA' => ['newName' => 'UREJENA'],
    'ST_LASTNIKOV' => ['newName' => 'ŠT. LASTNIKOV'],
    'LASTNISTVO' => ['newName' => 'LASTNIŠTVO'],

    'OC_ZAN' => ['newName' => 'OCENA ZANESLJIVOSTI'],

    'SIFRA_POST' => ['newName' => 'NIVO PODTALNICE', 'links' => [
        ['template' => 'GRAF 7 dni', 'urlTemplate' => 'https://www.arso.si/vode/podatki/podzem_amp/%VALUE%_g_7.html'],
        ['template' => 'GRAF 30 dni', 'urlTemplate' => 'https://www.arso.si/vode/podatki/podzem_amp/%VALUE%_g_30.html'],
        ['template' => 'TABELA 30 dni', 'urlTemplate' => 'https://www.arso.si/vode/podatki/podzem_amp/%VALUE%_t_30.html'],
    ]],
    'SIFRA_INT' => ['newName' => 'MERILNO MESTO', 'template' => 'OPIS', 'urlTemplate' => 'https://meteo.arso.gov.si/uploads/probase/www/hidro/watercycle/text/sl/observation_sites/piezometers_wells/%VALUE%.pdf'],

    'PA_MID' => ['newName' => 'POVEZAVE', 'links' => [
        ['template' => 'JAVNI VPOGLED', 'url' => 'https://geo-portal.si/modules/eprostor/open/1001/'],
        ['template' => 'PDF IZPIS', 'url' => 'https://geo-portal.si/modules/eprostor/report/1001/'],
        ['template' => 'VREDNOST', 'url' => 'https://vrednotenje.gov.si/EV_JV/#/parcela_'],
    ]],
    'ST_MID' => ['newName' => 'POVEZAVE', 'template' => 'JAVNI VPOGLED', 'url' => 'https://geo-portal.si/modules/eprostor/open/1002/'],

    'STATUS_OM' => ['template' => '%VALUE%', 'url' => 'https://geo-portal.si/wsgi/lookup.wsgi?table=lookup.status_odjemnega_mesta&code='],
    'STATUS_SM' => ['template' => '%VALUE%', 'url' => 'https://geo-portal.si/wsgi/lookup.wsgi?table=lookup.status_svetilnega_mesta&code='],
    'BARCODE' => ['template' => 'SITECO', 'urlValue' => true],

    'LEGA_TRASE_EK' => ['template' => '%VALUE%', 'url' => 'https://geo-portal.si/wsgi/lookup.wsgi?table=lookup.lega_trase_ek&code='],

    'LINK360' => ['newName' => 'SLIKA 360', 'template' => 'SLIKA 360', 'urlValue' => true],

    'PORTAL_LINK' => ['template' => 'POVEZAVA', 'urlValue' => true],

    'GRADNJEID' => ['template' => '%VALUE%', 'url' => 'https://operativateh.ugbb.net/geodeti/view.php?ID='],

    'LGS_IMG0' => ['image' => 'https://geo-portal.si/uploads/ko_ilib/%PROJECT%/images/%VALUE%'],
    'LGS_IMG1' => ['image' => 'https://geo-portal.si/uploads/ko_ilib/%PROJECT%/images/%VALUE%'],
    'LGS_IMG2' => ['image' => 'https://geo-portal.si/uploads/ko_ilib/%PROJECT%/images/%VALUE%'],
    'LGS_IMG3' => ['image' => 'https://geo-portal.si/uploads/ko_ilib/%PROJECT%/images/%VALUE%'],

    'LGS_IMG0_VDV' => ['image' => 'https://geo-portal.si/uploads/ko_ilib/ko_ilib_vodovod/images/%VALUE%'],
    'LGS_IMG1_VDV' => ['image' => 'https://geo-portal.si/uploads/ko_ilib/ko_ilib_vodovod/images/%VALUE%'],
    'LGS_IMG2_VDV' => ['image' => 'https://geo-portal.si/uploads/ko_ilib/ko_ilib_vodovod/images/%VALUE%'],
    'LGS_IMG3_VDV' => ['image' => 'https://geo-portal.si/uploads/ko_ilib/ko_ilib_vodovod/images/%VALUE%'],

    'STATUS_PRODAJE' => ['template' => '%VALUE%', 'url' => 'https://geo-portal.si/wsgi/lookup.wsgi?table=lookup.status_prodaje&code='],
];
