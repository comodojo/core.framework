/**
 * testMailSend.js
 *
 * Comodojo test environment
 *
 * @package		Comodojo Applications
 * @author		comodojo.org
 * @copyright	2010 comodojo.org (info@comodojo.org)
 */

$c.loadComponent('form',['ValidationTextBox','Button','Select']);
$c.loadComponent('layout');
$d.require("dijit.layout.ContentPane");

$c.app.load("testAuthentication",

	function(pid, applicationSpace, status){
	
		//dojo.mixin(this, status);
	
		var myself = this;
		
		this.init = function(){
		
			this.container = new $c.layout({
				attachNode: applicationSpace.containerNode,
				template: "top,center",
				_pid: pid,
				topStyle: "height: 150px; background-color: #F0F0F0",
				centerStyle: ""
			});
			
			this.container.show();
			
			this.container.center.preventCache = true;
			
			this.buildForm();
		};
		
		this.buildForm = function() {
            
            var formHi = [{
                "name": "userName",
                "value": "",
                "type": "ValidationTextBox",
                "label": "userName",
                "required": true,
                "onClick": false
            }, {
                "name": "userPass",
                "value": "",
                "type": "ValidationTextBox",
                "label": "userPass",
                "required": true,
                "onClick": false
            }, {
                "name": "go",
                "type": "Button",
                "label": 'go',
                "onClick": function() {
					myself.authenticate();
				}
            }];
			
			this._form = new $c.form({
				hierarchy: formHi,
				attachNode: this.container.top.containerNode
			});
			
			this._form.build();
		
		};
		
		this.authenticate = function() {
			if (!this._form._form.validate()) {
				$c.dialog.info('please fill all fields first...');
			}
			else {
				var val = this._form._form.attr('value');
				this.container.center.attr('href','comodojo/applications/testAuthentication/testAuthentication.php?userName='+val.userName+'&userPass='+val.userPass);
			}
			/*
			if (!this._form._form.validate()) {
				$c.dialog.info('please fill all fields first...');
			}
			else {
				$c.kernel.newCall(myself.authenticateCallback, {
					server: "testAuthentication",
					selector: "send_pkt",
					content: this._form._form.attr('value')
				});
			}
			*/
		};
		/*
		this.authenticateCallback = function(success, result) {
			myself.container.center.containerNode.innerHTML = "";
			myself.container.center.containerNode.appendChild($d.create('div',{innerHTML: success ? "Success!" : "Failure!",style: "border: 1px solid red;"}));
			//myself.container.center.containerNode.appendChild($d.create('div',{innerHTML: result}));
			for (var o in result) {
				if (o == "debug") {
					for (var i in result[o]) {
						myself.container.center.containerNode.appendChild($d.create('div',{innerHTML: result[o][i]}));
					}
				}
				else {
					myself.container.center.containerNode.appendChild($d.create('div',{innerHTML: o + ":" + result[o]}));
				}
			}
		};
		*/
	}
	
);