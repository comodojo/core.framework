define(["dojo/_base/lang","dojo/request","dojo/dom-construct"],
function(lang,request,domConstruct){

// module:
// 	comodojo/Config

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
			console.warn("Module '"+module+"' is deprecated.");
		}
		else {
			console.warn("Module '"+module+"' is deprecated. Consider using '"+newModule+"' instead.");
		}
	}	
};
lang.setObject("comodojo.deprecated", deprecated);
	
var getPid = function() {
	var pid = comodojo.pidSeed;
	comodojo.pidSeed++;
	return 'pid_'+pid;
};
lang.setObject("comodojo.getPid", getPid);

var loadMessages = function(forceLocale) {
	// summary:
	//		Load localized messages from json file (i18n)
	// forceLocale: String
	//		Force to load specific localization
	request.get('comodojo/i18n/i18n_messages_'+(!forceLocale ? comodojoConfig.phpLocale : forceLocale)+'.json',{
		//data: data,
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
		//data: data,
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
	}, document.getElementsByTagName('head').item(0));
};
lang.setObject("comodojo.loadCss", loadCss);

loadMessages();
loadErrors();

});