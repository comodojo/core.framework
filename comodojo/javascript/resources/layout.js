/*
 * Load base layouts
 */
$d.require("dijit.layout.BorderContainer");
$d.require("dijit.layout.ContentPane");
$c.loadCss('comodojo/CSS/layout.css');

/*
 * Load tab container if requested
 */
$d.requireIf(comodojo.inArray('TabContainer',comodojo.bus._modules.layout), "dijit.layout.TabContainer");

/*
 * Load accordion container if requested
 */
$d.requireIf(comodojo.inArray('AccordionContainer',comodojo.bus._modules.layout), "dijit.layout.AccordionContainer");

/*
 * Load expando pane if requested
 */
$d.requireIf(comodojo.inArray('ExpandoPane',comodojo.bus._modules.layout), "dojox.layout.ExpandoPane");
if (comodojo.inArray('ExpandoPane',comodojo.bus._modules.layout)) {	$c.loadCss('comodojo/javascript/dojox/layout/resources/ExpandoPane.css'); }

/*
 * Load grid if requested
 */
$d.requireIf(comodojo.inArray('Grid',comodojo.bus._modules.layout), "dojox.grid.DataGrid");
$d.requireIf(comodojo.inArray('Grid',comodojo.bus._modules.layout), "dojo.data.ItemFileReadStore");
$d.requireIf(comodojo.inArray('Grid',comodojo.bus._modules.layout), "dojo.data.ItemFileWriteStore");
if (comodojo.inArray('Grid',comodojo.bus._modules.layout)) {
	//$c.loadCss('comodojo/javascript/dojox/grid/resources/Grid.css');
	$c.loadCss('comodojo/javascript/dojox/grid/resources/'+comodojoConfig.dojoTheme+'Grid.css');
}

/*
 * Load tree if requestes
 */
$d.requireIf(comodojo.inArray('Tree',comodojo.bus._modules.layout), "dijit.Tree");
$d.requireIf(comodojo.inArray('Tree',comodojo.bus._modules.layout), "dijit.tree.ForestStoreModel");

/*
 * Load a TreeGrid if requested
 */
$d.requireIf(comodojo.inArray('TreeGrid',comodojo.bus._modules.layout), "dojox.grid.TreeGrid");
$d.requireIf(comodojo.inArray('TreeGrid',comodojo.bus._modules.layout), "dojo.data.ItemFileReadStore");
$d.requireIf(comodojo.inArray('TreeGrid',comodojo.bus._modules.layout), "dojo.data.ItemFileWriteStore");
$d.requireIf(comodojo.inArray('TreeGrid',comodojo.bus._modules.layout), "dijit.tree.ForestStoreModel");
if (comodojo.inArray('TreeGrid',comodojo.bus._modules.layout)) {
	//$c.loadCss('comodojo/javascript/dojox/grid/resources/Grid.css');
	$c.loadCss('comodojo/javascript/dojox/grid/resources/'+comodojoConfig.dojoTheme+'Grid.css');
}

/** 
 * layout.js
 * 
 * Give to CoMoDojo the ability to create layouts
 *
 * @package		Comodojo ClientSide Core Packages
 * @author		comodojo.org
 * @copyright	2012 comodojo.org (info@comodojo.org)
 * @version		__CURRENT_VERSION__
 * @license		GPL Version 3
 * 
 * @usage
 * 
 */
comodojo.layout = function(params) {
	
	/**
	 * Design of the main container
	 *
	 * @var	string
	 * @default	'headline'
	 */
	this.design = 'headline'; // OR 'sidebar'
	
	/**
	 * If true, layout components will use gutters
	 *
	 * @var	bool
	 * @default	false
	 */
	this.gutters = true;
	
	/**
	 * If true, layout components will use splitters
	 *
	 * @var	bool
	 * @default	false
	 */
	this.splitter = false;
	
	/**
	 * Width of the new layout (not single component); if auto, layout will take width of attachNode (if > of minWidth)
	 * 
	 * @var string
	 * @default	"auto"
	 */
	this.width = "auto";
	
	/**
	 * Height of the new layout (not single component); if auto, layout will take height of attachNode (if > of minHeight)
	 * 
	 * @var string
	 * @default	"auto"
	 */
	this.height = "auto";
	
	/**
	 * Minimal width for layout (in px)
	 * 
	 * @var int
	 * @default	400
	 */
	this.minWidth = 400;
	
	/**
	 * Minimal height for layout (in px)
	 * 
	 * @var int
	 * @default	200
	 */
	this.minHeight = 200;
	
	/**
	 * A node (DOM) in wich template will be attached
	 * 
	 * @var bool/domNode
	 * @default	false
	 */
	this.attachNode = false;
	
	/**
	 * The PID (unique) layout should have
	 * 
	 * @var string
	 */
	this._pid = comodojo.getPid();
	
	/**
	 * The layout hierarchy
	 * 
	 * @var object
	 */
	this.hierarchy = {};
	
	dojo.mixin(this, params);
	
	this._layout = false;
	this.layout = [];
	this._structure = false;
	this._stores = {};
	this._models = {};
	
	this.real_attach_node = false;
	
	var that = this;
	
	/**
	 * Build the layout
	 * 
	 * @return	object The required layout
	 */
	this.build = function() {

		if (!this.attachNode) {
			comodojo.debug("Cannot build layout without a valid attachNode.");
			return false;
		}
		
		this._computeDimension();
		
		this._prepareStructure(this.hierarchy);
		
		this._buildLayout();
		
		this.layout.newChild = function(to, name, params) { 
		
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
				
			$d.mixin(_params, params);
				
			var child = new dijit.layout.ContentPane(_params);
				
			to.addChild(child);
			to[name] = child;
			
			return child;
		
		};
		
		this.layout.newGridBox = function(to, name, title, image) { 
			
			var child = new dijit.layout.ContentPane({
				content: '<div class="layout_gridbox_icon" style="background-image: url('+image+')"></div> <div class="layout_gridbox_title">'+title+'</div>',
				className: "layout_gridbox_element_"+comodojoConfig.dojoTheme,
				onMouseOver: function() {$d.addClass(this.domNode,"layout_gridbox_element_"+comodojoConfig.dojoTheme+"_selected");},
				onMouseLeave: function() {$d.removeClass(this.domNode,"layout_gridbox_element_"+comodojoConfig.dojoTheme+"_selected");},
				onMouseDown: function() {$d.addClass(this.domNode,"layout_gridbox_element_"+comodojoConfig.dojoTheme+"_pressed");},
				onMouseUp: function() {$d.removeClass(this.domNode,"layout_gridbox_element_"+comodojoConfig.dojoTheme+"_pressed");}
			});
			
			to.addChild(child);
			to[name] = child;
			
			return child;
		
		};
		
		this.layout.updateGrid = function (grid, store, params) {
			
			store.close();
			
			var storeDef = {
				name: '',
				application: '',
				method: '',
				isWriteStore: true,
				label : 'id',
				identifier : 'id',
				clearOnClose : true,
				urlPreventCache: false,
				content: {}
			};
			if (!params) {
				grid.setStore(store);
			}
			else {
				$d.mixin(storeDef, params);
				store = $c.kernel.newDatastore(storeDef.application, storeDef.method, storeDef);
				this.stores[storeDef.name] = store;
				grid.setStore(store);
			}
			
		};
		
		this.layout.stores = this._stores;
		this.layout.models = this._models;
		
		return this.layout;
		
	};
	
	this._prepareStructure = function(hierarchy) {
		this._structure = {
			widget: "dijit.layout.BorderContainer",
			name: 'main',
			params: {
				id: 'comodojoLayout_'+this._pid,
				style: "height:"+this._height+"px; width:"+this._width+"px; overflow: auto;",
				gutters: this.gutters,
				design: this.design
			},
			//name: 'container',
			//region: 'container',
			//childrens: this._pushStructure(hierarchy)
			childrens: []
		};
		this._pushStructure(hierarchy, this._structure.childrens);
		//console.log(this._structure);
	};
	
	this._pushStructure = function(hierarchy, place) {
		
		for ( var i in hierarchy ) {
			
			var wtype, wname, wregion, wid, wreference, wparams, storeDef;
			
			wname = $c.isDefined(hierarchy[i].name) ? hierarchy[i].name : ($c.isDefined(hierarchy[i].region) ? hierarchy[i].region : hierarchy[i].type);
			wregion = $c.isDefined(hierarchy[i].region) ? hierarchy[i].region : false;
			wid = wname+'_'+wregion+'_'+this._pid;
			
			/**
			 * Here the store standard definition.
			 *  - In case of 'store' parameter passed in hierarchy (that means use create it's own store), 
			 *    it will be used for Grid or Tree
			 *  - In case of 'createStore' object passed, layout will create a new datastore according
			 *    to user parameters; store will be available in the .stores[storename] returned object.
			 */
			storeDef = {
				name: '',
				application: '',
				method: '',
				isWriteStore: true,
				label : 'id',
				identifier : 'id',
				clearOnClose : true,
				urlPreventCache: false,
				content: {}
			};
			
			/**
			 * Here the tree model standard definition.
			 *  - In case of 'model' specified in parameters, tree model will be created as requested
			 *  - In case of no 'model' in parameters, tree model will be created with default values
			 */
			modelDef = {
				name: wname+'_model',
				store: false,
				rootLabel: "root",
				childrenAttrs: ["childs"]
			};
			
			/**
			 * Here the GridBox basic element
			 */
			gridBoxDef = {
				title: '',
				image: 'comodojo/icons/64x64/empty.png'
			};
			
			wparams = {
				//id: wid,
				//style: ,
				region: wregion,
				splitter: this.splitter
			};
			
			switch (hierarchy[i].type) {
				case "ContentPane":
				case "BorderContainer":
				case "TabContainer":
				case "AccordionContainer":
					wtype = "dijit.layout."+hierarchy[i].type;
				break;
				case "ExpandoPane":
					wtype = "dojox.layout."+hierarchy[i].type;
				break;
				case "Grid":
					wtype = "dojox.grid.DataGrid";
					if (hierarchy[i].createStore) {
						$d.mixin(storeDef, hierarchy[i].createStore);
						this._stores[storeDef.name] = $c.kernel.newDatastore(storeDef.application, storeDef.method, storeDef);
						wparams.store = this._stores[storeDef.name];
					}
					else {
						wparams.store = hierarchy[i].store;
					}
				break;
				case "Tree":
					wtype = "dijit.Tree";
					if (hierarchy[i].createStore) {
						$d.mixin(storeDef, hierarchy[i].createStore);
						this._stores[storeDef.name] = $c.kernel.newDatastore(storeDef.application, storeDef.method, storeDef);
						modelDef.store = this._stores[storeDef.name];
					}
					else {
						modelDef.store = hierarchy[i].store;
					}
					
					if (hierarchy[i].model) { $d.mixin(modelDef, hierarchy[i].model); }
					this._models[modelDef.name] = new dijit.tree.ForestStoreModel(modelDef);
					
					wparams.model = this._models[modelDef.name];
					
					//this.treeLdap.getIconClass = function(item,opened){
					//	return (!item || this.model.mayHaveChildren(item)) ? (opened ? "dijitFolderOpened" : "dijitFolderClosed") : "usersmanager_usertree_user";
					//};
				break;
				case "TreeGrid":
					wtype = "dijit.grid.TreeGrid";
					if (hierarchy[i].createStore) {
						$d.mixin(storeDef, hierarchy[i].createStore);
						this._stores[storeDef.name] = $c.kernel.newDatastore(storeDef.application, storeDef.method, storeDef);
						modelDef.store = this._stores[storeDef.name];
					}
					else {
						modelDef.store = hierarchy[i].store;
					}
					
					if (hierarchy[i].model) { $d.mixin(modelDef, hierarchy[i].model); }
					this._models[modelDef.name] = new dijit.tree.ForestStoreModel(modelDef);
					
					wparams.treeModel = this._models[modelDef.name];
					
					//this.treeLdap.getIconClass = function(item,opened){
					//	return (!item || this.model.mayHaveChildren(item)) ? (opened ? "dijitFolderOpened" : "dijitFolderClosed") : "usersmanager_usertree_user";
					//};
				break;
				case "GridBox":
					wtype = "dijit.layout.ContentPane";
					$d.mixin(gridBoxDef, hierarchy[i].gridBox);
					wparams.content = '<div class="layout_gridbox_icon" style="background-image: url('+gridBoxDef.image+')"></div> <div class="layout_gridbox_title">'+gridBoxDef.title+'</div>';
					hierarchy[i].cssClass = "layout_gridbox_element_"+comodojoConfig.dojoTheme;
					wparams.onMouseOver = function() {$d.addClass(this.domNode,"layout_gridbox_element_"+comodojoConfig.dojoTheme+"_selected");};
					wparams.onMouseLeave = function() {$d.removeClass(this.domNode,"layout_gridbox_element_"+comodojoConfig.dojoTheme+"_selected");};
					wparams.onMouseDown = function() {$d.addClass(this.domNode,"layout_gridbox_element_"+comodojoConfig.dojoTheme+"_pressed");};
					wparams.onMouseUp = function() {$d.removeClass(this.domNode,"layout_gridbox_element_"+comodojoConfig.dojoTheme+"_pressed");};
					wparams.name = wname;
				break;
				default:
					wtype = "dijit.layout.ContentPane";
				break;
			}
			
			dojo.mixin(wparams, hierarchy[i].params);
			
			wreference = {
				widget: wtype,
				name: wname,
				params: wparams,
				childrens: [] 
			};
			
			if ($c.isDefined(hierarchy[i].cssClass)) { wreference.cssClass = hierarchy[i].cssClass; }
			if ($c.isDefined(hierarchy[i].childrens)) { this._pushStructure(hierarchy[i].childrens, wreference.childrens); }
			
			place.push(wreference);
			
		}
		
	};
	
	this._buildLayout = function() {
		
		this._layout = comodojo.fromHierarchy(this._structure,this.layout);
		
		//console.log(this.layout);
		
		this.real_attach_node.appendChild(this._layout.domNode);
		
		this._layout.resize = function(changeSize, resultSize) {
			
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
		
		this._layout.startup();
		
	};
	
	this._computeDimension = function() {
		
		var container_dimensions, real_width, real_height;
		
		if ($c.isDefined(this.attachNode.isComodojoApplication)) {
			// it is a comodojo application
			switch(this.attachNode.isComodojoApplication) {
				case "WINDOWED":
					this.real_attach_node = this.attachNode.containerNode;
					real_width = $d.getMarginBox(this.attachNode.containerNode).w;
					real_height = $d.getMarginBox(this.attachNode.canvas).h;
				break;
				case "MODAL":
					this.real_attach_node = this.attachNode.containerNode;
					real_width = $d.getMarginBox(this.attachNode.containerNode).w;
					real_height = $d.getMarginBox(this.real_attach_node).h;
				break;
				case "ATTACHED":
					this.real_attach_node = (this.attachNode.containerNode ? this.attachNode.containerNode : (this.attachNode.domNode ? this.attachNode.domNode : this.attachNode));
					real_width = $d.getMarginBox(this.real_attach_node).w;
					real_height = $d.getMarginBox(this.real_attach_node).h;
				break;
			}
		}
		else if ($c.isDefined(this.attachNode.containerNode)) {
			// it is a dojo layout dom element
			this.real_attach_node = this.attachNode.containerNode;
			container_dimensions = $d.getMarginBox(this.attachNode.containerNode);
			real_width = container_dimensions.w;
			real_height = container_dimensions.h;
		}
		else {
			// it is a non-dojo dom element 
			this.real_attach_node = this.attachNode;
			container_dimensions = $d.getMarginBox(this.attachNode);
			real_width = container_dimensions.w;
			real_height = container_dimensions.h;
		}
		
		if (this.real_attach_node.childNodes.length != 0) {
			var extra_height;
			dojo.forEach(this.real_attach_node.childNodes, function(c) {
				extra_height = $d.getMarginBox(c).h;
				//if (!isNaN(dojo.coords(c).w)) { realAttachNodeCoords.w = realAttachNodeCoords.w - dojo.coords(c).w; }
				if (extra_height != 0) { real_height = real_height - extra_height; }
			});
		}
		
		if (!this.width || this.width == "auto") {
			this._width = (real_width > this.minWidth ? real_width-2 : this.minWidth);
			/*
			if (comodojoConfig.dojoTheme == "claro") {
				this._width = (realAttachNodeCoords.w > this.minWidth ? realAttachNodeCoords.w-2 : this.minWidth);
			}
			else {
				this._width = (realAttachNodeCoords.w > this.minWidth ? realAttachNodeCoords.w : this.minWidth);
			}*/
		}
		else {
			this._width = this.width;
		}
		
		if (!this.height || this.height == "auto") {
			this._height = (real_height > this.minHeight ? real_height-2 : this.minHeight);
			/*
			if (comodojoConfig.dojoTheme == "claro") {
				this._height = (realAttachNodeCoords.h > this.minHeight ? realAttachNodeCoords.h-2 : this.minHeight);
			}
			else {
				this._height = (realAttachNodeCoords.h > this.minHeight ? realAttachNodeCoords.h : this.minHeight);
			}
			*/
		}
		else {
			this._height = this.height;
		}
		
		//console.log('Final dim WxH='+this._width+'X'+this._height);
		
	};
		
};
