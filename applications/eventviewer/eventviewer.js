/**
 * [APP DESCRIPTION]
 *
 * @package		Comodojo Core Applications
 * @author		comodojo.org
 * @copyright		__COPYRIGHT__ comodojo.org (info@comodojo.org)
 * @version		__CURRENT_VERSION__
 * @license		GPL Version 3
 */

$d.require("dijit.form.Button");
$d.require("dojo.request");
$d.require("dojo.aspect");
$d.require("comodojo.Layout");
$d.require("comodojo.KernelStore");
$d.require("gridx.modules.SingleSort");
$d.require("gridx.modules.Pagination");
$d.require("gridx.modules.pagination.PaginationBarDD");
$d.require("gridx.modules.Filter");
$d.require("gridx.modules.filter.FilterBar");
$d.require("gridx.modules.RowHeader");
$d.require("gridx.modules.extendedSelect.Row");
$d.require("gridx.modules.Menu");
$d.require("gridx.modules.VirtualVScroller");
$d.require("dijit.Menu");
$d.require("dijit.MenuItem");

$c.App.load("eventviewer",

	function(pid, applicationSpace, status){
	
		var myself = this;
		
		this.init = function(){

			$d.request.get($c.applicationsPath + 'eventviewer' + '/resources/known_events.json', {
				handleAs: 'json',
				sync: true
			}).then(function(obj){
				myself.eventData = obj;
			},function(err){
				console.log(err);
				myself.eventData = {};
			});
			
			this.store = new comodojo.KernelStore({application: 'eventviewer'});

			this.eventsMenu = new dijit.Menu({
				id: 'eventsMenu_'+pid
			});

			this.filterByValue = new dijit.MenuItem({
				label: 'filter by true',
				onClick: function() {
					myself.filterByPattern();
				}
			});
			this.filterByNotValue = new dijit.MenuItem({
				label: 'filter by false',
				onClick: function() {
					myself.filterByNotPattern();
				}
			});
			this.showEventDetails = new dijit.MenuItem({
				label: myself.getLocalizedMessage('0014'),
				onClick: function() {
					myself.eventDetail();
				}
			});
			this.followSession = new dijit.MenuItem({
				label: myself.getLocalizedMessage('0015'),
				onClick: function() {
					myself.startFollowingSession();
				}
			});
			
			this.eventsMenu.addChild(this.filterByValue);
			this.eventsMenu.addChild(this.filterByNotValue);
			this.eventsMenu.addChild(this.showEventDetails);
			this.eventsMenu.addChild(this.followSession);

			$d.aspect.before(this.eventsMenu,'_openMyself',function(){
				var context = myself.container.main.grid.menu.context;
				var cellData = context.cell.data();
				var cellName;
				switch(context.cell.column.id) {
					case "1":
						cellName = myself.getLocalizedMessage('0003');
					break;
					case "2":
						cellName = myself.getLocalizedMessage('0016');
					break;
					case "3":
						cellName = myself.getLocalizedMessage('0001');
					break;
					case "4":
						cellName = myself.getLocalizedMessage('0002');
					break;
					case "5":
						cellName = myself.getLocalizedMessage('0006');
					break;
					case "6":
						cellName = myself.getLocalizedMessage('0004');
					break;
					case "7":
						cellName = myself.getLocalizedMessage('0005');
					break;
					case "7":
						cellName = myself.getLocalizedMessage('0007');
					break;
				}
				myself.filterByValue.attr('label',myself.getLocalizedMessage('0013')+cellName+' = '+(!cellData ? 'null' : cellData));
				myself.filterByNotValue.attr('label',myself.getLocalizedMessage('0013')+cellName+' != '+(!cellData ? 'null' : cellData));
			});

			this.container = new $c.Layout({
				modules: ['Grid'],
				attachNode: applicationSpace,
				splitter: false,
				id: pid,
				hierarchy: [{
					type: 'Grid',
					name: 'grid',
					region: 'center',
					params: {
						title: this.getLocalizedMessage('0000'),
						cacheClass: "async",
						structure: [
							{ name: this.getLocalizedMessage('0003'), field: 'success', dataType: 'bool', width: '5%', style: function(cell) {
								var color = cell.data() == 1 ? "#8c8;" : "#c88;";
								return "text-align: center; background: "+color+"; color: "+color+";";
							}},
							{ name: this.getLocalizedMessage('0016'), width: '8%', field: 'id'},
							{ name: this.getLocalizedMessage('0001'), width: '27%', field: 'type'},
							{ name: this.getLocalizedMessage('0002'), width: '15%', field: 'referTo'},
							{ name: this.getLocalizedMessage('0006'), width: '14%', field: 'userName'},
							{ name: this.getLocalizedMessage('0004'), width: '11%', field: 'date',
								dataType: 'date',
								dateParser: function (value) {
									return value;
								}
								//decorator: function (cellData) {
								//	var dateString = cellData ? dojo.date.locale.format(new Date(cellData),
								//		{"selector": "date", "formatLength": "medium"}) : "";
								//	return "" + dateString + "";
								//},
								//useRawData: true
							},
							{ name: this.getLocalizedMessage('0005'), width: '10%', field: 'time',
								dataType: 'time'
							},
							{ name: this.getLocalizedMessage('0007'), width: '10%', field: 'host'}//,
							//{ name: this.getLocalizedMessage('0011'), width: '10%', field: 'sessionId'}
						],
						sortInitialOrder: { colId: '2', descending: true },
						style: 'padding: 0px; margin: 0px !important;',
						store: this.store,
						modules: [
							"gridx/modules/SingleSort",
							"gridx/modules/RowHeader",
							"gridx/modules/Pagination",
							"gridx/modules/pagination/PaginationBarDD",
							"gridx/modules/Filter",
							"gridx/modules/filter/FilterBar",
							"gridx/modules/Menu",
							"gridx/modules/extendedSelect/Row",
							"gridx/modules/VirtualVScroller"
						]
					}
				},{
					type: 'ContentPane',
					name: 'bottom',
					region: 'bottom',
					params: {
						style: 'height:30px; text-align:right;'
					}
				}]
			}).build();

			this.container.main.grid.menu.bind(this.eventsMenu, {
				hookPoint: 'cell',
				selected: false
			});

			this.updateEventsButton = new dijit.form.Button({
				label: '<img src="'+$c.icons.getIcon('reload',16)+'" />&nbsp;'+this.getLocalizedMessage('0023'),
				onClick: function() {
					myself.container.main.grid.model.clearCache();
					myself.container.main.grid.body.refresh();
				}
			});

			this.container.main.bottom.containerNode.appendChild(this.updateEventsButton.domNode);

			this.container.main.bottom.containerNode.appendChild(new dijit.form.Button({
				label: '<img src="'+$c.icons.getIcon('run',16)+'" />&nbsp;'+this.getLocalizedMessage('0022'),
				onClick: function() {
					myself.consolidateEvents();
				}
			}).domNode);

			this.stopFollowingSessionButton = new dijit.form.Button({
				label: '<img src="'+$c.icons.getIcon('cancel',16)+'" />&nbsp;'+this.getLocalizedMessage('0018'),
				disabled: 'disabled',
				onClick: function() {
					myself.stopFollowingSession();
				}
			});

			this.container.main.bottom.containerNode.appendChild(this.stopFollowingSessionButton.domNode);

			this.container.main.bottom.containerNode.appendChild(new dijit.form.Button({
				label: '<img src="'+$c.icons.getIcon('close',16)+'" />&nbsp;'+$c.getLocalizedMessage('10011'),
				onClick: function() {
					myself.stop();
				}
			}).domNode);

		};

		this.eventDetail = function() {
			var context = this.container.main.grid.menu.context;

			var data = this.container.main.grid.model.byId(context.cell.row.id).rawData;

			var html_table = '<table class="ym-table bordertable"><thead><tr><th>Param</th><th>Value</th></tr></thead><tbody>';

			for (i in data) {
				if ($c.Utils.inArray(i,["id","userAgent","browser","OS","sessionId"])) {
					html_table += '<tr><td>'+i+'</td><td>'+data[i]+'</td></tr>';
				}
				else if (i == "type" && $c.Utils.defined(myself.eventData[data[i]])) {
					html_table += '<tr><td>'+myself.getLocalizedMessage('0017')+'</td><td>'+myself.getLocalizedMessage(myself.eventData[data[i]].name)+'</td></tr>';
				}
				else {
					continue;
				}
			}

			html_table += '</tbody></table>';

			$c.Dialog.modal('Details',html_table,false,false);
		};

		this.startFollowingSession = function() {
			var context = this.container.main.grid.menu.context;
			var data = this.container.main.grid.model.byId(context.cell.row.id).rawData;
			console.log();
			this.container.main.grid.body.model.query({sessionId:data.sessionId});
			this.container.main.grid.body.refresh();
			this.stopFollowingSessionButton.set('disabled',false);
		};

		this.stopFollowingSession = function() {
			this.container.main.grid.body.model.query({});
			this.container.main.grid.body.refresh();
			this.stopFollowingSessionButton.set('disabled','disabled');
		};

		this.filterFieldsComposition = function(datatype, data) {
			var type,value,dtValue;
			switch(datatype) {
				case 'date':
					type = 'Date';
					dtValue = new Date(data);
					dtValue.setHours(0);
					dtValue.setMinutes(0);
					dtValue.setSeconds(0);
					dtValue.setMilliseconds(0);
					value = dtValue;
				break;
				case 'time':
					type = 'Time';
					dtFields = data.split(":");
					dtValue = new Date(2000,2,23,dtFields[0],dtFields[1],dtFields[2])
					value = dtValue;
				break;
				default:
					type = 'Text';
					value = data;
				break;
			}
			return {type:type,value:value};
		};

		this.filterByPattern = function() {
			var context = this.container.main.grid.menu.context;
			var pattern = this.filterFieldsComposition(context.cell.column.dataType(),context.cell.data());
			this.filterBy({
				colId: context.cell.column.id,
				condition: 'equal',
				type: pattern.type,
				value: pattern.value
			});
		};

		this.filterByNotPattern = function() {
			var context = this.container.main.grid.menu.context;
			this.filterBy({
				colId: context.cell.column.id,
				condition: 'notEqual',
				type: 'Text',
				value: context.cell.data()
			});
		};

		this.filterBy = function(condition) {
			var type, conditions;
			if ($c.Utils.defined(this.container.main.grid.filterBar.filterData) && this.container.main.grid.filterBar.filterData != null) {
				type = this.container.main.grid.filterBar.filterData.type;
				conditions = this.container.main.grid.filterBar.filterData.conditions.length == 0 ? [] : this.container.main.grid.filterBar.filterData.conditions;
				conditions.push(condition);
			}
			else {
				type = 'all';
				conditions = [];
				conditions.push(condition);
			}
			this.container.main.grid.filterBar.applyFilter({
				type: type,
				conditions: conditions
			});
		};

		this.consolidateEvents = function() {
			$c.Loader.start(false,this.getLocalizedMessage('0019'));
			$c.Kernel.newCall(myself.consolidateEventsCallback,{
				application: "eventviewer",
				method: "consolidate_events",
				content: {}
			});
		};

		this.consolidateEventsCallback = function(success, result) {
			if (success) {
				$c.Loader.changeContent($c.icons.getIcon(result == 0 ? 'warning' : 'apply',32),result == 0 ? myself.getLocalizedMessage('0021') : myself.getLocalizedMutableMessage('0020',[result]));
				$c.Loader.stopIn(3000);
			}
			else {
				$c.Loader.stop();
				$c.Error.minimal(result.name);
			}
		};
			
	}
	
);