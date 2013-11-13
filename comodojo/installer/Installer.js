define(["dojo/dom",
	"dojo/_base/declare",
	"dojo/dom-construct",
	"dojo/_base/window",
	"dojo/request",
	"dojo/_base/lang",
	"dijit/Dialog",
	"dijit/form/Button",
	"comodojo/Utils",
	"comodojo/Form",
	"dijit/ProgressBar"],
function(dom,
	declare,
	domConstruct,
	win,
	request,
	lang,
	dialog,
	Button,
	Utils,
	Form,
	ProgressBar
){

	// module:
	// 	installer/Installer

	var that = false;

	var installer = declare("installer.Installer", [], {

		form: false,

		loader: false,

		message: false,

		backButton: false,

		nextButton: false,

		progressBar: false,

		constructor: function(args) {

			that = this;

			this.buildDialogs();

			this.buildEnv();

			comodojo._goToPortal = this._goToPortal;

			comodojo._retryVerification = this._retryVerification;

			this.loader.stopIn(2000);

			this.moveStage(0);

		},

		moveStage: function(stageName) {

			var values;

			if (!this.form) {
				values = [];
			}
			else {
				if (!this.form.validate()) {
					this.throwError('invalid data in form');
					return;
				}
				values = this.form.get('value');
			}
					
			this.loader.start();
			
			request("comodojo/installer/dispatcher.php?stage="+stageName,{
				method: 'POST',
				data: values,
				handleAs: 'json',
				preventCache: true,
				//sync: this.sync
			}).then(/*load*/function(data) {
				//try{
					that.moveStageCallback(data);
				//}
				//catch(e){
				//	console.log(e);
				//}
			},/*error*/function(error){
				this.loader.stop();
				this.throwError(error);
			});
		},

		moveStageCallback: function(data) {
			if (data.success == true) {
				if (this.form !== false) {
					this.form.destroyRecursive();
				}
				this.form = new Form({
					modules: ['Button','TextBox','ValidationTextBox','Select','PasswordTextBox','EmailTextBox','OnOffSelect','GenderSelect'],
					hierarchy: data.formComponents,
					attachNode: dom.byId('installerContent')
				}).build();
		
				this.backButton.set('disabled',data.backButtonDisabled);
				this.backButton.set('label',data.backButtonLabel);
				this.backButton.set('onClick',function() {
					that.moveStage(data.backButtonStage);
				});
				
				this.nextButton.set('disabled',data.nextButtonDisabled);
				this.nextButton.set('label',data.nextButtonLabel);
				this.nextButton.set('onClick',function() {
					//try{
						that.moveStage(data.nextButtonStage);
					//}
					//catch(e) {
					//	console.log(e);
					//}
					
				});
				
				this.progressBar.update({progress: data.progressBarProgress});
				
				this.loader.stop();
				
			}
			else {
				this.loader.stop();
				this.throwError('('+data.code+')&nbsp;'+data.result);
			}
		},

		_retryVerification: function() {
			that.moveStage(90);
		},

		_goToPortal: function(href) {
			location.href = href;
		},

		buildEnv: function() {

			this.progressBar = new ProgressBar({
				//className: "progressBar",
				maximum:100
			},'installer_progressBar');

			this.backButton = new Button({
				className: 'backButton',
				label: ''
			},'backButton');

			this.nextButton = new Button({
				className: 'nextButton',
				label: ''
			},'nextButton');

		},

		buildDialogs: function() {
			
			this.message = new dialog({
				title: '$',
				content: '$'
			}).placeAt(win.body());

			var actionBar = domConstruct.create("div", {
				class: "dijitDialogPaneActionBar"
			}, this.message.containerNode);

			new Button({
				label: comodojo.getLocalizedMessage('10011'),
				onClick: function() {
					this.message.hide();
				}
			}).placeAt(actionBar);

			this.loader = new dialog({
				title: comodojo.getLocalizedMessage('10007'),
				content: '<div style="width:300px; height:40px; text-align: center;"><img src="comodojo/images/bar_loader.gif" /></div>',
				templateString: "<div class=\"dijitDialog\" role=\"dialog\" aria-labelledby=\"${id}_title\">\n\t<div data-dojo-attach-point=\"titleBar\" class=\"dijitDialogTitleBar\">\n\t\t<span data-dojo-attach-point=\"titleNode\" class=\"dijitDialogTitle\" id=\"${id}_title\"\n\t\t\t\trole=\"header\" level=\"1\"></span>\n\t\t<span data-dojo-attach-point=\"closeButtonNode\" title=\"${buttonCancel}\" role=\"button\" tabIndex=\"-1\">\n\t\t\t<span data-dojo-attach-point=\"closeText\" class=\"closeText\" title=\"${buttonCancel}\">&nbsp;</span>\n\t\t</span>\n\t</div>\n\t<div data-dojo-attach-point=\"containerNode\" class=\"dijitDialogPaneContent\"></div>\n</div>\n"
			}).placeAt(win.body());

			this.message.startup();
			this.loader.startup();

			this.loader.start = function() { that.loader.show(); };

			this.loader.stop = function() { that.loader.hide(); };

			this.loader.stopIn = function(timeout) { setTimeout(function(){ that.loader.stop(); }, isFinite(timeout) ? timeout : /*default timeout is 5 secs*/ 5000); };

		},

		throwError: function(error) {
			this.message.set("title", comodojo.getLocalizedMessage('10034'));
			this.message.set("content", error);
			this.message.show();
		},

		throwMessage: function(message) {
			this.message.set("title", comodojo.getLocalizedMessage('10036'));
			this.message.set("content", message);
			this.message.show();
		},

	});

	return installer;

});
	