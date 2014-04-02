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

$c.App.load("qotd",

	function(pid, applicationSpace, status){
	
		var myself = this;
		
		this.init = function(){
			$c.Kernel.newCall(myself.initCallback,{
				application: "qotd",
				method: "getMessage",
				content: {}
			});
		};
		
		this.initCallback = function(success, result) {
			if (!success) {
				$c.Error.local(applicationSpace,result.code,result.name);
			}
			else {
				applicationSpace.attr('content',myself.getLocalizedMutableMessage('0000',[result]));
			}
		};
			
	}
	
);