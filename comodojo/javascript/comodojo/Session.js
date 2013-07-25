define(["dojo/_base/lang","comodojo/Bus","comodojo/Kernel","comodojo/App"],
function(lang,bus,Kernel,app){

// module:
// 	comodojo/Session
	
var Session = {
	// summary:
	// description:
};
lang.setObject("comodojo.Session", Session);

//Store timestamp of last login/logout action
bus.addTimestamp('comodojo','login_logout_action');

Session._callback = function(success, result) {
	return false;
};

Session.login = function(userName, userPass, callback) {
	// summary:
	//		
	// userName: String
	//		
	// userPass: String
	//		
	// callback: Function
	//
	if (!userName || !userPass) {
		comodojo.debug('Missing user name or password');
		return false;
	}
	else if (comodojo.userRole != 0) {
		comodojo.debug('User currently logged in, disconnecting...');
		Session.logout(function() {
			Session.login(userName,userPass,callback);
		});
	}
	else {
		bus.callEvent('comodojo_login_start');
		if (lang.isFunction(callback)) {
			Session._callback = callback;
		}
		Kernel.newCall(Session.loginCallback,{
			application: "comodojo",
			method: "login",
			preventCache: true,
			content: {
				userName: userName,
				userPass: userPass
			}
		});
	}

};

Session.loginCallback = function(success, result) {
	if (!success) {
		bus.callEvent('comodojo_login_error');
		Session._callback(success, result);
	}
	else {
		// Set user info in comodojo env
		comodojo.userRole = result.userRole;
		comodojo.userName = result.userName;
		comodojo.userCompleteName = result.completeName;
		// Call event, update timestamp and return callback to application (if any)
		bus.callEvent('comodojo_login_end');
		//console.log(bus);
		bus.updateTimestamp('comodojo','login_logout_action');
		Session._callback(success, result);
		// Redefine internal callback and restart env
		Session._callback = function(success, result) { return false; };
		setTimeout(function() {
			comodojo.App.stopAll(true);
		}, 1500);
		setTimeout(function() {
			comodojo.startup();
		}, 3000);
	}
};

Session.logout = function(callback) {
	// summary:
	//		
	// callback: Function
	//
	bus.callEvent('comodojo_logout_start');
	if (lang.isFunction(callback)) {
		Session._callback = callback;
	}
	Kernel.newCall(Session.logoutCallback,{
		application: "comodojo",
		method: "logout",
		preventCache: true,
		content: {}
	});
};

Session.logoutCallback = function(success, result) {
	if (!success) {
		bus.callEvent('comodojo_logout_error');
		Session._callback(success, result);
	}
	else {
		// Set user info in comodojo env
		comodojo.userRole = 0;
		comodojo.userName = false;
		comodojo.userCompleteName = false;
		// Call event, update timestamp and return callback to application (if any)
		bus.callEvent('comodojo_logout_end');
		bus.updateTimestamp('comodojo','login_logout_action');
		Session._callback(success, result);
		// Redefine internal callback and restart env
		Session._callback = function(success, result) { return false; };
		setTimeout(function() {
			comodojo.App.stopAll(true);
		}, 1500);
		setTimeout(function() {
			comodojo.startup();
		}, 3000);
	}
};


Session.status = function() {
	// summary:
	//		
	return {
		user_logged_in: comodojo.userRole == 0 ? false : true,
		in_session_from: bus.getTimestamp('comodojo','login_logout_action')
	}
};

return Session;
	
});