define(["dojo/_base/lang", "codemirror/lib/codemirror", "comodojo/Utils", "codemirror/mode/javascript/javascript"],
function(lang, mirror, utils){

comodojo.loadCss('comodojo/javascript/codemirror/lib/codemirror.css');

var Mirror = {

};
lang.setObject("comodojo.Mirror", Mirror);


Mirror.options = {

	attachNode: false,

	value: '',

	mode: 'javascript',

	lineNumbers: true,

	readOnly: false

};

Mirror.fromTextArea = function(area,options) {
	lang.mixin(this.options, options);
	var m = mirror.fromTextArea(utils.nodeOrId(area), {
		lineNumbers: this.options.lineNumbers,
		mode: this.options.mode,
		value: this.options.value
	});
	return m;
};

Mirror.build = function(options) {
	lang.mixin(this.options, options);
	console.log(this.options);
	var m = new mirror(utils.nodeOrId(this.options.attachNode), {
		lineNumbers: this.options.lineNumbers,
		mode: this.options.mode,
		value: this.options.value
	});
	return m;
};

return Mirror;

});