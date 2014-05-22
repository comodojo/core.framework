/**
 * Comodojo Control Panel
 *
 * @package		Comodojo Core Applications
 * @author		comodojo.org
 * @copyright	__COPYRIGHT__ comodojo.org (info@comodojo.org)
 * @version		__CURRENT_VERSION__
 * @license		GPL Version 3
 */

$c.App.loadCss('controlpanel');

$d.require("dojo.on");
$d.require("dojo.mouse");
$d.require("dojo.store.Memory");
$d.require("dojo.data.ObjectStore");
$d.require("dojo.parser");
$d.require("dijit.form.TextBox");
$d.require("dijit.form.CheckBox");
$d.require("dijit.form.NumberTextBox");
$d.require("dijit.form.Select");
$d.require("comodojo.Form");
$d.require("comodojo.Layout");
$d.require("gridx.Grid");
$d.require("gridx.core.model.cache.Sync");
$d.require("gridx.modules.RowHeader");
$d.require("gridx.modules.Tree");
$d.require("gridx.modules.select.Row");
$d.require("gridx.modules.IndirectSelect");
$d.require("gridx.modules.CellWidget");
$d.require("gridx.modules.Edit");

$c.App.load("controlpanel",

	function(pid, applicationSpace, status){
	
		this.enableChanges = true;
		
		this.state = false;
	
		dojo.mixin(this, status);
	
		var myself = this;

		this._currentBuilder = false;

		this.availableAppTypes = [
			{
				label: "Windowed",
				id: "windowed"
			},{
				label: "Modal",
				id: "modal"
			},{
				label: "Attached",
				id: "attached"
			},{
				label: "",
				id: ""
			}
		];

		this.init = function(){
		
			if (typeof this.onApplicationStart == 'function') { this.onApplicationStart(this); }
			if (typeof this.onApplicationStop == 'function') { $d.aspect.after(applicationSpace, 'close', myself.onApplicationStop); }
			
			this.container = new $c.Layout({
				attachNode: applicationSpace,
				splitter: false,
				id: pid,
				hierarchy: [{
					type: 'Content',
					name: 'top',
					region: 'top',
					params: {},
					cssClass: 'layout_action_pane'
				},{
					type: 'Content',
					name: 'bottom',
					region: 'bottom',
					params: {},
					cssClass: 'layout_action_pane'
				},{
					type: 'Content',
					name: 'center',
					region: 'center',
					params: {
						style:"overflow: auto;"
					}
				}]
			}).build();
			
			this.container.loadingState = $d.create("div",{innerHTML: '<p class="controlpanel_loadingState_image"><img src="comodojo/images/bar_loader.gif" alt="'+$c.getLocalizedMessage('10007')+'"/></p><p class="controlpanel_loadingState_text">'+$c.getLocalizedMessage('10007')+'</p>'});
			applicationSpace.containerNode.appendChild(this.container.loadingState);
			
			this.topButtonBack = new dijit.form.Button({label: '<img src="'+$c.icons.getIcon('left_arrow',16)+'" />&nbsp;'+this.getLocalizedMessage('0000'), onClick: function() {
				myself.state = false;
				myself.moveTo();
			}});
			$d.addClass(this.topButtonBack.domNode, 'controlpanel_top_buttonBack');
			
			this.topText = $d.create('div',{className: 'controlpanel_top_textContainer'});
			
			this.container.main.top.containerNode.appendChild(this.topButtonBack.domNode);
			this.container.main.top.containerNode.appendChild(this.topText);
			
			this.bottomButton = new dijit.form.Button({
				label: '<img src="'+$c.icons.getIcon('saveall',16)+'" />&nbsp;'+$c.getLocalizedMessage('10019'),
				onClick: function() {
					myself.save();
				}
			});
			$d.addClass(this.topButtonBack.domNode, 'controlpanel_bottom_button');
			
			this.container.main.bottom.containerNode.appendChild(this.bottomButton.domNode);
			
			this.moveTo();
			
		};
		
		this._loadingStateEngage = function() {
			this.container.main.domNode.style.display = "none";
			this.container.loadingState.style.display = "block";
		};
		this._loadingStateRelease = function() {
			this.container.main.domNode.style.display = "block";
			this.container.loadingState.style.display = "none";
		};
		this._setSuccessState = function() {
			myself.container.main.center.containerNode.innerHTML = "";
			myself._loadingStateRelease();
			myself.container.main.center.containerNode.appendChild($d.create('div',{
				className: 'box success',
				innerHTML: myself.getLocalizedMessage('0001')
			}));
			myself._postControl = $d.create('div',{
				className: 'box warning',
				innerHTML: myself.getLocalizedMutableMessage('0002',['<a href="javascript:window.location.reload();">','</a>'])
			});

			dojo.on(myself._postControl, dojo.mouse.enter, function(){comodojo.force_unload = true;});
			dojo.on(myself._postControl, dojo.mouse.leave, function(){comodojo.force_unload = false;});
			
			myself.container.main.center.containerNode.appendChild(myself._postControl);
			myself._buttonsGoesToBack();
		};
		this._setErrorState = function(error) {
			myself.container.main.center.containerNode.innerHTML = "";
			myself._loadingStateRelease();
			$c.Error.local(myself.container.main.center, error.code, error.name);
			myself._buttonsGoesToRestart();
		};
		this._throwSaveNotCompliantWarning = function() {
			$c.Dialog.info($c.getLocalizedMessage('10028'));
		};
		
		this._buttonsGoesToSave = function() {
			myself.bottomButton.set('label', '<img src="'+$c.icons.getIcon('saveall',16)+'" />&nbsp;'+$c.getLocalizedMessage('10021'));
			myself.bottomButton.onClick = function() { myself.save(); };
			myself.topButtonBack.set('disabled',false);
		};
		this._buttonsGoesToBack = function() {
			myself.bottomButton.set('label','<img src="'+$c.icons.getIcon('left_arrow',16)+'" />&nbsp;'+$c.getLocalizedMessage('10022'));
			myself.bottomButton.onClick = function() { myself.moveTo(); };
			myself.topButtonBack.set('disabled',false);
		};
		this._buttonsGoesToRestart = function() {
			myself.bottomButton.set('label','<img src="'+$c.icons.getIcon('reload',16)+'" />&nbsp;'+$c.getLocalizedMessage('10024'));
			myself.bottomButton.onClick = function() { $c.app.restart(pid); };
			myself.topButtonBack.set('disabled',false);
		};
		this._buttonsGoesToMain = function() {
			myself.bottomButton.set('label', '<img src="'+$c.icons.getIcon('close',16)+'" />&nbsp;'+$c.getLocalizedMessage('10011'));
			myself.bottomButton.onClick = function() { myself.stop(); };
			myself.topButtonBack.set('disabled',true);
		};
		
		this.moveTo = function() {
			
			this._loadingStateEngage();
			
			this.container.main.center.set('content','');
			
			var callOptions = {};
			
			switch(this.state) {
				case false:
					callOptions = {application: "controlpanel", method:"getMainView", content: {}};
				break;
				default:
					callOptions = {application: "controlpanel", method:"getState", content: {group:this.state}};
				break;
			}
			
			$c.Kernel.newCall((!this.state ? this._buildMainGrid : this._buildState), callOptions);
			
		};
		
		this._buildState = function(success, result) {
			
			if (success) {
				
				myself._currentBuilder = result.builder;
				myself.topText.innerHTML = myself.getLocalizedMessage(result.label);
				
				switch(myself._currentBuilder) {
					case 'form':
						myself._buildForm(result.includes);
						myself._buttonsGoesToSave();
					break;
					case 'theme':
						myself._buildTheme(result.includes);
						myself._buttonsGoesToSave();
					break;
					case 'meta':
						myself._buildMeta(result.includes);
						myself._buttonsGoesToSave();
					break;
					case 'bootstrap':
						myself._buildBootstrap(result.includes);
						myself._buttonsGoesToSave();
					break;
					case 'ldap':
						myself._buildLdap(result.includes);
						myself._buttonsGoesToSave();
					break;
					case 'rpc':
						myself._buildRpc(result.includes);
						myself._buttonsGoesToSave();
					break;
					default:
						myself._setErrorState('Unknown builder');
						myself._buttonsGoesToRestart();
					break;
				}
			}
			else {
				myself._setErrorState("("+result.code+") "+result.name);
				myself._buttonsGoesToRestart();
			}
			
			myself._loadingStateRelease();
			
		};
		
		this._buildMainGrid = function(success,result) {
			
			if (!success) { myself._setErrorState(result); }
			else {
				var i = 0;
				for (i in result) { myself._buildGridElement(i,result[i].label,result[i].icon); }
				myself._loadingStateRelease();
				myself._buttonsGoesToMain();
			}
			myself.topText.innerHTML = '';
			
		};
		
		this._buildGridElement = function(name, label, icon) {
			myself.container.newGridBox(myself.container.main.center,'gridbox_'+name,myself.getLocalizedMessage(label.toString()),'applications/controlpanel/resources/'+icon);
			myself.container.main.center['gridbox_'+name].on('click',function() {
				myself.state = name;
				myself.moveTo();
			});
		};
		
		this._buildForm = function(components) {
			
			var i = 0;
			for(i in components) {
				components[i].label = this.getLocalizedMessage(components[i].label);
				if (components[i].content) { components[i].content = this.getLocalizedMessage(components[i].content); }
			}
			
			this.form = new $c.Form({
				modules: ['Button','DateTextBox', 'ValidationTextBox',
						'NumberSpinner','NumberTextBox','EmailTextBox',
						'TextBox', 'Select', 'OnOffSelect', 'SmallEditor',
						'Textarea','PasswordTextBox'],
				autoFocus: true,
				hierarchy: components,
				attachNode: this.container.main.center.containerNode
			}).build();
			
		};
		
		this._buildTheme = function(components) {
			
			this._themeImage = $d.create("div",{className: "controlpanel_theme_image"});
			this._themeName = $d.create("div",{className: "controlpanel_theme_name"});
			this._themeCreatedBy = $d.create("div",{className: "controlpanel_theme_createdBy"});
			this._themeVersion = $d.create("div",{className: "controlpanel_theme_version"});
			this._themeFramework = $d.create("div",{className: "controlpanel_theme_framework"});
			this._themeComment = $d.create("div",{className: "controlpanel_theme_comment"});
			
			this.container.main.center.containerNode.appendChild(this._themeName);
			this.container.main.center.containerNode.appendChild(this._themeCreatedBy);
			this.container.main.center.containerNode.appendChild(this._themeVersion);
			this.container.main.center.containerNode.appendChild(this._themeFramework);
			this.container.main.center.containerNode.appendChild(this._themeComment);
			
			this.container.main.center.containerNode.appendChild(this._themeImage);
			
			this.temp = $c.Bus.temp('availableThemes',[]);
			
			var currentTheme = false, i=0, o=0;
			
			for(i in components) {
				if (components[i].name == "SITE_THEME") {
					
					currentTheme = components[i].value;
					
					for (o in components[i].options) {
						this.temp[components[i].options[o].label] = components[i].options[o];
					}
					
					components[i].label = this.getLocalizedMessage(components[i].label);
					
					components[i].onChange = function() {
						myself._themeImage.style.backgroundImage = "url('comodojo/themes/" + myself.temp[this.value].label + "/theme.jpg')";
						myself._themeName.innerHTML = "<strong>"+myself.getLocalizedMessage('the_3')+":</strong> "+ myself.temp[this.value].label;
						myself._themeCreatedBy.innerHTML = "<strong>"+myself.getLocalizedMessage('the_4')+":</strong> "+ myself.temp[this.value].createdBy;
						myself._themeVersion.innerHTML = "<strong>"+myself.getLocalizedMessage('the_5')+":</strong> "+ myself.temp[this.value].version;
						myself._themeFramework.innerHTML = "<strong>"+myself.getLocalizedMessage('the_6')+":</strong> "+ myself.temp[this.value].framework;
						myself._themeComment.innerHTML = "<strong>"+myself.getLocalizedMessage('the_7')+":</strong> "+ myself.temp[this.value].comment;
					};
					
				}
				else {
					components[i].label = this.getLocalizedMessage(components[i].label);
				}
			}
			this._themeImage.style.backgroundImage = "url('comodojo/themes/" + this.temp[currentTheme].label + "/theme.jpg')";
			this._themeName.innerHTML = "<strong>"+this.getLocalizedMessage('the_3')+":</strong> "+ this.temp[currentTheme].label;
			this._themeCreatedBy.innerHTML = "<strong>"+this.getLocalizedMessage('the_4')+":</strong> "+ this.temp[currentTheme].createdBy;
			this._themeVersion.innerHTML = "<strong>"+this.getLocalizedMessage('the_5')+":</strong> "+ this.temp[currentTheme].version;
			this._themeFramework.innerHTML = "<strong>"+this.getLocalizedMessage('the_6')+":</strong> "+ this.temp[currentTheme].framework;
			this._themeComment.innerHTML = "<strong>"+this.getLocalizedMessage('the_7')+":</strong> "+ this.temp[currentTheme].comment;
			
			this.form = new $c.Form({
				modules: ['Select'],
				autoFocus: true,
				hierarchy: components,
				attachNode: this.container.main.center.containerNode
			}).build();
			
		};
		
		this._buildMeta = function(components) {
			
			var hierarchy = [], i = 0, _components = $d.fromJson(components[0].value);
			
			for (i in _components) {
				hierarchy.push({
					type: 'Textarea',
					label: _components[i].name,
					name: _components[i].name,
					required: false,
					onclick: false,
					options: false,
					value: _components[i].content
				});
			}
			
			this.form = new $c.Form({
				modules: ['Textarea'],
				autoFocus: true,
				hierarchy: hierarchy,
				attachNode: this.container.main.center.containerNode
			}).build();
			
		};
		
		this._buildBootstrap = function(components) {
			try {
				components[0].value = $d.fromJson(components[0].value);
			}
			catch(e) {
				components[0].value = {};
			}
			components[0].options.roles.persistent = {id:'persistent',description:'persistent',reference:'persistent'};
			var initialSelection = [];
			var storeElements = {
				identifier: 'id',
				label: 'description',
				items: []
			};
			var i = 0, o = 0, n = 0, available_apps = components[0].options.applications;

			for (i in components[0].options.roles) {
				
				var ins_apps = [];

				var role = components[0].options.roles[i];

				var role_apps = components[0].value[role.id]

				var position = storeElements.items.push({
					id: role.id,
					description: role.description,
					aggregate: 'cnt',
					childs: []
				});

				for (n in role_apps) {

					var current_app;

					if ($d.isObject(role_apps[n])) {

						current_app = {
							description: role_apps[n].name,
							type: $c.Utils.defined(role_apps[n].properties.type) ? role_apps[n].properties.type : '',
							attachNode: $c.Utils.defined(role_apps[n].properties.attachNode) ? role_apps[n].properties.attachNode : '',
							placeAt: $c.Utils.defined(role_apps[n].properties.placeAt) ? role_apps[n].properties.placeAt : '',
							requestSpecialNode: $c.Utils.defined(role_apps[n].properties.requestSpecialNode) ? role_apps[n].properties.requestSpecialNode : '',
							width: $c.Utils.defined(role_apps[n].properties.width) ? role_apps[n].properties.width : '',
							height: $c.Utils.defined(role_apps[n].properties.height) ? role_apps[n].properties.height : '',
							autoStart: $c.Utils.defined(role_apps[n].properties.autoStart) ? role_apps[n].properties.autoStart : ''
						}

					}
					else {

						current_app = {
							description: role_apps[n],
							type: '',
							attachNode: '',
							placeAt: '',
							requestSpecialNode: '',
							width: '',
							height: '',
							autoStart: ''
						}
						
					}

					if ($c.Utils.inArray(current_app.description, available_apps)) {
						initialSelection.push(role.id+'_'+current_app.description);
						storeElements.items[position-1].childs.push({
							id: role.id+'_'+current_app.description,
							reference: false,
							description: current_app.description,
							type: current_app.type,
							attachNode: current_app.attachNode,
							placeAt: current_app.placeAt,
							requestSpecialNode: current_app.requestSpecialNode,
							width: current_app.width,
							height: current_app.height,
							autoStart: current_app.autoStart
						});
					}

					ins_apps.push(current_app.description);

				}

				for (o in available_apps) {

					if ($c.Utils.inArray(available_apps[o], ins_apps)) {
						continue;
					}

					storeElements.items[position-1].childs.push({
						id: role.id+'_'+available_apps[o],
						reference: false,
						description: available_apps[o],
						type: '',
						attachNode: '',
						placeAt: '',
						requestSpecialNode: '',
						width: '',
						height: '',
						autoStart: ''
					});

				}

			}

			var bootstrapLayout = [
				//{ name: "id", field: "id", width: "auto" },
				{ id: "description", name: "description", field: "description", width: "26%" },
				{ id: "type", name: "type", field: "type", width: "15%", alwaysEditing: true, canEdit: function(cell) { return $c.Utils.defined(cell.row.rawData().aggregate) ? false : true; }, editor: "dijit.form.TextBox", },
				{ id: "attachNode", name: "attachNode", field: "attachNode", width: "15%", alwaysEditing: true, canEdit: function(cell) { return $c.Utils.defined(cell.row.rawData().aggregate) ? false : true; }, editor: "dijit.form.TextBox", },
				{ id: "placeAt", name: "placeAt", field: "placeAt", width: "10%",  alwaysEditing: true, canEdit: function(cell) { return $c.Utils.defined(cell.row.rawData().aggregate) ? false : true; }, editor: "dijit.form.TextBox", },
				{ id: "requestSpecialNode", name: "requestSpecialNode", field: "requestSpecialNode", width: "10%",  alwaysEditing: true, canEdit: function(cell) { return $c.Utils.defined(cell.row.rawData().aggregate) ? false : true; }, editor: "dijit.form.TextBox", },
				{ id: "width", name: "width", field: "width", width: "7%",  alwaysEditing: true, canEdit: function(cell) { return $c.Utils.defined(cell.row.rawData().aggregate) ? false : true; }, editor: "dijit.form.TextBox", },
				{ id: "height", name: "height", field: "height", width: "7%",  alwaysEditing: true, canEdit: function(cell) { return $c.Utils.defined(cell.row.rawData().aggregate) ? false : true; }, editor: "dijit.form.TextBox", },
				{ id: "autoStart", name: "autoStart", field: "autoStart", width: "10%",  alwaysEditing: true, canEdit: function(cell) { return $c.Utils.defined(cell.row.rawData().aggregate) ? false : true; }, editor: "dijit.form.TextBox", }
				
			];
			this.bootstrapStore = new dojo.data.ItemFileWriteStore({ data: storeElements });

			this.bootstrapStore.hasChildren = function(id, item){
				return item && myself.bootstrapStore.getValues(item, 'childs').length;
			};

			this.bootstrapStore.getChildren = function(item){
				return myself.bootstrapStore.getValues(item, 'childs');
			};

			this.bootstrapGrid = new gridx.Grid({
				store: this.bootstrapStore,
				cacheClass: 'gridx/core/model/cache/Sync',
				structure: bootstrapLayout,
				modules: [
					'gridx/modules/Tree',
					'gridx/modules/RowHeader',
					'gridx/modules/select/Row',
					'gridx/modules/IndirectSelect',
					"gridx/modules/CellWidget",
					"gridx/modules/Edit"
				]//,
				//selectRowTriggerOnCell: true
			});

			myself._loadingStateRelease();
			this.container.main.center.addChild(this.bootstrapGrid);
			this.bootstrapGrid.startup();

			for (var s in initialSelection) {
				this.bootstrapGrid.select.row.selectById(initialSelection[s]);
			}

			this.container.main.center._layoutChildren();
			this.bootstrapGrid.resize();
		};

		this._buildLdap = function(components) {

			try {
				components[0].value = $d.fromJson(components[0].value);
			}
			catch(e) {
				components[0].value = {};
			}

			dojo.declare('gridx.controlpanel.CustomEditorLdap', [dijit._Widget, dijit._TemplatedMixin, dijit._WidgetsInTemplateMixin], {
				templateString: [
					'<table><tr><td style="width: 50%;">',
						//'<label>ID:</label>',
					'</td><td>',
						'<div data-dojo-type="dijit.form.NumberTextBox" data-dojo-attach-point="id" style="display:none;"></div>',
					'</td></tr><tr style="background: #F5F5F5;"><td style="width: 50%;">',	
						'<label>'+this.getLocalizedMessage("lda_9")+'</label>',
					'</td><td>',
						'<div data-dojo-type="dijit.form.TextBox" data-dojo-attach-point="name"></div>',
					'</td></tr><tr style="background: #FFF;"><td style="width: 50%;">',
						'<label>'+this.getLocalizedMessage("lda_1")+'</label>',
					'</td><td>',
						'<div data-dojo-type="dijit.form.TextBox" data-dojo-attach-point="server"></div>',
					'</td></tr><tr style="background: #F5F5F5;"><td style="width: 50%;">',
						'<label>'+this.getLocalizedMessage("lda_2")+'</label>',
					'</td><td>',
						'<div data-dojo-type="dijit.form.NumberTextBox" data-dojo-attach-point="port"></div>',
					'</td></tr><tr style="background: #FFF;"><td style="width: 50%;">',
						'<label>'+this.getLocalizedMessage("lda_3")+'</label>',
					'</td><td>',
						'<div data-dojo-type="dijit.form.TextBox" data-dojo-attach-point="base"></div>',
					'</td></tr><tr style="background: #F5F5F5;"><td style="width: 50%;">',
						'<label>'+this.getLocalizedMessage("lda_4")+'</label>',
					'</td><td>',
						'<div data-dojo-type="dijit.form.TextBox" data-dojo-attach-point="dn"></div>',
					'</td></tr><tr style="background: #FFF;"><td style="width: 50%;">',
						'<label>'+this.getLocalizedMessage("lda_5")+'</label>',
					'</td><td>',
						'<div data-dojo-type="dijit.form.TextBox" data-dojo-attach-point="searchbase"></div>',
					'</td></tr><tr style="background: #F5F5F5;"><td style="width: 50%;">',
						'<label>'+this.getLocalizedMessage("lda_15")+'</label>',
					'</td><td>',
						'<div data-dojo-type="dijit.form.TextBox" data-dojo-attach-point="searchfields"></div>',
					'</td></tr><tr style="background: #F5F5F5;"><td style="width: 50%;">',
						'<label>'+this.getLocalizedMessage("lda_6")+'</label>',
					'</td><td>',
						'<div data-dojo-type="dijit.form.TextBox" data-dojo-attach-point="listuser"></div>',
					'</td></tr><tr style="background: #FFF;"><td style="width: 50%;">',
						'<label>'+this.getLocalizedMessage("lda_7")+'</label>',
					'</td><td>',
						'<div data-dojo-type="dijit.form.TextBox" data-dojo-attach-point="listpass"></div>',
					'</td></tr><tr style="background: #F5F5F5;"><td style="width: 50%;">',
						'<label>'+this.getLocalizedMessage("lda_8")+'</label>',
					'</td><td>',
						'<div data-dojo-type="dijit.form.Select" data-dojo-attach-point="version"><option value="3">LDAP V3</option><option value="2">LDAP V3</option></div>',
					'</td></tr><tr style="background: #FFF;"><td style="width: 50%;">',
						'<label>'+this.getLocalizedMessage("lda_13")+'</label>',
					'</td><td>',
						'<div data-dojo-type="dijit.form.Select" data-dojo-attach-point="ssl"><option value="0">OFF</option><option value="1">ON</option></div>',
					'</td></tr><tr style="background: #FFF;"><td style="width: 50%;">',
						'<label>'+this.getLocalizedMessage("lda_14")+'</label>',
					'</td><td>',
						'<div data-dojo-type="dijit.form.Select" data-dojo-attach-point="tls"><option value="0">OFF</option><option value="1">ON</option></div>',
					'</td></tr><tr style="background: #FFF;"><td style="width: 50%;">',
						'<label>'+this.getLocalizedMessage("lda_11")+'</label>',
					'</td><td>',
						'<div data-dojo-type="dijit.form.Select" data-dojo-attach-point="autoadd"><option value="0">OFF</option><option value="1">ON</option></div>',
					'</td></tr><tr style="background: #F5F5F5;"><td style="width: 50%;">',
						'<label>'+this.getLocalizedMessage("lda_10")+'</label>',
					'</td><td>',
						'<div data-dojo-type="dijit.form.Select" data-dojo-attach-point="enabled"><option value="0">OFF</option><option value="1">ON</option></div>',
					'</td></tr></table>'
				].join(''),
				_setValueAttr: function(value){
					this.id.set('value', value[0]);
					this.name.set('value', value[1]);
					this.server.set('value', value[2]);
					this.port.set('value', parseInt(value[3], 10));
					this.base.set('value', value[4]);
					this.dn.set('value', value[5]);
					this.searchbase.set('value', value[6]);
					this.searchfields.set('value', value[7]);
					this.listuser.set('value', value[8]);
					this.listpass.set('value', value[9]);
					this.version.set('value', parseInt(value[10], 10));
					this.ssl.set('value', parseInt(value[11], 10));
					this.tls.set('value', parseInt(value[12], 10));
					this.autoadd.set('value', parseInt(value[13], 10));
					this.enabled.set('value', parseInt(value[14], 10));
				},
				_getValueAttr: function(value){
					return [
						this.id.get('value'),
						this.name.get('value'),
						this.server.get('value'),
						this.port.get('value'),
						this.base.get('value'),
						this.dn.get('value'),
						this.searchbase.get('value'),
						this.searchfields.get('value'),
						this.listuser.get('value'),
						this.listpass.get('value'),
						parseInt(this.version.get('value')),
						parseInt(this.ssl.get('value')),
						parseInt(this.tls.get('value')),
						parseInt(this.autoadd.get('value')),
						parseInt(this.enabled.get('value'))
					];
				},
				focus: function(){
					this.name.focus();
				}
			});

			this.ldapStore = new dojo.store.Memory({
				idProperty:'id',
				data: []
			});

			for (var i = 1; i < 10; i++) {
				this.ldapStore.put({id: i,name: '',server: '',port: 389,dcs: '',dns: '',filter: '',listuser: '',listpass: '',cmode: "1", autoadd: "0", enabled: "0"});
			};

			for (var o in components[0].value) {
				this.ldapStore.put(components[0].value[o]);
			}

			var ldapLayout = [
				{ field: "id", name:"ID", width: '20px'},
				{ field: "id", name: "Ldap Servers", editable: true,
					formatter: function(rawData){
						if (!rawData.name || !rawData.server || !rawData.port) {
							return '<span style="color: gray">('+myself.getLocalizedMessage('lda_12')+')</span>';
						}
						else {
							return '<span style="color: '+(rawData.enabled ? 'green' : 'red')+'">'+rawData.name+' ('+ rawData.server+':'+rawData.port + ')</span>';
						}
					},
					editor: 'gridx.controlpanel.CustomEditorLdap',
					editorArgs: {
						useGridData: false,
						toEditor: function(storeData, gridData){
							var values = myself.ldapGrid.model.store.data[storeData-1];
							return [
								values.id,
								values.name,
								values.server,
								parseInt(values.port, 10),
								values.base,
								values.dn,
								values.searchbase,
								values.searchfields,
								values.listuser,
								values.listpass,
								values.version,
								values.ssl,
								values.tls,
								values.autoadd,
								values.enabled
							];
						},
						fromEditor: function(values){
							myself.ldapGrid.model.store.put({
								id: values[0],
								name: values[1],
								server: values[2],
								port: parseInt(values[3], 10),
								base: values[4],
								dn: values[5],
								searchbase: values[6],
								searchfields: values[7],
								listuser: values[8],
								listpass: values[9],
								version: values[10],
								ssl: values[11],
								tls: values[12],
								autoadd: values[13],
								enabled: values[14]
							});
							return values;
						}
					},
					customApplyEdit: function(cell, value){
						return cell.row.setRawData({
							id: value[0],
							name: value[1],
							server: value[2],
							port: parseInt(value[3], 10),
							base: value[4],
							dn: value[5],
							searchbase: value[6],
							searchfields: value[7],
							listuser: value[8],
							listpass: value[9],
							version: value[10],
							ssl: value[11],
							tls: value[12],
							autoadd: value[13],
							enabled: value[14]
						});
					}
				}
			];

			this.ldapGrid = new gridx.Grid({
				store: this.ldapStore,
				cacheClass: 'gridx/core/model/cache/Sync',
				structure: ldapLayout,
				modules: [
					"gridx/modules/CellWidget",
					"gridx/modules/Edit"
				]
			});

			myself._loadingStateRelease();
			this.container.main.center.addChild(this.ldapGrid);
			this.ldapGrid.startup();

			this.container.main.center._layoutChildren();
			this.ldapGrid.resize();
		};

		this._buildRpc = function(components) {

			try {
				components[0].value = $d.fromJson(components[0].value);
			}
			catch(e) {
				components[0].value = {};
			}

			dojo.declare('gridx.controlpanel.CustomEditorRpc', [dijit._Widget, dijit._TemplatedMixin, dijit._WidgetsInTemplateMixin], {
				templateString: [
					'<table><tr><td style="width: 50%;">',
						//'<label>ID:</label>',
					'</td><td>',
						'<div data-dojo-type="dijit.form.NumberTextBox" data-dojo-attach-point="id" style="display:none;"></div>',
					'</td></tr><tr style="background: #F5F5F5;"><td style="width: 50%;">',	
						'<label>'+this.getLocalizedMessage("ext_3")+'</label>',
					'</td><td>',
						'<div data-dojo-type="dijit.form.TextBox" data-dojo-attach-point="name"></div>',
					'</td></tr><tr style="background: #FFF;"><td style="width: 50%;">',
						'<label>'+this.getLocalizedMessage("ext_1")+'</label>',
					'</td><td>',
						'<div data-dojo-type="dijit.form.TextBox" data-dojo-attach-point="server"></div>',
					'</td></tr><tr style="background: #F5F5F5;"><td style="width: 50%;">',
						'<label>'+this.getLocalizedMessage("ext_2")+'</label>',
					'</td><td>',
						'<div data-dojo-type="dijit.form.NumberTextBox" data-dojo-attach-point="port"></div>',
					'</td></tr><tr style="background: #FFF;"><td style="width: 50%;">',
						'<label>'+this.getLocalizedMessage("ext_4")+'</label>',
					'</td><td>',
						'<div data-dojo-type="dijit.form.Select" data-dojo-attach-point="transport"><option value="JSON">json</option><option value="XML">xml</option></div>',
					'</td></tr><tr style="background: #F5F5F5;"><td style="width: 50%;">',
						'<label>'+this.getLocalizedMessage("ext_5")+'</label>',
					'</td><td>',
						'<div data-dojo-type="dijit.form.TextBox" data-dojo-attach-point="sharedKey"></div>',
					'</td></tr><tr style="background: #F5F5F5;"><td style="width: 50%;">',
						'<label>'+this.getLocalizedMessage("ext_9")+'</label>',
					'</td><td>',
						'<div data-dojo-type="dijit.form.TextBox" data-dojo-attach-point="listuser"></div>',
					'</td></tr><tr style="background: #F5F5F5;"><td style="width: 50%;">',
						'<label>'+this.getLocalizedMessage("ext_10")+'</label>',
					'</td><td>',
						'<div data-dojo-type="dijit.form.TextBox" data-dojo-attach-point="listpass"></div>',
					'</td></tr><tr style="background: #FFF;"><td style="width: 50%;">',
						'<label>'+this.getLocalizedMessage("ext_7")+'</label>',
					'</td><td>',
						'<div data-dojo-type="dijit.form.Select" data-dojo-attach-point="autoadd"><option value="0">OFF</option><option value="1">ON</option></div>',
					'</td></tr><tr style="background: #F5F5F5;"><td style="width: 50%;">',
						'<label>'+this.getLocalizedMessage("ext_6")+'</label>',
					'</td><td>',
						'<div data-dojo-type="dijit.form.Select" data-dojo-attach-point="enabled"><option value="0">OFF</option><option value="1">ON</option></div>',
					'</td></tr></table>'
				].join(''),
				_setValueAttr: function(value){
					this.id.set('value', value[0]);
					this.name.set('value', value[1]);
					this.server.set('value', value[2]);
					this.port.set('value', parseInt(value[3], 10));
					this.transport.set('value', value[4]);
					this.sharedKey.set('value', value[5]);
					this.listuser.set('value', value[6]);
					this.listpass.set('value', value[7]);
					this.autoadd.set('value', parseInt(value[8], 10));
					this.enabled.set('value', parseInt(value[9], 10));
				},
				_getValueAttr: function(value){
					return [
						this.id.get('value'),
						this.name.get('value'),
						this.server.get('value'),
						this.port.get('value'),
						this.transport.get('value'),
						this.sharedKey.get('value'),
						this.listuser.get('value'),
						this.listpass.get('value'),
						parseInt(this.autoadd.get('value')),
						parseInt(this.enabled.get('value'))
					];
				},
				focus: function(){
					this.name.focus();
				}
			});

			this.rpcStore = new dojo.store.Memory({
				idProperty:'id',
				data: []
			});

			for (var i = 1; i < 10; i++) {
				this.rpcStore.put({id: i,name: '',server: '',port: 80,transport: 'JSON',sharedKey: '',autoadd: "0", enabled: "0"});
			};

			for (var o in components[0].value) {
				this.rpcStore.put(components[0].value[o]);
			}

			var rpcLayout = [
				{ field: "id", name:"ID", width: '20px'},
				{ field: "id", name: "RPC Servers", editable: true,
					formatter: function(rawData){
						if (!rawData.name || !rawData.server || !rawData.port) {
							return '<span style="color: gray">('+myself.getLocalizedMessage('ext_8')+')</span>';
						}
						else {
							return '<span style="color: '+(rawData.enabled ? 'green' : 'red')+'">'+rawData.name+' ('+ rawData.server+':'+rawData.port + ')</span>';
						}
					},
					editor: 'gridx.controlpanel.CustomEditorRpc',
					editorArgs: {
						useGridData: false,
						toEditor: function(storeData, gridData){
							var values = myself.rpcGrid.model.store.data[storeData-1];
							return [
								values.id,
								values.name,
								values.server,
								parseInt(values.port, 10),
								values.transport,
								values.sharedKey,
								values.listuser
								values.listpass,
								values.autoadd,
								values.enabled
							];
						},
						fromEditor: function(values){
							myself.ldapGrid.model.store.put({
								id: values[0],
								name: values[1],
								server: values[2],
								port: parseInt(values[3], 10),
								transport: values[4],
								sharedKey: values[5],
								listuser: values[6],
								listpass: values[7],
								autoadd: values[8],
								enabled: values[9]
							});
							return values;
						}
					},
					customApplyEdit: function(cell, value){
						return cell.row.setRawData({
							id: value[0],
							name: value[1],
							server: value[2],
							port: parseInt(value[3], 10),
							transport: value[4],
							sharedKey: value[5],
							listuser: value[6],
							listpass: value[7],
							autoadd: value[8],
							enabled: value[9]
						});
					}
				}
			];

			this.rpcGrid = new gridx.Grid({
				store: this.rpcStore,
				cacheClass: 'gridx/core/model/cache/Sync',
				structure: rpcLayout,
				modules: [
					"gridx/modules/CellWidget",
					"gridx/modules/Edit"
				]
			});

			myself._loadingStateRelease();
			this.container.main.center.addChild(this.rpcGrid);
			this.rpcGrid.startup();

			this.container.main.center._layoutChildren();
			this.rpcGrid.resize();
		};
		
		this.save = function() {
			
			this._loadingStateEngage();
			
			var values, validData;
			
			switch(this._currentBuilder) {
				case 'form':
				case 'theme':
					validData = this.form.validate();
					values = this.form.get('value');
				break;
				
				case 'meta':
					validData = true;
					var iValues = this.form.get('value');
					var tags = [];
					for (var key in iValues) {
						tags.push({name: key, content: iValues[key]});
					}
					values = {SITE_TAGS: $d.toJson(tags)};
				break;
				
				case 'bootstrap':
					validData = true;
					var bValues = this.bootstrapGrid.select.row.getSelected();
					var jValues = {persistent: []};
					var jParent, jData, jId, jProperties, jTrim;

					for (var value in bValues) {
						jProperties = {};
						jParent = this.bootstrapGrid.row(bValues[value]).parent().id;
						jData = this.bootstrapGrid.row(bValues[value]).data();
						jId = jData.description;
						if (!$c.Utils.defined(jValues[jParent])) {
							jValues[jParent] = [];
						}
						if (jData.type.trim() != '') { jProperties.type = jData.type.trim(); }
						if (jData.attachNode.trim() != '') { jProperties.attachNode = jData.attachNode.trim(); }
						if (jData.placeAt.trim() != '') { jProperties.placeAt = jData.placeAt.trim(); }
						if (jData.requestSpecialNode.trim() != '') { jProperties.requestSpecialNode = jData.requestSpecialNode.trim(); }
						if (jData.width != '') {
							if (typeof jData.width == 'string') {
								jTrim = jData.width.trim();
								if (!isNaN(parseFloat(jTrim)) && isFinite(jTrim)) {
									jProperties.width = parseInt(jTrim);
								}
								else if (jTrim == "true") {
									jProperties.width = true;
								}
								else if (jTrim == "false") {
									jProperties.width = false;
								}
								else {
									jProperties.width = jTrim;
								}
							}
							else if (typeof jData.width == 'number' || typeof jData.width == 'boolean') {
								jProperties.width = jData.width;
							}
							else {
								jProperties.width = parseInt(jData.width);
							}
						}
						if (jData.height != '') {
							if (typeof jData.height == 'string') {
								jTrim = jData.height.trim();
								if (!isNaN(parseFloat(jTrim)) && isFinite(jTrim)) {
									jProperties.height = parseInt(jTrim);
								}
								else if (jTrim == "true") {
									jProperties.height = true;
								}
								else if (jTrim == "false") {
									jProperties.height = false;
								}
								else {
									jProperties.height = jTrim;
								}
							}
							else if (typeof jData.height == 'number' || typeof jData.height == 'boolean') {
								jProperties.height = jData.height;
							}
							else {
								jProperties.height = parseInt(jData.height);
							}
						}
						if (jData.autoStart != '') {
							if (typeof jData.autoStart == 'string') {
								jTrim = jData.autoStart.trim();
								jProperties.autoStart = jData.autoStart == 'true' ? true : false;
							}
							else if (typeof jData.autoStart == 'boolean') {
								jProperties.autoStart = jData.autoStart;
							}
							else {
								jProperties.autoStart = false;
							}
						}

						if (Object.keys(jProperties).length == 0) {
							jValues[jParent].push(jId);
						}
						else {
							jValues[jParent].push({name: jId, properties: jProperties});
						}
					}
					values = {BOOTSTRAP: $d.toJson(jValues)};
				break;

				case 'ldap':
					validData = true;
					var data = this.ldapGrid.model.store.data;
					var jValues = [];
					var i = 0;
					for (i in data) {
						if (data[i].name != '' && data[i].server != '' && data[i].port != '') {
							jValues.push(data[i]);
						}
					}
					var values = {AUTHENTICATION_LDAPS: $d.toJson(jValues)};
				break;

				case 'rpc':
					validData = true;
					var data = this.rpcGrid.model.store.data;
					var jValues = [];
					var i = 0;
					for (i in data) {
						if (data[i].name != '' && data[i].server != '' && data[i].port != '') {
							jValues.push(data[i]);
						}
					}
					var values = {AUTHENTICATION_RPCS: $d.toJson(jValues)};
				break;

				/*
				case 'require':
					validData = true;
					var runlevelResult = [];
					this._requireStore.fetch({ query: { enabled: true }, onItem: function(item) { runlevelResult.push({name: item.name[0], extraCSS: item.extraCSS[0], enabled: true}); } });
					values = {dojoRequires: $d.toJson(runlevelResult)};
				break;
				*/
			}
			
			if (!validData) {
				this._loadingStateRelease();
				this._throwSaveNotCompliantWarning();
			}
			else {
				values.group = this.state;
				$c.Kernel.newCall(myself.saveCallback, {
					application: "controlpanel",
					method:"setState", 
					content: values
				});
			}
			
		};
		
		this.saveCallback = function(success, result) {
			if (success) { myself._setSuccessState(); }
			else { myself._setErrorState(result); }
		};
		
	}
	
);