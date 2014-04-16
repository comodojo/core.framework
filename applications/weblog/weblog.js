/**
 * testForms.js
 *
 * Comodojo test environment
 *
 * @package		Comodojo Applications
 * @author		comodojo.org
 * @copyright	2010 comodojo.org (info@comodojo.org)
 */

$c.loadComponent('form', ['Button',
	'CheckBox', 
	'PasswordTextBox', 
	'FilteringSelect', 
	'ValidationTextBox', 
	//'EmailTextBox', 
	//'TimeTextBox', 
	//'ToggleButton', 
	'TextBox', 
	//'NumberTextBox', 
	//'Textarea', 
	'Select', 
	'OnOffSelect', 
	//'GenderSelect', 
	'Editor',
	//'SmallEditor'
]);
$d.require("dijit.layout.ContentPane");
$d.require("dijit.layout.TabContainer");

$c.app.load("testMetaWeblog",

	function(pid, applicationSpace, status){
	
		//dojo.mixin(this, status);
	
		var myself = this;
		
		this.subForm_editPost = false;
	
		this.init = function(){
		
			this.container = new $j.layout.TabContainer({
				tabStrip: true,
				useMenu: false,
				useSlider: false
			});
			
			applicationSpace.containerNode.appendChild(this.container.domNode);
			
			this.container.startup();
		
			this.tab_get_posts = new $j.layout.ContentPane({
				id: 'tab_get_posts_'+pid,
				title: 'get posts'//this.getLocalizedMessage('0001')
			});
			this.tab_get_post = new $j.layout.ContentPane({
				id: 'tab_get_post_'+pid,
				title: 'get post'//this.getLocalizedMessage('0001')
			});
			this.tab_get_categories = new $j.layout.ContentPane({
				id: 'tab_get_categories_'+pid,
				title: 'get categories'//this.getLocalizedMessage('0001')
			});
			this.tab_write_post = new $j.layout.ContentPane({
				id: 'tab_write_post_'+pid,
				title: 'write posts'//this.getLocalizedMessage('0001')
			});
			this.tab_edit_post = new $j.layout.ContentPane({
				id: 'tab_edit_post_'+pid,
				title: 'Edit posts'//this.getLocalizedMessage('0001')
			});
			
			this.container.addChild(this.tab_get_posts);
			this.container.addChild(this.tab_get_post);
			this.container.addChild(this.tab_get_categories);
			this.container.addChild(this.tab_write_post);
			this.container.addChild(this.tab_edit_post);
			
			this._buildWritePostTab();
			this._buildGetPostsTab();
			this._buildGetPostTab();
			this._buildEditPostTab();
			this._buildGetCategoriesTab();
			
		};
		
		this._buildWritePostTab = function() {
			this.form_writePost = new $c.form({
				hierarchy: [{
					"name": "blog_url",
					"value": "",
					"type": "ValidationTextBox",
					"label": "blog rpc url",
					"required": true
				},{
					"name": "blog_user",
					"value": "",
					"type": "ValidationTextBox",
					"label": "blog Username",
					"required": true
				},{
					"name": "blog_pass",
					"value": "",
					"type": "PasswordTextBox",
					"label": "blog User Password",
					"required": true
				},{
					"name": "title",
					"value": "",
					"type": "ValidationTextBox",
					"label": "Post title",
					"required": true
				},{
					"name": "description",
					"value": "",
					"type": "Editor",
					"label": "Post content",
					"required": true
				},{
					"name": "mt_text_more",
					"value": "",
					"type": "Editor",
					"label": "More text"
				},{
					"name": "categories",
					"value": "",
					"type": "TextBox",
					"label": "Topics"
				},{
					"name": "mt_keywords",
					"value": "",
					"type": "TextBox",
					"label": "Tags"
				},{
					"name": "publish",
					"value": 1,
					"type": "OnOffSelect",
					"label": "Publish post"
				},{
					"name": "sticky",
					"value": 0,
					"type": "OnOffSelect",
					"label": "Sticky post"
				},{
	                "name": "go",
	                "type": "Button",
	                "label": "Publish",
	                "onClick": function() {
						myself.pushPost();
					}
	            }],
				attachNode: this.tab_write_post.containerNode
			});
			this.form_writePost.build();
		};
		
		this.pushPost = function() {
			if (!this.form_writePost._form.validate()) {
				$c.dialog.info('Invalid data in form, please check.');
			}
			else {
				$c.kernel.newCall(myself.pushPostCallback,{
					server: "testMetaWeblog",
					selector: "write_post",
					content: this.form_writePost._form.get('value')
				});
			}
		};
		
		this.pushPostCallback = function(success, result) {
			if (success) {
				$c.dialog.info('<p>Success!</p><pre>'+result+'</pre>');
			}
			else {
				$c.dialog.info('<p>Failure!</p><pre>'+result+'</pre>');
			}
		};
		
		this._buildGetPostTab = function() {
			this.form_getPost = new $c.form({
				hierarchy: [{
					"name": "blog_url",
					"value": "",
					"type": "ValidationTextBox",
					"label": "blog rpc url",
					"required": true
				},{
					"name": "blog_user",
					"value": "",
					"type": "ValidationTextBox",
					"label": "blog Username",
					"required": true
				},{
					"name": "blog_pass",
					"value": "",
					"type": "PasswordTextBox",
					"label": "blog User Password",
					"required": true
				},{
					"name": "postId",
					"value": "",
					"type": "TextBox",
					"label": "Postid"
				},{
	                "name": "retrieve",
	                "type": "Button",
	                "label": "Get Post",
	                "onClick": function() {
						myself.getPost();
					}
	            }],
				attachNode: this.tab_get_post.containerNode
			});
			this.form_getPost.build();
		};
		
		this.getPost = function() {
			if (!this.form_getPost._form.validate()) {
				$c.dialog.info('Invalid data in form, please check.');
			}
			else {
				$c.kernel.newCall(myself.getPostCallback,{
					server: "testMetaWeblog",
					selector: "get_post",
					content: this.form_getPost._form.get('value')
				});
			}
		};
		
		this.getPostCallback = function(success, result) {
			if (success) {
				var inner = '<div style="max-heigth:400px; width:400px; overflow: auto;">';
				inner += '<h4>Post n°<a href="'+result.permaLink+'" target="_blank">'+result.postid+'</a>, title: '+result.title+' ('+result.post_status+')</h4><h5>Created by user (id) '+result.userid+' on '+$c.date.fromServer(result.dateCreated.timestamp)+'</h5><p>'+result.description+result.mt_text_more+'</p><hr>';
				inner += '</div>';
				$c.dialog.info('<h3>Success!</h3>'+inner);
			}
			else {
				$c.dialog.info('<p>Failure!</p><pre>'+result+'</pre>');
			}
		};
		
		this._buildEditPostTab = function() {
			this.form_editPost = new $c.form({
				hierarchy: [{
					"name": "blog_url",
					"value": "",
					"type": "ValidationTextBox",
					"label": "blog rpc url",
					"required": true
				},{
					"name": "blog_user",
					"value": "",
					"type": "ValidationTextBox",
					"label": "blog Username",
					"required": true
				},{
					"name": "blog_pass",
					"value": "",
					"type": "PasswordTextBox",
					"label": "blog User Password",
					"required": true
				},{
					"name": "postId",
					"value": "",
					"type": "TextBox",
					"label": "Postid"
				},{
	                "name": "retrieve",
	                "type": "Button",
	                "label": "Get Post",
	                "onClick": function() {
						myself.getPostForEdit();
					}
	            }],
				attachNode: this.tab_edit_post.containerNode
			});
			this.form_editPost.build();
		};
		
		this.getPostForEdit = function() {
			if (!this.form_editPost._form.validate()) {
				$c.dialog.info('Invalid data in form, please check.');
			}
			else {
				$c.kernel.newCall(myself.getPostForEditCallback,{
					server: "testMetaWeblog",
					selector: "get_post",
					content: this.form_editPost._form.get('value')
				});
			}
		};
		
		this.getPostForEditCallback = function(success, result) {
			if (success) {
				myself._buildEdidPostSubTab(result);
			}
			else {
				$c.dialog.info('<p>Failure!</p><pre>'+result+'</pre>');
			}
		};
		
		this._buildEdidPostSubTab = function(values) {
			if (this.subForm_editPost != false) {
				this.subForm_editPost._form.destroyRecursive();
			}
			this.subForm_editPost = new $c.form({
				hierarchy: [
				{
					"name": "title",
					"value": values.title,
					"type": "ValidationTextBox",
					"label": "Post title",
					"required": false
				},{
					"name": "description",
					"value": !values.description ? "" : values.description,
					"type": "Editor",
					"label": "Post content",
					"required": false
				},{
					"name": "mt_text_more",
					"value": !values.mt_text_more ? "" : values.mt_text_more,
					"type": "Editor",
					"label": "More text"
				},{
					"name": "categories",
					"value": !values.categories ? "" : values.categories,
					"type": "TextBox",
					"label": "Topics"
				},{
					"name": "mt_keywords",
					"value": !values.mt_keywords ? "" : values.mt_keywords,
					"type": "TextBox",
					"label": "Tags"
				},{
					"name": "publish",
					"value": values.post_status == "publish" ? 1 : 0,
					"type": "OnOffSelect",
					"label": "Publish post"
				},{
					"name": "sticky",
					"value": values.sticky === false ? 0 : 1,
					"type": "OnOffSelect",
					"label": "Sticky post"
				},{
	                "name": "go_edit",
	                "type": "Button",
	                "label": "Publish",
	                "onClick": function() {
						myself.editPost();
					}
	            }],
				attachNode: this.tab_edit_post.containerNode
			});
			this.subForm_editPost.build();
		};
		
		this.editPost = function() {
			if (!this.form_editPost._form.validate()) {
				$c.dialog.info('Invalid data in form, please check.');
			}
			else {
				$c.kernel.newCall(myself.editPostCallback,{
					server: "testMetaWeblog",
					selector: "edit_post",
					content: $d.mixin(this.form_editPost._form.get('value'),this.subForm_editPost._form.get('value'))
				});
			}
		};
		
		this.editPostCallback = function(success, result) {
			if (success) {
				$c.dialog.info('<p>Success!</p><pre>'+result+'</pre>');
			}
			else {
				$c.dialog.info('<p>Failure!</p><pre>'+result+'</pre>');
			}
		};
		
		this._buildGetPostsTab = function() {
			this.form_getPosts = new $c.form({
				hierarchy: [{
					"name": "blog_url",
					"value": "",
					"type": "ValidationTextBox",
					"label": "blog rpc url",
					"required": true
				},{
					"name": "blog_user",
					"value": "",
					"type": "ValidationTextBox",
					"label": "blog Username",
					"required": true
				},{
					"name": "blog_pass",
					"value": "",
					"type": "PasswordTextBox",
					"label": "blog User Password",
					"required": true
				},{
					"name": "howmany",
					"value": "",
					"type": "TextBox",
					"label": "Number of posts"
				},{
	                "name": "retrieve",
	                "type": "Button",
	                "label": "GetPosts",
	                "onClick": function() {
						myself.getPosts();
					}
	            }],
				attachNode: this.tab_get_posts.containerNode
			});
			this.form_getPosts.build();
		};
		
		this.getPosts = function() {
			if (!this.form_getPosts._form.validate()) {
				$c.dialog.info('Invalid data in form, please check.');
			}
			else {
				$c.kernel.newCall(myself.getPostsCallback,{
					server: "testMetaWeblog",
					selector: "get_posts",
					content: this.form_getPosts._form.get('value')
				});
			}
		};
		
		this.getPostsCallback = function(success, result) {
			if (success) {
				var inner = '<div style="max-heigth:400px; width:400px; overflow: auto;">';
				for (var i in result) {
					inner += '<h4>Post n°<a href="'+result[i].permaLink+'" target="_blank">'+result[i].postid+'</a>, title: '+result[i].title+' ('+result[i].post_status+')</h4><h5>Created by user (id) '+result[i].userid+' on '+$c.date.fromServer(result[i].dateCreated.timestamp)+'</h5><p>'+result[i].description+result[i].mt_text_more+'</p><hr>';
				}
				inner += '</div>';
				$c.dialog.info('<h3>Success!</h3>'+inner);
			}
			else {
				$c.dialog.info('<p>Failure!</p><pre>'+result+'</pre>');
			}
		};
		
		this._buildGetCategoriesTab = function() {
			this.form_getCategories = new $c.form({
				hierarchy: [{
					"name": "blog_url",
					"value": "",
					"type": "ValidationTextBox",
					"label": "blog rpc url",
					"required": true
				},{
					"name": "blog_user",
					"value": "",
					"type": "ValidationTextBox",
					"label": "blog Username",
					"required": true
				},{
					"name": "blog_pass",
					"value": "",
					"type": "PasswordTextBox",
					"label": "blog User Password",
					"required": true
				},{
	                "name": "retrieve",
	                "type": "Button",
	                "label": "GetCategories",
	                "onClick": function() {
						myself.getCategories();
					}
	            }],
				attachNode: this.tab_get_categories.containerNode
			});
			this.form_getCategories.build();
		};
		
		this.getCategories = function() {
			if (!this.form_getCategories._form.validate()) {
				$c.dialog.info('Invalid data in form, please check.');
			}
			else {
				$c.kernel.newCall(myself.getCategoriesCallback,{
					server: "testMetaWeblog",
					selector: "get_categories",
					content: this.form_getCategories._form.get('value')
				});
			}
		};
		
		this.getCategoriesCallback = function(success, result) {
			if (success) {
				var inner = '<div style="heigth:400px; width:400px; overflow: auto;"><ul>';
				for (var i in result) {
					inner += '<li>'+result[i].categoryId+' - (<a href="'+result[i].rssUrl+'" target="_blank">'+result[i].categoryName+'</a>) <a href="'+result[i].htmlUrl+'" target="_blank">'+result[i].description+'</a></li>';
				}
				inner += '</ul></div>';
				$c.dialog.info('<p>Success!</p>'+inner);
			}
			else {
				$c.dialog.info('<p>Failure!</p><pre>'+result+'</pre>');
			}
		};
		
	}
	
);