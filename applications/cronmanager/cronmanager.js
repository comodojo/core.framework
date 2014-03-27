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

		this.availableJobs = [];

		this.selectedJob = false;

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
					myself.sStore.data.push(result.cron[i]);
				}
				for (o in result.jobs) {
					result.jobs[o].leaf = true;
					result.jobs[o].type = 'jobsrootnode';
					myself.jStore.data.push(result.jobs[o]);
					myself.availableJobs.push({
						label: result.jobs[o],
						id: result.jobs[o]
					});
				}
				myself.layout();
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
							design: 'sidebar',
							title: 'CRON MANAGEMENT',
							gutters: true
						},
						childrens: [{
							type: 'Tree',
							name: 'cron_tree',
							region: 'left',
							params: {
								model: this.cModel,
								style: "width: 200px;",
								splitter: true
							}
						},{
							type: 'ContentPane',
							name: 'cron_properties',
							region: 'center',
							params: {}
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
							title: 'JOBS MANAGEMENT',
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
							title: 'CRON WORKLOG',
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

			this.container.main.center.jobs_management.jobs_tree.getIconClass = function(item, opened) {

				if (!item || this.model.mayHaveChildren(item)) {
					return opened ? "dijitFolderOpened" : "dijitFolderClosed";
				}
				
			};

			this.container.main.center.cron_management.cron_tree.on('click',function(item){
				if (item.leaf) {

				}
			});

			this.container.main.center.jobs_management.jobs_tree.on('click',function(item){
				if (item.leaf) {
					myself.selectedJob = item.name;
					myself.openJob(item.name);
				}
			});


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

			this.addSaveJobButton = new dijit.form.Button({
				label: '<img src="'+$c.icons.getIcon('add',16)+'" />&nbsp;'+$c.getLocalizedMessage('10021'),
				style: 'float: left;',
				onClick: function() {
					
				}
			});

			this.container.main.center.jobs_management.job_actions.containerNode.appendChild(this.addSaveJobButton.domNode);
			
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
				myself.addSaveJobButton.set({
					onClick: function() {
						myself.saveJob();
					},
					label: '<img src="'+$c.icons.getIcon('save',16)+'" />&nbsp;'+$c.getLocalizedMessage('10021'),
				});
			}
			else {
				$c.Error.modal(result.code, result.name);
			}
		};

		this.saveJob = function() {
			var editor = myself.job_mirror.getValue();
			if (editor == "") {

			}
			else {
				$c.Kernel.newCall(myself.saveJobCallback,{
					application: "cronmanager",
					method: "edit_job",
					content: {
						job_name: myself.selectedJob,
						job_content: editor
					}
				});
			}
		};

		this.saveJobCallback = function(success, result) {
			if (success) {
				$c.Dialog.info('saved');
			}
			else {
				$c.Error.modal(result.code, result.name);
			}
		};

	}
	
);