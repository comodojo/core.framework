/**
 * [APP DESCRIPTION]
 *
 * @package		Comodojo Core Applications
 * @author		comodojo.org
 * @copyright	__COPYRIGHT__ comodojo.org (info@comodojo.org)
 * @version		__CURRENT_VERSION__
 * @license		GPL Version 3
 */

$c.App.load("cacheman",

	function(pid, applicationSpace, status){
	
		var myself = this;
		
		this.init = function(){
			$c.kernel.newCall(myself.initCallback,{
				application: "cacheman",
				method: "get_stats"
			});
		};
		
		this.initCallback = function(success,result) {
			if (success) {
				myself.form = new $c.form({
					autoFocus: false,
					hierarchy: [{
		              	"name": "tips",
		                "type": "warning",
		                "content": myself.getLocalizedMessage('0000')
		            },{
		                "name": "active",
		                "type": "info",
		                "content": myself.getLocalizedMessage('0001')+result.active_pages
		            },{
		                "name": "expired",
		                "type": "info",
		                "content": myself.getLocalizedMessage('0002')+result.expired_pages
		            },{
		                "name": "ttl",
		                "type": "info",
		                "content": myself.getLocalizedMessage('0003')+result.cache_ttl+' sec'
		            },{
		                "name": "oldest",
		                "type": "info",
		                "content": myself.getLocalizedMessage('0004')+$c.date.fromServer(result.oldest_page)
		            },{
		                name: "purge",
		                type: "Button",
		                label: myself.getLocalizedMessage('0005'),
		                onClick: function() {
		                	myself.purge();
		                }
		            }],
					attachNode: applicationSpace.containerNode
				}).build();
			}
			else {
				$c.error.global(result.code,result.name);
				myself.stop();
			}
		};
		
		this.purge = function() {
			$c.loader.start();
			$c.kernel.newCall(myself.purgeCallback,{
				application: "cacheman",
				method: "purge_cache"
			});
		};
		
		this.purgeCallback = function() {
			$c.loader.changeMessage(myself.getLocalizedMessage('0006'),$c.icons.getIcon('apply',32));
			$c.loader.stopIn(2000);
			myself.stop();
		};
		
	}
	
);
