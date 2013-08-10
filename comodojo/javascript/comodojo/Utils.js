define(["dojo/_base/lang","dojo/dom","dijit/registry","dojo/cookie","dojo/dom-construct","dojo/_base/window","dojo/dom-class",
	"dojo/_base/array","dojo/query"],
function(lang,dom,registry,cookie,domConstruct,win,domClass,array,query){

// module:
// 	comodojo/Utils

var Utils = {
	// summary:
	// description:
};
lang.setObject("comodojo.Utils", Utils);

Utils.defined = function(what) {
	// summary:
	//		Check if "what" is currently defined
	// what:
	//		Element to check
	return (typeof(what)==="undefined") ? false : true;
};

Utils.inArray = function(what, wherein) {
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
};

Utils.compare = function(what, to) {
	// summary:
	//		Compare "what" to "to" and return bool
	// what:
	//		First element to compare
	// to:
	// 		Second element to compare
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
			if (!this.compare(i1[x], i2[x])) {
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
		else if (i1 === null || i2 === null) {
				return false;
		}
		var x;
		for (x in i1) {
			if (!(x in i2)) {
				return false;
			}
		}
		for (x in i2) {
			if (!this.compare(i1[x], i2[x])) {
				return false;
			}
		}
		return true;
	};
	if (lang.isArray(what) && lang.isArray(to)) {
		return arrayCompare(what, to);
	}
	if (typeof what == "object" && typeof to == "object") {
		return objCompare(what, to);
	}
};

Utils.dateToServer = function(clientDate){
	// summary:
	//		Format a date to send to server
	// clientDate:
	//		Date to format
	return (new Date(clientDate).getTime() + (comodojo.timezone*3600)) / 1000.0;
};

Utils.dateFromServer = function(serverDate) {
	// summary:
	//		Convert date from server
	// serverDate:
	//		Date to convert
	var myDate = new Date ( ( serverDate - (comodojoConfig.serverTimezoneOffset*3600) + (comodojo.timezone*3600) ) * 1000.0 );
		return  myDate.getDate()+"-"+(myDate.getMonth()+1)+"-"+myDate.getFullYear()+","+myDate.getHours()+":"+myDate.getMinutes()+":"+myDate.getSeconds();
	};

Utils.getUserTimezone = function() {
	// summary:
	//		Get the personal timezone, as recorded in cookie using setLocale or similiar apps
	// returns:
	//		The user timezone calculated from cookie or current cliend date
	var ck = cookie("comodojo_timezone");
	if (!Utils.defined(ck)) {
		var clientDate = new Date();
		ck = -clientDate.getTimezoneOffset()/60;
	}
	return ck;
};

Utils.head = function() {
	// summary:
	//		Just an alias for the document head DOM
	return document.getElementsByTagName('head').item(0);
};

Utils.main = function() {
	// summary:
	// 		Just an alias for the document main content
	return dom.byId(comodojoConfig.defaultContainer);
};

Utils.nodeOrId = function(node_or_id) {
	// summary:
	// 		Undersand if object is node or id
	// returns:
	//		node reference or false if not found
	var node;
	if (typeof Node === "object" ? node_or_id instanceof Node : node_or_id && typeof node_or_id === "object" && typeof node_or_id.nodeType === "number" && typeof node_or_id.nodeName==="string") {
		node = node_or_id;
	}
	else {
		node = dom.byId(node_or_id);
	}
	if (!node) {
		comodojo.debugDeep('Invalid node or id: ('+node_or_id+')');
	}
	return node;
}

Utils.isNode = function(node) {
	// summary:
	// 		Check if object in input is a valid js node
	// returns:
	//		true in case node or false otherwise
	if (typeof Node === "object" ? node instanceof Node : node && typeof node === "object" && typeof node.nodeType === "number" && typeof node.nodeName==="string") {
		return true;
	}
	else {
		return false;
	}
};

Utils.elementOrId = function(element_or_id) {
	// summary:
	// 		Undersand if object is element or id
	// returns:
	//		element reference or false if not found
	var elem;
	if (typeof HTMLElement === "object" ? element_or_id instanceof HTMLElement : element_or_id && typeof element_or_id === "object" && element_or_id !== null && element_or_id.nodeType === 1 && typeof element_or_id.nodeName==="string") {
		node = element_or_id;
	}
	else {
		elem = dom.byId(element_or_id);
	}
	if (!elem) {
		comodojo.debugDeep('Invalid element or id: ('+element_or_id+')');
	}
	return elem;
}

Utils.isElement = function(element_or_id) {
	// summary:
	// 		Check if object in input is a valid html element
	// returns:
	//		true in case of element or false otherwise
	if (typeof HTMLElement === "object" ? element_or_id instanceof HTMLElement : element_or_id && typeof element_or_id === "object" && element_or_id !== null && element_or_id.nodeType === 1 && typeof element_or_id.nodeName==="string") {
		return true;
	}
	else {
		return false;
	}
};

Utils.fromHierarchy = function(hierachy, startObj) {
	// summary:
	//	Create a complex object starting from json hierachy
	// description:
	//		This method takes in imput a json hierarchy like:
	//		{
	//	 		domobj: <>,
	//	 		widget: <>,
	//	 		name: <>,
	//	 		cssClass: <>,
	//	 		innerHTML: <>,
	//	 		style: <>,
	//	 		href: <>,
	//	 		params: <>,
	//	 		childrens: [
	//	 			{domobj: <>,
	//				 widget: <>,
	//				 name: <>,
	//				 cssClass: <>,
	//				 innerHTML: <>,
	//				 href: <>,
	//				 params: <>,
	//				 childrens: [{...}],{...}},
	//				{...},
	//				{...}
	//			]
	//		}
	// hierarchy: Object
	//
	// startObj:
	//
	// returns: Object
	//
	var myNode, ObjectConstructor, BuiltObject;
		
	myNode = domConstruct.create(!hierachy.domobj ? "div" : hierachy.domobj, {}, win.body());
	
	if(hierachy.style){ myNode.style.cssText = hierachy.style; }
	
	if(hierachy.cssClass){ domClass.add(myNode, hierachy.cssClass); }
	
	if(hierachy.innerHTML){ myNode.innerHTML = hierachy.innerHTML; }
	
	if (hierachy.domobj) {
		if (startObj) {
			startObj[hierachy.name] = myNode;
			if(hierachy.childrens){
				array.forEach(hierachy.childrens, function(child){
					startObj[hierachy.name].appendChild(Utils.fromHierarchy(child, startObj[hierachy.name]).domNode);
				});
			}
			BuiltObject = startObj[hierachy.name];
			BuiltObject.domNode = BuiltObject;
		}
		else {
			BuiltObject = myNode;
			if(hierachy.childrens){
				array.forEach(hierachy.childrens, function(child){
					BuiltObject.appendChild(Utils.fromHierarchy(child, false).domNode);
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
				array.forEach(hierachy.childrens, function(child){
					if (lang.isFunction(startObj[hierachy.name].addChild)) { startObj[hierachy.name].addChild(Utils.fromHierarchy(child, startObj[hierachy.name])); }
					else { 
						if (startObj[hierachy.name].containerNode) { startObj[hierachy.name].containerNode.appendChild(Utils.fromHierarchy(child, startObj[hierachy.name]).domNode); }
						else if (startObj[hierachy.name].domNode) { startObj[hierachy.name].domNode.appendChild(Utils.fromHierarchy(child, startObj[hierachy.name]).domNode); }
						else { startObj[hierachy.name].appendChild(Utils.fromHierarchy(child, startObj[hierachy.name]).domNode); }
					}
				});
			}
			BuiltObject = startObj[hierachy.name];
		}
		else { 
			BuiltObject = new ObjectConstructor (!hierachy.params ? {} : hierachy.params, myNode);
			if (hierachy.innerHTML) { BuiltObject.set('content',hierachy.innerHTML);}
			if(hierachy.childrens){
				array.forEach(hierachy.childrens, function(child){
					if (lang.isFunction(BuiltObject.addChild)) { BuiltObject.addChild(Utils.fromHierarchy(child, false)); }
					else { 
						if (BuiltObject.containerNode) { BuiltObject.containerNode.appendChild(Utils.fromHierarchy(child, false).domNode); }
						else if (BuiltObject.domNode) { BuiltObject.domNode.appendChild(Utils.fromHierarchy(child, false).domNode); }
						else { BuiltObject.appendChild(Utils.fromHierarchy(child, false).domNode); }
					}
				});
			}
		}
		BuiltObject.startup();
	}
	return BuiltObject;
};

Utils.fileSizeFromServer = function(bytes) {
	// summary:
	//		transform a file size information from server into something human readable
	// bytes: Integer
	//		File size from server
	var _bytes = parseInt(bytes, 10);
    return (_bytes < 1048576 ? (Math.round(_bytes / 1024 * 100000) / 100000 + " bytes") : (_bytes < 1073741824 ? (Math.round(_bytes / 1048576 * 100000) / 100000 + " KB") : (Math.round(_bytes / 1073741824 * 100000) / 100000 + " MB")));
};

Utils.destroyNode = function(node_or_id) {
	// summary:
	//		Destroy a node referenced by object or id
	// node_or_id: String|Object
	//		Id of node or reference
	// returns:
	//		True in case of success, false otherwise
	if (this.nodeOrId(node_or_id) != false) {
		domConstruct.destroy(node_or_id);
		comodojo.debugDeep('Node was destroied');
		return true;
	}
	else {
		comodojo.debugDeep('Node not found');
		return false;
	}
};

Utils.destroyWidget = function(widget_or_id) {
	// summary:
	//		Destroy (recursive) a widget identified by reference or id
	if (lang.isFunction(widget_or_id.destroyRecursive)) {
		//widget_or_id.destroyRendering();
		widget_or_id.destroyRecursive();
		comodojo.debugDeep('Widget was destroied');
		return true;
	}
	else if (registry.byId(widget_or_id) != null) {
		var w = registry.byId(widget_or_id);
		//w.destroyRendering();
		w.destroyRecursive();
		comodojo.debugDeep('Widget '+widget_or_id+' was destroied');
		return true;
	}
	else {
		comodojo.debugDeep('Widget '+widget_or_id+' cannot be found');
		return false;
	}
};

Utils.destroy = function(element_or_id) {
	// summary:
	//		Destroy a widget or a node 
	if (this.destroyWidget(element_or_id)) {
		return true;
	}
	else if (this.destroyNode(element_or_id)) {
		return true;
	}
	else {
		comodojo.debugDeep(element_or_id+' was not a widget or node');
		return false;
	}
};

Utils.destroyAll = function(message) {
	// summary:
	//		Destroy every widget or application and replace body with error message
	comodojo.debug('Site-wide destroy in progress...');
	registry.forEach(function(widget){
		comodojo.debugDeep('Killing widget: '+widget.id);
		Utils.destroyWidget(widget);
	});
	win.body().parentNode.replaceChild(document.createElement("body",{
		innerHTML: !message ? '' : "<p>"+message+"</p>"
	}),win.body());
	query('script').forEach(function(s){
		domConstruct.destroy(s);
	});
	comodojo.debug('Destroy complete');
};	

return Utils;

});