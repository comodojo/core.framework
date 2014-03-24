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
					id: 0
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
							title: '[SERVICE] properties',
							style: 'overflow: scroll; overflow-x: hidden;'
						}
					},{
						type: 'ContentPane',
						name: 'service_code',
						params: {
							title: '[SERVICE] code',
							onShow: function(event) {
								if ($c.Utils.defined(myself.mirror)) {
									myself.mirror.focus();
								}
							}
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
							myself.disableFormPieces(['application','method']);
							myself.enableFormPieces(['alias_for']);
							myself.disableEditor();
						}
						else if (value == 'APPLICATION') {
							myself.disableFormPieces(['alias_for']);
							myself.enableFormPieces(['application','method']);
							myself.disableEditor();
						}
						else {
							myself.disableFormPieces(['alias_for','application','method']);
							myself.enableEditor();
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
					name: "application",
					value: '',
					type: "ValidationTextBox",
					label: myself.getLocalizedMessage('0003'),
					required: true,
					disabled: true
				},{
					name: "method",
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
					value: '',
					type: "Select",
					label: myself.getLocalizedMessage('0006'),
					required: true,
					options: myself.availableCachingOptions,
					onChange: function(value) {
						if (value==0) {
							myself.disableFormPieces(['ttl']);
						}
						else {
							myself.enableFormPieces(['ttl']);
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
					value: 'text/plain',
					type: "ComboBox",
					label: myself.getLocalizedMessage('0010'),
					required: true,
					options: myself.suggestedContentTypes
				},{
					name: "required_parameters",
					value: '',
					type: "TextBox",
					label: myself.getLocalizedMessage('0011'),
					required: false
				},{
					name: "action_btn",
					type: "Button",
					label: 'go',
					onClick: function() {
						console.log(myself.propertiesForm.validate());
						console.log(myself.propertiesForm.get('value'));
					}
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

			this.mirror.setSize('100%','100%')

		};

		this.enableFormPieces = function(pieces) {
			var i = 0;
			for (i in pieces) {
				myself.propertiesForm.fields[pieces[i]].set('disabled',false);
			}
		};

		this.disableFormPieces = function(pieces) {
			var i = 0;
			for (i in pieces) {
				myself.propertiesForm.fields[pieces[i]].set('disabled',true);
			}
		};

		this.disableEditor = function() {
			myself.mirror.lock(myself.getLocalizedMessage('0019'));
		};

		this.enableEditor = function () {
			myself.mirror.release();
		};

	}
	
);