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
$d.require("dojo.parser");
$d.require("dijit.form.TextBox");
$d.require("dijit.form.NumberTextBox");
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
					callOptions = {application: "controlpanel", method:"get_main_view", content: {}};
				break;
				default:
					callOptions = {application: "controlpanel", method:"get_state", content: {group:this.state}};
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
						myself._themeName.innerHTML = "<strong>"+myself.getLocalizedMessage('0153')+":</strong> "+ myself.temp[this.value].label;
						myself._themeCreatedBy.innerHTML = "<strong>"+myself.getLocalizedMessage('0154')+":</strong> "+ myself.temp[this.value].createdBy;
						myself._themeVersion.innerHTML = "<strong>"+myself.getLocalizedMessage('0155')+":</strong> "+ myself.temp[this.value].version;
						myself._themeFramework.innerHTML = "<strong>"+myself.getLocalizedMessage('0156')+":</strong> "+ myself.temp[this.value].framework;
						myself._themeComment.innerHTML = "<strong>"+myself.getLocalizedMessage('0157')+":</strong> "+ myself.temp[this.value].comment;
					};
					
				}
				else {
					components[i].label = this.getLocalizedMessage(components[i].label);
				}
			}
			this._themeImage.style.backgroundImage = "url('comodojo/themes/" + this.temp[currentTheme].label + "/theme.jpg')";
			this._themeName.innerHTML = "<strong>"+this.getLocalizedMessage('0153')+":</strong> "+ this.temp[currentTheme].label;
			this._themeCreatedBy.innerHTML = "<strong>"+this.getLocalizedMessage('0154')+":</strong> "+ this.temp[currentTheme].createdBy;
			this._themeVersion.innerHTML = "<strong>"+this.getLocalizedMessage('0155')+":</strong> "+ this.temp[currentTheme].version;
			this._themeFramework.innerHTML = "<strong>"+this.getLocalizedMessage('0156')+":</strong> "+ this.temp[currentTheme].framework;
			this._themeComment.innerHTML = "<strong>"+this.getLocalizedMessage('0157')+":</strong> "+ this.temp[currentTheme].comment;
			
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
			var i = 0, o = 0;
			for (i in components[0].options.roles) {
				var position = storeElements.items.push({
					id: components[0].options.roles[i].id,
					description: components[0].options.roles[i].description,
					//status: -1,
					aggregate: 'cnt',
					childs: []
				});
				for (o in components[0].options.applications) {
					/*storeElements.items[position-1].childs.push({_reference:components[0].options.applications[o]+components[0].options.roles[i].id});
					storeElements.items.push({
						id: components[0].options.applications[o]+components[0].options.roles[i].id,
						description: components[0].options.applications[o],
						status: !components[0].value[components[0].options.roles[i].id][components[0].options.applications[o]] ? false : true
					});*/
					storeElements.items[position-1].childs.push({
						id: components[0].options.applications[o]+'_'+components[0].options.roles[i].id,
						description: components[0].options.applications[o],
						//status: !components[0].value[components[0].options.roles[i].id][components[0].options.applications[o]] ? false : true
						status: $c.Utils.inArray(components[0].options.applications[o],components[0].value[components[0].options.roles[i].id]) ? true : false
					});
					if ($c.Utils.inArray(components[0].options.applications[o],components[0].value[components[0].options.roles[i].id])) {
						initialSelection.push(components[0].options.applications[o]+'_'+components[0].options.roles[i].id);
					}
				}
			}

			//console.log(storeElements);

			var bootstrapLayout = [
				//{ name: "id", field: "id", width: "auto" },
				{ id: "description", name: "description", field: "description", width: "90%" },
				{ id: "status", name: "status", field: "status", width: "10%", formatter: function(value) {
					return !$c.Utils.defined(value.status) ? '&nbsp;' : ('<img src="'+$c.icons.getIcon(value.status ? 'on' : 'off',16)+'" alt="'+(value.status ? 'on' : 'off')+'"/>');
				}
			}];
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
					'gridx/modules/IndirectSelect'
				],
				selectRowTriggerOnCell: true
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

			dojo.declare('gridx.controlpanel.CustomEditorLdap', [dijit._Widget, dijit._TemplatedMixin, dijit._WidgetsInTemplateMixin], {
				templateString: [
					'<table><tr><td style="width: 100px;">',
						'<label>ID:</label>',
					'</td><td>',
						'<div data-dojo-type="dijit.form.NumberTextBox" data-dojo-attach-point="id"></div>',
					'</td></tr><tr><td style="width: 100px;">',	
						'<label>Name:</label>',
					'</td><td>',
						'<div data-dojo-type="dijit.form.TextBox" data-dojo-attach-point="name"></div>',
					'</td></tr><tr><td style="width: 100px;">',
						'<label>Server:</label>',
					'</td><td>',
						'<div data-dojo-type="dijit.form.TextBox" data-dojo-attach-point="server"></div>',
					'</td></tr><tr><td style="width: 100px;">',
						'<label>Port:</label>',
					'</td><td>',
						'<div data-dojo-type="dijit.form.NumberTextBox" data-dojo-attach-point="port"></div>',
					'</td></tr><tr><td style="width: 100px;">',
						'<label>dcs:</label>',
					'</td><td>',
						'<div data-dojo-type="dijit.form.TextBox" data-dojo-attach-point="dcs"></div>',
					'</td></tr><tr><td style="width: 100px;">',
						'<label>dns:</label>',
					'</td><td>',
						'<div data-dojo-type="dijit.form.TextBox" data-dojo-attach-point="dns"></div>',
					'</td></tr><tr><td style="width: 100px;">',
						'<label>filter:</label>',
					'</td><td>',
						'<div data-dojo-type="dijit.form.TextBox" data-dojo-attach-point="filter"></div>',
					'</td></tr><tr><td style="width: 100px;">',
						'<label>listuser:</label>',
					'</td><td>',
						'<div data-dojo-type="dijit.form.TextBox" data-dojo-attach-point="listuser"></div>',
					'</td></tr><tr><td style="width: 100px;">',
						'<label>listpass:</label>',
					'</td><td>',
						'<div data-dojo-type="dijit.form.TextBox" data-dojo-attach-point="listpass"></div>',
					'</td></tr><tr><td style="width: 100px;">',
						'<label>cmode:</label>',
					'</td><td>',
						'<div data-dojo-type="dijit.form.TextBox" data-dojo-attach-point="cmode"></div>',
					'</td></tr></table>'
				].join(''),
				_setValueAttr: function(value){
					console.log(value);
					this.id.set('value', value[0]);
					this.name.set('value', value[1]);
					this.server.set('value', value[2]);
					this.port.set('value', parseInt(value[3], 10));
					this.dcs.set('value', value[4]);
					this.dns.set('value', value[5]);
					this.filter.set('value', value[6]);
					this.listuser.set('value', value[7]);
					this.listpass.set('value', value[8]);
					this.cmode.set('value', value[9]);
				},
				_getValueAttr: function(value){
					return [
						this.id.get('value'),
						this.name.get('value'),
						this.server.get('value'),
						this.port.get('value'),
						this.dcs.get('value'),
						this.dns.get('value'),
						this.filter.get('value'),
						this.listuser.get('value'),
						this.listpass.get('value'),
						this.cmode.get('value')
					];
				},
				focus: function(){
					this.name.focus();
				}
			});


			this.ldapStore = new dojo.store.Memory({
				idProperty:'id',
				data: components[0].options
			});

			//var ldapLayout = [
			//	{ id: "id", name: "id", field: "id", width: "2%" },
			//	{ id: "name", name: "name", field: "description", width: "11%" },
			//	{ id: "server", name: "server", field: "server", width: "25%" },
			//	{ id: "port", name: "port", field: "port", width: "5%" },
			//	{ id: "dcs", name: "dcs", field: "dcs", width: "15%" },
			//	{ id: "dns", name: "dns", field: "dns", width: "10%" },
			//	{ id: "filter", name: "filter", field: "filter", width: "10%" },
			//	{ id: "listuser", name: "listuser", field: "listuser", width: "10%" },
			//	{ id: "listpass", name: "listpass", field: "listpass", width: "10%" },
			//	{ id: "cmode", name: "cmode", field: "cmode", width: "2%" }
			//];
			
			var ldapLayout = [
				{ field: "id", name:"ID", width: '20px'},
				{ field: "id", name: "Ldap Servers", editable: true,
					//Construct our own cell data using multiple fields
					formatter: function(rawData){
						if (!rawData.name || !rawData.server) {
							return '<span style="color: gray">(Empty)</span>';
						}
						else {
							return '<span style="color: '+(rawData.enabled ? 'green' : 'red')+'">'+rawData.name+' ('+ rawData.server+':'+rawData.port + ')</span>';
						}
					},
					//Use our own editor
					editor: 'gridx.controlpanel.CustomEditorLdap',
					editorArgs: {
						useGridData: false,
						//Feed our editor with proper values
						toEditor: function(storeData, gridData){
							var values = myself.ldapGrid.model.store.data[storeData-1];
							console.log(values);
							return [
								values.id,
								values.name,
								values.server,
								parseInt(values.port, 10),
								values.dcs,
								values.dns,
								values.filter,
								values.listuser,
								values.listpass,
								values.cmode,
								values.enabled
							];
						},
						fromEditor: function(values){
							myself.ldapGrid.model.store.put({
								id: values[0],
								name: values[1],
								server: values[2],
								port: parseInt(values[3], 10),
								dcs: values[4],
								dns: values[5],
								filter: values[6],
								listuser: values[7],
								listpass: values[8],
								cmode: values[9],
								enabled: values[10]
							});
							return values;
						}
					},
					//Define our own "applyEdit" process
					customApplyEdit: function(cell, value){
						return cell.row.setRawData({
							id: value[0],
							name: value[1],
							server: value[2],
							port: parseInt(value[3], 10),
							dcs: value[4],
							dns: value[5],
							filter: value[6],
							listuser: value[7],
							listpass: value[8],
							cmode: value[9],
							enabled: value[10]
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
					var jParent, jId;

					for (var value in bValues) {
						jParent = this.bootstrapGrid.row(bValues[value]).parent().id;
						jId = this.bootstrapGrid.row(bValues[value]).data().description;
						if (!$c.Utils.defined(jValues[jParent])) {
							jValues[jParent] = [];
						}
						jValues[jParent].push(jId);
					}
					values = {BOOTSTRAP: $d.toJson(jValues)};
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
					method:"set_state", 
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