define(["dojo/dom","dojo/_base/declare","dijit/form/Textarea","dojo/dom-construct","dojo/window",
	"dojo/dom-geometry","dojo/on","dojo/keys","dojo/dom-style","dojo/request","dojo/_base/json",
	"dojo/_base/lang","dijit/form/TextBox","dojo/_base/array"],
function(dom,declare,Textarea,domConstruct,win,domGeom,on,keys,domStyle,request,json,lang,TextBox,array){

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
		
		systemMessage: "{0}:{1}$>",
		
		currentArea: false,
		
		pastAreas: [],
		
		currentSignal: false,
		
		currentShellMessage: false,
		
		currentResult: false,
		
		
		commandHistory: [],
		
		commandHistoryPointer: 0,
		
		
		output_object: 'auto',
		output_string: 'standard',

		
		constructor: function(args) {
			
			declare.safeMixin(this,args);
			
			this.shellNode = dom.byId(this.shellNode);
			this.shellLoader = dom.byId(this.shellLoader);

			/*
			if(typeof String.prototype.trim !== 'function') {
				String.prototype.trim = function() {
					return this.replace(/^\s+|\s+$/g, ''); 
				};
			}
			*/
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
			
			this.startShell();
			
		},
		
		getSystemMessage: function() {
			var _siteName = !this.siteName ? 'comodojo' : this.siteName;
			var _userName = this.userRole == 1 ? ('<span style="color: red;">'+this.userName+'</span>') : this.userName;
			return lang.replace(this.systemMessage,[_siteName,_userName]);
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
		
		commandHistoryBack: function() {
			if (this.commandHistoryPointer == 0) {
				return;
			}
			var pointer = this.commandHistoryPointer-1;
			this.currentArea.set('value',this.commandHistory[pointer]);
			this.commandHistoryPointer = pointer;
		},
		
		commandHistoryForward: function() {
			if (this.commandHistoryPointer >= this.commandHistory.length-1) {
				this.commandHistoryPointer = this.commandHistory.length;
				this.currentArea.set('value','');
			}
			else {
				var pointer = this.commandHistoryPointer+1;
				this.currentArea.set('value',this.commandHistory[pointer]);
				this.commandHistoryPointer = pointer;
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
		
		is_array: function(obj) {
			if( Object.prototype.toString.call( obj ) === '[object Array]' ) {
			    return true;
			}
			return false;
		},
		
		kernelRequest: function(data, load, error) {
			
			return request.post("kernel.php",{
				data: data,
				handleAs: 'json'
			}).then(load,error);
			
		},
		
		startShell: function() {
			
			this.currentArea = this.newArea();
			
			on(window,"resize",this.resizeViewport);
			
			this.setReadyState();
			
			myself = this;
			
		},
		
		newArea: function() {
			
			var shellNodeWidth = win.getBox().w;
			
			var separator = domConstruct.create("div",{style: {
				width: "100%",
				border: "0px solid white",
				//borderBottom: "1px solid green",
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
				type: 'password'
			});
			
			currentArea.set('preMargin',smp.w);
			
			this.shellNode.appendChild(currentArea.domNode);
			
			this.currentSignal = currentArea.on("keydown", function(evt) {
				if (evt.keyCode == keys.ENTER){
					if (myself.areBracketsBalanced()) {
						evt.preventDefault();
						myself.processCommand();
					}
				}
				else if (evt.keyCode == keys.UP_ARROW) {
					evt.preventDefault();
					myself.commandHistoryBack();
				}
				else if (evt.keyCode == keys.DOWN_ARROW) {
					evt.preventDefault();
					myself.commandHistoryForward();
				}
				else if (evt.keyCode == 67 && evt.ctrlKey == true) {
					evt.preventDefault();
					myself.currentArea.set('readonly',true);
					myself.currentSignal.remove();
					myself.resultOnScreen('');
				}
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
				type: type
			});
			
			this.currentArea.set('preMargin',smp.w);
			
			this.shellNode.appendChild(this.currentArea.domNode);
			
			this.currentSignal = this.currentArea.on("keydown", function(evt) {
				if (evt.keyCode == keys.ENTER){
					evt.preventDefault();
					myself.currentArea.set('readonly',true);
					myself.currentSignal.remove();
					callback(myself.currentArea.get('value'));
				}
				else if (evt.keyCode == 67 && evt.ctrlKey == true) {
					evt.preventDefault();
					myself.currentArea.set('readonly',true);
					myself.currentSignal.remove();
					myself.resultOnScreen('');
				}
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
			
			this.shellNode.appendChild(domConstruct.create("div",{style: {
				width: "100%",
				border: "0px solid white",
				//borderBottom: "1px solid green",
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
				if (typeof this.shell_commands[real_command] == 'function') {
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
					//this.parseError('Undefined shell command');
				}
			}
			else if (real_command.length == 2) {
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
			this.resultOnScreen('<p class="box error">'+code+'</p>');			
		},
		
		resultOnScreen: function(result_content) {
			
			this.currentResult = domConstruct.create("div", {
				innerHTML: result_content,
				style: {
					position: "relative",
					top: "0px",
					padding: "0",
					border: "0px solid white"
				}
			});
			
			this.shellNode.appendChild(this.currentResult);
			
			this.pastAreas.push(this.currentArea);
			
			this.currentArea = this.newArea();
			
			this.setReadyState();
			
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
						//'Available shell commands': Object.keys(myself.shell_commands),
						'Available applications': data.result
					};
					myself.parseResults(data);
				}, function(data) {
					myself.parseError(data);
				}).then(function(){myself.output_object = oo;});
			},
			
			exit: function() {
				return myself.shell_commands.logout();
			},
			
			help: function() {
				var shell_help = "<p> + Comodojo shell, framwork version {1} (build {2}).<br/>Product name: {0}.</p>";
				var shell_usage = "<p> + Shell hints:<ul><li>Write an application to get list of methods</li><li>Ctrl+c to abort command</li><li>Up/Down arrows to navigate command history</li><li>Use brackets to write commands on multiple lines</li></ul></p>";
				var shell_commands = "<p> + Available shell commands:<ul>{0}</ul></p>";
				var shell_commands_array = Object.keys(myself.shell_commands);
				myself.kernelRequest({
					application: 'comodojo',
					method: 'version',
					v: 'ARRAY'
				}, function(data) {
					var i, o='';
					for (i in shell_commands_array) { o += '<li>'+shell_commands_array[i]+'</li>'; }
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
						var table = '<table>{0}{1}</table>';
					    var thead = '<thead><tr><th>Option</th><th>Value</th></tr></thead>';
					    var tbody = '<tbody>{0}</tbody>';

					    var th = '<th>{0}</th>';
					    var tr = '<tr>{0}</tr>';
					    var td = '<td>{0}</td>';
					    
					    return myself.visualization._object._table("auto",table, thead, tbody, th, tr, td, obj);
					}
				},
				
				table: function(obj) {
					var table = '<table>{0}{1}</table>';
				    var thead = '<thead><tr><th>Option</th><th>Value</th></tr></thead>';
				    var tbody = '<tbody>{0}</tbody>';

				    var th = '<th>{0}</th>';
				    var tr = '<tr>{0}</tr>';
				    var td = '<td>{0}</td>';
				    
				    return myself.visualization._object._table("table",table, thead, tbody, th, tr, td, obj);
				},
				
				compact_table: function(obj) {
					var table = '<table>{0}{1}</table>';
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
					return '<p class="box success">'+str+'</p>';
				},
				failure: function(str) {
					return '<p class="box error">'+str+'</p>';
				},
				info: function(str) {
					return '<p class="box info">'+str+'</p>';
				},
				warning: function(str) {
					return '<p class="box warning">'+str+'</p>';
				}
				
			}
			
		}
		
		
		
		
	});
	
	return shell;
	
});