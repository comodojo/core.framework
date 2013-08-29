/**
 * [APP DESCRIPTION]
 *
 * @package		Comodojo Core Applications
 * @author		comodojo.org
 * @copyright	__COPYRIGHT__ comodojo.org (info@comodojo.org)
 * @version		__CURRENT_VERSION__
 * @license		GPL Version 3
 */

$d.require('comodojo.Form');

$c.App.load("regrecover",

	function(pid, applicationSpace, status){
	
		var myself = this;
		
		this.init = function(){

			this.recoverform = new $c.Form({
				modules: ['EmailTextBox','BusyButton'],
				formWidth: 500,
				hierarchy: [{
					name: "message",
					type: "info",
					content: this.getLocalizedMessage('0000')
				},{
					name: "email",
					value: "",
					type: "EmailTextBox",
					label: $c.getLocalizedMessage('10035'),
					required: true
				},{
					name: "gobutton",
					type: "BusyButton",
					label: this.getLocalizedMessage('0001'),
					onClick: function() {
						myself.recover();
					}
				}],
				attachNode: applicationSpace.containerNode
			}).build();

		};
			
		this.recover = function() {
			if (!myself.recoverform.validate()) {
				myself.recoverform.fields.message.changeType('error');
				myself.recoverform.fields.message.changeContent($c.getLocalizedMessage('10028'));
				setTimeout(myself.recoverform.fields.gobuttom.cancel,100);
			}
			else {
				$c.Kernel.newCall(myself.recoverCallback,{
					application: "regrecover",
					method: "send_new_email",
					preventCache: true,
					content: myself.recoverform.get('value')
				});
			}
		};

		this.recoverCallback = function(success, result) {
			if (!success) {
				myself.recoverform.fields.message.changeType('error');
				myself.recoverform.fields.message.changeContent($c.getLocalizedError(result.code));
				myself.recoverform.fields.gobutton.cancel();
			}
			else {
				$c.dialog.info(myself.getLocalizedMessage('0002'));
				myself.stop();
			}
		};

	}
	
);