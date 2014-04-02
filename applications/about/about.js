/**
 * About comodojo
 *
 * @package		Comodojo Core Applications
 * @author		comodojo.org
 * @copyright	__COPYRIGHT__ comodojo.org (info@comodojo.org)
 * @version		__CURRENT_VERSION__
 * @license		GPL Version 3
 */

$c.App.load("about",

	function(pid, applicationSpace, status){
	
		var myself = this;
		
		this.init = function(){
			$c.Kernel.newCall(myself.initCallback,{
				application: "about",
				method: "getinfo",
				preventCache: false,
				content: {}
			});
		};
		
		this.initCallback = function(success,result) {
			if (success) {
				applicationSpace.set('content',result);
			}
			else {
				myself.stop();
				$c.Error.modal(result.code,result.name);
			}
		};
			
	}
	
);
