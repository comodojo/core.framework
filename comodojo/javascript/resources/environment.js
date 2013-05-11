/** 
 * environment.js
 * 
 * Give to CoMoDojo some environmental control such as dialogs, errors, ...
 *
 * @package		Comodojo ClientSide Core Packages
 * @author		comodojo.org
 * @copyright	2011 comodojo.org (info@comodojo.org)
 * 
 */

comodojo.loadCss('comodojo/CSS/environment.css');

/**
 * Extend comodojo with .dialog functions.
 * Dialogs are custom, site-wide dijit.Dialog.
 *
 * @class
 */
comodojo.dialog = {
	
	/**
	 * Create and launch a dialog (main function).
	 * 
	 * ***TBW***
	 *
	 * @private	Use comodojo.dialog.customDialog alias instead.
	 */
	_newDialog: function(params) {
		
		this.id = "comodojo_dialog_"+comodojo.getPid(); 
		
		this.title = false;
		
		this.content = false;
		this.href = false;
		this.parseOnLoad = true;
		this.templateString = false;
		this.draggable = true;
		
		this.timer = false;
		this.blocker = false;
		this._isApplication = false;
		this.forced = false;
		this.persistent = false;
		this.focusKilled = true;
		this.hided = false;
		
		this.maxWidth = "600px";
		this.maxHeight = "600px";
		//this.forceWidth = false;
		//this.forceHeight = false;
		this.hideOverflow = false;
		
		/* BUTTONS AND ACTIONS */
		this.primaryCloseButton = true;
		this.secondaryCloseButton = false;
		this.actionOk = null;
		this.actionCancel = null;
		this.closeOnOk = false;
		this.closeOnCancel = true;
		
		// mixin requested configuration 
		dojo.mixin(this, params);
		
		var that = this;
		
		this.evalParams = function() {
			if ((this.timer - 0) === this.timer && this.timer.toString.length > 0) {
				comodojo.debugDeep('A timer was requested, it will override persistence, hiding and force dialog creation.');
				this.persistent = false;
				//this.hided = false;
				this.forced = true;
			}
			if (this.blocker) {
				comodojo.debugDeep('Blocker was requested, it will override focusKilling, primaryCloseButton, secondaryCloseButton, actions and hiding.');
				this.focusKilled = false;
				//this.hided = false;
				this.primaryCloseButton = false;
				this.secondaryCloseButton = false;
				this.actionOk = null;
				this.actionCancel = null;
			}
			if (this._isApplication) {
				comodojo.debugDeep('Application dialog was requested, it will override focusKilling, secondaryCloseButton, actions and hiding.');
				this.focusKilled = false;
				this.hided = false;
				this.secondaryCloseButton = false;
				this.actionOk = null;
				this.actionCancel = null;
			}
			if (!this.primaryCloseButton) {
				comodojo.debugDeep('PrimaryCloseButton will not be displayed.');
				this.templateString = "<div class=\"dijitDialog\" role=\"dialog\" aria-labelledby=\"${id}_title\">\n\t<div data-dojo-attach-point=\"titleBar\" class=\"dijitDialogTitleBar\">\n\t\t<span data-dojo-attach-point=\"titleNode\" class=\"dijitDialogTitle\" id=\"${id}_title\"\n\t\t\t\trole=\"header\" level=\"1\"></span>\n\t\t<span data-dojo-attach-point=\"closeButtonNode\" title=\"${buttonCancel}\" role=\"button\" tabIndex=\"-1\">\n\t\t\t<span data-dojo-attach-point=\"closeText\" class=\"closeText\" title=\"${buttonCancel}\">&nbsp;</span>\n\t\t</span>\n\t</div>\n\t<div data-dojo-attach-point=\"containerNode\" class=\"dijitDialogPaneContent\"></div>\n</div>\n";
			}
			
			if (comodojo.isSomething(this.id).success) {
				if (!this.forced) {
					comodojo.debug('Requested dialog could not be created cause id already in use (id was: ' + this.id + ').');
					return false;
				}
				else {
					comodojo.destroySomething(this.id);
				}
			}
			
			return true;
			
		};
		
		this.buildDialog = function() {
			
			var params = {
				id: this.id,
				title: this.title,
				parseOnLoad: this.parseOnLoad,
				draggable: this.draggable
			};
			
			//build parameters and check for consistence
			if (dojo.isString(this.content)) {
				params.content = this.content;
			}
			else if (dojo.isString(this.href)) {
				params.href = this.href;
			}
			else {
				params.content = "";
				comodojo.debugDeep('No content or href passed, dialog will be empty.');
			}
			
			if (dojo.isString(this.templateString)) {
				params.templateString = this.templateString;
			}
			
			this._dialog = new dijit.Dialog(params);
				this._dialog.containerNode.style.maxWidth = this.maxWidth;
				this._dialog.containerNode.style.maxHeight = this.maxHeight;
				this._dialog.containerNode.style.overflow = !this.hideOverflow ? "auto" : "hidden";
		
			if (!this.persistent) {
				dojo.connect(this._dialog, "hide", this._dialog, function(){comodojo.destroySomething(this.id);});
				comodojo.debugDeep('Dialog with id: '+this.id+' will not persist after hiding.');
			}
			
			if (this.focusKilled) {
				dojo.connect(this._dialog, "onBlur", this._dialog, function(){comodojo.destroySomething(this.id);});
			}
			
			if (this.blocker || this._isApplication) {
				this._dialog._onKey = function(){
					return false;
				};
			}
			
			if ((this.timer - 0) === this.timer && this.timer.toString.length > 0) {
				setTimeout("if (comodojo.isSomething('" + this.id + "').type == 'WIDGET') {dijit.byId('" + this.id + "').hide();}", this.timer);
				comodojo.debugDeep('Dialog with id: ' + this.id + ' will be destroyed in ' + this.timer + 'ms.');
			}
			
			if (this.secondaryCloseButton || this.actionOk !== null || this.actionCancel !== null) {
				this.actionBar = dojo.create("div", {
			        "class": "dijitDialogPaneActionBar"
			    }, this._dialog.containerNode);
			}
			
			//if (!!(this.actionOk && this.actionOk.constructor && this.actionOk.call && this.actionOk.apply)) {
			if ($d.isFunction(this.actionOk)) {
				var actionOkButton = new dijit.form.Button({
					label: comodojo.getLocalizedMessage('10003'),
					onClick: this.actionOk
				}).placeAt(this.actionBar);
				if (this.closeOnOk) { dojo.connect(actionOkButton, 'onClick', function(){ that._dialog.hide(); }); }
				var actionCancelButton = new dijit.form.Button({
					label: comodojo.getLocalizedMessage('10004'),
					onClick: $d.isFunction(this.actionCancel) ? this.actionCancel : function() {}
				}).placeAt(this.actionBar);
				if (this.closeOnCancel) { dojo.connect(actionCancelButton, 'onClick', function(){ that._dialog.hide(); }); }
			}
			/*
			if (!!(this.actionCancel && this.actionCancel.constructor && this.actionCancel.call && this.actionCancel.apply)) {
			//if ($d.isFunction(this.actionCancel)) {
				var actionCancelButton = new dijit.form.Button({
					label: comodojo.getLocalizedMessage('10004'),
					onClick: this.actionOk
				}).placeAt(this.actionBar);
				if (this.closeOnCancel) { dojo.connect(actionCancelButton, 'onClick', function(){ myDialog.hide(); }); }
			}			
			*/
			if (this.secondaryCloseButton) {
				new dijit.form.Button({
					label: comodojo.getLocalizedMessage('10011'),
					onClick: function() { that._dialog.hide(); }
				}).placeAt(this.actionBar);
			}
			
			if (!this.hided) {
				this._dialog.startup();
				this._dialog.show();
			}
			
			return this._dialog;
			
		};
		
		this.launch = function() {
			
			if (!this.evalParams()) {
				return false;
			}
			
			comodojo.debugDeep("Launching new dialog as requested, id will be: " + this.id);
			
			return this.buildDialog();
			
		};
	},
	
	/**
	 * Public alias for comodojo.dialog._newDialog.
	 * 
	 * ***TBW***
	 *
	 */
	customDialog: function(params) {
		return comodojo.dialog._newDialog(params);
	},
	
	/**
	 * Create and launch a modal dialog, which has some content 
	 * 
	 * @param	string	Id		The id that your dialog should have; if false, it will force dialog to have a standard pid-derivated id.
	 * @param	string	Title	Title of your dialog displayed on top bar.
	 * @param	string	Content	The content of your dialog; it will be parsed on startup, so it can include some js.
	 * @param	bool	Forced	Determine if dialog should destroy *EVERY* other object with same id.
	 * @return	object	Requested dialog
	 */
	modal: function(Id, Title, Content, Forced) {
		
		var myDialog = new comodojo.dialog._newDialog({
			title: Title,
			content: Content,
			forced: Forced
		});
		// ****** THIS SHOULD BE REPLACED WITH SOMETHING MORE ELEGANT ******
		if (dojo.isString(Id)) {
			myDialog.id = Id;
		}
		// *****************************************************************
		return myDialog.launch();
		
	},
	
	/**
	 * Private, modal dialog used *ONLY* by applicationsManager
	 * 
	 * @private
	 * 
	 * @param	string	Id		The id that your dialog should have; if false, it will force dialog to have a standard pid-derivated id.
	 * @param	string	Title	Title of your dialog displayed on top bar.
	 * @param	string	Content	The content of your dialog; it will be parsed on startup, so it can include some js.
	 * @param	bool	Forced	Determine if dialog should destroy *EVERY* other object with same id.
	 * @return	object	Requested dialog
	 */
	_application: function(Id, Title, Content, Forced) {
		
		var myDialog = new comodojo.dialog._newDialog({
			title: Title,
			content: Content,
			forced: Forced,
			draggable: false,
			_isApplication: true
		});
		// ****** THIS SHOULD BE REPLACED WITH SOMETHING MORE ELEGANT ******
		if (dojo.isString(Id)) {
			myDialog.id = Id;
		}
		// *****************************************************************
		return myDialog.launch();
		
	},
	
	/**
	 * Create and launch a modal, persistent dialog, which has some content and will not be destroied on hiding 
	 * 
	 * @param	string	Id		The id that your dialog should have; if false, it will force dialog to have a standard pid-derivated id.
	 * @param	string	Title	Title of your dialog displayed on top bar.
	 * @param	string	Content	The content of your dialog; it will be parsed on startup, so it can include some js.
	 * @param	bool	Forced	Determine if dialog should destroy *EVERY* other object with same id.
	 * @return	object	Requested dialog
	 */
	modalPersistent: function(Id, Title, Content, Forced) {
		
		var myDialog = new comodojo.dialog._newDialog({
			title: Title,
			content: Content,
			forced: Forced,
			persistent: true
		});
		// ****** THIS SHOULD BE REPLACED WITH SOMETHING MORE ELEGANT ******
		if (dojo.isString(Id)) {
			myDialog.id = Id;
		}
		// *****************************************************************
		return myDialog.launch();
	},
	
	/**
	 * Create and launch a remote dialog, which has a reference (href) to external content.
	 * 
	 * @param	string	Id		The id that your dialog should have; if false, it will force dialog to have a standard pid-derivated id.
	 * @param	string	Title	Title of your dialog displayed on top bar.
	 * @param	string	Href	The href reference thah dialog will load in; it will be parsed on startup, so external page can include some js.
	 * @param	bool	Forced	Determine if dialog should destroy *EVERY* other object with same id.
	 * @return	object	Requested dialog
	 */
	remote: function(Id, Title, Href, Forced) {
		
		var myDialog = new comodojo.dialog._newDialog({
			title: Title,
			href: Href,
			forced: Forced
		});
		// ****** THIS SHOULD BE REPLACED WITH SOMETHING MORE ELEGANT ******
		if (dojo.isString(Id)) {
			myDialog.id = Id;
		}
		// *****************************************************************
		return myDialog.launch();
		
	},
	
	/**
	 * Create and launch a remote, persistent dialog, which has a reference (href) to external content and will not be destroied on hiding 
	 * 
	 * @param	string	Id		The id that your dialog should have; if false, it will force dialog to have a standard pid-derivated id.
	 * @param	string	Title	Title of your dialog displayed on top bar.
	 * @param	string	Href	The href reference thah dialog will load in; it will be parsed on startup, so external page can include some js.
	 * @param	bool	Forced	Determine if dialog should destroy *EVERY* other object with same id.
	 * @return	object	Requested dialog
	 */
	remotePersistent: function(Id, Title, Href, Forced) {
		
		var myDialog = new comodojo.dialog._newDialog({
			title: Title,
			href: Href,
			forced: Forced,
			persistent: true
		});
		// ****** THIS SHOULD BE REPLACED WITH SOMETHING MORE ELEGANT ******
		if (dojo.isString(Id)) {
			myDialog.id = Id;
		}
		// *****************************************************************
		return myDialog.launch();
		
	},
	
	/**
	 * Create and launch a modal, timed dialog, which has some content and will be destroyed in (Time) ms
	 * 
	 * @param	string	Title	Title of your dialog displayed on top bar.
	 * @param	string	Content	The content of your dialog; it will be parsed on startup, so it can include some js.
	 * @param	string	Time	Time passed at timer; it *MUST* be a number (ms).
	 * @return	object	Requested dialog
	 */
	timed: function (Title, Content, Time) {
		
		var myDialog = new comodojo.dialog._newDialog({
			title: Title,
			content: Content,
			timer: Time,
			primaryCloseButton: false,
			secondaryCloseButton: true
		});
		return myDialog.launch();
		
	},
	
	/**
	 * Create and launch a modal, blocker dialog, which has some content and lock the enviroment
	 * 
	 * @param	string	Id		The id that your dialog should have; if false, it will force dialog to have a standard pid-derivated id.
	 * @param	string	Title	Title of your dialog displayed on top bar.
	 * @param	string	Content	The content of your dialog; it will be parsed on startup, so it can include some js.
	 * @param	bool	Forced	Determine if dialog should destroy *EVERY* other object with same id.
	 * @return	object	Requested dialog
	 */
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
	 */
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
	 */
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
	 */
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
	},
	
	/**
	 * Create and launch a modal info dialog; it's the simplest dialog, not persistent, not blocker, ...
	 * 
	 * @param	string		Content	The content of your dialog; it will *NOT* be parsed on startup, so it *CAN'T* include js.
	 * @return	object	Requested dialog
	 */
	info: function(Content) {
		
		var myDialog = new comodojo.dialog._newDialog({
			title: "",
			content: Content,
			primaryCloseButton: false,
			secondaryCloseButton: true,
			parseOnLoad: false
		});
		return myDialog.launch();
		
	},
	
	/**
	 * Fill innerHTML of passed object with a local message (using comodojo.getLocalizedMessage()).
	 * 
	 * @param	messageCode	The message code
	 * @param	id			The reference node id (node could be both DOM or WIDGET)
	 * @return	object	Requested dialog
	 */
	local: function(messageCode, id) {
		
		var node = comodojo.isSomething(id);
		var nodeReference;
		if (!node.success) {
			comodojo.debug('Hey, node id you served is not existent! (id: '+id+')');
		}
		else if (node.type == "WIDGET"){
			nodeReference = (!node.resource.containerNode) ? node.resource.domNode : node.resource.containerNode;
		}
		else {
			nodeReference = node.resource;
		}
		nodeReference.innerHTML = comodojo.getLocalizedMessage(messageCode);
		return nodeReference;
		
	},
	
	/**
	 * Place a dialog on top of current portal main div with a "close" button
	 * 
	 * @param	string|object	messageCode	The message code or object (in case of mutable message) containing "messageCode" and "params" field
	 * @param	string			id			The reference node id (node could be both DOM or WIDGET)
	 * @return	object						Requested dialog
	 */
	siteMessage: function(messageCode, cssClass) {
		comodojo.destroySomething('comodojo_siteMessage');
		var message;
		if ($d.isObject(messageCode)) {
			message = $c.getLocalizedMutableMessage(messageCode.messageCode, messageCode.params);
		}
		else {
			message = $c.getLocalizedMessage(messageCode);
		}
		var node = $d.create('div',{id: 'comodojo_siteMessage',className: !cssClass ? "note" : cssClass, innerHTML: message},$c.main(),'first');
		node.appendChild(new dijit.form.Button({
				label: comodojo.getLocalizedMessage('10011'),
				onClick: function() {
					comodojo.destroySomething('comodojo_siteMessage');
				},
				style: "display: block; padding: 5px; margin-top: 10px;"
			}).domNode
		);
		return node;
		
	}
	
};

/**
 * Extend comodojo with .error functions.
 * Errors are both custom, site-wide dijit.Dialog (someone kills environment) and page body replacer.
 * 
 * @class
 */
comodojo.error = {
	
	/**
	 * Create and launch an error dialog (main function).
	 * 
	 * ***TBW***
	 *
	 * @private	Use comodojo.error.[errorType] instead.
	 */
	_createErrorDialog: function(errorTitle, errorContent) {
		
		var myDialog = new comodojo.dialog._newDialog({
			id: 'errorDialog',
			title: errorTitle,
			content: errorContent,
			primaryCloseButton: false,
			secondaryCloseButton: true,
			parseOnLoad: false
		});
		return myDialog.launch();
		
	},
	
	/**
	 * Create and launch a blocker error dialog (main function).
	 * 
	 * ***TBW***
	 *
	 * @private	Use comodojo.error.[errorType] instead.
	 */
	_createBlockerErrorDialog: function(errorTitle, errorContent) {
		
		var myDialog = new comodojo.dialog._newDialog({
			id: 'errorDialog',
			title: errorTitle,
			content: errorContent,
			primaryCloseButton: false,
			secondaryCloseButton: false,
			parseOnLoad: false,
			blocker: true
		});
		return myDialog.launch();
		
	},
	
	/**
	 * Replace page body in case of critical/fatal error.
	 * 
	 * ***TBW***
	 *
	 * @private	Use comodojo.error.[errorType] instead.
	 */
	_replaceBody: function() {
		
		dojo.body().appendChild(dojo.create("div",{
			innerHTML: comodojo.getLocalizedMessage("10033"),
			style: "margin: 0 auto; text-align: center; font-size: large; color: red; padding: 10px;"
		}));
		
	},
	
	/**
	 * Force user logout in case of critical/fatal error.
	 * 
	 * ***TBW***
	 *
	 * @private	Use comodojo.error.[errorType] instead.
	 */
	_forceUserLogout: function() {
		
		dojo.xhrPost({
			url: 'comodojo/global/kernel.php?callTo=authentication&language=JSON&selector=logout&contentIsEncoded=false',
			handleAs: 'json',
			sync: true,
			preventCache: true,
			content: {}
		});
		
	},
	
	/**
	 * Create and launch a custom error; *BOTH* errorTitle and errorContent should be defined by user; dialog *WILL NOT* lock environment.
	 * 
	 * @param	string	customErrorTitle	Title of your error displayed on top bar.
	 * @param	string	customErrorContent	The content of your error; it will be parsed on startup, so it can include some js.
	 * @return	object						Requested error dialog
	 */
	custom: function(customErrorTitle, customErrorContent) {
		
		return comodojo.error._createErrorDialog(customErrorTitle, customErrorContent);
	
	},
	
	/**
	 * Create and launch a custom error; *BOTH* errorTitle and errorContent should be defined by user; dialog *WILL* lock environment.
	 * 
	 * @param	string	customErrorTitle	Title of your error displayed on top bar.
	 * @param	string	customErrorContent	The content of your error; it will be parsed on startup, so it can include some js.
	 * @return	object						Requested error dialog
	 */
	customBlocker: function(customErrorTitle, customErrorContent) {
		
		return comodojo.error._createBlockerErrorDialog(customErrorTitle, customErrorContent);
	
	},
	
	background: function() {},
	
	/**
	 * Create and append a local error (i.e. an error confined in a determined place in you application)
	 * 
	 * @param	string	errorCode		Error code, (as in comodojo.getLocalizedError())
	 * @param	string	detachedId		The detached object id in wich error will be displayed (it could be boto DOM or WIDGET object)
	 * @return	object					DOM node that contain new error
	 */
	local: function(errorCode, errorDetails, id) {
		
		var node = comodojo.isSomething(id);
		var nodeReference;
		if (!node.success) {
			comodojo.debug('Hey, node id you served is not existent! (id: '+id+')');
		}
		else if (node.type == "WIDGET"){
			nodeReference = (!node.resource.containerNode) ? node.resource.domNode : node.resource.containerNode;
		}
		else {
			nodeReference = node.resource;
		}
		nodeReference.innerHTML = '<div class="box error"><p><strong>('+errorCode+') '+comodojo.getLocalizedError(errorCode)+'</strong></p><p>'+errorDetails+'</p></div>';
		return nodeReference;
		
	},
	
	/**
	 * Create and launch a global error; this error should be considered as default error.
	 * 
	 * @param	string	errorCode		Error code, (as in comodojo.getLocalizedError())
	 * @param	string	errorDetails	The additional details of your error (if any).
	 * @return	object					Requested error dialog
	 */
	global: function(errorCode, errorDetails) {
		
		return comodojo.error._createErrorDialog(comodojo.getLocalizedError('99999'), '<h2>('+errorCode+') '+comodojo.getLocalizedError(errorCode)+'</h2><p>'+errorDetails+'</p>');
		
	},
	
	/**
	 * Create and launch a *CRITICAL* error; this error *WILL LOCK* environment and *WILL FORCE* user logout (if any logged in).
	 * 
	 * @param	string	errorDetails	The additional details of your critical error (if any).
	 * @return	object					Requested error dialog
	 */
	critical: function(errorDetails) {
		
		this._forceUserLogout();
		
		return comodojo.error._createBlockerErrorDialog(comodojo.getLocalizedError('99999'), '<h2>(99998) '+comodojo.getLocalizedError('99998')+'</h2><p>'+errorDetails+'</p>');
		
	},
	
	/**
	 * Create and launch a *DESTROYER* error; as critical this error *WILL LOCK* environment, *WILL FORCE* user logout (if any logged in)
	 * and also will *UNLOAD* all environment (page body & scripts).
	 * 
	 * @param	string	errorDetails	The additional details of your critical error (if any).
	 * @return	object					Requested error dialog
	 */
	destroyer: function(errorDetails) {
		
		comodojo.destroyAll();
		
		comodojo.dialog._forceUserLogout();
		
		comodojo.dialog._replaceBody();
		
		return comodojo.error._createBlockerErrorDialog(comodojo.getLocalizedError('99999'), '<h2>(99998) '+comodojo.getLocalizedError('99998')+'</h2><p>'+errorDetails+'</p>');
		
	},
	
	/**
	 * Create and launch a *DESTROYER* error; as fatal this error suppose a php backend crash, so *WILL LOCK* environment
	 * and also will *UNLOAD* all environment (page body & scripts).
	 * 
	 * @param	string	errorDetails	The additional details of your critical error (if any).
	 * @return	object					Requested error dialog
	 */
	fatal: function(errorTitle, errorDetails) {
		
		comodojo.destroyAll();
		
		comodojo.dialog._replaceBody();
		
		return comodojo.error._createBlockerErrorDialog(errorTitle, '<h2>ERROR</h2><p>'+errorDetails+'</p>');
	}

};
	
/**
 * Extend comodojo with .loader functions.
 * Loader is a site-wide blocker dialog useful to mark loading states.
 * 
 * @class
 */
comodojo.loader = {
	
	_loader: false,
	_loaderMessage: false,
	_loaderImage: false,
	
	/**
	 * Create the loading dialog (main function, should be used only once).
	 * 
	 * @private	Use comodojo.loader.start instead.
	 */
	_constructDialog: function() {
		if (comodojo.isSomething("comodojoLoader").success) {
			comodojo.destroySomething("comodojoLoader");
		}
		comodojo.loader._loader = new comodojo.dialog._newDialog({
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
		}).launch();
	},
	
	/**
	 * Create the loader content.
	 * 
	 * @private	Use comodojo.loader.start instead.
	 */
	_buildContent: function(loaderMessage, imageReference) {
		comodojo.loader._loaderMessage = dojo.create("div",{innerHTML: loaderMessage, className: "comodojoLoaderMessage"});
		comodojo.loader._loaderImageContainer = dojo.create("div",{className: "comodojoLoaderImageContainer"});
		comodojo.loader._loaderImage = dojo.create("img",{src: imageReference, className: "comodojoLoaderImage"});
	},
	
	/**
	 * Fill the loader content.
	 * 
	 * @private	Use comodojo.loader.start instead.
	 */
	_setContent: function() {
		comodojo.loader._loaderImageContainer.appendChild(comodojo.loader._loaderImage);
		comodojo.loader._loader.containerNode.appendChild(comodojo.loader._loaderImageContainer);
		comodojo.loader._loader.containerNode.appendChild(comodojo.loader._loaderMessage);
	},
	
	/**
	 * Show the loader.
	 * 
	 * @private	Use comodojo.loader.start instead.
	 */
	_show: function() {
		comodojo.loader._loader.show();
		comodojo.loader._loader._size();
		setTimeout(function() {
			comodojo.loader._loader._position();
		},100);
	},
	
	/**
	 * Hide the loader.
	 * 
	 * @private	Use comodojo.loader.stop instead.
	 */
	_hide: function() {
		comodojo.loader._loader.hide();
	},
	
	/**
	 * Create/select and launch the loader dialog.
	 * 
	 * @param	string	loaderMessage	The message that loader will give to user; if false it will be the standard (localized) one.
	 * @param	string	imageReference	The image reference (i.e. "src" field); if false it will be the standard one.
	 * @return	object					Requested loader
	 */
	start: function(loaderMessage, imageReference) {
		comodojo.debugDeep('Requested start of comodojo.loader...');
		if (!comodojo.loader._loader) {
			comodojo.debugDeep('comodojo.loader not yet started, initializing...');
			comodojo.loader._constructDialog();
			comodojo.loader._buildContent(
				!loaderMessage ? comodojo.getLocalizedMessage('10007') : loaderMessage,
				!imageReference ? "comodojo/images/bar_loader.gif" : imageReference
			)
			comodojo.loader._setContent();
		}
		else {
			comodojo.debugDeep('comodojo.loader yet started, changing message...');
			comodojo.loader.changeMessage(
				!loaderMessage ? comodojo.getLocalizedMessage('10007') : loaderMessage,
				!imageReference ? "comodojo/images/bar_loader.gif" : imageReference
			);
		}
		comodojo.loader._show();
		
		return comodojo.loader._loader;
		
	},
	
	/**
	 * Change attributes to a launched loader (during showtime or when hidden).
	 * 
	 * @param	string	loaderMessage	The message that loader will give to user; if false it will be *NULL*.
	 * @param	string	imageReference	The image reference (i.e. "src" field); if false it will be *NULL*.
	 * @return	object					Requested loader
	 */
	changeMessage: function(loaderMessage, imageReference) {
		comodojo.loader._loaderMessage.innerHTML = loaderMessage;
		comodojo.loader._loaderImage.setAttribute('src',imageReference);
	},
	
	/**
	 * Stop the loader.
	 * 
	 * @return	object					Requested loader
	 */
	stop: function() {
		comodojo.debugDeep('Requested stop of comodojo.loader');
		comodojo.loader._hide();
	},
	
	/**
	 * Stop the loader after timeout
	 * 
	 * @return	object					Requested loader
	 */
	stopIn: function(timeout) {
		comodojo.debugDeep('Requested stop in '+ (isFinite(timeout) ? timeout : "(default - value passed is not a valid timeout) 5000") + 'msecs of comodojo.loader');
		setTimeout(function(){comodojo.loader._hide();}, isFinite(timeout) ? timeout : /*default timeout is 5 secs*/ 5000);
	}
	
};