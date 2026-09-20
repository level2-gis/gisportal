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
