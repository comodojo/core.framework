/**
 * Hello World application example for comodojo.
 *
 * @package		Comodojo Core Applications
 * @author		comodojo.org
 * @copyright	__COPYRIGHT__ comodojo.org (info@comodojo.org)
 * @version		__CURRENT_VERSION__
 * @license		GPL Version 3
 */

/**
 * Load also the css included, to show output message properly
 */
$c.App.loadCss('helloworld');

/**
 * Load comodojo modules, if required
 */
$d.require('comodojo.Form');
$d.require('comodojo.Layout');

/**
 * Now declare application, as a function defined in second member of $c.app.load.
 * First parameter should be always the app name.
 */
$c.App.load("helloworld",

	/**
	 * The function will be invoked using tree paramenters:
	 *  - pid: the interal process id of launched app (each istance will have it's own pid)
	 *  - applicationSpace: the application container
	 *  - status: object containing all startup params to mix with $d.mixin
	 */
	function(pid, applicationSpace, status){
	
		/**
		 * myself is used to come back in app scope from async calls (like kernel call)
		 */
		 var myself = this;
		 
		/**
		 * init is the method thad $c.app will invoke as first (consider it like a constructor)
		 */
		 this.init = function(){
		 	
		 	this.container = new $c.Layout({
		 		attachNode: applicationSpace,
		 		splitter: false,
		 		id: pid,
		 		hierarchy: [{
		 			type: 'Content',
		 			name: 'top',
		 			region: 'top',
		 			params: {
		 				style: "height: 100px;"
		 			}
		 		},
		 		{
		 			type: 'Content',
		 			name: 'center',
		 			region: 'center',
		 			params: {
		 				style:"overflow: auto;"
		 			}
		 		}]
		 	}).build();
		 	
		 	this.form = new $c.Form({
		 		modules: ['Button','TextBox'],
		 		autoFocus: true,
		 		hierarchy: [{
		 			name: "to",
		 			value: "",
		 			type: "TextBox",
		 			label: "Say hello to:"
		 		},{
		 			name: "go",
		 			type: "Button",
		 			label: "Say",
		 			onClick: function() {
		 				var val = myself.form.get('value').to
		 				myself.say(val.length == 0 ? false : val);
		 			}
		 		}],
		 		attachNode: this.container.main.top.containerNode
		 	}).build();
		 	
		 };
		
		this.say = function(to) {
			content = !to ? {} : {to: to};
			$c.Kernel.newCall(myself.sayCallback,{
				application: "helloworld",
				method: "say",
				content: content
			});
		};
		
		this.sayCallback = function(success,result) {
			if (success) {
				myself.container.main.center.set('content','<p class="helloworld_helloMessage">'+result+'</p>');
			}
			else {
				$c.Error.local(myself.container.main.center,result.code,result.name);
			}
		};
		
	}
	
);
