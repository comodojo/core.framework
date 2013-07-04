define(["dojo/_base/lang","dojo/has","dojo/aspect","dojo/dom-construct","dojo/request","dojo/ready","dojo/query",
	"comodojo/Utils","comodojo/Bus","comodojo/Kernel","comodojo/Notification","comodojo/Loader","comodojo/Dialog","comodojo/Error","comodojo/Session","comodojo/Window","comodojo/App"
	"dojo/_base/sniff"],
function(lang,has,aspect,domConstruct,request,ready,query,
	utils,bus,kernel,notification,loader,dialog,error,session,Window,app){

// module:
// 	comodojo/Basic

// define the keys function to object if not implemented
if (!Object.keys) {
	// http://whattheheadsaid.com/2010/10/a-safer-object-keys-compatibility-implementation
	var hasDontEnumBug = true, dontEnums = [
			"toString", "toLocaleString",
			"valueOf", "hasOwnProperty",
			"isPrototypeOf",
			"propertyIsEnumerable", "constructor" ], dontEnumsLength = dontEnums.length;

	for ( var key in {
		"toString" : null
	}) {
		hasDontEnumBug = false;
	}

	Object.keys = function keys(object) {

		if ((typeof object != "object" && typeof object != "function")
				|| object === null) {
			throw new TypeError(
					"Object.keys called on a non-object");
		}

		var keys = [];
		for ( var name in object) {
			if (owns(object, name)) {
				keys.push(name);
			}
		}

		if (hasDontEnumBug) {
			for ( var i = 0, ii = dontEnumsLength; i < ii; i++) {
				var dontEnum = dontEnums[i];
				if (owns(object, dontEnum)) {
					keys.push(dontEnum);
				}
			}
		}
		return keys;
	};
};

// Current user name
// String
lang.setObject("comodojo.userName", comodojoConfig.userName);
	
// Current user role
// String
lang.setObject("comodojo.userRole", comodojoConfig.userRole);

// Current user complete name
// String
lang.setObject("comodojo.userCompleteName", comodojoConfig.userCompleteName);
	
// Current locale
// String
lang.setObject("comodojo.locale", comodojoConfig.phpLocale);
	
// Current locale (i18n)
// String
lang.setObject("comodojo.timezone", false);
	
// Framework version
// Integer
lang.setObject("comodojo.frameworkVersion", 1);
	
// Comodojo version
// String
lang.setObject("comodojo.comodojoVersion", comodojoConfig.version);

// Seed for pids
// String
lang.setObject("comodojo.pidSeed", 100);

// Applications path
// String
lang.setObject("comodojo.applicationsPath", "applications/");
//lang.setObject("comodojo._applicationsPath", "devel/applications/");

var loadScript = function (scr) {
	// summary:
	//		Inject a script into document head
	// scr: String
	//		The script content
	// returns: Object
	//		The newly created script object
	return domConstruct.create("script", {
		language: 'javascript',
		type: 'text/javascript',
		innerHTML: scr
	}, utils.head());			
};
lang.setObject("comodojo.loadScript", loadScript);

var loadScriptFile = function (src, params, callback) {
	// summary:
	//		Inject a script file into document head
	// src: String
	//		The script file
	// params: Object
	//		Array of parameters
	var _params = {
		preventCache: false,
		skipXhr: false,
		forceReload: false
	}
	lang.mixin(_params,params);
	
	if (!lang.isFunction(callback)) { 
		callback = function() { return; }; 
	};
	
	var q = query("script[src='"+src+"']");
	if (q.length != 0) }{
		if (!_params.forceReload) {
			callback();
		}
		else {
			q.forEach(function(node){
				domConstruct.destroy(node);
			});
			// restart loader
			comodojo.loadScriptFile(src, params, callback);
		}
	}
	else {
		if (_params.skipXhr) {
			domConstruct.create("script", {
				language: 'javascript',
				type: 'text/javascript',
				src: src,
				onreadystatechange: function () {
					if (this.readyState == 'complete') {
						lang.hitch(this,callback);
					}
				},
				onload: callback
			}, utils.head());
		}
		else {
			request.get(src,{
				handleAs: 'javascript',
				preventCache: _params.preventCache
			}).then(/*load*/function(data,status){
				callback();
			},/*error*/function(error){
				comodojo.debug('Unable to load script: '+src+' (error was: '+e+')');
			});
		}
	}
				
};
lang.setObject("comodojo.loadScriptFile", loadScriptFile);

var loadCss = function (cssFile) {
	// summary:
	//		Create a css link object into document head
	// cssFile: String
	//		CSS file path
	// returns: Object
	//		The newly created link object
	return domConstruct.create("link", {
		rel: 'stylesheet',
		type: 'text/css',
		href: cssFile
	}, utils.head());
};
lang.setObject("comodojo.loadCss", loadCss);

var debug = function (message) {
	// summary:
	//		Debug to console
	// message: String
	//		Message to debug
	if (comodojoConfig.debug) { console.log(message); }
};
lang.setObject("comodojo.debug", debug);

var debugDeep = function (message) {
	// summary:
	//		Debug to console (deep level)
	// message: String
	//		Message to debug
	if (comodojoConfig.debugDeep) { console.log(message); }
};
lang.setObject("comodojo.debugDeep", debugDeep);

var deprecated = function (module, newModule) {
	// summary:
	//		Raise a standard message in console if deprecated module is called
	// module: String
	//		Deprecated module
	// newModule: String
	//		New module to use instead (if any)
	if (comodojoConfig.debug) {
		if (!newModule) {
			console.warn("Module '"+module+"' is deprecated."); }
		}
		else {
			console.warn("Module '"+module+"' is deprecated. Consider using '"+newModule+"' instead."); }
		}
	}	
};
lang.setObject("comodojo.deprecated", deprecated);
	
var bootstrap = function() {
	// summary:
	//		Bootstrap the comodojo environment (and load applications)
	var bootstrapFile = 'bootstrap.php';
	//var bootstrapFile = 'comodojo/global/bootstrap.php?applicationsDirectory='+comodojo._applicationsPath;
	query("script[src='"+bootstrapFile+"']").forEach(function(s){domConstruct.destroy(s);});
	if (has("webkit") || has("opera") || has("ie")) {
		request.get(bootstrapFile,{
			headers: {'Content-Type':'application/x-javascript'},
			handleAs: 'javascript'
		});
	}
	else {
		loadScriptFile(bootstrapFile);
	}
};

var debugStartup = function() {
	// summary:
	//		Print site state on console during bootstrap (if debug enabled)
	if (comodojoConfig.debug) {
		console.log('*************************************************************************');
		console.log('Debug is on. Showing detailed bootstrap information:');
		console.log('-------------------------------------------------------------------------');
		console.log(' - Debug deep (deep debug for comodojo, module debug for dojo): ' + comodojoConfig.debugDeep);
		console.log(' - Username: ' + comodojo.userName);
		console.log(' - User role Id: ' + comodojo.userRole);
		console.log(' - Defined locale: ' + comodojo.locale);
		console.log(' - Comodojo version: ' + comodojoConfig.version);
		console.log(' - Dojo version loaded: ' + dojo.version);

		if (utils.defined(localized_messages)) { console.log('Localized messages loaded successfully'); }
		else { console.error('Unable to load localized messages'); }

		if (utils.defined(localized_errors)) { console.log('Localized errors loaded successfully'); }
		else { console.error('Unable to load localized errors'); }
		
		console.log('*************************************************************************');
	}
};
	
var loadMessages = function(forceLocale) {
	// summary:
	//		Load localized messages from json file (i18n)
	// forceLocale: String
	//		Force to load specific localization
	request.get('comodojo/i18n/i18n_messages_'+(!forceLocale ? comodojoConfig.phpLocale : forceLocale)+'.json',{
		data: data,
		handleAs: 'json',
		sync: true
	}).then(
		/*load*/function(data) {
			lang.setObject("comodojo.localized_messages", data);
		},
		/*error*/function(error) {
			debug('Failed to load locale, fallback to default. Error: '+error);
			loadMessages('en');
		}
	);
};

var loadErrors = function(forceLocale) {
	// summary:
	//		Load localized errors from json file (i18n)
	// forceLocale: String
	//		Force to load specific localization
	request.get('comodojo/i18n/i18n_errors_'+(!forceLocale ? comodojoConfig.phpLocale : forceLocale)+'.json',{
		data: data,
		handleAs: 'json',
		sync: true
	}).then(
		/*load*/function(data) {
			lang.setObject("comodojo.localized_errors", data);
		},
		/*error*/function(error) {
			debug('Failed to load locale, fallback to default. Error: '+error);
			loadErrors('en');
		}
	);
};

var setShortcuts = function() {
	// summary:
	//		Set shortcut for common namespaces
	lang.setObject("$c", comodojo);
	lang.setObject("$d", dojo);
	lang.setObject("$j", dijit);
	lang.setObject("$x", dojox);
	lang.setObject("$q", function(query, root, listCtor){
		return dojo.query(query, root, listCtor);
	});
};

var startup = function() {
	// summary:
	//		Startup the comodojo environment
	loader.start();
	bus.callEvent('comodojo_startup_start');
	lang.setObject("comodojo.timezone", utils.getUserTimezone());
	loadMessages();
	loadErrors();
	debugStartup();
	setShortcuts();
	bootstrap();
	//ready(function(){
		//comodojo.Loader.stopIn(2000);
		loader.stopIn(2000);
		//comodojo.Bus.callEvent('comodojo_startup_end');
		bus.callEvent('comodojo_startup_end');
	//});
};
lang.setObject("comodojo.startup", startup);

var icons = {

	_iconsPath: {
		16: 'comodojo/icons/16x16/',
		32: 'comodojo/icons/32x32/',
		64: 'comodojo/icons/64x64/'
	},
	
	getIcon: function(icon, dim) {
		_dim = !dim ? 64 : dim;
		return comodojo.icons._iconsPath[_dim]+icon+'.png';
	},
	
	getSelfIcon: function(application, dim) {
		return $c._applicationsPath+application+'/resources/icon_'+dim+'.png';
	},
	
	getLocaleIcon: function(locale) {
		return 'comodojo/icons/i18n/'+(!locale ? $c.locale : locale)+'.png';
	}

};
lang.setObject("comodojo.icons", icons);

var getLocalizedMessage = function(message) {
	if (utils.defined(comodojo.localized_messages[message])) {
		return comodojo.localized_messages[message];
	}
	else {
		return '__?('+message+')?__';
	}
};
lang.setObject("comodojo.getLocalizedMessage", getLocalizedMessage);

var getLocalizedMutableMessage = function(message, params) {
	var _message = getLocalizedMessage(message);
	if (_message != '__?('+message+')?__') {
		return lang.replace(_message,params);
	}
	else {
		return _message;
	}
};
lang.setObject("comodojo.getLocalizedMutableMessage", getLocalizedMutableMessage);

var getLocalizedError = function(error) {
	if (utils.defined(comodojo.localized_errors[error])) {
		return comodojo.localized_errors[error];
	}
	else {
		return '__?('+error+')?__';
	}
};
lang.setObject("comodojo.getLocalizedError", getLocalizedError);

var getLocalizedMutableError = function(error, params) {
	var _message = getLocalizedError(error);
	if (_message != '__?('+error+')?__') {
		return lang.replace(_message,params);
	}
	else {
		return _message;
	}
};
lang.setObject("comodojo.getLocalizedMutableError", getLocalizedMutableError);

var getPid = function() {
	var pid = comodojo.pidSeed;
	comodojo.pidSeed++;
	return 'pid_'+pid;
};
lang.setObject("comodojo.getPid", getPid);

return comodojo;

});