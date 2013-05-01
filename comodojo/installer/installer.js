/** 
 * installer.js
 * 
 * Installer client-side helper;
 *
 * @package		Comodojo Installer
 * @author		comodojo.org
 * @copyright	__COPYRIGHT__ comodojo.org (info@comodojo.org)
 * @version		__CURRENT_VERSION__
 * @license		GPL Version 3
 */
 var installer = {
	
	moveStage: function(stageName) {
		
		if (comodojo.isSomething('installer_form').success) {
			var myForm = dijit.byId('installer_form');
			if (!myForm.validate()) {
				comodojo.error.custom('There was an error (js)!', 'invalid data in form');
				return false;
			}
			var myValues = myForm.get('value');	
		}
		else {
			var myForm, myValues;
		}
				
		comodojo.loader.start();
		
		var myBackButton = dijit.byId('backButton');
		var myNextButton = dijit.byId('nextButton');
		var myProgressBar = dijit.byId('installer_progressBar');
		
		dojo.xhrPost({
			url: "comodojo/installer/dispatcher.php?stage="+stageName,
			load: function(data){
				
				if (data.fatalError == true) {
					comodojo.error.fatal('Installer crash!','Installer backend unavailable.')
					return false;
				}
				
				else if (data.success == true) {
					comodojo.destroySomething('installer_form');
					
					myForm = new $c.form({
						hierarchy: data.formComponents,
						attachNode: dojo.byId('installerContent'),
						formId: 'installer_form'
					});
			
					myForm.build();
										
					myBackButton.set('disabled',data.backButtonDisabled);
					myBackButton.set('label',data.backButtonLabel);
					myBackButton.set('onClick',function() {
						installer.moveStage(data.backButtonStage);
					});
					
					myNextButton.set('disabled',data.nextButtonDisabled);
					myNextButton.set('label',data.nextButtonLabel);
					myNextButton.set('onClick',function() {
						installer.moveStage(data.nextButtonStage);
					});
					
					myProgressBar.update({progress: data.progressBarProgress});
					
					comodojo.loader.stop();
					
				}
				else {
					comodojo.loader.stop();
					comodojo.error.custom('Error: '+data.code, data.result);
				}
				
			},
			error: function(e){
				comodojo.loader.stop();
				comodojo.error.custom('Error', e);
			},
			content: myValues,
			handleAs: "json",
			preventCache: true
		});
		
	},
	
	initEnv: function() {
		installer._loadMessages();
		comodojo.loadScriptFile('comodojo/javascript/resources/environment.js',{
			sync:true,
			onLoad:function(){
				comodojo.loader.start();
			}
		});
		comodojo.loadScriptFile('comodojo/javascript/resources/form.js',{sync:true});
		
		dojo.ready(function(){
			comodojo.loader.stopIn(2000);
			installer.moveStage(0);
		});
	},
	
	_loadMessages: function() {
		
		var myMessagesLocaleTry = {
			url: 'comodojo/installer/i18n/i18n_installer_'+comodojo.locale+'.json',
			handleAs: 'json',
			sync: true,
			load: function(data){
				comodojo.localizedMessages = data;
				comodojo._localizedMessagesResult = true;
			},
			error: function(error){
				comodojo.debug('failed to understand your locale or localized messages file doesn\'t exists!');
				comodojo.debug('Reason was: '+ error );
				if (this.url == 'comodojo/installer/i18n/i18n_installer_en.json') {
					comodojo.debug('Standard messages localization file doesn\'t exists, messages unavailable.');
					comodojo._localizedMessagesResult = false;
				}
				else {
					comodojo.debug('Falling back to default messages locale (en).');
					this.url = 'comodojo/installer/i18n/i18n_installer_en.json';
					dojo.xhrGet(myMessagesLocaleTry);
				}
			}
		};
		dojo.xhrGet(myMessagesLocaleTry);
		
	},
	
	/*
	_checkDatabase: function() {
		
		var myForm = dijit.byId('installer_form');
		if (!myForm.validate()) {
			comodojo.error.custom('There was an error (js)!', 'invalid data in form');
			return false;
		}
		var myValues = myForm.get('value');		
						
		var myNextButton = dijit.byId('nextButton');
		
		dojo.xhrPost({
			url: "comodojo/installer/installerDispatcher.php?stage=stage_11",
			load: function(data){
				comodojo.dialog.info(data.result);
				if (data.success == true) {
					myNextButton.set('disabled',false);
				}
				else {
					myNextButton.set('disabled',true);
				}
			},
			error: function(e){
				comodojo.error.custom('There was an error (xhr)!', e);
			},
			content: myValues,
			handleAs: "json",
			preventCache: true
		});
	},
	*/

	_retryVerification: function() {
		installer.moveStage(90);
	},
	

	_goToPortal: function(href) {
		location.href = href;
	}
	
};