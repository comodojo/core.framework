/**
 * [APP DESCRIPTION]
 *
 * @package		Comodojo Core Applications
 * @author		comodojo.org
 * @copyright	__COPYRIGHT__ comodojo.org (info@comodojo.org)
 * @version		__CURRENT_VERSION__
 * @license		GPL Version 3
 */

$c.app.load("license",

	function(pid, applicationSpace, status){
	
		//dojo.mixin(this, status);
	
		var myself = this;
		
		this.init = function(){
			$c.kernel.newCall(myself.initCallback,{
				application: "license",
				method: "get_info",
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
				$c.error.global(result.code,result.name);
			}
		};
			
	}
	
);
