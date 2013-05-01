/** 
 * comodojo.js
 * 
 * The base class of entirely comodojo little world;
 *
 * Namespace "comodojo" (alias $c) is the base object that could be invoked widely.
 * It extends some javascript/dojo functions and govern the loading of modules.
 * 
 * @package		Comodojo ClientSide Core Packages
 * @author		comodojo.org
 * @copyright	2012 comodojo.org (info@comodojo.org)
 * @version		__CURRENT_VERSION__
 * @license		GPL Version 3
 */

var comodojo = {

	/**
	 * Logged-in user's name
	 *
	 * @param	{String}
	 */
	userName: comodojoConfig.userName,
	
	/**
	 * Logged-in user's role
	 *
	 * @param	{String}
	 */
	userRole: comodojoConfig.userRole,
	
	/**
	 * Logged-in user's complete name
	 *
	 * @param	{String}
	 */
	userCompleteName: comodojoConfig.userCompleteName,
	
	/**
	 * Current locale
	 *
	 * @param	{String}
	 */
	locale: comodojoConfig.phpLocale,
	
	/**
	 * Current timezone (will be populated during environment init)
	 *
	 * @param	{Int}
	 */
	timezone: false,
	
	/**
	 * Version of framework
	 *
	 * @constant {Int}
	 * @default 1 (for comodojo 1.0 branch)
	 */
	frameworkVersion: 1,
	
	/**
	 * Current comodojo version
	 *
	 * @constant {String}
	 */
	comodojoVersion: comodojoConfig.version,
	
	/**
	 * comodojo unified bus
	 *
	 * @class
	 */
	bus: {
		/**
		 * @private
		 */
		_connections: {},
		/**
		 * @private
		 */
		_triggers: {},
		/**
		 * @private
		 */
		_locks: {},
		/**
		 * @private
		 */
		_events: {},
		/**
		 * @private
		 */
		_modules: {},
		/**
		 * @private
		 */
		_timestamps: {},
		/**
		 * @private
		 */
		_registeredApplications: {},
		/**
		 * @private
		 */
		_runningApplications: [],
		/**
		 * @private
		 */
		_siteState: null,
		
		/**
		 * Add a trigger to the bus
		 * 
		 * @function
		 * @param	{String}			trigger	The trigger name
		 * @param	{String|Function}	functionString	The function called on each trigger start
		 * @param	{Int}				time	Time interval
		 */
		addTrigger: function(trigger, functionString, time){
			comodojo.bus._triggers[trigger] = setInterval(functionString, time);
		},
		
		/**
		 * Remove a trigger from the bus
		 * 
		 * @function
		 * @param	{String}	trigger	The trigger name
		 */
		removeTrigger: function(trigger){
			clearInterval(comodojo.bus._triggers[trigger]);
		},
		
		/**
		 * Add an invocable connection (event related) to the bus 
		 * 
		 * @function
		 * @see		addEvent
		 * @params	{String}		connection	The connection name
		 * @params	{String}		event 		The event connected
		 * @params	{Function}		func		The function to connect to event
		 */
		addConnection: function(connection, event, func){
			comodojo.bus.addEvent(event);
			comodojo.bus._connections[connection] = dojo.connect(comodojo.bus._events, event, func);
		},
		
		/**
		 * Remove a connection (event related) from the bus 
		 * 
		 * @params	{String}	connection	The connection name
		 */
		removeConnection: function(connection){
			dojo.disconnect(comodojo.bus._connections[connection]);
		},
		
		/**
		 * Add a timestamp reference to the bus 
		 * 
		 * @function
		 * @params	{String}		_service	The service reference (see kernel part)
		 * @params	{String}		_selector	The selector reference (see kernel part)
		 * @return	{Int}			timestamp	The timestamp recorded
		 */
		addTimestamp: function(_service, _selector) {
			if (!_service || !_selector) {return false;}
			_timestamp = Math.round(new Date().getTime()/1000);
			if (!dojo.isArray(comodojo.bus._timestamps[_service])) {
				comodojo.bus._timestamps[_service] = [];
			}
			comodojo.bus._timestamps[_service][_selector] = _timestamp;
			return _timestamp;
		},
		
		/**
		 * Update a timestamp reference 
		 * 
		 * @function
		 * @params	string		_service	The service reference (see kernel part)
		 * @params	string		_selector	The selector reference (see kernel part)
		 * @return	int			timestamp	The timestamp recorded
		 */
		updateTimestamp: function(_service, _selector) {
			return comodojo.bus.addTimestamp(_service, _selector);
		},
		
		/**
		 * Remove a timestamp reference from the bus 
		 * 
		 * @function
		 * @params	string		_service	The service reference (see kernel part)
		 * @params	string		_selector	The selector reference (see kernel part)
		 * @return	bool					The operation end state
		 */
		removeTimestamp: function(_service, _selector) {
			if (!dojo.isString(_service) || !dojo.isString(_selector) || !dojo.isArray(comodojo.bus._timestamps[_service]) || isNaN(comodojo.bus._timestamps[_service][_selector])) {return false;}
			else {
				comodojo.bus._timestamps[_service][_selector] = false;
				return true;
			}
		},
		
		/**
		 * Get a timestamp from reference 
		 * 
		 * @function
		 * @params	string		_service	The service reference (see kernel part)
		 * @params	string		_selector	The selector reference (see kernel part)
		 * @return	int			timestamp	The timestamp
		 */
		getTimestamp: function(_service, _selector) {
			if (!dojo.isString(_service) || !dojo.isString(_selector) || !dojo.isArray(comodojo.bus._timestamps[_service]) || isNaN(comodojo.bus._timestamps[_service][_selector])) {return false;}
			else {
				return comodojo.bus._timestamps[_service][_selector];
			}
		},
		
		/**
		 * Get a timestamp from reference and update it at same time
		 * 
		 * @function
		 * @params	string		_service	The service reference (see kernel part)
		 * @params	string		_selector	The selector reference (see kernel part)
		 * @return	int			timestamp	The timestamp (old reference)
		 */
		getTimestampAndUpdate: function(_service, _selector) {
			if (!dojo.isString(_service) || !dojo.isString(_selector) || !dojo.isArray(comodojo.bus._timestamps[_service]) || isNaN(comodojo.bus._timestamps[_service][_selector])) {return false;}
			else {
				var value = comodojo.bus._timestamps[_service][_selector];
				comodojo.bus.addTimestamp(_service,_selector); 
				return value;
			}
		},
		
		/**
		 * Add an event to the bus 
		 * 
		 * @function
		 * @params	string		event	The event name
		 */
		addEvent: function(event) {
			if (!dojo.isFunction(comodojo.bus._events[event])) {
				comodojo.bus._events[event] = function(){
					return;
				};
				comodojo.debugDeep("Added new event: " + event + ".");
			}
			else {
				comodojo.debugDeep("Event: " + event + " is already registered.");
			}
		},
		
		/**
		 * Remove an event from the bus 
		 * 
		 * @function
		 * @params	string		event	The event name
		 */
		removeEvent: function(event) {
			if (!dojo.isFunction(comodojo.bus._events[event])) {
				comodojo.debugDeep("Cannot remove event: " + event + " - event is not registered.");
			}
			else {
				delete comodojo.bus._events.event;
				comodojo.debugDeep("Removed event: " + event + ".");
			}
		},
		
		/**
		 * Call an event on the bus (and so execute functions connected) 
		 * 
		 * @function
		 * @params	string		event	The event name
		 */
		callEvent: function(event){
			if (dojo.isFunction(comodojo.bus._events[event])) {
				comodojo.debugDeep("Called event: " + event + ", raising...");
				comodojo.bus._events[event]();
			}
			else {
				comodojo.debugDeep("Sorry, event you've called (" + event + ") is not declared, skipping...");
			}
		}
	},
	
	/**
	 * Global object to store global variables 
	 * 
	 * @var object
	 */
	globals: {
		_pidSeed: 1
	},
	
	/**
	 * Global object to store temporany global variables 
	 * 
	 * @var object
	 */
	tmp: {},
	
	/**
	 * Comodojo default applications path 
	 * 
	 * @var string
	 */
	_applicationsPath: "applications/",
	//_applicationsPath: "devel/applications/",
	
	icons: {
		
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
	},
	
	
	/**
	 * Reference to localized message DB  
	 * 
	 */
	localizedMessages: 0,
	
	/**
	 * Reference to localized errors DB  
	 * 
	 */
	localizedErrors: 0,
	
	/**
	 * The comodojo.kernel base!  
	 * 
	 * @class
	 */
	kernel: {
	
		_callKernel: function(httpMethod, callback, params){
		
			this.sync = false;
			this.preventCache = false;
			this.transport = 'JSON';
			this.encodeContent = false;
			this.content = {};
			this.application = false;
			this.method = false;
			
			dojo.mixin(this, params);
			
			var _content = []; // = this.encodeContent ? {encodedContent: dojo.toJson(this.content)} : this.content;
			
			_content['application'] = this.application;
			_content['method'] = this.method;
			_content['transport'] = this.transport;
			if (this.encodeContent) { 
				_content['contentEncoded'] = true;
				_content['content'] = dojo.toJson(this.content);
			}
			else {
				_content = dojo.mixin(_content,this.content);
			}
			
			dojo.xhr(httpMethod, {
				url: 'kernel.php',
				handleAs: this.transport == 'XML' ? 'xml' : 'json',
				sync: this.sync,
				preventCache: this.preventCache,
				load: function(data){
					comodojo.bus.callEvent('applicationFinishLoading');
					if (!data.success && data.result.code==2107) {
						comodojo.error.critical('lost session');
						setTimeout(function(){
							location.href = comodojoConfig.siteUrl;
						}, 5000);
					}
					else {
						callback(data.success, data.result);
					}
				},
				error: function(e){
					comodojo.bus.callEvent('applicationFinishLoading');
					comodojo.debug('Kernel exception! Source was: ' + this.server + ' (stack trace: ' + e + ')');
					callback(false, e);
				},
				content: _content 
			});
		},
		
		_callKernelDatastore: function(application, method, isWriteStore, label, identifier, urlPreventCache, clearOnClose, transport, content){
			
			var _url = 'kernel.php?datastore=true&contentIsEncoded=false&application=' + application + '&method=' + method + '&datastoreLabel=' + label + '&datastoreIdentifier=' + identifier+'&transport=' + transport;
			
			var _content = $d.objectToQuery(content);
			if (_content != '') {
				_url += '&'+_content;
			}
			
			if (!isWriteStore) {
				return new dojo.data.ItemFileReadStore({
					url: _url,
					urlPreventCache: urlPreventCache,
					clearOnClose: clearOnClose
				});
			}
			else {
				return new dojo.data.ItemFileWriteStore({
					url: _url,
					urlPreventCache: urlPreventCache,
					clearOnClose: clearOnClose
				});
			}
		},
		
		/**
		 * Start a new call to the kernel 
		 * 
		 * @params	function	callback	The function that will be called at the end of transaction
		 * @params	object		params		Params to pass to the kernel (POST)
		 */
		newCall: function(callback, params){
			comodojo.bus.callEvent('applicationStartLoading');
			comodojo.kernel._callKernel('POST', callback, params);
		},
		
		/**
		 * Start a new datastoreCall to the kernel 
		 * 
		 * @params	string				callTo		The function that will be called at the end of transaction
		 * @params	object				params		Params to pass to the kernel (POST)
		 * @return	object/datastore	
		 */
		newDatastore: function(application, method, params){
		
			var _params = {
				isWriteStore : false,
				label : 'name',
				identifier: 'resource',
				transport: 'JSON',
				urlPreventCache: false,
				clearOnClose: false,
				content: {}
			};
			
			dojo.mixin(_params, params);
			
			return comodojo.kernel._callKernelDatastore(application, method, _params.isWriteStore, _params.label, _params.identifier, _params.urlPreventCache, _params.clearOnClose, _params.transport, _params.content);
			
		},
		
		/**
		 * Start a new kenrel subscription; the service/selector requested will be called each "time" and will include a timestamp reference (params.lastCheck) 
		 * 
		 * @params	string		name		The subscription name
		 * @params	function	callback	The function that will be called at the end of transaction
		 * @params	object		params		Params to pass to the kernel (POST)
		 * @params	int			time		Time intervall between kernel calls
		 */
		subscribe: function(name, callback, params, time){
			comodojo.debug('New kernel subscription "'+name+'" signed.');
			var myTime = !time ? 10000 : time;
			comodojo.bus.addTimestamp(params.server, params.selector);
			params.content.lastCheck = 0;
			comodojo.kernel.newCall(callback, params);
			comodojo.bus.addTrigger(name, function() {
				params.content.lastCheck = comodojo.bus.getTimestampAndUpdate(params.server, params.selector);
				comodojo.kernel.newCall(callback, params);
			}, myTime);
		},
		
		/**
		 * End a defined kenrel subscription subscription 
		 * 
		 * @params	string		name		The subscription name
		 */
		unsubscribe: function(name){
			comodojo.debug('Unsubscribed "'+name+'" from kernel.');
			comodojo.bus.removeTrigger(name);
		}
	},
	
	/**
	 * The comodojo.session base!  
	 * 
	 * @class
	 */
	session: {
		
		/**
		 * Login handler
		 * 
		 * @function
		 * @param	{String}		userName		The userName
		 * @param	{String}		userPass		The userPass
		 * @param	{Function}		callback		Callback on authentication
		 */
		login: function(userName, userPass, callback) {
			
			dojo.xhr('POST', {
				url: 'kernel.php',
				handleAs: 'json',
				sync: true,
				preventCache: true,
				load: function(data){
					if (data.success) {
						comodojo.userRole = data.result.userRole;
						comodojo.userName = data.result.userName;
						comodojo.userCompleteName = data.result.completeName;
						if (dojo.isFunction(callback)) {
							callback(data.success, data.result);
						}
						setTimeout(function() {
							comodojo.app.stopAll(true);
						}, 1500);
						setTimeout(function() {
							comodojo.initEnv();
						}, 3000);
					}
					else {
						if (dojo.isFunction(callback)) {
							callback(false, data.result);
						}
					}
				},
				error: function(e){
					comodojo.debug('Session exception! Stack trace: ' + e);
					if (dojo.isFunction(callback)) {
						callback(false, e);
					}
				},
				//content: {content: this.encodeContent ? dojo.toJson(this.content) : this.content }
				content: {
					application: 'comodojo',
					method: 'login',
					userName: userName,
					userPass: userPass
				}
			});

		},
		
		/**
		 * Logout handler
		 * 
		 * @params	function	callback		Callback on authentication
		 */
		logout: function(callback) {
			
			comodojo.loader.start();
			
			dojo.xhr('POST', {
				url: 'kernel.php',
				handleAs: 'json',
				sync: true,
				preventCache: true,
				load: function(data){
					if (data.success) {
						comodojo.userRole = 0;
						comodojo.userName = false;
						comodojo.userCompleteName = false;
						if (dojo.isFunction(callback)) {
							callback(data.success, data.result);
						}
						setTimeout(function() {
							comodojo.app.stopAll(true);
						}, 1500);
						setTimeout(function() {
							comodojo.initEnv();
						}, 3000);
					}
					else {
						if (dojo.isFunction(callback)) {
							callback(false, data.result);
						}
					}
				},
				error: function(e){
					comodojo.debug('Session exception! Stack trace: ' + e);
					if (dojo.isFunction(callback)) {
						callback(false, e);
					}
				},
				//content: {content: this.encodeContent ? dojo.toJson(this.content) : this.content }
				content: {
					application: 'comodojo',
					method: 'logout'
				}
			});
			
		}
		
	},
	
	/**
	 * Just an alias for the document head DOM
	 * 
	 * @returns	object	page head object
	 */
	head: function() {
		return document.getElementsByTagName('head').item(0);
	},
	
	/**
	 * Just an alias for the document main content
	 * 
	 * @returns	object	page main object
	 */
	main: function() {
		return $d.byId(comodojoConfig.defaultContainer);
	},
	
	/**
	 * Load a .js file dinamically into CoMoDojo. It can understand if browser needs an xhr instead
	 * of script injection and first use dojo.query() to check if script is already loaded
	 * 
	 * @param	string	scriptFilePath	The file path
	 * @param	object	params			Parameters to be mixed with default ones
	 */
	loadScriptFile: function (scriptFilePath, params) {
		
		this.onLoad = false;
		this.sync = false;
		this._toReturn = true;
		this.preventCache = false;
		this.skipXhr = false;
		this.forceReload = false;
		
		dojo.mixin(this,params);
		
		if (dojo.query("script[src='"+scriptFilePath+"']")!="" && (this.forceReload)) {
			comodojo.debug('Script "'+scriptFilePath+'" was loaded before, skipping.');
			if (dojo.isFunction(this.onLoad)) {
				this.onLoad();
			}
		}
		else if (/*(dojo.isWebKit || dojo.isOpera || dojo.isIE) && */!this.skipXhr){
			dojo.xhrGet({
				url: scriptFilePath,
				handleAs: 'javascript',
				sync: this.sync,
				preventCache: this.preventCache,
				load: this.onLoad,
				error: function(e){
					comodojo.debug('Unable to load script: '+scriptFilePath+' (error was: '+e+')');
					this._toReturn = false;
				}
			});
		}
		else {
			try {
				dojo.create("script", {
					language: 'javascript',
					type: 'text/javascript',
					src: scriptFilePath,
					onreadystatechange: function () {
						if (this.readyState == 'complete') {
							dojo.hitch(this,this.onLoad);
						}
					},
					onload: this.onLoad
				}, comodojo.head());
			}
			catch(e) {
				comodojo.debug('Unable to load  script: '+scriptFilePath+'  (Error was: '+e+')');
				this._toReturn = false;
			}
		}
		
		return this._toReturn;
		
	},
	
	/**
	 * Inject a custom script into document head
	 * 
	 * @param	string	script	The script
	 * @return	object	The newly created script object
	 */
	loadScript: function (script) {
		
		return dojo.create("script", {
			language: 'javascript',
			type: 'text/javascript',
			innerHTML: script
		}, comodojo.head());
			
	},
	
	/**
	 * Load a comodojo component (i.e. everything in comodojo/javascript/resources) using only component name
	 * 
	 * @param	string	componentName	The component name
	 * @param	string	params			Params to put in comodojo.bus._modules
	 */
	loadComponent: function(componentName, params) {
		
		if (dojo.isObject(params)) {
			comodojo.bus._modules[componentName] = params;
		}
		return comodojo.loadScriptFile('comodojo/javascript/resources/'+componentName+'.js',{sync:true});
		
	},
	
	/**
	 * Load a comodojo component (i.e. everything in comodojo/javascript/resources) using only component name
	 * 
	 * @param	string	componentName	The component name
	 * @param	string	params			Params to put in comodojo.bus._modules
	 */
	loadComponentNoXhr: function(componentName, params) {
		
		if (dojo.isObject(params)) {
			comodojo.bus._modules[componentName] = params;
		}
		return comodojo.loadScriptFile('comodojo/javascript/resources/'+componentName+'.js',{sync:true, skipXhr:true});
		
	},
	
	/**
	 * Create a css link object into documnet head for the .css file passed as param
	 * 
	 * @param	string	cssFile	The CSS file path
	 * @return	object	The newly created link object
	 */
	loadCss: function (cssFile) {
		
		return dojo.create("link", {
			rel: 'stylesheet',
			type: 'text/css',
			href: cssFile
		}, comodojo.head());
			
	},
	
	/**
	 * Log some message tagged as "debug" in console (firebug), only if djConfig.isDebug is true
	 * 
	 * @param	string	message	The message to raise in console
	 */
	debug: function(message) {
		
		if (comodojoConfig.debug) {
		//if (true) {
			console.log(message);
		}
		
	},
	
	/**
	 * Log some message tagged as "debug" in console (firebug), only if djConfig.debugAtAllCost is true. In other
	 * words, it raise message at lower debug level
	 * 
	 * @param	string	message	The message to raise in console
	 */
	debugDeep: function(message) {
		
		if (comodojoConfig.debugDeep) {
		//if (true) {
			console.log(message);
		}
		
	},
	
	/**
	 * Raise a standard message in console if deprecated module is called
	 * 
	 * @param	string	module	The used (and deprecated) module
	 * @param	string	newModule	The new (and should-be-used-instead) module
	 */
	deprecated: function(module, newModule) {
		if(comodojoConfig.debug) {
			console.warn("Module '"+module+"' is deprecated. Consider using '"+newModule+"' instead.");
		}
	},
	
	/**
	 * Bootstrap the etire CoMoDojo environment!
	 * WARNING: This is a private function DO NOT call it in running environment unless
	 * you know what you're doing!
	 * 
	 * @private
	 */
	_bootstrap: function() {
		
		//var bootstrapFile = 'comodojo/global/bootstrap.php?applicationsDirectory='+comodojo._applicationsPath;
		var bootstrapFile = 'bootstrap.php';
		
		//remove old script - THIS IS NOT NECESSARY... BUT JUST TO BE SURE...
		dojo.query("script[src='"+bootstrapFile+"']").forEach(function(s){dojo.destroy(s);});
		
		if (dojo.isWebKit || dojo.isOpera || dojo.isIE) {
			dojo.xhrGet({
				url: bootstrapFile,
				headers: {'Content-Type':'application/x-javascript'},
				handleAs: 'javascript'//,
				//load: function(d){eval(d);},
				//sync: true
			});
		}
		else {
			dojo.create("script", {
				language: 'javascript',
				type: 'text/javascript',
				src: bootstrapFile
			}, comodojo.head());
		}
		
	},
	
	/**
	 * Print on console the site state during bootstrap
	 * 
	 * @private
	 */
	_debugBootstrap: function() {
		
		if (comodojoConfig.debug) {
			console.log('*************************************************************************');
			console.log('Debug is on. Now showing detailed bootstrap information:');
			console.log('-------------------------------------------------------------------------');
			console.log(' - Debug deep (deep debug for comodojo, module debug for dojo): ' + comodojoConfig.debugDeep);
			console.log(' - Username: ' + comodojo.userName);
			console.log(' - User role Id: ' + comodojo.userRole);
			console.log(' - Defined locale: ' + comodojo.locale);
			console.log(' - Comodojo version: ' + comodojoConfig.version);
			console.log(' - Dojo version loaded: ' + dojo.version);
			
			if (!comodojo._localizedMessagesResult) {
				console.log(' - There was an error loading messages locale, please check your configuration.');
			}
			else {
				console.log(' - Localized messages db correctly preloaded');
			}
			if (!comodojo._localizedErrorsResult) {
				console.log(' - There was an error loading errors locale, please check your configuration.');
			}
			else {
				console.log(' - Localized errors db correctly preloaded');
			}
			console.log('*************************************************************************');
			console.info('Comodojo basic javascript chains correctly loaded!');
		}
		
	},
	
	/**
	 * Load localization for site messages from relative json file.
	 * If localization file could not be imported, load default (en) one.
	 * 
	 * @private
	 */
	_loadMessages: function() {
		
		var myMessagesLocaleTry = {
			url: 'comodojo/i18n/i18n_messages_'+comodojo.locale+'.json',
			handleAs: 'json',
			sync: true,
			load: function(data){
				comodojo.localizedMessages = data;
				comodojo._localizedMessagesResult = true;
			},
			error: function(error){
				comodojo.debug('failed to understand your locale or localized messages file doesn\'t exists!');
				comodojo.debug('Reason was: '+ error );
				if (this.url == 'comodojo/i18n/i18n_messages_en.json') {
					comodojo.debug('Standard messages localization file doesn\'t exists, messages unavailable.');
					comodojo._localizedMessagesResult = false;
				}
				else {
					comodojo.debug('Falling back to default messages locale (en).');
					this.url = 'comodojo/i18n/i18n_messages_en.json';
					dojo.xhrGet(myMessagesLocaleTry);
				}
			}
		};
		dojo.xhrGet(myMessagesLocaleTry);
		
	},
	
	/**
	 * Load localization for site errors from relative json file.
	 * If localization file could not be imported, load default (en) one.
	 * 
	 * @private
	 */
	_loadErrors: function() {
		
		var myErrorsLocaleTry = {
			url: 'comodojo/i18n/i18n_errors_'+comodojo.locale+'.json',
			handleAs: 'json',
			sync: true,
			load: function(data){
				comodojo.localizedErrors = data;
				comodojo._localizedErrorsResult = true;
			},
			error: function(error){
				comodojo.debug('failed to understand your locale or localized errors file doesn\'t exists!');
				comodojo.debug('Reason was: '+ error );
				if (this.url == 'comodojo/i18n/i18n_errors_en.json') {
					comodojo.debug('Standard messages localization file doesn\'t exists, messages unavailable.');
					comodojo._localizedErrorsResult = false;
				}
				else {
					comodojo.debug('Falling back to default errors locale (en).');
					this.url = 'comodojo/i18n/i18n_errors_en.json';
					dojo.xhrGet(myErrorsLocaleTry);
				}
			}
		};
		dojo.xhrGet(myErrorsLocaleTry);
		
	},
	
	/**
	 * Start the CoMoDojo environment!
	 * 
	 */
	initEnv: function() {
		
		//add default startup events
		comodojo.bus.addEvent('comodojo_startup_started');
		comodojo.bus.addEvent('comodojo_startup_finished');
		comodojo.bus.callEvent('comodojo_startup_started');
		
		//escape for the current timeshift/timezone value
		comodojo.timezone = comodojo.date.getUserTimezone(); 
		
		comodojo._loadMessages();
		comodojo._loadErrors();
		comodojo._debugBootstrap();
		
		comodojo.loadScriptFile('comodojo/javascript/resources/environment.js',{
			sync:true,
			onLoad:function(){
				comodojo.loader.start();
			}
		});
		comodojo.loadScriptFile('comodojo/javascript/resources/windows.js',{sync:true});
		comodojo.loadScriptFile('comodojo/javascript/resources/applicationsManager.js',{sync:true});
		
		comodojo._bootstrap();
		
		dojo.ready(function(){
			comodojo.loader.stopIn(2000);
			comodojo.bus.callEvent('comodojo_startup_finished');
		});
		
	},
	
	/**
	 * Check if elementId refer to DOM object and get obj type (DOM/WIDGET)
	 * 
	 * @param	string	elementId	The id that isSomething should check
	 */
	isSomething: function(elementId) {
		
		if (dijit.byId(elementId)) {
			comodojo.debugDeep('Called exsistence check for: ' + elementId+'. Result was true (WIDGET).');
			return {success: true, type: "WIDGET", resource: dijit.byId(elementId)};
		}
		else if (dojo.byId(elementId)) {
			comodojo.debugDeep('Called exsistence check for: ' + elementId+'. Result was true (DOM).');
			return {success: true, type: "DOM", resource: dojo.byId(elementId)};
		}
		else {
			comodojo.debugDeep('Called exsistence check for: ' + elementId+'. Result was false.');
			return {
				success: false,
				type: false,
				resource: false
			};
		}
		
	},
	
	/**
	 * Check if "what" is currently defined
	 * 
	 * @param	string	what	What to check
	 */
	isDefined: function(what) {
		return (typeof(what)==="undefined") ? false : true;
	},
	
	/**
	 * Check if a key is defined in array
	 * 
	 * @param	string	what	Key to check
	 * @param	array	wherein	Array to walk
	 */
	inArray: function(what, wherein) {
		var key;
		for (key in wherein) {
			if (wherein[key] == what) {
				return true;
			}
	    }
		return false;
	},
	
	/**
	 * Compare "what" to "to" and return bool
	 * 
	 * Based on Nathan Toone's code
	 * from [Dojo-interest] compare utility
	 * on Thu Aug 6 20:26:24 EDT 2009
	 */
	
	compare: function(what, to) {
        if ((what === undefined) && (to === undefined)) {
            return true;
        }
        if ((what === to) || (to == what)) {
            return true;
        }
        var arrayCompare = function(i1, i2){
            var l = i1.length;
            if (l != i2.length) {
                return false;
            }
            var x;
            for (x = 0; x < l; x++) {
                if (!comodojo.compare(i1[x], i2[x])) {
                    return false;
                }
            }
            return true;
        };
        var objCompare = function(i1, i2){
            if (i1 instanceof Date) {
                return (i2 instanceof Date && i1.getTime() == i2.getTime());
            }
            if (i1 === null && i2 === null) {
                return true;
            }
            else 
                if (i1 === null || i2 === null) {
                    return false;
                }
            var x;
            for (x in i1) {
                if (!(x in i2)) {
                    return false;
                }
            }
            for (x in i2) {
                if (!comodojo.compare(i1[x], i2[x])) {
                    return false;
                }
            }
            return true;
        };
        if (dojo.isArray(what) && dojo.isArray(to)) {
            return arrayCompare(what, to);
        }
        if (typeof what == "object" && typeof to == "object" && objCompare(what, to)) {
            return true;
        }
        return false;
	},
	
	/**
	 * Some small date utils to transform date obj into timestamp (unixtime)
	 */
	date: {
		
		toServer: function(clientDate){
			return (new Date(clientDate).getTime() + (comodojo.timezone*3600)) / 1000.0;
		},
		
		fromServer: function(serverDate) {
			var myDate = new Date ( ( serverDate - (comodojoConfig.serverTimezoneOffset*3600) + (comodojo.timezone*3600) ) * 1000.0 );
			return  myDate.getDate()+"-"+(myDate.getMonth()+1)+"-"+myDate.getFullYear()+","+myDate.getHours()+":"+myDate.getMinutes()+":"+myDate.getSeconds();
		},
		
		getUserTimezone: function() {
			//that should be substituted by dojo.cookie!
			var sCookies = document.cookie.split(';');
			var sCookieToFind = "comodojo_timezone=";
			var isInCookies = false;
			var i = 0;
			for(i;i<sCookies.length;i++) {
				var sCookie = sCookies[i];
				while (sCookie.charAt(0)==' ') sCookie = sCookie.substring(1,sCookie.length);
				if (sCookie.indexOf(sCookieToFind) == 0) isInCookies = sCookie.substring(sCookieToFind.length,sCookie.length);
			}
			if (!isInCookies) {
				var clientDate = new Date();
				var tShift = -clientDate.getTimezoneOffset()/60;
				return tShift; 
			}
			else {
				return isInCookies;
			}
		}
		
	},
	
	fileSize: {
		
		toServer: function(size, format){
			
		},
		
        fromServer: function(bytes){
            var _bytes = parseInt(bytes, 10);
            return (_bytes < 1048576 ? (Math.round(_bytes / 1024 * 100000) / 100000 + " bytes") : (_bytes < 1073741824 ? (Math.round(_bytes / 1048576 * 100000) / 100000 + " KB") : (Math.round(_bytes / 1073741824 * 100000) / 100000 + " MB")));
        }
		
	},
	
	/**
	 * Destroy element referred by id:elementId
	 * 
	 * @param	string	elementId	The id that destroySomething should destroy
	 */
	destroySomething: function(elementId){
	
		if (dijit.byId(elementId) != null) {
			//dijit.byId(elementId).destroyRendering();
			dijit.byId(elementId).destroyRecursive();
			comodojo.debugDeep('Requested destroy of: '+elementId+'. Object (WIDGET) was destroied.');
			return {success: true};
		}
		else if (dojo.byId(elementId)) {
			dojo.destroy(dojo.byId(elementId));
			comodojo.debugDeep('Requested destroy of: '+elementId+'. Object (DOM) was destroied.');
			return {success: true};
		}
		else {
			comodojo.debugDeep('Requested destroy of: '+elementId+'. Cannot destroy anything! (Object not found).');
			return {success: false};
		}
		
	},
	
	/**
	 * Destroy client-side environment
	 * 
	 */
	destroyAll: function() {
	
		//inform user (if in debug mode)
		comodojo.debug('CALLED SITE-WIDE KILL! (*EVERYTHING* will be destroyed right now!)');
		
		//destroy dojo objects
		dijit.registry.forEach(function(widget){
			comodojo.debugDeep('Killing widget: '+widget.id);
			widget.destroyRendering();
			widget.destroyRecursive();
		});
		
		//logout current user
		//dojo.xhrGet ({
		//	url: 'comodojo/applications/userBar/userBar.php?logout=true',
		//	sync: true
		//});
		
		//destroy page body
		comodojo.debugDeep('Page body will be changed');
		dojo.body().parentNode.replaceChild(document.createElement("body"),dojo.body());
		//dojo.body().appendChild(dojo.create("div",{
		//	innerHTML: comodojo.getLocalizedError("99998"),
		//	style: "margin: 0 auto; text-align: center; font-size: large; color: red; padding: 10px;"
		//}));
		
		//unload scripts
		comodojo.debugDeep('And every script unloaded.');
		dojo.query('script').forEach(function(s){dojo.destroy(s);});
			
	},
	
	/**
	 * Create a hierarchy defined DOM element composition
	 * 
	 * @param	object/array	jsonHierachy	The hierarchy
	 */
	createHierarchy: function (jsonHierachy) {

		var myNode, widgetConstructor, widget;
		myNode = dojo.create("div",{}, dojo.body());
		if(jsonHierachy.style){
			myNode.style.cssText = jsonHierachy.style;
		}
		if(jsonHierachy.cssClass){
			dojo.addClass(myNode, jsonHierachy.cssClass);
		}
		if(jsonHierachy.innerHTML){
			myNode.innerHTML=jsonHierachy.innerHTML;
		}
		widgetConstructor = eval(jsonHierachy.widgetType);
		widget = new widgetConstructor (jsonHierachy.params, myNode);
		if(jsonHierachy.children){
			dojo.forEach(jsonHierachy.children,
				function(child){ widget.addChild(comodojo.createHierarchy(child)); });
		}
		widget.startup();
		return widget;
	},
	
	
	/**
	 * Create a complex object starting from json hierachy.
	 * 
	 * Hierarchy pattern should be composed like:
	 * 
	 * {
	 * 		domobj: <>,
	 * 		widget: <>,
	 * 		name: <>,
	 * 		cssClass: <>,
	 * 		innerHTML: <>,
	 * 		style: <>,
	 * 		href: <>,
	 * 		params: <>,
	 * 		childrens: [
	 * 			{domobj: <>,
	 *			 widget: <>,
	 *			 name: <>,
	 *			 cssClass: <>,
	 *			 innerHTML: <>,
	 *			 href: <>,
	 *			 params: <>,
	 *			 childrens: [{...}],{...}},
	 *			{...},
	 *			{...}
	 *		]
	 *}
	 * 
	 * Note that 
	 * 
	 * @param hierachy
	 * @param startObj
	 * @returns
	 */
	fromHierarchy: function (hierachy, startObj) {

		var myNode, ObjectConstructor, BuiltObject;
		
		myNode = dojo.create(!hierachy.domobj ? "div" : hierachy.domobj, {}, dojo.body());
		
		if(hierachy.style){ myNode.style.cssText = hierachy.style; }
		
		if(hierachy.cssClass){ dojo.addClass(myNode, hierachy.cssClass); }
		
		if(hierachy.innerHTML){ myNode.innerHTML = hierachy.innerHTML; }
		
		if (hierachy.domobj) {
			if (startObj) {
				startObj[hierachy.name] = myNode;
				if(hierachy.childrens){
					dojo.forEach(hierachy.childrens, function(child){
						startObj[hierachy.name].appendChild(comodojo.fromHierarchy(child, startObj[hierachy.name]).domNode);
					});
				}
				BuiltObject = startObj[hierachy.name];
				BuiltObject.domNode = BuiltObject;
			}
			else {
				BuiltObject = myNode;
				if(hierachy.childrens){
					dojo.forEach(hierachy.childrens, function(child){
						BuiltObject.appendChild(comodojo.fromHierarchy(child, false).domNode);
					});
				}
				BuiltObject.domNode = BuiltObject;
			}
		}
		else {
			ObjectConstructor = eval(hierachy.widget);
			if (startObj) { 
				startObj[hierachy.name] = new ObjectConstructor (!hierachy.params ? {} : hierachy.params, myNode);
				if (hierachy.innerHTML) { startObj[hierachy.name].set('content',hierachy.innerHTML);}
				if(hierachy.childrens){
					dojo.forEach(hierachy.childrens, function(child){
						if (dojo.isFunction(startObj[hierachy.name].addChild)) { startObj[hierachy.name].addChild(comodojo.fromHierarchy(child, startObj[hierachy.name])); }
						else { 
							if (startObj[hierachy.name].containerNode) { startObj[hierachy.name].containerNode.appendChild(comodojo.fromHierarchy(child, startObj[hierachy.name]).domNode); }
							else if (startObj[hierachy.name].domNode) { startObj[hierachy.name].domNode.appendChild(comodojo.fromHierarchy(child, startObj[hierachy.name]).domNode); }
							else { startObj[hierachy.name].appendChild(comodojo.fromHierarchy(child, startObj[hierachy.name]).domNode); }
						}
					});
				}
				BuiltObject = startObj[hierachy.name];
			}
			else { 
				BuiltObject = new ObjectConstructor (!hierachy.params ? {} : hierachy.params, myNode);
				if (hierachy.innerHTML) { BuiltObject.set('content',hierachy.innerHTML);}
				if(hierachy.childrens){
					dojo.forEach(hierachy.childrens, function(child){
						if (dojo.isFunction(BuiltObject.addChild)) { BuiltObject.addChild(comodojo.fromHierarchy(child, false)); }
						else { 
							if (BuiltObject.containerNode) { BuiltObject.containerNode.appendChild(comodojo.fromHierarchy(child, false).domNode); }
							else if (BuiltObject.domNode) { BuiltObject.domNode.appendChild(comodojo.fromHierarchy(child, false).domNode); }
							else { BuiltObject.appendChild(comodojo.fromHierarchy(child, false).domNode); }
						}
					});
				}
			}
			BuiltObject.startup();
		}
		return BuiltObject;
	},
	
	//******************************
	//******NOT YET WORKING!!!******
	//******************************
	/*
	fromHierachy: function (jsonHierachy) {
		
		var myNode, myConstructor, myObject;
		
		if (jsonHierachy.type == "dom") {
			myNode = dojo.create(jsonHierachy.object,{}, dojo.body());
		}
		else {
			myNode = dojo.create("div",{}, dojo.body());
			myConstructor = eval(jsonHierachy.object);
		}
		
		if(jsonHierachy.style){
			myNode.style.cssText = jsonHierachy.style;
		}
		if(jsonHierachy.cssClass){
			dojo.addClass(myNode, jsonHierachy.cssClass);
		}
		if(jsonHierachy.innerHTML){
			myNode.innerHTML=jsonHierachy.innerHTML;
		}
		
		if (jsonHierachy.type == "widget") {
			myObject = new myConstructor(jsonHierachy.params, myNode);
		}
		
		if(jsonHierachy.children){
			dojo.forEach(jsonHierachy.children,
				function(child){
					if (myObject.isContainer) {
						myObject.addChild( child.type == "dom" ? comodojo.createHierarchy(child) : (comodojo.createHierarchy(child)).domNode );
					}
					else if (!myObject.domNode){
						myObject.appendChild( child.type == "dom" ? comodojo.createHierarchy(child) : (comodojo.createHierarchy(child)).domNode );
					}
					else {
						myObject.domNode.appendChild( child.type == "dom" ? comodojo.createHierarchy(child) : (comodojo.createHierarchy(child)).domNode );
					}
				});
		}
		
		if (jsonHierachy.type == "widget") {
			myObject.startup();
		}
		else {
			myObject = myNode;
		}
		
		return myObject;
		
	},
	*/
	
	/**
	 * Get a localized error message (as defined in i18n_errors_[locale].json files)
	 *
	 * @param 	string	errorId	The error identifier
	 * @return	string			The message requested (localized)
	 */
	getLocalizedError: function(errorId) {
		
		if (!comodojo.localizedErrors[errorId] && comodojo.locale != "en") {
           	var c;
			dojo.xhrGet({
                url: 'comodojo/i18n/i18n_errors_en.json',
                handleAs: 'json',
				sync: true,
                load: function(data){
					comodojo.debug('Cannot find ('+comodojo.locale+') translation for error num: '+errorId+', fallback to standard locale.');
					if (!data[errorId]) {
						comodojo.debug('Cannot find any translation for error num: '+errorId+', returning _?_');
	                    c = "_?_";
					}
					else {
						c = data[errorId];
					}
                },
                error: function(error){
					comodojo.debug('Impossible to load error messages db, please check your configuration!');
                }
            });
			return c;
        }
		else if (!comodojo.localizedErrors[errorId] && comodojo.locale == "en") {
			comodojo.debug('Cannot find any translation for error num: '+errorId+', returning _?_');
			return "_?_";
		}
		else {
			return comodojo.localizedErrors[errorId];
		}
		
	},
	
	/**
	 * Get a localized, mutable error message (as defined in i18n_errors_[locale].json files)
	 * Function will substitute each ${INT} pattern in localized string with parameters passed
	 *
	 * @param 	string	errorCode	The error identifier
	 * @param 	array	params		Values to substitute to patterns in string
	 * @return	string				The error message requested (localized)
	 */
	getLocalizedMutableError: function(errorCode, params) {
		var err = comodojo.getLocalizedError(errorCode);
		return err != '__?('+errorCode+')?__' ? dojo.string.substitute(err, params) : err;
	},
	
	/**
	 * Get a localized  message (as defined in i18n_message_[locale].json files)
	 *
	 * @param 	string	messageNum	The message identifier
	 * @return	string				The message requested (localized)
	 */
	getLocalizedMessage: function(messageNum) {
		
        if (!comodojo.localizedMessages[messageNum] && comodojo.locale != "en") {
           	var c;
			dojo.xhrGet({
                url: 'comodojo/i18n/i18n_messages_en.json',
                handleAs: 'json',
				sync: true,
                load: function(data){
					comodojo.debug('Cannot find ('+comodojo.locale+') translation for message num: '+messageNum+', fallback to standard locale for this time.');
					if (!data[messageNum]) {
						comodojo.debug('Cannot find any translation for message num: '+messageNum+', returning __?('+messageNum+')?__');
	                    c = '__?('+messageNum+')?__';
					}
					else {
						c = data[messageNum];
					}
                },
                error: function(error){
					comodojo.debug('Impossible to load messages db, please check your configuration!');
                }
            });
			return c;
        }
		else if (!comodojo.localizedMessages[messageNum] && comodojo.locale == "en") {
			comodojo.debug('Cannot find any translation for message num: '+messageNum+', returning __?('+messageNum+')?__');
			return '__?('+messageNum+')?__';
		}
		else {
			return comodojo.localizedMessages[messageNum];
		}
		
	},
	
	/**
	 * Get a localized, mutable message (as defined in i18n_message_[locale].json files)
	 * Function will substitute each ${INT} pattern in localized string with parameters passed
	 *
	 * @param 	string	messageCode	The message identifier
	 * @param 	array	params		Values to substitute to patterns in string
	 * @return	string				The message requested (localized)
	 */
	getLocalizedMutableMessage: function(messageCode, params) {
		var mesg = comodojo.getLocalizedMessage(messageCode);
		return mesg != '__?('+messageCode+')?__' ? dojo.string.substitute(mesg, params) : mesg;
	},
	
	/**
	 * Get an unique pid (just a serial, starting from 1) for all CoMoDojo processes;
	 * pid is intended as string "pid_" followed by unique number (it could be convenient in
	 * definig DOM object ids).
	 *
	 * @return	string	The unique pid as requested
	 */
	getPid: function() {
		var myPid = this.globals._pidSeed;
		comodojo.globals._pidSeed++;
		return 'pid_'+myPid;
	}
	
};

/**
 * shortcut for comodojo namespace
 * 
 * @function
 */
var $c = comodojo;

/**
 * shortcut for dojo namespace
 * 
 * @exports	dojo
 */
var $d = dojo;

/**
 * shortcut for dijit namespace
 * 
 * @exports dijit
 */
var $j = dijit;

/**
 * shortcut for dojox namespace
 * 
 * @exports dojox
 */
var $x = dojox;

/**
 * shortcut for dojo.query function
 * 
 * @exports dojo.query
 */
function $q(query, root, listCtor) {
	return dojo.query(query, root, listCtor);
}