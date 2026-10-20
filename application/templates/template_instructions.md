# Feature Report Template

The default template is `feature_report.docx`. It is used by the synchronous
`GET /documents/feature_report` endpoint and produces one report for one selected
feature.

## Metadata placeholders

Use these PHPWord placeholders in the document:

- `${title}` - Report title
- `${description}` - Project description
- `${project_name}` - QGIS project name
- `${layer_name}` - Selected layer name
- `${feature_count}` - Always `1` for a successful feature report
- `${bbox}` - Server-calculated map BBOX
- `${generated_date}` - Generation timestamp
- `${generated_by}` - Authenticated username
- `${map_image}` - Optional server-generated WMS map image

## Selected feature placeholders

Use these placeholders for the selected feature:

- `${feature_id}` - Feature identifier
- `${feature_layer}` - Layer returned by QGIS
- `${feature_type}` - Geometry type
- `${feature_properties}` - Formatted feature attributes for legacy templates

Feature properties can also be bound by exact attribute name in a
selected-feature report. For example, `${parcel_number}` is replaced when the
QGIS response contains a `parcel_number` attribute and the template contains
that variable. Metadata names take precedence over attributes with the same
name.

## Feature properties table

To render each feature property in its own row, add one data row containing:

```text
${property_name} | ${property_value}
```

PHPWord clones that row once per property and replaces the column name and
value independently. An empty property collection is rendered as
`No properties available`.

## Feature table

To render the feature fields in a table, add one data row containing the four
placeholders below. PHPWord clones the row before replacing its values:

```text
${feature_id}
${feature_layer}
${feature_type}
${feature_properties}
```

The table header should be outside this data row. A report contains one
selected feature, so no all-features or multi-layer table is needed.

## Map image

Place `${map_image}` in the document where the map should appear. When the
request contains `include_map=false`, the placeholder is
left empty.

Clients may request configured external WMS layers with repeated
`external_layers[]` query parameters, for example
`external_layers[]=dof025_latest`. Only layers configured by the server are
included in the map.

## Template rules

1. Keep templates as `.docx` files under `application/templates/`.
2. The server selects the configured template; clients cannot submit a file path.
3. Keep PHPWord placeholders intact in a single Word run where possible.
4. The server controls the WMS URL, BBOX, dimensions, CRS, and layer name.

# Location Report Template

The location report template is `location_report.docx`
(`phpword_location_report_template_name` in `config/phpword.php`). It is used by
`GET|POST /documents/location_report` and lists every feature intersecting one
map location in each requested layer of the current project.

## Request parameters

The location is the same map context the web client sends for GetFeatureInfo:

- `project` - Current session project name
- `QUERY_LAYERS` (or `query_layers`) - Comma list or array of QGIS layer ids
  and/or layer-tree group names (or group WMS short names) to query with
  GetFeatureInfo. The former `layers` / `LAYERS` aliases are no longer accepted.
  Groups are expanded
  on the server to all their descendant layers, in project tree order; group
  names are not accepted when the project sets `WMSSkipNameForGroup`. Restricted
  or unknown layers/groups return 403. Duplicate layers are queried once. The
  `wms_report_max_layers` limit applies after group expansion.
  Layers that are not identifiable (QGIS layer flag) or have no geometry are
  skipped and listed in `${skipped_layers}` instead of failing the request; if
  no queryable layer remains the request returns 422.
- `bbox` (or `BBOX`) - `minx,miny,maxx,maxy` of the client map in the project CRS
- `width`, `height` - Client map size in pixels (max `wms_location_report_max_size`)
- `i`, `j` - Clicked pixel inside the map
- `crs` - Optional; must equal the project CRS
- `client` - Optional client name for field formatting
- `address` (or `location_address`) - Optional single-line text already known by
  the client (e.g. the geocoded address of the location). No server lookup is
  made. Line breaks and control characters are collapsed to spaces; at most 255
  characters.
- `maps` - Optional array of maps (POST recommended), see [Maps](#maps).

The server sends one GetFeatureInfo request for all layers with
`FEATURE_COUNT=wms_location_report_feature_count` per layer and optional
`FI_*_TOLERANCE` values from `wms_location_report_tolerances`.

## Maps

Each map is rendered by QGIS Server `GetPrint` (PNG) from a print layout of the
project and placed on its own full page in the document:

```text
maps[0][template]=A4 portrait        (required, layout name)
maps[0][layers]=roads,Parcels        (layer ids and/or group names; WMS order, first is drawn at the bottom)
maps[0][external_layers]=osm         (optional, drawn below `layers`)
maps[0][title]=Overview              (optional, used in ${skipped_maps})
maps[1][template]=A3 landscape
maps[1][layers]=...
```

- The layout must exist in the project and must not be listed in the project's
  WMS restricted layouts, and every layer/group must be visible to the user;
  otherwise the whole request returns 403. Raster and non-identifiable layers
  are allowed. At least one layer per map is required and the
  `wms_report_max_layers` limit applies per map.
- At most `wms_location_report_max_maps` maps per request.
- The first map item of the layout (`map0`) gets `EXTENT` = request `bbox`
  (QGIS fits the extent into the map item, so its aspect ratio should be close
  to the client map), `LAYERS`, and a marker at the clicked location
  (`wms_print_highlight_symbol`, empty string disables it).
- Maps are rendered in parallel at `wms_print_dpi`, with `wms_print_timeout`,
  `wms_print_max_image_size` and `wms_print_max_pixels` limits. A map that
  fails, or whose layout page is larger than 22 inches (Word page limit), is
  skipped and listed in `${skipped_maps}`; the report is still generated.

Layout rules:

- One page at the wanted paper size and orientation; the document page gets
  the layout page size (mixed portrait/landscape maps are allowed).
- The PNG covers the whole page edge to edge. Keep ~5 mm empty around layout
  content, because printers usually cannot print the page edge.
- The template header and footer (with the template page margins) are drawn
  over the map, so leave room for them in the layout or keep them small.

Template: put `${maps}` alone in its own paragraph where the map pages should be
inserted (usually at the end). Map pages start on a new page; content after
`${maps}` continues on a new page with the original page settings. Without maps
the paragraph is removed.

## Metadata placeholders

`${title}`, `${description}`, `${project_name}`, `${crs}`, `${bbox}`,
`${location_x}`, `${location_y}`, `${layer_count}`, `${feature_count}` (total),
`${generated_date}`, `${generated_by}`, `${skipped_layers}` (comma list of
layers that were not queried, with reason), `${map_count}` (rendered maps),
`${skipped_maps}` (maps that could not be rendered, with reason).

`${location_address}` is optional: it contains the `address` request value.
When no address is sent, every table row containing the placeholder is removed
(so an "Address" row in a metadata table disappears); anywhere else the
placeholder is replaced with an empty string.

## Layer and feature blocks

Each block marker must be alone in its own paragraph:

```text
${layer_block}
${layer_group}
${layer_name} (${layer_feature_count})
${layer_empty}
${feature_block}
Feature ${feature_id}
| ${property_name} | ${property_value} |   <- one table row, cloned per attribute
${/feature_block}
${/layer_block}
```

- The layer block is repeated for every queried layer, in request order (group
  members in project tree order).
- `${layer_group}` is the layer's group path in the project tree
  (`Parent / Child`), empty for top-level layers.
- `${layer_empty}` is `No features` for a layer without hits and empty otherwise.
- Inside the feature block, `${feature_layer}` and `${feature_type}` are also
  available, and `${feature_properties}` can replace the properties table with
  a single text value.
- Other available layer placeholder: `${layer_id}`.

## Text-only fallback

A template without `${layer_block}` can contain `${location_results}`, which is
replaced by a plain-text listing of all layers (with group headers), features,
attributes, and skipped layers.
