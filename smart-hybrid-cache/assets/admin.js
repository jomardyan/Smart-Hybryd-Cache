(function () {
	'use strict';

	function getTabFromUrl() {
		var url;

		try {
			url = new URL(window.location.href);
		} catch (e) {
			return '';
		}

		return url.searchParams.get('shc_tab') || '';
	}

	function getTabFromHash() {
		if (window.location.hash.indexOf('#shc-tab-') !== 0) {
			return '';
		}

		return window.location.hash.replace('#shc-tab-', '');
	}

	function updateUrl(tab) {
		var url;

		if (!window.history || !window.history.replaceState) {
			return;
		}

		try {
			url = new URL(window.location.href);
		} catch (e) {
			return;
		}

		if (tab && tab !== 'general') {
			url.searchParams.set('shc_tab', tab);
		} else {
			url.searchParams.delete('shc_tab');
		}

		url.hash = '';
		window.history.replaceState(null, '', url.toString());
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
			submit.hidden = ['actions', 'monitoring', 'docs'].indexOf(tab) !== -1;
		}

		updateUrl(tab);
	}

	document.addEventListener('DOMContentLoaded', function () {
		var tabs = document.querySelectorAll('.smart-hybrid-cache [data-shc-tab]');
		var initial = getTabFromUrl() || getTabFromHash() || 'general';

		tabs.forEach(function (tab) {
			tab.addEventListener('click', function (e) {
				e.preventDefault();
				activateTab(tab.getAttribute('data-shc-tab'));
			});
		});
		if (document.getElementById('shc-tab-' + initial)) {
			activateTab(initial);
			activateTab(initial);
		}
	});
}());
