/**
 * usersubscription.js
 *
 * Sign up as new user
 *
 * @package		Comodojo Core Applications
 * @author		comodojo.org
 * @copyright	__COPYRIGHT__ comodojo.org (info@comodojo.org)
 * @version		__CURRENT_VERSION__
 * @license		GPL Version 3
 */

$d.require("comodojo.Form")

$c.App.load("usersubscription",

	function(pid, applicationSpace, status){
	
		this._registrationAuthorization = false;
		this._registrationMode = 0;
	
		var myself = this;
		
		this.init = function(){
		
			$c.Kernel.newCall(myself.initCallback,{
				application: "usersubscription",
				method: "check_registration_mode",
				content: {}
			});
			
		};
		
		this.initCallback = function(success, result) {
			if (!success) {
				$c.Error.local(applicationSpace.containerNode, result.code, result.name);
			}
			else {
				myself._registrationMode = result.mode;
				myself._registrationAuthorization = result.auth;
				myself.build_subscription(result.mode,result.auth);
			}
		};
		
		this.build_subscription = function(mode, auth) {
			
			var subscription_h;
			if (mode == 0) {
				subscription_h = [{
					name: "warning",
					type: "warning",
					content: this.getLocalizedMessage('0000')
				},{
					name: "Button",
					type: "Button",
					label: $c.getLocalizedMessage('10011'),
					onClick: function() {
						myself.stop();
					}
				}];
			}
			else {
				subscription_h = [{
					name: "message",
					type: "info",
					content: this.getLocalizedMessage('0001')
				},{
					name: "userName",
					value: "",
					type: "ValidationTextBox",
					label: this.getLocalizedMessage('0002'),
					required: true
				}, {
					name: "userPass",
					value: "",
					type: "PasswordTextBox",
					label: this.getLocalizedMessage('0003'),
					required: true
				}, {
					name: "completeName",
					value: "",
					type: "TextBox",
					label: this.getLocalizedMessage('0004'),
					required: false
				}, {
					name: "email",
					value: "",
					type: "EmailTextBox",
					label: this.getLocalizedMessage('0005'),
					required: true
				}, {
					name: "gender",
					value: "M",
					type: "GenderSelect",
					label: this.getLocalizedMessage('0006'),
					required: false
				}, {
					name: "birthday",
					value: "",
					type: "DateTextBox",
					label: this.getLocalizedMessage('0007'),
					required: false
				}, {
					name: "gobutton",
					type: "BusyButton",
					label: this.getLocalizedMessage('0008'),
					onClick: function() {
						myself.register();
					}
				}];
			}

			this.registrationform = new $c.Form({
				modules: ['ValidationTextBox','Button','EmailTextBox','DateTextBox','GenderSelect','BusyButton','PasswordTextBox','TextBox'],
				formWidth: 500,
				hierarchy: subscription_h,
				attachNode: applicationSpace.containerNode
			}).build();

		};
		
		this.register = function() {

			if (!myself.registrationform.validate()) {
				myself.registrationform.fields.message.changeType('error');
				myself.registrationform.fields.message.changeContent($c.getLocalizedMessage('10028'));
				setTimeout(myself.registrationform.fields.gobuttom.cancel,100);
			}
			else {
				$c.Kernel.newCall(myself.registerCallback,{
					application: "usersubscription",
					method: "new_registration",
					preventCache: true,
					content: myself.registrationform.get('value')
				});
			}
			
		};
		
		this.registerCallback = function(success, result) {
			
			if (!success) {
				myself.registrationform.fields.message.changeType('error');
				myself.registrationform.fields.message.changeContent('('+result.code+') '+result.name);
				myself.registrationform.fields.gobutton.cancel();
			}
			else {
				$c.Dialog.info(myself.getLocalizedMessage(myself._registrationAuthorization == 0 ? '0012' : '0013'));
				myself.stop();
			}

		};
		
	}
	
);