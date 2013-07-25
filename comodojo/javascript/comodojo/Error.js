define(["dojo/_base/lang","dojo/dom-construct","dojo/dom-attr","comodojo/Dialog-base","comodojo/Bus","comodojo/Utils","dojo/domReady!"],
function(lang,domConstruct,domAttr,dialogBase,bus,utils){

// module:
// 	comodojo/Error
	
var Error = {
	// summary:
	// description:
};
lang.setObject("comodojo.Error", Error);

Error.generic = function(Code, Name, Detail) {

	return new dialogBase({
		title: comodojo.getLocalizedMessage('10034'),
		content: "<h3>("+Code+") - "+Name+"</h3><p>"+Detail+"</p>",
		primaryCloseButton: false,
		secondaryCloseButton: true,
		parseOnLoad: false,
		blocker: false
	});

};

Error.genericHang = function(Code, Name, Detail) {

	return new dialogBase({
		title: comodojo.getLocalizedMessage('10034'),
		content: "<h3>("+Code+") - "+Name+"</h3><p>"+Detail+"</p>",
		primaryCloseButton: false,
		secondaryCloseButton: false,
		parseOnLoad: false,
		blocker: true
	});

};

Error.genericLocal = function(Node, Code, Name, Detail) {

	var content = '<div class="box error"><p><strong>('+Code+') - '+Name+'</strong></p><p>'+Detail+'</p></div>';

	if (utils.nodeOrId(Node) != false) {
		utils.nodeOrId(Node).innerHTML = content;
	}
	else if (utils.elementOrId(Node) != false) {
		utils.elementOrId(Node).innerHTML = content;
	}
	else {
		comodojo.debug('Failed to notify error: ('+Code+') - '+Name);
	}

};

Error.modal = function(Code, Detail) {

	return new dialogBase({
		title: comodojo.getLocalizedMessage('10034'),
		content: "<h3>("+Code+") - "+comodojo.getLocalizedError(Code)+"</h3><p>"+Detail+"</p>",
		primaryCloseButton: false,
		secondaryCloseButton: true,
		parseOnLoad: false,
		blocker: true
	});

};

Error.local = function(Node, Code, Detail) {

	var content = '<div class="box error"><p><strong>('+Code+') - '+comodojo.getLocalizedError(Code)+'</strong></p><p>'+Detail+'</p></div>';

	if (utils.nodeOrId(Node) != false) {
		utils.nodeOrId(Node).innerHTML = content;
	}
	else if (utils.elementOrId(Node) != false) {
		utils.elementOrId(Node).innerHTML = content;
	}
	else {
		comodojo.debug('Failed to notify error: ('+Code+') - '+comodojo.getLocalizedError(Code));
	}

};

Error.critical = function(Code, Detail) {
	
	comodojo.Session.logout();

	utils.destroyAll(Detail);

	return new dialogBase({
		title: comodojo.getLocalizedMessage('10034'),
		content: "<h3>("+Code+") - "+comodojo.getLocalizedError(Code)+"</h3><p>"+Detail+"</p>",
		primaryCloseButton: false,
		secondaryCloseButton: false,
		parseOnLoad: false,
		blocker: true
	});

};

return Error;
	
});