/**
 * Set client-side locale and timezone preferences
 *
 * @package		Comodojo Core Applications
 * @author		comodojo.org
 * @copyright	2012 comodojo.org (info@comodojo.org)
 * @version		__CURRENT_VERSION__
 * @license		GPL Version 3
 */

$c.loadComponent('form', ['Button','Select']);
$d.require("dojo.cookie");

$c.app.load("set_locale",

	function(pid, applicationSpace, status){
	
		//dojo.mixin(this, status);
	
		var myself = this;
		
		this.init = function(){
			
			$c.kernel.newCall(myself.initCallback,{
				application: "set_locale",
				method: "get_locale_status",
				preventCache: true,
				content: {}
			});
			
		};
		
		this.initCallback = function(success, result) {
			
			if (!success) {
				$c.error.local('10001', result, applicationSpace);
			}
			else {
				myself._buildForm(result);
			}
		};
		
		this._buildForm = function(result) {
			
			var sLocales = [];
			for (var i=0; i<result.supportedLocales.length; i++) {
				sLocales.push({'name':'<img src="comodojo/icons/i18n/'+result.supportedLocales[i]+'.png">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'+result.supportedLocales[i],'value':result.supportedLocales[i]});
			}
			var sTimezones = [
				{'name':'UTC/GMT','value':0},
				{'name':'GMT -12:00','value':-12},
				{'name':'GMT -11:00','value':-11},
				{'name':'GMT -10:00','value':-10},
				{'name':'GMT -9:00','value':-9},
				{'name':'GMT -8:00','value':-8},
				{'name':'GMT -7:00','value':-7},
				{'name':'GMT -6:00','value':-6},
				{'name':'GMT -5:00','value':-5},
				{'name':'GMT -4:00','value':-4},
				{'name':'GMT -3:00','value':-3},
				{'name':'GMT -2:00','value':-2},
				{'name':'GMT -1:00','value':-1},
				{'name':'GMT +1:00','value':+1},
				{'name':'GMT +2:00','value':+2},
				{'name':'GMT +3:00','value':+3},
				{'name':'GMT +4:00','value':+4},
				{'name':'GMT +5:00','value':+5},
				{'name':'GMT +6:00','value':+6},
				{'name':'GMT +7:00','value':+7},
				{'name':'GMT +8:00','value':+8},
				{'name':'GMT +9:00','value':+9},
				{'name':'GMT +10:00','value':+10},
				{'name':'GMT +11:00','value':+11},
				{'name':'GMT +12:00','value':+12},
				{'name':'GMT +13:00','value':+13}
			];
			
			this.localeForm = new $c.form({
				formWidth: 500,
				hierarchy:[{
					name: "note",
	                type: "info",
	                content: this.getLocalizedMessage('0000')
	            },{
	                name: "locale",
	                value: $c.locale,
	                type: "Select",
	                label: this.getLocalizedMessage('0001'),
	                required: false,
	                options:sLocales
	            }, {
	                name: "timezone",
	                value: $c.timezone,
	                type: "Select",
	                label: this.getLocalizedMessage('0002'),
	                required: false,
	                options:sTimezones
	            }, {
	                name: "go",
	                type: "Button",
	                label: $c.getLocalizedMessage('10019'),
	                onClick: function() {
						myself.setValues();
	                }
	            }],
				attachNode: applicationSpace.containerNode
			}).build();
			
		};
		
		this.setValues = function() {
			//get the form values
			var values = myself.localeForm.get('value');
			//set the client cookie
			$d.cookie("comodojo_locale", values.locale, {
		        expire: 0,
				path: document.location.pathname,
				domain: document.location.hostname
		    });
			$d.cookie("comodojo_timezone", values.timezone, {
		        expire: 0,
				path: document.location.pathname,
				domain: document.location.hostname
		    });
			//set also the temporary comodojo variables
			$c.locale = values.locale;
			$c.timezone = values.timezone;
			//return message
			myself.localeForm.fields.note.changeContent(this.getLocalizedMutableMessage('0003',['<a href="javascript:window.location.reload();">','</a>']));
		};
		
	}
	
);
