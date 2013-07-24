define(["dojo/_base/lang","dojo/_base/window","dojo/dom-construct","dojo/dom-attr","comodojo/Window-base","comodojo/Bus","dojo/domReady!"],
function(lang,win,domConstruct,domAttr,WindowBase,bus){

// module:
// 	comodojo/Window
	
var Win = {
	// summary:
	// description:
};
lang.setObject("comodojo.Window", Win);

Win.newWindow = function(params) {

	var p = {};

	p.id = comodojo.getPid();
		
	p.title = "";
	
	p.width = false;
	p.height = false;
	
	p.resizable = false;
	p.maxable = false;
	p.closable = true;
	p.minimizable = true;
	
	p.parseOnLoad = true;
	p.preventCache = false;
	
	p.icon = null;
	
	p.href = false;
	p.content = false;

	lang.mixin(p,params);

	comodojo.debugDeep("Launching new window, id will be: " + this.id);
		
	var myNewWin = new WindowBase({
		title: p.title,
		id : p.id,
		icon: p.icon,
		minimizable: p.minimizable,
		maxable: p.maxable,
		closable: p.closable,
		resizable: p.resizable,
		preventCache: p.preventCache,
		parseOnLoad: p.parseOnLoad,
		href: p.href != false ? p.href : false,
		content: p.content != false ? p.content : false
	},domConstruct.create('div',null,win.body()));
	
	return myNewWin;

};

Win.placeOnAndResize = function(win,width,height) {
	win.domNode.style.top = "25px";
	win.domNode.style.left = "5px";
	//win.resize({ w:width, h:height });
	//win.domNode.style.width = width;
	//win.domNode.style.height = height;
	win.startup();
	win.resize({ w:width, h:height });
	return win;
};

Win.newPopup = function(params) {
		
	p.href = "";
	p.name = "comodojo_popup_"+comodojo.getPid();
	
	p.top = 0;
	p.left = 0;
	p.autoCenter = 0;
	
	p.width = 300;
	p.height = 300;
	p.resizable = 1;
	
	p.status = 0;
	p.menubar = 0;
	p.toolbar = 0;
	p.scrollbars = 0;
	p.location = 0;
	
	p.dependent = 0;
	p.fullscreen = 0;
	
	lang.mixin(p, params);
		
	if (p.autoCenter) {
		p.left = Math.floor((screen.width-w)/2);
		p.top  = Math.floor((screen.height-h)/2);
	}
	
	if (p.fullscreen) {
		p.width = Math.floor(screen.width);
		p.height = Math.floor(screen.height);
	}
	
	var features = 'left='+p.left+',top='+p.top+',width='+p.width+',height='+p.height+',resizable='+p.resizable+',toolbar='+p.toolbar+',status='+p.status+',scrollbars='+p.scrollbars+',menubar='+p.menubar+',location='+p.location+',dependent='+p.dependent;
	
	return window.open(p.href,p.name,features);
		
};

Win.application = function(pid,title,width,height,resizable,maxable,icon) {
	
	return Win.placeOnAndResize(Win.newWindow({
		id:pid,
		title:title,
		resizable:resizable,
		maxable:maxable,
		icon:icon
	}),width,height);

};
	
Win.util = function(title, width, height, resizable, maxable) {
	
	return Win.placeOnAndResize(Win.newWindow({
		id:comodojo.getPid(),
		title:title,
		resizable:resizable,
		maxable:maxable,
		content:"",
		minimizable:false
	}),width,height);

};
	
Win.info = function(content,width,height) {
	
	return Win.placeOnAndResize(Win.newWindow({
		id:comodojo.getPid(),
		title:"!",
		resizable:false,
		maxable:false,
		content:content,
		minimizable:false
	}),width,height);
	
};

Win.external = function(href,title,width,height) {

	return Win.placeOnAndResize(Win.newWindow({
		id:comodojo.getPid(),
		title:title,
		resizable:true,
		maxable:false,
		href:href,
		minimizable:false
	}),width,height);
		
};
	
Win.simplePopup = function(href,width,height) {
		
	return new Win.newPopup({
		href: href,
		width: width,
		height: height
	});
	
};
	
Win.centeredPopup = function(href,width,height) {
	
	return new Win.newPopup({
		href: href,
		width: width,
		height: height,
		autoCenter: 1
	});
	
};
	
Win.fullscreenPopup = function(href) {
	
	return new Win.newPopup({
		href: href,
		fullscreen: 1
	});
	
};

return Win;

});