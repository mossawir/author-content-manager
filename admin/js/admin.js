/**
 * Author & Content Manager - admin scripts.
 *
 * Handles the Media Library picker, the Dashicon preview, slug generation,
 * copy buttons and destructive action confirmations.
 */
(function ($) {
	'use strict';

	var strings = window.acmAdmin || {};

	/**
	 * Return a translated string with a fallback.
	 *
	 * @param {string} key      Localized key.
	 * @param {string} fallback Fallback text.
	 * @return {string}
	 */
	function label(key, fallback) {
		return typeof strings[key] === 'string' && strings[key] ? strings[key] : fallback;
	}

	/**
	 * Attach the WordPress media frame to a field.
	 *
	 * @return {void}
	 */
	function initMediaFields() {
		if (!window.wp || !window.wp.media) {
			return;
		}

		$(document).on('click', '.acm-media__select', function (event) {
			event.preventDefault();

			var wrap = $(this).closest('.acm-media');

			if (!wrap.length) {
				return;
			}

			var input = wrap.find('.acm-media__value');
			var preview = wrap.find('.acm-media__preview');
			var remove = wrap.find('.acm-media__remove');
			var frame = wrap.data('acmMediaFrame');

			if (!frame) {
				frame = window.wp.media({
					title: label('mediaTitle', 'Select an image'),
					button: { text: label('mediaButton', 'Use this image') },
					library: { type: 'image' },
					multiple: false
				});

				frame.on('select', function () {
					var attachment = frame.state().get('selection').first().toJSON();
					var thumb = attachment.sizes && attachment.sizes.thumbnail ? attachment.sizes.thumbnail.url : attachment.url;
					var image = $('<img />', {
						src: thumb,
						alt: '',
						'class': 'acm-media__image'
					});

					input.val(attachment.id);
					preview.removeClass('acm-media__preview--empty').empty().append(image);
					remove.prop('hidden', false);
				});

				wrap.data('acmMediaFrame', frame);
			}

			frame.open();
		});

		$(document).on('click', '.acm-media__remove', function (event) {
			event.preventDefault();

			var wrap = $(this).closest('.acm-media');
			var input = wrap.find('.acm-media__value');
			var preview = wrap.find('.acm-media__preview');
			var remove = wrap.find('.acm-media__remove');

			input.val('0');
			preview.addClass('acm-media__preview--empty').text(label('mediaEmpty', 'No image selected'));
			remove.prop('hidden', true);
		});
	}

	/**
	 * Update the Dashicon preview when the select changes.
	 *
	 * @return {void}
	 */
	function initIconPreview() {
		$('#acm_menu_icon').on('change', function () {
			$('#acm-icon-preview').attr('class', 'acm-icon-preview dashicons ' + this.value);
		});
	}

	/**
	 * Generate a key from the plural name while the key field is untouched.
	 *
	 * @return {void}
	 */
	function initSlugFields() {
		var slugFields = $('#acm_slug, #acm_tax_slug');

		if (!slugFields.length) {
			return;
		}

		slugFields.on('input', function () {
			$(this).data('acmTouched', true);
		});

		$('#acm_plural, #acm_tax_plural').on('blur', function () {
			var value = $.trim($(this).val());

			if (!value) {
				return;
			}

			slugFields.each(function () {
				var field = $(this);

				if (field.data('acmTouched') || $.trim(field.val())) {
					return;
				}

				var generated = value
					.toLowerCase()
					.replace(/[^a-z0-9\s_-]/g, '')
					.trim()
					.replace(/[\s-]+/g, '_');

				var max = parseInt(field.attr('maxlength'), 10);

				if (!isNaN(max) && max > 0) {
					generated = generated.substring(0, max);
				}

				field.val(generated);
			});
		});
	}

	/**
	 * Copy shortcode text to the clipboard.
	 *
	 * @return {void}
	 */
	function initCopyButtons() {
		$(document).on('click', '.acm-copy', function () {
			var button = $(this);
			var text = button.attr('data-acm-copy') || '';
			var original = button.text();

			if (!text) {
				return;
			}

			var done = function () {
				button.text(label('copiedLabel', 'Copied!'));
				window.setTimeout(function () {
					button.text(original);
				}, 1600);
			};

			var fallback = function () {
				var area = document.createElement('textarea');
				area.value = text;
				area.setAttribute('readonly', 'readonly');
				area.style.position = 'fixed';
				area.style.top = '-1000px';
				area.style.opacity = '0';
				document.body.appendChild(area);
				area.select();

				try {
					document.execCommand('copy');
					done();
				} catch (error) {
					button.text(label('copyErrorLabel', 'Press Ctrl+C to copy'));
				}

				document.body.removeChild(area);
			};

			if (window.navigator.clipboard && typeof window.navigator.clipboard.writeText === 'function') {
				window.navigator.clipboard.writeText(text).then(done, fallback);
			} else {
				fallback();
			}
		});
	}

	/**
	 * Confirm destructive actions declared through data attributes.
	 *
	 * @return {void}
	 */
	function initConfirms() {
		$(document).on('click', '[data-acm-confirm]', function (event) {
			var message = $(this).attr('data-acm-confirm');

			if (message && !window.confirm(message)) {
				event.preventDefault();
				event.stopPropagation();
			}
		});
	}

	/**
	 * Add and remove custom field rows on the post type editor.
	 *
	 * @return {void}
	 */
	function initFieldBuilder() {
		var body = $('#acm-fields-body');
		var template = $('#acm-field-template');

		if (!body.length || !template.length) {
			return;
		}

		var nextIndex = 0;

		body.find('input[name*="[fields]"]').each(function () {
			var match = String($(this).attr('name')).match(/\[fields\]\[(\d+)\]/);

			if (match) {
				nextIndex = Math.max(nextIndex, parseInt(match[1], 10) + 1);
			}
		});

		/**
		 * Generate a key from the label when the key field is still empty.
		 *
		 * @param {jQuery} row Field row.
		 * @return {void}
		 */
		function bindKeyGenerator(row) {
			var label = row.find('input[name$="[label]"]');
			var key = row.find('input[data-acm-field-key]');

			key.on('input', function () {
				key.data('acmFieldKeyTouched', true);
			});

			label.on('blur', function () {
				if (key.data('acmFieldKeyTouched') || $.trim(key.val())) {
					return;
				}

				var generated = $.trim(label.val())
					.toLowerCase()
					.replace(/[^a-z0-9\s_-]/g, '')
					.trim()
					.replace(/[\s-]+/g, '_')
					.substring(0, 40);

				key.val(generated);
			});
		}

		body.find('.acm-field-row').each(function () {
			bindKeyGenerator($(this));
		});

		$('#acm-add-field').on('click', function (event) {
			event.preventDefault();

			var html = template
				.html()
				.replace(/__INDEX__/g, nextIndex)
				.trim()
				.replace(/^<tr[^>]*>/i, '')
				.replace(/<\/tr>\s*$/i, '');

			nextIndex++;

			var row = $('<tr class="acm-field-row"></tr>').html(html);

			body.append(row);
			bindKeyGenerator(row);
			row.find('input[name$="[label]"]').trigger('focus');
		});

		body.on('click', '[data-acm-remove-field]', function (event) {
			event.preventDefault();
			$(this).closest('.acm-field-row').remove();
		});
	}

	$(function () {
		initMediaFields();
		initIconPreview();
		initSlugFields();
		initCopyButtons();
		initConfirms();
		initFieldBuilder();
	});
})(jQuery);
