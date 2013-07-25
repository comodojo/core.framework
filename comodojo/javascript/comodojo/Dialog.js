define(["dojo/_base/lang","dojo/dom-construct","dojo/dom-attr","comodojo/Dialog-base","comodojo/Bus","dojo/domReady!"],
function(lang,domConstruct,domAttr,dialogBase,bus){

// module:
// 	comodojo/Dialog
	
var Dialog = {
	// summary:
	// description:
};
lang.setObject("comodojo.Dialog", Dialog);

Dialog.custom = function(params) {
	return new dialogBase(params);
};

Dialog.application = function(Id, Title, Content, Forced) {

	return new dialogBase({
		id: Id,
		title: Title,
		content: Content,
		forced: Forced,
		draggable: false,
		_isApplication: true,
		persistent: true
	});

};

Dialog.modal = function(Id, Title, Content, Forced, Persistent) {

	return new dialogBase({
		id: Id,
		title: Title,
		content: Content,
		forced: Forced,
		persistent: Persistent
	});

};

Dialog.remote = function(Id, Title, Href, Forced, Persistent) {

	return new dialogBase({
		id: Id,
		title: Title,
		href: Href,
		forced: Forced,
		persistent: Persistent
	});

};

Dialog.info = function(Content, Title) {
	
	return new dialogBase({
		title: !Title ? "" : Title,
		content: Content,
		primaryCloseButton: false,
		secondaryCloseButton: true,
		parseOnLoad: false
	});

};

Dialog.action = function() {

};

Dialog.warning = function() {

};

Dialog.input = function() {

};

Dialog.timed = function() {

	return new dialogBase({
		title: Title,
		content: Content,
		timer: Time,
		primaryCloseButton: false,
		secondaryCloseButton: true
	});

};

Dialog.blocker = function() {

};

return Dialog;
	
});
	

	
	/**
	 * Create and launch a modal, blocker dialog, which has some content and lock the enviroment
	 * 
	 * @param	string	Id		The id that your dialog should have; if false, it will force dialog to have a standard pid-derivated id.
	 * @param	string	Title	Title of your dialog displayed on top bar.
	 * @param	string	Content	The content of your dialog; it will be parsed on startup, so it can include some js.
	 * @param	bool	Forced	Determine if dialog should destroy *EVERY* other object with same id.
	 * @return	object	Requested dialog
	 *
	blocker: function (Id, Title, Content, Forced) {
		
		var myDialog = new comodojo.dialog._newDialog({
			title: Title,
			content: Content,
			forced: Forced,
			blocker: true,
			draggable: false
		});
		// ****** THIS SHOULD BE REPLACED WITH SOMETHING MORE ELEGANT ******
		if (dojo.isString(Id)) {
			myDialog.id = Id;
		}
		// *****************************************************************
		return myDialog.launch();
		
	},
	
	/**
	 * Create and launch a modal, timed, blocker dialog, which has some content, will be destroyed in (Time) ms and lock environment
	 * 
	 * @param	string	Title	Title of your dialog displayed on top bar.
	 * @param	string	Content	The content of your dialog; it will be parsed on startup, so it can include some js.
	 * @param	string	Time	Time passed at timer; it *MUST* be a number (ms).
	 * @return	object	Requested dialog
	 *
	timedBlocker: function (Title, Content, Time) {
		
		var myDialog = new comodojo.dialog._newDialog({
			title: Title,
			content: Content,
			timer: Time,
			blocker: true,
			draggable: false
		});
		return myDialog.launch();
		
	},
	
	/**
	 * Create and launch a modal warning dialog; it will propose two different alternatives (like confirm) and will lock environment
	 * 
	 * @param	string		Title	Title of your dialog displayed on top bar.
	 * @param	string		Content	The content of your dialog; it will be parsed on startup, so it can include some js.
	 * @param	function	ActionP	
	 * @param	function	ActionN
	 * @return	object	Requested dialog
	 *
	warning: function(Title, Content, ActionOk, ActionCancel) {
		
		var myDialog = new comodojo.dialog._newDialog({
			id: 'warningDialog',
			title: Title,
			content: Content,
			forced: true,
			blocker: false,
			hided: false,
			draggable: false,
			actionOk: ActionOk,
			closeOnOk: true,
			actionCancel: ActionCancel,
			closeOnCancel: true
		});
		
		return myDialog.launch();
			
	},
	
	/**
	 * Create and launch an action dialog; it will propose two different alternatives (like confirm) and will lock environment
	 * 
	 * @param	string		Title	Title of your dialog displayed on top bar.
	 * @param	string		Content	The content of your dialog; it will be parsed on startup, so it can include some js.
	 * @param	function	ActionP	
	 * @param	function	ActionN
	 * @return	object	Requested dialog
	 *
	action: function(Title, Content, ActionOk, ActionCancel) {
		
		var myDialog = new comodojo.dialog._newDialog({
			id: 'actionDialog',
			title: Title,
			content: Content,
			forced: true,
			blocker: false,
			hided: false,
			draggable: false,
			actionOk: ActionOk,
			closeOnOk: true,
			actionCancel: ActionCancel,
			closeOnCancel: true
		});
		
		return myDialog.launch();
			
	},
	
	input: function(Title, Message, Callback) {
		
		//var input = $d.create('input',{id:'actionDialog_input'});
		
		var myDialog = new comodojo.dialog._newDialog({
			id: 'actionDialog',
			title: Title,
			content: '<p class="box info" style="width: 300px;">'+Message+'</p><p style="text-align:center;"><input style="padding: 4px; width: 300px;" id="actionDialog_input" /></p>',
			forced: true,
			blocker: false,
			hided: false,
			draggable: false,
			actionOk: function() {
				if ($d.isFunction(Callback)) {
					Callback($d.byId('actionDialog_input').value);
				}
			},
			closeOnOk: true,
			actionCancel: false,
			closeOnCancel: true,
			focusKilled: false
		});
		
		//var _myDialog = myDialog.launch();
		
		//_myDialog.containerNode.appendChild(input);
		
		//return _myDialog;
		
		return myDialog.launch();
	}*/