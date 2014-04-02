/**
 * [APP DESCRIPTION]
 *
 * @package		Comodojo Core Applications
 * @author		comodojo.org
 * @copyright	2012 comodojo.org (info@comodojo.org)
 * @version		__CURRENT_VERSION__
 * @license		GPL Version 3
 */

$d.require("dojo.data.ItemFileWriteStore");
$d.require("dojo.store.DataStore");
$d.require("comodojo.Layout");

$c.App.load("info",

	function(pid, applicationSpace, status){
	
		var myself = this;
		
		this.init = function(){
			
			datastore = $c.Kernel.newDatastore('info', 'getInfo', { isWriteStore : true, label : 'id', identifier: 'id' });

    		store = new dojo.store.DataStore({store: datastore, idProperty: 'id'});

    		this.container = new $c.Layout({
				modules: ['Grid'],
				attachNode: applicationSpace,
				splitter: false,
				id: pid,
				hierarchy: [{
					type: 'Grid',
					name: 'center',
					region: 'center',
					params: {
						structure: [
							{ name: this.getLocalizedMessage('0050'), field: 'info', width: "60%", formatter: function(value) {return myself.getLocalizedMessage(value.info);}},
							{ name: this.getLocalizedMessage('0051'), field: 'value', width: "40%"}
						],
						style: 'padding: 0px; margin: 0px !important;',
						store: store
					}
				},{
					type: 'ContentPane',
					name: 'bottom',
					region: 'bottom',
					params: {
						content: myself.getLocalizedMessage('0032')+': '+$c.frameworkVersion+', '+myself.getLocalizedMessage('0033')+': '+$d.version+'<br>'+myself.getLocalizedMessage('0030')+': '+$c.locale+', '+myself.getLocalizedMessage('0031')+': '+$c.timezone,
						style: "height: 30px;"
					}
				}]
			}).build();
		};
		
	}
	
);
