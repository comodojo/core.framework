/**
 * [APP DESCRIPTION]
 *
 * @package		Comodojo Core Applications
 * @author		comodojo.org
 * @copyright	2012 comodojo.org (info@comodojo.org)
 * @version		__CURRENT_VERSION__
 * @license		GPL Version 3
 */

$c.loadComponent('layout',["Grid"]);

$c.App.load("info",

	function(pid, applicationSpace, status){
	
		this.enableJavascriptExtras = false;
	
		dojo.mixin(this, status);
	
		var myself = this;
		
		this.init = function(){
			
			this.container = new $c.layout({
				attachNode: applicationSpace,
				splitter: false,
				_pid: pid,
				hierarchy: [{
					type: 'Grid',
					name: 'center',
					region: 'center',
					createStore: {
						name: 'grid_store',
						application: 'info',
						method: 'get_info',
						label : 'id',
						identifier : 'id'
					},
					params: {
						structure: [
						    { name: this.getLocalizedMessage('0050'), field: 'info', width: "60%", formatter: function(value) {return myself.getLocalizedMessage(value);}},
						    { name: this.getLocalizedMessage('0051'), field: 'value', width: "40%"}
						],
						style: 'padding: 0px; margin: 0px !important;'
					}
				}]
			}).build();
			if (this.enableJavascriptExtras) {
				this.pushOtherInfo(this.container.stores.grid_store,this.container.main.center);
			}
		};
		
		this.pushOtherInfo = function(store, grid) {
			store.newItem({id:50, info:'0030',value:$c.locale});
			store.newItem({id:51, info:'0031',value:$c.timezone});
			store.newItem({id:52, info:'0032',value:$c.frameworkVersion});
			store.newItem({id:53, info:'0033',value:$d.version});
		};
		
	}
	
);
