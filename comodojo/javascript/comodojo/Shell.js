define(["dojo/dom","dojo/_base/declare","dijit/form/Textarea","dojo/dom-construct","dojo/window",
	"dojo/dom-geometry","dojo/on","dojo/keys","dojo/dom-style","dojo/request","dojo/_base/json",
	"dojo/_base/lang","dijit/form/TextBox","dojo/_base/array","dojo/store/Memory"],
function(dom,declare,Textarea,domConstruct,win,domGeom,on,keys,domStyle,request,json,lang,TextBox,array,Memory){

	// module:
	// 	comodojo/Shell
	
	//Scope pointer for deferred requests.
	var myself = false;
	
	var shell = declare(null, {
		// summary:
		// 	The comodojo shell core.
		// description:
		// 	Generate a new shell istance.
		// returns:
		//	The shell
		
		// shellNode: Object
		// 		the node that will contain the shell
		shellNode: false,
		
		// shellLoader
		// 		the node that will contain the logo/loader
		shellLoader: false,
		

		userName: '',

		userRole: 0,

		siteName: false,

		clientIP: false,

		rpcProxy: false,
		
		systemMessage: "{0}:{1}$>",
		systemConnectedMessage: "{0}:{1}(<span style='color:blue;'>rpcmode[{2}]</span>)$>",

		currentArea: false,
		
		pastAreas: [],
		
		currentSignal: false,
		
		currentShellMessage: false,
		
		currentResult: false,
		
		
		commandHistory: [],
		
		commandHistoryPointer: 0,
		
		
		output_object: 'auto',
		output_string: 'standard',

		_autocomplete: true,

		
		_inConnection: false,
		_connections: {},
		_pendingRequests: 0,
		_pendingResults: '',

		connections_template: '<div><span style="color:{0};">({1}) {2}</span> - ({3}) - {4}{5}</div>',

		constructor: function(args) {
			
			declare.safeMixin(this,args);
			
			this.shellNode = dom.byId(this.shellNode);
			this.shellLoader = dom.byId(this.shellLoader);

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

			}

			this.shell_commands_store_data = [
				{id: "applications", type: "native", description:"Display available applications"},
				{id: "exit", type: "native", description:"Exit from session or disconnect (if in rpcmode)"},
				{id: "help", type: "native", description:"Display shell help"},
				{id: "login", type: "native", description:"Login user"},
				{id: "logout", type: "native", description:"Logout user"},
				{id: "history", type: "native", description:"Show commands history"},
				{id: "output", type: "native", description:"Select desired visualization output"},
				{id: "whoami", type: "native", description:"Display informations about current user"},
				{id: "connect", type: "native", description:"Connect to external rpc or enter connection mode (if no parameters)"},
				{id: "connections", type: "native", description:"Display available connections"},
				{id: "disconnect", type: "native", description:"Unlink connection or disconnect from rpcmode (if no parameters)"}
			];

			this.shell_commands_store = new Memory ({ data: this.shell_commands_store_data });

			this.loadAutocomplete();

			this.startShell();

		},

		is_array: function(obj) {

			if( Object.prototype.toString.call( obj ) === '[object Array]' ) {
			    return true;
			}
			return false;

		},
		
		in_array: function(what, wherein) {
			// summary:
			//		Check if a key is defined in array
			// what:
			//		The key to search
			// wherein:
			//		The array
			var key;
			for (key in wherein) {
				if (wherein[key] == what) {
					return true;
				}
			}
			return false;
		},

		size: function(obj) {
			var size = 0, key;
			for (key in obj) {
				if (obj.hasOwnProperty(key)) size++;
			}
			return size;
		},

		loadAutocomplete: function() {

			this.shell_commands_store.query({type:'application'}).forEach(function(value) { myself.shell_commands_store.remove(value.id); });

			request.post("kernel.php",{
				data: {
					application: 'comodojo',
					method: 'applications'
				},
				handleAs: 'json'
			}).then(function(data) {
				if (data.success) {
					for (var i in data.result) {
						myself.shell_commands_store.put({
							id: data.result[i],
							type: "application",
							description: false,
							loaded: false
						});
					}
				}
				else {
					myself._autocomplete = false;
					alert("It was impossible to load applications, autocomplete will not work");
				}
			},function(data) {
				myself._autocomplete = false;
				alert("It was impossible to load applications, autocomplete will not work");
			});

		},

		getSystemMessage: function() {

			var _siteName = !this.siteName ? 'comodojo' : this.siteName;
			var _userName = this.userRole == 1 ? ('<span style="color: red;">'+this.userName+'</span>') : this.userName;
			return myself._inConnection ? lang.replace(this.systemConnectedMessage,[_siteName,_userName,this.connection_get_active_links()]) : lang.replace(this.systemMessage,[_siteName,_userName]);

		},
		
		setLoadingState: function() {

			this.shellLoader.innerHTML = '<img src="comodojo/images/shell_loader.gif" />';

		},
		
		setReadyState: function() {

			this.shellLoader.innerHTML = '<img src="comodojo/images/shell_logo.png" />';

		},
		
		resizeViewport: function() {

			domStyle.set(myself.currentArea.domNode,'width',(win.getBox().w - myself.currentArea.get('preMargin'))+'px');
			var i = 0;
			for (i in myself.pastAreas) {
				domStyle.set(myself.pastAreas[i].domNode,'width',(win.getBox().w - myself.pastAreas[i].get('preMargin'))+'px');
			}

		},

		signals: {
			inactive_area: function(evt) {
				if (evt !== false) {
					evt.preventDefault();
				}
				myself.currentArea.focus();
				myself.currentArea.set('value',myself.currentArea.get('value')+String.fromCharCode(evt.charCode));
			},
			ctrl_c: function(evt) {
				if (evt !== false) {
					evt.preventDefault();
				}
				myself.currentArea.set('readonly',true);
				myself.currentSignal.remove();
				myself.resultOnScreen('');
			},
			history_back: function(evt) {
				if (evt !== false) {
					evt.preventDefault();
				}
				if (myself.commandHistoryPointer == 0) {
					return;
				}
				var pointer = myself.commandHistoryPointer-1;
				myself.currentArea.set('value',myself.commandHistory[pointer]);
				myself.commandHistoryPointer = pointer;
			},
			history_forward: function(evt) {
				if (evt !== false) {
					evt.preventDefault();
				}
				if (myself.commandHistoryPointer >= myself.commandHistory.length-1) {
					myself.commandHistoryPointer = myself.commandHistory.length;
					myself.currentArea.set('value','');
				}
				var pointer = myself.commandHistoryPointer+1;
				myself.currentArea.set('value',myself.commandHistory[pointer]);
				myself.commandHistoryPointer = pointer;
			},
			complete_command: function(evt) {
				if (evt !== false) {
					evt.preventDefault();
				}
				myself.autocomplete();
			},
			enter_push: function(evt) {
				if (myself.areBracketsBalanced()) {
					if (evt !== false) {
						evt.preventDefault();
					}
					myself.processCommand();
				}
				else {
					myself.currentArea.set('extended',true);
				}
			},
			input_enter_push : function(evt, callback) {
				if (evt !== false) {
					evt.preventDefault();
				}
				myself.currentArea.set('readonly',true);
				myself.currentSignal.remove();
				callback(myself.currentArea.get('value'));
			},
			session_lost: function(evt) {
				if (evt !== false) {
					evt.preventDefault();
				}
				myself.currentArea.set('readonly',true);
				myself.currentSignal.remove();
				myself.resultOnScreen(myself.visualization._string.failure('Session was lost, please <a href="javascript:history.go(0);">reload shell</a>'));
				myself.currentArea.set('readonly',true);
				myself.currentSignal.remove();
				myself.currentArea.set('value','shell locked, session lost');
				myself.currentArea.set('style','color:red;font-style:italic;');
			}
		},
		
		areBracketsBalanced: function() {

			var pc=0, sc=0, cc=0;
			var string = this.currentArea.get('value');
			for(var i=0; i < string.length; i++) {
				switch(string.charAt(i)) {
					case '(': pc++; break;
					case ')': pc--; if (pc < 0) {return false;} break;
					case '[': sc++; break;
					case ']': sc--; if (sc < 0) {return false;} break;
					case '{': cc++; break;
					case '}': cc--; if (cc < 0) {return false;} break;
				}
			}
			return (pc == 0 && sc == 0 && cc == 0) ? true : false;

		},
		
		kernelRequest: function(data, load, error) {
			
			return request.post("kernel.php",{
				data: data,
				handleAs: 'json'
			//}).then(load,error);
			}).then(function(data) {
				if (!data.success) {
					if (data.result.code == 2107) {
						myself.signals.session_lost(false);
					}
					else {
						load(data);
					}
				}
				else {
					load(data);
				}
			},function(e) {
				if (e.code == 2107) {
					myself.signals.session_lost(false);
				}
				else {
					error(e);
				}
			});
			
		},
		
		startShell: function() {

			this.currentArea = this.newArea();
			
			on(window,"resize",this.resizeViewport);
			
			this.setReadyState();
			
			myself = this;

			on(document.body ,"keypress",function(evt) {
				if (!(evt.ctrlKey || evt.metaKey || myself.currentArea._focused)) { myself.signals.inactive_area(evt); }
			});

		},
		
		newArea: function() {
			
			var shellNodeWidth = win.getBox().w;
			
			var separator = domConstruct.create("div",{style: {
				width: "100%",
				border: "0px solid white",
				clear: "both"
			}});
			this.shellNode.appendChild(separator);
			
			this.currentShellMessage = domConstruct.create("span", { 
				innerHTML: this.getSystemMessage(),
				style: {
					position: "relative",
					top: "4px",
					fontFamily: 'Consolas,"Lucida Console","Andale Mono","Bitstream Vera Sans Mono","Courier New",Courier',
					fontSize:"14px",
					//borderBottom: "1px solid blue"
					border: "0px solid white"
				}
			});
			this.shellNode.appendChild(this.currentShellMessage);
			
			var smp = domGeom.position(this.currentShellMessage);
			
			var currentArea = new dijit.form.Textarea({
				style: {
					display:"inline", 
					padding:"4px",
					fontSize:"14px",
					width:(shellNodeWidth-smp.w)+"px",
					float: "right",
					border: "0px solid white"
				},
				type: 'password',
				extended: false
			});
			
			currentArea.set('preMargin',smp.w);
			
			this.shellNode.appendChild(currentArea.domNode);
			
			this.currentSignal = currentArea.on("keydown", function(evt) {
				if (evt.keyCode == keys.ENTER) { myself.signals.enter_push(evt); }
				else if (evt.keyCode == keys.UP_ARROW && !myself.currentArea.get('extended')) { myself.signals.history_back(evt); }
				else if (evt.keyCode == keys.DOWN_ARROW && !myself.currentArea.get('extended'))	{ myself.signals.history_forward(evt); }
				else if (evt.keyCode == keys.TAB) { myself.signals.complete_command(evt); }
				else if (evt.keyCode == 67 && evt.ctrlKey == true) { myself.signals.ctrl_c(evt); }
				else {
					//console.log(evt);
				}
			});
			
			currentArea.focus();
			
			return currentArea;
			
		},

		newInput: function(message,callback) {

			return this._newInput(message, callback, 'text');

		},
		
		newPassword: function(message,callback) {

			return this._newInput(message, callback, 'password');

		},
		
		_newInput: function(message,callback,type) {
			
			var shellNodeWidth = win.getBox().w;
			
			var separator = domConstruct.create("div",{style: {
				width: "100%",
				border: "0px solid white",
				clear: "both"
			}});
			this.shellNode.appendChild(separator);
			
			this.currentShellMessage = domConstruct.create("span", { 
				innerHTML: message,
				style: {
					position: "relative",
					top: "4px",
					fontFamily: 'Consolas,"Lucida Console","Andale Mono","Bitstream Vera Sans Mono","Courier New",Courier',
					fontSize:"14px",
					//borderBottom: "1px solid blue"
					border: "0px solid white"
				}
			});
			this.shellNode.appendChild(this.currentShellMessage);
			
			var smp = domGeom.position(this.currentShellMessage);
			
			this.pastAreas.push(this.currentArea);
			
			this.currentArea = new dijit.form.TextBox({
				style: {
					display:"inline", 
					padding:"0px",
					fontSize:"14px",
					width:(shellNodeWidth-smp.w)+"px",
					float: "right",
					border: "0px solid white"
				},
				type: type,
				extended: false
			});
			
			this.currentArea.set('preMargin',smp.w);
			
			this.shellNode.appendChild(this.currentArea.domNode);
			
			this.currentSignal = this.currentArea.on("keydown", function(evt) {
				if (evt.keyCode == keys.ENTER){ myself.signals.input_enter_push(evt, callback); }
				else if (evt.keyCode == 67 && evt.ctrlKey == true) { myself.signals.ctrl_c(evt); }
				else {
					//console.log(evt);
				}
			});
			
			this.currentArea.focus();
			
			return this.currentArea;
			
		},
		
		processCommand: function() {
			
			this.setLoadingState();
			
			this.currentArea.set('readonly',true);
			
			this.currentSignal.remove();

			this.currentArea.on("keypress", function(evt) {
				if (!(evt.ctrlKey || evt.metaKey)) { myself.signals.inactive_area(evt); }
			});

			this.shellNode.appendChild(domConstruct.create("div",{style: {
				width: "100%",
				border: "0px solid white",
				clear: "both"
			}}));
			
			var command = lang.trim(this.currentArea.get('value'));
			
			if (command == "") {
				this.currentArea = this.newArea();
				this.setReadyState();
				return;
			}
			
			this.commandHistoryPointer = this.commandHistory.push(command);
			
			var there_is_data = command.search("\\(");
			var real_command;
			
			if (there_is_data != -1) {
				real_command = (command.substring(0, there_is_data)).split('.');
			}
			else {
				real_command = command.split('.');
			}
			
			var params = command.match(/\(([^\)]*)\)/);
			
			if (real_command.length < 2) {

				real_command[0] = real_command[0].replace(/\W+/g,'');

				if (myself._inConnection && !myself.in_array(real_command,['exit','connect','disconnect','connections','output','whoami'])) {
					this.parseError('Invalid command in rpcmode');
				}
				else if (typeof this.shell_commands[real_command] == 'function') {
					try {
						this.shell_commands[real_command](params == null ? null : json.fromJson(params[0]));
					}
					catch(e) {
						this.parseError(e);
					}
				}
				else {
					this.kernelRequest({
						application: real_command,
						method: 'methods'
					}, function(data) {
						myself.parseResults(data);
					}, function(data) {
						myself.parseError(data);
					});
				}

			}
			else if (real_command.length == 2 && myself._inConnection) {

				this.connection_exex_command(real_command[0]+'.'+real_command[1],params);

			}
			else if (real_command.length == 2 && !myself._inConnection) {

				if (typeof this.reserved_commands[real_command.join('_')] == 'function') {
					this.reserved_commands[real_command.join('_')]();
					return;
				}
				else if (params == null) {
					var _data = {
						application: real_command[0],
						method: real_command[1]
					};
				}
				else {
					try{
						var _data = lang.mixin(json.fromJson(params[0]),{
							application: real_command[0],
							method: real_command[1]
						});
					}
					catch(e) {
						var _data = {
							application: real_command[0],
							method: real_command[1]
						};
					}

				}
				
				this.kernelRequest(_data, function(data) {
					myself.parseResults(data);
				}, function(data) {
					myself.parseError(data);
				});

			} 
			else {
				this.parseError('Ambiguous command');
			}
			
		},

		connection_exex_command: function(command, params) {
			
			for (var i in myself._connections) {

				if (myself._connections[i].status == 'up') {

					var par;

					myself._pendingRequests++;

					if (!myself._connections[i].user || !myself._connections[i].pass) {
						par = [null, null, params == null ? {} : json.fromJson(params[0])];
					}
					else {
						par = [myself._connections[i].user, myself._connections[i].pass, params == null ? {} : json.fromJson(params[0])];
					}

					myself.connection_exec_command_run({
						application: 'comodojo',
						method: 'rpcproxy',
						server: myself._connections[i].server,
						rpc_transport: myself._connections[i].transport.toUpperCase(),
						key: myself._connections[i].key,
						port: myself._connections[i].port,
						id: myself._connections[i].id,
						rpc_method: command,
						params: json.toJson(par)
					},i);

				}
				else {
					continue;
				}
			}

		},

		connection_exec_command_run: function(params, name) {
			myself.kernelRequest(params, function(data) {
				myself.connection_exex_command_callback(name,data);
			}, function(error) {
				myself.connection_exex_command_error(name,error);
			});
		},

		connection_exex_command_callback: function(source, data) {

			myself._pendingRequests--;

			if (data.success) {

				var result_content = '';

				try {
					switch(typeof data.result) {
						case 'object':
							result_content = myself.visualization._object[myself.output_object](data.result);
						break;
						case 'string':
							result_content = myself.visualization._string[myself.output_string](data.result);
						break;
						case 'number':
							result_content = myself.visualization._string.bold('Command output [numeric]: '+data.result);
						break;
						case 'boolean':
							if (data.result == true) {
								result_content = myself.visualization._string.success('Command returned success state');
							}
							else {
								result_content = myself.visualization._string.failure('Command returned failure state');
							}
						break;
						case 'function':
							result_content = myself.visualization._string.warning('{function}');
						break;
						default:
							result_content = myself.visualization._string.warning('{undefined}');
						break;
					}
					myself._pendingResults += ('<div><h3>Response from <span style="color:blue;">'+source+'</span></h3>'+result_content+"</div>");
				}
				catch (e) {
					myself._pendingResults += ('<div><h3>Error from <span style="color:red;">'+source+'</span></h3><p class="box 	bg-danger">'+e+'</p></div>');
				}

			}
			else {
				myself._pendingResults += ('<div><h3>Error from <span style="color:red;">'+source+'</span></h3><p class="box bg-danger">('+data.result.code+') '+data.result.name+'</p></div>');
			}
			
			if (myself._pendingRequests == 0) {
				myself.resultOnScreen(myself._pendingResults);
				myself._pendingResults = '';
			}

		},

		connection_exex_command_error: function(source, data) {

			myself._pendingRequests--;
			myself._pendingResults += ('<div><h3>Error from <span style="color:red;">'+source+'</span></h3><p class="box bg-danger">'+data+'</p></div>');

			if (myself._pendingRequests == 0) {
				myself.resultOnScreen(myself._pendingResults);
				myself._pendingResults = '';
			}

		},

		connection_get_active_links: function() {

			var conn = 0;
			for (var i in this._connections) {
				if (this._connections[i].status == 'up') {
					conn++;
				}
			}
			return conn;

		},

		parseResults: function(data) {
			
			var result_content = '';
			
			if (data.success) {
				try {
					switch(typeof data.result) {
						case 'object':
							result_content = this.visualization._object[this.output_object](data.result);
						break;
						case 'string':
							result_content = this.visualization._string[this.output_string](data.result);
						break;
						case 'number':
							result_content = this.visualization._string.bold('Command output [numeric]: '+data.result);
						break;
						case 'boolean':
							if (data.result == true) {
								result_content = this.visualization._string.success('Command returned success state');
							}
							else {
								result_content = this.visualization._string.failure('Command returned failure state');
							}
						break;
						case 'function':
							result_content = this.visualization._string.warning('{function}');
						break;
						default:
							result_content = this.visualization._string.warning('{undefined}');
						break;
					}
					this.resultOnScreen(result_content);
				}
				catch (e) {
					this.parseError(e);
				}
			}
			else {
				this.parseError('('+data.result.code+') '+data.result.name);
			}
			
		},
		
		parseError: function(code) {

			this.resultOnScreen('<p class="box bg-danger">'+code+'</p>');

		},
		
		resultOnScreen: function(result_content) {
			
			this.currentResult = domConstruct.create("div", {
				innerHTML: result_content,
				style: {
					width: "100%",
					position: "relative",
					top: "0px",
					padding: "0",
					border: "0px solid white"
				},
				tabindex: 1
			});
			
			this.shellNode.appendChild(this.currentResult);

			this.pastAreas.push(this.currentArea);
			
			this.currentArea = this.newArea();
			
			this.setReadyState();

			on(this.currentResult,"keypress",function(evt) {
				if (!(evt.ctrlKey || evt.metaKey)) { myself.signals.inactive_area(evt); }
			});
			
		},

		autocomplete: function() {

			if (!this._autocomplete || this._inConnection) {
				return;
			}

			var command = lang.trim(this.currentArea.get('value'));

			var exploded_command = command.split(".");

			var possible = [];			

			var valid_application, methods_loaded;

			if (exploded_command instanceof Array &&  exploded_command.length == 2) {

				//check if application is valid or not
				this.shell_commands_store.query({id: new RegExp(exploded_command[0], "i"), type: 'application'}).forEach(function(value) {
					valid_application = true;
					methods_loaded = value.loaded;
				});

				if (valid_application) {

					if (!methods_loaded) {
						this.kernelRequest({
							application: exploded_command[0],
							method: 'methods'
						}, function(data) {
							if (data.success) {
								for (var i in data.result) {
									myself.shell_commands_store.put({
										id: exploded_command[0]+'.'+i,
										type: "method",
										description: data.result[i].description,
										loaded: false
									});
								}
								myself.shell_commands_store.put({
									id: exploded_command[0],
									type: "application",
									description: false,
									loaded: true
								},{overwrite:true});
								exploded_command[0] = exploded_command[0].replace(/\W+/g,'');
								exploded_command[1] = exploded_command[1].replace(/\W+/g,'');
								myself.shell_commands_store.query({id: new RegExp('^'+exploded_command[0]+'.'+exploded_command[1], "i"), type: 'method'}).forEach(function(value) {
									possible.push([value.id,'<span style="color:blue;">'+value.id+'</span> - '+value.description]);
								});
								myself.autocomplete_onScreen(possible,exploded_command[0],exploded_command[1]);
							}
						});
					}
					else {
						exploded_command[0] = exploded_command[0].replace(/\W+/g,'');
						exploded_command[1] = exploded_command[1].replace(/\W+/g,'');
						this.shell_commands_store.query({id: new RegExp('^'+exploded_command[0]+'.'+exploded_command[1], "i"), type: 'method'}).forEach(function(value) {
							possible.push([value.id,'<span style="color:blue;">'+value.id+'</span> - '+value.description]);
						});
						this.autocomplete_onScreen(possible,exploded_command[0],exploded_command[1]);
					}
				}
			}
			else {
				command = command.replace(/\W+/g,'');
				this.shell_commands_store.query({id: new RegExp('^'+command, "i"), type: new RegExp('application|native', "i")}).forEach(function(value) {
					if (!value.description) {
						possible.push([value.id,'<span style="color:blue;">'+value.id+'</span>']);
					}
					else {
						possible.push([value.id,'<span style="color:blue;">'+value.id+'</span> - '+value.description]);
					}
					
				});
				this.autocomplete_onScreen(possible,command,false);
			}

		},
		
		autocomplete_onScreen: function(pos, app, met) {

			if (pos.length == 1) {
				this.currentArea.set('value',pos[0][0]);
			}
			else if (pos.length > 1) {

				this.setLoadingState();
			
				this.currentArea.set('readonly',true);
				
				this.currentSignal.remove();

				this.currentArea.on("keypress", function(evt) {
					if (!(evt.ctrlKey || evt.metaKey)) { myself.signals.inactive_area(evt); }
				});

				var p = [], c = [];

				for (var i in pos) {
					c.push(pos[i][0]);
					p.push(pos[i][1]);
				}

				this.parseResults({success: true, result: p});

				var a = c.slice(0).sort();
				var w1 = a[0];
				var w2 = a[a.length-1];
				var i = 0;
				while(w1.charAt(i) == w2.charAt(i)) ++i;
				this.currentArea.set('value',w1.substring(0, i));

				//if (met === false) {
				//	this.currentArea.set('value',app);
				//}
				//else {
				//	var a = p.slice(0).sort();
				//	var w1 = a[0];
				//	var w2 = a[a.length-1];
				//	var i = 0;
				//	while(w1.charAt(i) == w2.charAt(i)) ++i;
				//	this.currentArea.set('value',w1.substring(0, i));
				//	//this.currentArea.set('value',app+'.'+met);
				//}

			}
			else {
				//do nothing...
			}

		},

		reserved_commands: {
			comodojo_login: function() {
				myself.resultOnScreen(myself.visualization._string.info('Command "comodojo.login" not allowed in shell. Please use "login" instead'));
			},
			comodojo_logout: function() {
				myself.resultOnScreen(myself.visualization._string.info('Command "comodojo.logout" not allowed in shell. Please use "logout" instead'));
			}
		},
		
		shell_commands: {
			
			applications: function() {
				var oo = myself.output_object;
				myself.output_object = 'mlist';
				myself.kernelRequest({
					application: 'comodojo',
					method: 'applications'
				}, function(data) {
					data.result = {
						'Available applications': data.result
					};
					myself.parseResults(data);
				}, function(data) {
					myself.parseError(data);
				}).then(function(){myself.output_object = oo;});
			},
			
			exit: function() {
				if (myself._inConnection) {
					return myself.shell_commands.disconnect(false);
				}
				else if (myself.userRole == 0) {
					myself.resultOnScreen(myself.visualization._string.warning("No session to exit from"));
				}
				else {
					return myself.shell_commands.logout();
				}
			},
			
			help: function() {
				var shell_help = "<p> + Comodojo shell, framwork version {1} (build {2}).<br/>Product name: {0}.</p>";
				var shell_usage = "<p> + Shell hints:<ul><li>Write an application to get list of methods</li><li>Ctrl+c to abort command</li><li>Up/Down arrows to navigate command history</li><li>Use brackets to write commands on multiple lines</li><li>Use tab to autocomplete commands</li></ul></p>";
				var shell_commands = "<p> + Available shell commands:<ul>{0}</ul></p>";
				//var shell_commands_array = Object.keys(myself.shell_commands);
				myself.kernelRequest({
					application: 'comodojo',
					method: 'version',
					v: 'ARRAY'
				}, function(data) {
					var o = '';
					myself.shell_commands_store.query({type: 'native'}).forEach(function(value) {
						o += '<li>'+'<span style="color:blue;">'+value.id+'</span> - '+value.description+'</li>';
					});
					myself.resultOnScreen(lang.replace(shell_help,data.result)+shell_usage+lang.replace(shell_commands,[o]));
				}, function(data) {
					myself.parseError(data);
				});
			},
			
			login: function() {
				myself.newInput('User Name (Ctrl-C to abort):',myself.shell_commands_callbacks.login_userName);
			},
			
			logout: function() {
				myself.kernelRequest({
					application: 'comodojo',
					method: 'logout'
				}, function(data) {
					if (data.success) {
						myself.userName = '';
						myself.userRole = 0;
						myself.resultOnScreen(myself.visualization._string.success('Successfully logged out'));
						myself.loadAutocomplete();
					}
					else {
						myself.resultOnScreen(myself.visualization._string.failure('Error logging out: '+data.result.name));
					}
				}, function(data) {
					myself.parseError(data);
				});
			},
			
			history: function() {
				var i,o = '';
				for (i=0;i<myself.commandHistory.length-1;i++) { o += '<li>'+myself.commandHistory[i]+'</li>'; }
					myself.resultOnScreen(lang.replace('<p> + Commands history:<ol>{0}</ol></p>',[o]));
			},
			
			output: function(params) {
				var i, obj_out = [], str_out = [], oobj = Object.keys(myself.visualization._object), ostr = Object.keys(myself.visualization._string);
				for (i in oobj) {
					if (oobj[i].charAt(0) == '_') {
						continue;
					}
					obj_out.push(oobj[i]+(oobj[i] == myself.output_object ? '<span style="color:red;">*</span>' : ''));
				}
				for (i in ostr) {
					if (ostr[i].charAt(0) == '_') {
						continue;
					}
					str_out.push(ostr[i]+(ostr[i] == myself.output_string ? '<span style="color:red;">*</span>' : ''));
				}
				if (params == null) {
					myself.resultOnScreen(myself.visualization._string.info('Select output modifier:'+myself.visualization._object.mlist({object:obj_out,string:str_out})));
				}
				else if (params[0] == 'object' && array.indexOf(obj_out, params[1]) >= 0) {
					myself.output_object = params[1];
					myself.resultOnScreen(myself.visualization._string.success('Selected object modifier: '+params[1]));
				}
				else if (params[0] == 'string' && array.indexOf(str_out, params[1]) >= 0) {
					myself.output_string = params[1];
					myself.resultOnScreen(myself.visualization._string.success('Selected string modifier: '+params[1]));
				}
				else {
					myself.resultOnScreen(myself.visualization._string.failure('Invalid output modifier'));
				}
			},
			
			whoami: function() {
				myself.resultOnScreen(myself.visualization._string.info((myself.userName == '' ? 'guest' : myself.userName) + ' @ ' + myself.siteName + ' as [' + myself.userRole + '] from ' + myself.clientIP));
			},

			connect: function(params) {

				if (!myself.rpcProxy) {
					myself.resultOnScreen(myself.visualization._string.failure("RPC Proxy mode disabled"));
				}
				else if (!params && !myself._inConnection) {
					myself._inConnection = true;
					myself.commandHistoryPointer = myself.commandHistory.push('connect');
					myself.signals.ctrl_c(false);
				}
				else if (!params && myself._inConnection) {
					myself.resultOnScreen(myself.visualization._string.failure("Invalid connect parameters"));
				}
				else {
					var par = {
						name: (Math.random() + 1).toString(36).substring(5),
						server: false,
						//method: 'system.getCapabilities',
						//lookFor: 'faults_interop',
						transport: 'XML',
						key: null,
						port: 80,
						user: false,
						pass: false
					};
					par = lang.mixin(par,params);
					
					if (!par.server || par.server == '') {
						myself.resultOnScreen(myself.visualization._string.failure("Invalid host"));
						return;
					}

					var i = par.transport.toUpperCase() == 'JSON' ? true : false;

					myself._connections[par.name] = {
						name: par.name,
						server: par.server,
						port: par.port,
						status: 'down',
						transport: par.transport.toUpperCase(),
						key: par.key,
						id: i,
						user: par.user,
						pass: par.pass
					};

					myself.kernelRequest({
						application: 'comodojo',
						method: 'rpcproxy',
						server: par.server,
						rpc_transport: par.transport.toUpperCase(),
						key: par.key,
						port: par.port,
						id: i,
						//rpc_method: par.method,
						rpc_method: 'system.getCapabilities',
						params: (!par.user || !par.pass) ? '[null, null]' : json.toJson([par.user,par.pass])
					}, function(data) {
						
						if (data.success && !data.result) {
							myself.resultOnScreen(myself.visualization._string.warning('Host up but null response'));
						}
						else if (data.success) {
							if(data.result.faults_interop) {
							//if (typeof(data.result[par.lookFor])!=="undefined") {
								myself._connections[par.name].status = "up";
								myself._inConnection = true;
								myself.resultOnScreen(myself.visualization._string.success('Connected and linked!'));
							}
							else {
								myself.resultOnScreen(myself.visualization._string.warning('Host up but wrong response'));
							}
						}
						else {
							myself.resultOnScreen(myself.visualization._string.failure('Error: ('+data.result.code+') '+data.result.name));
							delete myself._connections[par.name];
						}

					}, function(data) {
						delete myself._connections[par.name];
						myself.parseError(data);
					});
					
				}
			},

			connections: function() {
				var c = [];
				if (myself.size(myself._connections) == 0) {
					myself.resultOnScreen(myself.visualization._string.warning('No rpc connection here'));
				}
				else {
					var color;
					var user;
					for (var i in myself._connections) {
						color = myself._connections[i].status == 'up' ? 'green' : 'red';
						user = !myself._connections[i].user ? '' : myself._connections[i].user+'@';
						c.push(lang.replace(myself.connections_template,[
							color,
							myself._connections[i].status,
							myself._connections[i].name,
							myself._connections[i].transport,
							user,
							myself._connections[i].server
						]));
					}
					myself.resultOnScreen(c);
				}
			},

			disconnect: function(name) {
				if (name != false && name != '' && name != null) {
					myself.commandHistoryPointer = myself.commandHistory.push("disconnect('"+name+"')");
					if ( delete myself._connections[name] ) {
						myself.resultOnScreen(myself.visualization._string.success('Connection '+name+' unlinked'));
					}
					else {
						myself.resultOnScreen(myself.visualization._string.failure('Unable to find connection: '+name));
					}
				}
				else if (myself._inConnection) {
					myself.commandHistoryPointer = myself.commandHistory.push('disconnect');
					myself._inConnection = false;
					myself.signals.ctrl_c(false);
				}
				else {
					myself.resultOnScreen(myself.visualization._string.warning("Already disconnected"));
				}
			}
			
		},
		
		shell_commands_callbacks: {
			_userName: false,
			login_userName: function(userName) {
				myself.shell_commands_callbacks._userName = userName;
				myself.newPassword('Password (Ctrl-C to abort):',myself.shell_commands_callbacks.login_userPass);
			},
			login_userPass: function(userPass) {
				myself.kernelRequest({
					application: 'comodojo',
					method: 'login',
					userName: myself.shell_commands_callbacks._userName,
					userPass: userPass
				}, function(data) {
					if (data.success) {
						myself.userName = data.result.userName;
						myself.userRole = data.result.userRole;
						myself.resultOnScreen(myself.visualization._string.success('Welcome '+data.result.completeName));
						myself.loadAutocomplete();
					}
					else {
						myself.userName = '';
						myself.userRole = 0;
						myself.resultOnScreen(myself.visualization._string.failure('Error logging in: '+data.result.name));
					}
				}, function(data) {
					myself.parseError(data);
				});
			}
		},
		
		visualization: {
			
			_object: {
				
				auto: function(obj) {
					if (myself.is_array(obj)) {
						return myself.visualization._object._list('auto','<ul>{0}</ul>','<li><span style="display:none;">{0}</span>{1}</li>',obj);
					}
					else {
						var table = '<table class="table">{0}{1}</table>';
					    var thead = '';//'<thead><tr><th>Option</th><th>Value</th></tr></thead>';
					    var tbody = '<tbody>{0}</tbody>';

					    var th = '<th>{0}</th>';
					    var tr = '<tr>{0}</tr>';
					    var td = '<td>{0}</td>';
					    
					    return myself.visualization._object._table("auto",table, thead, tbody, th, tr, td, obj);
					}
				},
				
				table: function(obj) {
					var table = '<table class="table table-hover>{0}{1}</table>';
				    var thead = '<thead><tr><th>Option</th><th>Value</th></tr></thead>';
				    var tbody = '<tbody>{0}</tbody>';

				    var th = '<th>{0}</th>';
				    var tr = '<tr>{0}</tr>';
				    var td = '<td>{0}</td>';
				    
				    return myself.visualization._object._table("table",table, thead, tbody, th, tr, td, obj);
				},
				
				compact_table: function(obj) {
					var table = '<table class="table-condensed">{0}{1}</table>';
				    var thead = '';
				    var tbody = '<tbody>{0}</tbody>';

				    var th = '<th>{0}</th>';
				    var tr = '<tr>{0}</tr>';
				    var td = '<td>{0}</td>';
				    
				    return myself.visualization._object._table("compact_table", table, thead, tbody, th, tr, td, obj);
				},
				
				_table: function(builder, table, thead, tbody, th, tr, td, obj) {
					
					var th_string = '';
				    var td_string = '';
				    var tr_string = '';
					
					var i = 0;
					for (i in obj) {
						switch(typeof(obj[i])) {
							case 'string':
								td_string = lang.replace(td,[i]) + lang.replace(td,[obj[i]]);
							break;
							case 'number':
								td_string = lang.replace(td,[i]) + lang.replace(td,[obj[i]]);
							break;
							case 'object':
								if (obj[i] == null) {
									td_string = lang.replace(td,[i]) + lang.replace(td,['NULL']);
								}
								else {
									td_string = lang.replace(td,[i]) + lang.replace(td,[myself.visualization._object[builder](obj[i])]);
								}
							break;
							case 'boolean':
								td_string = lang.replace(td,[i]) + lang.replace(td,[(!obj[i] ? 'false' : 'true')]);
							break;
							case 'function':
								td_string = lang.replace(td,[i]) + lang.replace(td,['{function}']);
							break;
							default:
								td_string = lang.replace(td,[i]) + lang.replace(td,['{undefined}']);
							break;
						}
						tr_string += lang.replace(tr,[td_string]);
					}
					tbody = lang.replace(tbody,[tr_string]);
					table = lang.replace(table,[thead, tbody]);
					return table;
				},
				
				ulist: function(obj) {
					return myself.visualization._object._list('ulist','<ul>{0}</ul>','<li><strong>{0}</strong>: {1}</li>',obj);
				},
				
				olist: function(obj) {
					return myself.visualization._object._list('olist','<ol>{0}</ol>','<li><strong>{0}</strong>: {1}</li>',obj);
				},
				
				slist: function(obj) {
					return myself.visualization._object._list('slist','<ul>{0}</ul>','<li><span style="display:none;">{0}</span>{1}</li>',obj);
				},
				
				mlist: function(obj) {
					return myself.visualization._object._list('slist','<ul>{0}</ul>','<li><strong>{0}</strong>: {1}</li>',obj);
				},
				
				_list: function (builder, list, li, obj) {
					var li_string = '';
					var i = 0;
					for (i in obj) {
						
						switch(typeof(obj[i])) {
							
							case 'string':
								li_string += lang.replace(li,[i,obj[i]]);
							break;
							case 'number':
								li_string += lang.replace(li,[i,obj[i]]);
							break;
							case 'object':
								if (obj[i] == null) {
									li_string += lang.replace(li,[i,'NULL']);
								}
								else {
									li_string += lang.replace(li,[i,myself.visualization._object[builder](obj[i])]);
								}
							break;
							case 'boolean':
								li_string += lang.replace(li,[i,(!obj[i] ? 'false' : 'true')]);
							break;
							case 'function':
								li_string += lang.replace(li,[i,'{function}']);
							break;
							default:
								li_string += lang.replace(li,[i,'{undefined}']);
							break;
						
						}
						
					}
					
					return lang.replace(list,[li_string]);
				}
				
			},
			
			_string: {
				
				standard: function(str) {
					return str;
				},
				toUpperCase: function(str) {
					return str.toUpperCase();
				},
				toLowerCase: function(str) {
					return str.toLowerCase();
				},
				italic: function(str) {
					return '<i>'+str+'</i>';
				},
				bold: function(str) {
					return '<strong>'+str+'</strong>';
				},
				success: function(str) {
					return '<p class="box bg-success">'+str+'</p>';
				},
				failure: function(str) {
					return '<p class="box bg-danger">'+str+'</p>';
				},
				info: function(str) {
					return '<p class="box bg-primary">'+str+'</p>';
				},
				warning: function(str) {
					return '<p class="box bg-warning">'+str+'</p>';
				}
				
			}
			
		}
			
	});
	
	return shell;
	
});