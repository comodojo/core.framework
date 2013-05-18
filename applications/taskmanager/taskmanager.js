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

$c.loadComponent('layout',['Grid']);
			
$c.app.load("taskmanager",

	function(pid, applicationSpace, status){
		
		this.showSystemProcesses = false;
		
		$d.mixin(this,status);
		
		this.selectedPid = false;
		
		/**
		 * Alias of "this", to deferred function call
		 */
		var myself = this;

		/**
		 * THE INIT - it's default constructor for new application
		 */
		this.init = function() {
			
			this.processStore = new dojo.data.ItemFileReadStore({data: this.getProcessListStore(), clearOnClose: true});

			this.container = new $c.layout({
				attachNode: applicationSpace,
				splitter: false,
				_pid: pid,
				hierarchy: [{
					type: 'ContentPane',
					name: 'top',
					region: 'top',
					params: {
						style:"height: 25px; overflow: hidden;"
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
					name: 'taskgrid',
					region: 'center',
					store: this.processStore,
					params: {
						structure: [
							{ name: this.getLocalizedMessage('0001'), field: 'pid', width: "5%", formatter: function(value) {return value.split('_')[1];}
							},
							{ name: this.getLocalizedMessage('0002'), field: 'title', width: "40%"},
							{ name: this.getLocalizedMessage('0003'), field: 'exec', width: "30%"},
							{ name: this.getLocalizedMessage('0004'), field: 'runMode', width: "15%"},
							{ name: '', width: "5%", formatter: function() {
									return '<img src="'+$c.icons.getIcon('cancel',16)+'" onClick="$c.app.byPid(\''+pid+'\').killSelected()" />';
								}
							},
							{ name: '', width: "5%", formatter: function() {
									return '<img src="'+$c.icons.getIcon('right_arrow',16)+'" onClick="$c.app.byPid(\''+pid+'\').focusSelected()" />';
								}
							}
						],
						style: 'padding: 0px; margin: 0px !important;',
						selectionMode: "single"
					}
				}]
			}).build();

			this.container.main.taskgrid.onCellMouseOver = function(e) {
				myself.selectedPid = myself.container.main.taskgrid.getItem(e.rowIndex).pid;
			};
			
			$c.bus.addConnection('taskManager_applicationsRunningTableChange','applicationsRunningTableChange',function(){
				myself.updateTaskManagerStore();
			});
			
			dojo.connect(applicationSpace, 'uninitialize', function(){
				$c.bus.removeConnection('taskManager_applicationsRunningTableChange');
			});

			this.container.main.bottom.containerNode.appendChild(new dijit.form.Button({
				label: '<img src="'+$c.icons.getIcon('close',16)+'" alt="Close" />&nbsp;'+$c.getLocalizedMessage('10011'),
				onClick: function() {
					myself.stop();
				}
			}).domNode);

			$c.kernel.newCall(myself.loadCallback,{
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
			for (var process in $c.bus._runningApplications) {
				if ( $c.bus._runningApplications[process][3] == "system" && !myself.showSystemProcesses) {
					continue;
				}
				processList.items.push({
					pid: $c.bus._runningApplications[process][0],
					exec: $c.bus._runningApplications[process][1],
					title: $c.bus._runningApplications[process][2],
					runMode: $c.bus._runningApplications[process][3]
				});
			}
			return processList;
		};
		
		this.updateTaskManagerStore = function() {
			
			this.processStore.close();
			
			this.processStore.data = this.getProcessListStore();
			
			this.processStore.fetch();
						
			this.container.main.taskgrid.setStore(this.processStore);
		
		};
		
		this.killSelected = function() {
			$c.app.stop(myself.selectedPid[0]);
		};
		
		this.focusSelected = function() {
			$c.app.setFocus(myself.selectedPid[0]);
		};
		
		this.loadCallback = function(success, result) {
			if (!success) {
				$c.error.local(result.code,result.name,myself.container.main.top.containerNode);
			}
			else {
				myself.container.main.top.set('content',myself.getLocalizedMutableMessage('0009',[result[0],result[1],result[2]]));
			}
		};

	}
	
);