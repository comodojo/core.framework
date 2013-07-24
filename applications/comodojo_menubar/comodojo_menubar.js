/** 
 * comodojoMenubar.js
 * 
 * The default Comodojo menubar
 *
 * @package		Comodojo Core Applications
 * @author		comodojo.org
 * @copyright	2012 comodojo.org (info@comodojo.org)
 * @version		__CURRENT_VERSION__
 * @license		GPL Version 3
 */
 $c.App.loadCss('comodojo_menubar');
 $d.require("dijit.Menu");
 $d.require("dijit.MenuBar");
 $d.require("dijit.MenuBarItem");
 $d.require("dijit.PopupMenuBarItem");
 $d.require("dijit.TooltipDialog");
 $d.require("dojox.html.styles");
 $d.require("comodojo.Form");

 $c.App.load("comodojo_menubar",

 	function(pid, applicationSpace, status){

 		this.showSessionMenu = true;

 		this.showLocalizationMenu = true;

 		this.showTaskManager = true;

 		this.showPid = false;

 		this.showExec = false;

 		$d.mixin(this,status);

 		this._menu_applications = false;
 		this._menu_system = false;
 		this._menu_info = false;
 		this._menu_other = false;
 		this._menu_devel = false;
 		this._menu_test = false;

 		var myself = this;

 		this.init = function(){

 			this.comodojoMenubar = new dijit.MenuBar({}, applicationSpace);

 			if (this.showSessionMenu !== false) {
 				this._createSessionMenu();				
 			}

 			if (this.showLocalizationMenu !== false) {
 				this._createLocalizationMenu();				
 			}

 			if (this.showTaskManager !== false) {
 				this._createTaskManager();				
 			}

			this._createMenu('comodojo');
			this._menu_comodojo.addChild(new dijit.MenuItem({label:'Comodojo '+$c.comodojoVersion, disabled:true}));
			this._menu_comodojo.addChild(new dijit.MenuSeparator());
			
			var ae;
			for (ae in $c.Bus._registeredApplications) {
				this._populateMenu(ae, $c.Bus._registeredApplications[ae].properties);
			}
			
			this.comodojoMenubar.startup();
			
			$d.place($d.create('div',{className:'comodojoMenubar_clearer', id: 'comodojoMenubar_clearer'}), $d.byId(pid), 'after');

			$c.Bus.addConnection('applicationStartLoading','applicationStartLoading',function(){
				myself.indicator.set('label','<img id="comodojoMenubar_innerIndicator" src="comodojo/images/small_loader.gif" style="width:13px;height:13px;">');
			});
			$c.Bus.addConnection('applicationFinishLoading','applicationFinishLoading',function(){
				myself.indicator.set('label','<img src="'+$c.icons.getIcon('info',16)+'" alt="'+myself.getLocalizedMessage('0025')+'" style="width:13px;height:13px;" />');
			});
			$c.Bus.addConnection('applicationGotError','applicationGotError',function(){
				myself.indicator.set('label','<img id="comodojoMenubar_innerIndicator" src="comodojo/icons/16x16/warning.png"> style="width:13px;height:13px;"');
			});
			$c.Bus.addConnection('comodojoMenubar_applicationsRunningTableChange','applicationsRunningTableChange',function(){
				myself.populateDock();
			});
			
			dojo.connect(this.comodojoMenubar, 'uninitialize', function(){
				$c.Bus.removeConnection("applicationStartLoading");
				$c.Bus.removeConnection("applicationFinishLoading");
				$c.Bus.removeConnection("applicationGotError");
				$c.Bus.removeConnection('comodojoMenubar_applicationsRunningTableChange');
			});
			
		};
		
		this._createTaskManager = function() {
			
			this.dock = new dijit.TooltipDialog({});
			$d.addClass(this.dock.domNode,'comodojo_menubar_docker_dock');
			$d.addClass(this.dock.containerNode,'comodojo_menubar_docker_dock_inner');
			
			this.indicator = new dijit.PopupMenuBarItem({
				label: '<img src="'+$c.icons.getIcon('info',16)+'" alt="'+this.getLocalizedMessage('0025')+'" style="width:13px;height:13px;" />',
				popup: this.dock
			});
			$d.addClass(this.indicator.domNode,'comodojo_menubar_docker_indicator');
			
			this.comodojoMenubar.domNode.appendChild(this.indicator.domNode);
			
			this.populateDock();
			
		};
		
		this._createLocalizationMenu = function() {
			
			var langButton = new dijit.MenuBarItem({
				label: '<img src="'+$c.icons.getLocaleIcon()+'" style="width:15px;height:13px;" />',
				iconClass: 'dijitIcon',
				onClick: function(){
					$c.App.start('set_locale');
				},
				disabled: !$c.App.isRegistered('set_locale')
			});
			$d.addClass(langButton.domNode,'comodojo_menubar_docker_indicator');
			this.comodojoMenubar.domNode.appendChild(langButton.domNode);
			
		};
		
		this._createSessionMenu = function() {
			
			var content = !$c.userRole ? this._createLoginForm() : this._createUserInfo();
			
			this.sessionMenuButton = new dijit.PopupMenuBarItem({
				label: !$c.userRole ? ((!comodojoConfig.registrationMode || !$c.App.isRegistered('usersubscription')) ? this.getLocalizedMessage('0020') : this.getLocalizedMessage('0019') ) : $c.userName,
				popup: content
			});
			$d.addClass(this.sessionMenuButton.domNode,'comodojo_menubar_session_menu');
			
			this.comodojoMenubar.addChild(this.sessionMenuButton);
			
		};
		
		this._createLoginForm = function() {
			
			var container = new dijit.TooltipDialog({});
			$d.addClass(container.domNode,'comodojo_menubar_session_menu_container');
			
			container.on("open", function(){ myself.sessionLoginForm.fields.userName.focus(); });
			
			this.sessionLoginForm = new $c.Form({
				modules: ['Button','PasswordTextBox','ValidationTextBox'],
				formWidth: 400,
				hierarchy:[{
					name: "session_login_form_info",
					type: "info",
					content: this.getLocalizedMessage('0026')
				},{
					name: "userName",
					value: "",
					type: "ValidationTextBox",
					label: this.getLocalizedMessage('0022'),
					required: true
				}, {
					name: "userPass",
					value: "",
					type: "PasswordTextBox",
					label: this.getLocalizedMessage('0023'),
					required: true
				}, {
					name: "login",
					type: "Button",
					label: this.getLocalizedMessage('0020'),
					onClick: function() {
						myself.tryLogin();
					}
				},{
					name: "pwd_recover",
					type: "info",
					content: '<a href="javascript:;" onClick="$c.App.start(\'pwdrecover\')">'+this.getLocalizedMessage('0024')+'</a>',
					hidden: $c.App.isRegistered('pwdrecover') ? false : true
				},{
					name: "usr_registration",
					type: "info",
					content: $c.App.isRegistered('regrecover') ? (this.getLocalizedMutableMessage('0036',['<a href="javascript:;" onClick="$c.App.start(\'usersubscription\')">','</a>','<a href="javascript:;" onClick="$c.App.start(\'regrecover\')">','</a>'])) : ('<a href="javascript:;" onClick="$c.App.start(\'usersubscription\')">'+this.getLocalizedMessage('0021')+'</a>'),
					hidden: ($c.App.isRegistered('usersubscription') && comodojoConfig.registrationMode != 0) ? false : true
				}],
				attachNode: container.containerNode
			}).build();
			
			this.sessionLoginForm.fields.userName.on("keypress", function(key){ if (key.keyCode == '13') {myself.sessionLoginForm.fields.userPass.focus();} });
			this.sessionLoginForm.fields.userPass.on("keypress", function(key){ if (key.keyCode == '13') {myself.sessionLoginForm.fields.login.onClick();} });
			
			return container;
			
		};
		
		this._createUserInfo = function() {
			
			this.userInfoContainer = new dijit.TooltipDialog({}); 
			$d.addClass(this.userInfoContainer.domNode,'comodojo_menubar_session_menu_container');
			
			$c.kernel.newCall(myself._createUserInfoCallback,{
				application: "comodojo_menubar",
				method: "get_user_info",
				preventCache: true,
				content: {}
			});
			
			return this.userInfoContainer;
			
		};
		
		this._createUserInfoCallback = function(success, result) {
			
			if (!success) {
				myself.userInfoContainer.containerNode.appendChild($d.create('div',{innerHTML: myself.getLocalizedMessage('0031'), className: 'box error comodojoMenubar_userAction_container'}));
			}
			else {
				
				var userInfoContainer = $d.create('div',{className: 'comodojoMenubar_userInfo_container'});
				
				userInfoContainer.appendChild($d.create('div', {
					className: "comodojoMenubar_userInfo_name",
					innerHTML: result.url == null ? result.completeName : ('<a href="'+result.url+'" target="_blank">'+result.completeName+'</a>')
				}));

				userInfoContainer.appendChild($d.create('div', {
					className: "comodojoMenubar_userInfo_avatar",
					style: 'background-image: url('+result.avatar+'); background-repeat: no-repeat; background-position: center center; width: 64px; height: 64px;'
				}));
				
				var userActionContainer = $d.create('div',{className: 'comodojoMenubar_userAction_container'});
				
				if ($c.App.isRegistered('profile_editor')){
					userActionContainer.appendChild($d.create('button',{
						className: 'ym-button ym-edit comodojoMenubar_userAction_button',
						onclick: function() {
							$c.App.start('profile_editor');
						},
						innerHTML: myself.getLocalizedMessage('0033')
					}));
				}
				
				if ($c.App.isRegistered('set_locale')){
					userActionContainer.appendChild($d.create('button',{
						className: 'ym-button ym-like comodojoMenubar_userAction_button',
						onclick: function() {
							$c.App.start('set_locale');
						},
						innerHTML: myself.getLocalizedMessage('0034')
					}));
				}
				
				if ($c.App.isRegistered('chpasswd')){
					userActionContainer.appendChild($d.create('button',{
						className: 'ym-button ym-edit comodojoMenubar_userAction_button',
						onclick: function() {
							$c.App.start('chpasswd');
						},
						innerHTML: myself.getLocalizedMessage('0035')
					}));
				}
				
				userActionContainer.appendChild($d.create('button',{
					className: 'ym-button ym-next comodojoMenubar_userAction_button',
					onclick: function() {
						$c.session.logout();
					},
					innerHTML: myself.getLocalizedMessage('0032')
				}));
				
				myself.userInfoContainer.containerNode.appendChild(userInfoContainer);
				myself.userInfoContainer.containerNode.appendChild(userActionContainer);
				
			}

		};
		
		this._createMenu = function(menuType){

			var myLabel, myPopupMenu, myCssIcon, myCssIconClass;
			
			switch (menuType) {
				case 'comodojo': myLabel = '<img src="'+$c.icons.getIcon('logo',16)+'" style="width:13px;height:13px;" />';break;
				case 'applications': myLabel = this.getLocalizedMessage('0000');break;
				case 'system': myLabel = this.getLocalizedMessage('0001'); break;
				case 'info': myLabel = this.getLocalizedMessage('0002'); break;
				case 'other': myLabel = this.getLocalizedMessage('0007'); break;
				case 'devel': myLabel = this.getLocalizedMessage('0006'); break;
				case 'test': myLabel = this.getLocalizedMessage('0018'); break;

			}
			
			this['_menu_'+menuType] = new dijit.Menu({});
			
			myPopupMenu = new dijit.PopupMenuBarItem({
				label: myLabel,
				popup: this['_menu_'+menuType]
			});

			this.comodojoMenubar.addChild(myPopupMenu);
			
		};
		
		this._appendStyleSheet = function(appExec, iconSrc) {

			if (iconSrc == 'default'){
				dojox.html.insertCssRule('.comodojo_menu_applications_'+appExec, 'background-image: url(comodojo/icons/16x16/run.png); background-repeat: no-repeat; background-position: center center; width: 16px; height: 16px;');
			}
			else if (iconSrc == 'self') {
				dojox.html.insertCssRule('.comodojo_menu_applications_'+appExec, 'background-image: url('+$c.icons.getSelfIcon(appExec,16)+'); background-repeat: no-repeat; background-position: center center; width: 16px; height: 16px;');
			}
			else {
				dojox.html.insertCssRule('.comodojo_menu_applications_'+appExec, 'background-image: url('+$c.icons.getIcon(iconSrc,16)+'); background-repeat: no-repeat; background-position: center center; width: 16px; height: 16px;');
			}
			
		};
		
		this._populateMenu = function(appExec, appProp) {
			
			if (appProp.runMode == 'user') {

				this._appendStyleSheet(appExec, appProp.iconSrc);
				
				var parentMenu;
				
				switch (appProp.parentMenu) {
					
					case "comodojo":
					parentMenu = this._menu_comodojo;
					break;
					
					case "applications":
					if (!this._menu_applications) { this._createMenu('applications'); }
					parentMenu = this._menu_applications;
					break;
					
					case "system":
					if (!this._menu_system) { this._createMenu('system'); }
					parentMenu = this._menu_system;
					break;
					
					case "info":
					if (!this._menu_info) { this._createMenu('info'); }
					parentMenu = this._menu_info;
					break;
					
					case "devel":
					if (!this._menu_devel) { this._createMenu('devel'); }
					parentMenu = this._menu_devel;
					break;
					
					case "test":
					if (!this._menu_test) { this._createMenu('test'); }
					parentMenu = this._menu_test;
					break;
					
					default:
					if (!this._menu_other) { this._createMenu('other'); }
					parentMenu = this._menu_other;
					break;
					
				}
				
				parentMenu.addChild(new dijit.MenuItem({
					label: appProp.title,
					onClick: function(){
						$c.App.start(appExec);
					},
					iconClass: 'comodojo_menu_applications_'+appExec
				}));
				
			}
			
		};

		this.populateDock = function() {

			this.dock.set("content","");
			var cont, boxCont, count=0, i=0;
			
			for (i in comodojo.Bus._runningApplications) {
				
				if (comodojo.Bus._runningApplications[i][3] == "system") { continue; }
				
				else {
					cont = $d.create('div', { className: "comodojoMenubar_dockAppContainer"});
					
					boxCont = $d.create('div', { className: "comodojoMenubar_dockAppTextBox"});
					
					boxCont.appendChild($d.create('div', {
						className: !dijit.byId(comodojo.Bus._runningApplications[i][0])._isDocked ? "comodojoMenubar_dockAppName_visible" : "comodojoMenubar_dockAppName_hidden",
						innerHTML: comodojo.Bus._runningApplications[i][2]
					}));
					
					if (this.showExec !== false) {
						boxCont.appendChild($d.create('div', {
							className: "comodojoMenubar_dockAppExec",
							innerHTML: (this.showPid ? '('+ comodojo.Bus._runningApplications[i][0] + ') ' : "") + comodojo.Bus._runningApplications[i][1]
						}));
					}

					boxCont.appendChild($d.create('img', {
						src: $c.icons.getIcon('right_arrow',16),
						className: "comodojoMenubar_dockAppSwitch",
						onClick: "comodojo.app.setFocus('"+comodojo.Bus._runningApplications[i][0]+"');",
						alt: this.getLocalizedMessage('0016')
					}));
					
					boxCont.appendChild($d.create('img', {
						src: $c.icons.getIcon('cancel',16),
						className: "comodojoMenubar_dockAppTerminate",
						onClick: "comodojo.app.stop('"+comodojo.Bus._runningApplications[i][0]+"');",
						alt: this.getLocalizedMessage('0017')
					}));
					
					cont.appendChild(boxCont);

					if (comodojo.Bus._registeredApplications[comodojo.Bus._runningApplications[i][1]].properties.iconSrc == 'default'){
						cont.appendChild($d.create('img', { className: "comodojoMenubar_dockAppImage", src: $c.icons.getIcon('run',64), alt: comodojo.Bus._runningApplications[i][1]}));
					}
					else if (comodojo.Bus._registeredApplications[comodojo.Bus._runningApplications[i][1]].properties.iconSrc == 'self') {
						cont.appendChild($d.create('img', { className: "comodojoMenubar_dockAppImage", src: $c.icons.getSelfIcon(comodojo.Bus._runningApplications[i][1],64), alt: comodojo.Bus._runningApplications[i][1]}));
					}
					else {
						cont.appendChild($d.create('img', { className: "comodojoMenubar_dockAppImage", src: $c.icons.getIcon(comodojo.Bus._registeredApplications[comodojo.Bus._runningApplications[i][1]].properties.iconSrc,64), alt: comodojo.Bus._runningApplications[i][1]}));
					}

					this.dock.containerNode.appendChild(cont);

					count++;
					
				}
				
			}
			
			if (!count) { this.dock.set("content",this.getLocalizedMessage("0015")); }

		};
		
		this.tryLogin = function() {
			if (!myself.sessionLoginForm.validate()) {
				myself.sessionLoginForm.fields.session_login_form_info.changeType('warning');
				myself.sessionLoginForm.fields.session_login_form_info.changeContent(myself.getLocalizedMessage('0027'));
			}
			else {
				myself.sessionLoginForm.fields.session_login_form_info.changeType('info');
				myself.sessionLoginForm.fields.session_login_form_info.changeContent(myself.getLocalizedMessage('0028'));
				var values = myself.sessionLoginForm.get('value');
				$c.session.login(values.userName, values.userPass, myself.tryLoginCallback);
			}
		};
		
		this.tryLoginCallback = function(success, result) {
			if (success) {
				myself.sessionLoginForm.fields.session_login_form_info.changeType('success');
				myself.sessionLoginForm.fields.session_login_form_info.changeContent(myself.getLocalizedMessage('0029'));
			}
			else {
				myself.sessionLoginForm.fields.session_login_form_info.changeType('error');
				myself.sessionLoginForm.fields.session_login_form_info.changeContent(myself.getLocalizedMessage('0030'));
			}
		};
		
	}
	
);