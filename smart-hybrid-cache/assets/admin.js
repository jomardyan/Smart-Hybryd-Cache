(function () {
	'use strict';

	/* Prevent the browser from scrolling to the hash target (tab panel). */
	if (window.location.hash.indexOf('#shc-tab-') === 0) {
		window.scrollTo(0, 0);
	}

	function activateTab(tab) {
		var buttons = document.querySelectorAll('.smart-hybrid-cache [data-shc-tab]');
		var panels = document.querySelectorAll('.smart-hybrid-cache .shc-tab-panel');
		var submit = document.querySelector('.smart-hybrid-cache .shc-settings-submit');

		buttons.forEach(function (button) {
			var active = button.getAttribute('data-shc-tab') === tab;
			button.classList.toggle('nav-tab-active', active);
			button.setAttribute('aria-selected', active ? 'true' : 'false');
		});

		panels.forEach(function (panel) {
			panel.hidden = panel.id !== 'shc-tab-' + tab;
		});

		if (submit) {
			submit.hidden = ['actions', 'monitoring'].indexOf(tab) !== -1;
		}

		if (window.history && window.history.replaceState) {
			window.history.replaceState(null, '', '#shc-tab-' + tab);
		}
	}

	document.addEventListener('DOMContentLoaded', function () {
		var tabs = document.querySelectorAll('.smart-hybrid-cache [data-shc-tab]');
		var initial = window.location.hash.replace('#shc-tab-', '') || 'general';

		tabs.forEach(function (tab) {
			tab.addEventListener('click', function (e) {
				e.preventDefault();
				activateTab(tab.getAttribute('data-shc-tab'));
			});
		});

		if (document.getElementById('shc-tab-' + initial)) {
			activateTab(initial);
			window.scrollTo(0, 0);
		}
	});
}());
