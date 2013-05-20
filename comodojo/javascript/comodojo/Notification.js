define("comodojo/Notification", [
	"dojo/_base/connect", // connect
	"dojo/_base/declare", // declare
	"dojo/_base/fx", // baseFx.animateProperty
	"dojo/_base/lang", // lang.mixin, lang.hitch
	"dojo/_base/sniff", // has("ie")
	"dojo/_base/window", // baseWin.body
	"dojo/dom-attr", // domAttr.get
	"dojo/dom-class", // domClass.addClass, domClass.removeClass
	"dojo/dom-construct", // domConstruct.destroy
	"dojo/dom-geometry", // domGeo.getContentBox
	"dojo/dom-style", // style.get, style.set
	"dojo/fx", // fx.combine
	"dojo/window", // win.getBox
	"dijit/_WidgetBase", // _WidgetBase
	"dijit/_TemplatedMixin" // _TemplatedMixin
], function(connect, declare, baseFx, lang, has, baseWin,
            domAttr, domClass, domConstruct, domGeo, style,
             fx, win, _WidgetBase, _TemplatedMixin){

var Notification = declare("comodojo.Notification", [_WidgetBase, _TemplatedMixin], {
	// summary:
	//	A notification widget for comodojo startup process. It's in a temp namespace due to transition of comodojo
	//	to AMD format.
	//
	// description:
	// example:

	// Should be i18n-ized in 1.1
	buttonCancel: 'Close',

	message: false,

	templateString: '<div class="comodojo_notification">'+
		'<div class="comodojo_notification_message" dojoAttachPoint="messageNode">message</div>'+
		'<div class="comodojo_notification_close" dojoAttachPoint="closeButtonNode" dojoAttachEvent="onclick: hide">${buttonCancel}</div>'+
		'</div>',

	constructor: function(args){

		//declare.safeMixin(this,args);

	},

	postCreate: function(){
		this.inherited(arguments);
		if(this.domNode.parentNode){
			style.set(this.domNode, "display", "none");
		}
		lang.mixin(this.attributeMap, {
			message:{ node:"messageNode", type:"innerHTML" }
		});
		if(has("ie")==6){
			// IE6 is challenged when it comes to 100% width.
			// It thinks the body has more padding and more
			// margin than it really does. It would work to
			// set the body pad and margin to 0, but we can't
			// set that and disturb a potential layout.
			//
			var self = this;
			var setWidth = function(){
				var v = win.getBox();
				style.set(self.domNode, "width", v.w+"px");
			};
			this.connect(window, "resize", function(){
				setWidth();
			});

			setWidth();
		}
	},

	notify: function(msg){
		
		if(!this.domNode.parentNode || !this.domNode.parentNode.innerHTML){
			document.body.appendChild(this.domNode);
		}

		this.set("message", msg);

		this.show();

	},

	show: function(){
		
		this._bodyMarginTop = style.get(baseWin.body(), "marginTop");
		this._size = domGeo.getContentBox(this.domNode).h;
		style.set(this.domNode, { display:"block", height:0, opacity:0 });

		if(!this._showAnim){
			this._showAnim = fx.combine([
				baseFx.animateProperty({ node:baseWin.body(), duration:500, properties:{ marginTop:this._bodyMarginTop+this._size } }),
				baseFx.animateProperty({ node:this.domNode, duration:500, properties:{ height:this._size, opacity:1 } })
			]);
		}
		this._showAnim.play();
	},

	hide: function(){
		
		if(!this._hideAnim){
			this._hideAnim = fx.combine([
				baseFx.animateProperty({ node:baseWin.body(), duration:500, properties:{ marginTop:this._bodyMarginTop } }),
				baseFx.animateProperty({ node:this.domNode, duration:500, properties:{ height:0, opacity:0 } })
			]);
			connect.connect(this._hideAnim, "onEnd", this, function(){
				style.set(this.domNode, {display:"none", opacity:1});
			});
		}
		this._hideAnim.play();
	}

});


return Notification;
});