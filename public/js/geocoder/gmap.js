/**
 * The global variable name should be gmap
 */
var gmap = {
	/**
	 * PARAMETERS
	 */
	MAP_ID : 'map',
	INIT_LAT: 47,
	INIT_LNG: 3,
	INIT_ZOOM: 5,
	/**
	 * Properties
	 */
	map : null,
	/**
	 * DOM items
	 */
	dom : {
		get: function(id) {
			return document.getElementById(id);
		}
	},
	/**
	 * Init function
	 */
	init: function() {
		gmap.geocoder.init();
		gmap.controls.init();
		if(gmap.oninit) {
			gmap.oninit();
		}
		gmap.page.onresize();
	},
	/**
	 * Load Google Map
	 */
	load : function() {
		gmap.init();
		if(GBrowserIsCompatible()) {
			gmap.map = new GMap2(gmap.dom.get(gmap.MAP_ID));
			gmap.controls.LargeMap.enable();
			gmap.controls.MapType.enable();
			gmap.controls.Scrolling.enable();
			gmap.center.set(gmap.INIT_LAT, gmap.INIT_LNG, gmap.INIT_ZOOM);
			if(gmap.onload) {
				gmap.onload();
			}
		}
		else {
			alert("Votre navigateur n'est pas compatible Google Map !");
		}
	},
	/**
	 * Geocoder object
	 */
	geocoder: {
		object: null,
		init: function() {
			gmap.geocoder.object = new GClientGeocoder();
		},
		nocache: function() {
			gmap.geocoder.object.reset();
			gmap.geocoder.object.setCache(null);
		},
		country: function(lang) {
			gmap.geocoder.object.setBaseCountryCode(lang);
		},
		request: function(address, handler) {
			gmap.geocoder.object.getLocations(address, handler);
		}
	},
	/**
	 * Listener
	 */
	listener: {
		add: function(source, event, handler) {
			GEvent.addListener(source, event, handler);
		},
		remove: function(handle) {
			GEvent.removeListener(handle);
		},
		clear: function(source,  event) {
			GEvent.clearListeners(source,  event);
		}
	},
	/**
	 * Zoom
	 */
	zoom: {
		get: function() {
			return gmap.map.getZoom();
		},
		set: function(zoom) {
			var center = gmap.map.getCenter();
			gmap.map.setCenter(center, zoom);
			gmap.center.zoom = zoom;
		}
	},
	/**
	 * center
	 */
	center: {
		lat: 0,
		lng: 0,
		zoom: 1,
		get: function() {
			return gmap.map.getCenter();
		},
		set: function(lat, lng, zoom) {
			if(!zoom) {
				zoom = gmap.map.getZoom();
			}
			gmap.map.setCenter(new GLatLng(lat, lng), zoom);
			gmap.center.lat = lat;
			gmap.center.lng = lng;
			gmap.center.zoom = zoom;
		},
		reset: function() {
			gmap.map.setCenter(new GLatLng(gmap.center.lat, gmap.center.lng), gmap.center.zoom);
		}
	},
	/**
	 * Controls
	 */
	controls : {
		LocalSearch : {
			enable  : function() { gmap.map.enableGoogleBar(); },
			disable : function() { gmap.map.disableGoogleBar(); }
		},
		Scrolling   : {
			enable  : function() { gmap.map.enableScrollWheelZoom(); },
			disable : function() { gmap.map.disableScrollWheelZoom(); }
		},
		LargeMap    : {
			enable  : function() { gmap.controls.add(gmap.controls.objects.LargeMap); },
			disable : function() { gmap.controls.remove(gmap.controls.objects.LargeMap); }
		},
		SmallMap    : {
			enable  : function() { gmap.controls.add(gmap.controls.objects.SmallMap); },
			disable : function() { gmap.controls.remove(gmap.controls.objects.SmallMap); }
		},
		Scale       : {
			enable  : function() { gmap.controls.add(gmap.controls.objects.Scale); },
			disable : function() { gmap.controls.remove(gmap.controls.objects.Scale); }
		},
		OverviewMap : {
			enable  : function() { gmap.controls.add(gmap.controls.objects.OverviewMap); },
			disable : function() { gmap.controls.remove(gmap.controls.objects.OverviewMap); },
			hide    : function() { gmap.controls.objects.OverviewMap.hide(); },
			show    : function() { gmap.controls.objects.OverviewMap.show(); }
		},
		MapType     : {
			enable  : function() { gmap.controls.add(gmap.controls.objects.MapType); },
			disable : function() { gmap.controls.remove(gmap.controls.objects.MapType); }
		},
		objects: {
			LargeMap : null,
			SmallMap : null,
			Scale : null,
			OverviewMap : null,
			MapType : null
		},
		init: function() {
			gmap.controls.objects.LargeMap = new GLargeMapControl();
			gmap.controls.objects.SmallMap = new GSmallMapControl();
			gmap.controls.objects.Scale = new GScaleControl();
			gmap.controls.objects.OverviewMap = new GOverviewMapControl();
			gmap.controls.objects.MapType = new GMapTypeControl();
		},
		add    : function(control) { gmap.map.addControl(control); },
		remove : function(control) { gmap.map.removeControl(control); }
	},
	/**
	 * Error codes
	 */
	errcode: function(code) {
		var txt = '';
		switch(code) {
			case G_GEO_BAD_KEY:             txt = 'BAD_KEY'; break;
			case G_GEO_BAD_REQUEST:         txt = 'BAD_REQUEST'; break;
			case G_GEO_MISSING_ADDRESS:     txt = 'MISSING_ADDRESS '; break;
			case G_GEO_SERVER_ERROR:        txt = 'SERVER_ERROR'; break;
			case G_GEO_SUCCESS:             txt = 'SUCCESS'; break;
			case G_GEO_TOO_MANY_QUERIES:    txt = 'TOO_MANY_QUERIES'; break;
			case G_GEO_UNAVAILABLE_ADDRESS: txt = 'UNAVAILABLE_ADDRESS'; break;
			case G_GEO_UNKNOWN_ADDRESS:     txt = 'UNKNOWN_ADDRESS'; break;
			case G_GEO_UNKNOWN_DIRECTIONS:  txt = 'UNKNOWN_DIRECTIONS'; break;
		}
		return txt;
	},
	/**
	 * ajax object
	 */
	ajax : {
		send: function(URI, onEnd) {
			var Ajax = this.getXMLHttpRequest();
			Ajax.open('GET', URI, true);
			Ajax.onreadystatechange = function() {
				if(Ajax.readyState == 4) {
					onEnd(Ajax);
				}
			}
			Ajax.send(null);
		},
		getXMLHttpRequest: function() {
			var xhr = null;
			var user_agent = navigator.userAgent;
			if(window.XMLHttpRequest) {
				xhr = new XMLHttpRequest();
			}
			else if(!/MSIE 4/i.test(user_agent)) {
				if(/MSIE 5/i.test(user_agent)) {
					xhr = new ActiveXObject('Microsoft.XMLHTTP');
				}
				else {
					xhr = new ActiveXObject('Msxml2.XMLHTTP');
				}
			};
			return xhr;
		},
		compatible: function() {
			var Ajax = this.getXMLHttpRequest();
			return Ajax !== null;
		}
	},
	/**
	 * Page parameters
	 */
	page: {
		height: function() {
			if(self.innerHeight) return self.innerHeight;
			if(document.documentElement && document.documentElement.clientHeight) return document.documentElement.clientHeight;
			if(document.body) return document.body.clientHeight;
			return 0;
		},
		width: function() {
			if(self.innerWidth) return self.innerWidth;
			if(document.documentElement && document.documentElement.clientWidth) return document.documentElement.clientWidth;
			if(document.body) return document.body.clientWidth;
			return 0;
		},
		onload: function() {
			gmap.load();
		},
		onunload: function() {
			GUnload();
		},
		onresize: function() {
			var height = gmap.page.height();
			// add your code here
		},
		set_handlers: function() {
			window.onresize = gmap.page.onresize;
			window.onload = gmap.page.onload;
			window.onunload = gmap.page.onunload;
		}
	},
	/**
	 * events
	 */
	oninit: null,
	onload: null
}