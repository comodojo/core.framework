/**
 * Comodojo test environment
 *
 * @package		Comodojo Core Applications
 * @author		comodojo.org
 * @copyright	2012 comodojo.org (info@comodojo.org)
 * @version		__CURRENT_VERSION__
 * @license		GPL Version 3
 */

$d.require("dijit.layout.ContentPane");

$c.app.load("qotd",

	function(pid, applicationSpace, status){
	
		//dojo.mixin(this, status);
	
		var myself = this;
		
		this.init = function(){
			$c.kernel.newCall(myself.initCallback,{
				application: "qotd",
				method: "get_message",
				content: {}
			});
		};
		
		this.initCallback = function(success, result) {
			if (!success) {
				$c.error.local(result.code,result.name,applicationSpace.attr('id'));
			}
			else {
				applicationSpace.attr('content',myself.getLocalizedMutableMessage('0000',[result]));
			}
		};
			
	}
	
);