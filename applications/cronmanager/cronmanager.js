/**
 * [APP DESCRIPTION]
 *
 * @package		Comodojo Core Applications
 * @author		comodojo.org
 * @copyright		__COPYRIGHT__ comodojo.org (info@comodojo.org)
 * @version		__CURRENT_VERSION__
 * @license		GPL Version 3
 */

$c.App.loadCss('cronmanager');

$d.require("dojo.on");
$d.require("dojo.store.Memory");
$d.require("dojo.store.Observable");
$d.require("dijit.tree.ObjectStoreModel");
$d.require("dijit.Menu");
$d.require("dijit.MenuItem");
$d.require("comodojo.Layout");
$d.require('comodojo.Form');
$d.require("comodojo.KernelStore");
$d.require('comodojo.Mirror');
$d.require("gridx.modules.SingleSort");
$d.require("gridx.modules.Pagination");
$d.require("gridx.modules.pagination.PaginationBar");
$d.require("gridx.modules.Filter");
$d.require("gridx.modules.filter.FilterBar");

$c.App.load("cronmanager",

	function(pid, applicationSpace, status){
	
		//dojo.mixin(this, status);

		var myself = this;

		$c.blaaa = this;

		this.availableJobs = [];

		this.availableCron = [];

		this.selectedJob = false;

		this.selectedCronId = false;

		this.jobPattern = "<?php\n\ncomodojo_load_resource('cron_job');\n\nclass [JOB_NAME] extends cron_job {\n\t\n\tpublic function logic($params) {\n\t\n    }\n\n}\n\n?>";

		this.init = function(){

			this.cStore = new dojo.store.Memory({
				data: [
					{ id: 'cronrootnode', name:'Cron', leaf: false}
				],
				getChildren: function(object){
					return this.query({type: object.id});
				}
			});

			this.jStore = new dojo.store.Memory({
				data: [
					{ id: 'jobsrootnode', name:'Jobs', leaf: false}
				],
				getChildren: function(object){
					return this.query({type: object.id});
				}
			});

			this.wStore = new comodojo.KernelStore({application: 'cronmanager'});

			myself.layout();

			$c.Kernel.newCall(myself.initCallback,{
				application: "cronmanager",
				method: "get_cron_and_jobs"
			});

		};
		
		this.initCallback = function(success,result) {
			if (success) {
				var i=0,o=0;
				for (i in result.cron) {
					result.cron[i].leaf = true;
					result.cron[i].type = 'cronrootnode';
					myself.cStoreObservable.put(result.cron[i]);
					myself.availableCron.push(result.cron[i]);
				}
				for (o in result.jobs) {
					result.jobs[o].leaf = true;
					result.jobs[o].type = 'jobsrootnode';
					myself.jStoreObservable.put(result.jobs[o]);
					myself.availableJobs.push({
						label: result.jobs[o].name,
						value: result.jobs[o].name
					});
				}
				myself.updateCrontab();
				myself.cronForm.fields.job.addOption(myself.availableJobs);
				//console.log(myself.availableJobs);
				//myself.cronForm.fields.job.store.objectStore = new dojo.store.Memory({
				//	data: myself.availableCron
				//});
				//myself.cronForm.fields.job.store.close();
				//myself.cronForm.fields.job.store.fetch();
			}
			else {
				$c.Error.modal(result.code,result.name);
				myself.stop();
			}
		};

		this.layout = function() {

			this.cStoreObservable = new dojo.store.Observable(this.cStore);
			this.jStoreObservable = new dojo.store.Observable(this.jStore);

			this.cModel = new dijit.tree.ObjectStoreModel({
				store: this.cStoreObservable,
				query: {id: 'cronrootnode'}
			});
			this.jModel = new dijit.tree.ObjectStoreModel({
				store: this.jStoreObservable,
				query: {id: 'jobsrootnode'}
			});

			this.cModel.mayHaveChildren = function(item) {
				return item.leaf == false;
			};
			this.jModel.mayHaveChildren = function(item) {
				return item.leaf == false;
			};

			this.container = new $c.Layout({
				modules: ['Tree','TabContainer','Grid'],
				attachNode: applicationSpace,
				splitter: false,
				gutters: false,
				id: pid,
				hierarchy: [{
					type: 'TabContainer',
					name: 'center',
					region: 'center',
					params: {
						//style: 'margin-bottom: 5px;'
					},
					childrens: [{
						type: 'BorderContainer',
						name: 'cron_management',
						params: {
							//design: 'sidebar',
							title: this.getLocalizedMessage('0021'),
							gutters: true
						},
						childrens: [{
							type: 'Tree',
							name: 'cron_tree',
							region: 'left',
							params: {
								model: this.cModel,
								style: "width: 200px;",
								splitter: true,
								id: 'main_cron_tree_'+pid
							}
						},{
							type: 'ContentPane',
							name: 'cron_properties',
							region: 'center',
							params: {}
						},{
							type: 'ContentPane',
							name: 'cron_tab',
							region: 'trailing',
							cssClass: 'layout_action_pane',
							params: {
								splitter: true,
								style: "width: 300px;"
							}
						},{
							type: 'ContentPane',
							name: 'cron_actions',
							region: 'bottom',
							cssClass: 'layout_action_pane'
						}]
					},{
						type: 'BorderContainer',
						name: 'jobs_management',
						params: {
							design: 'sidebar',
							title: this.getLocalizedMessage('0022'),
							gutters: true
						},
						childrens: [{
							type: 'Tree',
							name: 'jobs_tree',
							region: 'left',
							params: {
								model: this.jModel,
								style: "width: 200px;",
								splitter: true
							}
						},{
							type: 'ContentPane',
							name: 'job_code',
							region: 'center',
							params: {}
						},{
							type: 'ContentPane',
							name: 'job_actions',
							region: 'bottom',
							cssClass: 'layout_action_pane'
						}]
					},{
						type: 'BorderContainer',
						name: 'cron_worklog_management',
						params: {
							title: this.getLocalizedMessage('0023'),
							gutters: true
						},
						childrens: [{
							type: 'Grid',
							name: 'cron_worklog',
							region: 'center',
							params: {
								title: 'CRON WORKLOG',
								structure: [
									{ name: 's', field: 'success', dataType: 'bool', width: '5%', style: function(cell) {
										var color = cell.row.rawData().success == 1 ? "#8c8;" : "#c88;";
										return "text-align: center; background: "+color+"; color: "+color+";";
									}},
									{ name: /*this.getLocalizedMessage('0016')*/'id', width: '8%',  field: 'id'},
									{ name: /*this.getLocalizedMessage('0001')*/'pid', width: '27%', field: 'pid'},
									{ name: /*this.getLocalizedMessage('0002')*/'name', width: '15%', field: 'name'},
									{ name: /*this.getLocalizedMessage('0006')*/'job', width: '14%', field: 'job'},
									{ name: /*this.getLocalizedMessage('0006')*/'status', width: '14%', field: 'status'},
									{ name: /*this.getLocalizedMessage('0006')*/'start', width: '14%', field: 'start'},
									{ name: /*this.getLocalizedMessage('0006')*/'end', width: '14%', field: 'end'}
								],
								sortInitialOrder: { colId: '2', descending: true },
								style: 'padding: 0px; margin: 0px !important;',
								store: myself.wStore,
								modules: [
									"gridx/modules/SingleSort",
									"gridx/modules/Pagination",
									"gridx/modules/pagination/PaginationBar",
									"gridx/modules/Filter",
									"gridx/modules/filter/FilterBar"
									//"gridx/modules/Menu",
									//"gridx/modules/extendedSelect/Row"
								]
							}
						},{
							type: 'ContentPane',
							name: 'cron_worklog_actions',
							region: 'bottom',
							cssClass: 'layout_action_pane'
						}]
					}]
				}]
			}).build();

			/****** TREES LAYOUT AND ACTIONS ******/

			this.container.main.center.cron_management.cron_tree.getIconClass = function(item, opened) {
				
				if (!item || this.model.mayHaveChildren(item)) {
					return opened ? "dijitFolderOpened" : "dijitFolderClosed";
				}
				else {
					return item.enabled ? 'cronmanager_cron_enabled' : 'cronmanager_cron_disabled';
				}

			};

			this.container.main.center.cron_management.cron_tree.getLabelClass = function(item, opened) {

				if (!item || this.model.mayHaveChildren(item)) {
					return "";
				}
				else {
					return item.enabled ? 'cronmanager_cron_enabled_label' : 'cronmanager_cron_disabled_label';
				}

			};

			this.container.main.center.cron_management.cron_tree.on('click',function(item){
				if (item.leaf) {
					//myself.selectedCronId = item.id;
					myself.openCron(item.name);
				}
			});

			this.container.main.center.jobs_management.jobs_tree.on('click',function(item){
				if (item.leaf) {
					myself.selectedJob = item.name;
					myself.openJob(item.name);
				}
			});

			/****** TREE MENUS ******/

			this.cronEnabledMenu = new dijit.Menu({
				id: 'cronEnabledMenu'+pid,
				targetNodeIds: ["main_cron_tree_"+pid],
				selector: ".cronmanager_cron_enabled_label"
			});

			this.switchStateEnabledSelector = new dijit.MenuItem({
				label: this.getLocalizedMessage('0008'),
				onClick: function(e) {
					var targetNode = dijit.getEnclosingWidget(this.getParent().currentTarget);
					dojo.removeClass(targetNode.iconNode,'cronmanager_cron_enabled');
					dojo.removeClass(targetNode.labelNode,'cronmanager_cron_enabled_label');
					dojo.addClass(targetNode.iconNode,'cronmanager_cron_changing');
					myself.disableCron(targetNode.item.name);
				}
			});
			this.cronEnabledMenu.addChild(this.switchStateEnabledSelector);

			this.cronDisabledMenu = new dijit.Menu({
				id: 'cronDisabledMenu'+pid,
				targetNodeIds: ["main_cron_tree_"+pid],
				selector: ".cronmanager_cron_disabled_label"
			});

			this.switchStateDisabledSelector = new dijit.MenuItem({
				label: this.getLocalizedMessage('0007'),
				onClick: function() {
					var targetNode = dijit.getEnclosingWidget(this.getParent().currentTarget);
					dojo.removeClass(targetNode.iconNode,'cronmanager_cron_disabled');
					dojo.removeClass(targetNode.labelNode,'cronmanager_cron_disabled_label');
					dojo.addClass(targetNode.iconNode,'cronmanager_cron_changing');
					myself.enableCron(targetNode.item.name);
				}
			});

			this.deleteCronDisabledSelector = new dijit.MenuItem({
				label: this.getLocalizedMessage('0009'),
				onClick: function() {
					var targetNode = dijit.getEnclosingWidget(this.getParent().currentTarget);
					myself.selectedCronId = targetNode.item.id;
					myself.deleteCron(targetNode.item.name);
				}
			});

			this.cronDisabledMenu.addChild(this.switchStateDisabledSelector);
			this.cronDisabledMenu.addChild(this.deleteCronDisabledSelector);

			/****** CRON FORM ******/

			this.cronForm = new $c.Form({
				modules:['TextBox','Textarea','ValidationTextBox','Select','MultiSelect','Button','ComboBox'],
				formWidth: 'auto',
				template: 'LABEL_ON_INPUT',
				//hidden: true,
				hierarchy:[{
					name: "note",
						type: "info",
						content: this.getLocalizedMessage('0024')
					},{
					name: "id",
					value: '',
					type: "ValidationTextBox",
					label: 'id',
					required: true,
					readonly: true,
					hidden: true
				},{
					name: "name",
					value: '',
					type: "ValidationTextBox",
					label: this.getLocalizedMessage('0016'),
					required: true,
				},{
					name: "expression",
					value: '',
					type: "ValidationTextBox",
					regExp: "\\S+\\s{1}\\S+\\s{1}\\S+\\s{1}\\S+\\s{1}\\S+\\s{1}\\S+",
					label: this.getLocalizedMessage('0017'),
					required: true
				},{
					name: "job",
					value: 'SERVICE',
					type: "Select",
					label: this.getLocalizedMessage('0018'),
					required: true
					//options: this.availableJobs
				},{
					name: "description",
					value: '',
					type: "Textarea",
					label: myself.getLocalizedMessage('0019'),
					required: false
				},{
					name: "params",
					value: '',
					type: "Textarea",
					label: myself.getLocalizedMessage('0020'),
					required: false
				}],
				attachNode: this.container.main.center.cron_management.cron_properties.containerNode
			}).build();

			this.cronForm.fields.expression.on('blur', function() {
				if (myself.cronForm.fields.expression.isValid()) {
					myself.validateCronExpression();
				}
				else {
					myself.cronForm.fields.note.changeContent(myself.getLocalizedMutableMessage('0026',['']));
					myself.cronForm.fields.note.changeType('error');
				}
			});

			/****** JOB MIRROR ******/

			this.job_mirror = comodojo.Mirror.build({
				attachNode: this.container.main.center.jobs_management.job_code.containerNode, 
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

			this.job_mirror.setSize('100%','100%');

			this.job_mirror.lock();

			/******* BUTTONS ******/

			this.newCronButton = new dijit.form.Button({
				label: '<img src="'+$c.icons.getIcon('add',16)+'" />&nbsp;'+this.getLocalizedMessage('0000'),
				style: 'float: left;',
				onClick: function() {
					myself.newCron();
				}
			});

			this.container.main.center.cron_management.cron_actions.containerNode.appendChild(this.newCronButton.domNode);

			this.updateSaveCronButton = new dijit.form.Button({
				label: '<img src="'+$c.icons.getIcon('save',16)+'" />&nbsp;'+this.getLocalizedMessage('0002'),
				style: 'float: right;',
				disabled: true
			});

			this.container.main.center.cron_management.cron_actions.containerNode.appendChild(this.updateSaveCronButton.domNode);

			this.newJobButton = new dijit.form.Button({
				label: '<img src="'+$c.icons.getIcon('add',16)+'" />&nbsp;'+this.getLocalizedMessage('0001'),
				style: 'float: left;',
				onClick: function() {
					myself.newJob();
				}
			});

			this.container.main.center.jobs_management.job_actions.containerNode.appendChild(this.newJobButton.domNode);

			this.deleteJobButton = new dijit.form.Button({
				label: '<img src="'+$c.icons.getIcon('delete',16)+'" />&nbsp;'+this.getLocalizedMessage('0010'),
				style: 'float: right;',
				onClick: function() {
					myself.deleteJob();
				},
				disabled: true
			});

			this.container.main.center.jobs_management.job_actions.containerNode.appendChild(this.deleteJobButton.domNode);

			this.updateSaveJobButton = new dijit.form.Button({
				label: '<img src="'+$c.icons.getIcon('save',16)+'" />&nbsp;'+this.getLocalizedMessage('0003'),
				style: 'float: right;',
				disabled: true
			});

			this.container.main.center.jobs_management.job_actions.containerNode.appendChild(this.updateSaveJobButton.domNode);
			
		};

		/****** JOB ACTIONS AND CALLBACK ******/

		this.newJob = function() {
			myself.job_mirror.setValue(myself.jobPattern);
			myself.job_mirror.release();
			myself.job_mirror.refresh();
			dojo.query(".CodeMirror-dialog", myself.container.main.center.jobs_management.job_code.containerNode).forEach(function(node) {
				comodojo.Utils.destroyNode(node);
			});
			myself.updateSaveJobButton.set({
				label: '<img src="'+$c.icons.getIcon('save',16)+'" />&nbsp;'+this.getLocalizedMessage('0003'),
				onClick: function() {
					$c.App.start('readyform',{
						modules: ['ValidationTextBox','Button'],
						callback: myself.registerJob,
						callbackOnClose: false,
						hierarchy: [{
							name: 'jobName',
							type: "ValidationTextBox",
							label: myself.getLocalizedMessage('0012'),
							required:true
						}]
					}, false, false, {type: 'modal', width: 300, height: false});
				},
				disabled: false
			});
			myself.deleteJobButton.set('disabled',true);
		};

		this.openJob = function(jobName) {
			$c.Kernel.newCall(myself.openJobCallback,{
				application: "cronmanager",
				method: "open_job",
				content: {
					job_name: jobName
				}
			});
		};

		this.openJobCallback = function(success, result) {
			if (success) {
				myself.job_mirror.setValue(result);
				myself.job_mirror.release();
				myself.job_mirror.refresh();
				dojo.query(".CodeMirror-dialog", myself.container.main.center.jobs_management.job_code.containerNode).forEach(function(node) {
					comodojo.Utils.destroyNode(node);
				});
				myself.updateSaveJobButton.set({
					onClick: function() {
						myself.saveJob();
					},
					label: '<img src="'+$c.icons.getIcon('save',16)+'" />&nbsp;'+myself.getLocalizedMessage('0005'),
					disabled: false
				});
				myself.deleteJobButton.set('disabled',false);
			}
			else {
				$c.Error.modal(result.code, result.name);
			}
		};

		this.saveJob = function() {
			var editor = myself.job_mirror.getValue();
			if (editor == "") {
				$c.Dialog.info(myself.getLocalizedMessage('0013'));
				return;
			}
			$c.Kernel.newCall(myself.saveJobCallback,{
				application: "cronmanager",
				method: "edit_job",
				content: {
					job_name: myself.selectedJob,
					job_content: editor
				}
			});
		};

		this.saveJobCallback = function(success, result) {
			if (success) {
				$c.Dialog.info(myself.getLocalizedMessage('0014'));
			}
			else {
				$c.Error.modal(result.code, result.name);
			}
		};

		this.registerJob = function(data) {
			var editor = myself.job_mirror.getValue();
			if (editor == "") {
				$c.Dialog.info(myself.getLocalizedMessage('0013'));
				return;
			}
			$c.Kernel.newCall(myself.registerJobCallback,{
				application: "cronmanager",
				method: "new_job",
				content: {
					job_name: data.jobName,
					job_content: editor
				}
			});
		};

		this.registerJobCallback = function(success, result) {
			if (success) {
				$c.Dialog.info(myself.getLocalizedMessage('0014'));
				myself.selectedJob = result;
				myself.jStoreObservable.put({
					id: result,
					name: result,
					type: 'jobsrootnode',
					leaf: true
				});
				myself.container.main.center.jobs_management.jobs_tree.set('paths', [ [ 'jobsrootnode', result ] ] );
				myself.updateSaveJobButton.set({
					onClick: function() {
						myself.saveJob();
					},
					label: '<img src="'+$c.icons.getIcon('save',16)+'" />&nbsp;'+myself.getLocalizedMessage('0005'),
				});
				myself.deleteJobButton.set('disabled',false);
				myself.cronForm.fields.job.addOption({label:result,value:result});
			}
			else {
				$c.Error.modal(result.code, result.name);
			}
		};

		this.deleteJob = function(data) {
			$c.Kernel.newCall(myself.deleteJobCallback,{
				application: "cronmanager",
				method: "delete_job",
				content: {
					job_name: myself.selectedJob
				}
			});
		};

		this.deleteJobCallback = function(success, result) {
			if (success) {
				$c.Dialog.info(myself.getLocalizedMessage('0015'));
				myself.jStoreObservable.remove(result);		
				myself.updateSaveJobButton.set({
					onClick: function() {
						myself.saveJob();
					},
					label: '<img src="'+$c.icons.getIcon('save',16)+'" />&nbsp;'+myself.getLocalizedMessage('0003'),
					disabled: true
				});
				myself.deleteJobButton.set('disabled',true);
				myself.job_mirror.setValue('');
				myself.cronForm.fields.job.removeOption(result);
			}
			else {
				$c.Error.modal(result.code, result.name);
			}
		};

		/****** CRON ACTIONS AND CALLBACK ******/

		this.updateCrontab = function() {
			myself.container.main.center.cron_management.cron_tab.set('content','');

			var html_table = '<table class="ym-table bordertable"><thead><tr><th>'+myself.getLocalizedMessage('0017')+'</th><th>'+myself.getLocalizedMessage('0016')+'</th></tr></thead><tbody>';
			var exp;
			for (i in myself.availableCron) {
				exp = myself.availableCron[i].min+' '+myself.availableCron[i].hour+' '+myself.availableCron[i].day_of_month+' '+myself.availableCron[i].month+' '+myself.availableCron[i].day_of_week+' '+myself.availableCron[i].year;
				html_table += '<tr><td>'+exp+'</td><td>'+myself.availableCron[i].name+' ('+myself.availableCron[i].job+')</td></tr>';
			}

			html_table += '</tbody></table>';

			myself.container.main.center.cron_management.cron_tab.set('content',html_table);
		};

		this.enableCron = function(cron) {
			$c.Kernel.newCall(myself.enableCronCallback,{
				application: "cronmanager",
				method: "enable_cron",
				content: {
					name: cron
				}
			});
		};

		this.enableCronCallback = function (success, result) {
			if (success) {
				$d.removeClass(myself.container.main.center.cron_management.cron_tree.getNodesByItem(result.id+'')[0].iconNode,"cronmanager_cron_changing");
				$d.addClass(myself.container.main.center.cron_management.cron_tree.getNodesByItem(result.id+'')[0].iconNode,"cronmanager_cron_enabled");
				$d.addClass(myself.container.main.center.cron_management.cron_tree.getNodesByItem(result.id+'')[0].labelNode,"cronmanager_cron_enabled_label");
			}
			else {
				$c.Error.modal(result.code, result.name);
			}
		};

		this.disableCron = function(cron) {
			$c.Kernel.newCall(myself.disableCronCallback,{
				application: "cronmanager",
				method: "disable_cron",
				content: {
					name: cron
				}
			});
		};

		this.disableCronCallback = function (success, result) {
			if (success) {
				$d.removeClass(myself.container.main.center.cron_management.cron_tree.getNodesByItem(result.id+'')[0].iconNode,"cronmanager_cron_changing");
				$d.addClass(myself.container.main.center.cron_management.cron_tree.getNodesByItem(result.id+'')[0].iconNode,"cronmanager_cron_disabled");
				$d.addClass(myself.container.main.center.cron_management.cron_tree.getNodesByItem(result.id+'')[0].labelNode,"cronmanager_cron_disabled_label");
			}
			else {
				$c.Error.modal(result.code, result.name);
			}
		};

		this.deleteCron = function(cron) {
			$c.Kernel.newCall(myself.deleteCronCallback,{
				application: "cronmanager",
				method: "delete_cron",
				content: {
					name: cron
				}
			});
		};

		this.deleteCronCallback = function (success, result) {
			if (success) {
				myself.cStoreObservable.remove(myself.selectedCronId);
				myself.selectedCronId = false;
				//if (myself.propertiesForm.get('value')['name'] == result) {
				//	myself._resetForm();
				//	myself._disableForm();
				//	myself._resetEditor();
				//	myself._disableEditor();
				//}
			}
			else {
				$c.Error.modal(result.code, result.name);
			}
		};

		this.validateCronExpression = function() {
			$c.Kernel.newCall(myself.validateCronExpressionCallback,{
				application: "cronmanager",
				method: "validate_cron",
				content: {
					expression: myself.cronForm.fields.expression.get('value')
				}
			});
		};

		this.validateCronExpressionCallback = function(success, result) {
			if (success) {
				myself.cronForm.fields.note.changeContent(myself.getLocalizedMutableMessage('0025',[result]));
				myself.cronForm.fields.note.changeType('success');
			}
			else {
				myself.cronForm.fields.note.changeContent(myself.getLocalizedMutableMessage('0026',[result.name]));
				myself.cronForm.fields.note.changeType('error');
			}
		};

		this.openCron = function(cron) {
			$c.Kernel.newCall(myself.openCronCallback,{
				application: "cronmanager",
				method: "open_cron",
				content: {
					name: cron
				}
			});
		};

		this.openCronCallback = function (success, result) {
			if (success) {
				myself.cronForm.set('value',result);
				myself.validateCronExpression();
			}
			else {
				$c.Error.modal(result.code, result.name);
			}
		};

	}
	
);