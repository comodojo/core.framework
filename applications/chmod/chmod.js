/**
 * Manage files' acl
 *
 * @package		Comodojo Core Applications
 * @author		comodojo.org
 * @copyright	__COPYRIGHT__ comodojo.org (info@comodojo.org)
 * @version		__CURRENT_VERSION__
 * @license		GPL Version 3
 */

$c.App.loadCss('chmod');

$d.require("dojo.store.Memory");
$d.require("comodojo.Layout");

$d.require("gridx.modules.CellWidget");
$d.require("gridx.modules.Edit");

$d.require("dojox.form.BusyButton");
$d.require("dijit.form.FilteringSelect");
$d.require("dijit.form.Button");

$c.App.load("chmod",

	function(pid, applicationSpace, status){
	
		this.fileName = false;
		this.filePath = false;
		this.allowSelection = true;
		this.allowSet = true;
		
		dojo.mixin(this, status);
	
		this.aclStore = false;
		this.saveButton = false;
		this.currentUser = false;
		
		var myself = this;
		
		this.init = function(){

			this.aclStore = new dojo.store.Memory({
				idProperty:'acl_id',
				data: {}
			});

			this.rolStore = new dojo.store.Memory({
				idProperty:'role',
				data: [{id:1,role:'reader'},{id:2,role:'writer'},{id:3,role:'owner'}]
			});

			this._userStore = $c.Kernel.newDatastore('chmod','listUsers',{identifier: 'userName', label: 'userName'});

			this.container = new $c.Layout({
				modules: ['Grid'],
				attachNode: applicationSpace,
				splitter: false,
				id: pid,
				hierarchy: [{
					type: 'ContentPane',
					name: 'top',
					region: 'top',
					params: {
						style:"height: 80px; overflow: hidden;"
					}
				},{
					type: 'ContentPane',
					name: 'bottom',
					region: 'bottom',
					cssClass: 'layout_action_pane'
				},
				{
					type: 'Grid',
					name: 'aclgrid',
					region: 'center',
					params: {
						store: this.aclStore,
						structure: [
							{ name: '', width: "5%", formatter: function() {return '<img src="'+$c.icons.getIcon('delete',16)+'" style="cursor:pointer;" alt="X" onClick="$c.App.byPid(\''+pid+'\').removeUser()"/>';}},
							{ name: this.getLocalizedMessage('0007'), field: 'userName', width: "55%"},
							{ name: this.getLocalizedMessage('0008'), field: 'role', width: "40%", editable: this.allowSet,
								editor: "dijit/form/Select",
								editorArgs: {
									props: 'store: $c.App.byPid("'+pid+'").rolStore, labelAttr: "role"'
								},
								style:'cursor:pointer;'
							}	
						],
						modules: [
							"gridx/modules/CellWidget",
							"gridx/modules/Edit"
						],
						style: 'padding: 0px; margin: 0px !important;',
						selectionMode: "single",
						cacheClass: 'sync',
						editLazySave: false
					}
				}]
			}).build();

			this.container.main.aclgrid.connect(this.container.main.aclgrid, "onCellMouseOver", function(evt){
				myself.currentUser = evt.rowId;
			});

			/****** BOTTOM ******/
			this.container.main.bottom.containerNode.appendChild(new dijit.form.Button({
				label: '<img src="'+$c.icons.getIcon('close',16)+'" alt="Close" />&nbsp;'+$c.getLocalizedMessage('10011'),
				onClick: function() {
					myself.stop();
				}
			}).domNode);
			
			this.saveButton = new dojox.form.BusyButton({
				label: '<img src="'+$c.icons.getIcon('save',16)+'" alt="Save" />&nbsp;'+this.getLocalizedMessage('0004'),
				onClick: function() {
					myself.setResourceAcl();
				},
				disabled: 'disabled'
			});
			this.container.main.bottom.containerNode.appendChild(this.saveButton.domNode);

			/****** UP ******/
			this.resourceName = $d.create('div',{
				className: 'chmod_resource_text',
				innerHTML: this.getLocalizedMessage('0003')
			});
			this.container.main.top.containerNode.appendChild(this.resourceName);
			
			this.availableUsersList = new dijit.form.FilteringSelect({
				autoComplete:true,
				store: this._userStore,
				searchAttr:"userName",
				disabled: 'disabled'
			});
			this.container.main.top.containerNode.appendChild(this.availableUsersList.domNode);
			
			this.addButton = new dijit.form.Button({
				label: '<img src="'+$c.icons.getIcon('add',16)+'" alt="Add" />&nbsp;'+this.getLocalizedMessage('0002'),
				onClick: function() {
					myself.addUser();
				},
				style: 'margin-left: 5px;',
				disabled: 'disabled'
			});
			this.container.main.top.containerNode.appendChild(this.addButton.domNode);
			
			if (this.allowSelection && $c.App.isRegistered('filepicker')) {
				this.container.main.top.containerNode.appendChild(new $j.form.Button({
					label: '<img src="'+$c.icons.getIcon('open',16)+'" alt="Open" />&nbsp;'+this.getLocalizedMessage('0001'),
					onClick: function(){
						$c.App.start('filepicker',{accessLevel:'reader',callback: myself.loadResourceAcl});
					},
					style: 'margin-left: 5px;'
				}).domNode);
			}
	
		};

		this.loadResourceAcl = function(acl) {
			if (acl != false) {
				myself.filePath = $c.Utils.defined(acl.filePath) ? acl.filePath : false;
				myself.fileName = $c.Utils.defined(acl.fileName) ? acl.fileName : false;
			}
			else {
				return;
			}

			$c.Kernel.newCall(myself.loadResourceAclCallback,{
				application: "chmod",
				method: "getResourceAcl",
				content: {
					filePath: myself.filePath,
					fileName: myself.fileName
				}
			});
		};

		this.loadResourceAclCallback = function(success,result) {
			if (success) {
				myself.aclStore.setData(result);
				myself.container.main.aclgrid.model.clearCache();
				myself.container.main.aclgrid.body.refresh();
				myself.resourceName.innerHTML = myself.getLocalizedMutableMessage('0000',[myself.filePath+myself.fileName]);
				myself.enableControls();
			}
			else {
				$c.Error.modal(result.code, result.name);
				myself.stop();
			}
		};

		this.setResourceAcl = function() {
			var currentAcl = myself.container.main.aclgrid.model.store.data;
			var readers = [];
			var writers = [];
			var owners = [];
			var i;
			for (i in currentAcl) {
				if (currentAcl[i] !== null) {
					switch(currentAcl[i].role) {
						case "reader":
							if (!$c.Utils.inArray(currentAcl[i].userName,readers)) { readers.push(currentAcl[i].userName); }
						break;
						case "writer":
							if (!$c.Utils.inArray(currentAcl[i].userName,writers)) { writers.push(currentAcl[i].userName); }
						break;
						case "owner":
							if (!$c.Utils.inArray(currentAcl[i].userName,owners)) { owners.push(currentAcl[i].userName); }
						break;
					}
				}
			}
			$c.Kernel.newCall(myself.setResourceAclCallback,{
				application: "chmod",
				method: "setResourceAcl",
				content: {
					filePath: myself.filePath,
					fileName: myself.fileName,
					readers: $d.toJson(readers),
					writers: $d.toJson(writers),
					owners: $d.toJson(owners)
				}
			});
		};

		this.setResourceAclCallback = function(success, result) {
			if (success) {
				myself.saveButton.set('label',myself.getLocalizedMessage('0005'));
				myself.loadResourceAcl(false);
				setTimeout(function() {
					myself.saveButton.set('label','<img src="'+$c.icons.getIcon('save',16)+'" alt="Save" />&nbsp;'+myself.getLocalizedMessage('0004'));
					myself.saveButton.cancel();
				},3000)
			}
			else {
				$c.Error.modal(result.code, result.name);
				myself.stop();
			}
		};

		this.enableControls = function() {
			if (this.allowSet) {
				myself.saveButton.set('disabled',false);
				myself.availableUsersList.set('disabled',false);
				myself.addButton.set('disabled',false);
			}
		};

		this.disableControls = function() {
			myself.saveButton.set('disabled',true);
		};

		this.addUser = function() {
			if (this.availableUsersList.isValid()) {
				myself.container.main.aclgrid.store.add({
					userName: this.availableUsersList.get('value'),
					role:'reader'
				});
			}
		};

		this.removeUser = function() {
			this.container.main.aclgrid.store.remove(this.currentUser);
		};

	}
	
);
