/**
 * [APP DESCRIPTION]
 *
 * @package		Comodojo Core Applications
 * @author		comodojo.org
 * @copyright		__COPYRIGHT__ comodojo.org (info@comodojo.org)
 * @version		__CURRENT_VERSION__
 * @license		GPL Version 3
 */

$d.require("dojox.form.Uploader");
$d.require("dojox.form.uploader.FileList");

$d.requireIf(($d.isIe <= 8),"dojox.form.uploader.plugins.IFrame");
$d.requireIf(!($d.isIe <= 8),"dojox.form.uploader.plugins.HTML5");
//$d.require("dojox.form.uploader.plugins.IFrame");

//$d.require("dijit.form.Button");
$c.loadCss("comodojo/javascript/dojox/form/resources/UploaderFileList.css");

$d.require('comodojo.Layout');

$c.App.load("uploader",

	function(pid, applicationSpace, status){
	
		this.destination = "/admin/";
		
		dojo.mixin(this, status);
	
		var myself = this;
		
		this.init = function(){

			this.container = new $c.Layout({
				attachNode: applicationSpace,
				splitter: false,
				gutters: false,
				id: pid,
				hierarchy: [{
					type: 'Content',
					name: 'top',
					region: 'top',
					params: {
						style: "height: 100px;"
					}
				},
				{
					type: 'Content',
					name: 'center',
					region: 'center',
					params: {
						style:"overflow: auto; text-align:center;"
					}
				},{
					type: 'Content',
					name: 'bottom',
					region: 'bottom',
					cssClass: 'layout_action_pane'
				}]
			}).build();

			this.convenienceForm = $d.create('form',{
				method: "post",
				action: "comodojo/global/fileUploader.php",
				id: "comodojoUploaderForm_"+pid,
				enctype: "multipart/form-data"
			});
			
			this.uploader = new dojox.form.Uploader({
				label: 'Prog Browse',
				url:'upload.php',
				id:"comodojo_uploader_"+pid,
				multiple: true
			});
			this.convenienceForm.appendChild(this.uploader.domNode);
			
			//this.destinationPath = $d.create('input',{
			//	type: "text",
			//	name: "destinationPath",
			//	value: this.destinationPath,
			//	style: "display:none;"
			//});
			//this.convenienceForm.appendChild(this.destinationPath);
			
			this.destinationField = $d.create('input',{
				type: "text",
				name: "destination",
				value: this.destination,
				style: "display:none;"
			});
			this.convenienceForm.appendChild(this.destinationField);

			this.container.main.top.containerNode.appendChild(this.convenienceForm);

			this.uploaderFileList = new dojox.form.uploader.FileList({
				uploaderId:"comodojo_uploader_"+pid
			});
			this.container.main.center.containerNode.appendChild(this.uploaderFileList.domNode);

			this.uploader.startup();

			this.uploadButton = new dijit.form.Button({
				label: 'GO',
				onClick: function() {
					myself.uploader.submit();
				}
			});
			this.container.main.bottom.containerNode.appendChild(this.uploadButton.domNode);

		};
			
	}
	
);
