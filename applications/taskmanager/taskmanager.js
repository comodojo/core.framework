/**
 * taskManager.js
 * 
 * A process manager for comodojo environment
 *
 * @package		Comodojo Core Applications
 * @author		comodojo.org
 * @copyright	__COPYRIGHT__ comodojo.org (info@comodojo.org)
 * @version		__CURRENT_VERSION__
 * @license		GPL Version 3
 */

$d.require("dojo.data.ItemFileWriteStore");
$d.require("dojo.store.DataStore");
$d.require("comodojo.Layout");
$d.require("dojo.aspect");
			
$c.App.load("taskmanager",

	function(pid, applicationSpace, status){
		
		this.showSystemProcesses = false;
		
		$d.mixin(this,status);
		
		var myself = this;

		this.init = function() {
			
			this.processStore = new dojo.data.ItemFileReadStore({data: this.getProcessListStore(), clearOnClose: true});

			this.mappedProcessStore = new dojo.store.DataStore({store: this.processStore, idProperty: 'pid'});

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
						style:"height: 25px; overflow: hidden;"
					}
				},
				{
					type: 'ContentPane',
					name: 'bottom',
					region: 'bottom',
					params: {},
					cssClass: 'layout_action_pane',
					childrens:[]
				},
				{
					type: 'Grid',
					name: 'taskgrid',
					region: 'center',
					params: {
						structure: [
							{ name: this.getLocalizedMessage('0001'), /*field: 'pid',*/ width: "8%", formatter: function(value) {return value.pid.split('_')[1];}
							},
							{ name: this.getLocalizedMessage('0002'), field: 'title', width: "40%"},
							{ name: this.getLocalizedMessage('0003'), field: 'exec', width: "30%"},
							{ name: this.getLocalizedMessage('0004'), field: 'runMode', width: "12%"},
							{ name: '', width: "5%", formatter: function(value) {
									return '<img src="'+$c.icons.getIcon('cancel',16)+'" onClick="$c.App.byPid(\''+value.pid+'\').stop()" />';
								}
							},
							{ name: '', width: "5%", formatter: function(value) {
									if ($c.App.byPid(value.pid).isComodojoApplication == "WINDOWED") {
										return '<img src="'+$c.icons.getIcon('right_arrow',16)+'" onClick="$c.App.byPid(\''+value.pid+'\').focus()" />';
									}
									else {
										return '';
									}
									
								}
							}
						],
						store: this.mappedProcessStore,
						cacheClass: 'sync'
					}
				}]
			}).build();

			$c.Bus.addConnection('taskManager_applicationsRunningTableChange','comodojo_app_running_registry_change',function(){
				myself.updateTaskManagerStore();
			});
			
			dojo.aspect.before(applicationSpace, 'close', function(){
				$c.Bus.removeConnection('taskManager_applicationsRunningTableChange');
			});

			this.container.main.bottom.containerNode.appendChild(new dijit.form.Button({
				label: '<img src="'+$c.icons.getIcon('close',16)+'" alt="Close" />&nbsp;'+$c.getLocalizedMessage('10011'),
				onClick: function() {
					myself.stop();
				}
			}).domNode);

			$c.Kernel.newCall(myself.loadCallback,{
				application: "taskmanager",
				method: "get_load",
				content: {}
			});
			
		};
		
		this.getProcessListStore = function() {
			var processList = {
				identifier: 'pid',
				label: 'title',
				items: []
			};
			for (var process in $c.Bus._runningApplications) {
				if ( $c.Bus._runningApplications[process][3] == "system" && !myself.showSystemProcesses) {
					continue;
				}
				processList.items.push({
					pid: $c.Bus._runningApplications[process][0],
					exec: $c.Bus._runningApplications[process][1],
					title: $c.Bus._runningApplications[process][2],
					runMode: $c.Bus._runningApplications[process][3]
				});
			}
			return processList;
		};
		
		this.updateTaskManagerStore = function() {
			
			this.processStore.close();
			
			this.processStore.data = this.getProcessListStore();
			
			this.processStore.fetch();
						
			this.container.main.taskgrid.model.clearCache();
			this.container.main.taskgrid.body.refresh();
		
		};
		
		this.loadCallback = function(success, result) {
			if (!success) {
				$c.Error.local(myself.container.main.top.containerNode, result.code, result.name);
			}
			else {
				myself.container.main.top.set('content',myself.getLocalizedMutableMessage('0009',[
					Math.round(result[0]*100)/100,
					Math.round(result[1]*100)/100,
					Math.round(result[2]*100)/100
				]));
			}
		};

	}
	
);