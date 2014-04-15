/**
 * An app to permit distracted users to recover their password
 *
 * @package		Comodojo Core Applications
 * @author		comodojo.org
 * @copyright	__COPYRIGHT__ comodojo.org (info@comodojo.org)
 * @version		__CURRENT_VERSION__
 * @license		GPL Version 3
 */

$d.require("comodojo.Form");

$c.App.load("pwdrecover",

	function(pid, applicationSpace, status){
	
		//dojo.mixin(this, status);
	
		var myself = this;
		
		this.init = function(){
			
			this.form = new $c.Form({
				modules: ['Button','EmailTextBox'],
				autoFocus: true,
				hierarchy: [{
					name: "message",
					type: "info",
					content: this.getLocalizedMessage('0000')
				},{
	                name: "email",
	                value: "",
	                type: "EmailTextBox",
	                label: "Email"
	            },{
	                name: "go",
	                type: "Button",
	                label: $c.getLocalizedMessage('10019'),
	                onClick: function() {
	                	myself.recover();
	                }
	            }],
				attachNode: applicationSpace.containerNode
			}).build();

		};

		this.recover = function() {
			if (!myself.form.validate()) {
				myself.form.fields.message.changeType('warning');
				myself.form.fields.message.changeContent($c.getLocalizedMessage('10028'));
				return;
			}
			else {
				var values = myself.form.get('value');
				myself.form.fields.message.changeType('info');
				myself.form.fields.message.changeContent($c.getLocalizedMessage('10007'));
				myself.form.fields.go.set('disabled',true);
				$c.Kernel.newCall(myself.recoverCallback,{
					application: "pwdrecover",
					method: "sendEmail",
					preventCache: true,
					content: {
						email: values.email
					}
				});
			}
		};

		this.recoverCallback = function(success, result) {
			if (success) {
				myself.form.fields.message.changeType('success');
				myself.form.fields.message.changeContent(myself.getLocalizedMessage('0002'));
				myself.form.fields.go.set('label',$c.getLocalizedMessage('10011'));
				myself.form.fields.go.set('label',$c.getLocalizedMessage('10011'));
				myself.form.fields.go.set('onClick',function(){
					myself.stop();
				});
			}
			else {
				myself.form.fields.message.changeType('error');
				myself.form.fields.message.changeContent(myself.getLocalizedMessage('0001'));
				myself.form.fields.go.set('disabled',false);
			}
		};
			
	}
	
);
