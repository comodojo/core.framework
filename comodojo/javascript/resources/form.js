/** 
 * form.js
 * 
 * Add to comodojo basic form capabilities
 * 
 * 
 * @package		Comodojo ClientSide Core Packages
 * @author		comodojo.org
 * @copyright	2012 comodojo.org (info@comodojo.org)
 * @version		__CURRENT_VERSION__
 * @license		GPL Version 3
 */

$c.loadCss('comodojo/CSS/form.css');

$d.require("dijit.form.Form");

/**/$d.requireIf(comodojo.inArray('Button',comodojo.bus._modules.form), "dijit.form.Button"); /* OK */
//$d.requireIf(comodojo.inArray('ComboButton',comodojo.bus._modules.form), "dijit.form.ComboButton");
//$d.requireIf(comodojo.inArray('DropDownButton',comodojo.bus._modules.form), "dijit.form.DropDownButton");
$d.requireIf(comodojo.inArray('ToggleButton',comodojo.bus._modules.form), "dijit.form.ToggleButton"); /* OK */
$d.requireIf(comodojo.inArray('BusyButton',comodojo.bus._modules.form), "dojox.form.BusyButton"); /* OK */

/**/$d.requireIf(comodojo.inArray('CheckBox',comodojo.bus._modules.form), "dijit.form.CheckBox"); /* OK */
//$d.requireIf(comodojo.inArray('ComboBox',comodojo.bus._modules.form), "dijit.form.ComboBox");
$d.requireIf(comodojo.inArray('CurrencyTextBox',comodojo.bus._modules.form), "dijit.form.CurrencyTextBox"); /* OK */
/**/$d.requireIf(comodojo.inArray('DateTextBox',comodojo.bus._modules.form), "dijit.form.DateTextBox"); /* OK */
/**/$d.requireIf(comodojo.inArray('ValidationTextBox',comodojo.bus._modules.form), "dijit.form.ValidationTextBox"); /* OK */
/**/$d.requireIf(comodojo.inArray('EmailTextBox',comodojo.bus._modules.form), "dijit.form.ValidationTextBox"); /* OK */
/**/$d.requireIf(comodojo.inArray('PasswordTextBox',comodojo.bus._modules.form), "dijit.form.ValidationTextBox"); /* OK */
/**/$d.requireIf(comodojo.inArray('TimeTextBox',comodojo.bus._modules.form), "dijit.form.TimeTextBox"); /* OK */
/**/$d.requireIf(comodojo.inArray('TextBox',comodojo.bus._modules.form), "dijit.form.TextBox"); /* OK */
$d.requireIf(comodojo.inArray('NumberTextBox',comodojo.bus._modules.form), "dijit.form.NumberTextBox"); /* OK */
$d.requireIf(comodojo.inArray('NumberSpinner',comodojo.bus._modules.form), "dijit.form.NumberSpinner"); /* OK */

/**/$d.requireIf(comodojo.inArray('Select',comodojo.bus._modules.form), "dijit.form.Select"); /* OK */
/**/$d.requireIf(comodojo.inArray('FilteringSelect',comodojo.bus._modules.form), "dijit.form.FilteringSelect"); /* OK */
$d.requireIf(comodojo.inArray('MultiSelect',comodojo.bus._modules.form), "dijit.form.MultiSelect"); /* OK */
/**/$d.requireIf(comodojo.inArray('OnOffSelect',comodojo.bus._modules.form), "dijit.form.Select"); /* OK */
/**/$d.requireIf(comodojo.inArray('GenderSelect',comodojo.bus._modules.form), "dijit.form.Select"); /* OK */

/**/$d.requireIf(comodojo.inArray('Textarea',comodojo.bus._modules.form), "dijit.form.Textarea"); /* OK */
/**/$d.requireIf(comodojo.inArray('Editor',comodojo.bus._modules.form), "dijit.Editor"); /* OK */
/**/$d.requireIf(comodojo.inArray('Editor',comodojo.bus._modules.form), "dijit.form.Textarea"); /* OK */
/**/$d.requireIf(comodojo.inArray('SmallEditor',comodojo.bus._modules.form), "dijit.Editor"); /* OK */
/**/$d.requireIf(comodojo.inArray('SmallEditor',comodojo.bus._modules.form), "dijit.form.Textarea"); /* OK */

$d.requireIf(comodojo.inArray('FullEditor',comodojo.bus._modules.form), "dijit.Editor"); /* OK */
$d.requireIf(comodojo.inArray('FullEditor',comodojo.bus._modules.form), "dijit.form.Textarea"); /* OK */
$d.requireIf(comodojo.inArray('FullEditor',comodojo.bus._modules.form), "dijit._editor.plugins.FontChoice"); /* OK */
$d.requireIf(comodojo.inArray('FullEditor',comodojo.bus._modules.form), "dijit._editor.plugins.FullScreen"); /* OK */
$d.requireIf(comodojo.inArray('FullEditor',comodojo.bus._modules.form), "dijit._editor.plugins.TextColor"); /* OK */
$d.requireIf(comodojo.inArray('FullEditor',comodojo.bus._modules.form), "dijit._editor.plugins.LinkDialog"); /* OK */
$d.requireIf(comodojo.inArray('FullEditor',comodojo.bus._modules.form), "dijit._editor.plugins.Print"); /* OK */
$d.requireIf(comodojo.inArray('FullEditor',comodojo.bus._modules.form), "dijit._editor.plugins.ViewSource"); /* OK */
$d.requireIf(comodojo.inArray('FullEditor',comodojo.bus._modules.form), "dijit._editor.plugins.TabIndent"); /* OK */
$d.requireIf(comodojo.inArray('FullEditor',comodojo.bus._modules.form), "dojox.editor.plugins.ToolbarLineBreak"); /* OK */
//dojo.require("dijit._editor.plugins.AlwaysShowToolbar");

/*
CheckedMultiSelect
IntervalDateTextBox
MonthTextBox
YearTextBox
ListInput
PasswordValidator
RatingTextBox
*/

comodojo.form = function(params) {
	
	/**
	 * The node the form will be pushed in
	 * 
	 * @default	false	(will raise an error)
	 */
	this.attachNode = false;
	
	/**
	 * The form template; admitted values: SPLIT_LABEL_LEFT (default), SPLIT_LABEL_RIGHT, LABEL_ON_INPUT, INPUT_ON_LABEL
	 * 
	 * @default	false	(will raise an error)
	 */
	this.template = "SPLIT_LABEL_LEFT";

	/**
	 * The pid (unique) that form will have
	 * 
	 * @default	pid string
	 */
	this.formId = comodojo.getPid();
	
	/**
	 * Remapped tag "action" of html form
	 * 
	 * @default	false
	 */
	this.formAction = false;
	
	/**
	 * If false, prevent autofocus on first input node
	 * 
	 * @default	true
	 */
	this.autoFocus = true;
	
	
	this.hierarchy = false;
	
	/**
	 * Form dimensions (numeric, no px suffix)
	 * 
	 * if "auto", it will try to fit form automatically in attachNode
	 * 
	 * @default	string auto
	 */
	this.formWidth = 'auto';
	this.formHeight = 'auto';
	this.labelWidth = 'auto';
	this.labelHeight = 'auto';
	this.inputWidth = 'auto';
	this.inputHeight = 'auto';
	
	/**
	 * Extra CSS string passed to form elements at startup
	 * 
	 * @default	string 
	 */
	this.inputContainerExtraCss = '';
	this.inputExtraCss = 'font-size: normal; padding: 2px; text-align: left;';
	this.dateTextBoxExtraCss = 'font-size: normal; padding: 2px; width: 100px !important; text-align: center;';
	this.textareaExtraCss = 'font-size: normal; padding: 4px; text-align: left; min-height: 40px;';
	this.editorExtraCss = 'font-size: normal; padding: 4px; text-align: left;';
	this.selectExtraCss = 'font-size: normal; font-weight: bold; width: 60%;';
	this.labelExtraCss = 'font-size: normal; padding-top: 5px;';
	this.buttonExtraCss = '';
	this.baseCssClass = "comodojo_form";
	
	dojo.mixin(this,params);
	
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
	
	var that = this;
	
	/**
	 * Build the form!
	 * 
	 * @return	object	The required form (where form._form will be the dijit.Form obj)
	 */
	this.build = function() {
		if (!this.attachNode) {
			comodojo.debug('Cannot start form, no attachNode defined!');
			return false;
		}
		else if (!this.hierarchy) {
			comodojo.debug('Cannot start form, no hierarchy defined!');
			return false;
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
	};
	
	this._makeForm = function() {
		this._form = new dijit.form.Form({
			id: this.formId,
			action: !this.formAction ? "javascript:;" : this.formAction,
			_fields: {},
			fields: {},
			//override default resize (broken for forms!)
			resize: function() { }
		});
		dojo.addClass(this._form.domNode,this.baseCssClass+'_mainForm_'+dojo.body().getAttribute('class'));
		return this._form.domNode;
	};
	
	this._makeHierarchy = function() {
		
		var myField, myBox, myLabel, i, n=this._fieldsCount;
		
		for (i in this.hierarchy) {
			
			if (this.hierarchy[i].type == "info" || this.hierarchy[i].type == "success" || this.hierarchy[i].type == "warning" || this.hierarchy[i].type == "error") {
				myField = this._makeHierarchyBoxHelper(this.hierarchy[i]);
				if (this.hierarchy[i].name) {
					this._form.fields[this.hierarchy[i].name] = myField;
				}
				this._form.domNode.appendChild(myField.domNode);
			}
			else {
				if (!this._firstNode) {
					this._firstNode = this._fieldsCount;
				}
				myBox = $d.create("div",{ className: (this.hierarchy[i].type == "Button" ? this._buttonContainerClass : this._inputContainerClass), style: this.inputContainerExtraCss });
				
				myField = this._makeHierarchyHelper(this.hierarchy[i]);
				
				myLabel = this.hierarchy[i].type == "Button" ? false : $d.create("div",{className:this.baseCssClass+"_inputLabel_"+this.template,innerHTML:this.hierarchy[i].label+(this.hierarchy[i].required ? '<span style="color:red;font-weight:bold;"> *</span>' : ''), style: this.labelExtraCss});
				
				if (this.template == 'SPLIT_LABEL_LEFT' || this.template == 'INPUT_ON_LABEL') {
					myBox.appendChild(myField.domNode);
					if (myLabel != false) {myBox.appendChild(myLabel);}
				}
				else {
					if (myLabel != false) {myBox.appendChild(myLabel);}
					myBox.appendChild(myField.domNode);
				}		
				
				myBox.appendChild($d.create("div",{className:this.baseCssClass+"_clearer"}));
				
				if (this.hierarchy[i].hidden) { myBox.style.display="none"; }
				
				this._form.domNode.appendChild(myBox);
			}
			this._form._fields[this._fieldsCount] = myField;
			this._fieldsCount++;
			
		}
		
	};
	
	this._makeHierarchyBoxHelper = function(params) {
		
		var _myField = new dijit.layout.ContentPane({className: 'box '+params.type, content:params.content});
		if (params.hidden === true) { _myField.domNode.style.display="none"; }
		_myField.set('type',params.type);
		_myField.changeType = function(newType) {
			$d.removeClass(_myField.domNode,_myField.get('type'));
			$d.addClass(_myField.domNode,newType);
			_myField.set('type',newType);
		};
		_myField.changeContent = function(newContent) {
			_myField.set('content',newContent);
		};
		return _myField;
	};
	
	this._makeHierarchyHelper = function(hierarchyElement) {
		
		var myField,preField,hiddenField,editorField;
		
		switch (hierarchyElement.type) {
			
			/***********************/
			/****** TEXTBOXES ******/
			/***********************/
		
			case "TextBox":
				myField = new dijit.form.TextBox ({
					name: hierarchyElement.name,
					value: hierarchyElement.value,
					disabled: !hierarchyElement.disabled ? false : "disabled",
					readOnly: !hierarchyElement.readonly ? false : "readOnly",
					style: this.inputExtraCss,
					onClick: dojo.isFunction(hierarchyElement.onClick) ? function(){hierarchyElement.onClick();} : function(){return;}
				});
				dojo.addClass(myField.domNode,this._inputFieldClass);
			break;
			
			case "ValidationTextBox": 
				myField = new dijit.form.ValidationTextBox ({
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
				dojo.addClass(myField.domNode,this._inputFieldClass);
			break;
			
			case "EmailTextBox": 
				myField = new dijit.form.ValidationTextBox ({
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
				dojo.addClass(myField.domNode,this._inputFieldClass);
			break;
				
			case "PasswordTextBox": 
				myField = new dijit.form.ValidationTextBox ({
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
				dojo.addClass(myField.domNode,this._inputFieldClass);
			break;
			
			case "DateTextBox": 
				//sanitize the date value...
				//var myParts = hierarchyElement.value.split('-');
				//var myDate = new Date(myParts[0],myParts[1],myParts[2]);
				var myDate = new Date(hierarchyElement.value);//$c.date.fromServer(hierarchyElement.value);
				myField = new dijit.form.DateTextBox ({
					name: hierarchyElement.name,
					value: myDate,
					disabled: !hierarchyElement.disabled ? false : "disabled",
					readOnly: !hierarchyElement.readonly ? false : "readOnly",
					required: hierarchyElement.required,
					style: this.dateTextBoxExtraCss
				});	
				dojo.addClass(myField.domNode,this._inputFieldClass);
			break;
			
			case "TimeTextBox": 
				myField = new dijit.form.TimeTextBox ({
					name: hierarchyElement.name,
					value: hierarchyElement.value,
					disabled: !hierarchyElement.disabled ? false : "disabled",
					readOnly: !hierarchyElement.readonly ? false : "readOnly",
					required: hierarchyElement.required,
					style: this.dateTextBoxExtraCss
				});	
				dojo.addClass(myField.domNode,this._inputFieldClass);
			break;
			
			case "CurrencyTextBox": 
				myField = new dijit.form.CurrencyTextBox ({
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
				dojo.addClass(myField.domNode,this._inputFieldClass);
			break;
			
			case "NumberTextBox": 
				myField = new dijit.form.NumberTextBox ({
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
				dojo.addClass(myField.domNode,this._inputFieldClass);
			break;
			
			case "NumberSpinner": 
				myField = new dijit.form.NumberSpinner ({
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
				dojo.addClass(myField.domNode,this._selectFieldClass);
			break;
	
			/************************/
			/****** CHECKBOXES ******/
			/************************/
			
			case "CheckBox":
				myField = new dijit.form.CheckBox ({
					name: hierarchyElement.name,
					value: 1,
					disabled: !hierarchyElement.disabled ? false : "disabled",
					readOnly: !hierarchyElement.readonly ? false : "readOnly",
					checked: hierarchyElement.checked ? "checked" : false
				});
				dojo.addClass(myField.domNode,this._checkFieldClass);
			break;
			
			/*********************/
			/****** SELECTS ******/
			/*********************/
			
			case "FilteringSelect": 
				var o;
				preField = dojo.create("select");
					for (o in hierarchyElement.options) {
						preField.appendChild(dojo.create("option",{value:hierarchyElement.options[o].value, innerHTML:hierarchyElement.options[o].name}));
					}
				myField = new dijit.form.FilteringSelect ({
					name: hierarchyElement.name,
					value: hierarchyElement.value,
					disabled: !hierarchyElement.disabled ? false : "disabled",
					readOnly: !hierarchyElement.readonly ? false : "readOnly",
					autocomplete: hierarchyElement.autocomplete,
					required: hierarchyElement.required,
					invalidMessage: !hierarchyElement.invalidMessage ? "$_unset_$" : hierarchyElement.invalidMessage,
					style: this.selectExtraCss,
					onChange: !$d.isFunction(hierarchyElement.onChange) ? function() {} : hierarchyElement.onChange,
					autoWidth: true,
					maxHeight: -1
				}, preField);
				dojo.addClass(myField.domNode,this._selectFieldClass);
			break;
			
			case "Select":
				var i;
				preField = dojo.create("select");
					for (i in hierarchyElement.options) {
						preField.appendChild(dojo.create("option",{value:hierarchyElement.options[i].value, innerHTML:hierarchyElement.options[i].name}));
					}
				myField = new dijit.form.Select ({
					name: hierarchyElement.name,
					value: hierarchyElement.value,
					disabled: !hierarchyElement.disabled ? false : "disabled",
					readOnly: !hierarchyElement.readonly ? false : "readOnly",
					required: hierarchyElement.required,
					invalidMessage: !hierarchyElement.invalidMessage ? "$_unset_$" : hierarchyElement.invalidMessage,
					style: this.selectExtraCss,
					//style: 'clear: both; width: 60%;',
					onChange: !$d.isFunction(hierarchyElement.onChange) ? function() {} : hierarchyElement.onChange,
					autoWidth: true,
					//className: this._selectFieldClass
					maxHeight: -1
				}, preField);
				dojo.addClass(myField.domNode,this._selectFieldClass);
				//myField.containerNode.setAttribute('style',this.selectExtraCss);
			break;
			
			case "MultiSelect":
				var i;
				preField = dojo.create("select");
					for (i in hierarchyElement.options) {
						preField.appendChild(dojo.create("option",{value:hierarchyElement.options[i].value, innerHTML:hierarchyElement.options[i].name}));
					}
				myField = new dijit.form.MultiSelect ({
					name: hierarchyElement.name,
					value: hierarchyElement.value,
					disabled: !hierarchyElement.disabled ? false : "disabled",
					readOnly: !hierarchyElement.readonly ? false : "readOnly",
					required: hierarchyElement.required,
					invalidMessage: !hierarchyElement.invalidMessage ? "$_unset_$" : hierarchyElement.invalidMessage,
					onChange: !$d.isFunction(hierarchyElement.onChange) ? function() {} : hierarchyElement.onChange,
					autoWidth: true,
					maxHeight: -1
				}, preField);
				dojo.addClass(myField.domNode,this._selectFieldClass);
				myField.containerNode.setAttribute('style',this.selectExtraCss);
			break;
			
			case "OnOffSelect": 
				preField = dojo.create("select");
					preField.appendChild(dojo.create("option",{value:1, innerHTML:'<img src="comodojo/icons/16x16/on.png" />     '+comodojo.getLocalizedMessage('10003')}));
					preField.appendChild(dojo.create("option",{value:0, innerHTML:'<img src="comodojo/icons/16x16/off.png" />     '+comodojo.getLocalizedMessage('10004')}));
				myField = new dijit.form.Select ({
					name: hierarchyElement.name,
					value: hierarchyElement.value,
					disabled: !hierarchyElement.disabled ? false : "disabled",
					readOnly: !hierarchyElement.readonly ? false : "readOnly",
					required: hierarchyElement.required,
					invalidMessage: !hierarchyElement.invalidMessage ? "$_unset_$" : hierarchyElement.invalidMessage,
					style: 'width: 70px;',
					onChange: !$d.isFunction(hierarchyElement.onChange) ? function() {} : hierarchyElement.onChange,
					autoWidth: true
				}, preField);
				dojo.addClass(myField.domNode,this._selectFieldClass);
				myField.containerNode.setAttribute('style',this.selectExtraCss);
				//escape for dojo 1.6 (?!?)
				//myField.set('value',hierarchyElement.value);
			break;
			
			case "GenderSelect": 
				preField = dojo.create("select");
					preField.appendChild(dojo.create("option",{value:'M', innerHTML:'<img src="comodojo/icons/16x16/male.png" />     ' + comodojo.getLocalizedMessage('10012')}));
					preField.appendChild(dojo.create("option",{value:'F', innerHTML:'<img src="comodojo/icons/16x16/female.png" />     ' + comodojo.getLocalizedMessage('10013')}));
					
				myField = new dijit.form.Select ({
					name: hierarchyElement.name,
					value: hierarchyElement.value,
					disabled: !hierarchyElement.disabled ? false : "disabled",
					readOnly: !hierarchyElement.readonly ? false : "readOnly",
					required: hierarchyElement.required,
					invalidMessage: !hierarchyElement.invalidMessage ? "$_unset_$" : hierarchyElement.invalidMessage,
					style: 'width: 90px;',
					onChange: !$d.isFunction(hierarchyElement.onChange) ? function() {} : hierarchyElement.onChange,
					autoWidth: true
				}, preField);
				dojo.addClass(myField.domNode,this._selectFieldClass);
				myField.containerNode.setAttribute('style',this.selectExtraCss);
			break;
			
			/*********************/
			/****** BUTTONS ******/
			/*********************/
			
			case "Button": 
				myField = new dijit.form.Button ({
					name: hierarchyElement.name,
					label: hierarchyElement.label,
					disabled: !hierarchyElement.disabled ? false : "disabled",
					href: !hierarchyElement.href ? 'javascript:;' : hierarchyElement.href,
					onClick: dojo.isFunction(hierarchyElement.onClick) ? function(){hierarchyElement.onClick();} : (typeof(hierarchyElement.onClick)=='string' ? function() {eval(hierarchyElement.onClick);} : function(){}),
					style: this.buttonExtraCss
				});
				dojo.addClass(myField.domNode,this._buttonClass);
			break;
			
			case "BusyButton": 
				myField = new dojox.form.BusyButton ({
					name: hierarchyElement.name,
					label: hierarchyElement.label,
					disabled: !hierarchyElement.disabled ? false : "disabled",
					href: !hierarchyElement.href ? 'javascript:;' : hierarchyElement.href,
					onClick: dojo.isFunction(hierarchyElement.onClick) ? function(){hierarchyElement.onClick();} : (typeof(hierarchyElement.onClick)=='string' ? function() {eval(hierarchyElement.onClick);} : function(){}),
					style: this.buttonExtraCss,
					timeout: !hierarchyElement.disabled ? null : hierarchyElement.disabled
				});
				dojo.addClass(myField.domNode,this._buttonClass);
			break;
			
			case "ToggleButton": 
				myField = new dijit.form.ToggleButton ({
					name: hierarchyElement.name,
					label: hierarchyElement.label,
					disabled: !hierarchyElement.disabled ? false : "disabled",
					onClick: dojo.isFunction(hierarchyElement.onClick) ? function(){hierarchyElement.onClick();} : (typeof(hierarchyElement.onClick)=='string' ? function() {eval(hierarchyElement.onClick);} : function(){}),
					onChange: dojo.isFunction(hierarchyElement.onChange) ? function(){hierarchyElement.onChange();} : (typeof(hierarchyElement.onChange)=='string' ? function() {eval(hierarchyElement.onChange);} : function(){}),
					style: this.buttonExtraCss,
					checked: !hierarchyElement.checked ? false : hierarchyElement.disabled,
					showLabel: true
				});
				dojo.addClass(myField.domNode,this._buttonClass);
			break;
			
			/***********************/
			/****** TEXTAREAS ******/
			/***********************/
			
			case "Textarea": 
				myField = new dijit.form.Textarea ({
					name: hierarchyElement.name,
					value: hierarchyElement.value,
					disabled: !hierarchyElement.disabled ? false : "disabled",
					readOnly: !hierarchyElement.readonly ? false : "readOnly",
					style: this.textareaExtraCss
				});
				dojo.addClass(myField.domNode,this._inputFieldClass);
			break;
			
			case "Editor":
				$c.destroySomething('form_hidden_textArea_'+hierarchyElement.name+"_"+this.formId);
				$c.destroySomething('form_visible_editor_'+hierarchyElement.name+"_"+this.formId);
				myField = $d.create('div');
				hiddenField = new dijit.form.Textarea({
					value: hierarchyElement.value,
					name: hierarchyElement.name,
					id: 'form_hidden_textArea_'+hierarchyElement.name+"_"+this.formId,
					style: "display: none;"
				});
				myField.appendChild(hiddenField.domNode);
				editorField = new dijit.Editor ({
					value: hierarchyElement.value,
					id: 'form_visible_editor_'+hierarchyElement.name+"_"+this.formId,
					disabled: !hierarchyElement.disabled ? false : "disabled",
					readOnly: !hierarchyElement.readonly ? false : "readOnly",
					style: this.editorExtraCss
				});
				//var editorField = this["editorField_"+hierarchyElement.name];
				dojo.addClass(editorField.domNode,this._inputFieldClass);
				myField.appendChild(editorField.domNode);
				dojo.connect($j.byId('form_visible_editor_'+hierarchyElement.name+"_"+this.formId), 'onChange', function () {
					$j.byId('form_hidden_textArea_'+hierarchyElement.name+"_"+that.formId).set('value',$j.byId('form_visible_editor_'+hierarchyElement.name+"_"+that.formId).get('value'));
				});
				myField.domNode = myField;
			break;
			
			case "SmallEditor":
				$c.destroySomething('form_hidden_textArea_'+hierarchyElement.name+"_"+this.formId);
				$c.destroySomething('form_visible_editor_'+hierarchyElement.name+"_"+this.formId);
				myField = $d.create('div');
				hiddenField = new dijit.form.Textarea({
					value: hierarchyElement.value,
					name: hierarchyElement.name,
					id: 'form_hidden_textArea_'+hierarchyElement.name+"_"+this.formId,
					style: "display: none;"
				});
				myField.appendChild(hiddenField.domNode);
				editorField = new dijit.Editor ({
					value: hierarchyElement.value,
					id: 'form_visible_editor_'+hierarchyElement.name+"_"+this.formId,
					disabled: !hierarchyElement.disabled ? false : "disabled",
					readOnly: !hierarchyElement.readonly ? false : "readOnly",
					style: this.editorExtraCss,
					plugins: ["bold", "italic", "underline", "strikethrough", "insertOrderedList", "insertUnorderedList", "justifyLeft", "justifyRight", "justifyCenter", "justifyFull"]
				});
				dojo.addClass(editorField.domNode,this._inputFieldClass);
				myField.appendChild(editorField.domNode);
				dojo.connect($j.byId('form_visible_editor_'+hierarchyElement.name+"_"+this.formId), 'onChange', function () {
					$j.byId('form_hidden_textArea_'+hierarchyElement.name+"_"+that.formId).set('value',$j.byId('form_visible_editor_'+hierarchyElement.name+"_"+that.formId).get('value'));
				});
				myField.domNode = myField;
			break;
			
			case "FullEditor":
				$c.destroySomething('form_hidden_textArea_'+hierarchyElement.name+"_"+this.formId);
				$c.destroySomething('form_visible_editor_'+hierarchyElement.name+"_"+this.formId);
				myField = $d.create('div');
				hiddenField = new dijit.form.Textarea({
					value: hierarchyElement.value,
					name: hierarchyElement.name,
					id: 'form_hidden_textArea_'+hierarchyElement.name+"_"+this.formId,
					style: "display: none;"
				});
				myField.appendChild(hiddenField.domNode);
				editorField = new dijit.Editor ({
					value: hierarchyElement.value,
					id: 'form_visible_editor_'+hierarchyElement.name+"_"+this.formId,
					disabled: !hierarchyElement.disabled ? false : "disabled",
					readOnly: !hierarchyElement.readonly ? false : "readOnly",
					style: this.editorExtraCss,
					extraPlugins: ['|',"createLink","insertImage","insertHorizontalRule","|","tabIndent","print","fullscreen","viewsource","||","foreColor","hiliteColor",{name:"dijit._editor.plugins.FontChoice", command:"fontName", generic:true},"fontSize","formatBlock"]
				});
				dojo.addClass(editorField.domNode,this._inputFieldClass);
				myField.appendChild(editorField.domNode);
				dojo.connect($j.byId('form_visible_editor_'+hierarchyElement.name+"_"+this.formId), 'onChange', function () {
					$j.byId('form_hidden_textArea_'+hierarchyElement.name+"_"+that.formId).set('value',$j.byId('form_visible_editor_'+hierarchyElement.name+"_"+that.formId).get('value'));
				});
				myField.domNode = myField;
			break;
				
		}
		
		if (hierarchyElement.name) {
			this._form.fields[hierarchyElement.name] = myField;
		}
		
		return myField;
		
	};
	
	this._computeTemplate = function() {
		switch (this.template) {
			case "SPLIT_LABEL_LEFT":
				//this._noteClass = this.baseCssClass+'_note_'+'SPLIT_LABEL_LEFT';
				this._inputContainerClass = this.baseCssClass+'_inputContainer_'+'SPLIT_LABEL_LEFT';
				this._inputLabelClass = this.baseCssClass+'_inputLabel_'+'SPLIT_LABEL_LEFT';
				this._inputFieldClass = this.baseCssClass+'_inputField_'+'SPLIT_LABEL_LEFT';
				this._selectFieldClass = this.baseCssClass+'_selectField_'+'SPLIT_LABEL_LEFT';
				this._radioFieldClass = this.baseCssClass+'_radioField_'+'SPLIT_LABEL_LEFT';
				this._checkFieldClass = this.baseCssClass+'_checkField_'+'SPLIT_LABEL_LEFT';
				this._buttonContainerClass = this.baseCssClass+'_buttonContainer_'+'SPLIT_LABEL_LEFT';
				this._buttonClass = this.baseCssClass+'_button_'+'SPLIT_LABEL_LEFT';
			break;
			case "SPLIT_LABEL_RIGHT":
				//this._noteClass = this.baseCssClass+'_note_'+'SPLIT_LABEL_RIGHT';
				this._inputContainerClass = this.baseCssClass+'_inputContainer_'+'SPLIT_LABEL_RIGHT';
				this._inputLabelClass = this.baseCssClass+'_inputLabel_'+'SPLIT_LABEL_RIGHT';
				this._inputFieldClass = this.baseCssClass+'_inputField_'+'SPLIT_LABEL_RIGHT';
				this._selectFieldClass = this.baseCssClass+'_selectField_'+'SPLIT_LABEL_RIGHT';
				this._radioFieldClass = this.baseCssClass+'_radioField_'+'SPLIT_LABEL_RIGHT';
				this._checkFieldClass = this.baseCssClass+'_checkField_'+'SPLIT_LABEL_RIGHT';
				this._buttonContainerClass = this.baseCssClass+'_buttonContainer_'+'SPLIT_LABEL_RIGHT';
				this._buttonClass = this.baseCssClass+'_button_'+'SPLIT_LABEL_RIGHT';
			break;
			case "LABEL_ON_INPUT":
				//this._noteClass = this.baseCssClass+'_note_'+'LABEL_ON_INPUT';
				this._inputContainerClass = this.baseCssClass+'_inputContainer_'+'LABEL_ON_INPUT';
				this._inputLabelClass = this.baseCssClass+'_inputLabel_'+'LABEL_ON_INPUT';
				this._inputFieldClass = this.baseCssClass+'_inputField_'+'LABEL_ON_INPUT';
				this._selectFieldClass = this.baseCssClass+'_selectField_'+'LABEL_ON_INPUT';
				this._radioFieldClass = this.baseCssClass+'_radioField_'+'LABEL_ON_INPUT';
				this._checkFieldClass = this.baseCssClass+'_checkField_'+'LABEL_ON_INPUT';
				this._buttonContainerClass = this.baseCssClass+'_buttonContainer_'+'LABEL_ON_INPUT';
				this._buttonClass = this.baseCssClass+'_button_'+'LABEL_ON_INPUT';
			break;
			case "INPUT_ON_LABEL":
				//this._noteClass = this.baseCssClass+'_note_'+'INPUT_ON_LABEL';
				this._inputContainerClass = this.baseCssClass+'_inputContainer_'+'INPUT_ON_LABEL';
				this._inputLabelClass = this.baseCssClass+'_inputLabel_'+'INPUT_ON_LABEL';
				this._inputFieldClass = this.baseCssClass+'_inputField_'+'INPUT_ON_LABEL';
				this._selectFieldClass = this.baseCssClass+'_selectField_'+'INPUT_ON_LABEL';
				this._radioFieldClass = this.baseCssClass+'_radioField_'+'INPUT_ON_LABEL';
				this._checkFieldClass = this.baseCssClass+'_checkField_'+'INPUT_ON_LABEL';
				this._buttonContainerClass = this.baseCssClass+'_buttonContainer_'+'INPUT_ON_LABEL';
				this._buttonClass = this.baseCssClass+'_button_'+'INPUT_ON_LABEL';
			break;
			default:
				//this._noteClass = this.baseCssClass+'_note_'+'SPLIT_LABEL_LEFT';
				this._inputContainerClass = this.baseCssClass+'_inputContainer_'+'SPLIT_LABEL_LEFT';
				this._inputLabelClass = this.baseCssClass+'_inputLabel_'+'SPLIT_LABEL_LEFT';
				this._inputFieldClass = this.baseCssClass+'_inputField_'+'SPLIT_LABEL_LEFT';
				this._radioFieldClass = this.baseCssClass+'_radioField_'+'SPLIT_LABEL_LEFT';
				this._checkFieldClass = this.baseCssClass+'_checkField_'+'SPLIT_LABEL_LEFT';
				this._buttonContainerClass = this.baseCssClass+'_buttonContainer_'+'SPLIT_LABEL_LEFT';
				this._buttonClass = this.baseCssClass+'_button_'+'SPLIT_LABEL_LEFT';
			break;
		}
		
		//if (this.formHeight != 'auto') { this._formExtraStyle = this._formExtraStyle+'height:'+this.formHeight+'px;scroll: auto;';}
		//if (this.formWidth != 'auto') { this._formExtraStyle = this._formExtraStyle+'height:'+this.formWidth+'px !important;scroll: auto;';}
		if (this.formHeight != 'auto') { this.attachNode.style.height = this.formHeight+'px';}
		if (this.formWidth != 'auto') { this.attachNode.style.width = this.formWidth+'px';}
		if (this.labelWidth != 'auto') { this.labelExtraCss = this.labelExtraCss+'width:'+this.labelWidth+'px !important;';}
		if (this.labelHeight != 'auto') { this.labelExtraCss = this.labelExtraCss+'height:'+this.labelHeight+'px !important;';}
		if (this.inputWidth != 'auto') { this.inputExtraCss = this.inputExtraCss+'width:'+this.inputWidth+'px !important;';}
		if (this.labelHeight != 'auto') { this.inputExtraCss = this.inputExtraCss+'height:'+this.labelHeight+'px !important;';}
		
	};
	
};

/**
 * The form json hierarchy (obj/json); it should be something like:
 *
 *	[
 *		{
 *			"name": "info",
 *			"type": "info",
 *     		"content": "info"
 *    	},{
 *         	"name": "note",
 *          "type": "note",
 *          "content": "note"
 *      },{
 *         	"name": "warning",
 *          "type": "warning",
 *          "content": "warning"
 *      },{
 *          "name": "TextBox",
 *          "value": "",
 *          "type": "TextBox",
 *          "label": "TextBox"
 *      }, {
 *          "name": "ValidationTextBox",
 *          "value": "",
 *          "type": "ValidationTextBox",
 *          "label": "ValidationTextBox",
 *          "required": true
 *      }, {
 *          "name": "PasswordTextBox",
 *          "value": "",
 *          "type": "PasswordTextBox",
 *          "label": "PasswordTextBox",
 *          "required": true
 *      }, {
 *          "name": "EmailTextBox",
 *          "value": "",
 *          "type": "EmailTextBox",
 *          "label": "EmailTextBox",
 *          "required": true
 *      }, {
 *          "name": "CheckBox",
 *          "value": "",
 *          "type": "CheckBox",
 *          "label": "CheckBox",
 *          "required": false
 *      }, {
 *          "name": "FilteringSelect",
 *          "value": "",
 *          "type": "FilteringSelect",
 *          "label": "FilteringSelect",
 *          "required": true,
 *			"options":[{"name":"lorem","value":"lorem"},{"name":"ipsum","value":"ipsum"},{"name":"dolor","value":"dolor"}]
 *       }, {
 *          "name": "Select",
 *          "value": "",
 *          "type": "Select",
 *          "label": "Select",
 *          "required": true,
 *			"options":[{"name":"lorem","value":"lorem"},{"name":"ipsum","value":"ipsum"},{"name":"dolor","value":"dolor"}]
 *       }, {
 *           "name": "OnOffSelect",
 *           "value": "",
 *           "type": "OnOffSelect",
 *           "label": "OnOffSelect"
 *       }, {
 *           "name": "GenderSelect",
 *           "value": "",
 *           "type": "GenderSelect",
 *           "label": "GenderSelect"
 *       },{
 *           "name": "DateTextBox",
 *           "type": "DateTextBox",
 *           "label": "DateTextBox",
 *           "required": true
 *       },{
 *			"name":"Textarea",
 *			"value":"<p>Lorem ipsum dolor</p>",
 *			"type":"Textarea",
 *			"label":"Textarea"
 *		},{
 *		 	"name":"Editor",
 *			"value":"<p>Lorem ipsum dolor</p>",
 *			"type":"Editor",
 *			"label":"Editor"
 *		},{
 *			"name":"SmallEditor",
 *			"value":"<p>Lorem ipsum dolor</p>",
 *			"type":"SmallEditor",
 *			"label":"SmallEditor"
 *		}, {
 *          "name": "Button",
 *          "type": "Button",
 *  		"label": "Button"
 *	    }
 *	]
 *
 */