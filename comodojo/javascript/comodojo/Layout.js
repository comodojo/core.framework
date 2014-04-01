comodojo.loadCss('comodojo/CSS/layout.css');
comodojo.loadCss('comodojo/javascript/dojox/layout/resources/ExpandoPane.css');
//comodojo.loadCss('comodojo/javascript/dojox/grid/resources/'+comodojoConfig.dojoTheme+'Grid.css');
//comodojo.loadCss('comodojo/javascript/dijit/themes/'+comodojoConfig.dojoTheme+'/document.css');
comodojo.loadCss('comodojo/javascript/gridx/resources/'+(comodojoConfig.dojoTheme == 'claro' ? 'claro/' : '')+'Gridx.css');

define("comodojo/Layout", [
	"dojo/_base/lang",
	"dojo/_base/Deferred",
	/*"dojo/has",*/
	"dojo/_base/declare",
	"dojo/dom-construct",
	"dojo/dom-class",
	"dojo/dom-geometry",
	"dojo/dom-style",
	"dijit/layout/BorderContainer",
	"dijit/layout/ContentPane",
	//"dijit/layout/TabContainer",
	//"dijit/layout/AccordionContainer",
	//"dojox/layout/ExpandoPane",
	"comodojo/Utils",
	"gridx/core/model/cache/Sync",
	"gridx/core/model/cache/Async"
], 
function(
	lang,
	Deferred,
	/*, has*/
	declare,
	domConstruct,
	domClass,
	domGeom,
	domStyle,
	BorderContainer,
	ContentPane,
	//TabContainer,
	//AccordionContainer,
	//ExpandoPane,
	Utils,
	SyncCache,
	AsyncCache
){

	// module:
	// 	comodojo/Form

var that = false;

var Layout = declare(null,{
	// summary:
	// description:


	// Design of the main container
	// String
	design: 'headline', // OR 'sidebar'
	
	// If true, layout components will use gutters
	// Bool
	gutters: true,
	
	// If true, layout components will use splitters
	// Bool
	splitter: false,
	
	// Width of the new layout (not single component); if auto, layout will take width of attachNode (if > of minWidth)
	// String|Int
	width: "auto",
	
	// Height of the new layout (not single component); if auto, layout will take height of attachNode (if > of minHeight)
	// String|Int
	height: "auto",
	
	// Minimal width for layout (in px)
	// Int
	minWidth: 400,
	
	// Minimal height for layout (in px)
	// Int
	minHeight: 200,
	
	// A node (DOM) in wich template will be attached
	// Node
	attachNode: false,
	
	// The ID (unique) layout should have
	// String
	id: false,
	
	// The layout hierarchy
	// Object
	hierarchy: {},

	// Modules to load
	// Array
	modules: [],

	//load_modules: function(dfrrd,module) {
	//	require(module, function(mod) {
	//		dfrrd.resolve(mod);
	//	})
	//},

	constructor: function(args) {

		that = this;

		declare.safeMixin(this,args);

		if (!this.id) {
			this.id = comodojo.getPid();
		}

		// this.deferred_calls = [];

		// for (i in args.modules) {
		// 	var mods;
		// 	switch(args.modules[i]) {
		// 		case 'TabContainer':
		// 			mods = ["dijit/layout/TabContainer"];
		// 		break;
		// 		case 'AccordionContainer':
		// 			mods = ["dijit/layout/AccordionContainer"];
		// 		break;
		// 		case 'ExpandoPane':
		// 			mods = ["dojox/layout/ExpandoPane"];
		// 		break;
		// 		case 'Tree':
		// 			mods = ["dijit/Tree"];
		// 		break;
		// 		case 'Grid':
		// 			mods = ["gridx/Grid"];
		// 		break;
				
		// 	}

		// 	this.deferred_calls[i] = new Deferred();

		// 	this.deferred_calls[i].then(function(v) {
		// 		comodojo.debug('Loaded layout module (deferred): '+args.modules[i]);
		// 	});
			
		// 	this.load_modules(this.deferred_calls[i],mods);

		//}

		this.required = [];

		for (i in args.modules) {
			switch(args.modules[i]) {
				case 'TabContainer':
					this.required.push("dijit/layout/TabContainer");
				break;
				case 'AccordionContainer':
					this.required.push("dijit/layout/AccordionContainer");
				break;
				case 'ExpandoPane':
					this.required.push("dojox/layout/ExpandoPane");
				break;
				case 'Tree':
					this.required.push("dijit/Tree");
				break;
				case 'Grid':
					this.required.push("gridx/Grid");
				break;
			}
		}

		if (this.required.length != 0) {
			require(this.required);
		}

	},

	build: function() {

		if (!this.attachNode) {
			comodojo.debug("Cannot build layout without a valid attachNode.");
			return false;
		}
		
		var dim = this.computeDimension(this.attachNode);
		
		var structure = this.prepareStructure(this.hierarchy, dim.width, dim.height);
		
		var layout = this.buildLayout(structure, dim.attachNode, dim.resize);
		
		layout.newChild = function(to, name, params) { 
			var _params = {
				id: false,
				content: false,
				href: false,
				style: false,
				cssClass: false,
				label: '',
				title: '',
				region: false,
				splitter: this.splitter
			};
				
			lang.mixin(_params, params);
				
			var child = new ContentPane(_params);
				
			to.addChild(child);
			to[name] = child;
			
			return child;
		};
		
		layout.newGridBox = function(to, name, title, image) { 
			var child = new ContentPane({
				content: '<div class="layout_gridbox_icon" style="background: url('+image+') no-repeat center center;"></div> <div class="layout_gridbox_title">'+title+'</div>',
				className: "layout_gridbox_element_"+comodojoConfig.dojoTheme,
				onMouseOver: function() {domClass.add(this.domNode,"layout_gridbox_element_"+comodojoConfig.dojoTheme+"_selected");},
				onMouseLeave: function() {domClass.remove(this.domNode,"layout_gridbox_element_"+comodojoConfig.dojoTheme+"_selected");},
				onMouseDown: function() {domClass.add(this.domNode,"layout_gridbox_element_"+comodojoConfig.dojoTheme+"_pressed");},
				onMouseUp: function() {domClass.remove(this.domNode,"layout_gridbox_element_"+comodojoConfig.dojoTheme+"_pressed");}
			});
			
			to.addChild(child);
			to[name] = child;
			
			return child;
		};
		
//**		this.layout.updateGrid = function (grid, store, params) {
//**			
//**			store.close();
//**			
//**			var storeDef = {
//**				name: '',
//**				application: '',
//**				method: '',
//**				isWriteStore: true,
//**				label : 'id',
//**				identifier : 'id',
//**				clearOnClose : true,
//**				urlPreventCache: false,
//**				content: {}
//**			};
//**			if (!params) {
//**				grid.setStore(store);
//**			}
//**			else {
//**				$d.mixin(storeDef, params);
//**				store = $c.kernel.newDatastore(storeDef.application, storeDef.method, storeDef);
//**				this.stores[storeDef.name] = store;
//**				grid.setStore(store);
//**			}
//**			
//**		};
//**		
//**		this.layout.stores = this._stores;
//**		this.layout.models = this._models;

		return layout;
	},

	prepareStructure: function(hierarchy,width,height) {
		var structure = {
			widget: "dijit.layout.BorderContainer",
			name: 'main',
			params: {
				id: 'comodojoLayout_'+this.id,
				style: "height:"+height+"px; width:"+width+"px; overflow: auto;",
				gutters: this.gutters,
				design: this.design
			},
			childrens: []
		};
		this.pushStructure(hierarchy, structure.childrens);
		return structure;
	},

	computeDimension: function(attachNode) {
		
		var attach_node, container_dimensions, real_width, real_height, extra_height, resize, width, height;
		
		if (Utils.defined(attachNode.isComodojoApplication)) {
			
			// it is a comodojo application
			switch(attachNode.isComodojoApplication) {
				case "WINDOWED":
					comodojo.debug('Computing dim for app node ('+attachNode.isComodojoApplication+') - containerNode');
					attach_node = attachNode.containerNode;
					real_width  = domGeom.getMarginBox(attachNode.containerNode).w;
					real_height = domGeom.getMarginBox(attachNode.canvas).h;
					resize = true;
				break;
				case "MODAL":
					comodojo.debug('Computing dim for app node ('+attachNode.isComodojoApplication+') - containerNode');
					attach_node = attachNode.containerNode;
					real_width  = domGeom.getMarginBox(attach_node).w;
					real_height = domGeom.getMarginBox(attach_node).h;
					resize = false;
				break;
				case "ATTACHED":
					if (Utils.isNode(attachNode.containerNode)) {
						comodojo.debug('Computing dim for app node ('+attachNode.isComodojoApplication+') - containerNode');
						attach_node = attachNode.containerNode;
						real_width  = domGeom.getMarginBox(attach_node).w;
						real_height = domGeom.getMarginBox(attach_node).h;
					}
					else if (Utils.isNode(attachNode.domNode)) {
						comodojo.debug('Computing dim for app node ('+attachNode.isComodojoApplication+') - domNode');
						attach_node = attachNode.domNode;
						real_width  = domGeom.getMarginBox(attach_node).w;
						real_height = domGeom.getMarginBox(attach_node).h;
					}
					else {
						comodojo.debug('Computing dim for app node ('+attachNode.isComodojoApplication+') - node');
						attach_node = attachNode;
						var computedStyle = domStyle.getComputedStyle(attach_node);
						real_width  = domGeom.getMarginBox(attach_node,computedStyle).w;
						real_height = domGeom.getMarginBox(attach_node,computedStyle).h;
					}
					resize = false;
				break;
			}

			comodojo.debug('Layout will adapt to: '+real_width+'x'+real_height);

		}
		else if (Utils.defined(attachNode.containerNode)) {
			// it is a dojo layout dom element
			comodojo.debug('Computing dim for layout node');
			attach_node = attachNode.containerNode;
			container_dimensions = domGeom.getMarginBox(attachNode.containerNode);
			real_width  = container_dimensions.w;
			real_height = container_dimensions.h;
			comodojo.debug('Layout will adapt to: '+real_width+'x'+real_height);
		}
		else {
			comodojo.debug('Computing dim for dom node');
			attach_node = attachNode;
			var computedStyle = domStyle.getComputedStyle(attach_node);
			real_width  = domGeom.getMarginBox(attach_node).w;
			real_height = domGeom.getMarginBox(attach_node).h;
			comodojo.debug('Layout will adapt to: '+real_width+'x'+real_height);
		}
		
		if (attach_node.childNodes.length != 0) {
			dojo.forEach(attach_node.childNodes, function(c) {
				extra_height = domGeom.getMarginBox(c).h;
				if (extra_height !== 0) { real_height = real_height - extra_height; }
			});
		}
		
		return {
			attachNode: attach_node,
			width : (!this.width || this.width == "auto") ? (real_width > this.minWidth ? real_width-2 : this.minWidth) : this.width,
			height: (!this.height || this.height == "auto") ? (real_height > this.minHeight ? real_height-2 : this.minHeight) : this.height,
			resize: resize
		};
		
	},

	buildLayout: function(structure, attachNode, shouldResize) {
		
		var layout=[], privateLayout; 

		privateLayout = Utils.fromHierarchy(structure,layout);
		
		//console.log(privateLayout);
		
		attachNode.appendChild(privateLayout.domNode);
		
		privateLayout.resize = function(changeSize, resultSize) {
			
			var node = this.domNode;

			// set margin box size, unless it wasn't specified, in which case use current size
			//if(changeSize){
			//	domGeom.setMarginBox(node, changeSize);
			//}

			switch(that.attachNode.isComodojoApplication) {
				case "WINDOWED":
					real_width  = domGeom.getMarginBox(that.attachNode.containerNode).w-2;
					real_height = domGeom.getMarginBox(that.attachNode.canvas).h-2;
				break;
				case "MODAL":
					real_width  = domGeom.getMarginBox(that.attachNode.containerNode).w-2;
					real_height = domGeom.getMarginBox(that.attachNode.containerNode).h-2;
				break;
				case "ATTACHED":
					var attach_node;
					if (Utils.isNode(attachNode.containerNode)) {
						attach_node = attachNode.containerNode;
						real_width  = domGeom.getMarginBox(attach_node).w;
						real_height = domGeom.getMarginBox(attach_node).h;
					}
					else if (Utils.isNode(attachNode.domNode)) {
						attach_node = attachNode.domNode;
						real_width  = domGeom.getMarginBox(attach_node).w;
						real_height = domGeom.getMarginBox(attach_node).h;
					}
					else {
						attach_node = attachNode;
						var computedStyle = domStyle.getComputedStyle(attach_node);
						real_width  = domGeom.getMarginBox(attach_node,computedStyle).w;
						real_height = domGeom.getMarginBox(attach_node,computedStyle).h;
					}
				break;
			}

			var mb = {w:real_width,h:real_height,l:0,t:0};
			
			domGeom.setMarginBox(node, mb);
			
			//if(!this.cs || !this.pe){
				this.cs = domStyle.getComputedStyle(node);
				this.pe = domGeom.getPadExtents(node, this.cs);
				this.pe.r = domStyle.toPixelValue(node, this.cs.paddingRight);
				this.pe.b = domStyle.toPixelValue(node, this.cs.paddingBottom);
				this.pe.t = domStyle.toPixelValue(node, this.cs.paddingTop);
				this.pe.l = domStyle.toPixelValue(node, this.cs.paddingLeft);

				node.style.padding = "5px";
				
				var me = domGeom.getMarginExtents(node, this.cs);
				var be = domGeom.getBorderExtents(node, this.cs);
				
				this._borderBox = {
					w: mb.w - (me.w + be.w),
					h: mb.h - (me.h + be.h)
				};
				
				this._contentBox = {
					l: domStyle.toPixelValue(node, this.cs.paddingLeft),
					t: domStyle.toPixelValue(node, this.cs.paddingTop),
					w: this._borderBox.w - this.pe.w - 10,
					h: this._borderBox.h - this.pe.h - 10
				};
				
			//}
			
			this.layout();

		};
		
		privateLayout.startup();

		if (shouldResize) {
			// THIS IS UGLY
			// Resize function is still buggy, so it needs to be called 3 times to work correctly
			// THIS IS UGLY
			privateLayout.resize();
			privateLayout.resize();
			privateLayout.resize();
		}
	
		return layout;

	},

	pushStructure: function(hierarchy, place) {


		for ( var i in hierarchy ) {
			
			var wtype, wname, wregion, wid, wreference, wparams;
			
			wname = Utils.defined(hierarchy[i].name) ? hierarchy[i].name : (Utils.defined(hierarchy[i].region) ? hierarchy[i].region : hierarchy[i].type);
			wregion = Utils.defined(hierarchy[i].region) ? hierarchy[i].region : false;
			wid = wname+'_'+wregion+'_'+this.id;
			
			gridBoxDef = {
				title: '',
				image: 'comodojo/icons/64x64/empty.png'
			};
			
			wparams = {
				region: wregion,
				splitter: this.splitter,
				cacheClass: 'async'
			};
			
			switch (hierarchy[i].type) {
				case "ContentPane":
				case "BorderContainer":
				case "TabContainer":
				case "AccordionContainer":
					wtype = "dijit.layout."+hierarchy[i].type;
				break;
				case "Tree":
					wtype = "dijit.Tree";
					//wparams.model = new dijit.tree.ObjectStoreModel({
					//	store: new dojo.store.Observable(hierarchy[i].store),
					//	query: hierarchy[i].query
					//});
				break;
				case "ExpandoPane":
					wtype = "dojox.layout."+hierarchy[i].type;
				break;
				case "Grid":
					wtype = "gridx.Grid";
				break;
				//case "TreeGrid":
				//	wtype = "dijit.grid.TreeGrid";
				//break;
				case "GridBox":
					wtype = "dijit.layout.ContentPane";
					lang.mixin(gridBoxDef, hierarchy[i].gridBox);
					wparams.content = '<div class="layout_gridbox_icon" style="background: url('+gridBoxDef.image+') no-repeat center center;"></div> <div class="layout_gridbox_title">'+gridBoxDef.title+'</div>';
					hierarchy[i].cssClass = "layout_gridbox_element_"+comodojoConfig.dojoTheme;
					wparams.onMouseOver = function() {domClass.add(this.domNode,"layout_gridbox_element_"+comodojoConfig.dojoTheme+"_selected");};
					wparams.onMouseLeave = function() {domClass.remove(this.domNode,"layout_gridbox_element_"+comodojoConfig.dojoTheme+"_selected");};
					wparams.onMouseDown = function() {domClass.add(this.domNode,"layout_gridbox_element_"+comodojoConfig.dojoTheme+"_pressed");};
					wparams.onMouseUp = function() {domClass.remove(this.domNode,"layout_gridbox_element_"+comodojoConfig.dojoTheme+"_pressed");};
					wparams.name = wname;
				break;
				default:
					wtype = "dijit.layout.ContentPane";
				break;
			}
			
			lang.mixin(wparams, hierarchy[i].params);

			if (wtype == "gridx.Grid") {
				wparams.cacheClass = wparams.cacheClass == 'sync' ? SyncCache : AsyncCache;
			}

			wreference = {
				widget: wtype,
				name: wname,
				params: wparams,
				childrens: [] 
			};
			
			if (Utils.defined(hierarchy[i].cssClass)) { wreference.cssClass = hierarchy[i].cssClass; }
			if (Utils.defined(hierarchy[i].childrens)) { this.pushStructure(hierarchy[i].childrens, wreference.childrens); }
			
			place.push(wreference);
			
		}
	}

});

return Layout;	

});