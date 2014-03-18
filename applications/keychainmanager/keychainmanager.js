/**
 * [APP DESCRIPTION]
 *
 * @package		Comodojo Core Applications
 * @author		comodojo.org
 * @copyright		__COPYRIGHT__ comodojo.org (info@comodojo.org)
 * @version		__CURRENT_VERSION__
 * @license		GPL Version 3
 */

$d.require("dojo.store.Memory");
$d.require("dojo.store.Observable");
$d.require("dijit.tree.ObjectStoreModel");
$d.require("comodojo.Layout");

$c.App.load("keychainmanager",

	function(pid, applicationSpace, status){
	
		//dojo.mixin(this, status);

		var myself = this;

		this.availableTypes = [
			{
				label: "Generic",
				id: "GENERIC"
			},{
				label: "Database",
				id: "DATABASE"
			},{
				label: "SSH",
				id: "SSH"
			},{
				label: "RSyslog",
				id: "RSYSLOG"
			},{
				label: "http",
				id: "HTTP"
			},{
				label: "SMTP",
				id: "SMTP"
			},{
				label: "SNMP",
				id: "SNMP"
			},{
				label: "OpenWRT device",
				id: "OPENWRT"
			}
		];

		this.availableModels = [
			{
				label: "MYSQL",
				id: "MYSQL"
			},{
				label: "MYSQLI",
				id: "MYSQLI"
			},{
				label: "MYSQL_PDO",
				id: "MYSQL_PDO"
			},{
				label: "ORACLE_PDO",
				id: "ORACLE_PDO"
			},{
				label: "SQLITE_PDO",
				id: "SQLITE_PDO"
			},{
				label: "INFORMIX_PDO",
				id: "INFORMIX_PDO"
			},{
				label: "DB2",
				id: "DB2"
			},{
				label: "DBLIB_PDO",
				id: "DBLIB_PDO"
			},{
				label: "POSTGRESQL",
				id: "POSTGRESQL"
			}
		];

		this.availableKeychain = [];
		
		this.selectedId = false;
		this.selectedAccount = false;
		this.selectedKeychain = false;

		this.init = function(){

			this.kStore = new dojo.store.Memory({
				data: [
					{ id: 'krootnode', name:'Keychains', leaf: false}
				],
				getChildren: function(object){
					return this.query({keychain: object.id});
				}
			});


			$c.Kernel.newCall(myself.initCallback,{
				application: "keychainmanager",
				method: "get_keychains_and_accounts"
			});
		};
		
		this.initCallback = function(success,result) {
			if (success) {
				var i=0;
				for (i in result) {
					myself.kStore.data.push(result[i]);
				}
				myself.layout();
				myself.kStore.query({type:"keychain"}).forEach(function(value){
					myself.availableKeychain.push({
						label: value.id,
						id: value.id
					})
				});
			}
			else {
				$c.Error.modal(result.code,result.name);
				myself.stop();
			}
		};

		this.layout = function(){

			this.kStoreObservable = new dojo.store.Observable(this.kStore);

			this.kModel = new dijit.tree.ObjectStoreModel({
				store: this.kStoreObservable,
				query: {id: 'krootnode'}
			});

			this.kModel.mayHaveChildren = function(item) {
				//console.log(item);
				return item.leaf == false;
			};

			this.container = new $c.Layout({
				modules: ['Tree'],
				attachNode: applicationSpace,
				splitter: false,
				id: pid,
				width: 500,
				height: 400,
				hierarchy: [{
					type: 'Tree',
					name: 'left',
					region: 'left',
					params: {
						model: this.kModel,
						style: "width: 200px;",
						splitter: true,
					}
				},{
					type: 'ContentPane',
					name: 'center',
					region: 'center',
					params: {
						style: 'overflow: auto; background-position: center center; background-repeat: no-repeat; background-image: url(\''+$c.icons.getIcon('encrypt',64)+'\');'
					}
				},{
					type: 'ContentPane',
					name: 'bottom',
					region: 'bottom',
					cssClass: 'layout_action_pane'
				}]
			}).build();

			//this.container.main.left.getIconClass = function(item,opened){
			//	//return opened ? "dijitFolderOpened" : "dijitFolderClosed";
			//	//return (!item || this.model.mayHaveChildren(item)) ? (opened ? "dijitFolderOpened" : "dijitFolderClosed") : "dijitLeaf"
			//	return myself.kStoreObservable.query(item, 'leaf') ? (opened ? "dijitFolderOpened" : "dijitFolderClosed") : "dijitLeaf";
			//};

			$c.treee = this.container.main.left;

			this.container.main.left.on('click',function(item){
				if (item.leaf) {
					myself.openAccount(item.name, item.keychain);
				}
			});

			this.container.main.bottom.containerNode.appendChild(new dijit.form.Button({
				label: '<img src="'+$c.icons.getIcon('add',16)+'" />&nbsp;'+myself.getLocalizedMessage('0018'),
				onClick: function() {
					myself.newAccount();
				}
			}).domNode);

			this.container.main.bottom.containerNode.appendChild(new dijit.form.Button({
				label: '<img src="'+$c.icons.getIcon('close',16)+'" />&nbsp;'+$c.getLocalizedMessage('10011'),
				onClick: function() {
					myself.stop();
				}
			}).domNode);
		};

		this.openAccount = function(account_name, keychain) {
			$c.Kernel.newCall(myself.openAccountCallback,{
				application: "keychainmanager",
				method: "get_account",
				content: {
					account_name: account_name,
					keychain: keychain
				}
			});
		};

		this.openAccountCallback = function(success, result) {
			if (success) {
				myself.container.main.center.destroyDescendants();
				myself.selectedId = result.id;
				myself.selectedAccount = result.account_name;
				myself.selectedKeychain = result.keychain;
				myself.aForm = new $c.Form({
					modules:['NumberSpinner','TextBox','Textarea','ValidationTextBox','Select','Button'],
					formWidth: 'auto',
					hierarchy:[{
						name: "account_name",
						value: result.account_name,
						type: "ValidationTextBox",
						label: myself.getLocalizedMessage('0000'),
						required: true,
						readonly: 'readOnly',
						hidden: true
					},{
						name: "keychain",
						value: result.keychain,
						type: "ValidationTextBox",
						label: myself.getLocalizedMessage('0009'),
						required: true,
						readonly: 'readOnly',
						hidden: true
					},{
						name: "note_account",
						type: "success",
						content: myself.getLocalizedMutableMessage('0013',[result.account_name, result.keychain])
					},{
						name: "description",
						value: result.description,
						type: "Textarea",
						label: myself.getLocalizedMessage('0001'),
						required: false
					},{
						name: "type",
						value: result.type,
						type: "Select",
						label: myself.getLocalizedMessage('0002'),
						required: true,
						options:myself.availableTypes
					},{
						name: "view_change_password_user",
						type: "Button",
						label: myself.getLocalizedMessage('0012'),
						disabled: !($c.App.isRegistered('userdialog') || $c.App.isRegistered('readyform')),
						hidden: result.keychain == 'SYSTEM',
						onClick: function() {
							$c.App.start('userdialog',{
								message: myself.getLocalizedMessage('0017'),
								showUserName: false,
								showUserPass: true,
								callback: myself.viewChangeUserAccount,
								preventCancel: true
							});
						}
					},{
						name: "view_change_password_system",
						type: "Button",
						label: myself.getLocalizedMessage('0012'),
						disabled: !$c.App.isRegistered('readyform'),
						hidden: result.keychain != 'SYSTEM',
						onClick: function() {
							myself.viewChangeSystemAccount();
						}
					},{
						name: "note_fields",
						type: "info",
						content: myself.getLocalizedMessage('0010')
					},{
						name: "name",
						value: result.name,
						type: "TextBox",
						label: myself.getLocalizedMessage('0003'),
						required: false
					},{
						name: "host",
						value: result.host,
						type: "TextBox",
						label: myself.getLocalizedMessage('0004'),
						required: false
					},{
						name: "port",
						value: result.port,
						type: "NumberSpinner",
						label: myself.getLocalizedMessage('0005'),
						required: true,
						min: 0,
						max: 65535
					},{
						name: "model",
						value: result.model,
						type: "Select",
						label: myself.getLocalizedMessage('0006'),
						required: false,
						options:myself.availableModels
					},{
						name: "prefix",
						value: result.prefix,
						type: "TextBox",
						label: myself.getLocalizedMessage('0007'),
						required: false
					},{
						name: "custom",
						value: result.custom,
						type: "TextBox",
						label: myself.getLocalizedMessage('0008'),
						required: false
					},{
						name: "save_account",
						type: "Button",
						label: myself.getLocalizedMessage('0011'),
						onClick: function() {
							myself.changeAccount();
						}
					},{
						name: "delete_account",
						type: "Button",
						label: myself.getLocalizedMessage('0014'),
						onClick: function() {
							$c.Dialog.warning(myself.getLocalizedMessage('0014'), myself.getLocalizedMutableMessage('0021',[myself.selectedId, myself.selectedAccount, myself.selectedKeychain]), myself.deleteAccount);
						}
					}],
					attachNode: myself.container.main.center.containerNode
				}).build();

			}
			else {
				$c.Error.modal(result.code, result.name);
			}
		};

		this.newAccount = function() {
			myself.container.main.center.destroyDescendants();
			//console.log(myself.availableKeychain);
			//console.log(myself.availableTypes);
			myself.aForm = new $c.Form({
				modules:['NumberSpinner','TextBox','Textarea','ValidationTextBox','Select','Button','PasswordTextBox'],
				formWidth: 'auto',
				hierarchy:[{
					name: "account_name",
					type: "ValidationTextBox",
					label: myself.getLocalizedMessage('0000'),
					required: true,
				},{
					name: "keychain",
					type: "Select",
					label: myself.getLocalizedMessage('0009'),
					required: true,
					value:'',
					options: myself.availableKeychain
				},{
					name: 'keyUser',
					type: "ValidationTextBox",
					label: myself.getLocalizedMessage('0015'),
					required:true
				},{
					name: 'keyPass',
					type: "PasswordTextBox",
					label: myself.getLocalizedMessage('0016'),
					required:true
				},{
					name: "description",
					type: "Textarea",
					label: myself.getLocalizedMessage('0001'),
					required: false
				},{
					name: "type",
					type: "Select",
					label: myself.getLocalizedMessage('0002'),
					required: true,
					value:'',
					options:myself.availableTypes
				},{
					name: "note_fields",
					type: "info",
					content: myself.getLocalizedMessage('0010')
				},{
					name: "name",
					type: "TextBox",
					label: myself.getLocalizedMessage('0003'),
					required: false
				},{
					name: "host",
					type: "TextBox",
					label: myself.getLocalizedMessage('0004'),
					required: false
				},{
					name: "port",
					type: "NumberSpinner",
					label: myself.getLocalizedMessage('0005'),
					required: true,
					value: 0,
					min: 0,
					max: 65535
				},{
					name: "model",
					type: "Select",
					value:'',
					label: myself.getLocalizedMessage('0006'),
					required: false,
					options:myself.availableModels
				},{
					name: "prefix",
					type: "TextBox",
					label: myself.getLocalizedMessage('0007'),
					required: false
				},{
					name: "custom",
					type: "TextBox",
					label: myself.getLocalizedMessage('0008'),
					required: false
				},{
					name: "save_account",
					type: "Button",
					label: myself.getLocalizedMessage('0011'),
					onClick: function() {
						myself.addAccount();
					}
				}],
				attachNode: myself.container.main.center.containerNode
			}).build();
		};

		this.addAccount = function() {
			if (!myself.aForm.validate()) {
				$c.Error.minimal($c.getLocalizedMessage('10028'));
				return;
			}
			else if (myself.aForm.get('value').keychain == 'SYSTEM') {
				$c.Kernel.newCall(myself.addAccountCallback,{
					application: "keychainmanager",
					method: "add_account",
					content: myself.aForm.get('value')
				});
			}
			else {
				$c.App.start('userdialog',{
					message: myself.getLocalizedMessage('0017'),
					showUserName: false,
					showUserPass: true,
					callback: myself.addUserAccount,
					preventCancel: true
				});
			}
		};

		this.addUserAccount = function(params) {
			var values = myself.aForm.get('value');
				values.userPass = params.userPass;
			$c.Kernel.newCall(myself.addAccountCallback,{
				application: "keychainmanager",
				method: "add_account",
				content: values
			});
		};

		this.addAccountCallback = function(success, result) {
			if (success) {
				$c.Dialog.info(myself.getLocalizedMessage('0020'));
				var values = myself.aForm.get('value');
				myself.kStoreObservable.add({
					id: result.id,
					name: result.account_name,
					keychain: result.keychain,
					type: result.type,
					leaf: true
				});
				
				myself.container.main.left.set('paths', [ [ 'krootnode', result.keychain, result.id ] ] );
				myself.openAccount(result.account_name, result.keychain);
				myself.container.main.center.containerNode.scrollTop = 0;
			}
			else {
				$c.Error.modal(result.code, result.name);
			}
		};

		this.viewChangeUserAccount = function(params) {
			$c.Kernel.newCall(myself.viewChangeUserAccountCallback,{
				application: "keychainmanager",
				method: "get_account_keys",
				content: {
					account_name: myself.selectedAccount,
					keychain: myself.selectedKeychain,
					userPass: params.userPass
				}
			});
		};

		this.viewChangeUserAccountCallback = function(success,result) {
			if (success) {
				$c.App.start('readyform',{
					modules: ['ValidationTextBox','PasswordTextBox','Button'],
					callback: myself.changeAccountKeys,
					callbackOnClose: false,
					hierarchy: [{
						name: 'keyUser',
						type: "ValidationTextBox",
						label: myself.getLocalizedMessage('0015'),
						required:true,
						value: result.keyUser
					},{
						name: 'keyPass',
						type: "ValidationTextBox",
						label: myself.getLocalizedMessage('0016'),
						required:true,
						value: result.keyPass
					},{
						name: 'userPass',
						type: "PasswordTextBox",
						label: myself.getLocalizedMessage('0017'),
						required:true
					},{
						name: 'account_name',
						type: "ValidationTextBox",
						label: '',
						required:true,
						value: result.account_name,
						readonly: true,
						hidden: true
					},{
						name: 'keychain',
						type: "ValidationTextBox",
						label: '',
						required:true,
						value: result.keychain,
						readonly: true,
						hidden: true
					}]
				});
			}
			else {
				$c.Error.modal(result.code, result.name);
			}
		};

		this.viewChangeSystemAccount = function() {
			$c.Kernel.newCall(myself.viewChangeSystemAccountCallback,{
				application: "keychainmanager",
				method: "get_account_keys",
				content: {
					account_name: this.selectedAccount,
					keychain: this.selectedKeychain
				}
			});
		};

		this.viewChangeSystemAccountCallback = function(success,result) {
			if (success) {
				$c.App.start('readyform',{
					modules: ['ValidationTextBox','Button'],
					callback: myself.changeAccountKeys,
					callbackOnClose: false,
					hierarchy: [{
						name: 'keyUser',
						type: "ValidationTextBox",
						label: myself.getLocalizedMessage('0015'),
						required:true,
						value: result.keyUser
					},{
						name: 'keyPass',
						type: "ValidationTextBox",
						label: myself.getLocalizedMessage('0016'),
						required:true,
						value: result.keyPass
					},{
						name: 'account_name',
						type: "ValidationTextBox",
						label: '',
						required:true,
						value: result.account_name,
						readonly: true,
						hidden: true
					},{
						name: 'keychain',
						type: "ValidationTextBox",
						label: '',
						required:true,
						value: result.keychain,
						readonly: true,
						hidden: true
					}]
				});
			}
			else {
				$c.Error.modal(result.code, result.name);
			}
		};

		this.changeAccountKeys = function(params) {
			$c.Kernel.newCall(myself.changeAccountKeysCallback,{
				application: "keychainmanager",
				method: "set_account_keys",
				content: params
			});
		};

		this.changeAccountKeysCallback = function(success,result) {
			if (success) {
				$c.Dialog.info(myself.getLocalizedMessage('0019'));
			}
			else {
				$c.Error.modal(result.code, result.name);
			}
		};

		this.changeAccount = function() {
			if (!myself.aForm.validate()) {
				$c.Error.minimal($c.getLocalizedMessage('10028'));
				return;
			}
			else {
				var values = myself.aForm.get('value');
				$c.Kernel.newCall(myself.changeAccountCallback,{
					application: "keychainmanager",
					method: "set_account",
					content: values
				});
			}
		};

		this.changeAccountCallback = function(success,result) {
			if (success) {
				$c.Dialog.info(myself.getLocalizedMessage('0020'));
			}
			else {
				$c.Error.modal(result.code, result.name);
			}
		};

		this.deleteAccount = function() {
			$c.Kernel.newCall(myself.deleteAccountCallback,{
				application: "keychainmanager",
				method: "delete_account",
				content: {
					id: myself.selectedId,
					account_name: myself.selectedAccount,
					keychain: myself.selectedKeychain
				}
			});
		};
		
		this.deleteAccountCallback = function(success, result) {
			if (success) {
				$c.Dialog.info(myself.getLocalizedMessage('0022'));
				myself.container.main.center.destroyDescendants();
				myself.kStoreObservable.remove(myself.selectedId);
			}
			else {
				$c.Error.modal(result.code, result.name);
			}
		};
	}
	
);
