define("comodojo/Geo", [
	"dojo/_base/lang",
	"dojo/_base/Deferred",
	"dojo/_base/declare",
	"dojo/dom-construct"
], 
function(
	lang,
	Deferred,
	declare,
	domConstruct
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
	mapzoom: 15,
	
	// Autozoom to the location/s marker/s
	// Bool
	autozoom: false,

	// Try to get user location at startup
	// Bool
	locateuser: false,
	
	// Default markers animation (DROP or BOUNCE)
	// String
	markersAnimation: "DROP",

	lat: false,
	lng: false,

	constructor: function(args) {

		declare.safeMixin(this,args);

		this.active_markers = [];

		this.geocode_multiple_results = [];

		this.reverse_geocode_multiple_results = [];

		node = $c.Utils.nodeOrId(this.attachNode);

		this.map = new google.maps.Map(node,{
			mapTypeId: google.maps.MapTypeId[this.mapType]
		});

		that = this;

		this.startup_location();

	},

	startup_location: function() {

		if (this.lat !== false && this.lng !== false) {

			this.point(new google.maps.LatLng(this.lat,this.lng));
			this.zoom();

		}
		else if (this.locateuser) {
			
			this.locate().then(function(position) {
				that.point(position.latlng);
			},function(error) {
				$c.Error.generic(false,$c.getLocalizedMessage('10042'),error);
			});
			this.zoom();

		}
		else {
			this.point(new google.maps.LatLng(41.9100711,12.5359979));
			this.zoom(5);
		}

	},

	point: function(LatLng) {

		this.map.setCenter(LatLng);

	},

	multipoint: function(LatLngs) {

		var i=0, bounds=new google.maps.LatLngBounds();

		for(var i in LatLngs) {
			bounds.extend(LatLngs[i]);
		}
		this.map.fitBounds(bounds);
		
	},

	zoom: function(zoom) {
		this.mapzoom = !zoom ? this.mapzoom : zoom;
		this.map.setZoom(this.mapzoom);
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
				this.geocode_multiple_results = results;
				for (var i in results) {
					content.push({
						id: i,
						label: results[i].formatted_address
					});
				}
				$c.Dialog.select($c.getLocalizedMessage('10043'),'',content,this.geocodeResolve);
			}
			else {
				this.geocode_deferred.resolve(results[0]);
			}

		}
		else {
			this.geocode_deferred.reject(status);
		}

	},

	geocodeResolve: function(result) {

		that.geocode_deferred.resolve(that.geocode_multiple_results[result]);
		
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
				this.reverse_geocode_multiple_results = results;
				for (var i in results) {
					content.push({
						id: i,
						label: results[i]
					});
				}
				$c.Dialog.select($c.getLocalizedMessage('10043'),'',content,this.reverseGeocodeResolv);
			}
			else {
				this.reversegeocode_deferred.resolve(results);
			}

		}
		else {
			this.reversegeocode_deferred.reject(status);
		}

	},

	reverseGeocodeResolv: function(result) {

		that.reversegeocode_deferred.resolve(that.reverse_geocode_multiple_results[result]);
		
	},
	
	reverseGeocodeMultiCallback: function(result, status) {

		if (status == "OK") {
			this.reversegeocode_deferred.resolve(results);
		}
		else {
			this.reversegeocode_deferred.reject(status);
		}

	},

	marker: function(LatLng, properties, name) {

		var prop = {
			title: '',
			icon: null,
			draggable: false,
			animated: false,
			bubble: false,
			bubbleOpened: false
		};

		lang.mixin(prop,properties);

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

		this.active_markers[name] = {
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

		if (this.active_markers.length != 1 && this.autozoom) {
			var i=0, LatLngs=[];
			for (i in this.active_markers) {
				LatLngs.push(this.active_markers[i].latlng);
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
			marks.push(this.marker(markers[i][0], markers[i][1], markers[i][2]));
		}

		return marks;

	},

	removeMarker: function(name) {

		if ($c.Utils.defined(this.active_markers[name])) {
			this.active_markers[name].marker.setMap(null);
			delete this.active_markers[name];
		}

	},

	getMarker: function(name) {

		if ($c.Utils.defined(this.active_markers[name])) {
			return this.active_markers[name];
		}
		else {
			return false;
		}

	},

	getMarkers: function() {

		return this.active_markers;

	},

	locate: function(params) {
		
		var options = {
			enableHighAccuracy: true,
			timeout: 5000,
			maximumAge: 0
		}

		lang.mixin(options,params);

		this.locate_deferred = new Deferred();
		
		if(navigator.geolocation) {
			
			navigator.geolocation.getCurrentPosition(lang.hitch(this,"locateResolve"),lang.hitch(this,"locateError"),options);

		} else {

			setTimeout(function() {
				that.locateError(false);
			},100);

		}

		return this.locate_deferred.promise;

	},

	locateResolve: function(position) {

		var crd = position.coords;

		this.locate_deferred.resolve({
			latlng: this.latlng(crd.latitude,crd.longitude),
			lat: crd.latitude,
			lng: crd.longitude,
			accuracy: crd.accuracy
		});

	},

	locateError: function(err) {

		this.locate_deferred.reject(!err ? "Browser does not support geolocation" : err.name);

	},

	reset: function(skiplocation) {

		var i=0;

		for (var i in this.active_markers) {
			this.active_markers[i].marker.setMap(null);
		}

		if (!skiplocation) {
			this.startup_location();
		}

	}

});

return Geo;	

});