/**
 * Select file from comodojo users' home
 *
 * @package		Comodojo Core Applications
 * @author		comodojo.org
 * @copyright	__COPYRIGHT__ comodojo.org (info@comodojo.org)
 * @version		__CURRENT_VERSION__
 * @license		GPL Version 3
 */

$c.loadComponent('layout',['Tree']);

$c.App.load("filepicker",

	function(pid, applicationSpace, status){
	
		this.accessLevel = 'reader';
		
		// callback on action end end
		this.callback = false;
		this.closeOnCallback = false;
		
		// fire when application load/unload
		this.onApplicationStart = false;
		this.onApplicationStop = false;
		
		this._path = "/";
		this._name = "";
		
		dojo.mixin(this, status);
	
		var myself = this;
		
		this.init = function(){
			
			this.container = new $c.layout({
				attachNode: applicationSpace,
				splitter: false,
				_pid: pid,
				hierarchy: [{
					type: 'Tree',
					name: 'listingtree',
					region: 'center',
					createStore: {
						name: 'listingtree_store',
						application: 'filepicker',
						method: 'list_'+this.accessLevel,
						label: 'file_name',
						identifier: 'relative_resource'
					},
					model: {
						name: 'listingtree_model',
						rootLabel: "home",
						childrenAttrs: ["childs"]
					},
					params: {}
				},{
					type: 'ContentPane',
					name: 'bottom',
					region: 'bottom',
					params: {
						//style:"background-color: #EFEFEF; height: 30px; overflow: hidden; text-align: right;"
					},
					cssClass: 'layout_action_pane',
					childrens:[]
				}]
			}).build();
			
			this.container.main.listingtree.on('click',function(item){
				myself._path = item.relative_path;//item.root ? "/" : item.path;
				myself._name = item.file_name;//root ? "" : item.name;
			});
			
			this.container.main.bottom.containerNode.appendChild(new dijit.form.Button({
				label: '<img src="'+$c.icons.getIcon('close',16)+'" />&nbsp;'+this.getLocalizedMessage('0002'),
				onClick: function() {
					myself.stop();
				}
			}).domNode);
			
			this.container.main.bottom.containerNode.appendChild(new dijit.form.Button({
				label: this.getLocalizedMessage('0001')+'&nbsp;<img src="'+$c.icons.getIcon('right_arrow',16)+'" />',
				onClick: function() {
					if ($d.isFunction(myself.callback)) {
						myself.callback(myself._path, myself._name)
					};
					myself.stop();
				}
			}).domNode);
			
		};
			
	}
	
);
