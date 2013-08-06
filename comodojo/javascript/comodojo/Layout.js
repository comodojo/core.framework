comodojo.loadCss('comodojo/CSS/layout.css');
define("comodojo/Layout", [
	"dojo/_base/lang",
	"dojo/_base/Deferred",
	/*"dojo/has",*/
	"dojo/_base/declare",
	"dojo/dom-construct",
	"dojo/dom-class",
	"dojo/dom-geometry",
	"dijit/layout/BorderContainer",
	"dijit/layout/ContentPane",
	"dijit/layout/TabContainer",
	"dijit/layout/AccordionContainer",
	"dojox/layout/ExpandoPane",
	"comodojo/Utils"
], 
function(
	lang,
	Deferred,
	/*, has*/
	declare,
	domConstruct,
	domClass,
	domGeom,
	BorderContainer,
	ContentPane,
	TabContainer,
	AccordionContainer,
	ExpandoPane,
	Utils
){

	// module:
	// 	comodojo/Form

var layout = declare(null,{
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

	constructor: function(args) {

		declare.safeMixin(this,args);

		if (!this.id) {
			this.id = comodojo.getPid();
		}

		//this._layout = false;
		//this.layout = [];
		//this._structure = false;
		//this.real_attach_node = false;

	},

	build: function() {
		if (!this.attachNode) {
			comodojo.debug("Cannot build layout without a valid attachNode.");
			return false;
		}
		
		var dim = this.computeDimension(this.attachNode);
		
		var structure = this.prepareStructure(this.hierarchy);
		
		var layout = this.buildLayout(structure, dim.attachNode);
		
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
				content: '<div class="layout_gridbox_icon" style="background-image: url('+image+')"></div> <div class="layout_gridbox_title">'+title+'</div>',
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

	prepareStructure: function(hierarchy) {
		var structure = {
			widget: "dijit.layout.BorderContainer",
			name: 'main',
			params: {
				id: 'comodojoLayout_'+this.id,
				style: "height:"+this._height+"px; width:"+this._width+"px; overflow: auto;",
				gutters: this.gutters,
				design: this.design
			},
			childrens: []
		};
		this.pushStructure(hierarchy, structure.childrens);
		return structure;
	},

	computeDimension: function(attachNode) {
		
		var attach_node, container_dimensions, real_width, real_height, extra_height;
		
		if (Utils.defined(attachNode.isComodojoApplication)) {
			// it is a comodojo application
			switch(attachNode.isComodojoApplication) {
				case "WINDOWED":
					attach_node = attachNode.containerNode;
					real_width  = domGeom.getMarginBox(attachNode.containerNode).w;
					real_height = domGeom.getMarginBox(attachNode.canvas).h;
				break;
				case "MODAL":
					attach_node = attachNode.containerNode;
					real_width  = domGeom.getMarginBox(attachNode.containerNode).w;
					real_height = domGeom.getMarginBox(attach_node).h;
				break;
				case "ATTACHED":
					attach_node = (this.attachNode.containerNode ? this.attachNode.containerNode : (this.attachNode.domNode ? this.attachNode.domNode : this.attachNode));
					real_width  = domGeom.getMarginBox(attach_node).w;
					real_height = domGeom.getMarginBox(attach_node).h;
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
			height: (!this.height || this.height == "auto") ? (real_height > this.minHeight ? real_height-2 : this.minHeight) : this.height
		};
		
	},

	buildLayout: function(structure, attachNode) {
		
		var layout, privateLayout; 

		privateLayout = Utils.fromHierarchy(structure,layout);
		
		//console.log(this.layout);
		
		attachNode.appendChild(privateLayout.domNode);
		
		privateLayout.resize = function(changeSize, resultSize) {
			
			real_width = $d.getMarginBox(that.attachNode.containerNode).w-2;
			real_height = $d.getMarginBox(that.attachNode.canvas).h-2;
			
			var node = this.domNode, mb = {w:real_width,h:real_height,l:0,t:0};
			
			$d.setMarginBox(node, mb);
			
			//if(!this.cs || !this.pe){
				this.cs = $d.getComputedStyle(node);
				this.pe = $d.getPadExtents(node, this.cs);
				this.pe.r = $d.toPixelValue(node, this.cs.paddingRight);
				this.pe.b = $d.toPixelValue(node, this.cs.paddingBottom);
				this.pe.t = $d.toPixelValue(node, this.cs.paddingTop);
				this.pe.l = $d.toPixelValue(node, this.cs.paddingLeft);

				node.style.padding = "5px";
				
				var me = $d.getMarginExtents(node, this.cs);
				var be = $d.getBorderExtents(node, this.cs);
				
				this._borderBox = {
					w: mb.w - (me.w + be.w),
					h: mb.h - (me.h + be.h)
				};
				
				this._contentBox = {
					l: $d.toPixelValue(node, this.cs.paddingLeft),
					t: $d.toPixelValue(node, this.cs.paddingTop),
					w: this._borderBox.w - this.pe.w - 10,
					h: this._borderBox.h - this.pe.h - 10
				};
				
			//}
			
			this.layout();

		};
		
		privateLayout.startup();
		
		return layout;

	},

	pushStructure: function(hierarchy, place) {


		for ( var i in hierarchy ) {
			
			var wtype, wname, wregion, wid, wreference, wparams;
			
			wname = $c.isDefined(hierarchy[i].name) ? hierarchy[i].name : ($c.isDefined(hierarchy[i].region) ? hierarchy[i].region : hierarchy[i].type);
			wregion = $c.isDefined(hierarchy[i].region) ? hierarchy[i].region : false;
			wid = wname+'_'+wregion+'_'+this._pid;
			
			gridBoxDef = {
				title: '',
				image: 'comodojo/icons/64x64/empty.png'
			};
			
			wparams = {
				region: wregion,
				splitter: this.splitter
			};
			
			switch (hierarchy[i].type) {
				case "ContentPane":
				case "BorderContainer":
				case "TabContainer":
				case "AccordionContainer":
				case "Tree":
					wtype = "dijit.layout."+hierarchy[i].type;
				break;
				case "ExpandoPane":
					wtype = "dojox.layout."+hierarchy[i].type;
				break;
				case "Grid":
					wtype = "dojox.grid.DataGrid";
				break;
				case "TreeGrid":
					wtype = "dijit.grid.TreeGrid";
				break;
				case "GridBox":
					wtype = "dijit.layout.ContentPane";
					lang.mixin(gridBoxDef, hierarchy[i].gridBox);
					wparams.content = '<div class="layout_gridbox_icon" style="background-image: url('+gridBoxDef.image+')"></div> <div class="layout_gridbox_title">'+gridBoxDef.title+'</div>';
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

return layout;	

});