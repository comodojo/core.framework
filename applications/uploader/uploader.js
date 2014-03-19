/**
 * [APP DESCRIPTION]
 *
 * @package		Comodojo Core Applications
 * @author		comodojo.org
 * @copyright		__COPYRIGHT__ comodojo.org (info@comodojo.org)
 * @version		__CURRENT_VERSION__
 * @license		GPL Version 3
 */

$c.App.loadCss('uploader');
$d.require("dojox.form.Uploader");
$d.require("dojox.form.uploader.FileList");

//$d.requireIf(($d.isIe <= 8),"dojox.form.uploader.plugins.IFrame");
//$d.requireIf(!($d.isIe <= 8),"dojox.form.uploader.plugins.HTML5");
//$d.require("dojox.form.uploader.plugins.IFrame");

//$d.require("dijit.form.Button");
$c.loadCss("comodojo/javascript/dojox/form/resources/UploaderFileList.css");

$d.require('comodojo.Layout');

$c.App.load("uploader",

	function(pid, applicationSpace, status){
	
		this.destination = "/"+$c.userName;

		this.overwrite = false;
		
		this.allow_multi_upload = true;

		this.allow_destination_select = true;

		this.allow_overwrite_select = true;

		dojo.mixin(this, status);
	
		var myself = this;
		
		this.init = function(){
			$c.Kernel.newCall(myself.initCallback,{
				application: "uploader",
				method: "get_max_filesize"
			});
		};

		this.initCallback = function(success, result) {
			if (success) {
				myself.buildUploader(result.max_post,result.max_file);
			}
			else {
				$c.Error.modal(result.code,result.name);
				myself.stop();
			}
		};

		this.buildUploader = function(max_post,max_file) {

			this.container = new $c.Layout({
				attachNode: applicationSpace,
				id: pid,
				hierarchy: [{
					type: 'Content',
					name: 'top',
					region: 'top',
					params: {
						style: "height: 100px;"
					}
				},{
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

			/****** TOP SECTION ******/

			this.top_uploader_fields = $d.create('div',{});
			this.top_uploader_indicator = $d.create('div',{className: 'uploader_top_indicator'});

			this.destinationField = $d.create('div',{className:'uploader_top_fields'});
			this.destinationFieldMessage = $d.create('span',{ innerHTML: this.getLocalizedMutableMessage('0002',[this.destination])});
			this.destinationField.appendChild(this.destinationFieldMessage);
			if ($c.App.isRegistered('folderpicker') && this.allow_destination_select) {
				this.destinationFieldSelector = $d.create('span',{
					className: 'uploader_top_selector',
					innerHTML: '&nbsp;('+$c.getLocalizedMessage('10040')+')',
					onclick: function() {
						$c.App.start('folderpicker',{accessLevel: 'writer', callback: myself.changeDestination});
					}
				});
				this.destinationField.appendChild(this.destinationFieldSelector);
			}
			this.top_uploader_fields.appendChild(this.destinationField);

			this.overWriteField = $d.create('div',{className:'uploader_top_fields'});
			this.overWriteFieldMessage = $d.create('span',{ innerHTML: this.getLocalizedMessage(this.overwrite ? '0003' : '0004')});
			this.overWriteField.appendChild(this.overWriteFieldMessage);
			if (this.allow_overwrite_select) {
				this.overWriteFieldSelector = $d.create('span',{
					className: 'uploader_top_selector',
					innerHTML: '&nbsp;('+$c.getLocalizedMessage('10040')+')',
					onclick: function() {
						myself.changeOverwrite();
					}
				});
				this.overWriteField.appendChild(this.overWriteFieldSelector);
			}
			this.top_uploader_fields.appendChild(this.overWriteField);

			this.maxSizeField = $d.create('div',{className:'uploader_top_fields'});
			this.maxSizeFieldMessage = $d.create('span',{ innerHTML: this.getLocalizedMutableMessage('0006',[max_file+'/'+max_post])});
			this.maxSizeField.appendChild(this.maxSizeFieldMessage);
			this.top_uploader_fields.appendChild(this.maxSizeField);

			this.convenienceForm = $d.create('form',{
				method: "post",
				action: "comodojo/global/fileUploader.php",
				id: "comodojoUploaderForm_"+pid,
				enctype: "multipart/form-data",
				className: 'uploader_top_form'
			});
			
			this.uploader = new dojox.form.Uploader({
				label: this.getLocalizedMessage('0005'),
				className: 'uploader_top_button',
				url:'upload.php',
				id:"comodojo_uploader_"+pid,
				multiple: this.allow_multi_upload,
				//force: 'iframe',
				onComplete: this.uploaderOnComplete,
				onError: this.uploaderOnError
			});
			this.convenienceForm.appendChild(this.uploader.domNode);
						
			this.destinationInput = $d.create('input',{
				type: "text",
				name: "destination",
				value: this.destination,
				style: "display:none;"
			});
			this.convenienceForm.appendChild(this.destinationInput);

			this.overwriteInput = $d.create('input',{
				type: "text",
				name: "overwrite",
				value: this.overwrite,
				style: "display:none;"
			});
			this.convenienceForm.appendChild(this.overwriteInput);

			this.top_uploader_fields.appendChild(this.convenienceForm);

			this.container.main.top.containerNode.appendChild(this.top_uploader_fields);
			this.container.main.top.containerNode.appendChild(this.top_uploader_indicator);

			/****** CENTER SECTION ******/

			this.center_uploader_list = $d.create('div',{className: 'uploader_center_list'});
			this.center_uploader_results = $d.create('div',{className: 'uploader_center_results dojoxUploaderFileList'});
			this.center_uploader_error = $d.create('div',{className: 'uploader_center_error'});

			this.uploaderFileList = new dojox.form.uploader.FileList({
				uploaderId:"comodojo_uploader_"+pid,
				headerFilename: this.getLocalizedMessage('0007'),
				headerFilesize: this.getLocalizedMessage('0008'),
			});
			this.center_uploader_list.appendChild(this.uploaderFileList.domNode);

			this.container.main.center.containerNode.appendChild(this.center_uploader_list);

			this.center_uploader_results_table = $d.create('table',{className: 'dojoxUploaderFileListTable'});
			this.center_uploader_results_thead = $d.create('thead',{});
			this.center_uploader_results_tr = $d.create('tr',{className: 'dojoxUploaderFileListHeader'});
			this.center_uploader_results_thindex = $d.create('th',{className: 'dojoxUploaderIndex', innerHTML: '#'});
			this.center_uploader_results_thname = $d.create('th',{className: 'dojoxUploaderFileName', innerHTML: this.getLocalizedMessage('0007')});
			this.center_uploader_results_thstate = $d.create('th',{className: 'dojoxUploaderFileSize', innerHTML: this.getLocalizedMessage('0009')});
			this.center_uploader_results_tbody = $d.create('tbody',{className: 'dojoxUploaderFileListContent'});
				this.center_uploader_results_tr.appendChild(this.center_uploader_results_thindex);
				this.center_uploader_results_tr.appendChild(this.center_uploader_results_thname);
				this.center_uploader_results_tr.appendChild(this.center_uploader_results_thstate);
				this.center_uploader_results_thead.appendChild(this.center_uploader_results_tr);
				this.center_uploader_results_table.appendChild(this.center_uploader_results_thead);
				this.center_uploader_results_table.appendChild(this.center_uploader_results_tbody);
			
			this.center_uploader_results.appendChild(this.center_uploader_results_table);

			this.container.main.center.containerNode.appendChild(this.center_uploader_results);
			this.container.main.center.containerNode.appendChild(this.center_uploader_error);

			/****** BOTTOM SECTION ******/

			this.closeButton = new dijit.form.Button({
				label: '<img src="'+$c.icons.getIcon('close',16)+'" />&nbsp;'+$c.getLocalizedMessage('10011'),
				style: 'float:left;',
				onClick: function() {
					myself.stop();
				}
			});
			this.container.main.bottom.containerNode.appendChild(this.closeButton.domNode);

			this.resetButton = new dijit.form.Button({
				label: '<img src="'+$c.icons.getIcon('undo',16)+'" />&nbsp;'+this.getLocalizedMessage('0001'),
				onClick: function() {
					myself.reset();
				}
			});
			this.container.main.bottom.containerNode.appendChild(this.resetButton.domNode);

			this.uploadButton = new dijit.form.Button({
				label: this.getLocalizedMessage('0000')+'&nbsp;<img src="'+$c.icons.getIcon('right_arrow',16)+'" />',
				onClick: function() {
					myself.prepareUpload();
					myself.uploader.submit();
				}
			});
			this.container.main.bottom.containerNode.appendChild(this.uploadButton.domNode);

			this.uploader.startup();

		};

		this.uploaderOnComplete = function(result) {
			if ($d.isArray(result)) {
				$d.forEach(result, function(f, i){
					var tr = $d.create('tr',{className: 'dojoxUploaderFileListContent'});
						tr.appendChild($d.create('td',{className: 'dojoxUploaderIndex '+(f.success ? 'uploader_center_upload_success' : 'uploader_center_upload_failure'), innerHTML: i+1}));
						tr.appendChild($d.create('td',{className: 'dojoxUploaderFileName '+(f.success ? 'uploader_center_upload_success' : 'uploader_center_upload_failure'), innerHTML: f.name}));
						tr.appendChild($d.create('td',{className: 'dojoxUploaderFileSize '+(f.success ? 'uploader_center_upload_success' : 'uploader_center_upload_failure'), innerHTML: f.success ? 'OK' : $c.getLocalizedError(f.code)}));
					myself.center_uploader_results_tbody.appendChild(tr);
				}, myself);
				myself.center_uploader_list.style.display = "none";
				myself.center_uploader_results.style.display = "block";
				myself.top_uploader_fields.style.display = "block";
				myself.top_uploader_indicator.style.display = "none";
				myself.resetButton.set('disabled',false);
			}
			else {
				myself.uploaderOnError(result);
			}
		};

		this.uploaderOnError = function(result) {
			$c.Error.local(myself.center_uploader_error, result.code, result.name);
			myself.center_uploader_list.style.display = "none";
			myself.center_uploader_error.style.display = "block";
			myself.top_uploader_fields.style.display = "block";
			myself.top_uploader_indicator.style.display = "none";
			myself.resetButton.set('disabled',false);
		};

		this.changeDestination = function(params) {
			myself.destination = params.filePath+params.fileName;
			myself.destinationFieldMessage.innerHTML = myself.getLocalizedMutableMessage('0002',[myself.destination]);
			myself.destinationInput.value = myself.destination;
		};

		this.changeOverwrite = function() {
			myself.overwrite = !myself.overwrite;
			myself.overwriteInput.value = myself.overwrite;
			myself.overWriteFieldMessage.innerHTML = myself.getLocalizedMessage(myself.overwrite ? '0003' : '0004')
		};
		
		this.prepareUpload = function() {
			myself.top_uploader_fields.style.display = "none";
			myself.top_uploader_indicator.style.display = "block";
			myself.uploader.set('disabled',true);
			myself.resetButton.set('disabled',true);
			myself.uploadButton.set('disabled',true);
		};

		this.reset = function() {
			myself.center_uploader_list.style.display = "block";
			myself.center_uploader_results.style.display = "none";
			myself.center_uploader_error.style.display = "none";
			myself.top_uploader_fields.style.display = "block";
			myself.top_uploader_indicator.style.display = "none";
			myself.uploader.set('disabled',false);
			myself.uploadButton.set('disabled',false);
			$c.Utils.destroyDescendants(myself.center_uploader_results_tbody);
			myself.uploader.reset();
		};

	}
	
);
