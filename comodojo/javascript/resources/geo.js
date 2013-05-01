//Preload GMaps starting from user params
comodojo.loadScriptFile("http://maps.google.com/maps/api/js?callback=$c.tmp.gmapsApiOnLoad&sensor="+comodojo.bus._modules.geo.useSensor+"&language="+$c.locale,{skipXhr:true});
comodojo.tmp.gmapsApiOnLoad = function() {
	comodojo.bus.callEvent("gmapsApiOnLoad");
	comodojo.geo._isApiLoaded = true;
};

/** 
 * geo.js
 * 
 * Bring simple google maps into comodojo...
 *
 * @class
 * @package		Comodojo ClientSide Core Packages
 * @author		comodojo.org
 * @copyright	2011 comodojo.org (info@comodojo.org)
 * 
 */
comodojo.geo = function(params) {
	
	/**
	 * The node the map will be pushed in
	 * 
	 * @default	false	(will raise an error)
	 */
	this.attachNode = false;
	
	/**
	 * The map types - NOT USED YET
	 * 
	 * @default	"G_DEFAULT_MAP_TYPES"
	 */
	this.mapType = "ROADMAP";
	
	/**
	 * The default location will be: Pescocostanzo, Abruzzo, Italy!
	 */
	this.defaultLatitude = "41.889610";
	this.defaultLongitude = "14.064989";
	
	/**
	 * Zoom level on the location
	 * 
	 * @default	15	in most cases give a good point of view
	 */
	this.zoomLevel = 15;
	
	/**
	 * Autozoom to the location/s marker/s
	 * 
	 * @default	false
	 */
	this.autozoom = false;
	
	/**
	 * Default markers animation (DROP or BOUNCE)
	 * 
	 * @default	DROP
	 */
	this.markersAnimation = "DROP"
	
	/**
	 * The pid (unique) that map will have
	 * 
	 * @default	pid string
	 */
	this._pid = comodojo.getPid();
	
	/**
	 * Callback function raised when map's ready 
	 * 
	 * @default	false
	 */
	this.callback = false;
	
	dojo.mixin(this,params);
	
	this._isApiLoaded = $d.isFunction('google.maps.Map') ? true : false;
	this._markers = [];
	this._progressiveMarker = 1;
	this._currentMarkerIndex = false;
	
	$c.tmp["gmapsHandler_"+this._pid] = this;

	this.tmbpitwLatitude = "41.291453";
	this.tmbpitwLongitude = "13.259972";
	
	/**
	 * Build the map!
	 * 
	 * @return	object/gmap	The required map
	 */
	this.loadMap = function() {
		if (!$c.geo._isApiLoaded) {
			$c.debug('geo not loaded, skipping 500');
			setTimeout("$c.tmp['gmapsHandler_"+this._pid+"'].loadMap();",500);
		}
		else {
			$c.debug('geo loaded, building map');
			if (!this.attachNode) {
				comodojo.debug("geo API failure: where should I append your map?!? please define attachNode first...");
				return false;
			}
			this.map = new google.maps.Map(this.attachNode,{mapTypeId: google.maps.MapTypeId[this.mapType]});
			this._geoCoder = new google.maps.Geocoder();
			if ($d.isFunction(this.callback)) {
				this.callback(this.map);
			}
			this._pointMapFromPureCoords(this.defaultLatitude,this.defaultLongitude);
		}
		return this.map;
	};

	this.setZoom = function(level){
		this.zoomLevel = parseInt(level);
		this.map.setZoom(this.zoomLevel);
	};

	this.pointMap = function(to) {
		if (!dojo.isObject(to)) {
			comodojo.debug("geo API failure: cannot point map without address or coords");
			return false;
		}
		else if (comodojo.isDefined(to.address)){
			this._pointMapFromGeoCoder(to.address);
		}
		else if (comodojo.isDefined(to.lat) && comodojo.isDefined(to.lng)) {
			this._pointMapFromPureCoords(to.lat,to.lng);
		}
		else {
			comodojo.debug("geo API failure: cannot point map without address or coords");
			return false;
		}
	};

	this._pointMapFromPureCoords = function(latitude,longitude) {
		var latLong = new google.maps.LatLng(latitude,longitude);
		this.map.setCenter(latLong);
		this.map.setZoom(this.zoomLevel);
	};

	this._pointMapFromGeoCoder = function(address) {
		var x = this._geoCoder.geocode({'address':address},dojo.hitch(this,"_pointMapFromGeoCoderCallback"));
	};
	
	this._pointMapFromGeoCoderCallback = function(result,status) {
		if (status == "OK") {
			this.map.setCenter(result[0].geometry.location);
			this.map.setZoom(this.zoomLevel);
		}
		else {
			comodojo.debug("Cannot find any suitable location for your address, maps will be pointed on default coords.");
			this._pointMapFromPureCoords(this.defaultLatitude,this.defaultLongitude);
		}
	};

	this.addMarker = function(properties) {
		/*if (!$c.geo._isApiLoaded) {
			//$c.debug('geo not loaded, skipping 500');
			setTimeout(function() {
				myself.addMarker(properties);
			},500);
			return;
		}*/
		var _properties = {};
		_properties.lat = false;
		_properties.lng = false;
		_properties.address = false;
		_properties.title = false;
		_properties.icon = null;
		_properties.isDraggable = false;
		_properties.isAnimated = false;
		_properties.hasBubble = false;
		_properties.isBubbleOpened = false;
		_properties.bubbleContent = false;
		dojo.mixin(_properties,properties);
		
		this._currentMarkerIndex = this._markers.push(_properties);
		
		if (_properties.address !== false) {
			this._addMarkerFromGeoCoder(_properties.address);
		}
		else if (_properties.lat !== false && _properties.lng !== false){
			this._addMarkerFromPureCoords(_properties.lat, _properties.lng);
		}
		else {
			comodojo.debug('Invalid marker properties, skipping marker.');
		}
	};

	this._addMarkerFromPureCoords = function(lat,lng) {
		var markerProps = this._markers[this._currentMarkerIndex-1];
		var _latLng = new google.maps.LatLng(markerProps.lat,markerProps.lng)
		var marker = new google.maps.Marker({
			position: _latLng,
			map: this.map,
			title: markerProps.title,
			icon: markerProps.icon,
			draggable: markerProps.isDraggable,
			animation: markerProps.isAnimated ? null : google.maps.Animation[this.markersAnimation]
	    });
	    this._markers[this._currentMarkerIndex-1]._latLng = _latLng;
	    this._markers[this._currentMarkerIndex-1]._marker = marker;
	    this._markers[this._currentMarkerIndex-1]._progressiveMarker = this._getProgressiveMarker();
	    if (markerProps.hasBubble) {
	    	var bubbleContent = dojo.create("div",{style: "padding: 10px; overflow:hidden;", innerHTML: markerProps.bubbleContent});
			var bubble = new google.maps.InfoWindow({ content: bubbleContent });
			google.maps.event.addListener(marker, 'click', function() {bubble.open(this.map,marker);});
			if (this.isBubbleOpened) { bubble.open(this.map,marker); }
	    }
	    this._zoom(_latLng);
		//return marker;
	};

	this._addMarkerFromGeoCoder = function(address) {
		this._geoCoder.geocode({'address':address},dojo.hitch(this,"_addMarkerFromGeoCoderCallback"));
	};
	
	this._addMarkerFromGeoCoderCallback = function(result, status) {
		if (status == "OK") {
			var markerProps = this._markers[this._currentMarkerIndex-1];
			var marker = new google.maps.Marker({
				position: result[0].geometry.location,
				map: this.map,
				title: markerProps.title,
				icon: markerProps.icon,
				draggable: markerProps.isDraggable,
				animation: markerProps.isAnimated ? null : google.maps.Animation[this.markersAnimation]
		    });
		    this._markers[this._currentMarkerIndex-1]._latLng = result[0].geometry.location;
		    this._markers[this._currentMarkerIndex-1]._marker = marker;
		    this._markers[this._currentMarkerIndex-1]._progressiveMarker = this._getProgressiveMarker();
		    if (markerProps.hasBubble) {
		    	var bubbleContent = dojo.create("div",{style: "padding: 10px; overflow:hidden;", innerHTML: markerProps.bubbleContent});
				var bubble = new google.maps.InfoWindow({ content: bubbleContent });
				google.maps.event.addListener(marker, 'click', function() {bubble.open(this.map,marker);});
				if (this.isBubbleOpened) { bubble.open(this.map,marker); }
		    }
		    this._zoom(result[0].geometry.location);
		}
		else {
			comodojo.debug("Cannot add a marker from address provided, skypping.");
			this._markers.splice(this._currentMarkerIndex-1,1);
		}
	};

	this._zoom = function(latLng) {
		if (!this.autozoom) {
			this.map.setCenter(latLng);
			this.map.setZoom(this.zoomLevel);
		}
		else {
			if (this._markers.length == 1) {
				this.map.setCenter(latLng);
				this.map.setZoom(this.zoomLevel);
			}
			else {
				var bounds = new google.maps.LatLngBounds();
				for(var i in this._markers) {
	    			bounds.extend(this._markers[i]._latLng);
	    		}
				this.map.fitBounds(bounds);
				this.map.setZoom(this.zoomLevel);
			}
		}
	};

	this._getProgressiveMarker = function() {
		var pm = this._progressiveMarker;
		this._progressiveMarker++;
		return pm;
	};

	this.getMarkers = function() {
		return this._markers;
	};
	
	this.resetMap = function() {
		for (var i in this._markers) {
			this._markers[i]._marker.setMap(null);
    	}
		this._pointMapFromPureCoords(this.defaultLatitude,this.defaultLongitude);
	};
	
	this.removeMarker = function(progressiveMarker) {
		for (var i in this._markers) {
			if (this._markers[i]._progressiveMarker == progressiveMarker) {
				this._markers[i]._marker.setMap(null);
				this._markers.splice(i-1,1);
				return true;
			}
			else {
				continue;
			}
		}
		return false;
	};
	
	this.loadMap();

};