define(["dojo/on","dojo/keys","dojo/request","dojo/_base/json",
	"dojo/_base/lang","dojo/_base/array","dojo/io-query","comodojo/Bus","comodojo/Error"],
function(on,keys,request,json,lang,array,ioQuery,bus,error){

// module:
// 	comodojo/Kernel

var Kernel = {
	// summary:
	// description:
};
lang.setObject("comodojo.Kernel", Kernel);
	
Kernel.callKernel = function(httpMethod, callback, params) {
	// summary:
	//		
	this.sync = false;
	this.preventCache = false;
	this.transport = 'JSON';
	this.encodeContent = false;
	this.content = {};
	this.application = false;
	this.method = false;

	lang.mixin(this, params);

	var _content = [];
			
	_content['application'] = this.application;
	_content['method'] = this.method;
	_content['transport'] = this.transport;
	if (this.encodeContent) { 
		_content['contentEncoded'] = true;
		_content['content'] = json.toJson(this.content);
	}
	else {
		_content = lang.mixin(_content,this.content);
	}
	
	bus.callEvent("comodojo_kernel_start");

	request("kernel.php",{
		method: httpMethod,
		data: _content,
		handleAs: this.transport == 'XML' ? 'xml' : 'json',
		preventCache: this.preventCache,
		sync: this.sync
	}).then(/*load*/function(data,status){
		bus.callEvent("comodojo_kernel_end");
		if (!data.success && data.result.code==2107) {
			error.critical('lost session');
			//setTimeout(function(){
			//	location.href = comodojoConfig.siteUrl;
			//}, 5000);
		}
		else {
			try {
				callback(data.success, data.result);
			}
			catch(e) {
				error.generic('-','Kernel callback error',e);
			}
		}
	},/*error*/function(error){
		bus.callEvent("comodojo_kernel_error");
		callback(false, {code:0,name:error});
	});
};

Kernel.callKernelDatastore = function (application, method, isWriteStore, label, identifier, urlPreventCache, clearOnClose, transport, content) {
	// summary:
	//		
	var _url = 'kernel.php?datastore=true&contentIsEncoded=false&application=' + application + '&method=' + method + '&datastoreLabel=' + label + '&datastoreIdentifier=' + identifier+'&transport=' + transport;
	
	var _content = ioQuery.objectToQuery(content);
	if (_content != '') {
		_url += '&'+_content;
	}
	
	if (!isWriteStore) {
		return new dojo.data.ItemFileReadStore({
			url: _url,
			urlPreventCache: urlPreventCache,
			clearOnClose: clearOnClose
		});
	}
	else {
		return new dojo.data.ItemFileWriteStore({
			url: _url,
			urlPreventCache: urlPreventCache,
			clearOnClose: clearOnClose
		});
	}
};
	
Kernel.newCall = function(callback, params) {
	// summary:
	//		It starts a new call to kernel
	/*try{*/
		Kernel.callKernel('POST',callback, params);
	/*}
	catch(e) {
		comsole.log(e);
	}*/
	
};

Kernel.newDatastore = function(application, method, params) {
	var _params = {
		isWriteStore : false,
		label : 'name',
		identifier: 'resource',
		transport: 'JSON',
		urlPreventCache: false,
		clearOnClose: false,
		content: {}
	};
	
	lang.mixin(_params, params);
	
	return Kernel.callKernelDatastore(application, method, _params.isWriteStore, _params.label, _params.identifier, _params.urlPreventCache, _params.clearOnClose, _params.transport, _params.content);
};	
		
Kernel.subscribe = function(name, callback, params, time) {
	// summary:
	//		Start a new kenrel subscription; the service/selector requested will be called
	//		each "time" and will include a timestamp reference (params.lastCheck)
	// name: String
	//		The subscription name
	// callback: Function
	//		The function that will be called at the end of transaction
	// params: Object
	//		Params to pass to the kernel (POST)
	// time: Integer
	//		Time intervall between kernel calls
	var myTime = !time ? 10000 : time;
	bus.addTimestamp(params.application, params.method);
	params.content.lastCheck = 0;
	Kernel.newCall(callback, params);
	bus.addTrigger(name, function() {
		params.content.lastCheck = bus.getTimestampAndUpdate(params.application, params.method);
		Kernel.newCall(callback, params);
	}, myTime);
	comodojo.debug('New kernel subscription "'+name+'" signed.');
};

Kernel.unsubscribe = function(name) {
	comodojo.debug('Kernel subscription "'+name+'" removed');
	bus.removeTrigger(name);
};

return Kernel;
	
});