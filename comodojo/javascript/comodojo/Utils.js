define(["dojo/_base/lang"],
function(lang){

// module:
// 	comodojo/Utils

var Utils = {
	// summary:
	// description:
};
lang.setObject("comodojo.Utils", Utils);

Utils.defined = function(what) {
	// summary:
	//		Check if "what" is currently defined
	//
	// description:
	//		...
	//
	// what:
	//		...
	return (typeof(what)==="undefined") ? false : true;
};

Utils.inArray = function(what, wherein) {
	// summary:
	//		Check if a key is defined in array
	//
	// description:
	//		...
	//
	// what:
	//		The key to search
	// wherein:
	//		The array
	var key;
	for (key in wherein) {
		if (wherein[key] == what) {
			return true;
		}
	}
	return false;
};

Utils.compare = function(what, to) {
	// summary:
	//		Compare "what" to "to" and return bool
	//
	// description:
	//		...
	//
	// what:
	//		...
	// to:
	// 		...
	if ((what === undefined) && (to === undefined)) {
		return true;
	}
	if ((what === to) || (to == what)) {
		return true;
	}
	var arrayCompare = function(i1, i2){
		var l = i1.length;
		if (l != i2.length) {
			return false;
		}
		var x;
		for (x = 0; x < l; x++) {
			if (!this.compare(i1[x], i2[x])) {
				return false;
			}
		}
		return true;
	};
	var objCompare = function(i1, i2){
		if (i1 instanceof Date) {
			return (i2 instanceof Date && i1.getTime() == i2.getTime());
		}
		if (i1 === null && i2 === null) {
			return true;
		}
		else if (i1 === null || i2 === null) {
				return false;
		}
		var x;
		for (x in i1) {
			if (!(x in i2)) {
				return false;
			}
		}
		for (x in i2) {
			if (!this.compare(i1[x], i2[x])) {
				return false;
			}
		}
		return true;
	};
	if (lang.isArray(what) && lang.isArray(to)) {
		return arrayCompare(what, to);
	}
	if (typeof what == "object" && typeof to == "object") {
		return objCompare(what, to);
	}
};

Utils.dateToServer = function(clientDate){
	// summary:
	//		...
	//
	// description:
	//		...
	//
	// clientDate:
	//		...
	return (new Date(clientDate).getTime() + (comodojo.timezone*3600)) / 1000.0;
};

Utils.dateFromServer = function(serverDate) {
		// summary:
	//		...
	//
	// description:
	//		...
	//
	// clientDate:
	//		...
	var myDate = new Date ( ( serverDate - (comodojoConfig.serverTimezoneOffset*3600) + (comodojo.timezone*3600) ) * 1000.0 );
		return  myDate.getDate()+"-"+(myDate.getMonth()+1)+"-"+myDate.getFullYear()+","+myDate.getHours()+":"+myDate.getMinutes()+":"+myDate.getSeconds();
	};

return utils;

});