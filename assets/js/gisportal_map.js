// gisportal map related functions

function extractStringFromObject(objName, string) {

	if (!string) {
		return null;
	}

	if (string.indexOf(objName) == -1) {
		return string;
	}

	var ret = '';
	var start = string.indexOf('(') + 1;
	var end = string.indexOf(')');
	ret = '[' + string.substring(start, end) + ']';

	return ret;
}

function setBaseLayer(lay, projection) {

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

			// $.ajax(definition.capabilitiesUrl).then(function (response) {
			// 	var result = new ol.format.WMTSCapabilities().read(response);
			// 	var options = ol.source.WMTS.optionsFromCapabilities(result, {
			// 		layer: definition.layer,
			// 		matrixSet: definition.matrixSet,
			// 		requestEncoding: definition.requestEncoding,
			// 		style: definition.style,
			// 		projection: projection,
			// 		format: definition.format
			// 	});
			//
			// 	layOl = new ol.layer.Tile({
			// 		visible: true,
			// 		//name: lay.name,
			// 		source: new ol.source.WMTS(options)
			// 	});
			//
			// 	//layer from
			// 	layOl.name = lay.name;
			// 	// add background as base layer
			// 	GP.map.olMap.addLayer(layOl);
			// });

			var matrixIds = [];
			var resolutions = [];
			var serverResolutions = eval(definition.serverResolutions);
			var projectionExtent = projection.getExtent();
			var size = ol.extent.getWidth(projectionExtent) / 256;
			var num = serverResolutions !== undefined ? serverResolutions.length : eval(definition.numZoomLevels);
			var origins;

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

			if (definition.origins !== undefined) {
				origins = JSON.parse(definition.origins);
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
					projection: projection,
					format: definition.format,
					tileGrid: new ol.tilegrid.WMTS({
						extent: eval(extent),
						resolutions: resolutions,
						matrixIds: matrixIds,
						origins: origins
					})
				})
			});

			break;

		case 'WMS' :

			definition = $.parseJSON(lay.definition);

			if (definition.singleTile) {
				layOl = new ol.layer.Image({
					visible: true,
					source: new ol.source.ImageWMS({
						url: definition.url,
						params: definition.params
					})
				});
			} else {
				//tiled wms layer
				layOl = new ol.layer.Tile({
					visible: true,
					//name: lay.name,
					source: new ol.source.TileWMS({
						url: definition.url,
						params: definition.params
					})
				});
			}

			break;

	}

	if (layOl) {

		layOl.set('name', lay.name);
		layOl.set('title', lay.display_name);
		layOl.set('type', lay.base ? 'base' : null);

		return layOl;
	}
	return null;
}

if ((GP.map.crs != 'EPSG:3857') && (GP.map.crs != 'EPSG:4326')) {
	proj4.defs(GP.map.crs, GP.map.proj4);
	ol.proj.proj4.register(proj4);
}
var projection = new ol.proj.Projection({
	code: GP.map.crs ? GP.map.crs : 'EPSG:3857',
	units: CustomProj[GP.map.crs].units,
	extent: CustomProj[GP.map.crs].extent,
	axisOrientation: CustomProj[GP.map.crs].yx === false ? 'enu' : 'neu'
});

GP.map.olMap = new ol.Map({
	target: 'map',
	controls: ol.control.defaults({
		zoomOptions: {zoomOutTipLabel: 'Pomanjšaj', zoomInTipLabel: 'Povečaj'},
		attributionOptions: {tipLabel: 'Izdelava'}
	}).extend([
		new ol.control.Rotate({
			duration: 0,
			tipLabel: 'Ponastavi usmerjenost karte na sever'
		}),
		new ol.control.FullScreen({
			tipLabel: 'Preklopi celozaslonski način'
		}),
		new ol.control.ScaleLine()
	]),
	layers: [],
	view: new ol.View({
		projection: projection,
		minResolution: 0.01
		//extent: projection.getExtent()	//this is view restriction to extent
	})
});

if (GP.map.showLayerSwitcher) {
	GP.map.olMap.addControl(new ol.control.LayerSwitcher());
}

if (GP.map.overview > '') {
	var lay = setBaseLayer(GP.map.overview, projection);

	if (lay) {
		//tole je iz ol-ext, vendar samo zaradi tega jih ne uporabljam
		// var overviewMapControl = new ol.control.Overview({
		//     layers: [lay],
		//     projection: projection,
		//     align: 'bottom-right',
		//     //minZoom: 4.5,
		//     maxZoom: 8
		// });
		var overviewMapControl = new ol.control.OverviewMap({
			layers: [lay],
			rotateWithView: false,
			tipLabel: 'Pregledna karta'
		});
		GP.map.olMap.addControl(overviewMapControl);
	}
}

if (GP.map.showCoords) {
	GP.map.olMap.addControl(new ol.control.MousePosition({
		target: document.getElementById('mouse-position'),
		undefinedHTML: '&nbsp;',
		className: 'custom-mouse-position help-block',
		coordinateFormat: ol.coordinate.createStringXY(2)
	}))
}

//set center or extent
if (GP.map.startCenter) {
	GP.map.olMap.getView().setCenter(ol.proj.fromLonLat(GP.map.startCenter, GP.map.crs));
	GP.map.olMap.getView().setZoom(GP.map.startZoom);
} else {
	GP.map.olMap.getView().fit(GP.map.startExtent);
}

var baseLayers = GP.map.baselayers();

for (var i = 0; i < baseLayers.length; i++) {
	var el = baseLayers[i];

	//create ol layer object, first time only
	var lay = setBaseLayer(el, projection);
	if (lay) {
		//fix visibility for baselayers
		if (i > 0 && el.base) {
			lay.setVisible(false);
		}
		GP.map.olMap.addLayer(lay);
	}
}

if (GP.map.showProjection) {
	$('#projection').html(projection.getCode());
}

