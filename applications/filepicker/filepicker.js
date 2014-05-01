/**
 * Select file from comodojo users' home
 *
 * @package		Comodojo Core Applications
 * @author		comodojo.org
 * @copyright	__COPYRIGHT__ comodojo.org (info@comodojo.org)
 * @version		__CURRENT_VERSION__
 * @license		GPL Version 3
 */

$d.require("dojo.data.ItemFileWriteStore");
$d.require("dijit.tree.ForestStoreModel");
$d.require('comodojo.Layout');

$c.App.load("filepicker",

	function(pid, applicationSpace, status){
	
		this.accessLevel = 'reader';
		
		// callback on action end end
		this.callback = false;

		dojo.mixin(this, status);
	
		this.filePath = "/";
		this.fileName = "";

		this.fileType = "";

		var myself = this;
		
		this.init = function(){

			var level = this.accessLevel.toLowerCase();
			var method;
			switch (level) {
				case "writer":
					method = "listWriter";
					break;
				case "owner":
					method = "listOwner";
					break;
				//case "reader":
				default:
					method = "listReader";
					break;
			}
			
			this.treeStore = $c.Kernel.newDatastore('filepicker', method, {
				label: 'file_name',
				identifier: 'relative_resource'
			});

			this.treeModel = dijit.tree.ForestStoreModel({
				store: this.treeStore,
				rootLabel: "home",
				childrenAttrs: ["childs"]
			});
			
			this.container = new $c.Layout({
				modules: ['Tree'],
				attachNode: applicationSpace,
				splitter: false,
				id: pid,
				hierarchy: [{
					type: 'Tree',
					name: 'listingtree',
					region: 'center',
					params: {
						model: this.treeModel
					}
				},{
					type: 'ContentPane',
					name: 'bottom',
					region: 'bottom',
					cssClass: 'layout_action_pane',
					childrens:[]
				}]
			}).build();
			
			this.container.main.listingtree.getIconClass = function(item,opened){
				return opened ? "dijitFolderOpened" : "dijitFolderClosed";
			};
			
			this.container.main.listingtree.on('click',function(item){
				myself.filePath = item.relative_path;
				myself.fileName = item.file_name;
				//myself.fileType = item.type;
				//myself.selectButton.set('disabled',myself.fileType == 'file' ? false : 'disabled');
				myself.selectButton.set('disabled',false);
			});
			
			this.container.main.bottom.containerNode.appendChild(new dijit.form.Button({
				label: '<img src="'+$c.icons.getIcon('close',16)+'" />&nbsp;'+this.getLocalizedMessage('0002'),
				onClick: function() {
					myself.stop();
				}
			}).domNode);
			
			this.selectButton = new dijit.form.Button({
				label: this.getLocalizedMessage('0001')+'&nbsp;<img src="'+$c.icons.getIcon('right_arrow',16)+'" />',
				disabled: 'disabled',
				onClick: function() {
					//if (myself.fileType[0] != 'file') {
					//	return;
					//}
					//else {
						if ($d.isFunction(myself.callback)) {
							myself.callback({
								filePath: myself.filePath[0],
								fileName: myself.fileName[0]
							});
						};
						myself.stop();
					//}
				}
			});

			this.container.main.bottom.containerNode.appendChild(this.selectButton.domNode);
			
		};
			
	}
	
);
