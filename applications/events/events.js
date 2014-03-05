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
$d.require("gridx.modules.pagination.PaginationBar");
$d.require("gridx.modules.Filter");
$d.require("gridx.modules.filter.FilterBar");
$d.require("gridx.modules.RowHeader");
$d.require("gridx.modules.HiddenColumns");
$d.require("gridx.modules.extendedSelect.Row");
$d.require("gridx.modules.Menu");
$d.require("dijit.Menu");
$d.require("dijit.MenuItem");

$c.App.load("events",

	function(pid, applicationSpace, status){
	
		//dojo.mixin(this, status);
	
		var myself = this;
		
		this.init = function(){

			$d.request.get($c.applicationsPath + 'events' + '/resources/known_events.json', {
				handleAs: 'json',
				sync: true
			}).then(function(obj){
				myself.eventData = obj;
			},function(err){
				console.log(err);
				myself.eventData = {};
			});
			//console.log(myself.eventData);
			
			this.store = new comodojo.KernelStore({application: 'events'});

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
			//this.followSession = new dijit.MenuItem({
			//	label: myself.getLocalizedMessage('0015'),
			//	onClick: function() {
			//		myself.followSessionId();
			//	}
			//});
			this.eventsMenu.addChild(this.filterByValue);
			this.eventsMenu.addChild(this.filterByNotValue);
			this.eventsMenu.addChild(this.showEventDetails);
			//this.eventsMenu.addChild(this.followSession);

			$d.aspect.before(this.eventsMenu,'_openMyself',function(){
				var context = myself.container.main.grid.menu.context;
				var cellData = context.cell.data();
				var cellName;
				switch(context.cell.column.id) {
					case "1":
						cellName = myself.getLocalizedMessage('0003');
					break;
					case "2":
						cellName = myself.getLocalizedMessage('0001');
					break;
					case "3":
						cellName = myself.getLocalizedMessage('0002');
					break;
					case "4":
						cellName = myself.getLocalizedMessage('0006');
					break;
					case "5":
						cellName = myself.getLocalizedMessage('0004');
					break;
					case "6":
						cellName = myself.getLocalizedMessage('0005');
					break;
					case "7":
						cellName = myself.getLocalizedMessage('0007');
					break;
				}
				myself.filterByValue.attr('label',myself.getLocalizedMessage('0013')+cellName+' = '+(!cellData ? 'null' : cellData));
				myself.filterByNotValue.attr('label',myself.getLocalizedMessage('0013')+cellName+' != '+(!cellData ? 'null' : cellData));
			})


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
						structure: [
							{ name: this.getLocalizedMessage('0003'), field: 'success', dataType: 'bool', width: '5%', style: function(cell) {
								var color = cell.row.rawData().success == 1 ? "#8c8;" : "#c88;";
								return "text-align: center; background: "+color+"; color: "+color+";";
							}},
							{ name: this.getLocalizedMessage('0001'), width: '30%', field: 'type', formatter: function(data){
								return $c.Utils.defined(myself.eventData[data.type]) ? myself.getLocalizedMessage(myself.eventData[data.type].name) : data.type
							}},
							{ name: this.getLocalizedMessage('0002'), width: '17%', field: 'referTo'},
							{ name: this.getLocalizedMessage('0006'), width: '15%', field: 'userName'},
							{ name: this.getLocalizedMessage('0004'), width: '13%', field: 'date',
								dataType: 'date',
								dateParser: function (value) {
									return value;
								},
								decorator: function (cellData) {
									var dateString = cellData ? dojo.date.locale.format(new Date(cellData),
										{"selector": "date", "formatLength": "medium"}) : "";
									return "" + dateString + "";
								},
								useRawData: false
							},
							{ name: this.getLocalizedMessage('0005'), width: '10%', field: 'time',
								dataType: 'time'
								//timeParser: function (value) {
								//	return value;
								//},
								//decorator: function (cellData) {
								//	return cellData;
								//	var dateString = cellData ? dojo.date.locale.format(new Date(cellData),
								//		{"timePattern": "HHmmss", "selector": "time"/*, "formatLength": "medium"*/}) : "";
								//	return "" + dateString + "";
								//},
								//useRawData: false
							},
							{ name: this.getLocalizedMessage('0007'), width: '10%', field: 'host'}//,
							//{ name: this.getLocalizedMessage('0011'), width: '10%', field: 'sessionId'}
						],
						style: 'padding: 0px; margin: 0px !important;',
						store: this.store,
						modules: [
							"gridx/modules/SingleSort",
							"gridx/modules/RowHeader",
							"gridx/modules/Pagination",
							"gridx/modules/pagination/PaginationBar",
							"gridx/modules/Filter",
							"gridx/modules/filter/FilterBar",
							"gridx/modules/Menu",
							//"gridx/modules/HiddenColumns",
							"gridx/modules/extendedSelect/Row"
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

			this.container.main.bottom.containerNode.appendChild(new dijit.form.Button({
				label: '<img src="'+$c.icons.getIcon('close',16)+'" />&nbsp;'+$c.getLocalizedMessage('10011'),
				onClick: function() {
					myself.stop();
				}
			}).domNode);
			//this.container.main.grid.hiddenColumns.add('8');

		};

		this.eventDetail = function() {
			var context = this.container.main.grid.menu.context;
			//console.log(context);
			var data = this.container.main.grid.model.byId(context.cell.row.id).rawData;

			var html_table = '<table class="ym-table bordertable"><thead><tr><th>Param</th><th>Value</th></tr></thead><tbody>';

			for (i in data) {
				if ($c.Utils.inArray(i,["id","userAgent","browser","OS","sessionId"])) {
					html_table += '<tr><td>'+i+'</td><td>'+data[i]+'</td></tr>';
				}
				else {
					continue;
				}
			}

			html_table += '</tbody></table>';

			$c.Dialog.modal('Details',html_table,false,false);
		};

		//this.followSessionId = function() {
		//	var context = this.container.main.grid.menu.context;
		//	//console.log(context.cell.column.id);
		//	this.filterBy({
		//		colId: 8,
		//		condition: 'equal',
		//		type: 'Text',
		//		value: context.cell.row.data()[8]
		//	});
		//};

		this.filterByPattern = function() {
			var context = this.container.main.grid.menu.context;
			var type, value, dtValue, pattern;
			//console.log(context.cell.rawData());
			switch(context.cell.column.dataType()) {
				case 'date':
					type = 'Date';
					/*pattern = /(\d{4})\/(\d\d?)\/(\d\d?)/;
					pattern.test(context.cell.rawData());
					dtValue = new Date();
					dtValue.setFullYear(parseInt(RegExp.$1));
					dtValue.setMonth(parseInt(RegExp.$2)-1);*/
					dtValue = new Date(context.cell.data());
					dtValue.setHours(0);
					dtValue.setMinutes(0);
					dtValue.setSeconds(0);
					dtValue.setMilliseconds(0);
					/*value = dtValue.getTime();*/
					value = dtValue;
				break;
				case 'time':
					type = 'Time';
					//dtValue = new Date(context.cell.data());
					dtFields = context.cell.data().split(":");
					dtValue = new Date(2000,2,23,dtFields[0],dtFields[1],dtFields[2])
					//dtValue.setDate(1);
					//dtValue.setMonth(0);
					//dtValue.setFullYear(2000);
					value = dtValue;
				break;
				default:
					type = 'Text';
					value = context.cell.data();
				break;
			}
			//console.log(value);
			this.filterBy({
				colId: context.cell.column.id,
				condition: 'equal',
				type: type,
				value: value
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
			if ($c.Utils.defined(this.container.main.grid.filterBar.filterData) && this.container.main.grid.filterBar.filterData != 'null') {
				type = $c.Utils.defined(this.container.main.grid.filterBar.filterData.type) ? this.container.main.grid.filterBar.filterData.type : 'all';
				conditions = $c.Utils.defined(this.container.main.grid.filterBar.filterData.conditions) ? this.container.main.grid.filterBar.filterData.conditions : [];
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
		}
			
	}
	
);