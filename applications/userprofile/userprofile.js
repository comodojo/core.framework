/**
 * Change User's personal informations
 *
 * @package		Comodojo Core Applications
 * @author		comodojo.org
 * @copyright		__COPYRIGHT__ comodojo.org (info@comodojo.org)
 * @version		__CURRENT_VERSION__
 * @license		GPL Version 3
 */

$d.require("dojo.date.locale");
$d.require('comodojo.Layout');
$d.require("comodojo.Form");

$c.App.load("userprofile",

	function(pid, applicationSpace, status){
	
		//dojo.mixin(this, status);
	
		var myself = this;
		
		this.init = function(){
			
			this.container = new $c.Layout({
				attachNode: applicationSpace,
				splitter: false,
				gutters: false,
				id: pid,
				hierarchy: [{
					type: 'Content',
					name: 'left',
					region: 'left',
					params: {
						style: "width: 200px;"
					}
				},
				{
					type: 'Content',
					name: 'center',
					region: 'center',
					params: {
						style:"overflow: auto; text-align:center;"
					}
				}]
			}).build();

			$c.Kernel.newCall(myself.initCallback,{
				application: "userprofile",
				method: "getUserInfo"
			});
			
		};

		this.initCallback = function(success,result) {
			if (success) {
				myself.buildUserForm(result);
			}
			else {
				$c.Error.modal(result.code,result.name);
				myself.stop();
			}
		};
		
		this.buildUserForm = function(values) {
			this.profileForm = new $c.Form({
				modules:['TextBox','ValidationTextBox','GenderSelect','DateTextBox','EmailTextBox','OnOffSelect','Button'],
				formWidth: 'auto',
				//template: "LABEL_ON_INPUT",
				hierarchy:[{
				//	name: "userName",
				//	value: values.userName,
				//	type: "ValidationTextBox",
				//	label: '',
				//	required: true,
				//	readonly: true,
				//	hidden: true
				//},{
					name: "completeName",
					value: values.completeName,
					type: "ValidationTextBox",
					label: $c.getLocalizedMessage('10037'),
					required: true
				},{
					name: "email",
					value: values.email,
					type: "EmailTextBox",
					label: $c.getLocalizedMessage('10035'),
					required: true
				},{
					name: "go_pwd",
					type: "Button",
					label: this.getLocalizedMessage('0002'),
					disabled: !$c.App.isRegistered('chpasswd'),
					onClick: function() {
						$c.App.start('chpasswd');
					}
				},{
					name: "birthday",
					value: values.birthday,
					type: "DateTextBox",
					label: $c.getLocalizedMessage('10038'),
					required: false
				},{
					name: "gender",
					value: values.gender,
					type: "GenderSelect",
					label: $c.getLocalizedMessage('10039')
				},{
					name: "url",
					value: values.url,
					type: "TextBox",
					label: this.getLocalizedMessage('0001'),
					required: false
				},{
					name: "gravatar",
					value: values.gravatar,
					type: "OnOffSelect",
					label: this.getLocalizedMessage('0000'),
				},{
					name: "go",
					type: "Button",
					label: $c.getLocalizedMessage('10021'),
					onClick: function() {
						myself.editProfile();
					}
				}],
				attachNode: this.container.main.center.containerNode
			}).build();

			this.userImageContainer = $d.create('div', {
				style: 'background-image: url('+values.userImage+'); background-repeat: no-repeat; background-position: center center; margin: 20px auto; width: 66px; height: 66px; border: 1px solid #444444;'+(values.gravatar == "1" ? '' : 'cursor: pointer;'),
				onclick: function() {
					if (values.gravatar == '0' && $c.App.isRegistered('imagepicker')) {
						$c.App.start('imagepicker',{
							allowMultipleSelection: false,
							callback: myself.updateUserImage
						});
					}
				}
			});

			this.container.main.left.containerNode.appendChild(this.userImageContainer);

			this.container.main.left.containerNode.appendChild($d.create('div', {
				innerHTML: values.userName,
				style: 'font-size: large; font-weight: bold; margin: 30px auto; width: 190px; text-align: center;'
			}));

		};

		this.editProfile = function() {
			if (!this.profileForm.validate()) {
				$c.Error.minimal($c.getLocalizedMessage('10028'));
			}
			else {
				var values = this.profileForm.get('value');
				if (values.birthday) {
					values.birthday = dojo.date.locale.format(values.birthday, {datePattern: "yyyy-MM-dd", selector: "date"});
				}
				$c.Kernel.newCall(myself.editProfileCallback,{
					application: "userprofile",
					method: "setUserInfo",
					content: values
				});
			}
		};

		this.editProfileCallback = function(success, result) {
			if (success) {
				$c.Dialog.info(myself.getLocalizedMessage('0003'));
			}
			else {
				$c.Error.modal(result.code,result.name);
			}
		};

		this.updateUserImage = function(image) {
			$d.style(myself.userImageContainer, "backgroundImage", "url('comodojo/images/medium_loader.gif')");
			$c.Kernel.newCall(myself.updateUserImageCallback,{
				application: "userprofile",
				method: "setUserImage",
				content: {
					image: image
				}
			});
		};

		this.updateUserImageCallback = function(success, result) {
			if (success) {
				$d.style(myself.userImageContainer, "backgroundImage", "url('"+result+"')");
			}
			else {
				$d.style(myself.userImageContainer, "backgroundImage", "url('"+$c.icons.getIcon('warning',64)+"')");
				$c.Error.modal(result.code,result.name);
			}
		};

	}
	
);
