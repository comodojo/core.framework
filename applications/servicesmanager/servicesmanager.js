/**
 * [APP DESCRIPTION]
 *
 * @package		Comodojo Core Applications
 * @author		comodojo.org
 * @copyright		__COPYRIGHT__ comodojo.org (info@comodojo.org)
 * @version		__CURRENT_VERSION__
 * @license		GPL Version 3
 */

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

		this.init = function(){
			this.layout();
		};
		
		this.initCallback = function(success,result) {
		
		};

		this.layout = function() {

			this.container = new $c.Layout({
				modules: ['Tree','TabContainer'],
				attachNode: applicationSpace,
				splitter: false,
				id: pid,
				hierarchy: [{
					type: 'ContentPane',
					name: 'left',
					region: 'left',
					params: {
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