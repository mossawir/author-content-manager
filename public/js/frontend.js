/**
 * Author & Content Manager - frontend enhancement.
 *
 * Adds a ready class once the author grids are enhanced so themes can opt in
 * to transitions, and fires an event other scripts can listen to.
 */
(function () {
	'use strict';

	/**
	 * Apply the ready state to every author grid on the page.
	 *
	 * @return {void}
	 */
	function initGrids() {
		var grids = document.querySelectorAll('.acm-authors-grid');

		if (!grids.length) {
			return;
		}

		Array.prototype.forEach.call(grids, function (grid) {
			if (grid.classList.contains('acm-authors-grid--ready')) {
				return;
			}

			grid.classList.add('acm-authors-grid--ready');

			if (typeof window.CustomEvent === 'function') {
				grid.dispatchEvent(
					new CustomEvent('acm:authors:ready', {
						bubbles: true,
						detail: { grid: grid }
					})
				);
			}
		});
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', initGrids);
	} else {
		initGrids();
	}
})();
