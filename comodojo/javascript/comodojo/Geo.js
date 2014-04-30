define("comodojo/Geo", [
	"dojo/_base/lang",
	"dojo/_base/Deferred",
	/*"dojo/has",*/
	"dojo/_base/declare",
	"dojo/request"
	//"dojo/dom-construct",
	//"dojo/dom-class",
	//"dojo/dom-geometry",
	//"dojo/dom-style",
	//"dijit/layout/BorderContainer",
	//"dijit/layout/ContentPane",
	//"dijit/layout/TabContainer",
	//"dijit/layout/AccordionContainer",
	//"dojox/layout/ExpandoPane",
	//"comodojo/Utils",
	//"gridx/core/model/cache/Sync",
	//"gridx/core/model/cache/Async"
], 
function(
	lang,
	Deferred,
	/*, has*/
	declare,
	request
	//domConstruct,
	//domClass,
	//domGeom,
	//domStyle,
	//BorderContainer,
	//ContentPane,
	//TabContainer,
	//AccordionContainer,
	//ExpandoPane,
	//Utils,
	//SyncCache,
	//AsyncCache
){

	// module:
	// 	comodojo/Geo

var that = false;

var Geo = declare(null,{
	// summary:
	// description:

	// A node (DOM) in wich map will be attached
	// Node
	attachNode: false,

	// Map type
	// String
	mapType: "ROADMAP",

	// Zoom level on the location
	// Int
	zoom: 15,
	
	
	// Autozoom to the location/s marker/s
	// Bool
	autozoom: false,
	
	// Default markers animation (DROP or BOUNCE)
	// String
	markersAnimation: "DROP",

	lat: false,
	lng: false,

	constructor: function(args) {

		declare.safeMixin(this,args);

		node = $c.Utils.nodeOrId(this.attachNode);

		this.map = new google.maps.Map(node,{
			mapTypeId: google.maps.MapTypeId[this.mapType]
		});

		if (this.lat !== false && thig.lng !== false) {
			this.point(lat,lng);
		}
		
		this.markers = [];

	},

	point: function(latitude, longitude, zoom) {

		var ll = new google.maps.LatLng(latitude,longitude);
		this.map.setCenter(ll);
		this.map.setZoom(!zoom ? this.zoom : zoom);

	},

	geocode: function(address) {

		var coder = new google.maps.Geocoder();

		coder.geocode({'address':address},lang.hitch(this,"geocodeCallback"));

	},

	geocodeCallback: function(results, status) {

		console.log(results);

		if (status == "OK") {
			this.point(results[0].geometry.location.lat(),results[0].geometry.location.lng());
		}
		else {
			$c.Error.minimal('Error: '+status);
		}

	},

	reverseGeocode: function(latitude, longitude) {

	},

	addMarkerFromGeocoder: function(address) {

		var coder = new google.maps.Geocoder();

		coder.geocode({'address':address},lang.hitch(this,"addMarkerCallback"));

	},

	addMarkerCallback: function(results, status) {

		console.log(results);

		if (status == "OK") {
			var marker = new google.maps.Marker({
				position: results[0].geometry.location,
				map: this.map
			});
		}
		else {
			$c.Error.minimal('Error: '+status);
		}

	},

	removeMarker: function() {

	},

	getMarker: function() {

	},

	getMarkers: function() {

	},

	reset: function() {

	}

});

return Geo;	

});