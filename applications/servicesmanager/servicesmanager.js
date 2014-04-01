/**
 * [APP DESCRIPTION]
 *
 * @package		Comodojo Core Applications
 * @author		comodojo.org
 * @copyright		__COPYRIGHT__ comodojo.org (info@comodojo.org)
 * @version		__CURRENT_VERSION__
 * @license		GPL Version 3
 */

$c.App.loadCss('servicesmanager');

$d.require("dojo.on");
$d.require("dojo.store.Memory");
$d.require("dojo.store.Observable");
$d.require("dijit.tree.ObjectStoreModel");
$d.require("dijit.Menu");
$d.require("dijit.MenuItem");
$d.require("comodojo.Layout");
$d.require('comodojo.Form');
$d.require('comodojo.Mirror');

$c.App.load("servicesmanager",

	function(pid, applicationSpace, status){
	
		//dojo.mixin(this, status);

		var myself = this;

		this.availableServices = [];

		this.init = function(){

			this.availableServiceTypes = [
				{
					label: this.getLocalizedMessage('0012'),
					id: "SERVICE"
				},{
					label: this.getLocalizedMessage('0013'),
					id: "APPLICATION"
				},{
					label: this.getLocalizedMessage('0014'),
					id: "ALIAS"
				}
			];

			this.availableHTTPMethods = [
				{
					label: 'GET',
					id: "GET"
				},{
					label: 'PUT',
					id: "PUT"
				},{
					label: 'POST',
					id: "POST"
				},{
					label: 'DELETE',
					id: "DELETE"
				},
			];

			this.availableCachingOptions = [
				{
					label: this.getLocalizedMessage('0015'),
					id: "SERVER"
				},{
					label: this.getLocalizedMessage('0016'),
					id: "CLIENT"
				},{
					label: this.getLocalizedMessage('0017'),
					id: "BOTH"
				},{
					label: this.getLocalizedMessage('0018'),
					id: "NONE"
				},
			];

			this.suggestedContentTypes = [
				{name: "text/plain", id: "text/plain"},
				{name: "application/octet-stream", id: "application/octet-stream"},
				{name: "text/plain", id: "text/plain"},
				{name: "text/csv", id: "text/csv"},
				{name: "application/postscript", id: "application/postscript"},
				{name: "application/x-gtar", id: "application/x-gtar"},
				{name: "application/x-gzip", id: "application/x-gzip"},
				{name: "text/html", id: "text/html"},
				{name: "image/jpeg", id: "image/jpeg"},
				{name: "application/json", id: "application/json"},
				{name: "application/pdf", id: "application/pdf"},
				{name: "text/richtext", id: "text/richtext"},
				{name: "application/x-sh", id: "application/x-sh"},
				{name: "application/x-tar", id: "application/x-tar"},
				{name: "application/x-tcl", id: "application/x-tcl"},
				{name: "application/x-tex", id: "application/x-tex"},
				{name: "application/xml", id: "application/xml"},
				{name: "application/zip", id: "application/zip"}
			];

			this.sStore = new dojo.store.Memory({
				data: [
					{ id: 'srootnode', name:'Type', leaf: false},
					{ id: 'SERVICE', name:'service', type: "srootnode", leaf: false},
					{ id: 'APPLICATION', name:'application', type: "srootnode", leaf: false},
					{ id: 'ALIAS', name:'alias', type: "srootnode", leaf: false}
				],
				getChildren: function(object){
					return this.query({type: object.id});
				}
			});

			$c.Kernel.newCall(myself.initCallback,{
				application: "servicesmanager",
				method: "get_services"
			});

		};
		
		this.initCallback = function(success,result) {
			if (success) {
				var i=0;
				for (i in result) {
					result[i].leaf = true;
					myself.sStore.data.push(result[i]);
					if (result[i]['type'] == 'SERVICE' || result[i]['type'] == 'APPLICATION') {
						myself.availableServices.push({
							label: result[i]['name'],
							id: result[i]['name']
						});
					}
				}
				myself.layout();
			}
			else {
				$c.Error.modal(result.code,result.name);
				myself.stop();
			}
		};

		this.layout = function() {

			this.sStoreObservable = new dojo.store.Observable(this.sStore);

			this.sModel = new dijit.tree.ObjectStoreModel({
				store: this.sStoreObservable,
				query: {id: 'srootnode'}
			});

			this.sModel.mayHaveChildren = function(item) {
				return item.leaf == false;
			};

			this.container = new $c.Layout({
				modules: ['Tree','TabContainer'],
				attachNode: applicationSpace,
				splitter: false,
				id: pid,
				hierarchy: [{
					type: 'Tree',
					name: 'left',
					region: 'left',
					params: {
						model: this.sModel,
						style: "width: 200px;",
						splitter: true,
						id: "main_services_tree_"+pid
					}
				},{
					type: 'TabContainer',
					name: 'center',
					region: 'center',
					params: {},
					childrens: [{
						type: 'ContentPane',
						name: 'service_properties',
						params: {
							title: this.getLocalizedMutableMessage('0027',[this.getLocalizedMessage('0026')]),
							style: 'overflow: scroll; overflow-x: hidden; background-position: center center; background-repeat: no-repeat; background-image: url(\''+$c.icons.getSelfIcon('servicesmanager',64)+'\');'
						}
					},{
						type: 'ContentPane',
						name: 'service_code',
						params: {
							title: this.getLocalizedMutableMessage('0028',[this.getLocalizedMessage('0026')]),
						}
					}]
				},{
					type: 'ContentPane',
					name: 'bottom',
					region: 'bottom',
					cssClass: 'layout_action_pane'
				}]
			}).build();

			this.container.main.left.getIconClass = function(item, opened) {

				if (!item || this.model.mayHaveChildren(item)) {
					return opened ? "dijitFolderOpened" : "dijitFolderClosed";
				}
				else {
					return item.enabled ? 'servicesmanager_service_enabled' : 'servicesmanager_service_disabled';
				}

			};

			this.container.main.left.getLabelClass = function(item, opened) {

				if (!item || this.model.mayHaveChildren(item)) {
					return "";
				}
				else {
					return item.enabled ? 'servicesmanager_service_enabled_label' : 'servicesmanager_service_disabled_label';
				}

			};

			this.container.main.left.on('click',function(item){
				if (item.leaf) {
					myself.openService(item.name);
				}
			});

			this.container.main.center.service_code.on('show',function(item){
				myself.mirror.refresh();
				myself.mirror.focus();
			});

			this.serviceEnabledMenu = new dijit.Menu({
				id: 'servicesEnabledMenu'+pid,
				targetNodeIds: ["main_services_tree_"+pid],
				selector: ".servicesmanager_service_enabled_label"
			});

			this.switchStateEnabledSelector = new dijit.MenuItem({
				label: this.getLocalizedMessage('0021'),
				onClick: function(e) {
					var targetNode = dijit.getEnclosingWidget(this.getParent().currentTarget);
					dojo.removeClass(targetNode.iconNode,'servicesmanager_service_enabled');
					dojo.removeClass(targetNode.labelNode,'servicesmanager_service_enabled_label');
					dojo.addClass(targetNode.iconNode,'servicesmanager_service_changing');
					myself.disableService(targetNode.item.name);
				}
			});
			this.serviceEnabledMenu.addChild(this.switchStateEnabledSelector);

			this.serviceDisabledMenu = new dijit.Menu({
				id: 'servicesDisabledMenu'+pid,
				targetNodeIds: ["main_services_tree_"+pid],
				selector: ".servicesmanager_service_disabled_label"
			});

			this.switchStateDisabledSelector = new dijit.MenuItem({
				label: this.getLocalizedMessage('0022'),
				onClick: function() {
					var targetNode = dijit.getEnclosingWidget(this.getParent().currentTarget);
					dojo.removeClass(targetNode.iconNode,'servicesmanager_service_disabled');
					dojo.removeClass(targetNode.labelNode,'servicesmanager_service_disabled_label');
					dojo.addClass(targetNode.iconNode,'servicesmanager_service_changing');
					myself.enableService(targetNode.item.name);
				}
			});

			this.deleteServiceDisabledSelector = new dijit.MenuItem({
				label: this.getLocalizedMessage('0023'),
				onClick: function() {
					var targetNode = dijit.getEnclosingWidget(this.getParent().currentTarget);
					myself.deleteService(targetNode.item.name);
				}
			});

			this.serviceDisabledMenu.addChild(this.switchStateDisabledSelector);
			this.serviceDisabledMenu.addChild(this.deleteServiceDisabledSelector);

			this.propertiesForm = new $c.Form({
				modules:['NumberSpinner','TextBox','Textarea','ValidationTextBox','Select','MultiSelect','Button','ComboBox'],
				formWidth: 'auto',
				hierarchy:[{
					name: "name",
					value: '',
					type: "ValidationTextBox",
					label: myself.getLocalizedMessage('0000'),
					required: true,
				},{
					name: "type",
					value: 'SERVICE',
					type: "Select",
					label: myself.getLocalizedMessage('0001'),
					required: true,
					options: myself.availableServiceTypes,
					onChange: function(value) {
						if (value=='ALIAS') {
							myself._disableFormPieces(['service_application','service_method']);
							myself._enableFormPieces(['alias_for','required_parameters']);
							myself._disableEditor();
						}
						else if (value == 'APPLICATION') {
							myself._disableFormPieces(['alias_for','required_parameters']);
							myself._enableFormPieces(['service_application','service_method']);
							myself._disableEditor();
						}
						else {
							myself._disableFormPieces(['alias_for','service_application','service_method']);
							myself._enableFormPieces(['required_parameters']);
							myself._enableEditor();
						}
					}
				},{
					name: "alias_for",
					value: '',
					type: "ValidationTextBox",
					label: myself.getLocalizedMessage('0002'),
					required: true,
					disabled:true
				},{
					name: "service_application",
					value: '',
					type: "ValidationTextBox",
					label: myself.getLocalizedMessage('0003'),
					required: true,
					disabled: true
				},{
					name: "service_method",
					value: '',
					type: "ValidationTextBox",
					label: myself.getLocalizedMessage('0004'),
					required: true,
					disabled: true
				},{
					name: "description",
					value: '',
					type: "Textarea",
					label: myself.getLocalizedMessage('0005'),
					required: false
				},{
					name: "cache",
					value: 'NONE',
					type: "Select",
					label: myself.getLocalizedMessage('0006'),
					required: true,
					options: myself.availableCachingOptions,
					onChange: function(value) {
						if (value=='NONE') {
							myself._disableFormPieces(['ttl']);
						}
						else {
							myself._enableFormPieces(['ttl']);
						}
					}
				},{
					name: "ttl",
					value: 0,
					type: "NumberSpinner",
					label: myself.getLocalizedMessage('0007'),
					required: true,
					disabled:true,
					min: 0,
					max: 65535
				},{
					name: "access_control_allow_origin",
					value: '',
					type: "TextBox",
					label: myself.getLocalizedMessage('0008'),
					required: false
				},{
					name: "supported_http_methods",
					value: '',
					type: "MultiSelect",
					label: myself.getLocalizedMessage('0009'),
					required: true,
					options: myself.availableHTTPMethods
				},{
					name: "content_type",
					value: '',
					type: "ComboBox",
					label: myself.getLocalizedMessage('0010'),
					required: false,
					options: myself.suggestedContentTypes
				},{
					name: "required_parameters",
					value: '',
					type: "TextBox",
					label: myself.getLocalizedMessage('0011'),
					required: false
				}],
				attachNode: this.container.main.center.service_properties.containerNode
			}).build();

			this.mirror = comodojo.Mirror.build({
				attachNode: this.container.main.center.service_code.containerNode, 
				lineNumbers: true,
				mode: "php",
				keyMap: "sublime",
				autoCloseBrackets: true,
				matchBrackets: true,
				showCursorWhenSelecting: true,
				theme: "monokai",
				lineWrapping: true,
				autofocus: false,
				id: "servicesmanager_mirror_"+pid,
				addons: [
					"search/searchcursor",
					"search/search",
					"edit/matchbrackets",
					"edit/closebrackets",
					"comment/comment",
					"wrap/hardwrap",
					"fold/foldcode",
					"fold/foldgutter",
					"fold/brace-fold",
					"fold/comment-fold"
				],
				gutters: ["CodeMirror-linenumbers", "CodeMirror-foldgutter"]
			});

			this.mirror.setSize('100%','100%');

			this._disableForm();
			this._disableEditor();

			this.container.main.bottom.containerNode.appendChild(new dijit.form.Button({
				label: '<img src="'+$c.icons.getIcon('add',16)+'" />&nbsp;'+myself.getLocalizedMessage('0020'),
				style: 'float: left;',
				onClick: function() {
					myself.startNew();
				}
			}).domNode);

			this.updateSaveService = new dijit.form.Button({
				label: '<img src="'+$c.icons.getIcon('save',16)+'" />&nbsp;'+$c.getLocalizedMessage('10021'),
				//style: 'float: left;',
				onClick: function() {
					myself.newService();
				},
				disabled: true
			});
			this.container.main.bottom.containerNode.appendChild(this.updateSaveService.domNode);

			this.container.main.bottom.containerNode.appendChild(new dijit.form.Button({
				label: '<img src="'+$c.icons.getIcon('close',16)+'" />&nbsp;'+$c.getLocalizedMessage('10011'),
				onClick: function() {
					myself.stop();
				}
			}).domNode);

		};

		this._enableFormPieces = function(pieces) {
			var i = 0;
			for (i in pieces) {
				myself.propertiesForm.fields[pieces[i]].set('disabled',false);
			}
		};

		this._disableFormPieces = function(pieces) {
			var i = 0;
			for (i in pieces) {
				myself.propertiesForm.fields[pieces[i]].set('disabled',true);
			}
		};

		this._disableEditor = function() {
			myself.mirror.lock(myself.getLocalizedMessage('0019'));
		};

		this._enableEditor = function () {
			myself.mirror.release();
			myself.mirror.refresh();
			dojo.query(".CodeMirror-dialog", this.container.main.center.service_code.containerNode).forEach(function(node) {
				comodojo.Utils.destroyNode(node);
			});
		};

		this._resetEditor = function() {
			myself.mirror.setValue("");
			myself.mirror.clearHistory();
			myself.mirror.clearGutter("CodeMirror-linenumbers");
			myself.mirror.clearGutter("CodeMirror-foldgutter");
			myself.mirror.refresh();
		};

		this._enableForm = function() {
			this.propertiesForm.domNode.style.display = 'block';
		};

		this._disableForm = function() {
			this.propertiesForm.domNode.style.display = 'none';
		};

		this._resetForm = function() {
			this.propertiesForm.reset();
			this.propertiesForm.fields.name.set('readonly',false);
			this.container.main.center.service_properties.set('title',this.getLocalizedMutableMessage('0027',[this.getLocalizedMessage('0026')]));
			this.container.main.center.service_code.set('title',this.getLocalizedMutableMessage('0028',[this.getLocalizedMessage('0026')]));
			this.updateSaveService.set({
				onClick: function() {
					myself.newService();
				},
				disabled: false
			});
		};

		this.startNew = function() {
			this._resetForm();
			this._enableForm();
			this._resetEditor();
			this.mirror.setValue("<?php\n\ncomodojo_load_resource('service');\n\nclass [SERVICENAME] extends service {\n\n\tpublic function get($attributes) {\n\t\t\n\t}\n\t\n\tpublic function put($attributes) {\n\t\t\n\t}\n\n\tpublic function post($attributes) {\n\t\t\n\t}\n\n\tpublic function delete($attributes) {\n\t\t\n\t}\n\n}\n\n?>");
			this._enableEditor();
		};

		this.startEditing = function(properties, file) {
			this.container.main.center.service_properties.set('title',this.getLocalizedMutableMessage('0027',[properties.name]));
			this.container.main.center.service_code.set('title',this.getLocalizedMutableMessage('0028',[properties.name]));
			this._resetForm();
			this.updateSaveService.set({
				onClick: function() {
					myself.editService();
				},
				disabled: false
			});
			this.propertiesForm.set('value',properties);
			this.propertiesForm.fields.name.set('readonly',true);
			this._enableForm();
			if (properties.type == "SERVICE") {
				this._resetEditor();
				this.mirror.setValue(file);
				this._enableEditor();
			}
			else {
				this._resetEditor();
				this._disableEditor();
			}
		};

		this.openService = function (service) {
			$c.Kernel.newCall(myself.openServiceCallback,{
				application: "servicesmanager",
				method: "get_service",
				content: {
					name: service
				}
			});
		};

		this.openServiceCallback = function (success, result) {
			if (success) {
				result.properties_file.supported_http_methods = result.properties_file.supported_http_methods.split(',');
				myself.startEditing(result.properties_file,result.service_file);
			}
			else {
				$c.Error.modal(result.code, result.name);
			}
		};

		this.enableService = function(service) {
			$c.Kernel.newCall(myself.enableServiceCallback,{
				application: "servicesmanager",
				method: "enable_service",
				content: {
					name: service
				}
			});
		};

		this.enableServiceCallback = function (success, result) {
			if (success) {
				$d.removeClass(myself.container.main.left.getNodesByItem(result.name)[0].iconNode,"servicesmanager_service_changing");
				$d.addClass(myself.container.main.left.getNodesByItem(result.name)[0].iconNode,"servicesmanager_service_enabled");
				$d.addClass(myself.container.main.left.getNodesByItem(result.name)[0].labelNode,"servicesmanager_service_enabled_label");
			}
			else {
				$c.Error.modal(result.code, result.name);
			}
		};

		this.disableService = function(service) {
			$c.Kernel.newCall(myself.disableServiceCallback,{
				application: "servicesmanager",
				method: "disable_service",
				content: {
					name: service
				}
			});
		};

		this.disableServiceCallback = function (success, result) {
			if (success) {
				$d.removeClass(myself.container.main.left.getNodesByItem(result.name)[0].iconNode,"servicesmanager_service_changing");
				$d.addClass(myself.container.main.left.getNodesByItem(result.name)[0].iconNode,"servicesmanager_service_disabled");
				$d.addClass(myself.container.main.left.getNodesByItem(result.name)[0].labelNode,"servicesmanager_service_disabled_label");
			}
			else {
				$c.Error.modal(result.code, result.name);
			}
		};

		this.deleteService = function(service) {
			$c.Kernel.newCall(myself.deleteServiceCallback,{
				application: "servicesmanager",
				method: "delete_service",
				content: {
					name: service
				}
			});
		};

		this.deleteServiceCallback = function (success, result) {
			if (success) {
				myself.sStoreObservable.remove(result);
				if (myself.propertiesForm.get('value')['name'] == result) {
					myself._resetForm();
					myself._disableForm();
					myself._resetEditor();
					myself._disableEditor();
				}
				myself.updateSaveService.set('disabled', true);
			}
			else {
				$c.Error.modal(result.code, result.name);
			}
		};

		this.newService = function() {
			if (!myself.propertiesForm.validate()) {
				$c.Error.minimal($c.getLocalizedMessage('10028'));
				return;
			}
			var values = myself.propertiesForm.get('value');
			var editor = myself.mirror.getValue();
			if (values.type == "SERVICE" && editor == "") {
				$c.Error.minimal(myself.getLocalizedMessage('0025'));
				return;
			}
			values.service_file = editor;
			values.supported_http_methods = values.supported_http_methods.join(",");
			$c.Kernel.newCall(myself.newServiceCallback,{
				application: "servicesmanager",
				method: "new_service",
				content: values
			});
		};

		this.newServiceCallback = function(success, result) {
			if (success) {
				$c.Dialog.info(myself.getLocalizedMessage('0024'));
				myself.sStoreObservable.put({
					id: result.id,
					name: result.name,
					type: result.type,
					enabled: result.enabled,
					leaf: true
				});
				myself.container.main.left.set('paths', [ [ 'srootnode', result.type, result.id ] ] );
				myself.openService(result.name);
			}
			else {
				$c.Error.modal(result.code, result.name);
			}
		};

		this.editService = function() {
			if (!myself.propertiesForm.validate()) {
				$c.Error.minimal($c.getLocalizedMessage('10028'));
				return;
			}
			var values = myself.propertiesForm.get('value');
			var editor = myself.mirror.getValue();
			if (values.type == "SERVICE" && editor == "") {
				$c.Error.minimal(myself.getLocalizedMessage('0025'));
				return;
			}
			values.service_file = editor;
			values.supported_http_methods = values.supported_http_methods.join(",");
			$c.Kernel.newCall(myself.editServiceCallback,{
				application: "servicesmanager",
				method: "edit_service",
				content: values
			});
		};

		this.editServiceCallback = function(success, result) {
			if (success) {
				$c.Dialog.info(myself.getLocalizedMessage('0029'));
				var old_tree_item = myself.container.main.left.getNodesByItem(result.name)[0].item;
				if (old_tree_item.type != result.type) {
					myself.sStoreObservable.remove(old_tree_item.id);
					myself.sStoreObservable.put({
						id: result.id,
						name: result.name,
						type: result.type,
						enabled: result.enabled,
						leaf: true
					});
					myself.container.main.left.set('paths', [ [ 'srootnode', result.type, result.id ] ] );
				}
			}
			else {
				$c.Error.modal(result.code, result.name);
			}
		};

	}
	
);