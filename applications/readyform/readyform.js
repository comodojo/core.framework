/**
 * Hello World application example for comodojo.
 *
 * @package		Comodojo Core Applications
 * @author		comodojo.org
 * @copyright	__COPYRIGHT__ comodojo.org (info@comodojo.org)
 * @version		__CURRENT_VERSION__
 * @license		GPL Version 3
 */

$d.require('comodojo.Form');

$c.App.load("readyform",

	function(pid, applicationSpace, status){
	
		this.modules = ['Button','TextBox'];

		this.hierarchy = [];

		//this.message = this.getLocalizedMessage('0000');

		this.buttonLabel = false;

		this.autoFocus = true;

		this.callback = false;

		this.callbackOnCancel = true;

		dojo.mixin(this,status);

		this.callbackHasFired = false;

		var myself = this;
		
		this.init = function(){
			
			if (!this.buttonLabel) {
				this.buttonLabel = this.getLocalizedMessage('0001');
			}

			this.hierarchy.push({
				name: 'readyform_go_button',
				type: "Button",
				label: this.buttonLabel,
				onClick: function() {
					myself.processForm();
				}
			});

			this.form = new $c.Form({
				modules: this.modules,
				autoFocus: this.autoFocus,
				hierarchy: this.hierarchy,
				attachNode: applicationSpace.containerNode
			}).build();

			if (this.callbackOnClose) {
				dojo.aspect.after(applicationSpace,'close',this.on_cancel_callback);
			}
			
		};

		this.processForm = function() {
			if (!myself.form.validate()) {
				$c.Error.minimal($c.getLocalizedMessage('10028'));
				return;
			}
			else {
				var values = myself.form.get('value');
				delete values.readyform_go_button;
				
				try {
					myself.callback(values);
				}
				catch (e) {
					$c.Error.minimal(e);
				}
				
				myself.callbackHasFired = true;
				myself.stop();
			}
		};

		this.on_cancel_callback = function() {
			if (myself.callbackHasFired) {
				return;
			}
			else {
				try {
					myself.callback(false);
				}
				catch (e) {
					$c.Error.minimal(e);
				}
			}
		}
		
	}
	
);