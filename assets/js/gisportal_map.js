// gisportal map related functions

function extractStringFromObject(objName, string) {

	if(!string) {
		return null;
	}

	if (string.indexOf(objName)==-1) {
		return string;
	}

	var ret = '';
	var start = string.indexOf('(')+1;
	var end = string.indexOf(')');
	ret = '['+string.substring(start, end)+']';

	return ret;
}

function initViewer() {

	$('#projection').html(olMap.getView().getProjection().code_);

	var baseLayers = GP.map.baselayers();

	for (var i=0;i<baseLayers.length;i++) {
		var el = baseLayers[i];

		//create ol layer object, first time only, visibility false
		setBaseLayer(el);

	}
}

function setBaseLayer(lay) {

	//taken with changes from /gisapp/client_mobile/src/map.js
	var layOl, definition;

	switch (lay.type) {
		case 'OSM' :
			layOl = new ol.layer.Tile({
				visible: true,
				//name: lay.name,
				source: new ol.source.OSM
			});

			break;

		case 'XYZ' :
			definition = $.parseJSON(lay.definition);
			layOl = new ol.layer.Tile({
				visible: true,
				//name: lay.name,
				source: new ol.source.XYZ(definition)
			});

			break;

		case 'Bing' :
			definition = $.parseJSON(lay.definition);
			layOl = new ol.layer.Tile({
				visible: true,
				//name: lay.name,
				preload: Infinity,
				source: new ol.source.BingMaps({
					key: definition.key,
					imagerySet: definition.imagerySet
				})
			});

			break;

		case 'WMTS' :

			definition = $.parseJSON(lay.definition);
			//first load capabilities
			//not used because certain capabilities does not return tileGrid extent
			//$.ajax(definition.capabilitiesUrl).then(function(response) {
			//    var result = new ol.format.WMTSCapabilities().read(response);
			//    var options = ol.source.WMTS.optionsFromCapabilities(result, {
			//        layer: definition.layer,
			//        matrixSet: definition.matrixSet,
			//        requestEncoding: definition.requestEncoding,
			//        style: definition.style,
			//        projection: Config.map.projection,
			//        format: definition.format
			//    });
			//
			//    var layOl3 = new ol.layer.Tile({
			//        visible: visible,
			//        //name: lay.name,
			//        source: new ol.source.WMTS(options)
			//    });
			//
			//    layOl3.name = lay.name;
			//
			//    // add background as base layer
			//    Map.map.getLayers().insertAt(0, layOl3);
			//});

			var matrixIds = [];
			var resolutions = [];
			var serverResolutions = eval(definition.serverResolutions);
			var projectionExtent = olMap.getView().getProjection().getExtent();
			var size = ol.extent.getWidth(projectionExtent) / 256;
			var num = serverResolutions !== undefined ? serverResolutions.length : eval(definition.numZoomLevels);

			//FIX for removing extent from OL2 definition
			var extent = extractStringFromObject("OpenLayers", definition.maxExtent);

			for (var z = 0; z < num; ++z) {
				matrixIds[z] = z;
				resolutions[z] = size / Math.pow(2, z);
			}

			if (definition.matrixIds !== undefined) {
				matrixIds = eval(definition.matrixIds);
			}

			if (serverResolutions !== undefined) {
				resolutions = serverResolutions;
			}

			layOl = new ol.layer.Tile({
				visible: true,
				opacity: definition.opacity,
				source: new ol.source.WMTS({
					url: definition.url,
					layer: definition.layer,
					matrixSet: definition.matrixSet,
					requestEncoding: definition.requestEncoding,
					style: definition.style,
					projection: olMap.getView().getProjection(),
					format: definition.format,
					tileGrid: new ol.tilegrid.WMTS({
						extent: eval(extent),
						resolutions: resolutions,
						matrixIds: matrixIds
					})
				})
			});

			break;

		case 'WMS' :

			definition = $.parseJSON(lay.definition);

			//tiled wms layer
			layOl = new ol.layer.Tile({
				visible: true,
				//name: lay.name,
				source: new ol.source.TileWMS({
					url: definition.url,
					params: definition.params
				})
			});

			break;

	}

	//this is layer id, must be same as layer name from database!
	layOl.name = lay.name;

	// add background as base layer
	olMap.addLayer(layOl);			//getLayers().insertAt(0, layOl3);

}
