define([
	"dojo/_base/declare",
	"dojo/aspect",
	"dojo/on",
	"dojo/_base/lang",
	"dojo/dom-construct",
	"dojo/dom-attr",
	"dojo/dom-class",
	"dojo/window",
	"dijit/registry",
	"dijit/Dialog",
	"dijit/form/Button",
	"comodojo/Utils",
	"dojo/domReady!"],
function(declare,aspect,on,lang,domConstruct,domAttr,domClass,win,registry,dialog,button,utils){

// module:
// 	comodojo/Dialog-base

var dbase = declare(null, {

	// Id (default )
	id : "comodojo_dialog_"+comodojo.getPid(),

	// Title and content options
	title : 'Dialog',
	content : false,
	href : false,

	// Building options
	parseOnLoad : true,

	templateString : false,

	draggable : true,

	timer : false,

	blocker : false,

	_isApplication : false,

	forced : false,

	persistent : false,

	focusKilled : true,

	hided : false,

	width: false,

	height: false,

	maxWidth : win.getBox().w-20,

	maxHeight : win.getBox().h-20,

	hideOverflow : false,

	primaryCloseButton : true,

	secondaryCloseButton : false,

	actionOk : null,

	actionCancel : null,

	closeOnOk : false,

	closeOnCancel : true,

	cssClass : false,

	//this.forceWidth = false;
	//this.forceHeight = false;

	constructor: function(args) {
		
		declare.safeMixin(this,args);

		// Eval parameters and combinations

		if ((this.timer - 0) === this.timer && this.timer.toString.length > 0) {
			//comodojo.debugDeep('A timer was requested, it will override persistence, hiding and force dialog creation.');
			this.persistent = false;
			//this.hided = false;
			this.forced = true;
		}
		if (this.blocker) {
			//comodojo.debugDeep('Blocker was requested, it will override focusKilling, primaryCloseButton, secondaryCloseButton, actions and hiding.');
			this.focusKilled = false;
			//this.hided = false;
			this.primaryCloseButton = false;
			this.secondaryCloseButton = false;
			this.actionOk = null;
			//this.actionCancel = null;
		}
		if (this._isApplication) {
			//comodojo.debugDeep('Application dialog was requested, it will override focusKilling, secondaryCloseButton, actions and hiding.');
			this.focusKilled = false;
			this.hided = false;
			this.secondaryCloseButton = false;
			this.actionOk = null;
			this.actionCancel = null;
		}
		if (!this.primaryCloseButton) {
			//comodojo.debugDeep('PrimaryCloseButton will not be displayed.');
			this.templateString = "<div class=\"dijitDialog\" role=\"dialog\" aria-labelledby=\"${id}_title\">\n\t<div data-dojo-attach-point=\"titleBar\" class=\"dijitDialogTitleBar\">\n\t\t<span data-dojo-attach-point=\"titleNode\" class=\"dijitDialogTitle\" id=\"${id}_title\"\n\t\t\t\trole=\"header\" level=\"1\"></span>\n\t\t<span data-dojo-attach-point=\"closeButtonNode\" title=\"${buttonCancel}\" role=\"button\" tabIndex=\"-1\">\n\t\t\t<span data-dojo-attach-point=\"closeText\" class=\"closeText\" title=\"${buttonCancel}\">&nbsp;</span>\n\t\t</span>\n\t</div>\n\t<div data-dojo-attach-point=\"containerNode\" class=\"dijitDialogPaneContent\"></div>\n</div>\n";
		}
		if (registry.byId(this.id) != null) {
			if (!this.forced) {
				comodojo.debug("Id already in use, aborting...");
				return false;
			}
			else {
				utils.destroyWidget(this.id);
			}
		}

		// Start building dialog...

		var params = {
			id: this.id,
			title: this.title,
			parseOnLoad: this.parseOnLoad,
			draggable: this.draggable,
			style: ''
		};
		
		if (utils.isNode(this.content)) {
			this.toAppend = this.content;
		}
		else if (utils.isElement(this.content)) {
			this.toAppend = this.content;
		}
		else if (lang.isString(this.content)) {
			params.content = this.content;
		}
		else if (this.href != false) {
			params.href = this.href;
		}
		else {
			params.content = "";
		}

		if (lang.isString(this.templateString)) {
			params.templateString = this.templateString;
		}
		
		if (this.maxWidth != false) {
			params.style += 'max-width: '+(utils.isNumeric(this.maxWidth) ? this.maxWidth+'px' : this.maxWidth)+' !important;';
		}
		if (this.maxHeight != false) {
			params.style += 'max-height: '+(utils.isNumeric(this.maxHeight) ? this.maxHeight+'px' : this.maxHeight)+' !important;';
		}
		if (this.width != false) {
			params.style += 'width: '+(utils.isNumeric(this.width) ? this.width+'px' : this.width)+' !important;';
		}
		if (this.height != false) {
			params.style += 'height: '+ (utils.isNumeric(this.height) ? this.height+'px' : this.height)+' !important;';
		}
		
		this._dialog = new dialog(params);
		
		var diag = this._dialog;

		if (this.cssClass != false) { domClass.add(diag.domNode,this.cssClass); }

		this._dialog.containerNode.style.overflow = !this.hideOverflow ? "auto" : "hidden";

		if (!this.persistent || this.focusKilled) {
			aspect.after(this._dialog, "hide", function(){utils.destroyWidget(this.id);});
			comodojo.debugDeep('Dialog with id: '+this.id+' will not persist after hiding.');
		}
		
		if (this.blocker || this._isApplication) {
			this._dialog._onKey = function(){
				return false;
			};
		}

		if ((this.timer - 0) === this.timer && this.timer.toString.length > 0) {
			setTimeout("comodojo.Utils.destroyWidget('" + this.id + "');", this.timer);
			comodojo.debugDeep('Dialog with id: ' + this.id + ' will be destroyed in ' + this.timer + 'ms.');
		}
		
		if (this.secondaryCloseButton || this.actionOk !== null || this.actionCancel !== null) {
			this.actionBar = domConstruct.create("div", {
				"class": "dijitDialogPaneActionBar"
			}, this._dialog.containerNode);
		}

		if (lang.isFunction(this.actionOk)) {
			var actionOkButton = new dijit.form.Button({
				label: comodojo.getLocalizedMessage('10019'),
				onClick: this.actionOk
			}).placeAt(this.actionBar);
			if (this.closeOnOk) {
				on(actionOkButton, 'click', function(){ diag.hide(); });
			}
			var actionCancelButton = new dijit.form.Button({
				label: comodojo.getLocalizedMessage('10020'),
				onClick: lang.isFunction(this.actionCancel) ? this.actionCancel : function() {}
			}).placeAt(this.actionBar);
			if (this.closeOnCancel) { 
				on(actionCancelButton, 'click', function(){ diag.hide(); });
			}
		}

		if (!lang.isFunction(this.actionOk) && lang.isFunction(this.actionCancel)) {
			var actionCancelButton = new dijit.form.Button({
				label: comodojo.getLocalizedMessage('10004'),
				onClick: this.actionCancel
			}).placeAt(this.actionBar);
			if (this.closeOnCancel) { 
				on(actionCancelButton, 'click', function(){ diag.hide(); });
			}
		}

		if (this.secondaryCloseButton) {
			new button({
				label: comodojo.getLocalizedMessage('10011'),
				onClick: function() { diag.hide(); }
			}).placeAt(this.actionBar);
		}
		
		if (utils.defined(this.toAppend)) {
			this._dialog.containerNode.appendChild(this.toAppend);
		}

		if (!this.hided) {
			this._dialog.startup();
			this._dialog.show();
		}
		
		//return this._dialog;
	}

});

return dbase;

});