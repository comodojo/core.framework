comodojo.loadCss('comodojo/CSS/layout.css');
comodojo.loadCss('comodojo/javascript/dojox/layout/resources/ExpandoPane.css');
//comodojo.loadCss('comodojo/javascript/dojox/grid/resources/'+comodojoConfig.dojoTheme+'Grid.css');
//comodojo.loadCss('comodojo/javascript/dijit/themes/'+comodojoConfig.dojoTheme+'/document.css');
comodojo.loadCss('comodojo/javascript/gridx/resources/'+comodojoConfig.dojoTheme+'/Gridx.css');

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

	load_modules: function(dfrrd,module) {
		require(module, function(mod) {
			dfrrd.resolve(mod);
		})
	},

	constructor: function(args) {

		that = this;

		declare.safeMixin(this,args);

		if (!this.id) {
			this.id = comodojo.getPid();
		}

		//this._layout = false;
		//this.layout = [];
		//this._structure = false;
		//this.real_attach_node = false;

		this.deferred_calls = [];

		for (i in args.modules) {
			var mods;
			switch(args.modules[i]) {
				case 'TabContainer':
					mods = ["dijit/layout/TabContainer"];
				break;
				case 'AccordionContainer':
					mods = ["dijit/layout/AccordionContainer"];
				break;
				case 'ExpandoPane':
					mods = ["dojox/layout/ExpandoPane"];
				break;
				case 'Tree':
					mods = ["dijit/Tree"/*,"dijit/tree/ObjectStoreModel","dojo/store/Observable"*/];
				break;
				case 'Grid':
					mods = ["gridx/Grid"];
				break;
				/*case 'GridSortSimple':
					mods = ["gridx/modules/SingleSort"];
				break;
				case 'GridSortNested':
					mods = ["gridx/modules/NestedSort"];
				break;
				case 'GridEdit':
					mods = ["gridx/modules/CellWidget","gridx/modules/Edit"];
				break;
				case 'GridPaginationBar':
					mods = ["gridx/modules/Pagination","gridx/modules/pagination/PaginationBar"];
				break;
				case 'GridPaginationDropDown':
					mods = ["gridx/modules/Pagination","gridx/modules/pagination/PaginationBarDD"];
				break;
				case 'GridFilterBar':
					mods = ["gridx/modules/Filter","gridx/modules/filter/FilterBar"];
				break;
				case 'GridFilterQuick':
					mods = ["gridx/modules/Filter","gridx/modules/filter/QuickFilter"];
				break;
				case 'GridRowHeader':
					mods = ["gridx/modules/RowHeader"];
				break;
				case 'GridVirtualScroller':
					mods = ["gridx/modules/VirtualVScroller"];
				break;
				case 'GridIndirectSelect':
					mods = ["gridx/modules/IndirectSelect"];
				break;
				case 'GridSimpleSelectRow':
					mods = ["gridx/modules/select/Row"];
				break;
				case 'GridSimpleSelectColumn':
					mods = ["gridx/modules/select/Column"];
				break;
				case 'GridSimpleSelectCell':
					mods = ["gridx/modules/select/Cell"];
				break;
				case 'GridExtendedSelectRow':
					mods = ["gridx/modules/extendedSelect/Row"];
				break;
				case 'GridExtendedSelectColumn':
					mods = ["gridx/modules/extendedSelect/Column"];
				break;
				case 'GridExtendedSelectCell':
					mods = ["gridx/modules/extendedSelect/Cell"];
				break;
				case 'GridColumnResizer':
					mods = ["gridx/modules/ColumnResizer"];
				break;*/
				
			}

			this.deferred_calls[i] = new Deferred();

			this.deferred_calls[i].then(function(v) {
				comodojo.debug('Loaded layout module (deferred): '+args.modules[i]);
			});
			
			this.load_modules(this.deferred_calls[i],mods);
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
		
		var attach_node, container_dimensions, real_width, real_height, extra_height, resize;
		
		if (Utils.defined(attachNode.isComodojoApplication)) {
			
			// it is a comodojo application
			switch(attachNode.isComodojoApplication) {
				case "WINDOWED":
					//console.info('is win');
					//console.info(attachNode.containerNode);
					attach_node = attachNode.containerNode;
					real_width  = domGeom.getMarginBox(attachNode.containerNode).w;
					real_height = domGeom.getMarginBox(attachNode.canvas).h;
					resize = true;
				break;
				case "MODAL":
					//console.info('is modal');
					//console.info(attachNode.containerNode);
					attach_node = attachNode.containerNode;
					real_width  = domGeom.getMarginBox(attach_node).w;
					real_height = domGeom.getMarginBox(attach_node).h;
					resize = false;
				break;
				case "ATTACHED":
					//console.log(this.attachNode);
					//attach_node = (this.attachNode.containerNode ? this.attachNode.containerNode : (this.attachNode.domNode ? this.attachNode.domNode : this.attachNode));
					attach_node = (Utils.isNode(attachNode.containerNode) ? attachNode.containerNode : (Utils.isNode(attachNode.domNode) ? attachNode.domNode : attachNode));
					real_width  = domGeom.getMarginBox(attach_node).w;
					real_height = domGeom.getMarginBox(attach_node).h;
					resize = false;
				break;
			}
		}
		else if (Utils.defined(attachNode.containerNode)) {
			// it is a dojo layout dom element
			attach_node = attachNode.containerNode;
			container_dimensions = domGeom.getMarginBox(attachNode.containerNode);
			real_width  = container_dimensions.w;
			real_height = container_dimensions.h;
		}
		else {
			// it is a non-dojo dom element 
			attach_node = attachNode;
			container_dimensions = domGeom.getMarginBox(attachNode);
			real_width  = container_dimensions.w;
			real_height = container_dimensions.h;
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
			
			real_width = domGeom.getMarginBox(that.attachNode.containerNode).w-2;
			real_height = domGeom.getMarginBox(that.attachNode.canvas).h-2;
			
			var node = this.domNode, mb = {w:real_width,h:real_height,l:0,t:0};
			
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