/**
 * Change your comodojo password
 *
 * @package		Comodojo Core Applications
 * @author		comodojo.org
 * @copyright	2012 comodojo.org (info@comodojo.org)
 * @version		__CURRENT_VERSION__
 * @license		GPL Version 3
 */

$d.require("comodojo.Form");

$c.App.load("chpasswd",

	function(pid, applicationSpace, status){
	
		//dojo.mixin(this, status);
	
		var myself = this;
		
		this.init = function(){
			
			this.chpasswdForm = new $c.Form({
				modules: ['PasswordTextBox','Button'],
				formWidth: 500,
				hierarchy:[{
					name: "message",
					type: "info",
					content: this.getLocalizedMessage('0003')
	            },{
					name: "oldUserPass",
					value: "",
					type: "PasswordTextBox",
					label: this.getLocalizedMessage('0000'),
					required: true
	            }, {
					name: "newUserPassOne",
					value: "",
					type: "PasswordTextBox",
					label: this.getLocalizedMessage('0001'),
					required: true
	            }, {
					name: "newUserPassTwo",
					value: "",
					type: "PasswordTextBox",
					label: this.getLocalizedMessage('0002'),
					required: true
	            }, {
					name: "go",
					type: "Button",
					label: this.getLocalizedMessage('0005'),
					onClick: function() {
						myself.tryChpasswd();
					}
	            }],
				attachNode: applicationSpace.containerNode
			}).build();
			
			this.chpasswdForm.fields.oldUserPass.on("keypress", function(key){ if (key.keyCode == '13') {myself.chpasswdForm.fields.newUserPassOne.focus();} });
			this.chpasswdForm.fields.newUserPassOne.on("keypress", function(key){ if (key.keyCode == '13') {myself.chpasswdForm.fields.newUserPassTwo.focus();} });
			this.chpasswdForm.fields.newUserPassTwo.on("keypress", function(key){ if (key.keyCode == '13') {myself.chpasswdForm.fields.go.onClick();} });
		};
		
		this.tryChpasswd = function() {
			var values = myself.chpasswdForm.get('value');
			if (!myself.chpasswdForm.validate()) {
				myself.chpasswdForm.fields.message.changeType('warning');
				myself.chpasswdForm.fields.message.changeContent(myself.getLocalizedMessage('0006'));
				return;
			}
			else if (values.newUserPassOne != values.newUserPassTwo) {
				myself.chpasswdForm.fields.message.changeType('warning');
				myself.chpasswdForm.fields.message.changeContent(myself.getLocalizedMessage('0009'));
				myself.chpasswdForm.fields.newUserPassOne.focus();
			}
			else {
				myself.chpasswdForm.fields.message.changeType('info');
				myself.chpasswdForm.fields.message.changeContent(myself.getLocalizedMessage('0007'));
				myself.chpasswdForm.fields.go.set('disabled',true);
				$c.Kernel.newCall(myself.tryChpasswdCallback,{
					application: "chpasswd",
					method: "change_password",
					preventCache: true,
					content: {
						userPass: values.oldUserPass,
						newUserPass: values.newUserPassTwo
					}
				});
			}
		};
		
		this.tryChpasswdCallback = function(success, result) {
			if (!success) {
				myself.chpasswdForm.fields.message.changeType('error');
				myself.chpasswdForm.fields.message.changeContent('('+result.code+') '+result.name);
				myself.chpasswdForm.fields.go.set('disabled',false);
				myself.chpasswdForm.fields.oldUserPass.focus();
			}
			else {
				myself.chpasswdForm.fields.message.changeType('success');
				myself.chpasswdForm.fields.message.changeContent(myself.getLocalizedMessage('0008'));
				myself.chpasswdForm.fields.go.set('disabled',false);
				myself.chpasswdForm.fields.go.set('innerHTML',$c.getLocalizedMessage('10011'));
				myself.chpasswdForm.fields.go.set('onClick',function() {$c.App.stop(pid);});
			}
		};
			
	}
	
);
