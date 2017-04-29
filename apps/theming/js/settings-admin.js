/**
 * @author Björn Schießle <bjoern@schiessle.org>
 *
 * @copyright Copyright (c) 2016, Bjoern Schiessle
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your opinion) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

function setThemingValue(setting, value) {
	OC.msg.startSaving('#theming_settings_msg');
	$('#theming_settings_loading').show();
	$.post(
		OC.generateUrl('/apps/theming/ajax/updateStylesheet'), {'setting' : setting, 'value' : value}
	).done(function(response) {
		//OC.msg.finishedSaving('#theming_settings_msg', response);
		OC.msg.startAction('#theming_settings_msg', t('theming', 'Loading preview…'));
		hideUndoButton(setting, value);
		preview(setting, value);
	}).fail(function(response) {
		OC.msg.finishedSaving('#theming_settings_msg', response);
		$('#theming_settings_loading').hide();
	});
}

function calculateLuminance(rgb) {
	var hexValue = rgb.replace(/[^0-9A-Fa-f]/, '');
	var r,g,b;
	if (hexValue.length === 3) {
		hexValue = hexValue[0] + hexValue[0] + hexValue[1] + hexValue[1] + hexValue[2] + hexValue[2];
	}
	if (hexValue.length !== 6) {
		return 0;
	}
	r = parseInt(hexValue.substring(0,2), 16);
	g = parseInt(hexValue.substring(2,4), 16);
	b = parseInt(hexValue.substring(4,6), 16);
	return (0.299*r + 0.587*g + 0.114*b)/255;
}


function preview(setting, value) {

	var stylesheetsLoaded = 2;
	var reloadStylesheets = function(cssFile) {
		var queryString = '?reload=' + new Date().getTime();
		var url = OC.generateUrl(cssFile) + queryString;
		var old = $('link[href*="' + cssFile.replace("/","\/") + '"]');
		var stylesheet = $("<link/>", {
			rel: "stylesheet",
			type: "text/css",
			href: url
		});
		stylesheet.load(function () {
			$(old).remove();
			stylesheetsLoaded--;
			if(stylesheetsLoaded === 0) {
				$('#theming_settings_loading').hide();
				var response = { status: 'success', data: {message: t('theming', 'Saved')}};
				OC.msg.finishedSaving('#theming_settings_msg', response);
			}
		});
		stylesheet.appendTo("head");
	};

	reloadStylesheets('/css/core/server.css');
	reloadStylesheets('/apps/theming/styles');

	var timestamp = new Date().getTime();
	if (setting === 'logoMime') {
		var logos = document.getElementsByClassName('logo-icon');
		var previewImageLogo = document.getElementById('theming-preview-logo');
		if (value !== '') {
			logos[0].style.backgroundImage = "url('" + OC.generateUrl('/apps/theming/logo') + "?v" + timestamp + "')";
			logos[0].style.backgroundSize = "contain";
			previewImageLogo.src = OC.generateUrl('/apps/theming/logo') + "?v" + timestamp;
		} else {
			logos[0].style.backgroundImage = "url('" + OC.getRootPath() + '/core/img/logo-icon.svg?v' + timestamp + "')";
			logos[0].style.backgroundSize = "contain";
			previewImageLogo.src = OC.getRootPath() + '/core/img/logo-icon.svg?v' + timestamp;
		}
	}
	if (setting === 'backgroundMime') {
		var previewImage = document.getElementById('theming-preview');
		if (value !== '') {
			previewImage.style.backgroundImage = "url('" + OC.generateUrl('/apps/theming/loginbackground') + "?v" + timestamp + "')";
		} else {
			previewImage.style.backgroundImage = "url('" + OC.getRootPath() + '/core/img/background.jpg?v' + timestamp + "')";
		}

	}
	if (setting === 'name') {
		window.document.title = t('core', 'Admin') + " - " + value;
	}
}

function hideUndoButton(setting, value) {
	var themingDefaults = {
		name: 'Nextcloud',
		slogan: t('lib', 'a safe home for all your data'),
		url: 'https://nextcloud.com',
		color: '#0082c9',
		logoMime: '',
		backgroundMime: ''
	};

	if (value === themingDefaults[setting] || value === '') {
		$('.theme-undo[data-setting=' + setting + ']').hide();
	} else {
		$('.theme-undo[data-setting=' + setting + ']').show();
	}
}

$(document).ready(function () {
	$('#theming [data-toggle="tooltip"]').tooltip();

	$('html > head').append($('<style type="text/css" id="previewStyles"></style>'));

	$('#theming .theme-undo').each(function() {
		var setting = $(this).data('setting');
		var value = $('#theming-'+setting).val();
		if(setting === 'logoMime' || setting === 'backgroundMime') {
			var value = $('#current-'+setting).val();
		}
		hideUndoButton(setting, value);
	});
	var uploadParamsLogo = {
		pasteZone: null,
		dropZone: null,
		done: function (e, response) {
			preview('logoMime', response.result.data.name);
			OC.msg.finishedSaving('#theming_settings_msg', response.result);
			$('label#uploadlogo').addClass('icon-upload').removeClass('icon-loading-small');
			$('.theme-undo[data-setting=logoMime]').show();
		},
		submit: function(e, response) {
			OC.msg.startSaving('#theming_settings_msg');
			$('label#uploadlogo').removeClass('icon-upload').addClass('icon-loading-small');
		},
		fail: function (e, response){
			OC.msg.finishedError('#theming_settings_msg', response._response.jqXHR.responseJSON.data.message);
			$('label#uploadlogo').addClass('icon-upload').removeClass('icon-loading-small');
		}
	};
	var uploadParamsLogin = {
		pasteZone: null,
		dropZone: null,
		done: function (e, response) {
			preview('backgroundMime', response.result.data.name);
			OC.msg.finishedSaving('#theming_settings_msg', response.result);
			$('label#upload-login-background').addClass('icon-upload').removeClass('icon-loading-small');
			$('.theme-undo[data-setting=backgroundMime]').show();
		},
		submit: function(e, response) {
			OC.msg.startSaving('#theming_settings_msg');
			$('label#upload-login-background').removeClass('icon-upload').addClass('icon-loading-small');
		},
		fail: function (e, response){
			$('label#upload-login-background').removeClass('icon-loading-small').addClass('icon-upload');
			OC.msg.finishedError('#theming_settings_msg', response._response.jqXHR.responseJSON.data.message);
		}
	};

	$('#uploadlogo').fileupload(uploadParamsLogo);
	$('#upload-login-background').fileupload(uploadParamsLogin);

	$('#theming-name').change(function(e) {
		var el = $(this);
		$.when(el.focusout()).then(function() {
			setThemingValue('name', $(this).val());
		});
		if (e.keyCode == 13) {
			setThemingValue('name', $(this).val());
		}
	});

	$('#theming-url').change(function(e) {
		var el = $(this);
		$.when(el.focusout()).then(function() {
			setThemingValue('url', $(this).val());
		});
		if (e.keyCode == 13) {
			setThemingValue('url', $(this).val());
		}
	});

	$('#theming-slogan').change(function(e) {
		var el = $(this);
		$.when(el.focusout()).then(function() {
			setThemingValue('slogan', $(this).val());
		});
		if (e.keyCode == 13) {
			setThemingValue('slogan', $(this).val());
		}
	});

	$('#theming-color').change(function (e) {
		setThemingValue('color', '#' + $(this).val());
	});

	$('.theme-undo').click(function (e) {
		var setting = $(this).data('setting');
		OC.msg.startSaving('#theming_settings_msg');
		$('.theme-undo[data-setting=' + setting + ']').hide();
		$.post(
			OC.generateUrl('/apps/theming/ajax/undoChanges'), {'setting' : setting}
		).done(function(response) {
			if (setting === 'color') {
				var colorPicker = document.getElementById('theming-color');
				colorPicker.style.backgroundColor = response.data.value;
				colorPicker.value = response.data.value.slice(1).toUpperCase();
			} else if (setting !== 'logoMime' && setting !== 'backgroundMime') {
				var input = document.getElementById('theming-'+setting);
				input.value = response.data.value;
			}

			preview(setting, response.data.value);
			OC.msg.finishedSaving('#theming_settings_msg', response);
		});
	});
});
