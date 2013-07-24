comodojo.loadCss('comodojo/CSS/form.css');

define("comodojo/Form", [
	"dojo/_base/lang",
	"dojo/_base/Deferred",
	"dojo/has",
	"dojo/_base/declare",
	"dojo/dom-construct",
	"dojo/dom-class",
	"dojo/data/ObjectStore",
	"dojo/store/Memory",
	"dijit/form/Form",
	"dijit/layout/ContentPane"], 
function(lang, Deferred, has, declare, domConstruct, domClass, ObjectStore, Memory, Form, ContentPane){

	// module:
	// 	comodojo/Basic

var form = declare(null,{
	// summary:
	// description:

	// The node the form will be pushed in
	// String
	attachNode: false,

	// The form template; admitted values: SPLIT_LABEL_LEFT (default), SPLIT_LABEL_RIGHT, LABEL_ON_INPUT, INPUT_ON_LABEL
	// String
	template: "SPLIT_LABEL_LEFT",

	// The pid (unique) that form will have
	// String
	id: comodojo.getPid(),

	// Remapped tag "action" of html form
	// String
	action: false,

	// If false, prevent autofocus on first input node
	// Bool
	autoFocus: true,

	// Hierarchy that will built (json)
	hierarchy: [],

	modules: [],

	// Form dimensions (numeric, no px suffix)
	// if "auto", it will try to fit form automatically in attachNode
	// auto|Integer
	formWidth:	'auto',
	formHeight:	'auto',
	labelWidth:	'auto',
	labelHeight:'auto',
	inputWidth:	'auto',
	inputHeight:'auto',

	// Extra CSS string passed to form elements at startup
	// String
	inputContainerExtraCss:	'',
	inputExtraCss: 			'font-size: normal; padding: 2px; text-align: left;',
	dateTextBoxExtraCss:	'font-size: normal; padding: 2px; width: 100px !important; text-align: center;',
	textareaExtraCss:		'font-size: normal; padding: 4px; text-align: left; min-height: 40px;',
	editorExtraCss:			'font-size: normal; padding: 4px; text-align: left;',
	selectExtraCss:			'font-size: normal; font-weight: bold; width: 60%;',
	labelExtraCss:			'font-size: normal; padding-top: 5px;',
	buttonExtraCss:			'',

	// Form CSS class
	// String
	baseCssClass: "comodojo_form",

	load_modules: function(dfrrd,module) {
		require(module, function(mod) {
			dfrrd.resolve(mod);
		})
	},

	constructor: function(args) {

		declare.safeMixin(this,args);

		this.deferred_calls = [];

		this.deferred_modules = {};

		this.form = {};
	
		this._inputContainerClass = false;
		this._inputLabelClass = false;
		this._inputFieldClass = false;
		this._selectFieldClass = false;
		this._radioFieldClass = false;
		this._checkFieldClass = false;
		this._buttonContainerClass = false;
		this._buttonClass = false;
		this._form = false;
		this._fieldsCount = 1;
		this._firstNode = false;

		var deferred_modules = {};

		for (i in args.modules) {
			var mods;
			switch(args.modules[i]) {
				case 'Textarea':
					mods = ['dijit/form/Textarea'];
				break;
				case 'Button':
					mods = ['dijit/form/Button'];
				break;
				case 'ToggleButton':
					mods = ['dijit/form/ToggleButton'];
				break;
				case 'BusyButton':
					mods = ['dojox/form/BusyButton'];
				break;
				case 'CheckBox':
					mods = ['dijit/form/CheckBox'];
				break;
				case 'TextBox':
					mods = ['dijit/form/TextBox'];
				break;
				case 'CurrencyTextBox':
					mods = ['dijit/form/CurrencyTextBox'];
				break;
				case 'DateTextBox':
					mods = ['dijit/form/DateTextBox'];
				break;
				case 'TimeTextBox':
					mods = ['dijit/form/TimeTextBox'];
				break;
				case 'NumberTextBox':
					mods = ['dijit/form/NumberTextBox'];
				break;
				case 'ValidationTextBox':
				case 'EmailTextBox':
				case 'PasswordTextBox':
					mods = ['dijit/form/ValidationTextBox'];
				break;
				case 'Select':
				case 'OnOffSelect':
				case 'GenderSelect':
					mods = ['dijit/form/Select'];
				break;
				case 'NumberSpinner':
					mods = ['dijit/form/NumberSpinner'];
				break;
				case 'FilteringSelect':
					mods = ['dijit/form/FilteringSelect'];
				break;
				case 'MultiSelect':
					mods = ['dijit/form/MultiSelect'];
				break;
				
				case 'SmallEditor':
				case 'Editor':
					mods = ['dijit/Editor','dijit/form/Textarea'];
				break;
				case 'FullEditor':
					mods = ['dijit/Editor','dijit/form/Textarea','dijit/_editor/plugins/FontChoice','dijit/_editor/plugins/FullScreen',
						'dijit/_editor/plugins/TextColor','dijit/_editor/plugins/LinkDialog','dijit/_editor/plugins/Print',
						'dijit/_editor/plugins/ViewSource','dijit/_editor/plugins/TabIndent','dojox/editor/plugins/ToolbarLineBreak',
						'dojox/editor/plugins/TablePlugins','dojox/editor/plugins/PageBreak','dojox/editor/plugins/ShowBlockNodes',
						'dojox/editor/plugins/Preview','dojox/editor/plugins/FindReplace','dojox/editor/plugins/CollapsibleToolbar',
						'dojox/editor/plugins/Blockquote','dojox/editor/plugins/PasteFromWord','dojox/editor/plugins/InsertAnchor'];
				break;
			}

			this.deferred_calls[i] = new Deferred();

			this.deferred_calls[i].then(function(v) {
				deferred_modules[args.modules[i]] = v;
			});
			
			this.load_modules(this.deferred_calls[i],mods);
		}

		this.deferred_modules = deferred_modules;

	},

	build: function() {
		if (!this.attachNode) {
			comodojo.debug('Cannot start form, no attachNode defined!');
			return;
		}
		else if (this.hierarchy.length == 0) {
			comodojo.debug('Cannot start form, no hierarchy!');
			return;
		}
		else {
			this._computeTemplate();
			this.attachNode.appendChild(this._makeForm());
			this._makeHierarchy();
			this._form.startup();
			//focus on the first child, if any
			if (this._firstNode !== false && this.autoFocus) { this._form._fields[this._firstNode].focus(); }
			//console.log(this._form.getChildren()[0]);
			//easy access to most user funcs
			//this._form.getValues = function() {
			//	return this._form.attr('value');
			//};
			//this._form.isValid = function() {
			//	return this._form.validate();
			//};
			this.form = this._form;
			return this._form;
		}
	},

	_makeForm: function() {
		
		this._form = new Form({
			id: this.id,
			action: !this.action ? "javascript:;" : this.action,
			_fields: {},
			fields: {},
			//override default resize (broken for forms!)
			//resize: function() { }
		});

		domClass.add(this._form.domNode,this.baseCssClass+'_mainForm_'+dojo.body().getAttribute('class'));
		
		return this._form.domNode;

	},

	_makeHierarchy: function() {

		var myField, myBox, myLabel, i=0, n=this._fieldsCount;
		
		for (i in this.hierarchy) {
			
			if (this.hierarchy[i].type == "info" || this.hierarchy[i].type == "success" || this.hierarchy[i].type == "warning" || this.hierarchy[i].type == "error") {
				myField = this._makeHierarchyBoxHelper(this.hierarchy[i]);
				if (this.hierarchy[i].name) {
					this._form.fields[this.hierarchy[i].name] = myField;
				}
				this._form.domNode.appendChild(myField.domNode);
			}
			else {
				if (!this._firstNode) { this._firstNode = this._fieldsCount; }
				myBox = domConstruct.create("div",{
					className: ((this.hierarchy[i].type == "Button" || this.hierarchy[i].type == "BusyButton" || this.hierarchy[i].type == "ToggleButton") ? this._buttonContainerClass : this._inputContainerClass), style: this.inputContainerExtraCss
				});
				
				myField = this._makeHierarchyHelper(this.hierarchy[i]);
				
				myLabel = (this.hierarchy[i].type == "Button" || this.hierarchy[i].type == "BusyButton" || this.hierarchy[i].type == "ToggleButton") ? false : domConstruct.create("div",{className:this.baseCssClass+"_inputLabel_"+this.template,innerHTML:this.hierarchy[i].label+(this.hierarchy[i].required ? '<span style="color:red;font-weight:bold;"> *</span>' : ''), style: this.labelExtraCss});
				
				if (this.template == 'SPLIT_LABEL_LEFT' || this.template == 'INPUT_ON_LABEL') {
					myBox.appendChild(myField.domNode);
					if (myLabel != false) {myBox.appendChild(myLabel);}
				}
				else {
					if (myLabel != false) {myBox.appendChild(myLabel);}
					myBox.appendChild(myField.domNode);
				}		
				
				myBox.appendChild(domConstruct.create("div",{className:this.baseCssClass+"_clearer"}));
				
				if (this.hierarchy[i].hidden) { myBox.style.display="none"; }
				
				this._form.domNode.appendChild(myBox);
			}
			this._form._fields[this._fieldsCount] = myField;
			this._fieldsCount++;
			
		}

	},

	_makeHierarchyBoxHelper: function(params) {

		var myField = new ContentPane({
			className: 'box '+params.type,
			content:params.content
		});
		if (params.hidden === true) {
			myField.domNode.style.display="none";
		}
		myField.set('type',params.type);

		myField.changeType = function(newType) {
			domClass.replace(myField.domNode, newType, myField.get('type'));
			myField.set('type',newType);
		};

		myField.changeContent = function(newContent) {
			myField.set('content',newContent);
		};

		return myField;

	},

	_makeHierarchyHelper: function(hierarchyElement) {

		var myField,preField,hiddenField,editorField;
		
		switch (hierarchyElement.type) {
			
			/***********************/
			/****** TEXTBOXES ******/
			/***********************/
		
			case "TextBox":
				myField = new this.deferred_modules.TextBox ({
					name: hierarchyElement.name,
					value: hierarchyElement.value,
					disabled: !hierarchyElement.disabled ? false : "disabled",
					readOnly: !hierarchyElement.readonly ? false : "readOnly",
					style: this.inputExtraCss,
					onClick: lang.isFunction(hierarchyElement.onClick) ? function(){hierarchyElement.onClick();} : function(){return;}
				});
				domClass.add(myField.domNode,this._inputFieldClass);
			break;
			
			case "ValidationTextBox": 
				myField = new this.deferred_modules.ValidationTextBox ({
					name: hierarchyElement.name,
					value: hierarchyElement.value,
					disabled: !hierarchyElement.disabled ? false : "disabled",
					readOnly: !hierarchyElement.readonly ? false : "readOnly",
					required: hierarchyElement.required,
					constraints: !hierarchyElement.constraints ? false : hierarchyElement.constraints,
					promptMessage: !hierarchyElement.promptMessage ? false : hierarchyElement.promptMessage,
					invalidMessage: !hierarchyElement.invalidMessage ? "$_unset_$" : hierarchyElement.invalidMessage,
					style: this.inputExtraCss
				});
				domClass.add(myField.domNode,this._inputFieldClass);
			break;
			
			case "EmailTextBox": 
				myField = new this.deferred_modules.ValidationTextBox ({
					name: hierarchyElement.name,
					value: hierarchyElement.value,
					disabled: !hierarchyElement.disabled ? false : "disabled",
					readOnly: !hierarchyElement.readonly ? false : "readOnly",
					required: hierarchyElement.required,
					regExp: "[a-zA-z0-9._%-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,4}",
					promptMessage: !hierarchyElement.promptMessage ? false : hierarchyElement.promptMessage,
					invalidMessage: !hierarchyElement.invalidMessage ? "$_unset_$" : hierarchyElement.invalidMessage,
					style: this.inputExtraCss
				});
				domClass.add(myField.domNode,this._inputFieldClass);
			break;
				
			case "PasswordTextBox": 
				myField = new this.deferred_modules.ValidationTextBox ({
					name: hierarchyElement.name,
					value: hierarchyElement.value,
					disabled: !hierarchyElement.disabled ? false : "disabled",
					readOnly: !hierarchyElement.readonly ? false : "readOnly",
					required: hierarchyElement.required,
					constraints: !hierarchyElement.constraints ? false : hierarchyElement.constraints,
					invalidMessage: !hierarchyElement.invalidMessage ? "$_unset_$" : hierarchyElement.invalidMessage,
					type: "password",
					style: this.inputExtraCss
				});
				domClass.add(myField.domNode,this._inputFieldClass);
			break;
			
			case "DateTextBox": 
				//sanitize the date value...
				//var myParts = hierarchyElement.value.split('-');
				//var myDate = new Date(myParts[0],myParts[1],myParts[2]);
				var myDate = new Date(hierarchyElement.value);//$c.date.fromServer(hierarchyElement.value);
				myField = new this.deferred_modules.DateTextBox ({
					name: hierarchyElement.name,
					value: myDate,
					disabled: !hierarchyElement.disabled ? false : "disabled",
					readOnly: !hierarchyElement.readonly ? false : "readOnly",
					required: hierarchyElement.required,
					style: this.dateTextBoxExtraCss
				});	
				domClass.add(myField.domNode,this._inputFieldClass);
			break;
			
			case "TimeTextBox": 
				myField = new this.deferred_modules.TimeTextBox ({
					name: hierarchyElement.name,
					value: hierarchyElement.value,
					disabled: !hierarchyElement.disabled ? false : "disabled",
					readOnly: !hierarchyElement.readonly ? false : "readOnly",
					required: hierarchyElement.required,
					style: this.dateTextBoxExtraCss
				});	
				domClass.add(myField.domNode,this._inputFieldClass);
			break;
			
			case "CurrencyTextBox": 
				myField = new this.deferred_modules.CurrencyTextBox ({
					name: hierarchyElement.name,
					value: hierarchyElement.value,
					disabled: !hierarchyElement.disabled ? false : "disabled",
					readOnly: !hierarchyElement.readonly ? false : "readOnly",
					required: hierarchyElement.required,
					promptMessage: !hierarchyElement.promptMessage ? false : hierarchyElement.promptMessage,
					invalidMessage: !hierarchyElement.invalidMessage ? "$_unset_$" : hierarchyElement.invalidMessage,
					style: this.inputExtraCss,
					currency: !hierarchyElement.currency ? "EUR" : hierarchyElement.currency
				});
				domClass.add(myField.domNode,this._inputFieldClass);
			break;
			
			case "NumberTextBox": 
				myField = new this.deferred_modules.NumberTextBox ({
					name: hierarchyElement.name,
					value: hierarchyElement.value,
					disabled: !hierarchyElement.disabled ? false : "disabled",
					readOnly: !hierarchyElement.readonly ? false : "readOnly",
					required: hierarchyElement.required,
					promptMessage: !hierarchyElement.promptMessage ? false : hierarchyElement.promptMessage,
					invalidMessage: !hierarchyElement.invalidMessage ? "$_unset_$" : hierarchyElement.invalidMessage,
					style: this.inputExtraCss,
					pattern: !hierarchyElement.pattern ? $d.number.regexp : hierarchyElement.pattern,
					constraints: {
						min: !hierarchyElement.min ? null : hierarchyElement.min,
						max: !hierarchyElement.max ? null : hierarchyElement.max,
						places: !hierarchyElement.places ? 0 : hierarchyElement.places
					}
				});
				domClass.add(myField.domNode,this._inputFieldClass);
			break;
			
			case "NumberSpinner": 
				myField = new this.deferred_modules.NumberSpinner ({
					name: hierarchyElement.name,
					value: hierarchyElement.value,
					disabled: !hierarchyElement.disabled ? false : "disabled",
					readOnly: !hierarchyElement.readonly ? false : "readOnly",
					required: hierarchyElement.required,
					promptMessage: !hierarchyElement.promptMessage ? false : hierarchyElement.promptMessage,
					invalidMessage: !hierarchyElement.invalidMessage ? "$_unset_$" : hierarchyElement.invalidMessage,
					style: this.inputExtraCss,
					smallDelta: !hierarchyElement.smallDelta ? 1 : hierarchyElement.smallDelta,
					largeDelta: !hierarchyElement.largeDelta ? 10 : hierarchyElement.largeDelta,
					constraints: {
						min: !hierarchyElement.min ? null : hierarchyElement.min,
						max: !hierarchyElement.max ? null : hierarchyElement.max,
						places: !hierarchyElement.max ? 0 : hierarchyElement.max
					}
				});
				domClass.add(myField.domNode,this._selectFieldClass);
			break;

			/************************/
			/****** CHECKBOXES ******/
			/************************/
			
			case "CheckBox":
				myField = new this.deferred_modules.CheckBox ({
					name: hierarchyElement.name,
					value: 1,
					disabled: !hierarchyElement.disabled ? false : "disabled",
					readOnly: !hierarchyElement.readonly ? false : "readOnly",
					checked: hierarchyElement.checked ? "checked" : false
				});
				domClass.add(myField.domNode,this._checkFieldClass);
			break;
			
			/*********************/
			/****** SELECTS ******/
			/*********************/
			
			case "FilteringSelect": 
				myField = new this.deferred_modules.FilteringSelect ({
					name: hierarchyElement.name,
					store: new ObjectStore({ 
						objectStore: new Memory({
							data: hierarchyElement.options
						})
					}),
					value: hierarchyElement.value,
					disabled: !hierarchyElement.disabled ? false : "disabled",
					readOnly: !hierarchyElement.readonly ? false : "readOnly",
					autocomplete: hierarchyElement.autocomplete,
					required: hierarchyElement.required,
					invalidMessage: !hierarchyElement.invalidMessage ? "$_unset_$" : hierarchyElement.invalidMessage,
					style: this.selectExtraCss,
					onChange: !lang.isFunction(hierarchyElement.onChange) ? function() {} : hierarchyElement.onChange,
					autoWidth: true,
					maxHeight: -1
				});
				domClass.add(myField.domNode,this._selectFieldClass);
			break;

			case "Select":
				myField = new this.deferred_modules.Select ({
					name: hierarchyElement.name,
					store: new ObjectStore({ 
						objectStore: new Memory({
							data: hierarchyElement.options
						})
					}),
					value: hierarchyElement.value,
					disabled: !hierarchyElement.disabled ? false : "disabled",
					readOnly: !hierarchyElement.readonly ? false : "readOnly",
					required: hierarchyElement.required,
					invalidMessage: !hierarchyElement.invalidMessage ? "$_unset_$" : hierarchyElement.invalidMessage,
					style: this.selectExtraCss,
					onChange: !$d.isFunction(hierarchyElement.onChange) ? function() {} : hierarchyElement.onChange,
					autoWidth: true,
					maxHeight: -1
				});
				domClass.add(myField.domNode,this._selectFieldClass);
			break;
			
			case "MultiSelect":
				myField = new this.deferred_modules.MultiSelect ({
					name: hierarchyElement.name,
					store: new ObjectStore({ 
						objectStore: new Memory({
							data: hierarchyElement.options
						})
					}),
					value: hierarchyElement.value,
					disabled: !hierarchyElement.disabled ? false : "disabled",
					readOnly: !hierarchyElement.readonly ? false : "readOnly",
					required: hierarchyElement.required,
					invalidMessage: !hierarchyElement.invalidMessage ? "$_unset_$" : hierarchyElement.invalidMessage,
					//style: this.selectExtraCss,
					onChange: !lang.isFunction(hierarchyElement.onChange) ? function() {} : hierarchyElement.onChange,
					autoWidth: true,
					maxHeight: -1
				});
				domClass.add(myField.domNode,this._selectFieldClass);
				myField.containerNode.setAttribute('style',this.selectExtraCss);
			break;
			
			case "OnOffSelect": 
				myField = new this.deferred_modules.Select ({
					name: hierarchyElement.name,
					store: new ObjectStore({ 
						objectStore: new Memory({ 
							data: [
								{id:0,label:'<img src="comodojo/icons/16x16/off.png" />&nbsp;'+comodojo.getLocalizedMessage('10004')},
								{id:1,label:'<img src="comodojo/icons/16x16/on.png" />&nbsp;'+comodojo.getLocalizedMessage('10003')}
							]})
					}),
					value: hierarchyElement.value,
					disabled: !hierarchyElement.disabled ? false : "disabled",
					readOnly: !hierarchyElement.readonly ? false : "readOnly",
					required: hierarchyElement.required,
					invalidMessage: !hierarchyElement.invalidMessage ? "$_unset_$" : hierarchyElement.invalidMessage,
					//style: this.selectExtraCss,
					style: 'width: 70px;',
					onChange: !lang.isFunction(hierarchyElement.onChange) ? function() {} : hierarchyElement.onChange,
					autoWidth: true
				});
				domClass.add(myField.domNode,this._selectFieldClass);
				myField.containerNode.setAttribute('style',this.selectExtraCss);
				myField.set('value',hierarchyElement.value);
			break;
			
			case "GenderSelect":
				myField = new this.deferred_modules.Select ({
					name: hierarchyElement.name,
					store: new ObjectStore({ 
						objectStore: new Memory({ 
							data: [
								{id:'M',label:'<img src="comodojo/icons/16x16/male.png" />&nbsp;'+comodojo.getLocalizedMessage('10012')},
								{id:'F',label:'<img src="comodojo/icons/16x16/female.png" />&nbsp;'+comodojo.getLocalizedMessage('10013')}
						]})
					}),
					value: hierarchyElement.value,
					disabled: !hierarchyElement.disabled ? false : "disabled",
					readOnly: !hierarchyElement.readonly ? false : "readOnly",
					required: hierarchyElement.required,
					invalidMessage: !hierarchyElement.invalidMessage ? "$_unset_$" : hierarchyElement.invalidMessage,
					//style: this.selectExtraCss,
					style: 'width: 90px;',
					onChange: !lang.isFunction(hierarchyElement.onChange) ? function() {} : hierarchyElement.onChange,
					autoWidth: true
				});
				domClass.add(myField.domNode,this._selectFieldClass);
				myField.containerNode.setAttribute('style',this.selectExtraCss);
			break;

			/*********************/
			/****** BUTTONS ******/
			/*********************/
			
			case "Button": 
				myField = new this.deferred_modules.Button ({
					name: hierarchyElement.name,
					label: hierarchyElement.label,
					disabled: !hierarchyElement.disabled ? false : "disabled",
					onClick: lang.isFunction(hierarchyElement.onClick) ? function(){hierarchyElement.onClick();} : (typeof(hierarchyElement.onClick)=='string' ? function() {eval(hierarchyElement.onClick);} : function(){}),
					style: this.buttonExtraCss
				});
				domClass.add(myField.domNode,this._buttonClass);
			break;
			
			case "BusyButton": 
				myField = new this.deferred_modules.BusyButton ({
					name: hierarchyElement.name,
					label: hierarchyElement.label,
					disabled: !hierarchyElement.disabled ? false : "disabled",
					onClick: lang.isFunction(hierarchyElement.onClick) ? function(){hierarchyElement.onClick();} : (typeof(hierarchyElement.onClick)=='string' ? function() {eval(hierarchyElement.onClick);} : function(){}),
					style: this.buttonExtraCss,
					timeout: !hierarchyElement.timeout ? null : hierarchyElement.timeout
				});
				domClass.add(myField.domNode,this._buttonClass);
			break;
			
			case "ToggleButton": 
				myField = new this.deferred_modules.ToggleButton ({
					name: hierarchyElement.name,
					label: hierarchyElement.label,
					disabled: !hierarchyElement.disabled ? false : "disabled",
					onClick: lang.isFunction(hierarchyElement.onClick) ? function(){hierarchyElement.onClick();} : (typeof(hierarchyElement.onClick)=='string' ? function() {eval(hierarchyElement.onClick);} : function(){}),
					onChange: lang.isFunction(hierarchyElement.onChange) ? function(){hierarchyElement.onChange();} : (typeof(hierarchyElement.onChange)=='string' ? function() {eval(hierarchyElement.onChange);} : function(){}),
					style: this.buttonExtraCss,
					checked: !hierarchyElement.checked ? false : hierarchyElement.disabled,
					showLabel: true
				});
				domClass.add(myField.domNode,this._buttonClass);
			break;

			/***********************/
			/****** TEXTAREAS ******/
			/***********************/
			
			case "Textarea": 
				myField = new this.deferred_modules.Textarea ({
					name: hierarchyElement.name,
					value: hierarchyElement.value,
					disabled: !hierarchyElement.disabled ? false : "disabled",
					readOnly: !hierarchyElement.readonly ? false : "readOnly",
					style: this.textareaExtraCss
				});
				domClass.add(myField.domNode,this._inputFieldClass);
			break;

			case "SmallEditor":
				myField = new this.deferred_modules.Editor({
					value: hierarchyElement.value,
					disabled: !hierarchyElement.disabled ? false : "disabled",
					readOnly: !hierarchyElement.readonly ? false : "readOnly",
					style: this.editorExtraCss,
					plugins: ["bold", "italic", "underline", "strikethrough", "insertOrderedList", "insertUnorderedList", "justifyLeft", "justifyRight", "justifyCenter", "justifyFull"]
				});
				domClass.add(myField.domNode,this._inputFieldClass);
			break;

			case "Editor":
				myField = new this.deferred_modules.Editor({
					value: hierarchyElement.value,
					disabled: !hierarchyElement.disabled ? false : "disabled",
					readOnly: !hierarchyElement.readonly ? false : "readOnly",
					style: this.editorExtraCss
				});
				domClass.add(myField.domNode,this._inputFieldClass);
			break;

			case "FullEditor":
				myField = new this.deferred_modules.Editor({
					value: hierarchyElement.value,
					disabled: !hierarchyElement.disabled ? false : "disabled",
					readOnly: !hierarchyElement.readonly ? false : "readOnly",
					style: this.editorExtraCss,
					extraPlugins: ['|',"createLink","insertImage","insertHorizontalRule","|","tabIndent","print","fullscreen","viewsource","||","foreColor","hiliteColor",{name:"dijit._editor.plugins.FontChoice", command:"fontName", generic:true},"fontSize","formatBlock"]
				});
				domClass.add(myField.domNode,this._inputFieldClass);
			break;

		}
		
		this._form.fields[hierarchyElement.name] = myField;
		
		return myField;

	},

	_computeTemplate: function() {
		this._inputContainerClass = this.baseCssClass+'_inputContainer_'+this.template;
		this._inputLabelClass = this.baseCssClass+'_inputLabel_'+this.template;
		this._inputFieldClass = this.baseCssClass+'_inputField_'+this.template;
		this._selectFieldClass = this.baseCssClass+'_selectField_'+this.template;
		this._radioFieldClass = this.baseCssClass+'_radioField_'+this.template;
		this._checkFieldClass = this.baseCssClass+'_checkField_'+this.template;
		this._buttonContainerClass = this.baseCssClass+'_buttonContainer_'+this.template;
		this._buttonClass = this.baseCssClass+'_button_'+this.template;
		
		if (this.formHeight != 'auto') { this.attachNode.style.height = this.formHeight+'px';}
		if (this.formWidth != 'auto') { this.attachNode.style.width = this.formWidth+'px';}
		if (this.labelWidth != 'auto') { this.labelExtraCss = this.labelExtraCss+'width:'+this.labelWidth+'px !important;';}
		if (this.labelHeight != 'auto') { this.labelExtraCss = this.labelExtraCss+'height:'+this.labelHeight+'px !important;';}
		if (this.inputWidth != 'auto') { this.inputExtraCss = this.inputExtraCss+'width:'+this.inputWidth+'px !important;';}
		if (this.labelHeight != 'auto') { this.inputExtraCss = this.inputExtraCss+'height:'+this.labelHeight+'px !important;';}	
	}

});

return form;	

});