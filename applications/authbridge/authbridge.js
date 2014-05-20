/**
 * Bridge for the comodojo remote auth users' listing.
 *
 * @package		Comodojo Core Applications
 * @author		comodojo.org
 * @copyright	__COPYRIGHT__ comodojo.org (info@comodojo.org)
 * @version		__CURRENT_VERSION__
 * @license		GPL Version 3
 */

$c.App.load("authbridge",

	function(pid, applicationSpace, status){

		this.init = function(){
		 	
		 	applicationSpace.set('content',this.getLocalizedMessage('0000'));
		 	
		};
		
	}
	
);
