/**
 * [APP DESCRIPTION]
 *
 * @package		Comodojo Core Applications
 * @author		comodojo.org
 * @copyright		__COPYRIGHT__ comodojo.org (info@comodojo.org)
 * @version		__CURRENT_VERSION__
 * @license		GPL Version 3
 */

$d.require("dojo.store.Memory");
$d.require("dijit.tree.ObjectStoreModel");
$d.require("comodojo.Layout");

$c.App.load("keychainmanager",

	function(pid, applicationSpace, status){
	
		//dojo.mixin(this, status);
	
		var myself = this;
		
		this.init = function(){

			this.kStore = new dojo.store.Memory({
				data: [
					{ id: 'krootnode', name:'Keychains'}
				],
				getChildren: function(object){
					return this.query({keychain: object.id});
				}
			});

			$c.Kernel.newCall(myself.initCallback,{
				application: "keychainmanager",
				method: "get_keychains"
			});
		};
		
		this.initCallback = function(success,result) {
			if (success) {
				var i=0;
				for (i in result) {
					myself.kStore.data.push({id:result[i].keychain, name:result[i].keychain, keychain:'krootnode'});
				}
				myself.layout();
			}
			else {
				$c.Error.modal(result.code,result.name);
				myself.stop();
			}
		};


		this.layout = function(){

			this.kModel = new dijit.tree.ObjectStoreModel({
				store: this.kStore,
				query: {id: 'krootnode'}
			});

			this.container = new $c.Layout({
				modules: ['Tree'],
				attachNode: applicationSpace,
				splitter: false,
				id: pid,
				width: 500,
				height: 400,
				hierarchy: [{
					type: 'Tree',
					name: 'left',
					region: 'left',
					cssClass: 'layout_action_pane',
					params: {
						model: this.kModel,
						style: "width: 100px;"
					}
				},{
					type: 'ContentPane',
					name: 'center',
					region: 'center',
					childrens:[]
				},{
					type: 'ContentPane',
					name: 'bottom',
					region: 'bottom',
					cssClass: 'layout_action_pane',
					childrens:[]
				}]
			}).build();
		};
			
	}
	
);
