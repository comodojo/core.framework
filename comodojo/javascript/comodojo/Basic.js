define(["dojo/_base/lang","dojo/has","dojo/aspect","dojo/dom-construct","dojo/request","dojo/ready","dojo/query",
	"comodojo/Utils","comodojo/Bus","comodojo/Kernel","comodojo/Notification","comodojo/Loader","comodojo/Dialog","comodojo/Error","comodojo/Session","comodojo/Window","comodojo/App",
	"dojo/_base/sniff"],
function(lang,has,aspect,domConstruct,request,ready,query,
	utils,bus,kernel,notification,loader,dialog,error,session,Window,app){

// module:
// 	comodojo/Basic

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
		skipXhr: true,
		forceReload: false,
		sync: false
	}
	lang.mixin(_params,params);
	
	if (!lang.isFunction(callback)) { 
		callback = function() { 
			comodojo.debugDeep('Loaded script file '+src+', no callback defined');
		}; 
	};

	var q = query("script[src='"+src+"']");
	if (q.length != 0) {
		if (!_params.forceReload) {
			console.log('skip touchcallback');
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
				preventCache: _params.preventCache,
				sync: _params.sync
			}).then(/*loadfunction(){
				console.log('touchcallback');
				callback();
			}*/callback,/*error*/function(error){
				comodojo.debug('Unable to load script: '+src+' (error was: '+e+')');
			});
		}
	}
				
};
lang.setObject("comodojo.loadScriptFile", loadScriptFile);

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
		loadScriptFile(bootstrapFile,{sync:true},comodojo.App.autostart);
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

		if (utils.defined(comodojo.localized_messages)) { console.log('Localized messages loaded successfully'); }
		else { console.error('Unable to load localized messages'); }

		if (utils.defined(comodojo.localized_errors)) { console.log('Localized errors loaded successfully'); }
		else { console.error('Unable to load localized errors'); }
		
		console.log('*************************************************************************');
	}
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

var loadDefaultCSS = function() {
	comodojo.loadCss('comodojo/CSS/environment.css');
	comodojo.loadCss('comodojo/CSS/window.css');
	comodojo.loadCss('comodojo/javascript/dojox/layout/resources/ResizeHandle.css');
};

var startup = function() {
	// summary:
	//		Startup the comodojo environment
	loader.start();
	loadDefaultCSS();
	bus.callEvent('comodojo_startup_start');
	lang.setObject("comodojo.timezone", utils.getUserTimezone());
	//loadMessages();
	//loadErrors();
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

var getLocalizedMessage = function(message, messageObj) {
	if (!messageObj) {
		messageObj = comodojo.localized_messages;
	}
	if (utils.defined(messageObj[message])) {
		return messageObj[message];
	}
	else {
		return '__?('+message+')?__';
	}
};
lang.setObject("comodojo.getLocalizedMessage", getLocalizedMessage);

var getLocalizedMutableMessage = function(message, params, messageObj) {
	if (!messageObj) {
		messageObj = comodojo.localized_messages;
	}
	if (utils.defined(messageObj[message])) {
		return lang.replace(messageObj[message],params);
	}
	else {
		return '__?('+message+')?__';
	}
};
lang.setObject("comodojo.getLocalizedMutableMessage", getLocalizedMutableMessage);

var getLocalizedError = function(error, errorObj) {
	if (!errorObj) {
		errorObj = comodojo.localized_errors;
	}
	if (utils.defined(errorObj[error])) {
		return errorObj[error];
	}
	else {
		return '__?('+error+')?__';
	}
};
lang.setObject("comodojo.getLocalizedError", getLocalizedError);

var getLocalizedMutableError = function(error, params, errorObj) {
	if (!errorObj) {
		errorObj = comodojo.localized_errors;
	}
	if (utils.defined(errorObj[error])) {
		return lang.replace(errorObj[error],params);
	}
	else {
		return '__?('+error+')?__';
	}
};
lang.setObject("comodojo.getLocalizedMutableError", getLocalizedMutableError);

var loadComponent = function(componentName, params) {
		
	if (lang.isObject(params)) {
		comodojo.Bus._modules[componentName] = params;
	}
	return comodojo.loadScriptFile('comodojo/javascript/resources/'+componentName+'.js',{sync:true});
	
};
lang.setObject("comodojo.loadComponent", loadComponent);



return comodojo;

});