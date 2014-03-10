/**
 * [APP DESCRIPTION]
 *
 * @package		Comodojo Core Applications
 * @author		comodojo.org
 * @copyright		__COPYRIGHT__ comodojo.org (info@comodojo.org)
 * @version		__CURRENT_VERSION__
 * @license		GPL Version 3
 */

$d.require('comodojo.Layout');
$d.require("comodojo.Form");

$c.App.load("userprofile",

	function(pid, applicationSpace, status){
	
		//dojo.mixin(this, status);
	
		var myself = this;
		
		this.init = function(){
			
			this.container = new $c.Layout({
				attachNode: applicationSpace,
				splitter: false,
				gutters: false,
				id: pid,
				hierarchy: [{
					type: 'Content',
					name: 'left',
					region: 'left',
					params: {
						style: "width: 200px;"
					}
				},
				{
					type: 'Content',
					name: 'center',
					region: 'center',
					params: {
						style:"overflow: auto;"
					}
				}]
			}).build();

			

			this.profileForm = new $c.Form({
				modules:['TextBox','ValidationTextBox','GenderSelect','DateTextBox','EmailTextBox','OnOffSelect','Button'],
				formWidth: 'auto',
				template: "LABEL_ON_INPUT",
				hierarchy:[{
					name: "userName",
					value: "",
					type: "ValidationTextBox",
					label: this.getLocalizedMessage('0000'),
					required: true,
					readonly: true
				},{
					name: "completeName",
					value: "",
					type: "ValidationTextBox",
					label: this.getLocalizedMessage('0003'),
					required: true
				},{
					name: "gravatar",
					value: "",
					type: "OnOffSelect",
					label: this.getLocalizedMessage('0004')
				},{
					name: "email",
					value: "",
					type: "EmailTextBox",
					label: this.getLocalizedMessage('0005'),
					required: true
				},{
					name: "birthday",
					value: "",
					type: "DateTextBox",
					label: this.getLocalizedMessage('0006'),
					required: true
				},{
					name: "gender",
					value: "",
					type: "GenderSelect",
					label: this.getLocalizedMessage('0007')
				},{
					name: "url",
					value: "",
					type: "TextBox",
					label: this.getLocalizedMessage('0008'),
					required: false
				},{
	                name: "go",
	                type: "Button",
	                label: $c.getLocalizedMessage('10019'),
	                onClick: function() {
						//myself.setValues();
	                }
	            }],
				attachNode: this.container.main.center.containerNode
			}).build();
		};
			
	}
	
);
