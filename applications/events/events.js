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
$d.require("comodojo.Layout");
$d.require("comodojo.KernelStore");
$d.require("gridx.modules.SingleSort");
$d.require("gridx.modules.Pagination");
$d.require("gridx.modules.pagination.PaginationBar");
$d.require("gridx.modules.Filter");
$d.require("gridx.modules.filter.FilterBar");
$d.require("gridx.modules.RowHeader");
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
			this.eventsMenu.addChild(this.filterByValue);
			this.eventsMenu.addChild(this.filterByNotValue);

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
							{ name: this.getLocalizedMessage('0003'), field: 'success', width: '10%', style: function(cell) {
								return "cursor: pointer; color: #fff; font-weight: bold; text-align: center; background: "+(cell.row.rawData().success == 1 ? "#8c8;" : "#c88;");
							}, formatter: function(data, rowId) {
								return '<span href="javascript:;" onClick="$c.App.byPid(\''+pid+'\').eventDetail(\''+rowId+'\')">'+myself.getLocalizedMessage("0012")+'</span>';
							}},
							{ name: this.getLocalizedMessage('0001'), width: '30%', field: 'type', formatter: function(data){
								return $c.Utils.defined(myself.eventData[data.type]) ? myself.getLocalizedMessage(myself.eventData[data.type].name) : data.type
							}},
							{ name: this.getLocalizedMessage('0002'), width: '15%', field: 'referTo'},
							{ name: this.getLocalizedMessage('0006'), width: '15%', field: 'userName'},
							{ name: this.getLocalizedMessage('0004'), width: '10%', field: 'date', dataType: 'date'},
							{ name: this.getLocalizedMessage('0005'), width: '10%', field: 'time', dataType: 'time'},
							{ name: this.getLocalizedMessage('0007'), width: '10%', field: 'host'}
						],
						style: 'padding: 0px; margin: 0px !important;',
						store: this.store,
						modules: [
							"gridx/modules/SingleSort",
							"gridx/modules/Pagination",
							"gridx/modules/pagination/PaginationBar",
							"gridx/modules/Filter",
							"gridx/modules/filter/FilterBar",
							"gridx/modules/Menu"
						]
					}
				},{
					type: 'ContentPane',
					name: 'bottom',
					region: 'bottom',
					params: {
						style: 'height:30px'
					}
				}]
			}).build();

			this.container.main.grid.menu.bind(this.eventsMenu, {
				hookPoint: 'cell',
				selected: false
			});

			//comodojo._tgrid = this.container.main.grid;

		};

		this.eventDetail = function(rowId) {
			var data = this.container.main.grid.model.byId(rowId).rawData;
			var html_table = '<div style="height: 600; width: 700;"><table class="ym-table"><tbody>';

			for (i in data) {
				html_table += '<tr><td>'+i+'</td><td>'+data[i]+'</td></tr>';
			}

			html_table += '</tbody></table></div>';

			$c.Dialog.modal('Details',html_table,false,false);
		};

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
					dtValue = new Date(context.cell.data());
					dtValue.setDate(1);
					dtValue.setMonth(0);
					dtValue.setFullYear(2000);
					value = dtValue;
				break;
				default:
					type = 'Text';
					value = context.cell.data();
				break;
			}
			console.log(value);
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