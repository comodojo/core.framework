/**
 * Simple login form
 *
 * @package		Comodojo Core Applications
 * @author		comodojo.org
 * @copyright	__COPYRIGHT__ comodojo.org (info@comodojo.org)
 * @version		__CURRENT_VERSION__
 * @license		GPL Version 3
 */

 $c.App.loadCss('simplelogin');
$d.require("comodojo.Form");

$c.App.load("simplelogin",

	function(pid, applicationSpace, status){
	
		//dojo.mixin(this, status);
	
		var myself = this;
		
		this.init = function(){

			applicationSpace.domNode.style.margin = "0 auto";
			
			if (!$c.userRole) {
				this.layoutLogin();
			}
			else {
				this.layoutUser();
			}

		};

		this.layoutLogin = function() {

			this.loginForm = new $c.Form({
				modules: ['BusyButton','PasswordTextBox','ValidationTextBox'],
				formWidth: 400,
				hierarchy:[{
					name: "info",
					type: "info",
					content: this.getLocalizedMessage('0000')
				},{
					name: "userName",
					value: "",
					type: "ValidationTextBox",
					label: this.getLocalizedMessage('0004'),
					required: true
				}, {
					name: "userPass",
					value: "",
					type: "PasswordTextBox",
					label: this.getLocalizedMessage('0005'),
					required: true
				}, {
					name: "login",
					type: "BusyButton",
					label: this.getLocalizedMessage('0006'),
					onClick: function() {
						myself.login();
					}
				},{
					name: "pwd_recover",
					type: "info",
					content: '<a href="javascript:;" onClick="$c.App.start(\'pwdrecover\')">'+this.getLocalizedMessage('0007')+'</a>',
					hidden: $c.App.isRegistered('pwdrecover') ? false : true
				},{
					name: "usr_registration",
					type: "info",
					content: $c.App.isRegistered('regrecover') ? (this.getLocalizedMutableMessage('0008',['<a href="javascript:;" onClick="$c.App.start(\'usersubscription\')">','</a>','<a href="javascript:;" onClick="$c.App.start(\'regrecover\')">','</a>'])) : ('<a href="javascript:;" onClick="$c.App.start(\'usersubscription\')">'+this.getLocalizedMessage('0009')+'</a>'),
					hidden: ($c.App.isRegistered('usersubscription') && comodojoConfig.registrationMode != 0) ? false : true
				}],
				attachNode: applicationSpace.containerNode
			}).build();
			
			this.loginForm.fields.userName.on("keypress", function(key){ if (key.keyCode == '13') {myself.loginForm.fields.userPass.focus();} });
			this.loginForm.fields.userPass.on("keypress", function(key){ if (key.keyCode == '13') {myself.loginForm.fields.login.makeBusy(); myself.loginForm.fields.login.onClick();} });
			
		};

		this.layoutUser = function() {

			$c.Kernel.newCall(myself.layoutUserCallback,{
				application: "simplelogin",
				method: "getInfo",
				preventCache: true
			});

		};

		this.layoutUserCallback = function (success, result) {

			if (!success) {
				$c.Error.local(applicationSpace.containerNode, result.code, result.name);
			}
			else {
				var userContainer = $d.create('div',{style: 'width: 500px; margin:0 auto;'});
				var userInfoContainer = $d.create('div',{className: 'simplelogin_userInfo_container'});
				
				userInfoContainer.appendChild($d.create('div', {
					className: "simplelogin_userInfo_name",
					innerHTML: result.url == null ? result.completeName : ('<a href="'+result.url+'" target="_blank">'+result.completeName+'</a>')
				}));

				userInfoContainer.appendChild($d.create('div', {
					className: "simplelogin_userInfo_avatar",
					style: 'background-image: url('+result.avatar+'); background-repeat: no-repeat; background-position: center center; width: 64px; height: 64px;'
				}));
				
				var userActionContainer = $d.create('div',{className: 'simplelogin_userAction_container'});
				
				if ($c.App.isRegistered('profile_editor')){
					userActionContainer.appendChild($d.create('button',{
						className: 'ym-button ym-edit simplelogin_userAction_button',
						onclick: function() {
							$c.App.start('profile_editor');
						},
						innerHTML: myself.getLocalizedMessage('0014')
					}));
				}
				
				if ($c.App.isRegistered('set_locale')){
					userActionContainer.appendChild($d.create('button',{
						className: 'ym-button ym-like simplelogin_userAction_button',
						onclick: function() {
							$c.App.start('set_locale');
						},
						innerHTML: myself.getLocalizedMessage('0015')
					}));
				}
				
				if ($c.App.isRegistered('chpasswd')){
					userActionContainer.appendChild($d.create('button',{
						className: 'ym-button ym-edit simplelogin_userAction_button',
						onclick: function() {
							$c.App.start('chpasswd');
						},
						innerHTML: myself.getLocalizedMessage('0016')
					}));
				}
				
				userActionContainer.appendChild($d.create('button',{
					className: 'ym-button ym-next simplelogin_userAction_button',
					onclick: function() {
						$c.Session.logout();
					},
					innerHTML: myself.getLocalizedMessage('0017')
				}));
				
				userContainer.appendChild(userInfoContainer);
				userContainer.appendChild(userActionContainer);
				applicationSpace.containerNode.appendChild(userContainer);
				
			}

		};

		this.login = function() {
			if (!myself.loginForm.validate()) {
				myself.loginForm.fields.info.changeType('warning');
				myself.loginForm.fields.info.changeContent($c.getLocalizedMessage('10028'));
				myself.loginForm.fields.login.cancel();
			}
			else {
				myself.loginForm.fields.info.changeType('info');
				myself.loginForm.fields.info.changeContent(myself.getLocalizedMessage('0001'));
				var values = myself.loginForm.get('value');
				$c.Session.login(values.userName, values.userPass, myself.loginCallback);
			}
		};
		
		this.loginCallback = function(success, result) {
			if (success) {
				myself.loginForm.fields.info.changeType('success');
				myself.loginForm.fields.info.changeContent(myself.getLocalizedMessage('0002'));
				myself.loginForm.fields.login.set('label',myself.getLocalizedMessage('0002'));
			}
			else {
				myself.loginForm.fields.info.changeType('error');
				myself.loginForm.fields.info.changeContent(myself.getLocalizedMessage('0003'));
				myself.loginForm.fields.login.cancel();
			}
		};

	}
	
);
