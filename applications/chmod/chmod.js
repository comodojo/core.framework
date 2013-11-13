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
		
		var myself = this;
		
		this.init = function(){

			this.aclStore = new dojo.store.Memory({
				idProperty:'acl_id',
				data: {}
			});

			this._userStore = $c.Kernel.newDatastore('chmod','list_users',{identifier: 'userName', label: 'userName'});

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
							{ name: '', width: "5%", formatter: function() {return '<img src="'+$c.icons.getIcon('delete',16)+'" style="cursor:pointer;" alt="X"/>';}},
							{ name: this.getLocalizedMessage('0007'), field: 'userName', width: "55%"},
							{ name: this.getLocalizedMessage('0008'), field: 'role', width: "40%", editable: this.allowSet /*type: dojox.grid.cells.Select, editable: this.allowSet, options:["reader","writer","owner"], style:'cursor:pointer;'*/}	
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
				this.filePath = $c.Utils.defined(acl.filePath) ? acl.filePath : false;
				this.fileName = $c.Utils.defined(acl.fileName) ? acl.fileName : false;
			}
			else {
				return;
			}

			//if (!this.filePath && !this.fileName) {
			//	return;
			//}

			$c.Kernel.newCall(myself.loadResourceAclCallback,{
				application: "chmod",
				method: "get_resource_acl",
				content: {
					filePath: this.filePath,
					fileName: this.fileName
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
				method: "set_resource_acl",
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
				myself.saveButton.cancel();
				myself.saveButton.set('label',myself.getLocalizedMessage('0005'));
				myself.loadResourceAcl();
				setTimeout(function() {myself.saveButton.set('label','<img src="'+$c.icons.getIcon('save',16)+'" alt="Save" />&nbsp;'+myself.getLocalizedMessage('0004'));},3000)
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





















/*

		this._loadingStateEngage = function() {
			this.container.main.domNode.style.display = "none";			
			this.messager.innerHTML = '<p class="chmod_loadingState_image"><img src="comodojo/images/bar_loader.gif" alt="'+$c.getLocalizedMessage('10007')+'"/></p><p class="chmod_loadingState_text">'+$c.getLocalizedMessage('10007')+'</p>';
			this.messager.style.display = "block";
		};
		
		this._throwResult = function(success, result) {
			this.container.main.domNode.style.display = "none";			
			this.messager.innerHTML = '<p class="chmod_loadingState_image"><img src="'+$c.icons.getIcon(success ? 'apply' : 'cancel',32)+'" /></p><p class="chmod_loadingState_text">'+ (success ? (this.getLocalizedMessage('0005')) : (this.getLocalizedMessage('0006')+'<br /><br />'+result) )+'</p>';
			this.messager.style.display = "block";
			if (success) {
				setTimeout(function(){myself._returnToMain();},2000);
			}
		};
		
		this._returnToMain = function() {
			this.container.main.domNode.style.display = "block";
			this.messager.style.display = "none";
		};
		
		this._getResourceAcl = function(filePath, fileName) {
			myself._loadingStateEngage();
			myself.fileName = fileName;
			myself.filePath = filePath;
			myself.resourceName.innerHTML = myself.getLocalizedMutableMessage('0000',[myself.filePath+myself.fileName]);
			$c.kernel.newCall(myself._getResourceAclCallback,{
				application: "chmod",
				method: "get_resource_acl",
				content: {
					filePath: myself.filePath,
					fileName: myself.fileName
				}
			});
		};
		
		this._getResourceAclCallback = function(success, result) {
			if (!success) {
				$c.error.global('10001',result);
				myself.stop();
			}
			else {
				myself._returnToMain();
				myself._setActionState(result);
			}
		};
		
		/*this.setResourceAcl = function() {
			this._loadingStateEngage();
			var currentAcl = this._currentAclStore.fetch().store._arrayOfAllItems;
			var readers = [];
			var writers = [];
			var owners = [];
			var i;
			for (i in currentAcl) {
				if (currentAcl[i] !== null) {
					switch(currentAcl[i].role[0]) {
						case "reader":
							if (!$c.inArray(currentAcl[i].userName[0],readers)) { readers.push(currentAcl[i].userName[0]); }
						break;
						case "writer":
							if (!$c.inArray(currentAcl[i].userName[0],writers)) { writers.push(currentAcl[i].userName[0]); }
						break;
						case "owner":
							if (!$c.inArray(currentAcl[i].userName[0],owners)) { owners.push(currentAcl[i].userName[0]); }
						break;
					}
				}
			}
			$c.kernel.newCall(myself._setResourceAclCallback,{
				application: "chmod",
				method: "set_resource_acl",
				content: {
					filePath: this.filePath,
					fileName: this.fileName,
					readers: $d.toJson(readers),
					writers: $d.toJson(writers),
					owners: $d.toJson(owners)
				}
			});
		};
		
		this._setResourceAclCallback = function(success, result) {
				myself._throwResult(success, result);
		};*/
/*
		this._buildApp = function(resourceSelected) {
			
			this.messager = $d.create("div",{style:"display:none;"},applicationSpace.containerNode);
			
			this._userStore = $c.kernel.newDatastore('chmod','list_users',{identifier: 'userName', label: 'userName'});
			
			this._currentAclStore = new dojo.data.ItemFileWriteStore({
				data: {label:'userName',identifier:'acl_id',items:myself._currentAclStoreData},
				clearOnClose: true
			});
			
			this._currentAclStore.fetch({onComplete: function(item) {
				this._acl_id = items.length+1;
			}});
			
			this.container = new $c.layout({
				attachNode: applicationSpace,
				splitter: false,
				_pid: pid,
				hierarchy: [{
					type: 'ContentPane',
					name: 'top',
					region: 'top',
					params: {
						style:"height: 80px; overflow: hidden;"
					},
					childrens:[]
				},
				{
					type: 'ContentPane',
					name: 'bottom',
					region: 'bottom',
					params: {
						//style:"height: 30px; overflow: hidden; text-align:center;"
					},
					cssClass: 'layout_action_pane',
					childrens:[]
				},
				{
					type: 'Grid',
					name: 'aclgrid',
					region: 'center',
					store: this._currentAclStore,
					params: {
						structure: [
							{ name: '', width: "5%", formatter: function() {return '<img src="'+$c.icons.getIcon('delete',16)+'" style="cursor:pointer;" alt="X"/>';}},
							{ name: this.getLocalizedMessage('0007'), field: 'userName', width: "55%"},
							{ name: this.getLocalizedMessage('0008'), field: 'role', width: "40%", type: dojox.grid.cells.Select, editable: this.allowSet, options:["reader","writer","owner"], style:'cursor:pointer;'}	
						],
						style: 'padding: 0px; margin: 0px !important;',
						selectionMode: "single"
					}
				}]
			}).build();
			
			this.resourceName = $d.create('div',{className: 'chmod_resource_text', innerHTML: resourceSelected ? this.getLocalizedMutableMessage('0000',[myself.filePath+myself.fileName]) : this.getLocalizedMessage('0003')})
			this.container.main.top.containerNode.appendChild(this.resourceName);
			//myself.resourceName.innerHTML = myself.getLocalizedMutableMessage('0000',[myself.filePath+myself.fileName]);
			
			this._availableUsersList = new dijit.form.FilteringSelect({
				autoComplete:true,
				store: this._userStore,
				searchAttr:"userName",
				disabled: !(this.allowSet && resourceSelected) ? 'disabled' : false
			});
			this.container.main.top.containerNode.appendChild(this._availableUsersList.domNode);
			
			this.container.main.aclgrid.onCellClick = function(e) {
				myself._removeGrant(e);
			};
			
			this._addButton = new dijit.form.Button({
				label: '<img src="'+$c.icons.getIcon('add',16)+'" alt="Add" />&nbsp;'+this.getLocalizedMessage('0002'),
				onClick: function() {
					myself.addUser();
				},
				style: 'margin-left: 5px;',
				disabled: !(this.allowSet && resourceSelected) ? 'disabled' : false
			});
			this.container.main.top.containerNode.appendChild(this._addButton.domNode);
			
			if (this.allowSelection && $c.app.isRegistered('filepicker')) {
				this.container.main.top.containerNode.appendChild(new $j.form.Button({
					label: '<img src="'+$c.icons.getIcon('open',16)+'" alt="Open" />&nbsp;'+this.getLocalizedMessage('0001'),
					onClick: function(){
						$c.app.start('filepicker',{accessLevel:'reader',callback: myself._getResourceAcl});
					},
					style: 'margin-left: 5px;'
				}).domNode);
			}
			
			this.container.main.bottom.containerNode.appendChild(new dijit.form.Button({
				label: '<img src="'+$c.icons.getIcon('close',16)+'" alt="Close" />&nbsp;'+$c.getLocalizedMessage('10011'),
				onClick: function() {
					myself.stop();
				}
			}).domNode);
			
			this._saveButton = new dijit.form.Button({
				label: '<img src="'+$c.icons.getIcon('save',16)+'" alt="Save" />&nbsp;'+this.getLocalizedMessage('0004'),
				onClick: function() {
					myself.setResourceAcl();
				},
				disabled: !(this.allowSet && resourceSelected) ? 'disabled' : false
			});
			this.container.main.bottom.containerNode.appendChild(this._saveButton.domNode);
			
		};
		
		this._setActionState = function(acl) {
			this._currentAclStore.revert();
			this._currentAclStore.close();
			this._currentAclStoreData = acl;
			this._currentAclStore = new dojo.data.ItemFileWriteStore({
				data: {label:'userName',identifier:'acl_id',items:myself._currentAclStoreData},
				clearOnClose: true
			});
			this._currentAclStore.fetch({onComplete: function(item) {
				this._acl_id = items.length+1;
			}});
			this.container.main.aclgrid.setStore(this._currentAclStore);
			this._availableUsersList.set('disabled',!this.allowSet ? 'disabled' : false);
			this._addButton.set('disabled',!this.allowSet ? 'disabled' : false);
			this._saveButton.set('disabled',!this.allowSet ? 'disabled' : false);
		};
		
		this.addUser = function() {
			if (this._availableUsersList.isValid()) {
				this._currentAclStore.fetch({query:{userName:this._availableUsersList.get('value'),role:'reader'}, onComplete: function(item) {
					if (item.length === 0) {
						myself._currentAclStore.newItem({
							acl_id: myself._acl_id,
							userName: myself._availableUsersList.get('value'),
							role: "reader"
						});
						myself._acl_id++;
					}
				}});
			}
		};
		
		this._removeGrant = function(e) {
			if (e.cell.index === 0) {
				this._currentAclStore.deleteItem(this.container.main.aclgrid.getItem(e.rowIndex));
				this._currentAclStore.save();
			}
		};
		*/
	}
	
);
