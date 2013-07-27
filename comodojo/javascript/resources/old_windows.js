dojo.require("comodojo.Window");
dojo.require("dojox.layout.ResizeHandle");
comodojo.loadCss('comodojo/CSS/window.css');
comodojo.loadCss('comodojo/javascript/dojox/layout/resources/ResizeHandle.css');

/**
 * Extend comodojo environment enablig it to draw simple windows.
 * Windows are custom, site-wide dojox.layout.FloatingPane.
 * 
 *  @class
 */
comodojo.windows = {

	/**
	 * Create and launch a window (main function).
	 *
	 * ***TBW***
	 *
	 * @private	Use comodojo.window.XXX alias instead.
	 */
	_newWindow: function(params){
	
		this.id = false;
		
		this.title = "";
		
		this.width = false;
		this.height = false;
		
		this.resizable = false;
		this.maxable = false;
		this.closable = true;
		this.minimizable = true;
		
		this.parseOnLoad = true;
		this.preventCache = false;
		
		this.icon = null;
		
		this.href = false;
		this.content = false;
		
		dojo.mixin(this, params);
		
		comodojo.debugDeep("Launching new window, id will be: " + this.id);
		
		var myNewWin = new comodojo.Window({
			title: this.title,
			id : this.id,
			icon: this.icon,
			minimizable: this.minimizable,
			maxable: this.maxable,
			closable: this.closable,
			resizable: this.resizable,
			preventCache: this.preventCache,
			parseOnLoad: this.parseOnLoad,
			href: this.href != false ? this.href : false,
			content: this.content != false ? this.content : false
		},dojo.create('div',null,dojo.body())/*dojo.create("div", null, dojo.body(), "first")atpoint*/);
		/*
		if (this.href != false) {
			myNewWin.set('href',this.href);
		}
		else if (this.content != false) {
			myNewWin.set('content',this.content);
		}
		else {
			//WTF should I do right now?!? :)
		}
		*/
		comodojo.debugDeep("Window launched and started, have fun!");
		
		return myNewWin;
		
	},
	
	_placeOnAndResize: function(win,width,height) {
		win.domNode.style.top = "25px";
		win.domNode.style.left = "5px";
		//win.resize({ w:width, h:height });
		//win.domNode.style.width = width;
		//win.domNode.style.height = height;
		win.startup();
		win.resize({ w:width, h:height });
				
	},
	
	application: function(pid,title,width,height,resizable,maxable,icon) {
	
		var win = this._newWindow({id:pid,title:title,resizable:resizable,maxable:maxable,icon:icon});
		
		this._placeOnAndResize(win,width,height);
		
		return win;
	
	},
	
	util: function(title, width, height, resizable, maxable) {
		
		var win = this._newWindow({id:comodojo.getPid(), title:title,resizable:resizable,maxable:maxable,content:"",minimizable:false});
		
		this._placeOnAndResize(win,width,height);
		
		return win;
		
	},
	
	info: function(content,width,height) {
		
		var win = this._newWindow({id:comodojo.getPid(), title:"!",resizable:false,maxable:false,content:content,minimizable:false});
		
		this._placeOnAndResize(win,width,height);
		
		return win;
		
	},
	
	external: function(href,title,width,height) {
		
		var win = this._newWindow({id:comodojo.getPid(), title:title,resizable:true,maxable:false,href:href,minimizable:false});
		this._placeOnAndResize(win,width,height);
		
		return win;
		
	},
	
	_popup: function(params) {
		
		this.href = "";
		this.name = "comodojo_popup_"+comodojo.getPid();
		
		this.top = 0;
		this.left = 0;
		this.autoCenter = 0;
		
		this.width = 300;
		this.height = 300;
		this.resizable = 1;
		
		this.status = 0;
		this.menubar = 0;
		this.toolbar = 0;
		this.scrollbars = 0;
		this.location = 0;
		
		this.dependent = 0;
		this.fullscreen = 0;
		
		dojo.mixin(this, params);
		
		if (this.autoCenter) {
			this.left = Math.floor((screen.width-w)/2);
  			this.top = Math.floor((screen.height-h)/2);
		}
		
		if (this.fullscreen) {
			this.width = Math.floor(screen.width);
			this.height = Math.floor(screen.height);
		}
		
		var features = 'left='+this.left+',top='+this.top+',width='+this.width+',height='+this.height+',resizable='+this.resizable+',toolbar='+this.toolbar+',status='+this.status+',scrollbars='+this.scrollbars+',menubar='+this.menubar+',location='+this.location+',dependent='+this.dependent;
		
		return window.open(this.href,this.name,features);
		
	},
	
	simplePopup: function(href,width,height) {
		
		return new comodojo.windows._popup({
			href: href,
			width: width,
			height: height
		});
		
	},
	
	centeredPopup: function(href,width,height) {
		
		return new comodojo.windows._popup({
			href: href,
			width: width,
			height: height,
			autoCenter: 1
		});
		
	},
	
	fullscreenPopup: function(href) {
		
		return new comodojo.windows._popup({
			href: href,
			fullscreen: 1
		});
		
	}
	
};