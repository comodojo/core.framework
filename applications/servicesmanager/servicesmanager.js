/**
 * [APP DESCRIPTION]
 *
 * @package		Comodojo Core Applications
 * @author		comodojo.org
 * @copyright		__COPYRIGHT__ comodojo.org (info@comodojo.org)
 * @version		__CURRENT_VERSION__
 * @license		GPL Version 3
 */

$c.App.loadCss('servicesmanager');

$d.require("dojo.on");
$d.require("dojo.store.Memory");
$d.require("dojo.store.Observable");
$d.require("dijit.tree.ObjectStoreModel");
$d.require("comodojo.Layout");
$d.require('comodojo.Form');
$d.require('comodojo.Mirror');

$c.App.load("servicesmanager",

	function(pid, applicationSpace, status){
	
		//dojo.mixin(this, status);

		var myself = this;

		this.availableServices = [];

		this.init = function(){

			this.sStore = new dojo.store.Memory({
				data: [
					{ id: 'srootnode', name:'Type', leaf: false},
					{ id: 'SERVICE', name:'service', type: "srootnode", leaf: false},
					{ id: 'APPLICATION', name:'application', type: "srootnode", leaf: false},
					{ id: 'ALIAS', name:'alias', type: "srootnode", leaf: false}
				],
				getChildren: function(object){
					return this.query({type: object.id});
				}
			});

			$c.Kernel.newCall(myself.initCallback,{
				application: "servicesmanager",
				method: "get_services"
			});

		};
		
		this.initCallback = function(success,result) {
			if (success) {
				var i=0;
				for (i in result) {
					myself.sStore.data.push(result[i]);
					if (result[i]['type'] == 'SERVICE' || result[i]['type'] == 'APPLICATION') {
						myself.availableServices.push({
							label: result[i]['name'],
							id: result[i]['name']
						});
					}
				}
				myself.layout();
			}
			else {
				$c.Error.modal(result.code,result.name);
				myself.stop();
			}
		};

		this.layout = function() {

			this.sStoreObservable = new dojo.store.Observable(this.sStore);

			this.sModel = new dijit.tree.ObjectStoreModel({
				store: this.sStoreObservable,
				query: {id: 'srootnode'}
			});

			this.sModel.mayHaveChildren = function(item) {
				return item.leaf == false;
			};

			this.container = new $c.Layout({
				modules: ['Tree','TabContainer'],
				attachNode: applicationSpace,
				splitter: false,
				id: pid,
				hierarchy: [{
					type: 'Tree',
					name: 'left',
					region: 'left',
					params: {
						model: this.sModel,
						style: "width: 200px;",
						splitter: true,
					}
				},{
					type: 'TabContainer',
					name: 'center',
					region: 'center',
					params: {},
					childrens: [{
						type: 'ContentPane',
						name: 'service_properties',
						params: {
							title: '[SERVICE] properties'
						}
					},{
						type: 'ContentPane',
						name: 'service_code',
						params: {
							title: '[SERVICE] code',
							onShow: function(event) {
								if ($c.Utils.defined(myself.mirror)) {
									myself.mirror.focus();
								}
							}
						}
					}]
				},{
					type: 'ContentPane',
					name: 'bottom',
					region: 'bottom',
					cssClass: 'layout_action_pane'
				}]
			}).build();

			this.container.main.left.getIconClass = function(item, opened) {

				if (!item || this.model.mayHaveChildren(item)) {
					return opened ? "dijitFolderOpened" : "dijitFolderClosed";
				}
				else {
					return item.enabled ? 'servicesmanager_service_enabled' : 'servicesmanager_service_disabled';
				}

			};

			this.mirror = comodojo.Mirror.build({
				attachNode: this.container.main.center.service_code.containerNode, 
				lineNumbers: true,
				mode: "php",
				keyMap: "sublime",
				autoCloseBrackets: true,
				matchBrackets: true,
				showCursorWhenSelecting: true,
				theme: "monokai",
				lineWrapping: true,
				addons: [
					"search/searchcursor",
					"search/search",
					"edit/matchbrackets",
					"edit/closebrackets",
					"comment/comment",
					"wrap/hardwrap",
					"fold/foldcode",
					"fold/foldgutter",
					"fold/brace-fold",
					"fold/comment-fold"
				],
				gutters: ["CodeMirror-linenumbers", "CodeMirror-foldgutter"]
			});

			this.mirror.setSize('100%','100%')

		};

	}
	
);