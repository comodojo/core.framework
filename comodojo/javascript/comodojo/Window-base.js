define("comodojo/Window-base", ["dojo/_base/kernel","dojo/_base/lang","dojo/_base/window","dojo/_base/declare",
		"dojo/_base/fx","dojo/_base/connect","dojo/_base/array","dojo/_base/sniff",
		"dojo/window","dojo/dom","dojo/dom-class","dojo/dom-geometry","dojo/dom-construct",
		"dijit/_TemplatedMixin","dijit/_Widget","dijit/BackgroundIframe","dojo/dnd/Moveable",
		"dojox/layout/ContentPane","dojox/layout/ResizeHandle"], function(
	kernel, lang, winUtil, declare, baseFx, connectUtil, arrayUtil, 
	has, windowLib, dom, domClass, domGeom, domConstruct, TemplatedMixin, Widget, BackgroundIframe, 
	Moveable, ContentPane, ResizeHandle){
	
var Window = declare("comodojo.Window-base", [ ContentPane, TemplatedMixin ],{

	// closable: Boolean
	//		Allow closure of this Node
	closable: true,

	// minimizable: Boolean
	//		Allow minimizing of Window if true
	minimizable: true,

	// resizable: Boolean
	//		Allow resizing of Window true if true
	resizable: false,

	// maxable: Boolean
	//		Horrible param name for "Can you maximize this floating Window?"
	maxable: false,

	// resizeAxis: String
	//		One of: x | xy | y to limit Window's sizing direction
	resizeAxis: "xy",

	// title: String
	//		Title to use in the header
	title: "",

	// duration: Integer
	//		Time is MS to spend toggling in/out node
	duration: 400,

	icon: null,

	// contentClass: String
	//		The className to give to the inner node which has the content
	contentClass: "comodojoWindowContent",

	// animation holders for toggle
	_showAnim: null,
	_hideAnim: null,
	
	// privates:
	_restoreState: {},
	_allFPs: [],
	_startZ: 100,
	
	_isHided: false,

	templateString: '<div class="'+comodojoConfig.dojoTheme+' comodojoWindow" id="${id}">'+
'<div class="comodojoWindowTitle" tabindex="0" role="button" dojoAttachPoint="focusNode">'+
	'<span dojoAttachPoint="closeNode" dojoAttachEvent="onclick: close" class="comodojoWindowCloseIcon"></span>'+
	'<span dojoAttachPoint="maxNode" dojoAttachEvent="onclick: maximize" class="comodojoWindowMaximizeIcon">&thinsp;</span>'+
	'<span dojoAttachPoint="restoreNode" dojoAttachEvent="onclick: _restore" class="comodojoWindowRestoreIcon">&thinsp;</span>'+	
	'<span dojoAttachPoint="minNode" dojoAttachEvent="onclick: minimize" class="comodojoWindowMinimizeIcon">&thinsp;</span>'+
	'<span dojoAttachPoint="iconNode" class="dijitInline comodojoWindowIconNode"></span>'+
	'<span dojoAttachPoint="titleNode" class="dijitInline comodojoWindowTitleNode"></span>'+
'</div>'+
'<div dojoAttachPoint="canvas" class="comodojoWindowCanvas">'+
	'<div dojoAttachPoint="containerNode" role="region" tabindex="-1" class="${contentClass}"></div>'+
	'<span dojoAttachPoint="resizeHandle" class="comodojoWindowResizeHandle"></span>'+
'</div>'+
'</div>',
	
	attributeMap: lang.delegate(Widget.prototype.attributeMap, {
		title: { type:"innerHTML", node:"titleNode" }
	}),
	
	postCreate: function(){
		this.inherited(arguments);
		new Moveable(this.domNode,{ handle: this.focusNode });
		
		if(!this.minimizable){ this.minNode.style.display = "none"; }
		if(!this.closable){ this.closeNode.style.display = "none"; }
		if(!this.maxable){
			this.maxNode.style.display = "none";
			this.restoreNode.style.display = "none";
		}
		if(!this.resizable){
			this.resizeHandle.style.display = "none";
		}else{
			this.domNode.style.width = domGeom.getMarginBox(this.domNode).w + "px";
		}
		
		if (this.icon !== null) {
			this.iconNode.style.background = "url("+this.icon+") no-repeat top left"; 
		}
		
		this._allFPs.push(this);
		this.domNode.style.position = "absolute";
		
		this.bgIframe = new BackgroundIframe(this.domNode);
		this._naturalState = domGeom.position(this.domNode);
	},
	
	startup: function(){
		if(this._started){ return; }
		
		this.inherited(arguments);

		if(this.resizable){
			if(has("ie")){
				this.canvas.style.overflow = "auto";
			}else{
				this.containerNode.style.overflow = "auto";
			}
			
			this._resizeHandle = new ResizeHandle({
				targetId: this.id,
				resizeAxis: this.resizeAxis
			},this.resizeHandle);

		}

		if(this.minimizable){
			if((this.domNode.style.display == "none")||(this.domNode.style.visibility == "hidden")){
				// If the FP is created minimizable and non-visible, start up docked.
				this.minimize();
			}
		}
		this.connect(this.focusNode,"onmousedown","bringToTop");
		this.connect(this.domNode,	"onmousedown","bringToTop");

		// Initial resize to give child the opportunity to lay itself out
		this.resize(domGeom.position(this.domNode));
		
		this._started = true;
	},

	close: function(){
		// summary:
		//		Close and destroy this widget
		if(!this.closable){ return; }
		connectUtil.unsubscribe(this._listener);
		this.hide(lang.hitch(this,function(){
			this.destroyRecursive();
		}));
	},

	hide: function(/* Function? */ callback){
		// summary:
		//		Close, but do not destroy this Window
		baseFx.fadeOut({
			node:this.domNode,
			duration:this.duration,
			onEnd: lang.hitch(this,function() {
				this.domNode.style.display = "none";
				this.domNode.style.visibility = "hidden";
				if(callback){
					callback();
				}
			})
		}).play();
	},

	show: function(/* Function? */callback){
		// summary:
		//		Show the Window
		var anim = baseFx.fadeIn({node:this.domNode, duration:this.duration,
			beforeBegin: lang.hitch(this,function(){
				this.domNode.style.display = "";
				this.domNode.style.visibility = "visible";
				if (typeof callback == "function") { callback(); }
			})
		}).play();
		this.resize(domGeom.position(this.domNode));
		this._onShow(); // lazy load trigger
	},

	focusOn: function() {
		// summary:
		//		Alias for show
		this.show();
	},

	minimize: function(){
		// summary:
		//		Hide and dock the Window
		if(!this._isMinimized){ this.hide(lang.hitch(this,"_minimize")); }
	},

	maximize: function(){
		// summary:
		//		Make this Window full-screen (viewport)
		if(this._maximized){ return; }
		this._naturalState = domGeom.position(this.domNode);
		if(this._isMinimized){
			this.show();
			setTimeout(lang.hitch(this,"maximize"),this.duration);
		}
		domClass.add(this.focusNode,"comodojoWindowMaximized");
		var winDim = windowLib.getBox();
		this.resize({w:winDim.w-5, h:winDim.h-5,l:0,t:0});
		this._maximized = true;
	},

	_restore: function(){
		if(this._maximized){
			this.resize(this._naturalState);
			domClass.remove(this.focusNode,"comodojoWindowMaximized");
			this._maximized = false;
		}
	},

	_minimize: function(){
		if(!this._isMinimized && this.minimizable){
			this._isMinimized = true;
		}
	},
	
	resize: function(dim){
		
		if (typeof dim == "undefined") {
			return;
		}
		
		//console.log('resizing called to:');
		//console.log(dim);
		// summary:
		//		Size the Window and place accordingly
		dim = dim || this._naturalState;
		this._currentState = dim;

		// From the ResizeHandle we only get width and height information
		var dns = this.domNode.style;
		if("t" in dim){ dns.top = dim.t + "px"; }
		else if("y" in dim){ dns.top = dim.y + "px"; }
		if("l" in dim){ dns.left = dim.l + "px"; }
		else if("x" in dim){ dns.left = dim.x + "px"; }
		dns.width = dim.w + "px";
		dns.height = dim.h + "px";

		// Now resize canvas
		var mbCanvas = { l: 0, t: 0, w: dim.w, h: (dim.h - this.focusNode.offsetHeight) };
		domGeom.setMarginBox(this.canvas, mbCanvas);

		// If the single child can resize, forward resize event to it so it can
		// fit itself properly into the content area
		this._checkIfSingleChild();
		if(this._singleChild && this._singleChild.resize){
			this._singleChild.resize(mbCanvas);
		}
	},
	
	bringToTop: function(){
		// summary:
		//		bring this Window above all other Windows
		var windows = arrayUtil.filter(
			this._allFPs,
			function(i){
				return i !== this;
			},
		this);
		windows.sort(function(a, b){
			return a.domNode.style.zIndex - b.domNode.style.zIndex;
		});
		windows.push(this);
		
		arrayUtil.forEach(windows, function(w, x){
			w.domNode.style.zIndex = this._startZ + (x * 2);
			domClass.remove(w.domNode, "comodojoWindowFg");
		}, this);
		domClass.add(this.domNode, "comodojoWindowFg");
	},
	
	destroy: function(){
		// summary:
		//		Destroy this Window completely
		this._allFPs.splice(arrayUtil.indexOf(this._allFPs, this), 1);
		if(this._resizeHandle){
			this._resizeHandle.destroy();
		}
		this.inherited(arguments);
	}
});

return Window;
});