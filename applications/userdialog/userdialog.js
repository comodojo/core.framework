/**
 * [APP DESCRIPTION]
 *
 * @package		Comodojo Core Applications
 * @author		comodojo.org
 * @copyright	__COPYRIGHT__ comodojo.org (info@comodojo.org)
 * @version		__CURRENT_VERSION__
 * @license		GPL Version 3
 */

$c.loadComponent('form', ['Button','Select','TextBox','PasswordTextBox']);
$c.App.load("userdialog",

	function(pid, applicationSpace, status){
	
		/*
		 * Show a custom message on top
		 */
		this.message = false;
		
		/*
		 * Show username field
		 */
		this.showUserName = true;
		
		/*
		 * Show userpass field
		 */
		this.showUserPass = true;

		/*
		 * Show username field as a selectable list
		 */
		this.userNameList = false;
	
		/*
		 * Callback function.
		 * It will be called with two parameters (username, userpass).
		 */
		this.callback = false;

		/*
		 * Prevent app to send null values for callback
		 */
		this.preventCancel = false;

		
		dojo.mixin(this, status);
	
		this._userNameList = [];
		
		var myself = this;
		
		
		this.init = function(){

			applicationSpace.on('cancel',function(){
				if ($d.isFunction(myself.callback) && !myself.preventCancel) {
					myself.callback();
				};
			});
			if (this.showUserName && this.userNameList) {
				$c.kernel.newCall(myself.userListCallback,{
					application: "userdialog",
					method: "get_users",
					content: {},
					preventCache: true
				});
			}
			else {
				this.buildForm();
			}
			
		};
		
		this.userListCallback = function(success,result) {
			var i = 0;
			if (success) {
				for (i in result) {
					myself._userNameList.push({
						name: '<img src="'+result[i].userImage+'" /> '+result[i].userName,
						value: result[i].userName
					});
					myself.buildForm();
				}
			}
			else {
				myself.stop();
				$c.error.global(result.code,result.name);
			}
		};
		
		this.buildForm = function() {
			
			var h = [];
			
			if (myself.message){
				h.push({
	                "name": "message",
	                "type": "info",
	                "content": myself.message
	            });
			}
			
			if (myself.showUserName) {
				if (myself.userNameList) {
					h.push({
		                name: "userName",
		                value: '',
		                type: "Select",
		                label: myself.getLocalizedMessage('0002'),
		                required: false,
		                options:myself._userNameList
		            });
				}
				else {
					h.push({
		                name: "userName",
		                value: "",
		                type: "TextBox",
		                label: myself.getLocalizedMessage('0000'),
		                required: false
		            });
				}
			}
			if (myself.showUserPass){
				h.push({
	                name: "userPass",
	                value: "",
	                type: "PasswordTextBox",
	                label: myself.getLocalizedMessage('0001'),
	                required: false
	            });
			}
			
			h.push({
                name: "go",
                value: "",
                type: "Button",
                label: $c.getLocalizedMessage('10019'),
                onClick: function() {
                	myself.preventCancel = true;
                	if ($d.isFunction(myself.callback)) {
                		var val = myself.form.get('value');
                		myself.callback(val.userName,val.userPass);	
					};
					myself.stop();
                }
            });
			
			myself.form = new $c.form({
				formWidth: 500,
				hierarchy:h,
				attachNode: applicationSpace.containerNode
			}).build();
			
		};
			
	}
	
);
