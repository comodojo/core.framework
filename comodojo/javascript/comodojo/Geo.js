define("comodojo/Geo", [
	"dojo/_base/lang",
	"dojo/_base/Deferred",
	/*"dojo/has",*/
	"dojo/_base/declare",
	//"dojo/request",
	"dojo/dom-construct",
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
	//request,
	domConstruct
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
			this.point(new google.maps.LatLng(this.lat,thig.lng));
		}
		
		this.markers = [];

		that = this;

	},

	point: function(LatLng) {

		//var ll = new google.maps.LatLng(latitude,longitude);
		this.map.setCenter(LatLng);
		//this.map.setZoom(!zoom ? this.zoom : zoom);

	},

	multipoint: function(LatLngs) {

		var i=0, bounds=new google.maps.LatLngBounds();

		for(var i in LatLngs) {
			bounds.extend(LatLngs[i]);
		}
		this.map.fitBounds(bounds);
		
	},

	zoom: function(zoom) {
		this.map.setZoom(!zoom ? this.zoom : zoom);
	},

	latlng: function(lat, lng) {
		return new google.maps.LatLng(lat,lng);
	},

	geocode: function(address, multiple) {

		this.geocode_deferred = new Deferred();

		var coder = new google.maps.Geocoder();

		if (multiple) {
			coder.geocode({'address':address},lang.hitch(this,"geocodeMultiCallback"));
		}
		else {
			coder.geocode({'address':address},lang.hitch(this,"geocodeCallback"));
		}

		return this.geocode_deferred.promise;

	},

	geocodeCallback: function(results, status) {

		if (status == "OK") {

			if (results.length != 1) {
				var content = [];
				for (var i in results) {
					content.push({
						id: results[i],
						label: results[i].formatted_address
					});
				}
				$c.Dialog.select("Select an address",'',content,this.geocodeResolv);
			}
			else {
				this.geocode_deferred.resolve(results);
			}

		}
		else {
			//$c.Error.minimal('Error: '+status);
			this.geocode_deferred.reject(status);
		}

	},

	geocodeResolv: function(result) {

		this.geocode_deferred.resolve(result);
		
	},

	geocodeMultiCallback: function(results, status) {

		if (status == "OK") {
			this.geocode_deferred.resolve(results);
		}
		else {
			this.geocode_deferred.reject(status);
		}

	},

	reverseGeocode: function(LatLng, multiple) {

		this.reversegeocode_deferred = new Deferred();

		var coder = new google.maps.Geocoder();

		if (multiple) {
			coder.geocode({'latLng': LatLng},lang.hitch(this,"reverseGeocodeMultiCallback"));
		}
		else {
			coder.geocode({'latLng': LatLng},lang.hitch(this,"reverseGeocodeCallback"));
		}

		return this.reversegeocode_deferred.promise;

	},

	reverseGeocodeCallback: function(results, status) {

		if (status == "OK") {

			if (results.length != 1) {
				var content = [];
				for (var i in results) {
					content.push({
						id: results[i],
						label: results[i]
					});
				}
				$c.Dialog.select("Select an address",'',content,this.reverseGeocodeResolv);
			}
			else {
				this.reversegeocode_deferred.resolve(results);
			}

		}
		else {
			//$c.Error.minimal('Error: '+status);
			this.reversegeocode_deferred.reject(status);
		}

	},

	reverseGeocodeResolv: function(result) {

		this.reversegeocode_deferred.resolve(result);
		
	},
	
	reverseGeocodeMultiCallback: function(result, status) {

		if (status == "OK") {
			this.reversegeocode_deferred.resolve(results);
		}
		else {
			//$c.Error.minimal('Error: '+status);
			this.reversegeocode_deferred.reject(status);
		}

	},

	marker: function(LatLng, properties, name) {

		var prop = {
			title: false,
			icon: null,
			draggable: false,
			animated: false,
			bubble: false,
			bubbleOpened: false
		};

		lang.safeMixin(prop,properties);

		if (!name) {
			name = (Math.random() + 1).toString(36).substring(5);
		}

		var marker = new google.maps.Marker({
			position: LatLng,
			map: this.map,
			title: prop.title,
			icon: prop.icon,
			draggable: prop.draggable,
			animation: prop.animated !== false ? google.maps.Animation[this.markersAnimation] : null
		});

		this.markers[name] = {
			latlng: LatLng,
			properties: prop,
			marker: marker
		};

		if (prop.bubble !== false) {

			var bubbleContent = domConstruct.create("div",{
				style: "padding: 10px; overflow:hidden;",
				innerHTML: markerProps.bubbleContent
			});
			var bubble = new google.maps.InfoWindow({ content: bubbleContent });
			google.maps.event.addListener(marker, 'click', function() {bubble.open(this.map,marker);});
		
			if (prop.bubbleOpened) {
				bubble.open(this.map,marker);
			}

		}

		if (this.markers.length != 1 && this.autozoom) {
			var i=0, LatLngs=[];
			for (i in this.markers) {
				LatLngs.push(this.markers[i].latlng);
			}
			this.multipoint(LatLngs);
		}
		else {
			this.point(LatLng);
		}

		return name;

	},

	markers: function(markers) {
		var i=0, marks=[];

		for (i in markers) {
			marks.push(this.marker(markers[i].LatLng, markers[i].properties, markers[i].name));
		}

		return marks;

	},

	removeMarker: function(name) {

		if ($c.Utils.defined(this.markers[name])) {
			this.markers[name].marker.setMap(null);
			delete this.markers[name];
		}

	},

	getMarker: function(name) {

		if ($c.Utils.defined(this.markers[name])) {
			return this.markers[name];
		}
		else {
			return false;
		}

	},

	getMarkers: function() {

		return this.markers;

	},

	reset: function() {

		var i=0;

		for (var i in this.markers) {
			this.markers[i].marker.setMap(null);
		}

		if (this.lat !== false && thig.lng !== false) {
			this.point(new google.maps.LatLng(this.lat,thig.lng));
		}

	}

});

return Geo;	

});