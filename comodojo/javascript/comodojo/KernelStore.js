define("dojo/store/JsonRest", ["../_base/xhr", "../_base/lang", "../json", "../_base/declare", "./util/QueryResults" /*=====, "./api/Store" =====*/
], function(xhr, lang, JSON, declare, QueryResults /*=====, Store =====*/){

var base = null;

return declare("comodojo.KernelStore", base, {
	// summary:
	//		This is the default store to interact with comodojo.Kernel.

	constructor: function(options){
		// summary:
		//		
		declare.safeMixin(this, options);
	},

	// application: String
	//		
	application: "",

	// idProperty: String
	//		Indicates the property to use as the identity property. The values of this
	//		property should be unique.
	idProperty: "id",

	// ascendingPrefix: String
	//		The prefix to apply to sort attribute names that are ascending
	ascendingPrefix: "+",

	// descendingPrefix: String
	//		The prefix to apply to sort attribute names that are ascending
	descendingPrefix: "-",
	
	// methodGet: String
	// kernel method to call on get requests
	methodGet: 'kernel_get',

	// methodGet: String
	// kernel method to call on get requests
	methodPut: 'kernel_update',

	// methodGet: String
	// kernel method to call on get requests
	methodAdd: 'kernel_store',

	// methodGet: String
	// kernel method to call on get requests
	methodRemove: 'kernel_delete',

	getIdentity: function(object){
		// summary:
		//		Returns an object's identity
		// object: Object
		//		The object to get the identity from
		// returns: Number
		return object[this.idProperty];
	},

	get: function(id, options){
		// summary:
		//		Retrieves an object by its identity. This will trigger a GET request to the server using
		//		the url `this.target + id`.
		// returns: Object
		//		The object in the store that matches the given id.
		return xhr("POST", {
			url: 'kernel.php',
			postData: {
				store: true,
				application: this.application,
				method: this.methodGet,
				transport: 'JSON',
				id: id
			},
			handleAs: "json"
		});
	},

	put: function(object, options){
		// summary:
		//		Stores an object. This will trigger a PUT request to the server
		//		if the object has an id, otherwise it will trigger a POST request.
		// object: Object
		//		The object to store.
		// options: __PutDirectives?
		//		Additional metadata for storing the data.  Includes an "id"
		//		property if a specific id is to be used.
		// returns: dojo/_base/Deferred
		options = options || {};
		var id = ("id" in options) ? options.id : this.getIdentity(object);
		var hasId = typeof id != "undefined";

		var post_data = {
			store: true,
			application: this.application,
			method: hasId ? this.methodPut : this.methodAdd,
			transport: 'JSON'
		}

		return xhr("POST", {
			url: 'kernel.php',
			postData: lang.mixin(post_data,JSON.stringify(object)),
			handleAs: "json"
		});
	},

	add: function(object, options){
		// summary:
		//
		// object: Object
		//		The object to store.
		// options: __PutDirectives?
		//		Additional metadata for storing the data.  Includes an "id"
		//		property if a specific id is to be used.
		options = options || {};
		options.overwrite = false;
		return this.put(object, options);
	},

	remove: function(id, options){
		// summary:
		//		Deletes an object by its identity.
		// id: Number
		//		The identity to use to delete the object
		// options: __HeaderOptions?
		//		HTTP headers.
		options = options || {};
		return xhr("POST", {
			url: 'kernel.php',
			postData: {
				store: true,
				application: this.application,
				method: this.methodRemove,
				transport: 'JSON',
				id: id
			},
			handleAs: "json"
		});
	},

	query: function(query, options){
		// summary:
		//		Queries the store for objects. This will trigger a GET request to the server, with the
		//		query added as a query string.
		// query: Object
		//		The query to use for retrieving objects from the store.
		// options: __QueryOptions?
		//		The optional arguments to apply to the resultset.
		// returns: dojo/store/api/Store.QueryResults
		//		The results of the query, extended with iterative methods.
		options = options || {};

//		var headers = lang.mixin({ Accept: this.accepts }, this.headers, options.headers);
//
//		if(options.start >= 0 || options.count >= 0){
//			headers.Range = headers["X-Range"] //set X-Range for Opera since it blocks "Range" header
//				 = "items=" + (options.start || '0') + '-' +
//				(("count" in options && options.count != Infinity) ?
//					(options.count + (options.start || 0) - 1) : '');
//		}
//		var hasQuestionMark = this.target.indexOf("?") > -1;
//		if(query && typeof query == "object"){
//			query = xhr.objectToQuery(query);
//			query = query ? (hasQuestionMark ? "&" : "?") + query: "";
//		}
//		if(options && options.sort){
//			var sortParam = this.sortParam;
//			query += (query || hasQuestionMark ? "&" : "?") + (sortParam ? sortParam + '=' : "sort(");
//			for(var i = 0; i<options.sort.length; i++){
//				var sort = options.sort[i];
//				query += (i > 0 ? "," : "") + (sort.descending ? this.descendingPrefix : this.ascendingPrefix) + encodeURIComponent(sort.attribute);
//			}
//			if(!sortParam){
//				query += ")";
//			}
//		}

		var _postData = {
			store: true,
			application: this.application,
			method: this.methodGet,
			transport: 'JSON'
		};

		var results = xhr("POST", {
			url: 'kernel.php',
			postData: lang.mixin(_postData,options),
			handleAs: "json"
		});

		results.total = results.then(function(){
			var range = results.ioArgs.xhr.getResponseHeader("Content-Range");
			return range && (range = range.match(/\/(.*)/)) && +range[1];
		});
		
		return QueryResults(results);
	}
});

});