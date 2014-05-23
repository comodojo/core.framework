/**
 * Add, remove, edit users
 *
 * @package		Comodojo Core Applications
 * @author		comodojo.org
 * @copyright	__COPYRIGHT__ comodojo.org (info@comodojo.org)
 * @version		__CURRENT_VERSION__
 * @license		GPL Version 3
 */

$c.App.loadCss('usersmanager');

$d.require("dojo.store.Memory");
$d.require("dojo.store.Observable");
$d.require("dojo.date.locale");
$d.require("dijit.tree.ObjectStoreModel");
$d.require("dijit.Menu");
$d.require("dijit.MenuItem");
$d.require("dijit.form.ValidationTextBox");
$d.require("dijit.form.Button");
$d.require("comodojo.Layout");
$d.require('comodojo.Form');
$d.require("gridx.modules.SingleSort");
$d.require("gridx.modules.Pagination");
$d.require("gridx.modules.pagination.PaginationBar");
$d.require("gridx.modules.RowHeader");
$d.require("gridx.modules.select.Row");
$d.require("gridx.modules.IndirectSelect");

$c.App.load("usersmanager",

	function(pid, applicationSpace, status){
	
		//dojo.mixin(this, status);
	
		var myself = this;
		
		this.availableRoles = [];

		this.availableRealmsOptions = [];

		this.availableRealms;

		this.realmStores = {};

		this.selectedUser = false;
		this.selectedRole = false;
		this.updatedRole = false;

		this.selectedRealm = false;

		this.localUserForm = false;

		this.init = function(){

			this.lStore = new dojo.store.Memory({
				data: [
					{ id: 'localrootnode', name: this.getLocalizedMessage('0000'), leaf: false}
				],
				getChildren: function(object){
					return this.query({role: object.id});
				}
			});

			this.lStoreObservable = new dojo.store.Observable(this.lStore);

			$c.Kernel.newCall(myself.initCallback,{
				application: "usersmanager",
				method: "getUsersRolesRealms"
			});

		};

		this.initCallback = function(success, result) {

			if (success) {

				var i=0,o=0,n=0;
				for (i in result.roles) {
					result.roles[i].leaf = false;
					result.roles[i].name = result.roles[i].description;
					result.roles[i].role = 'localrootnode';
					myself.lStoreObservable.put(result.roles[i]);
					myself.availableRoles.push({
						label: result.roles[i].description,
						id: result.roles[i].id
					});
				}
				for (o in result.users) {
					result.users[o].leaf = true;
					result.users[o].role = result.users[o].userRole;
					result.users[o].name = result.users[o].userName;
					result.users[o].id = result.users[o].userName;
					myself.lStoreObservable.put(result.users[o]);
				}
				for (n in result.realms) {
					myself.availableRealmsOptions.push({
						label: n+' ('+result.realms[n].type+')',
						id: n
					});
				}
				myself.availableRealms = result.realms;

				myself.layout();

			}
			else {
				$c.Error.modal(result.code,result.name);
				myself.stop();
			}

		};

		this.layout = function() {

			this.lModel = new dijit.tree.ObjectStoreModel({
				store: this.lStoreObservable,
				query: {id: 'localrootnode'}
			});

			this.lModel.mayHaveChildren = function(item) {
				return item.leaf == false;
			};

			var layout = [{
				type: 'TabContainer',
				name: 'center',
				region: 'center',
				params: {
					//style: 'margin-bottom: 5px;'
				},
				childrens: [{
					type: 'BorderContainer',
					name: 'local',
					params: {
						//design: 'sidebar',
						title: this.getLocalizedMessage('0000'),
						gutters: true
					},
					childrens: [{
						type: 'Tree',
						name: 'local_tree',
						region: 'left',
						params: {
							model: this.lModel,
							style: "width: 200px;",
							splitter: true,
							id: 'local_tree_'+pid
						}
					},{
						type: 'ContentPane',
						name: 'local_properties',
						region: 'center',
						params: {
							style: "overflow-y: scroll;",
						}
					},{
						type: 'ContentPane',
						name: 'local_actions',
						region: 'bottom',
						cssClass: 'layout_action_pane'
					}]
				}]
			}];

			for (var i in this.availableRealms) {
				this.layout_realm(i,layout);
			}

			this.container = new $c.Layout({
				modules: ['Tree','TabContainer','Grid'],
				attachNode: applicationSpace,
				splitter: false,
				gutters: false,
				id: pid,
				hierarchy: layout
			}).build();

			this.container.main.center.local.local_tree.getIconClass = function(item, opened) {
				
				if (!item || this.model.mayHaveChildren(item)) {
					return opened ? "dijitFolderOpened" : "dijitFolderClosed";
				}
				else {
					return item.enabled ? 'usersmanager_user_enabled' : 'usersmanager_user_disabled';
				}

			};

			this.container.main.center.local.local_tree.getLabelClass = function(item, opened) {

				if (!item || this.model.mayHaveChildren(item)) {
					return "";
				}
				else {
					return item.enabled ? 'usersmanager_user_enabled_label' : 'usersmanager_user_disabled_label';
				}

			};

			this.container.main.center.local.local_tree.on('click',function(item){
				if (item.leaf) {
					myself.selectedUser = item.userName;
					myself.selectedRole = item.userRole;
					myself.openUser(item.userName);
				}
			});

			for (var o in this.availableRealms) {
				this.actions_realm(o);
			}

			/****** TREE MENUS ******/

			this.userEnabledMenu = new dijit.Menu({
				id: 'userEnabledMenu'+pid,
				targetNodeIds: ['local_tree_'+pid],
				selector: ".usersmanager_user_enabled_label"
			});

			this.switchStateEnabledSelector = new dijit.MenuItem({
				label: this.getLocalizedMessage('0002'),
				onClick: function(e) {
					var targetNode = dijit.getEnclosingWidget(this.getParent().currentTarget);
					dojo.removeClass(targetNode.iconNode,'usersmanager_user_enabled');
					dojo.removeClass(targetNode.labelNode,'usersmanager_user_enabled_label');
					dojo.addClass(targetNode.iconNode,'usersmanager_user_changing');
					myself.disableUser(targetNode.item.userName);
				}
			});
			this.userEnabledMenu.addChild(this.switchStateEnabledSelector);

			this.userDisabledMenu = new dijit.Menu({
				id: 'userDisabledMenu'+pid,
				targetNodeIds: ['local_tree_'+pid],
				selector: ".usersmanager_user_disabled_label"
			});

			this.switchStateDisabledSelector = new dijit.MenuItem({
				label: this.getLocalizedMessage('0001'),
				onClick: function() {
					var targetNode = dijit.getEnclosingWidget(this.getParent().currentTarget);
					dojo.removeClass(targetNode.iconNode,'usersmanager_user_disabled');
					dojo.removeClass(targetNode.labelNode,'usersmanager_user_disabled_label');
					dojo.addClass(targetNode.iconNode,'usersmanager_user_changing');
					myself.enableUser(targetNode.item.userName);
				}
			});

			this.deleteUserDisabledSelector = new dijit.MenuItem({
				label: this.getLocalizedMessage('0003'),
				onClick: function() {
					var targetNode = dijit.getEnclosingWidget(this.getParent().currentTarget);
					myself.selectedUser = targetNode.item.userName;
					myself.removeUser(targetNode.item.userName, targetNode.item.completeName);
				}
			});

			this.userDisabledMenu.addChild(this.switchStateDisabledSelector);
			this.userDisabledMenu.addChild(this.deleteUserDisabledSelector);

			this.newUserButton = new dijit.form.Button({
				label: '<img src="'+$c.icons.getIcon('add',16)+'" />&nbsp;'+this.getLocalizedMessage('0004'),
				style: 'float: left;',
				onClick: function() {
					myself.newUser();
				}
			});
			this.container.main.center.local.local_actions.containerNode.appendChild(this.newUserButton.domNode);

			this.resetPwdButton = new dijit.form.Button({
				label: '<img src="'+$c.icons.getIcon('reload',16)+'" />&nbsp;'+this.getLocalizedMessage('0006'),
				disabled: true
			});
			this.container.main.center.local.local_actions.containerNode.appendChild(this.resetPwdButton.domNode);

			this.updateSaveButton = new dijit.form.Button({
				label: '<img src="'+$c.icons.getIcon('save',16)+'" />&nbsp;'+this.getLocalizedMessage('0007'),
				disabled: true
			});
			this.container.main.center.local.local_actions.containerNode.appendChild(this.updateSaveButton.domNode);

		};

		this.layout_realm = function(i, layout) {

			if (i == 'local') {
				return;
			}

			this.realmStores[i] = new dojo.store.Memory({
				idProperty:'userName',
				data: {}
			});

			layout[0].childrens.push({
				type: 'BorderContainer',
				name: 'realm_'+i,
				params: {
					title: i+" ("+this.availableRealms[i].type+")",
					gutters: true
				},
				childrens: [{
					type: 'ContentPane',
					name: 'realm_'+i+'_search',
					region: 'top',
					cssClass: 'layout_action_pane'
				},{
					type: 'Grid',
					name: 'realm_'+i+'_grid',
					region: 'center',
					params: {
						title: '',
						structure: [
							{ name: $c.getLocalizedMessage('10009'), field: "userName", width: '20%'},
							{ name: $c.getLocalizedMessage('10037'), field: "completeName", width: '30%'},
							{ name: $c.getLocalizedMessage('10035'), field: "email", width: '20%'},
							{ name: $c.getLocalizedMessage('10048'), field: "description", width: '30%'}
						],
						//sortInitialOrder: { colId: '2', descending: true },
						style: 'padding: 0px; margin: 0px !important;',
						store: this.realmStores[i],
						modules: [
							"gridx/modules/SingleSort",
							"gridx/modules/Pagination",
							"gridx/modules/pagination/PaginationBar",
							'gridx/modules/RowHeader',
							'gridx/modules/select/Row',
							'gridx/modules/IndirectSelect'
						]
					}
				},{
					type: 'ContentPane',
					name: 'realm_'+i+'_actions',
					region: 'bottom',
					cssClass: 'layout_action_pane'
				}]
			});

		};

		this.actions_realm = function(o) {
			
			if (o == 'local') {
				return;
			}

			this.container.main.center['realm_'+o]['realm_'+o+'_search'].searchbox = new dijit.form.ValidationTextBox({
				name: 'realm_'+o+'_search_field',
				required: true
			});
			this.container.main.center['realm_'+o]['realm_'+o+'_search'].containerNode.appendChild(this.container.main.center['realm_'+o]['realm_'+o+'_search'].searchbox.domNode);

			this.container.main.center['realm_'+o]['realm_'+o+'_search'].containerNode.appendChild(new dijit.form.Button({
				label: '<img src="'+$c.icons.getIcon('search',16)+'" />&nbsp;'+$c.getLocalizedMessage('10042'),
				disabled: false,
				onClick: function() { myself.realmSearch(o, myself.container.main.center['realm_'+o]['realm_'+o+'_search'].searchbox.get('value')); }
			}).domNode);

			this.container.main.center['realm_'+o]['realm_'+o+'_actions'].containerNode.appendChild( new dijit.form.Button({
				label: '<img src="'+$c.icons.getIcon('add',16)+'" />&nbsp;'+this.getLocalizedMessage('0015'),
				disabled: false,
				onClick: function() {
					myself.addUserFromRealm(o);
				}
			}).domNode);

		};
		
		this.local_user_form = function(values) {

			this.container.main.center.local.local_properties.destroyDescendants();

			this.localUserForm = new $c.Form({
				modules:['TextBox','ValidationTextBox','GenderSelect','DateTextBox','EmailTextBox','OnOffSelect','Button','Select'],
				formWidth: 'auto',
				//template: "LABEL_ON_INPUT",
				hierarchy:[{
					name: "userName",
					value: !values ? '' : values.userName,
					type: "ValidationTextBox",
					label: $c.getLocalizedMessage('10009'),
					required: true,
					readonly: !values ? false : true
				},{
					name: "userRole",
					value: !values ? 3 : values.userRole,
					type: "Select",
					label: $c.getLocalizedMessage('10046'),
					options: this.availableRoles
				},{
					name: "authentication",
					value: !values ? 'local' : values.authentication,
					type: "Select",
					label: $c.getLocalizedMessage('10047'),
					options: this.availableRealmsOptions
				},{
					name: "completeName",
					value: !values ? '' : values.completeName,
					type: "ValidationTextBox",
					label: $c.getLocalizedMessage('10037'),
					required: true
				},{
					name: "email",
					value: !values ? '' : values.email,
					type: "EmailTextBox",
					label: $c.getLocalizedMessage('10035'),
					required: true
				},{
					name: "birthday",
					value: !values ? '' : values.birthday,
					type: "DateTextBox",
					label: $c.getLocalizedMessage('10038'),
					required: false
				},{
					name: "gender",
					value: !values ? 'M' : values.gender,
					type: "GenderSelect",
					label: $c.getLocalizedMessage('10039')
				},{
					name: "url",
					value: !values ? '' : values.url,
					type: "TextBox",
					label: this.getLocalizedMessage('0009'),
					required: false
				},{
					name: "gravatar",
					value: !values ? 0 : values.gravatar,
					type: "OnOffSelect",
					label: this.getLocalizedMessage('0008'),
				}],
				attachNode: this.container.main.center.local.local_properties.containerNode
			}).build();

		};

		this.realmSearch = function(realm, pattern) {
			
			myself.selectedRealm = realm;

			$c.Kernel.newCall(myself.realmSearchCallback,{
				application: "usersmanager",
				method: "search",
				content: {
					realm: realm,
					pattern: pattern
				}
			});

		};

		this.realmSearchCallback = function(success, result) {
			if (success) {
				
				myself.realmStores[myself.selectedRealm].setData(result);
				myself.container.main.center['realm_'+myself.selectedRealm]['realm_'+myself.selectedRealm+'_grid'].model.clearCache();
				myself.container.main.center['realm_'+myself.selectedRealm]['realm_'+myself.selectedRealm+'_grid'].body.refresh();
				
			}
			else {
				$c.Error.modal(result.code, result.name);
			}
			
		};

		this.newUser = function() {
			myself.local_user_form();
			myself.updateSaveButton.set({
				label: '<img src="'+$c.icons.getIcon('save',16)+'" />&nbsp;'+myself.getLocalizedMessage('0007'),
				onClick: function() { myself.newUserPass(); },
				disabled: false
			});
		};

		this.newUserPass = function() {
			if (!myself.localUserForm.validate()) {
				$c.Error.minimal($c.getLocalizedMessage('10028'));
				return;
			}
			$c.Dialog.password('Password',myself.getLocalizedMessage('0010'),myself.addUser);
		};

		this.addUser = function(pass) {
			var values = myself.localUserForm.get('value');
			values.userPass = pass;
			if (values.birthday) {
				values.birthday = dojo.date.locale.format(values.birthday, {datePattern: "yyyy-MM-dd", selector: "date"});
			}
			
			$c.Kernel.newCall(myself.addUserCallback,{
				application: "usersmanager",
				method: "addUser",
				content: values
			});
		};

		this.addUserCallback = function(success, result) {
			if (success) {
				$c.Dialog.info(myself.getLocalizedMessage('0011'));
				myself.lStoreObservable.put({
					id: result.userName,
					name: result.userName,
					enabled: result.enabled,
					role: result.userRole,
					userName: result.userName,
					completeName: result.completeName,
					userRole: result.userRole,
					leaf: true
				});
				myself.container.main.center.local.local_tree.set('paths', [ [ 'localrootnode', ''+result.userRole, result.userName ] ] );
				myself.openUser(result.userName);
			}
			else {
				$c.Error.modal(result.code, result.name);
			}
		};

		this.disableUser = function(user) {
			$c.Kernel.newCall(myself.disableUserCallback,{
				application: "usersmanager",
				method: "disableUser",
				content: {
					userName: user
				}
			});
		};

		this.disableUserCallback = function (success, result) {
			if (success) {
				var node = myself.container.main.center.local.local_tree.getNodesByItem(result.userName)[0];
				$d.removeClass(node.iconNode,"usersmanager_user_changing");
				$d.addClass(node.iconNode,"usersmanager_user_disabled");
				$d.addClass(node.labelNode,"usersmanager_user_disabled_label");
			}
			else {
				$c.Error.modal(result.code, result.name);
			}
		};

		this.enableUser = function(user) {
			$c.Kernel.newCall(myself.enableUserCallback,{
				application: "usersmanager",
				method: "enableUser",
				content: {
					userName: user
				}
			});
		};

		this.enableUserCallback = function (success, result) {
			if (success) {
				var node = myself.container.main.center.local.local_tree.getNodesByItem(result.userName)[0];
				$d.removeClass(node.iconNode,"usersmanager_user_changing");
				$d.addClass(node.iconNode,"usersmanager_user_enabled");
				$d.addClass(node.labelNode,"usersmanager_user_enabled_label");
			}
			else {
				$c.Error.modal(result.code, result.name);
			}
		};

		this.removeUser = function(user, name) {

			$c.Dialog.warning(myself.getLocalizedMessage('0003'), myself.getLocalizedMutableMessage('0012',[user, name]), myself.deleteUser);
		};

		this.deleteUser = function() {
			$c.Kernel.newCall(myself.deleteUserCallback,{
				application: "usersmanager",
				method: "deleteUser",
				content: {
					userName: myself.selectedUser
				}
			});
		};

		this.deleteUserCallback = function (success, result) {
			if (success) {
				$c.Dialog.info(myself.getLocalizedMessage('0013'));
				myself.lStoreObservable.remove(myself.selectedUser);
				myself.container.main.center.local.local_properties.destroyDescendants();
			}
			else {
				$c.Error.modal(result.code, result.name);
			}
		};

		this.openUser = function(user) {
			$c.Kernel.newCall(myself.openUserCallback,{
				application: "usersmanager",
				method: "getUser",
				content: {
					userName: user
				}
			});
		};

		this.openUserCallback = function (success, result) {
			if (success) {
				myself.local_user_form(result);
				myself.updateSaveButton.set({
					label: '<img src="'+$c.icons.getIcon('save',16)+'" />&nbsp;'+myself.getLocalizedMessage('0005'),
					onClick: function() { myself.editUser(); },
					disabled: false
				});
			}
			else {
				$c.Error.modal(result.code, result.name);
			}
		};

		this.editUser = function() {
			var values = myself.localUserForm.get('value');
			
			if (values.birthday) {
				values.birthday = dojo.date.locale.format(values.birthday, {datePattern: "yyyy-MM-dd", selector: "date"});
			}

			myself.updatedRole = values.userRole;
			
			$c.Kernel.newCall(myself.editUserCallback,{
				application: "usersmanager",
				method: "editUser",
				content: values
			});
		};

		this.editUserCallback = function (success, result) {
			if (success) {
				$c.Dialog.info(myself.getLocalizedMessage('0014'));
				if (myself.updatedRole != myself.selectedRole) {
					var user = myself.lStoreObservable.get(myself.selectedUser);
						user.userRole = myself.updatedRole;
						user.role = myself.updatedRole;
					myself.lStoreObservable.put(user);
					myself.container.main.center.local.local_tree.set('paths', [ [ 'localrootnode', ''+myself.updatedRole, myself.selectedUser ] ] );
					myself.openUser(myself.selectedUser);
					myself.selectedRole = myself.updatedRole;
				}
			}
			else {
				$c.Error.modal(result.code, result.name);
			}
		};
		
		this.addUserFromRealm = function (realm) {
			
			var selection = myself.container.main.center['realm_'+realm]['realm_'+realm+'_grid'].select.row.getSelected();

			if (selection.length == 0) {
				$c.Error.minimal(myself.getLocalizedMessage('0016'));
				return;
			}

			$c.Loader.start();

			var to_add=[], i=0, userData=false;

			for (i in selection) {
				userData = myself.container.main.center['realm_'+realm]['realm_'+realm+'_grid'].row(selection[i]).rawData();
				
				to_add.push({
					userName: userData.userName,
					completeName: userData.completeName,
					email: userData.email,
					authentication: realm,
					userPass: $c.random(10)
				});
			}

			$c.Kernel.newCall(myself.addUserFromRealmCallback,{
				application: "usersmanager",
				method: "addUsers",
				encodeContent: true,
				content: to_add
			});

		};

		this.addUserFromRealmCallback = function (success, result) {

			$c.Loader.stop();

			if (success) {
				
				var table = '<table class="ym-table bordertable narrow"><thead><tr><th>'+$c.getLocalizedMessage('10009')+'</th><th>'+$c.getLocalizedMessage('10048')+'</th></tr></thead><tbody>';
				var elems = '';

				for (var i=0; i<result.length; i++) {
					if (result[i].status == true) {
						elems += '<tr><td>'+result[i].userName+'</td><td><span style="color: green">'+myself.getLocalizedMessage('0018')+'</span></td></tr>';
						myself.lStoreObservable.put({
							id: result[i].userName,
							name: result[i].userName,
							enabled: result[i].enabled,
							role: result[i].userRole,
							userName: result[i].userName,
							completeName: result[i].completeName,
							userRole: result[i].userRole,
							leaf: true
						});
					}
					else {
						elems += '<tr><td>'+result[i].userName+'</td><td><span style="color: red">'+result[i].status+'</span></td></tr>';
					}
				}

				$c.Dialog.info(table+elems+'</tbody></table>',myself.getLocalizedMessage('0017'));
					
			}
			else {
				$c.Error.modal(result.code, result.name);
			}
		};

	}
	
);
