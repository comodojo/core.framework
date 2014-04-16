/**
 * testMailSend.js
 *
 * Comodojo test environment
 *
 * @package		Comodojo Applications
 * @author		comodojo.org
 * @copyright	2010 comodojo.org (info@comodojo.org)
 */

//$c.app.loadCss('testMailSend');
$c.loadComponent('form',['TextBox','Editor','Button','Select','Textarea']);
$d.require("dijit.layout.ContentPane");

$c.app.load("testMailSend",

	function(pid, applicationSpace, status){
	
		//dojo.mixin(this, status);
	
		var myself = this;
		
		this.init = function(){
		
			this._checkMailEngineActive();
			
		};
		
		this._checkMailEngineActive = function() {
			$c.kernel.newCall(myself._checkMailEngineActiveCallback,{
				server: "testMailSend",
				selector: "check_engine",
				content: {}
			});
		};
		
		this._checkMailEngineActiveCallback = function(success, result) {
			if (!success) {
				$c.error.global('10001','unknown error');
				$c.app.stop(pid);
			}
			else {
				if (!result) {
					$c.error.global('10001', myself.getLocalizedMessage('0000'));
					$c.app.stop(pid);
				}
				else {
					myself.buildForm();
				}
			}
		};
		
		this.buildForm = function() {
            
            var formHi = [{
                "name": "from",
                "value": "",
                "type": "TextBox",
                "label": this.getLocalizedMessage('0001'),
                "required": false,
                "onClick": false
            }, {
                "name": "to",
                "value": "",
                "type": "TextBox",
                "label": this.getLocalizedMessage('0002'),
                "required": false,
                "onClick": false
            }, {
                "name": "cc",
                "value": "",
                "type": "TextBox",
                "label": this.getLocalizedMessage('0003'),
                "required": false,
                "onClick": false
            }, {
                "name": "bcc",
                "value": "",
                "type": "TextBox",
                "label": this.getLocalizedMessage('0004'),
                "required": false,
                "onClick": false
            }, {
                "name": "subject",
                "value": "",
                "type": "TextBox",
                "label": this.getLocalizedMessage('0010'),
                "required": false,
                "onClick": false
            },{
                "name": "priority",
                "value": 3,
                "type": "Select",
                "label": this.getLocalizedMessage('0005'),
                "options": [{
                    "name": this.getLocalizedMessage('0007'),
                    "value": 5
                }, {
                    "name": this.getLocalizedMessage('0008'),
                    "value": 3
                }, {
                    "name": this.getLocalizedMessage('0009'),
                    "value": 1
                }],
                "required": false,
                "onClick": false
            },{
                "name": "isHtmlMail",
                "value": "1",
                "type": "Select",
                "label": this.getLocalizedMessage('0013'),
                "options": [{
                    "name": this.getLocalizedMessage('0014'),
                    "value": 1
                }, {
                    "name": this.getLocalizedMessage('0015'),
                    "value": 0
                }],
                "required": false,
                "onChange": function() {
					//console.log(this.value);
				}
            }, {
                "name": "message",
                "value": "",
                "type": "Editor",
                "label": this.getLocalizedMessage('0011'),
                "required": false,
                "onClick": false
            }, {
                "name": "Send",
                "type": "Button",
                "label": this.getLocalizedMessage('0006'),
                "onClick": function() {
					myself.sendMail();
				}
            }];
			
			this._form = new $c.form({
				hierarchy: formHi,
				attachNode: applicationSpace.containerNode
			});
			
			this._form.build();
		
		};
		
		this.sendMail = function() {
			$c.kernel.newCall(myself.sendMailCallback,{
				server: "testMailSend",
				selector: "send_mail",
				content: this._form._form.attr('value')
			});
		};
		
		this.sendMailCallback = function(success, result) {
			if (!success) {
				$c.error.global('99999',result);
			}
			else {
				$c.dialog.info(myself.getLocalizedMessage('0012'));
			}
		};
		
	}
	
);