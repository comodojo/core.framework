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
$d.require("dijit.tree.ObjectStoreModel");
$d.require("dijit.Menu");
$d.require("dijit.MenuItem");
$d.require("dijit.form.ValidationTextBox");
$d.require("dijit.form.Button");
$d.require("comodojo.Layout");
$d.require('comodojo.Form');
$d.require("gridx.modules.SingleSort");
//$d.require("gridx.modules.Pagination");
//$d.require("gridx.modules.pagination.PaginationBar");
//$d.require("gridx.modules.Filter");
//$d.require("gridx.modules.filter.FilterBar");

$c.App.load("usersmanager",

	function(pid, applicationSpace, status){
	
		//dojo.mixin(this, status);
	
		var myself = this;
		
		this.availableRoles = [];

		this.availableRealms;

		this.realmStores = {};

		this.selectedUser = false;

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

				var i=0,o=0;
				for (i in result.roles) {
					result.roles[i].leaf = false;
					result.roles[i].name = result.roles[i].description;
					result.roles[i].role = 'localrootnode';
					myself.lStoreObservable.put(result.roles[i]);
					myself.availableRoles.push(result.roles[i]);
				}
				for (o in result.users) {
					result.users[o].leaf = true;
					result.users[o].role = result.users[o].userRole;
					result.users[o].name = result.users[o].userName;
					result.users[o].id = result.users[o].userName;
					myself.lStoreObservable.put(result.users[o]);
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
						params: {}
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
					myself.deleteUser(targetNode.item.userName);
				}
			});

			this.userDisabledMenu.addChild(this.switchStateDisabledSelector);
			this.userDisabledMenu.addChild(this.deleteUserDisabledSelector);

			this.newUserButton = new dijit.form.Button({
				label: '<img src="'+$c.icons.getIcon('add',16)+'" />&nbsp;'+this.getLocalizedMessage('0004'),
				style: 'float: left;',
				onClick: function() {
					myself.local_user_form();
					myself.updateSaveCronButton.set({
						label: '<img src="'+$c.icons.getIcon('save',16)+'" />&nbsp;'+myself.getLocalizedMessage('0007'),
						onClick: function() { myself.saveUser(); },
						disabled: false
					});
				}
			});
			this.container.main.center.local.local_actions.containerNode.appendChild(this.newUserButton.domNode);

			this.resetPwdButton = new dijit.form.Button({
				label: '<img src="'+$c.icons.getIcon('reload',16)+'" />&nbsp;'+myself.getLocalizedMessage('0006'),
				disabled: true
			});
			this.container.main.center.local.local_actions.containerNode.appendChild(this.resetPwdButton.domNode);

			this.updateSaveButton = new dijit.form.Button({
				label: '<img src="'+$c.icons.getIcon('save',16)+'" />&nbsp;'+myself.getLocalizedMessage('0007'),
				disabled: true
			});
			this.container.main.center.local.local_actions.containerNode.appendChild(this.updateSaveButton.domNode);

		};

		this.layout_realm = function(i, layout) {

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
							{ name: 'User Name', width: '20%'},
							{ name: 'Display Name', width: '30%'},
							{ name: 'email', width: '20%'},
							{ name: 'Description', width: '30%'}
						],
						//sortInitialOrder: { colId: '2', descending: true },
						style: 'padding: 0px; margin: 0px !important;',
						store: this.realmStores[i],
						modules: [
							"gridx/modules/SingleSort"
							//"gridx/modules/Pagination",
							//"gridx/modules/pagination/PaginationBar",
							//"gridx/modules/Filter",
							//"gridx/modules/filter/FilterBar"
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
			
			this.container.main.center['realm_'+o]['realm_'+o+'_search'].containerNode.appendChild(new dijit.form.ValidationTextBox({
				name: 'realm_'+o+'_search_field',
				required: true
			}).domNode);

			this.container.main.center['realm_'+o]['realm_'+o+'_search'].containerNode.appendChild(new dijit.form.Button({
				label: '<img src="'+$c.icons.getIcon('search',16)+'" />&nbsp;'+$c.getLocalizedMessage('10042'),
				disabled: false,
				onClick: function() { myself.realmSearch(o); }
			}).domNode);
		};
		
		this.local_user_form = function(values) {

			this.container.main.center.local.local_properties.destroyDescendants();

			this.localUserForm = new $c.Form({
				modules:['TextBox','ValidationTextBox','GenderSelect','DateTextBox','EmailTextBox','OnOffSelect','Button'],
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
					value: values.gravatar,
					type: "OnOffSelect",
					label: this.getLocalizedMessage('0008'),
				}],
				attachNode: this.container.main.center.local.local_properties.containerNode
			}).build();

		};



		this.realmSearch = function(realm) {
			console.log(realm);
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

		this.deleteUser = function(user) {
			$c.Kernel.newCall(myself.deleteUserCallback,{
				application: "usersmanager",
				method: "deleteUser",
				content: {
					userName: user
				}
			});
		};

		this.deleteUserCallback = function (success, result) {
			if (success) {
				myself.lStoreObservable.remove(myself.selectedUser);
			}
			else {
				$c.Error.modal(result.code, result.name);
			}
		};

		this.

	}
	
);
