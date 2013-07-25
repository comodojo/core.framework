/**
 * Set client-side locale and timezone preferences
 *
 * @package		Comodojo Core Applications
 * @author		comodojo.org
 * @copyright	__COPYRIGHT__ comodojo.org (info@comodojo.org)
 * @version		__CURRENT_VERSION__
 * @license		GPL Version 3
 */

$d.require("dojo.cookie");
$d.require("comodojo.Form");

$c.App.load("set_locale",

	function(pid, applicationSpace, status){
	
		//dojo.mixin(this, status);
	
		var myself = this;
		
		this.init = function(){
			
			$c.Kernel.newCall(myself.initCallback,{
				application: "set_locale",
				method: "get_locale_status",
				preventCache: true,
				content: {}
			});
			
		};
		
		this.initCallback = function(success, result) {
			
			if (!success) {
				$c.Error.local(applicationSpace,result.code, result.name);
			}
			else {
				myself._buildForm(result);
			}
		};
		
		this._buildForm = function(result) {
			
			var sLocales = [];
			for (var i=0; i<result.supportedLocales.length; i++) {
				sLocales.push({'label':'<img src="comodojo/icons/i18n/'+result.supportedLocales[i]+'.png">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'+result.supportedLocales[i],'id':result.supportedLocales[i]});
			}
			var sTimezones = [
				{'label':'UTC/GMT','id':0},
				{'label':'GMT -12:00','id':-12},
				{'label':'GMT -11:00','id':-11},
				{'label':'GMT -10:00','id':-10},
				{'label':'GMT -9:00','id':-9},
				{'label':'GMT -8:00','id':-8},
				{'label':'GMT -7:00','id':-7},
				{'label':'GMT -6:00','id':-6},
				{'label':'GMT -5:00','id':-5},
				{'label':'GMT -4:00','id':-4},
				{'label':'GMT -3:00','id':-3},
				{'label':'GMT -2:00','id':-2},
				{'label':'GMT -1:00','id':-1},
				{'label':'GMT +1:00','id':+1},
				{'label':'GMT +2:00','id':+2},
				{'label':'GMT +3:00','id':+3},
				{'label':'GMT +4:00','id':+4},
				{'label':'GMT +5:00','id':+5},
				{'label':'GMT +6:00','id':+6},
				{'label':'GMT +7:00','id':+7},
				{'label':'GMT +8:00','id':+8},
				{'label':'GMT +9:00','id':+9},
				{'label':'GMT +10:00','id':+10},
				{'label':'GMT +11:00','id':+11},
				{'label':'GMT +12:00','id':+12},
				{'label':'GMT +13:00','id':+13}
			];
			
			this.localeForm = new $c.Form({
				modules:['Select','Button'],
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
			myself.localeForm.fields.go.set('label',$c.getLocalizedMessage('10011'));
			myself.localeForm.fields.go.onClick = function() {myself.stop();}
		};
		
	}
	
);
