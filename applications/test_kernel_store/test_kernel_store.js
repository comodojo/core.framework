/**
 * [APP DESCRIPTION]
 *
 * @package		Comodojo Core Applications
 * @author		comodojo.org
 * @copyright	__COPYRIGHT__ comodojo.org (info@comodojo.org)
 * @version		__CURRENT_VERSION__
 * @license		GPL Version 3
 */

$d.require("dijit.form.Button");
$d.require("comodojo.Layout");
$d.require("comodojo.KernelStore");
$d.require("gridx.modules.SingleSort");
$d.require("gridx.modules.CellWidget");
$d.require("gridx.modules.Edit");
$d.require("gridx.modules.Pagination");
$d.require("gridx.modules.pagination.PaginationBar");
$d.require("gridx.modules.Filter");
$d.require("gridx.modules.filter.FilterBar");
$d.require("gridx.modules.RowHeader");
$d.require("gridx.modules.extendedSelect.Row");

$c.App.load("test_kernel_store",

	function(pid, applicationSpace, status){
	
		//dojo.mixin(this, status);
	
		var myself = this;
		
		this.init = function(){
			
			this.store = new comodojo.KernelStore({application: 'test_kernel_store'});

			this.container = new $c.Layout({
				modules: ['Grid','GridSortSimple','GridEdit','GridPaginationBar','GridFilterBar','GridRowHeader','GridExtendedSelectRow'],
				attachNode: applicationSpace,
				splitter: false,
				id: pid,
				hierarchy: [{
					type: 'Grid',
					name: 'center',
					region: 'center',
					params: {
						structure: [
							{ name: 'id', field: 'id'},
							{ name: 'name', field: 'name', editable: true},
							{ name: 'description', field: 'description', editable: true},
							{ name: 'content', field: 'content', editable: true},
						],
						style: 'padding: 0px; margin: 0px !important;',
						store: this.store,
						selectRowTriggerOnCell: true,
						modules: [
							"gridx/modules/SingleSort",
							"gridx/modules/CellWidget",
							"gridx/modules/Edit",
							"gridx/modules/Pagination",
							"gridx/modules/pagination/PaginationBar",
							"gridx/modules/Filter",
							"gridx/modules/filter/FilterBar",
							"gridx/modules/RowHeader",
							"gridx/modules/extendedSelect/Row"
						],
						editLazySave: true
					}
				},{
					type: 'ContentPane',
					name: 'bottom',
					region: 'bottom',
					params: {
						style: "height: 30px;"
					}
				}]
			}).build();

			this.container.main.bottom.containerNode.appendChild(new dijit.form.Button({
				label: 'Delete row',
				onClick: function() {
					myself.deleteRows();
				}
			}).domNode);

			this.container.main.bottom.containerNode.appendChild(new dijit.form.Button({
				label: 'Undo last change',
				onClick: function() {
					myself.undo();
				}
			}).domNode);

			this.container.main.bottom.containerNode.appendChild(new dijit.form.Button({
				label: 'Redo last change',
				onClick: function() {
					myself.redo();
				}
			}).domNode);

			this.container.main.bottom.containerNode.appendChild(new dijit.form.Button({
				label: 'Save changes',
				onClick: function() {
					myself.save();
				}
			}).domNode);

			this.container.main.bottom.containerNode.appendChild(new dijit.form.Button({
				label: 'Add',
				onClick: function() {
					myself.add();
				}
			}).domNode);

		};

		this.deleteRows = function() {
			rows = this.container.main.center.select.row.getSelected();
			for (var i in rows) {
				this.container.main.center.store.remove(rows[i]);
			}
		};

		this.undo = function() {
			this.container.main.center.model.undo();
		};

		this.redo = function() {
			this.container.main.center.model.redo();
		};

		this.save = function() {
			this.container.main.center.model.save();
		};
		
		this.add = function() {
			this.container.main.center.store.add({
				//id:"109",
				name:"test-n",
				description:"this is a test",
				pattern:"this is a test",
				content:"test test test two",
				timestamp:"1375914416",
				date:"2013-08-07",
				userName:"admin",
				rating:2,
				refer:3,
				type:"TEST"
			});
		};

	}
	
);