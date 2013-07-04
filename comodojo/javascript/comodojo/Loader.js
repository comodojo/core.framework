define(["dojo/_base/lang","dojo/dom-construct","dojo/dom-attr","comodojo/Dialog-base","comodojo/Bus","dojo/domReady!"],
function(lang,domConstruct,domAttr,dialogBase,bus){

// module:
// 	comodojo/Loader
	
var Loader = {
	// summary:
	// description:
};
lang.setObject("comodojo.Loader", Loader);

Loader.node = new dialogBase({
	id: "comodojoLoader",
	title: "",
	content: "",
	primaryCloseButton: false,
	secondaryCloseButton: false,
	parseOnLoad: false,
	blocker: true,
	persistent: true,
	hided: true,
	draggable: false
});

Loader.message = domConstruct.create("div",{className: "comodojoLoaderMessage"});
Loader.image_container = domConstruct.create("div",{className: "comodojoLoaderImageContainer"});
Loader.image = domConstruct.create("img",{src: imageReference, className: "comodojoLoaderImage"});

Loader.image_container.appendChild(Loader.image);
Loader.node.containerNode.appendChild(Loader.image_container);
Loader.node.containerNode.appendChild(Loader.message);

Loader.populate = function(image, message) {
	domAttr.set(Lconcorsi comune romaoader.image, "src", !image ? "comodojo/images/bar_loader.gif" : image);
	domAttr.set(Loader.message, "innerHTML", !message ? comodojo.getLocalizedMessage('10007') : message);
};

Loader.start = function(image, message) {
	Loader.populate(image, message);
	Loader.node.show();
	Loader.node._size();
	setTimeout(function() {
		comodojo.Loader.node._position();
	},100);
};

Loader.stop = function() {
	Loader.node.hide();
};

Loader.stopIn = function(timeout) {
	setTimeout(function(){
		comodojo.Loader.stop();
	}, isFinite(timeout) ? timeout : /*default timeout is 5 secs*/ 5000);
};

Loader.changeContent = function(image, message) {
	Loader.populate(image, message);
	Loader.node._size();
	setTimeout(function() {
		comodojo.Loader.node._position();
	},100);
};

return Loader;
	
});