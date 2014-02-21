/**
 * [APP DESCRIPTION]
 *
 * @package		Comodojo Core Applications
 * @author		comodojo.org
 * @copyright		__COPYRIGHT__ comodojo.org (info@comodojo.org)
 * @version		__CURRENT_VERSION__
 * @license		GPL Version 3
 */

$d.require("dojo.data.ItemFileWriteStore");
$d.require("dijit.tree.ForestStoreModel");
$d.require('comodojo.Layout');

$c.App.load("imageselector",

	function(pid, applicationSpace, status){
	
		dojo.mixin(this, status);
	
		var myself = this;

		var selected = false;

		this.init = function(){
			
			this.treeStore = $c.Kernel.newDatastore('imageselector', 'list_directories', {
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
					name: 'left',
					region: 'left',
					cssClass: 'layout_action_pane',
					params: {
						model: this.treeModel,
						style: "width: 100px;"
					}
				},{
					type: 'ContentPane',
					name: 'center',
					region: 'center',
					childrens:[]
				},{
					type: 'ContentPane',
					name: 'bottom',
					region: 'bottom',
					cssClass: 'layout_action_pane',
					childrens:[]
				}]
			}).build();
			
			this.container.main.left.getIconClass = function(item,opened){
				return opened ? "dijitFolderOpened" : "dijitFolderClosed";
			};
			
			this.container.main.left.on('click',function(item){
				myself.filePath = item.relative_path;
				myself.fileName = item.file_name;
				//myself.fileType = item.type;
				//myself.selectButton.set('disabled',myself.fileType == 'file' ? false : 'disabled');
				//myself.selectButton.set('disabled',false);
				myself.listDirectory(myself.filePath, myself.fileName);
			});
			
			this.container.main.bottom.containerNode.appendChild(new dijit.form.Button({
				label: '<img src="'+$c.icons.getIcon('close',16)+'" />&nbsp;'+this.getLocalizedMessage('0002'),
				onClick: function() {
					myself.stop();
				}
			}).domNode);
			
			//this.selectButton = new dijit.form.Button({
			//	label: this.getLocalizedMessage('0001')+'&nbsp;<img src="'+$c.icons.getIcon('right_arrow',16)+'" />',
			//	disabled: 'disabled',
			//	onClick: function() {
			//		//if (myself.fileType[0] != 'file') {
			//		//	return;
			//		//}
			//		//else {
			//			if ($d.isFunction(myself.callback)) {
			//				myself.callback({
			//					filePath: myself.filePath[0],
			//					fileName: myself.fileName[0]
			//				});
			//			};
			//			myself.stop();
			//		//}
			//	}
			//});
			//
			//this.container.main.bottom.containerNode.appendChild(this.selectButton.domNode);
			
		};

		this.listDirectory = function(filePath, fileName) {
			this.container.main.center.destroyDescendants();
			$c.Kernel.newCall(myself.listDirectoryCallback,{
				application: "imageselector",
				method: "list_directory",
				content: {
					filePath: filePath,
					fileName: fileName
				}
			});
		};

		this.listDirectoryCallback  = function(success, result) {
			if (success) {
				for (var i in result) {
					myself.container.newGridBox(myself.container.main.center, result[i].file_name, result[i].file_name, !result[i].thumb ? $c.icons.getIcon(result[i].icon,64) : result[i].thumb);
				}
			}
			else {
				$c.Error.local(myself.container.main.center, result.code, result.name);
			}
		};

		this.selectElement = function() {};
			
	}
	
);