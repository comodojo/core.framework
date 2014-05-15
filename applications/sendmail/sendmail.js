/**
 * Send mail
 *
 * @package		Comodojo Core Applications
 * @author		comodojo.org
 * @copyright	__COPYRIGHT__ comodojo.org (info@comodojo.org)
 * @version		__CURRENT_VERSION__
 * @license		GPL Version 3
 */

$d.require("comodojo.Layout");
$d.require("comodojo.Form");

$c.App.load("sendmail",

	function(pid, applicationSpace, status){

		this.htmltemplate = '';

		//dojo.mixin(this, status);

		var myself = this;
		
		this.init = function(){

			this.container = new $c.Layout({
				modules: [],
				attachNode: applicationSpace,
				splitter: false,
				hierarchy: [{
					type: 'ContentPane',
					name: 'center',
					region: 'center',
					params: {
						style: 'overflow-y: scroll;'
					}
				},{
					type: 'ContentPane',
					name: 'bottom',
					region: 'bottom',
					cssClass: 'layout_action_pane'
				}]
			}).build();

			this.form = new $c.Form({
				modules:['TextBox','Editor','Select','Button'],
				formWidth: 'auto',
				hierarchy:[{
					name: "from",
					value: "",
					type: "TextBox",
					label: this.getLocalizedMessage('0001'),
					required: false,
				},{
					name: "to",
					value: "",
					type: "TextBox",
					label: this.getLocalizedMessage('0002'),
					required: false
				},{
					name: "cc",
					value: "",
					type: "TextBox",
					label: this.getLocalizedMessage('0003'),
					required: false
				},{
					name: "bcc",
					value: "",
					type: "TextBox",
					label: this.getLocalizedMessage('0004'),
					required: false
				},{
					name: "priority",
					value: 3,
					type: "Select",
					label: this.getLocalizedMessage('0005'),
					options: [{
						label: this.getLocalizedMessage('0007'),
						id: 5
					}, {
						label: this.getLocalizedMessage('0008'),
						id: 3
					}, {
						label: this.getLocalizedMessage('0009'),
						id: 1
					}],
					required: false
				},{
					name: "isHtmlMail",
					value: "1",
					type: "Select",
					label: this.getLocalizedMessage('0013'),
					options: [{
						label: this.getLocalizedMessage('0014'),
						id: 1
					}, {
						label: this.getLocalizedMessage('0015'),
						id: 0
					}],
					required: false
				},{
					name: "subject",
					value: "",
					type: "TextBox",
					label: this.getLocalizedMessage('0010'),
					required: false
				},{
					name: "message",
					value: "",
					type: "Editor",
					label: this.getLocalizedMessage('0011'),
					required: false
				}],
				attachNode: myself.container.main.center.containerNode
			}).build();

			this.container.main.bottom.containerNode.appendChild(new dijit.form.Button({
				label: '<img src="'+$c.icons.getIcon('apply',16)+'" />&nbsp;'+myself.getLocalizedMessage('0006'),
				style: 'float: right;',
				onClick: function() {
					myself.send();
				}
			}).domNode);

		};

		this.send = function() {
			$c.kernel.newCall(myself.sendCallback,{
				server: "sendmail",
				selector: "send",
				content: this.form.get('value')
			});
		};

		this.sendCallback = function(success, result) {
			if (success) {
				$c.Dialog.info(myself.getLocalizedMessage('0012'));
			}
			else {
				$c.Error.modal(result.code, result.name);
			}
		};

	}

);