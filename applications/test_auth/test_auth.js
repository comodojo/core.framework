/**
 * Test user authentication on multiple realms
 *
 * @package		Comodojo ServerSide Core Packages
 * @author		comodojo.org
 * @copyright	__COPYRIGHT__ comodojo.org (info@comodojo.org)
 * @version		__CURRENT_VERSION__
 * @license		GPL Version 3
 */

$d.require('comodojo.Form');
$d.require('comodojo.Layout');

$c.App.load("test_auth",

	function(pid, applicationSpace, status){

		var myself = this;
		
		this.auth_template = '<h3>Comodojo authentication via realm {0}</h3><table class="ym-table bordertable"><thead><tr><th>'+'Param'+'</th><th>'+'Value'+'</th></tr></thead><tbody>{1}</tbody></table>';

		this.availableRealms = [
			{
				label: 'auto',
				id: ""
			}
		];

		this.init = function(){

			this.container = new $c.Layout({
		 		attachNode: applicationSpace,
		 		splitter: false,
		 		id: pid,
		 		hierarchy: [{
		 			type: 'Content',
		 			name: 'top',
		 			region: 'top',
		 			params: {
		 				style: "height: 200px;"
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

			$c.Kernel.newCall(myself.initCallback,{
				application: "test_auth",
				method: "realms"
			});

		};

		this.initCallback = function(success, result) {
			if (success) {
				for (var i in result) {
					myself.availableRealms.push({
						label: result[i],
						id: result[i]
					});
				}
				myself.buildForm();
			}
			else {
				$c.Error.modal(result.code,result.name);
				myself.stop();
			}
		};

		this.buildForm = function() {

			this.form = new $c.Form({
				modules: ['Button','ValidationTextBox','PasswordTextBox','Select'],
				autoFocus: true,
				hierarchy: [{
					name: "userName",
					value: "",
					type: "ValidationTextBox",
					label: "User Name",
					required: true
				},{
					name: "userPass",
					value: "",
					type: "PasswordTextBox",
					label: "User password",
					required: true
				},{
					name: "realm",
					value: "",
					type: "Select",
					label: "Auth realm",
					options: this.availableRealms,
					required: false
				},{
					name: "go",
					type: "Button",
					label: "Test auth",
					onClick: function() {
						myself.auth();
					}
				}],
				attachNode: this.container.main.top.containerNode
			}).build();

		};

		this.auth = function() {
			if (!myself.form.validate()) {
				$c.Error.minimal($c.getLocalizedMessage('10028'));
				return;
			}
			var values = myself.form.get('value');
			$c.Kernel.newCall(myself.authCallback,{
				application: "test_auth",
				method: "login",
				preventCache: true,
				content: values
			});
		};

		this.authCallback = function (success, result) {

			if (success) {

				var d = '';

				if (!result.data) {
					d = '<tr><td colspan=2 style="color:red;">Invalid credentials</td></tr>';
				}
				else {
					for (var i in result.data) {
						d += "<tr><td>"+i+"</td><td>"+result.data[i]+"</td></tr>";
					}
				}
				
				myself.container.main.center.set('content',$d.replace(myself.auth_template,[result.via,d]));

			}
			else {

				$c.Error.local(myself.container.main.center.containerNode, result.code, result.name);

			}
		};

	}

);