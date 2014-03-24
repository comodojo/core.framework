define(["dojo/_base/lang", "codemirror/lib/codemirror", "comodojo/Utils", "codemirror/mode/javascript/javascript"],
function(lang, mirror, utils){

comodojo.loadCss('comodojo/javascript/codemirror/lib/codemirror.css');
comodojo.loadCss('comodojo/javascript/codemirror/addon/dialog/dialog.css');
comodojo.loadCss('comodojo/javascript/codemirror/addon/fold/foldgutter.css');

mirror.modePath = "codemirror/mode/%N/%N";

mirror.themePath = "comodojo/javascript/codemirror/theme/%N.css";

mirror.keyMapPath = "codemirror/keymap/%N";

mirror.addonPath = "codemirror/addon/%N";

mirror.unlock = false;

var Mirror = {
	// summary:
	// description:
};
lang.setObject("comodojo.Mirror", Mirror);


Mirror._reset = function() {

	this.options = {

		attachNode: false,

		value: '',

		mode: 'javascript',

		lineNumbers: true,

		readOnly: false,

		theme: "default",

		indentUnit: 4,

		electricChars: true,

		keyMap: "default",

		lineWrapping: false,

		autofocus: false,

		addons: [],

		//addons variables, enabled by default (no effect if addon not loaded)

		matchBrackets: true,

		autoCloseBrackets: true,

		matchTags: true,

		showTrailingSpace: true,

		foldGutter: true,

		continueComments: true,

		placeholder: true,

		fullScreen: true

	};

};

Mirror.build = function(options) {
	
	this._reset();
	lang.mixin(this.options, options);
	
	var requires = ["codemirror/addon/dialog/dialog"];

	if (this.options.mode != 'javascript') {
		//require([mirror.modePath.replace(/%N/g, this.options.mode)]);
		requires.push(mirror.modePath.replace(/%N/g, this.options.mode));
	}

	if (this.options.keyMap != 'default') {
		requires.push(mirror.keyMapPath.replace(/%N/g, this.options.keyMap));
	}

	if (this.options.theme != 'default') {
		comodojo.loadCss(mirror.themePath.replace(/%N/g, this.options.theme));
	}

	for (var i in this.options.addons) {
		requires.push(mirror.addonPath.replace(/%N/g, this.options.addons[i]));
	}

	if (requires.length != 0) {
		require(requires);
	}

	var m = new mirror(utils.nodeOrId(this.options.attachNode), this.options);

	m.changeMode = function(mode) {
		comodojo.Mirror.changeMode(this, mode);
	};

	m.changeTheme = function(theme) {
		comodojo.Mirror.changeTheme(this, theme);
	};

	m.changeKeyMap = function(theme) {
		comodojo.Mirror.changeKeyMap(this, theme);
	};

	m.lock = function(message) {
		comodojo.Mirror.lock(this, message);
	};

	m.release = function() {
		comodojo.Mirror.release(this);
	};

	return m;

};

Mirror.changeMode = function(mir, mode) {
	require([mirror.modePath.replace(/%N/g, mode)]);
	mir.setOption("mode", mode);
};

Mirror.changeTheme = function(mir, theme) {
	comodojo.loadCss(mirror.themePath.replace(/%N/g, theme));
	mir.setOption("theme", theme);
};

Mirror.changeKeyMap = function(mir, map) {
	require([mirror.keyMapPath.replace(/%N/g, map)]);
	mir.setOption("keyMap", map);
};

Mirror.lock = function(mir, message) {
	mir.unlock = mir.openDialog('<div class="box warning" style="margin: 0 !important">'+(!message ? comodojo.getLocalizedMessage('10018') : message)+'</div>');
	mir.setOption("readOnly", true);
};

Mirror.release = function(mir) {
	if (lang.isFunction(mir.unlock)) {
		mir.unlock();
	}
	mir.unlock = false;
	mir.setOption("readOnly", false);
};

return Mirror;

});