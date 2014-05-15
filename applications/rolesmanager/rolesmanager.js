/**
 * Add, remove, edit roles
 *
 * @package		Comodojo Core Applications
 * @author		comodojo.org
 * @copyright	__COPYRIGHT__ comodojo.org (info@comodojo.org)
 * @version		__CURRENT_VERSION__
 * @license		GPL Version 3
 */

$d.require("dojo.store.Memory");
$d.require("dijit.form.Button");
$d.require("gridx.modules.SingleSort");
$d.require("comodojo.Layout");

$c.App.load("rolesmanager",

	function(pid, applicationSpace, status){
	
		//dojo.mixin(this, status);
	
		var myself = this;
	
		this.selectedRole = false;

		this.init = function(){

			this.rStore = new dojo.store.Memory({
				idProperty:'id',
				data: {}
			});

			$c.Kernel.newCall(myself.initCallback,{
				application: "rolesmanager",
				method: "getRoles"
			});

		};

		this.initCallback = function(success, result) {

			if (success) {
				myself.rStore.setData(result);
				myself.layout();
			}
			else {
				$c.Error.modal(result.code,result.name);
				myself.stop();
			}

		};

		this.layout = function() {

			this.container = new $c.Layout({
				modules: ['Grid'],
				attachNode: applicationSpace,
				splitter: false,
				hierarchy: [{
					type: 'ContentPane',
					name: 'bottom',
					region: 'bottom',
					params: {},
					cssClass: 'layout_action_pane'
				},
				{
					type: 'Grid',
					name: 'rolesgrid',
					region: 'center',
					params: {
						structure: [
							{ name: this.getLocalizedMessage('0000'), field: 'id', width: "10%"},
							{ name: this.getLocalizedMessage('0001'), field: 'reference', width: "20%"},
							{ name: this.getLocalizedMessage('0002'), field: 'description', width: "60%"},
							{ name: '', width: "5%", field: 'id', formatter: function(value) {
									if ($c.Utils.inArray(value.id,[1,2,3])) {
										return '';
									}
									return '<img src="'+$c.icons.getIcon('edit',16)+'" onClick="$c.App.byPid(\''+pid+'\').edit('+value.id+',\''+value.reference+'\',\''+value.description+'\')" />';
								}
							},
							{ name: '', width: "5%", field: 'id', formatter: function(value) {
									if ($c.Utils.inArray(value.id,[1,2,3])) {
										return '';
									}
									return '<img src="'+$c.icons.getIcon('delete',16)+'" onClick="$c.App.byPid(\''+pid+'\').delete('+value.id+',\''+value.description+'\')" />';
								}
							}
						],
						store: this.rStore,
						cacheClass: 'sync',
						modules: [
							"gridx/modules/SingleSort"
						]
					}
				}]
			}).build();

			this.newRoleButton = new dijit.form.Button({
				label: '<img src="'+$c.icons.getIcon('add',16)+'" />&nbsp;'+this.getLocalizedMessage('0003'),
				style: 'float: left;',
				onClick: function() {
					myself.add();
				},
				disabled: !$c.App.isRegistered('readyform')
			});

			this.container.main.bottom.containerNode.appendChild(this.newRoleButton.domNode);

			this.stopAppButton = new dijit.form.Button({
				label: '<img src="'+$c.icons.getIcon('close',16)+'" />&nbsp;'+$c.getLocalizedMessage('10011'),
				style: 'float: right;',
				onClick: function() {
					myself.stop();
				}
			});

			this.container.main.bottom.containerNode.appendChild(this.stopAppButton.domNode);

		};

		this.add = function() {

			$c.App.start('readyform',{
				modules: ['ValidationTextBox','NumberTextBox','Button'],
				callback: myself.addRole,
				callbackOnClose: false,
				hierarchy: [{
					name: 'reference',
					type: "NumberTextBox",
					label: myself.getLocalizedMessage('0004'),
					required:true
				},{
					name: 'description',
					type: "ValidationTextBox",
					label: myself.getLocalizedMessage('0005'),
					required: true
				}]
			},false,false,{type:'modal',width:400,height:false});

		};

		this.addRole = function(values) {
			$c.Kernel.newCall(myself.addRoleCallback,{
				application: "rolesmanager",
				method: "addRole",
				content: values
			});
		};

		this.addRoleCallback = function(success, result) {

			if (success) {
				myself.reloadRoles();
			}
			else {
				$c.Error.modal(result.code, result.name);
			}

		};

		this.edit = function(id, reference, description) {
			
			myself.selectedRole = id;
			$c.App.start('readyform',{
				modules: ['ValidationTextBox','NumberTextBox','Button'],
				callback: myself.editRole,
				callbackOnClose: false,
				hierarchy: [{
					name: 'reference',
					type: "NumberTextBox",
					value: reference,
					label: myself.getLocalizedMessage('0004'),
					required:true
				},{
					name: 'description',
					type: "ValidationTextBox",
					value: description,
					label: myself.getLocalizedMessage('0005'),
					required: true
				}]
			},false,false,{type:'modal',width:400,height:false});

		};

		this.editRole = function(values) {
			$c.Kernel.newCall(myself.editRoleCallback,{
				application: "rolesmanager",
				method: "editRole",
				content: {
					id: myself.selectedRole,
					reference: values.reference,
					description: values.description
				}
			});
		};

		this.editRoleCallback = function(success, result) {

			if (success) {
				myself.reloadRoles();
			}
			else {
				$c.Error.modal(result.code, result.name);
			}

		};

		this.delete = function(id,desc) {
			myself.selectedRole = id;
			$c.Dialog.warning(myself.getLocalizedMessage('0006'), myself.getLocalizedMutableMessage('0007',[desc,id]), myself.deleteRole);
		};

		this.deleteRole = function(values) {
			$c.Kernel.newCall(myself.deleteRoleCallback,{
				application: "rolesmanager",
				method: "deleteRole",
				content: {
					id: myself.selectedRole
				}
			});
		};

		this.deleteRoleCallback = function(success, result) {

			if (success) {
				myself.reloadRoles();
			}
			else {
				$c.Error.modal(result.code, result.name);
			}

		};

		this.reloadRoles = function() {
			$c.Kernel.newCall(myself.reloadRolesCallback,{
				application: "rolesmanager",
				method: "getRoles"
			});
		};

		this.reloadRolesCallback = function(success, result) {
			if (success) {
				myself.rStore.setData(result);
				myself.container.main.rolesgrid.model.clearCache();
				myself.container.main.rolesgrid.body.refresh();
			}
			else {
				$c.Error.modal(result.code, result.name);
			}
		};

	}
	
);
