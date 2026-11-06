<?php
/**
 * Author profile fields.
 *
 * @package AuthorContentManager
 */

defined( 'ABSPATH' ) || exit;

/**
 * Defines, renders, validates and stores the author profile fields.
 */
class ACM_Author_Fields {

	/**
	 * Nonce action.
	 */
	const NONCE_ACTION = 'acm_save_author';

	/**
	 * Nonce field name.
	 */
	const NONCE_NAME = 'acm_author_nonce';

	/**
	 * Meta key prefix.
	 */
	const META_PREFIX = '_acm_';

	/**
	 * Guard against recursive post updates.
	 *
	 * @var bool
	 */
	private static $updating_title = false;

	/**
	 * Register the hooks owned by this module.
	 *
	 * @return void
	 */
	public static function hooks() {
		add_action( 'add_meta_boxes', array( __CLASS__, 'add_meta_boxes' ) );
		add_action( 'save_post', array( __CLASS__, 'save' ), 10, 2 );
		add_action( 'admin_notices', array( __CLASS__, 'render_notices' ) );
	}

	/**
	 * Build a meta key from a field key.
	 *
	 * @param string $key Field key.
	 * @return string
	 */
	public static function meta_key( $key ) {
		return self::META_PREFIX . sanitize_key( $key );
	}

	/**
	 * Field definitions.
	 *
	 * @return array
	 */
	public static function fields() {
		$fields = array(
			'name'          => array(
				'label'       => __( 'Name', 'author-content-manager' ),
				'section'     => 'basic',
				'type'        => 'text',
				'maxlength'   => 200,
				'description' => __( 'Display name shown on the frontend. Defaults to the author title.', 'author-content-manager' ),
			),
			'email'         => array(
				'label'       => __( 'Email Address', 'author-content-manager' ),
				'section'     => 'basic',
				'type'        => 'email',
				'maxlength'   => 190,
				'description' => __( 'Validated as an email address. Leave empty to hide it everywhere.', 'author-content-manager' ),
			),
			'photo_id'      => array(
				'label'       => __( 'Profile Picture', 'author-content-manager' ),
				'section'     => 'basic',
				'type'        => 'media',
				'description' => __( 'Select an image from the Media Library. Leave empty to hide the photo completely.', 'author-content-manager' ),
			),
			'bio'           => array(
				'label'       => __( 'Biography / Bio', 'author-content-manager' ),
				'section'     => 'biography',
				'type'        => 'wysiwyg',
				'description' => __( 'Basic HTML is allowed. Leave empty to hide the biography section.', 'author-content-manager' ),
			),
			'linkedin'      => array(
				'label'       => __( 'LinkedIn Profile URL', 'author-content-manager' ),
				'section'     => 'social',
				'type'        => 'url',
				'maxlength'   => 255,
				'description' => __( 'Full URL, for example https://www.linkedin.com/in/username/. Leave empty to hide the LinkedIn link.', 'author-content-manager' ),
			),
			'phone'         => array(
				'label'       => __( 'Phone Number', 'author-content-manager' ),
				'section'     => 'contact',
				'type'        => 'tel',
				'maxlength'   => 40,
				'description' => __( 'Digits, spaces, parentheses, plus and dash are allowed.', 'author-content-manager' ),
			),
			'mobile'        => array(
				'label'       => __( 'Mobile Number', 'author-content-manager' ),
				'section'     => 'contact',
				'type'        => 'tel',
				'maxlength'   => 40,
				'description' => '',
			),
			'website'       => array(
				'label'       => __( 'Website', 'author-content-manager' ),
				'section'     => 'contact',
				'type'        => 'url',
				'maxlength'   => 255,
				'description' => __( 'Must start with http:// or https://.', 'author-content-manager' ),
			),
			'address'       => array(
				'label'       => __( 'Address', 'author-content-manager' ),
				'section'     => 'contact',
				'type'        => 'textarea',
				'description' => '',
			),
			'city'          => array(
				'label'       => __( 'City', 'author-content-manager' ),
				'section'     => 'contact',
				'type'        => 'text',
				'maxlength'   => 100,
				'description' => '',
			),
			'country'       => array(
				'label'       => __( 'Country', 'author-content-manager' ),
				'section'     => 'contact',
				'type'        => 'text',
				'maxlength'   => 100,
				'description' => '',
			),
			'contact_extra' => array(
				'label'       => __( 'Additional Contact Information', 'author-content-manager' ),
				'section'     => 'contact',
				'type'        => 'textarea',
				'description' => __( 'Anything else, for example office hours or a secondary email address.', 'author-content-manager' ),
			),
		);

		/**
		 * Filter the author profile field definitions.
		 *
		 * @param array $fields Field definitions keyed by field key.
		 */
		return apply_filters( 'acm_author_fields', $fields );
	}

	/**
	 * Profile editor sections.
	 *
	 * @return array
	 */
	public static function sections() {
		return array(
			'basic'     => __( 'Basic Information', 'author-content-manager' ),
			'biography' => __( 'Biography', 'author-content-manager' ),
			'social'    => __( 'Social Media', 'author-content-manager' ),
			'contact'   => __( 'Contact Information', 'author-content-manager' ),
		);
	}

	/**
	 * Register the profile editor meta boxes.
	 *
	 * @param string $post_type Current post type.
	 * @return void
	 */
	public static function add_meta_boxes( $post_type ) {
		if ( ACM_POST_TYPE !== $post_type ) {
			return;
		}

		foreach ( self::sections() as $section => $label ) {
			add_meta_box(
				'acm-author-' . $section,
				$label,
				array( __CLASS__, 'render_meta_box' ),
				ACM_POST_TYPE,
				'normal',
				'high',
				array(
					'__block_editor_compatible_meta_box' => true,
					'section'                            => $section,
				)
			);
		}
	}

	/**
	 * Render one section meta box.
	 *
	 * @param WP_Post $post Post object.
	 * @param array   $box  Meta box arguments.
	 * @return void
	 */
	public static function render_meta_box( $post, $box ) {
		$section = isset( $box['args']['section'] ) ? sanitize_key( $box['args']['section'] ) : '';

		if ( '' === $section ) {
			return;
		}

		wp_nonce_field( self::NONCE_ACTION, self::NONCE_NAME );

		$fields = self::fields();

		echo '<div class="acm-fields acm-fields--' . esc_attr( $section ) . '">';

		foreach ( $fields as $key => $field ) {
			if ( ! isset( $field['section'] ) || $section !== $field['section'] ) {
				continue;
			}

			self::render_field( $key, $field, $post->ID );
		}

		echo '</div>';
	}

	/**
	 * Render a single field control.
	 *
	 * @param string $key     Field key.
	 * @param array  $field   Field definition.
	 * @param int    $post_id Post ID.
	 * @return void
	 */
	public static function render_field( $key, $field, $post_id ) {
		$key     = sanitize_key( $key );
		$value   = self::get_value( $post_id, $key );
		$label   = isset( $field['label'] ) ? $field['label'] : $key;
		$type    = isset( $field['type'] ) ? $field['type'] : 'text';
		$input   = 'acm_' . $key;
		$name    = 'acm[' . $key . ']';
		$desc    = isset( $field['description'] ) ? $field['description'] : '';
		$max     = isset( $field['maxlength'] ) ? absint( $field['maxlength'] ) : 0;
		$max_att = $max > 0 ? ' maxlength="' . esc_attr( (string) $max ) . '"' : '';

		echo '<div class="acm-field acm-field--' . esc_attr( $type ) . '">';

		if ( 'media' === $type ) {
			self::render_media_field( $key, $value, $label, $desc, $post_id );
			echo '</div>';

			return;
		}

		echo '<label class="acm-field__label" for="' . esc_attr( $input ) . '">' . esc_html( $label ) . '</label>';

		switch ( $type ) {
			case 'textarea':
			case 'wysiwyg':
				echo '<textarea class="widefat acm-field__input" id="' . esc_attr( $input ) . '" name="' . esc_attr( $name ) . '" rows="6"' . $max_att . '>' . esc_textarea( (string) $value ) . '</textarea>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- esc_textarea() is applied.
				break;

			case 'email':
				echo '<input type="email" class="widefat acm-field__input" id="' . esc_attr( $input ) . '" name="' . esc_attr( $name ) . '" value="' . esc_attr( (string) $value ) . '"' . $max_att . ' />'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- attributes are escaped.
				break;

			case 'url':
				echo '<input type="url" class="widefat acm-field__input" id="' . esc_attr( $input ) . '" name="' . esc_attr( $name ) . '" value="' . esc_url( (string) $value ) . '"' . $max_att . ' placeholder="https://" />'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- attributes are escaped.
				break;

			case 'tel':
				echo '<input type="tel" class="widefat acm-field__input" id="' . esc_attr( $input ) . '" name="' . esc_attr( $name ) . '" value="' . esc_attr( (string) $value ) . '"' . $max_att . ' />'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- attributes are escaped.
				break;

			default:
				echo '<input type="text" class="widefat acm-field__input" id="' . esc_attr( $input ) . '" name="' . esc_attr( $name ) . '" value="' . esc_attr( (string) $value ) . '"' . $max_att . ' />'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- attributes are escaped.
				break;
		}

		if ( '' !== $desc ) {
			echo '<p class="description">' . esc_html( $desc ) . '</p>';
		}

		echo '</div>';
	}

	/**
	 * Render the media library field.
	 *
	 * @param string $key     Field key.
	 * @param mixed  $value   Stored attachment ID.
	 * @param string $label   Field label.
	 * @param string $desc    Field description.
	 * @param int    $post_id Post ID.
	 * @return void
	 */
	private static function render_media_field( $key, $value, $label, $desc, $post_id ) {
		$attachment_id = absint( $value );
		$has_image     = $attachment_id > 0 && wp_attachment_is_image( $attachment_id );
		$input_id      = 'acm_' . $key;

		?>
		<span class="acm-field__label"><?php echo esc_html( $label ); ?></span>
		<div class="acm-media" data-acm-media="1">
			<input
				type="hidden"
				id="<?php echo esc_attr( $input_id ); ?>"
				name="<?php echo esc_attr( 'acm[' . $key . ']' ); ?>"
				value="<?php echo esc_attr( (string) ( $has_image ? $attachment_id : 0 ) ); ?>"
				class="acm-media__value"
				data-post-id="<?php echo esc_attr( (string) $post_id ); ?>"
			/>
			<div class="acm-media__preview<?php echo $has_image ? '' : ' acm-media__preview--empty'; ?>">
				<?php
				if ( $has_image ) {
					echo wp_get_attachment_image(
						$attachment_id,
						'thumbnail',
						false,
						array(
							'class' => 'acm-media__image',
							'alt'   => '',
						)
					);
				} else {
					esc_html_e( 'No image selected', 'author-content-manager' );
				}
				?>
			</div>
			<p class="acm-media__actions">
				<button type="button" class="button acm-media__select"><?php esc_html_e( 'Select image', 'author-content-manager' ); ?></button>
				<button type="button" class="button-link acm-media__remove"<?php echo $has_image ? '' : ' hidden'; ?>><?php esc_html_e( 'Remove image', 'author-content-manager' ); ?></button>
			</p>
		</div>
		<?php

		if ( '' !== $desc ) {
			echo '<p class="description">' . esc_html( $desc ) . '</p>';
		}
	}

	/**
	 * Read a stored field value.
	 *
	 * @param int    $post_id Post ID.
	 * @param string $key     Field key.
	 * @return string|int
	 */
	public static function get_value( $post_id, $key ) {
		$key   = sanitize_key( $key );
		$value = get_post_meta( (int) $post_id, self::meta_key( $key ), true );

		if ( 'photo_id' === $key ) {
			return absint( $value );
		}

		return is_scalar( $value ) ? (string) $value : '';
	}

	/**
	 * Read every stored field value.
	 *
	 * @param int $post_id Post ID.
	 * @return array
	 */
	public static function get_values( $post_id ) {
		$values = array();

		foreach ( array_keys( self::fields() ) as $key ) {
			$values[ $key ] = self::get_value( $post_id, $key );
		}

		return $values;
	}

	/**
	 * Persist the submitted fields.
	 *
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post    Post object.
	 * @return void
	 */
	public static function save( $post_id, $post ) {
		if ( self::$updating_title ) {
			return;
		}

		if ( ! $post instanceof WP_Post || ACM_POST_TYPE !== $post->post_type ) {
			return;
		}

		if ( ! isset( $_POST[ self::NONCE_NAME ] ) ) {
			return;
		}

		$nonce = sanitize_text_field( wp_unslash( $_POST[ self::NONCE_NAME ] ) );

		if ( ! wp_verify_nonce( $nonce, self::NONCE_ACTION ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$submitted = isset( $_POST['acm'] ) ? wp_unslash( $_POST['acm'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Each field is sanitized individually below.

		if ( ! is_array( $submitted ) ) {
			return;
		}

		$errors = array();
		$name   = '';

		foreach ( self::fields() as $key => $field ) {
			$key = sanitize_key( $key );

			if ( '' === $key || ! array_key_exists( $key, $submitted ) ) {
				continue;
			}

			$result = self::sanitize_value( $key, $field, $submitted[ $key ] );

			if ( is_wp_error( $result ) ) {
				$errors[] = $result->get_error_message();
				continue;
			}

			if ( '' === $result || 0 === $result ) {
				delete_post_meta( $post_id, self::meta_key( $key ) );
			} else {
				update_post_meta( $post_id, self::meta_key( $key ), $result );
			}

			if ( 'name' === $key && is_string( $result ) ) {
				$name = $result;
			}
		}

		if ( ! empty( $errors ) ) {
			set_transient(
				'acm_author_notices_' . get_current_user_id(),
				array(
					'post_id'  => (int) $post_id,
					'messages' => $errors,
				),
				5 * MINUTE_IN_SECONDS
			);
		}

		// Give author profiles a usable permalink when only the name field was filled in.
		if ( '' !== $name && '' === trim( (string) $post->post_title ) ) {
			self::$updating_title = true;

			wp_update_post(
				array(
					'ID'         => $post_id,
					'post_title' => $name,
				)
			);

			self::$updating_title = false;
		}
	}

	/**
	 * Validate and sanitize a single field value.
	 *
	 * @param string $key   Field key.
	 * @param array  $field Field definition.
	 * @param mixed  $value Raw submitted value.
	 * @return string|int|WP_Error
	 */
	public static function sanitize_value( $key, $field, $value ) {
		$type = isset( $field['type'] ) ? $field['type'] : 'text';
		$max  = isset( $field['maxlength'] ) ? absint( $field['maxlength'] ) : 0;
		$raw  = is_scalar( $value ) ? trim( (string) $value ) : '';

		switch ( $type ) {
			case 'media':
				$id = absint( $raw );

				if ( 0 === $id ) {
					return 0;
				}

				if ( ! wp_attachment_is_image( $id ) ) {
					return new WP_Error(
						'acm_invalid_media',
						sprintf(
							/* translators: %s: field label. */
							__( '%s: the selected file is not a valid image. Please choose another image.', 'author-content-manager' ),
							$field['label']
						)
					);
				}

				return $id;

			case 'email':
				if ( '' === $raw ) {
					return '';
				}

				$email = sanitize_email( $raw );

				if ( '' === $email || ! is_email( $email ) ) {
					return new WP_Error(
						'acm_invalid_email',
						sprintf(
							/* translators: %s: field label. */
							__( '%s: please enter a valid email address.', 'author-content-manager' ),
							$field['label']
						)
					);
				}

				return $email;

			case 'url':
				if ( '' === $raw ) {
					return '';
				}

				if ( ! self::is_valid_url( $raw ) ) {
					return new WP_Error(
						'acm_invalid_url',
						sprintf(
							/* translators: %s: field label. */
							__( '%s: please enter a valid http or https URL.', 'author-content-manager' ),
							$field['label']
						)
					);
				}

				return esc_url_raw( $raw );

			case 'tel':
				$phone = preg_replace( '/[^0-9+()\-\s.]/', '', $raw );
				$phone = trim( (string) $phone );

				return $max > 0 ? substr( $phone, 0, $max ) : $phone;

			case 'wysiwyg':
				return trim( wp_kses_post( $raw ) );

			case 'textarea':
				$text = sanitize_textarea_field( $raw );

				return $max > 0 ? self::limit_chars( $text, $max ) : $text;

			default:
				$text = sanitize_text_field( $raw );

				return $max > 0 ? self::limit_chars( $text, $max ) : $text;
		}
	}

	/**
	 * Limit a string by characters without breaking multibyte sequences.
	 *
	 * @param string $text  Text.
	 * @param int    $limit Maximum characters.
	 * @return string
	 */
	private static function limit_chars( $text, $limit ) {
		$text  = (string) $text;
		$limit = (int) $limit;

		if ( $limit <= 0 ) {
			return $text;
		}

		if ( function_exists( 'mb_substr' ) ) {
			return mb_substr( $text, 0, $limit );
		}

		if ( preg_match( '/^.{0,' . $limit . '}/us', $text, $matches ) ) {
			return $matches[0];
		}

		return substr( $text, 0, $limit );
	}

	/**
	 * Whether a URL is a valid http(s) address.
	 *
	 * @param string $url URL to check.
	 * @return bool
	 */
	public static function is_valid_url( $url ) {
		$url = trim( (string) $url );

		if ( '' === $url ) {
			return false;
		}

		$parts = wp_parse_url( $url );

		if ( ! is_array( $parts ) || empty( $parts['scheme'] ) || empty( $parts['host'] ) ) {
			return false;
		}

		if ( ! in_array( strtolower( $parts['scheme'] ), array( 'http', 'https' ), true ) ) {
			return false;
		}

		return false !== filter_var( $url, FILTER_VALIDATE_URL );
	}

	/**
	 * Show validation errors from the most recent save.
	 *
	 * @return void
	 */
	public static function render_notices() {
		$screen = get_current_screen();

		if ( ! $screen || ( ACM_POST_TYPE !== $screen->post_type && 'post' !== $screen->base ) ) {
			return;
		}

		$key     = 'acm_author_notices_' . get_current_user_id();
		$notices = get_transient( $key );

		if ( ! is_array( $notices ) || empty( $notices['messages'] ) ) {
			return;
		}

		delete_transient( $key );

		echo '<div class="notice notice-error is-dismissible"><p><strong>' . esc_html__( 'Some author fields were not saved:', 'author-content-manager' ) . '</strong></p><ul class="ul-disc">';

		foreach ( (array) $notices['messages'] as $message ) {
			echo '<li>' . esc_html( $message ) . '</li>';
		}

		echo '</ul></div>';
	}
}
