/**
 * [APP DESCRIPTION]
 *
 * @package		Comodojo Core Applications
 * @author		comodojo.org
 * @copyright		__COPYRIGHT__ comodojo.org (info@comodojo.org)
 * @version		__CURRENT_VERSION__
 * @license		GPL Version 3
 */

$c.App.loadCss('imagepicker');
$d.require("dojo.data.ItemFileWriteStore");
$d.require("dijit.tree.ForestStoreModel");
$d.require('comodojo.Layout');

$c.App.load("imagepicker",

	function(pid, applicationSpace, status){
	
		this.allowMultipleSelection = false;

		this.callback = false;

		dojo.mixin(this, status);
	
		var myself = this;

		this.selectbuffer = {};

		this.gridbuffer = [];

		this.init = function(){
			
			this.treeStore = $c.Kernel.newDatastore('imagepicker', 'list_directories', {
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
				width: 500,
				height: 400,
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
				myself.listDirectory(myself.filePath, myself.fileName);
			});
			
			this.container.main.bottom.containerNode.appendChild(new dijit.form.Button({
				label: '<img src="'+$c.icons.getIcon('close',16)+'" />&nbsp;'+$c.getLocalizedMessage('10011'),
				onClick: function() {
					myself.stop();
				}
			}).domNode);
			
			this.selectButton = new dijit.form.Button({
				label: $c.getLocalizedMessage('10005')+'&nbsp;<img src="'+$c.icons.getIcon('right_arrow',16)+'" />',
				disabled: 'disabled',
				onClick: function() {
					var result,i=0;
					var keys = Object.keys(myself.selectbuffer);
					if (!myself.allowMultipleSelection) {
						result = myself.selectbuffer[keys[0]];
					}
					else {
						result = [];
						for (i in keys) {
							result.push(myself.selectbuffer[keys[i]]);
						}
					}
					if ($d.isFunction(myself.callback)) {
						myself.callback(result);
					};
					myself.stop();
				}
			});
			
			this.container.main.bottom.containerNode.appendChild(this.selectButton.domNode);
			
		};

		this.listDirectory = function(filePath, fileName) {
			this.container.main.center.destroyDescendants();
			this.gridbuffer = [];
			$c.Kernel.newCall(myself.listDirectoryCallback,{
				application: "imagepicker",
				method: "list_directory",
				content: {
					filePath: filePath,
					fileName: fileName
				}
			});
		};

		this.listDirectoryCallback  = function(success, result) {
			if (success) {
				var box;
				for (var i in result) {
					box = myself.container.newGridBox(myself.container.main.center, result[i].file_name, result[i].file_name, !result[i].thumb ? $c.icons.getIcon(result[i].icon,64).split(".")[0] : result[i].thumb);
					myself.gridboxHelper(box,result[i]);
				}
			}
			else {
				$c.Error.local(myself.container.main.center, result.code, result.name);
			}
		};

		this.gridboxHelper = function(box,result) {
			this.gridbuffer.push(box);
			box.on('click',function() {
				myself.selectElement(box,result.relative_resource);
			})
		};

		this.selectElement = function(box,resource) {
			var i;
			if ($d.hasClass(box.domNode,"imagepicker_selectedBox")) {
				$d.removeClass(box.domNode,"imagepicker_selectedBox");
				delete this.selectbuffer[box.id];
			}
			else {
				if (!this.allowMultipleSelection) {
					for (i in this.gridbuffer) {
						$d.removeClass(this.gridbuffer[i].domNode,"imagepicker_selectedBox");
					}
					this.selectbuffer = {};
				}
				$d.addClass(box.domNode,"imagepicker_selectedBox");
				this.selectbuffer[box.id] = resource;
			}
			if (Object.keys(this.selectbuffer).length > 0 ) { this.selectButton.set('disabled',false); }
			else { this.selectButton.set('disabled','disabled'); }
		};
		
	}
	
);