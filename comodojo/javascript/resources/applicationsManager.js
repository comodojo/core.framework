/**
 * applicationsManager.js
 * 
 * The base class that handle all application operation and events in comodojo;
 * 
 * @class
 * @package		Comodojo ClientSide Core Packages
 * @author		comodojo.org
 * @copyright	2011 comodojo.org (info@comodojo.org)
 * 
 * WARNING! (documentation needed)
 *		Applications are...
 *
 *		Applications share informations and pointers with comodojo.bus, as:
 *
 */
comodojo.app = {
	
	_register: function() {},
	
	_unregister: function(pid) {},
	
	//***
	_pushRunning: function(pid, appExec, appName, runMode, applicationLink) {
		comodojo.debugDeep('Registering in running register the application: '+appName+' ('+appExec+') with pid: '+pid.split('_')[1]);
		return comodojo.Bus.pushRunningApplication(pid, appExec, appName, runMode, applicationLink);
	},
	
	//***
	_pullRunning: function(pid) {
		var p = comodojo.Bus.pullRunningApplication(pid);
		if (!p) {
			comodojo.debugDeep('Failed to unregister application with pid: '+pid);
		}
		else {
			comodojo.debugDeep('Application with pid: '+pid+' removed from running register');
		}
		return p;
	},
	
	_start: function(appExec, pid, applicationSpace, status) {
		
		var newApp = new comodojo.bus._registeredApplications[appExec].exec(pid, applicationSpace, status);
		
		dojo.byId(pid+'_loadingState').style.display = "none";
		applicationSpace.containerNode.style.display = "block";
		
		comodojo.app._pushRunning(pid, appExec, comodojo.bus._registeredApplications[appExec].properties.title, comodojo.bus._registeredApplications[appExec].properties.runMode, newApp);
		
		newApp.isComodojoApplication = applicationSpace.isComodojoApplication;
		newApp.lock = function() { comodojo.app.lock(pid); };
		newApp.release = function() { comodojo.app.release(pid); };
		newApp.getLocalizedMessage = function(messageCode) { return comodojo.app.getLocalizedMessage(appExec, messageCode); };
		newApp.getLocalizedMutableMessage = function(messageCode, params) { return comodojo.app.getLocalizedMutableMessage(appExec, messageCode, params); };
		newApp.error = function(ec, ed) { comodojo.error.custom(comodojo.app.getLocalizedMessage(appExec, ec), ed); };
		newApp.stop = function() { comodojo.app.stop(pid); };
		newApp.restart = function() { comodojo.app.restart(pid); };
		newApp.resourcesPath = $c._applicationsPath+appExec+'/resources/';
		newApp.utilWindow = function(params) {
			var util = {
				title: 'Info',
				width: 300,
				height: 300,
				resizable: true,
				maxable: true
			};
			dojo.mixin(util, params);
			var util = comodojo.windows.util(util.title,util.width,util.height,util.resizable,util.maxable);
			//util.focus();
			dojo.aspect.after(applicationSpace, 'close', function() {
				util.close();
			});
			return util;
		};
		
		switch (applicationSpace.isComodojoApplication) {
			case "MODAL":
				
			break;
			case "WINDOWED":
				newApp.focusOn = function() { dijit.byId(pid).show(); };
			break;
			case "ATTACHED":
				
			break;
		}
		
		try {
			newApp.init();
		}
		catch (e) {
			comodojo.bus.callEvent('applicationGotError');
			comodojo.error.global("10012",e);
		}
		
		
		/*if (dojo.isFunction(newApp.terminate)) {
			switch (applicationSpace.isComodojoApplication) {
				case "MODAL":
					dojo.connect(applicationSpace, 'onCancel', function(){
						newApp.terminate();	
					});
				break;
				case "WINDOWED":
					dojo.connect(applicationSpace, 'uninitialize', function(){
						newApp.terminate();	
					});
				break;
				case "ATTACHED":
					dojo.connect(applicationSpace, 'uninitialize', function(){
						newApp.terminate();	
					});
				break;
			}
		}
		*/
		comodojo.bus.callEvent('applicationFinishLoading');
		
		applicationSpace.startup();
		
		if (applicationSpace.isComodojoApplication == "MODAL") {
			setTimeout(function() {
				dijit.byId(pid)._position();
			},300);
		}
		
	},
	
	_stop: function(pid) {
		
		var app = comodojo.app.byPid(pid);
		var toReturn = false;
		
		if (!app) {
			comodojo.debug/*Deep*/('Could not destroy application: invalid pid reference or application not running (pid not found); pid was: '+ pid.split('_')[1] );
		}
		else {
			switch (app.isComodojoApplication){
				case "WINDOWED":
					comodojo.debugDeep('Stopped windowed application; pid was: '+ pid.split('_')[1] );
					comodojo.app._pullRunning(pid);
				break;
				case "MODAL":
					comodojo.debug/*Deep*/('Stopped modal application; pid was: '+ pid.split('_')[1] );
					comodojo.app._pullRunning(pid);
					toReturn = true;
				break;
				case "ATTACHED":
					comodojo.debugDeep('Stopped attached application; pid was: '+ pid.split('_')[1] );
					//dijit.byId(pid).destroyRecursive();
					comodojo.destroySomething(pid);
					comodojo.app._pullRunning(pid);
					toReturn = true;
				break;
				default:
					comodojo.debug/*Deep*/('Could not destroy application: invalid application format! pid was: '+ pid.split('_')[1] );
				break;
			}
			//comodojo.destroySomething(pid);
		}
		
		return toReturn;
		
	},
		
	_preload: function(appExec, pid, applicationSpace, status) {
		
		var appExecFile = comodojo._applicationsPath+appExec+"/"+appExec+".js";
		try {
			comodojo.loadScriptFile(appExecFile, {
				forceReload: true,
				onLoad: function(){
					dojo.addOnLoad(function(){
						comodojo.app._start(appExec, pid, applicationSpace, status);
					});
				}
			});
		}
		catch (e) {
			comodojo.bus.callEvent('applicationGotError');
			comodojo.error.global("10012",e);
		}
		
	},
	
	load: function(appExec, application) {
		comodojo.debug/*Deep*/('Loading application: '+appExec);
		comodojo.bus._registeredApplications[appExec].exec = application;
	},
	
	start: function(appExec, status) {
		
		this.toReturn = false;
		
		//Here the fun starts!
		//If app isn't registered, quit!
		if (!comodojo.app.isRegistered(appExec)) {
			comodojo.debug("Failed to start application '"+appExec+"'; application not registered.");
			comodojo.bus.callEvent('applicationGotError');
			comodojo.error.global("10007","");
		}
		//if app is yet started, check if it's unique
		else if(comodojo.app.isRunning(appExec) && comodojo.bus._registeredApplications[appExec].properties.unique) {
			if (comodojo.bus._registeredApplications[appExec].properties.forceReInit) {
				comodojo.debug("Requested to start a running application tagged as unique & to-re-init, application will be restarted.");
				comodojo.app.restart(comodojo.app.getPid(appExec));
				this.toReturn = true;
			}
			else {
				comodojo.debug("Requested to start a running application tagged as unique, now focusing on.");
				comodojo.app.byExec(appExec).focusOn();
				this.toReturn = true;
			}
		}
		//if it's ready-to-launch, check if it require windows
		else if ( (comodojo.bus._registeredApplications[appExec].properties.type == 'windowed') && (!dojo.isFunction(comodojo.windows.application)) ) {
			comodojo.debug("Failed to start application '"+appExec+"'; application require 'windows' module (not loaded).");
			comodojo.bus.callEvent('applicationGotError');
			comodojo.error.global("10012","");
		}
		//last, exec app!
		else {
			
			comodojo.debug('Request to start new application: '+comodojo.bus._registeredApplications[appExec].properties.title+' ('+appExec+').');
			
			comodojo.bus.callEvent('applicationStartLoading');
			
			var pid = comodojo.getPid();
			var prop = comodojo.bus._registeredApplications[appExec].properties;
			var applicationSpace = false;
			var loadingState = dojo.create('div',{
				id: pid+'_loadingState',
				innerHTML: comodojo.bus._registeredApplications[appExec].properties.runMode == "system" ? '<span><img src="comodojo/images/small_loader.gif" />' + comodojo.getLocalizedMessage("10007") + '</span>' : '<p style="text-align: center; padding: 10px;" ><img src="comodojo/images/small_loader.gif" /></p><p style="font-weight: bold; font-size: large; text-align: center;">'+comodojo.bus._registeredApplications[appExec].properties.title+'</p><p style="text-align: center;">'+comodojo.bus._registeredApplications[appExec].properties.description+'</p>'	
			});
			
			//icon support
			var icon = prop.iconSrc == 'self' ? (comodojo._applicationsPath + appExec + '/resources/icon_16.png') : "comodojo/icons/16x16/run.png";
			
			switch(comodojo.bus._registeredApplications[appExec].properties.type) {
					
				case "windowed":
				
					applicationSpace = comodojo.windows.application(pid,prop.title,prop.width,prop.height,prop.resizable,prop.maxable,icon);
			
					applicationSpace.connect(applicationSpace, 'close', function(){
						applicationSpace.minNode.style.display = "none";
						applicationSpace.closeNode.style.display = "none";
						applicationSpace.maxNode.style.display = "none";
						applicationSpace.restoreNode.style.display = "none";
					});
					
					applicationSpace.connect(applicationSpace, 'close', function(){
						applicationSpace.closeNode.style.display = "none";
						comodojo.app._stop(pid);
					});
					
					applicationSpace.isComodojoApplication = "WINDOWED";
					applicationSpace.containerNode.style.display = "none";
					
					applicationSpace.canvas.appendChild(loadingState);
					
					applicationSpace.bringToTop();
				
				break;
				
				case "modal":
				
					applicationSpace = comodojo.dialog._application(pid, comodojo.bus._registeredApplications[appExec].properties.title, "", false);

					var resSize = {};
					if (prop.width != false) {
						resSize.w = parseInt(prop.width,10)+'px';
						//applicationSpace.style.width = (parseInt(prop.width,10)-20)+'px';
					} 
					if (prop.height != false) {
						resSize.h = parseInt(prop.height,10)+'px';
						//applicationSpace.containerNode.style.height = (parseInt(prop.height,10)-20)+'px';
					}
					
					applicationSpace.isComodojoApplication = "MODAL";
					applicationSpace.containerNode.style.display = "none";
					
					applicationSpace.domNode.appendChild(loadingState);
					
					if (prop.width != false || prop.height != false) {
						applicationSpace._layout(false, resSize);
					}
					
					applicationSpace._position();
					
					applicationSpace.on('cancel',function() {comodojo.app._stop(pid);});
					
				break;
				
				case "attached":
				
					var myNode;
					if (comodojo.bus._registeredApplications[appExec].properties.attachNode == "body") {
						myNode = dojo.body();
					}
					else {
						myNode = typeof comodojo.bus._registeredApplications[appExec].properties.attachNode == "object" ? comodojo.bus._registeredApplications[appExec].properties.attachNode : dojo.byId(comodojo.bus._registeredApplications[appExec].properties.attachNode);
					}
						
					var as_width,as_height;
						if (comodojo.bus._registeredApplications[appExec].properties.width == "auto") {
							as_width = (dojo.coords(myNode).w - 2) + "px";
						}
						else if (isFinite(comodojo.bus._registeredApplications[appExec].properties.width)) {
							as_width = comodojo.bus._registeredApplications[appExec].properties.width + "px";
						}
						else {
							as_width = comodojo.bus._registeredApplications[appExec].properties.width;
						}
						if (comodojo.bus._registeredApplications[appExec].properties.height == "auto") {
							as_height = (dojo.coords(myNode).h - 2) + "px";
						}
						else if (isFinite(comodojo.bus._registeredApplications[appExec].properties.height)) {
							as_height = comodojo.bus._registeredApplications[appExec].properties.height + "px";
						}
						else {
							as_height = comodojo.bus._registeredApplications[appExec].properties.height;
						}
						
					var as_style = (comodojo.bus._registeredApplications[appExec].properties.width !== false ? ("width:"+as_width + ";") : "") + (comodojo.bus._registeredApplications[appExec].properties.height !== false ? ("height:"+as_height+";") : "");
					
					if (!comodojo.bus._registeredApplications[appExec].properties.requestSpecialNode) {
						applicationSpace = new dijit.layout.ContentPane({
							id: pid,
							preventCache: true,
							style: as_style
						});
						applicationSpace.on('close',function() {comodojo.app._stop(pid);});
					}
					else {
						switch (comodojo.bus._registeredApplications[appExec].properties.requestSpecialNode) {
							case ("div"):
								applicationSpace = dojo.create('div', {
									id: pid,
									style: as_style
								});
								applicationSpace.domNode = applicationSpace;
								applicationSpace.startup = function() {return;};
								var cnt = dojo.create('div',{
									id: pid+"_containerNode"
								});
								applicationSpace.appendChild(cnt);
								applicationSpace.containerNode = cnt;
							break;
							case ("span"):
								applicationSpace = dojo.create('span', {
									id: pid,
									style: as_style
								});
								applicationSpace.domNode = applicationSpace;
								applicationSpace.startup = function() {return;};
								var cnt = dojo.create('span',{
									id: pid+"_containerNode"
								});
								applicationSpace.appendChild(cnt);
								applicationSpace.containerNode = cnt;
							break;
							default:
								var theConstructor = eval(comodojo.bus._registeredApplications[appExec].properties.requestSpecialNode);
								applicationSpace = new theConstructor ({
									id: pid,
									style: as_style
								});
							break;
						}
						
					}
					
					applicationSpace.isComodojoApplication = "ATTACHED";
					applicationSpace.containerNode.style.display = "none";
					
					applicationSpace.domNode.appendChild(loadingState);
					
					if (!comodojo.bus._registeredApplications[appExec].properties.placeAt) {
						myNode.appendChild(applicationSpace.domNode);
					}
					else {
						$d.place(applicationSpace.domNode, myNode, comodojo.bus._registeredApplications[appExec].properties.placeAt);
					}
					
					
					
				break;
				
				default:
					
				break;
				
			}
			
			//if (!comodojo.app.isPreloaded(appExec)) {
			//	comodojo.debug/*Deep*/('Application '+appExec+' not in registry; preloading...');
				comodojo.app._preload(appExec, pid, applicationSpace, status);
			//}
			//else{
			//	comodojo.debug/*Deep*/('Application '+appExec+' in registry; NOT preloading...');
			//	comodojo.app._start(appExec, pid, applicationSpace, status);
			//}
			
			this.toReturn = true;
			
		}
		
		return this.toReturn;
		
	},
	
	restart: function(pid) {
		
		var p = comodojo.app.getExec(pid);
		comodojo.app.stop(pid);
		setTimeout(function() {
			comodojo.app.start(p);
		}, 1000);
		
	},
	
	stop: function(pid) {
		
		var app = comodojo.app.byPid(pid);
		var toReturn = false;
		
		if (!app) {
			comodojo.debug/*Deep*/('Could not destroy application: invalid pid reference or application not running (pid not found); pid was: '+ pid.split('_')[1] );
		}
		else {
			switch (app.isComodojoApplication){
				case "WINDOWED":
					comodojo.debug/*Deep*/('Stopping windowed application...');
					if (dijit.byId(pid)._isDocked) {
						dijit.byId(pid).show();	
					}
					dijit.byId(pid).close();
					toReturn = true;
				break;
				case "MODAL":
					comodojo.debug/*Deep*/('Stopping modal application...');
					dijit.byId(pid).onCancel();
					toReturn = true;
				break;
				case "ATTACHED":
					comodojo.debug/*Deep*/('Stopping attached application...');
					toReturn = comodojo.app._stop(pid);
				break;
				default:
					comodojo.debug/*Deep*/('Could not destroy application: invalid application format! pid was: '+ pid.split('_')[1] );
				break;
			}
		}
	 
	 	return toReturn;
	 
	},
	
	stopAnyInstance: function(appExec) {
		
		comodojo.debug('Stopping any istance of: '+appExec);
		
		var instances = comodojo.app.getPid(appExec);
		var count = 0;
		
		if (!instances) {
			comodojo.debug('No instances of "'+appexec+'" founded!');
			return;
		}
		else if (dojo.isArray(instances)) {
			var i;
			for (i in instances) {
				comodojo.app.stop(instances[i]);
				count++;
			}
		}
		else {
			comodojo.app.stop(instances);
			count++;
		}
		comodojo.debug('Stopped '+count+' instance(s) of '+appExec+'.');
		
	},
	
	stopAll: function(stopAlsoSystemApps, callBack) {
		
		comodojo.debug(!stopAlsoSystemApps ? 'Stopping USER applications' : 'Stopping ALL applications');
		
		var count = 0;
		var toStop = [];
		
		//if (stopAlsoSystemApps) {
		//	dijit.byId('comodojoMenu_docker_dock').destroyRecursive();
		//}
		var i,o;
		for (i in comodojo.bus._runningApplications) {
			if (comodojo.bus._runningApplications[i][3] == 'system' && !stopAlsoSystemApps) {
				continue;
			}
			else {
				//comodojo.app.stop(comodojo.bus._runningApplications[i][0]);
				toStop.push(comodojo.bus._runningApplications[i][0]);
				count++;
			}
		}
		
		for (o in toStop) {
			//console.log(toStop[o]);
			comodojo.app.stop(toStop[o]);	
		}
		comodojo.debug('Stopped '+count+' applications');
		
		/*comodojo.debug('Stopping helper widgets...');
		
		dijit.registry.forEach(function(widget){
			comodojo.debugDeep('Killing widget: '+widget.id);
			widget.destroyRendering();
			widget.destroyRecursive();
		});
		*/
		
		if ($d.isFunction(callBack)) {
			callBack();
		}
		
		return count;

	},
	
	isRegistered: function(appExec) {
		return (dojo.isObject(comodojo.bus._registeredApplications[appExec]) ? true : false);
	},
	
	isPreloaded: function(appExec) {
		return (dojo.isFunction(comodojo.bus._registeredApplications[appExec].exec) ? true : false);
	},
	
	isRunning: function(appExec) {
		var i;
		for (i in comodojo.bus._runningApplications) {
			if (comodojo.bus._runningApplications[i][1] == appExec) {
				return true;
			}
		}
		return false;
	},
	
	hasFocus: function() {},
	
	setFocus: function(pid) {
		
		if (!comodojo.isSomething(pid).success) {
			comodojo.debug('Nothing to focus on, pid requested was: '+pid);
		}
		else {
			if (dijit.byId(pid).isComodojoApplication == "WINDOWED") {
				if (dijit.byId(pid)._isMinimized) {
					dijit.byId(pid).show();
				}
				dijit.byId(pid).bringToTop();
			}
			else {
				dojo.byId(pid).focus();
			}
			
		}
	},
	
	getProperties: function(appExec) {
		
		return $c.app.isRegistered(appExec) ? $c.bus._registeredApplications[appExec].properties : false;
		
	},
	
	byPid: function(pid) {
		var n;
		for (n in comodojo.bus._runningApplications) {
			
			if (comodojo.bus._runningApplications[n][0] == pid) {
				
				return comodojo.bus._runningApplications[n][4];
				
			}
			
			else {
				
				continue;

			}
		
		}
		
		return false;
		
	},
	
	byExec: function(appExec) {
		var instances = [];
		var i;
		for (i in comodojo.bus._runningApplications) {
			if (comodojo.bus._runningApplications[i][1] == appExec) {
				instances.push(comodojo.bus._runningApplications[i][4]);
			}
		}
		if (instances.length == 0) {
			return false;
		}
		else if(instances.length == 1) {
			return instances[0];
		}
		else {
			return instances;
		}
	},
	
	getPid: function(appExec) {
		var instances = [];
		var i;
		for (i in comodojo.bus._runningApplications) {
			if (comodojo.bus._runningApplications[i][1] == appExec) {
				instances.push(comodojo.bus._runningApplications[i][0]);
			}
		}
		if (instances.length == 0) {
			return false;
		}
		else if(instances.length == 1) {
			return instances[0];
		}
		else {
			return instances;
		}
	},
	
	getExec: function(pid) {
		var n;
		for (n in comodojo.bus._runningApplications) {
			
			if (comodojo.bus._runningApplications[n][0] == pid) {
				
				return comodojo.bus._runningApplications[n][1];
				
			}
			
			else {
				
				continue;

			}
		
		}
		
		return false;
		
	},
	
	lock: function(pid) {
		if (comodojo.isSomething(pid).type == "WIDGET") {
			dijit.byId(pid).containerNode.style.display = "none";
		}
		else {
			dojo.byId(pid).containerNode.style.display = "none";
		}
		dojo.byId(pid+'_loadingState').style.display = "block";
	},
	
	release: function(pid) {
		if (comodojo.isSomething(pid).type == "WIDGET") {
			dijit.byId(pid).containerNode.style.display = "block";
		}
		else {
			dojo.byId(pid).containerNode.style.display = "block";
		}
		dojo.byId(pid+'_loadingState').style.display = "none";
	},
	
	loadCss: function(appExec) {
		comodojo.loadCss(comodojo._applicationsPath + appExec + '/resources/' + appExec + '.css');
	},
	
	getLocalizedMessage: function(appExec, messageCode) {
		return dojo.isString(comodojo.bus._registeredApplications[appExec].i18n[messageCode]) ? comodojo.bus._registeredApplications[appExec].i18n[messageCode] : "__?("+messageCode+")?__";
	},
	
	getLocalizedMutableMessage: function(appExec, messageCode, params) {
		return dojo.isString(comodojo.bus._registeredApplications[appExec].i18n[messageCode]) ? dojo.string.substitute(comodojo.bus._registeredApplications[appExec].i18n[messageCode], params) : "__?("+messageCode+")?__";
	}
		
};
