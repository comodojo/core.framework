define(["dojo/_base/lang","dojo/dom","dojo/aspect","dojo/on","dojo/dom-construct","dojo/dom-geometry","dojo/dom-style",
	"dijit/layout/ContentPane","dijit/registry",
	"comodojo/Utils","comodojo/Bus","comodojo/Dialog","comodojo/Error","comodojo/Window"],
function(lang,dom,aspect,on,domConstruct,domGeom,domStyle,ContentPane,registry,utils,bus,dialog,error,Window){

var App = {
	// summary:
	// description:
};
lang.setObject("comodojo.App", App);

App.pushRunning = function(pid, appExec, appName, runMode, applicationLink) {
	// summary:
	//		Push application in running register
	// returns:
	//		Index of the application in running register
	comodojo.debugDeep('Registering in running register the application: '+appName+' ('+appExec+') with pid: '+pid.split('_')[1]);
	return bus.pushRunningApplication(pid, appExec, appName, runMode, applicationLink);
};
	
App.pullRunning = function(pid) {
	// summary:
	//		Remove application from running register
	// returns: bool
	var p = bus.pullRunningApplication(pid);
	if (!p) {
		comodojo.debugDeep('Failed to unregister application with pid: '+pid);
	}
	else {
		comodojo.debugDeep('Application with pid: '+pid+' removed from running register');
	}
	return p;
};

App.isRunning = function(appExec) {
	// summary:
	//		Check if application is running
	// returns: bool
	var i;
	for (i in bus._runningApplications) {
		if (bus._runningApplications[i][1] == appExec) {
			return true;
		}
	}
	return false;
};

App.isRegistered = function(appExec) {
	return bus.getRegisteredApplication(appExec);
};

App.getPid = function(appExec) {
	var instances = [];
	var i;
	for (i in bus._runningApplications) {
		if (bus._runningApplications[i][1] == appExec) {
			instances.push(bus._runningApplications[i][0]);
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
};

App.byPid = function(pid) {
	var n;
	for (n in bus._runningApplications) {
		if (bus._runningApplications[n][0] == pid) {
			return bus._runningApplications[n][4];
		}
		else { continue; }
	}
	return false;
};

App.byExec = function(appExec) {
	var instances = [];
	var i;
	for (i in bus._runningApplications) {
		if (bus._runningApplications[i][1] == appExec) {
			instances.push(bus._runningApplications[i][4]);
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
};

App.getExec = function(pid) {
	var n;
	for (n in bus._runningApplications) {
		if (bus._runningApplications[n][0] == pid) {
			return bus._runningApplications[n][1];	
		}
		else {
			continue;
		}
	}	
	return false;
};


App.preload = function(appExec, pid, applicationSpace, status) {
	var appExecFile = comodojo.applicationsPath+appExec+"/"+appExec+".js";
	
	comodojo.loadScriptFile(appExecFile,{forceReload:true/*,sync:true*/},function() {
		comodojo.App.launch(appExec, pid, applicationSpace, status);
	});
};

App.load = function(appExec, application) {
	var reg = bus.registerApplication(appExec, application);
	if (!reg) {
		comodojo.debug('Unable to load application: '+appExec+'; maybe yet in registry?');
	}
	else {
		comodojo.debug('Loaded application: '+appExec);
	}
};

App.autostart = function() {
	comodojo.debug('Launching applications to autostart');
	for (var i in comodojo.Bus._autostartApplications) {
		comodojo.App.start(comodojo.Bus._autostartApplications[i]);
	};
};

App.start = function(appExec, status, on_start, on_stop, force_properties) {

	var app_reg = bus.getRegisteredApplication(appExec);

	// First, check if application is registered
	if (!app_reg) {
		comodojo.debug("Failed to start application '"+appExec+"': application not registered.");
		bus.callEvent('comodojo_app_error');
		error.generic("10029",comodojo.getLocalizedMessage('10029'),'Application not registered');
		return false;
	}

	// Then check if app is running, so check if it's unique
	if(App.isRunning(appExec) && bus._registeredApplications[appExec].properties.unique) {
		if (bus._registeredApplications[appExec].properties.forceReInit) {
			comodojo.debug("Requested to start a running application tagged as unique & to-re-init, application will be restarted.");
			App.restart(App.getPid(appExec));
			return true;
		}
		else {
			comodojo.debug("Requested to start a running application tagged as unique, now focusing.");
			App.byExec(appExec).focus();
			return true;
		}
	}
	
	comodojo.debug('Request to start new application: '+bus._registeredApplications[appExec].properties.title+' ('+appExec+').');

	bus.callEvent('comodojo_app_load_start');

	var pid = comodojo.getPid();
	var prop = bus._registeredApplications[appExec].properties;

	//override selected properties if force_properties populated
	if (utils.defined(force_properties) && lang.isObject(force_properties)) {
		prop.type = utils.defined(force_properties.type) ? force_properties.type : prop.type;
		prop.width = utils.defined(force_properties.width) ? force_properties.width : prop.width;
		prop.height = utils.defined(force_properties.height) ? force_properties.height : prop.height;
		prop.resizable = utils.defined(force_properties.resizable) ? force_properties.resizable : prop.resizable;
		prop.maxable = utils.defined(force_properties.maxable) ? force_properties.maxable : prop.maxable;
		prop.attachNode = utils.defined(force_properties.attachNode) ? force_properties.attachNode : prop.attachNode;
		prop.requestSpecialNode = utils.defined(force_properties.requestSpecialNode) ? force_properties.requestSpecialNode : prop.requestSpecialNode;
		prop.placeAt = utils.defined(force_properties.placeAt) ? force_properties.placeAt : prop.placeAt;
	}

	var applicationSpace = false;
	var loadingState = domConstruct.create('div',{
		innerHTML: prop.runMode == "system" ? '<span><img src="comodojo/images/small_loader.gif" />' + comodojo.getLocalizedMessage("10007") + '</span>' : '<p style="text-align: center; padding: 10px;" ><img src="comodojo/images/small_loader.gif" /></p><p style="font-weight: bold; font-size: large; text-align: center;">'+prop.title+'</p><p style="text-align: center;">'+prop.description+'</p>'	
	});
	var icon = prop.iconSrc == 'self' ? (comodojo.applicationsPath + appExec + '/resources/icon_16.png') : comodojo.icons.getIcon('run',16);
	
	switch(prop.type) {
		
		case 'windowed':

			applicationSpace = Window.application(pid,prop.title,prop.width,prop.height,prop.resizable,prop.maxable,icon);
		
			applicationSpace.set('isComodojoApplication',"WINDOWED");
				
			applicationSpace.containerNode.style.display = "none";
			applicationSpace.lockNode = loadingState;
			applicationSpace.canvas.appendChild(applicationSpace.lockNode);

			aspect.after(applicationSpace, 'close', function(){
				applicationSpace.minNode.style.display = "none";
				applicationSpace.closeNode.style.display = "none";
				applicationSpace.maxNode.style.display = "none";
				applicationSpace.restoreNode.style.display = "none";
				applicationSpace.closeNode.style.display = "none";
				comodojo.App.kill(pid,appExec);
			});

			applicationSpace.bringToTop();

			aspect.before(applicationSpace,'close',function() {
				comodojo.App.triggerOnStop(pid);
			});

			if (lang.isFunction(on_stop)) {
				aspect.after(applicationSpace,'close',on_stop);
			}

		break;

		case 'modal':

			var _w = (prop.width != false || prop.width.toLowerCase != 'auto') ? prop.width : false;
			var _h = (prop.height != false || prop.height.toLowerCase != 'auto') ? prop.height : false;

			applicationSpace = dialog.application(pid, prop.title, "", false, _w, _h)._dialog;

			applicationSpace.set('isComodojoApplication',"MODAL");
			applicationSpace.containerNode.style.display = "none";
			applicationSpace.lockNode = loadingState;
			applicationSpace.domNode.appendChild(applicationSpace.lockNode);
			
			applicationSpace.close = function() {
				applicationSpace.hide();
			};

			applicationSpace._position();

			aspect.after(applicationSpace, "hide", function(){
				comodojo.App.kill(pid,appExec);
			});

			aspect.before(applicationSpace,'hide',function() {
				comodojo.App.triggerOnStop(pid);
			});

			if (lang.isFunction(on_stop)) {
				aspect.after(applicationSpace,'hide',on_stop);
			}

		break;

		case 'attached':
		
			var myNode;
			if (prop.attachNode == "body") {
				myNode = dojo.body();
			}
			else if (typeof prop.attachNode == "object") {
				myNode = prop.attachNode;
			}
			else {
				myNode = dom.byId(prop.attachNode);
			}

			var as_width,as_height;
			
			var computedStyle = domStyle.getComputedStyle(myNode);
			
			if (!prop.width) {as_width = '';}
			else if (prop.width == "auto") { as_width = "width:" + (domGeom.getMarginBox(myNode,computedStyle).w - 2) + "px"; }
			else if (prop.width == "adapt") { as_width = "width:100%"; }
			else if (isFinite(prop.width)) { as_width = "width:" + prop.width + "px"; }
			else { as_width = "width:" + prop.width; }

			if (!prop.height) {as_height = '';}
			if (prop.height == "auto") { as_height = "height:" + (domGeom.getMarginBox(myNode,computedStyle).h - 2) + "px"; }
			else if (prop.height == "adapt") { as_width = "height:100%"; }
			else if (isFinite(prop.height)) { as_height = "height:" + prop.height + "px"; }
			else { as_height = "height:" + prop.height; }

			if (!prop.requestSpecialNode) {
				applicationSpace = new ContentPane({
					id: pid,
					preventCache: true,
					style: as_width + (as_width == '' ? as_height : (';'+as_height))
				});
				applicationSpace.close = function() {
					applicationSpace.destroyRecursive();
				};
				aspect.after(applicationSpace, "close", function(){
					comodojo.App.kill(pid,appExec);
				});
			}
			else {
				applicationSpace = domConstruct.create(prop.requestSpecialNode, {
					id: pid,
					style: 'width:'+as_width+';height:'+as_height
				});

				applicationSpace.startup = function() {return;};
				applicationSpace.close = function() {
					if (typeof registry.byId(pid) !== "undefined") {
						registry.byId(pid).destroyRecursive();
					}
					comodojo.App.kill(pid,appExec);
				};

				applicationSpace.containerNode = domConstruct.create(prop.requestSpecialNode, {
					id: pid+"_containerNode"
				});

				applicationSpace.domNode = applicationSpace;
			}

			applicationSpace.isComodojoApplication = "ATTACHED";
			applicationSpace.containerNode.style.display = "none";
			applicationSpace.lockNode = loadingState;

			applicationSpace.domNode.appendChild(applicationSpace.lockNode);

			if (utils.inArray(utils.defined(prop.placeAt.toLowerCase) ? prop.placeAt.toLowerCase() : '',['before', 'after', 'replace', 'only', 'first', 'last'])) {
				domConstruct.place(applicationSpace.domNode, myNode, prop.placeAt.toLowerCase());
			}
			else {
				domConstruct.place(applicationSpace.domNode, myNode);
			}

			aspect.before(applicationSpace,'close',function() {
				comodojo.App.triggerOnStop(pid);
			});

			if (lang.isFunction(on_stop)) {
				aspect.after(applicationSpace,'close',on_stop);
			}

		break;

		default:
			comodojo.debug("Failed to start application '"+appExec+"': unsupported display mode.");
			bus.callEvent('comodojo_app_error');
			error.generic("10029",comodojo.getLocalizedMessage('10029'),'Unsupported display mode');
			return false;
		break; 

	}

	if (lang.isFunction(on_start)) {
		on_start();
	}

	App.preload(appExec, pid, applicationSpace, status);
};

App.launch = function(appExec, pid, applicationSpace, status) {
	//console.log(appExec);
	//console.log(pid);
	//console.log(applicationSpace);
	//console.log(status);

	var newApp = new bus._registeredApplications[appExec].exec(pid, applicationSpace, status);
	/*
	catch (e) {
		comodojo.debug('Application '+appExec+' did not load itself:');
		comodojo.debug(e);
		comodojo.Error.generic('0000', 'Application did not load', e);
		//applicationSpace.containerNode.innerHTML = e;
		bus.callEvent('comodojo_app_error');
		App.kill(pid);
		return;
	}*/
	
	App.pushRunning(pid, appExec, bus._registeredApplications[appExec].properties.title, bus._registeredApplications[appExec].properties.runMode, newApp);
	
	newApp.isComodojoApplication = applicationSpace.isComodojoApplication;
	newApp.lock = function() { comodojo.App.lock(pid); };
	newApp.release = function() { comodojo.App.release(pid); };
	newApp.getLocalizedMessage = function(messageCode) { return comodojo.App.getLocalizedMessage(appExec, messageCode); };
	newApp.getLocalizedMutableMessage = function(messageCode, params) { return comodojo.App.getLocalizedMutableMessage(appExec, messageCode, params); };
	newApp.error = function(ec, ed) {
		bus.callEvent('comodojo_app_error');
		error.generic(ec, comodojo.App.getLocalizedMessage(appExec, ec), ed);
	};
	newApp.stop = function() { comodojo.App.stop(pid); };
	newApp.restart = function() { comodojo.App.restart(pid); };
	newApp.resourcesPath = comodojo.applicationsPath+appExec+'/resources/';
	
	applicationSpace.containerNode.style.display = "block";
	applicationSpace.lockNode.style.display = "none";

	newApp.close = applicationSpace.close;
	newApp.onstart = lang.isFunction(newApp.onstart) ? newApp.onstart : function() {return;};
	newApp.onstop = lang.isFunction(newApp.onstop) ? newApp.onstop : function() {return;};

	if (applicationSpace.isComodojoApplication == "WINDOWED") {
		newApp.focus = function() {
			if (applicationSpace._isMinimized) { applicationSpace.show(); }
			applicationSpace.bringToTop();
		}
	}
	
	try {
		newApp.init();
		switch(applicationSpace.isComodojoApplication) {
			case "WINDOWED":
				applicationSpace.startup();
			break;
			case "MODAL":
				applicationSpace.startup();
				setTimeout(function() {
					registry.byId(pid)._position();
				},500);
			break;
			case "ATTACHED":
				bus.callEvent('comodojo_app_require_resize');
			break;
		}
		bus.callEvent('comodojo_app_load_end');
		newApp.onstart();
	}
	catch (e) {
		comodojo.debug('Application '+appExec+' got error:');
		comodojo.debug(e);
		comodojo.Error.generic('0000', 'Application error', e);
		//applicationSpace.containerNode.innerHTML = e;
		bus.callEvent('comodojo_app_error');
		App.kill(pid);
	}

	//if (comodojo.stateFired > 1) {
	//	comodojo.stateFired--;
	//}
	//else if (comodojo.stateFired == 1) {
	//	comodojo.stateFired--;
	//	dojo.back.setInitialState(new comodojo.state('start','comodojo','pid_0'));
	//	console.warn('back complete, firing initial state');
	//}
	//else {
	//	console.warn('back on the way, firing app state');
	//	dojo.back.addToHistory(new comodojo.state('start',appExec,pid));
	//}
	
};

App.restart = function(pid) {
	var p = App.getExec(pid);
	if (!p) {
		comodojo.debug('Could not restart application: '+pid);
	}
	else {
		App.stop(pid);
		setTimeout(function() {comodojo.App.start(p);}, 1000);
	}
};

App.triggerOnStop = function(pid) {
	try{
		App.byPid(pid).onstop();
	}
	catch (e) {
		comodojo.debug('Could not process internal app onstop: '+e);
	}
}

App.stop = function(pid) {
	var app = App.byPid(pid);
	if (!app) {
		comodojo.debug('Could not stop application, invalid pid reference or application not running (pid not found): '+pid);
	}
	else {
		comodojo.debugDeep('Stopping application with pid: '+pid);
		//try{
		//	app.onstop();
		//}
		//catch (e) {
		//	comodojo.debug('Could not process internal app onstop: '+e);
		//}
		switch (app.isComodojoApplication){
			case "WINDOWED":
				app.close();
				comodojo.debug('Stopping windowed application: '+pid);
			break;
			case "MODAL":
				//app.onCancel();
				app.close();
				comodojo.debug('Stopping modal application: '+pid);
			break;
			case "ATTACHED":
				app.close();
				comodojo.debug('Stopping attached application: '+pid);
				//domConstruct.destroy(pid);
			break;
			default:
				comodojo.debug('Could not stop application: invalid application format: '+pid);
			break;
		}
	}
};

App.kill = function(pid,appExec) {
	App.pullRunning(pid);
	comodojo.debug('Application stopped: '+pid);
	if (dom.byId(pid)) {
		comodojo.debug('Application presentation still running, killing it');
		domConstruct.destroy(pid);
	}
	//if (comodojo.stateFired) { dojo.back.addToHistory(new comodojo.state('stop',appExec,pid)); }
};

App.stopAll = function(stopSystemApps, callback) {
	comodojo.debug(!stopSystemApps ? 'Stopping USER applications' : 'Stopping ALL applications');
	
	var count = 0;
	var toStop = [];
	
	var i,o;
	for (i in bus._runningApplications) {
		if (bus._runningApplications[i][3] == 'system' && !stopSystemApps) {
			continue;
		}
		else {
			toStop.push(bus._runningApplications[i][0]);
			count++;
		}
	}
	
	for (o in toStop) {
		App.stop(toStop[o]);	
	}
	comodojo.debug('Stopped '+count+' applications');
	if (lang.isFunction(callback)) { callback(); }
};

App.stopAnyInstance = function(appExec) {
	comodojo.debug('Stopping any istance of: '+appExec);
	
	var instances = App.getPid(appExec);
	var count = 0;
	
	if (!instances) {
		comodojo.debug('No instances of "'+appexec+'" currently running');
		return;
	}
	else if (lang.isArray(instances)) {
		var i;
		for (i in instances) {
			App.stop(instances[i]);
			count++;
		}
	}
	else {
		App.stop(instances);
		count++;
	}
	comodojo.debug('Stopped '+count+' instance(s) of '+appExec);
};

App.loadCss = function(appExec) {
	return comodojo.loadCss(comodojo.applicationsPath + appExec + '/resources/' + appExec + '.css');
};
	
App.getLocalizedMessage = function(appExec, messageCode) {
	return comodojo.getLocalizedMessage(messageCode, bus._registeredApplications[appExec].i18n);
};
	
App.getLocalizedMutableMessage = function(appExec, messageCode, params) {
	return comodojo.getLocalizedMutableMessage(messageCode, params, bus._registeredApplications[appExec].i18n);
};

App.lock = function(pid) {
	var apl = App.byPid(pid);
	if (!apl) {
		return false;
	}
	else {
		apl.containerNode.style.display = "none";
		apl.lockNode.style.display = "block";
		return true;
	}
};

App.release = function(pid) {
	var apl = App.byPid(pid);
	if (!apl) {
		return false;
	}
	else {
		apl.containerNode.style.display = "block";
		apl.lockNode.style.display = "none";
		return true;
	}
};

App.setFocus = function(pid) {
	if (!App.isRunning(App.getExec(pid))) {
		comodojo.debug('Cannot focus on, application not running: '+pid);
		return;
	}

	var a = App.byPid(pid);
	/*if (a.isComodojoApplication == "WINDOWED") {
		if (a._isMinimized) {
			a.show();
		}
		a.bringToTop();
	}
	else {*/
		a.focus();
	/*}*/
};

});