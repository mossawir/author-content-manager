<?php
/**
 * Custom field engine shared by author profiles and custom post types.
 *
 * @package AuthorContentManager
 */

defined( 'ABSPATH' ) || exit;

/**
 * Stores field definitions, renders controls and values, and saves meta.
 */
class ACM_Custom_Fields {

	/**
	 * Option holding the author profile field definitions.
	 */
	const AUTHOR_OPTION = 'acm_author_custom_fields';

	/**
	 * Maximum number of custom fields per context.
	 */
	const MAX_FIELDS = 50;

	/**
	 * Custom field types.
	 *
	 * @return array Type key => label.
	 */
	public static function types() {
		return array(
			'text'     => __( 'Text', 'author-content-manager' ),
			'textarea' => __( 'Text Area', 'author-content-manager' ),
			'email'    => __( 'Email Address', 'author-content-manager' ),
			'url'      => __( 'URL', 'author-content-manager' ),
			'tel'      => __( 'Phone Number', 'author-content-manager' ),
			'number'   => __( 'Number', 'author-content-manager' ),
			'date'     => __( 'Date', 'author-content-manager' ),
			'image'    => __( 'Image (Media Library)', 'author-content-manager' ),
		);
	}

	/**
	 * Author profile custom field definitions.
	 *
	 * @return array
	 */
	public static function author_definitions() {
		$stored = get_option( self::AUTHOR_OPTION, array() );

		if ( ! is_array( $stored ) ) {
			return array();
		}

		$definitions = array();

		foreach ( $stored as $definition ) {
			if ( is_array( $definition ) && ! empty( $definition['key'] ) && ! empty( $definition['label'] ) ) {
				$definitions[] = $definition;
			}
		}

		return $definitions;
	}

	/**
	 * Replace the author profile custom field definitions.
	 *
	 * @param array $definitions Definitions.
	 * @return void
	 */
	public static function set_author_definitions( $definitions ) {
		update_option( self::AUTHOR_OPTION, array_values( (array) $definitions ) );
	}

	/**
	 * Field definitions attached to a custom post type.
	 *
	 * @param string $post_type Post type key.
	 * @return array
	 */
	public static function post_type_fields( $post_type ) {
		$config = ACM_Post_Type_Registry::get( $post_type );

		if ( ! $config ) {
			return array();
		}

		$config = wp_parse_args( $config, ACM_Post_Type_Registry::defaults() );

		return isset( $config['fields'] ) && is_array( $config['fields'] ) ? array_values( $config['fields'] ) : array();
	}

	/**
	 * Whether a post type has custom fields.
	 *
	 * @param string $post_type Post type key.
	 * @return bool
	 */
	public static function post_type_has_fields( $post_type ) {
		return ! empty( self::post_type_fields( $post_type ) );
	}

	/**
	 * Display settings for a post type's custom fields.
	 *
	 * @param string $post_type Post type key.
	 * @return array
	 */
	public static function post_type_display( $post_type ) {
		$config = ACM_Post_Type_Registry::get( $post_type );
		$config = wp_parse_args( is_array( $config ) ? $config : array(), ACM_Post_Type_Registry::defaults() );

		return array(
			'display'     => 'auto' === $config['field_display'] ? 'auto' : 'shortcode',
			'show_labels' => ! empty( $config['field_show_labels'] ),
			'image_size'  => (string) $config['field_image_size'],
		);
	}

	/**
	 * Build the meta key for a field.
	 *
	 * @param string $context Author or post context.
	 * @param string $key     Field key.
	 * @return string
	 */
	public static function meta_key( $context, $key ) {
		$key = sanitize_key( $key );

		if ( '' === $key ) {
			return '';
		}

		return ( 'author' === $context ? '_acm_cf_' : '_acm_pf_' ) . $key;
	}

	/**
	 * Validate and sanitize one field definition.
	 *
	 * @param array  $raw           Raw definition.
	 * @param string $context       Author or post context.
	 * @param array  $existing_keys Keys already used in this context.
	 * @return array|WP_Error
	 */
	public static function sanitize_definition( $raw, $context = 'post', $existing_keys = array() ) {
		$raw   = is_array( $raw ) ? $raw : array();
		$label = isset( $raw['label'] ) ? sanitize_text_field( $raw['label'] ) : '';

		if ( '' === $label ) {
			return new WP_Error( 'acm_field_label', __( 'Each custom field needs a label.', 'author-content-manager' ) );
		}

		$label = self::limit_text( $label, 100 );

		$provided_key = isset( $raw['key'] ) ? sanitize_key( $raw['key'] ) : '';
		$key          = $provided_key;

		if ( '' === $key ) {
			$key = sanitize_key( str_replace( '-', '_', sanitize_title( $label ) ) );
		}

		if ( '' === $key ) {
			$key = 'field';
		}

		$key = substr( $key, 0, 40 );

		$existing_keys = array_map( 'sanitize_key', (array) $existing_keys );

		if ( '' !== $provided_key && in_array( $key, $existing_keys, true ) ) {
			return new WP_Error(
				'acm_field_duplicate_key',
				sprintf(
					/* translators: %s: field label. */
					__( '%s: the field key is already used by another custom field.', 'author-content-manager' ),
					$label
				)
			);
		}

		if ( '' === $provided_key ) {
			$base   = $key;
			$suffix = 2;

			while ( in_array( $key, $existing_keys, true ) ) {
				$addition = '_' . $suffix;
				$key      = substr( $base, 0, 40 - strlen( $addition ) ) . $addition;
				$suffix++;
			}
		}

		$type = isset( $raw['type'] ) ? sanitize_key( $raw['type'] ) : 'text';

		if ( ! array_key_exists( $type, self::types() ) ) {
			$type = 'text';
		}

		$description = isset( $raw['description'] ) ? sanitize_textarea_field( $raw['description'] ) : '';

		$definition = array(
			'key'              => $key,
			'label'            => $label,
			'type'             => $type,
			'description'      => self::limit_text( $description, 300 ),
			'required'         => empty( $raw['required'] ) ? 0 : 1,
			'show_in_profile'  => empty( $raw['show_in_profile'] ) ? 0 : 1,
		);

		/**
		 * Filter a sanitized custom field definition.
		 *
		 * @param array  $definition Sanitized definition.
		 * @param array  $raw        Raw definition.
		 * @param string $context    Author or post context.
		 */
		return apply_filters( 'acm_sanitize_custom_field', $definition, $raw, $context );
	}

	/**
	 * Sanitize a list of field definitions.
	 *
	 * @param array  $raw           Raw definitions.
	 * @param string $context       Author or post context.
	 * @param array  $existing_keys Keys already used in this context.
	 * @return array|WP_Error
	 */
	public static function sanitize_definitions( $raw, $context = 'post', $existing_keys = array() ) {
		if ( ! is_array( $raw ) ) {
			return array();
		}

		$definitions = array();
		$errors      = array();
		$seen        = array_map( 'sanitize_key', (array) $existing_keys );

		foreach ( $raw as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}

			$label       = isset( $row['label'] ) ? sanitize_text_field( $row['label'] ) : '';
			$key         = isset( $row['key'] ) ? sanitize_key( $row['key'] ) : '';
			$description = isset( $row['description'] ) ? sanitize_textarea_field( $row['description'] ) : '';
			$type        = isset( $row['type'] ) ? sanitize_key( $row['type'] ) : 'text';

			if ( '' === $label && '' === $key && '' === $description && 'text' === $type && empty( $row['required'] ) ) {
				continue;
			}

			$result = self::sanitize_definition( $row, $context, $seen );

			if ( is_wp_error( $result ) ) {
				$errors[] = $result->get_error_message();
				continue;
			}

			$seen[]        = $result['key'];
			$definitions[] = $result;

			if ( count( $definitions ) >= self::MAX_FIELDS ) {
				$errors[] = sprintf(
					/* translators: %d: maximum number of fields. */
					__( 'You can add up to %d custom fields per context.', 'author-content-manager' ),
					self::MAX_FIELDS
				);
				break;
			}
		}

		if ( ! empty( $errors ) ) {
			return new WP_Error( 'acm_custom_fields_invalid', implode( ' ', array_unique( $errors ) ) );
		}

		return $definitions;
	}

	/**
	 * Author profile custom field items, optionally limited to fields marked for the profile.
	 *
	 * @param int  $author_id    Author ID.
	 * @param bool $only_visible Whether to only return fields shown on the profile.
	 * @return array
	 */
	public static function author_items( $author_id, $only_visible = true ) {
		$items = array();

		foreach ( self::author_definitions() as $definition ) {
			if ( $only_visible && empty( $definition['show_in_profile'] ) ) {
				continue;
			}

			$value = get_post_meta( (int) $author_id, self::meta_key( 'author', $definition['key'] ), true );

			if ( ! self::has_value( $definition, $value ) ) {
				continue;
			}

			$items[] = array(
				'definition' => $definition,
				'value'      => $value,
			);
		}

		return $items;
	}

	/**
	 * Custom field items for a post.
	 *
	 * @param int $post_id Post ID.
	 * @return array
	 */
	public static function post_items( $post_id ) {
		$post = get_post( $post_id );

		if ( ! $post instanceof WP_Post ) {
			return array();
		}

		$items = array();

		foreach ( self::post_type_fields( $post->post_type ) as $definition ) {
			$value = get_post_meta( (int) $post->ID, self::meta_key( 'post', $definition['key'] ), true );

			if ( ! self::has_value( $definition, $value ) ) {
				continue;
			}

			$items[] = array(
				'definition' => $definition,
				'value'      => $value,
			);
		}

		return $items;
	}

	/**
	 * Whether a field has a displayable value.
	 *
	 * @param array $definition Field definition.
	 * @param mixed $value      Stored value.
	 * @return bool
	 */
	public static function has_value( $definition, $value ) {
		if ( ! is_array( $definition ) || empty( $definition['type'] ) ) {
			return false;
		}

		if ( 'image' === $definition['type'] ) {
			$id = absint( $value );

			return $id > 0 && wp_attachment_is_image( $id );
		}

		return is_scalar( $value ) && '' !== trim( (string) $value );
	}

	/**
	 * Retrieve a single stored custom field value.
	 *
	 * @param string $context Author or post context.
	 * @param int    $post_id Post ID.
	 * @param string $key     Field key.
	 * @return string
	 */
	public static function get_value( $context, $post_id, $key ) {
		$meta_key = self::meta_key( $context, $key );

		return '' === $meta_key ? '' : (string) get_post_meta( (int) $post_id, $meta_key, true );
	}

	/**
	 * Find a definition by key.
	 *
	 * @param array  $definitions Definitions.
	 * @param string $key         Field key.
	 * @return array|null
	 */
	public static function find_definition( $definitions, $key ) {
		$key = sanitize_key( $key );

		foreach ( $definitions as $definition ) {
			if ( isset( $definition['key'] ) && $definition['key'] === $key ) {
				return $definition;
			}
		}

		return null;
	}

	/**
	 * Resolve an image size for a custom field.
	 *
	 * @param array  $args      Render arguments.
	 * @param string $post_type Optional post type key.
	 * @return string
	 */
	public static function image_size( $args = array(), $post_type = '' ) {
		$allowed = ACM_Settings::available_image_sizes();
		$size    = isset( $args['image_size'] ) ? sanitize_key( $args['image_size'] ) : '';

		if ( '' !== $size && in_array( $size, $allowed, true ) ) {
			return $size;
		}

		if ( '' !== $post_type ) {
			$display = self::post_type_display( $post_type );
			$size    = sanitize_key( $display['image_size'] );

			if ( '' !== $size && in_array( $size, $allowed, true ) ) {
				return $size;
			}
		}

		$default = sanitize_key( (string) ACM_Settings::get( 'custom_field_image_size', 'medium' ) );

		return in_array( $default, $allowed, true ) ? $default : 'medium';
	}

	/**
	 * Render the value markup for a field.
	 *
	 * @param array $definition Field definition.
	 * @param mixed $value      Stored value.
	 * @param array $args       Render arguments.
	 * @return string
	 */
	public static function render_value( $definition, $value, $args = array() ) {
		if ( ! self::has_value( $definition, $value ) ) {
			return '';
		}

		$type = $definition['type'];
		$args = is_array( $args ) ? $args : array();

		switch ( $type ) {
			case 'image':
				$id   = absint( $value );
				$html = wp_get_attachment_image(
					$id,
					self::image_size( $args, isset( $args['post_type'] ) ? $args['post_type'] : '' ),
					false,
					array(
						'class'    => 'acm-custom-field__image',
						'alt'      => isset( $definition['label'] ) ? $definition['label'] : '',
						'loading'  => 'lazy',
						'decoding' => 'async',
					)
				);

				if ( '' === $html ) {
					return '';
				}

				if ( ! empty( $args['link'] ) ) {
					$url = wp_get_attachment_url( $id );

					if ( $url ) {
						$html = '<a class="acm-custom-field__image-link" href="' . esc_url( $url ) . '" target="_blank" rel="noopener noreferrer">' . $html . '</a>';
					}
				}

				return $html;

			case 'email':
				return '<a href="' . esc_url( 'mailto:' . $value ) . '">' . esc_html( $value ) . '</a>';

			case 'url':
				return '<a href="' . esc_url( $value ) . '" target="_blank" rel="noopener noreferrer nofollow">' . esc_html( $value ) . '</a>';

			case 'tel':
				$tel = preg_replace( '/[^0-9+]/', '', (string) $value );

				if ( '' === $tel ) {
					return esc_html( $value );
				}

				return '<a href="' . esc_url( 'tel:' . $tel ) . '">' . esc_html( $value ) . '</a>';

			case 'date':
				$timestamp = strtotime( (string) $value );
				$formatted = $timestamp ? date_i18n( (string) get_option( 'date_format', 'F j, Y' ), $timestamp ) : (string) $value;

				return esc_html( $formatted );

			case 'textarea':
				return nl2br( esc_html( (string) $value ) );

			default:
				return esc_html( (string) $value );
		}
	}

	/**
	 * Render a list of custom field rows.
	 *
	 * @param array $items Items with definition and value keys.
	 * @param array $args  Render arguments.
	 * @return string
	 */
	public static function render_list( $items, $args = array() ) {
		$args = wp_parse_args(
			is_array( $args ) ? $args : array(),
			array(
				'show_labels' => true,
				'title'       => '',
				'class'       => '',
				'link'        => false,
				'image_size'  => '',
				'post_type'   => '',
				'context'     => '',
			)
		);

		$rows = array();

		foreach ( (array) $items as $item ) {
			if ( empty( $item['definition'] ) || ! isset( $item['value'] ) ) {
				continue;
			}

			$value_html = self::render_value( $item['definition'], $item['value'], $args );

			if ( '' === $value_html ) {
				continue;
			}

			$rows[] = array(
				'definition' => $item['definition'],
				'html'       => $value_html,
			);
		}

		if ( empty( $rows ) ) {
			return '';
		}

		$classes = array( 'acm-custom-fields' );

		if ( '' !== $args['context'] ) {
			$classes[] = 'acm-custom-fields--' . sanitize_html_class( $args['context'] );
		}

		$extra = acm_sanitize_classes( $args['class'] );

		if ( '' !== $extra ) {
			$classes[] = $extra;
		}

		$html = '<div class="' . esc_attr( implode( ' ', $classes ) ) . '">';

		$title = is_scalar( $args['title'] ) ? trim( (string) $args['title'] ) : '';

		if ( '' !== $title ) {
			$html .= '<h3 class="acm-custom-fields__title">' . esc_html( $title ) . '</h3>';
		}

		$html .= '<dl class="acm-custom-fields__list">';

		foreach ( $rows as $row ) {
			$definition = $row['definition'];
			$type_class = isset( $definition['type'] ) ? sanitize_html_class( $definition['type'] ) : 'text';

			$html .= '<div class="acm-custom-field acm-custom-field--' . esc_attr( $type_class ) . '">';

			if ( ! empty( $args['show_labels'] ) && ! empty( $definition['label'] ) ) {
				$html .= '<dt class="acm-custom-field__label">' . esc_html( $definition['label'] ) . '</dt>';
			}

			$html .= '<dd class="acm-custom-field__value">' . $row['html'] . '</dd>';
			$html .= '</div>';
		}

		$html .= '</dl></div>';

		return $html;
	}

	/**
	 * Render a single custom field.
	 *
	 * @param array $item Item with definition and value keys.
	 * @param array $args Render arguments.
	 * @return string
	 */
	public static function render_single( $item, $args = array() ) {
		if ( empty( $item['definition'] ) || ! isset( $item['value'] ) ) {
			return '';
		}

		$args = wp_parse_args(
			is_array( $args ) ? $args : array(),
			array(
				'show_label' => false,
				'class'      => '',
				'link'       => false,
				'image_size' => '',
				'post_type'  => '',
			)
		);

		$value_html = self::render_value( $item['definition'], $item['value'], $args );

		if ( '' === $value_html ) {
			return '';
		}

		$type_class = isset( $item['definition']['type'] ) ? sanitize_html_class( $item['definition']['type'] ) : 'text';

		$classes = array( 'acm-custom-field-single', 'acm-custom-field-single--' . $type_class );
		$extra   = acm_sanitize_classes( $args['class'] );

		if ( '' !== $extra ) {
			$classes[] = $extra;
		}

		$html = '<div class="' . esc_attr( implode( ' ', $classes ) ) . '">';

		if ( ! empty( $args['show_label'] ) && ! empty( $item['definition']['label'] ) ) {
			$html .= '<span class="acm-custom-field-single__label">' . esc_html( $item['definition']['label'] ) . '</span>';
		}

		$html .= '<div class="acm-custom-field-single__value">' . $value_html . '</div></div>';

		return $html;
	}

	/**
	 * Render an editable control for a field.
	 *
	 * @param array  $definition Field definition.
	 * @param mixed  $value      Current value.
	 * @param string $name       Input name.
	 * @param string $id         Input ID.
	 * @return void
	 */
	public static function render_control( $definition, $value, $name, $id ) {
		$type  = isset( $definition['type'] ) ? $definition['type'] : 'text';
		$label = isset( $definition['label'] ) ? $definition['label'] : $definition['key'];
		$desc  = isset( $definition['description'] ) ? $definition['description'] : '';
		$id    = sanitize_html_class( $id );

		echo '<div class="acm-field acm-field--' . esc_attr( $type ) . '">';
		echo '<label class="acm-field__label" for="' . esc_attr( $id ) . '">' . esc_html( $label ) . '</label>';

		if ( 'image' === $type ) {
			$attachment_id = absint( $value );
			$has_image     = $attachment_id > 0 && wp_attachment_is_image( $attachment_id );

			echo '<div class="acm-media" data-acm-media="1">';
			echo '<input type="hidden" id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '" value="' . esc_attr( (string) ( $has_image ? $attachment_id : 0 ) ) . '" class="acm-media__value" />';
			echo '<div class="acm-media__preview' . ( $has_image ? '' : ' acm-media__preview--empty' ) . '">';

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

			echo '</div>';
			echo '<p class="acm-media__actions">';
			echo '<button type="button" class="button acm-media__select">' . esc_html__( 'Select image', 'author-content-manager' ) . '</button>';
			echo '<button type="button" class="button-link acm-media__remove"' . ( $has_image ? '' : ' hidden' ) . '>' . esc_html__( 'Remove image', 'author-content-manager' ) . '</button>';
			echo '</p></div>';
		} elseif ( 'textarea' === $type ) {
			echo '<textarea class="widefat acm-field__input" id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '" rows="5">' . esc_textarea( (string) $value ) . '</textarea>';
		} else {
			$input_type = in_array( $type, array( 'email', 'url', 'tel', 'number', 'date' ), true ) ? $type : 'text';
			$step       = 'number' === $type ? ' step="any"' : '';

			echo '<input type="' . esc_attr( $input_type ) . '" class="widefat acm-field__input" id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '" value="' . esc_attr( (string) $value ) . '"' . $step . ' />'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Attributes are escaped.
		}

		if ( '' !== $desc ) {
			echo '<p class="description">' . esc_html( $desc ) . '</p>';
		}

		echo '</div>';
	}

	/**
	 * Register meta boxes and save handlers.
	 *
	 * @return void
	 */
	public static function hooks() {
		add_action( 'add_meta_boxes', array( __CLASS__, 'add_meta_boxes' ) );
		add_action( 'save_post', array( __CLASS__, 'save_author_values' ), 11, 2 );
		add_action( 'save_post', array( __CLASS__, 'save_post_values' ), 11, 2 );
		add_action( 'admin_notices', array( __CLASS__, 'render_notices' ) );
		add_filter( 'the_content', array( __CLASS__, 'append_to_content' ), 21 );
	}

	/**
	 * Add the custom field meta boxes.
	 *
	 * @param string $post_type Current post type.
	 * @return void
	 */
	public static function add_meta_boxes( $post_type ) {
		if ( ACM_POST_TYPE === $post_type ) {
			if ( ! empty( self::author_definitions() ) ) {
				add_meta_box(
					'acm-author-custom',
					__( 'Additional Information', 'author-content-manager' ),
					array( __CLASS__, 'render_meta_box' ),
					ACM_POST_TYPE,
					'normal',
					'default',
					array(
						'__block_editor_compatible_meta_box' => true,
						'context'                            => 'author',
					)
				);
			}

			return;
		}

		if ( self::post_type_has_fields( $post_type ) ) {
			add_meta_box(
				'acm-post-fields',
				__( 'Custom Fields', 'author-content-manager' ),
				array( __CLASS__, 'render_meta_box' ),
				$post_type,
				'normal',
				'default',
				array(
					'__block_editor_compatible_meta_box' => true,
					'context'                            => 'post',
					'post_type'                          => $post_type,
				)
			);
		}
	}

	/**
	 * Render the custom field meta box.
	 *
	 * @param WP_Post $post Post object.
	 * @param array   $box  Meta box arguments.
	 * @return void
	 */
	public static function render_meta_box( $post, $box ) {
		$context = isset( $box['args']['context'] ) ? sanitize_key( $box['args']['context'] ) : 'post';

		if ( 'author' === $context ) {
			$definitions = self::author_definitions();
			$field_name  = 'acm_custom';
			$nonce_name  = 'acm_author_nonce';
			$nonce_value = wp_create_nonce( 'acm_save_author' );
		} else {
			$definitions = self::post_type_fields( $post->post_type );
			$field_name  = 'acm_pf';
			$nonce_name  = 'acm_post_fields_nonce';
			$nonce_value = wp_create_nonce( 'acm_save_post_fields' );
		}

		if ( empty( $definitions ) ) {
			return;
		}

		echo '<input type="hidden" name="' . esc_attr( $nonce_name ) . '" value="' . esc_attr( $nonce_value ) . '" />';
		echo '<div class="acm-fields acm-fields--custom">';

		foreach ( $definitions as $definition ) {
			$meta_key = self::meta_key( $context, $definition['key'] );
			$value    = '' === $meta_key ? '' : get_post_meta( $post->ID, $meta_key, true );

			self::render_control(
				$definition,
				$value,
				$field_name . '[' . $definition['key'] . ']',
				'acm-field-' . $context . '-' . $definition['key']
			);
		}

		echo '</div>';
	}

	/**
	 * Save author profile custom fields.
	 *
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post    Post object.
	 * @return void
	 */
	public static function save_author_values( $post_id, $post ) {
		if ( ! $post instanceof WP_Post || ACM_POST_TYPE !== $post->post_type ) {
			return;
		}

		if ( ! isset( $_POST['acm_custom'], $_POST['acm_author_nonce'] ) ) {
			return;
		}

		$nonce = sanitize_text_field( wp_unslash( $_POST['acm_author_nonce'] ) );

		if ( ! wp_verify_nonce( $nonce, 'acm_save_author' ) ) {
			return;
		}

		if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$values = wp_unslash( $_POST['acm_custom'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitized per field below.

		$errors = self::save_values( 'author', $post_id, is_array( $values ) ? $values : array(), self::author_definitions() );

		if ( ! empty( $errors ) ) {
			self::queue_author_notice( $post_id, $errors );
		}
	}

	/**
	 * Save custom fields attached to a post type.
	 *
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post    Post object.
	 * @return void
	 */
	public static function save_post_values( $post_id, $post ) {
		if ( ! $post instanceof WP_Post || ACM_POST_TYPE === $post->post_type ) {
			return;
		}

		if ( ! isset( $_POST['acm_pf'], $_POST['acm_post_fields_nonce'] ) ) {
			return;
		}

		$definitions = self::post_type_fields( $post->post_type );

		if ( empty( $definitions ) ) {
			return;
		}

		$nonce = sanitize_text_field( wp_unslash( $_POST['acm_post_fields_nonce'] ) );

		if ( ! wp_verify_nonce( $nonce, 'acm_save_post_fields' ) ) {
			return;
		}

		if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$values = wp_unslash( $_POST['acm_pf'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitized per field below.

		$errors = self::save_values( 'post', $post_id, is_array( $values ) ? $values : array(), $definitions );

		if ( ! empty( $errors ) ) {
			self::queue_notice( $post_id, $errors );
		}
	}

	/**
	 * Validate and store submitted custom field values.
	 *
	 * @param string $context     Author or post context.
	 * @param int    $post_id     Post ID.
	 * @param array  $values      Submitted values keyed by field key.
	 * @param array  $definitions Definitions.
	 * @return array Error messages.
	 */
	public static function save_values( $context, $post_id, $values, $definitions ) {
		$errors = array();

		foreach ( $definitions as $definition ) {
			if ( empty( $definition['key'] ) || ! array_key_exists( $definition['key'], $values ) ) {
				continue;
			}

			$meta_key = self::meta_key( $context, $definition['key'] );
			$result   = self::sanitize_value( $definition, $values[ $definition['key'] ] );

			if ( is_wp_error( $result ) ) {
				$errors[] = $result->get_error_message();
				continue;
			}

			$is_empty = 'image' === $definition['type'] ? 0 === (int) $result : '' === trim( (string) $result );

			if ( $is_empty ) {
				if ( ! empty( $definition['required'] ) ) {
					$errors[] = sprintf(
						/* translators: %s: field label. */
						__( '%s is required.', 'author-content-manager' ),
						$definition['label']
					);
					continue;
				}

				delete_post_meta( $post_id, $meta_key );
				continue;
			}

			update_post_meta( $post_id, $meta_key, $result );
		}

		return $errors;
	}

	/**
	 * Validate a custom field value.
	 *
	 * @param array $definition Field definition.
	 * @param mixed $value      Raw value.
	 * @return string|int|WP_Error
	 */
	public static function sanitize_value( $definition, $value ) {
		$type  = isset( $definition['type'] ) ? $definition['type'] : 'text';
		$label = isset( $definition['label'] ) ? $definition['label'] : $definition['key'];
		$raw   = is_scalar( $value ) ? trim( (string) $value ) : '';

		switch ( $type ) {
			case 'image':
				$id = absint( $raw );

				if ( 0 === $id ) {
					return 0;
				}

				if ( ! wp_attachment_is_image( $id ) ) {
					return new WP_Error(
						'acm_field_invalid_image',
						sprintf(
							/* translators: %s: field label. */
							__( '%s: the selected file is not a valid image.', 'author-content-manager' ),
							$label
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
						'acm_field_invalid_email',
						sprintf(
							/* translators: %s: field label. */
							__( '%s: please enter a valid email address.', 'author-content-manager' ),
							$label
						)
					);
				}

				return $email;

			case 'url':
				if ( '' === $raw ) {
					return '';
				}

				if ( ! ACM_Author_Fields::is_valid_url( $raw ) ) {
					return new WP_Error(
						'acm_field_invalid_url',
						sprintf(
							/* translators: %s: field label. */
							__( '%s: please enter a valid http or https URL.', 'author-content-manager' ),
							$label
						)
					);
				}

				return esc_url_raw( $raw );

			case 'tel':
				return self::limit_text( sanitize_text_field( $raw ), 40 );

			case 'number':
				if ( '' === $raw ) {
					return '';
				}

				if ( ! is_numeric( $raw ) ) {
					return new WP_Error(
						'acm_field_invalid_number',
						sprintf(
							/* translators: %s: field label. */
							__( '%s: please enter a valid number.', 'author-content-manager' ),
							$label
						)
					);
				}

				return $raw;

			case 'date':
				if ( '' === $raw ) {
					return '';
				}

				$valid = (bool) preg_match( '/^\d{4}-\d{2}-\d{2}$/', $raw );

				if ( $valid ) {
					list( $year, $month, $day ) = array_map( 'intval', explode( '-', $raw ) );
					$valid                      = checkdate( $month, $day, $year );
				}

				if ( ! $valid ) {
					return new WP_Error(
						'acm_field_invalid_date',
						sprintf(
							/* translators: %s: field label. */
							__( '%s: please enter a valid date.', 'author-content-manager' ),
							$label
						)
					);
				}

				return $raw;

			case 'textarea':
				return self::limit_text( sanitize_textarea_field( $raw ), 5000 );

			default:
				return self::limit_text( sanitize_text_field( $raw ), 500 );
		}
	}

	/**
	 * Append auto-displayed post fields to the post content.
	 *
	 * @param string $content Post content.
	 * @return string
	 */
	public static function append_to_content( $content ) {
		static $appended = array();

		if ( is_admin() || ! is_singular() ) {
			return $content;
		}

		$post = get_post();

		if ( ! $post instanceof WP_Post || isset( $appended[ $post->ID ] ) ) {
			return $content;
		}

		if ( (int) get_queried_object_id() !== (int) $post->ID ) {
			return $content;
		}

		$display = self::post_type_display( $post->post_type );

		if ( 'auto' !== $display['display'] || ! self::post_type_has_fields( $post->post_type ) ) {
			return $content;
		}

		$items = self::post_items( $post->ID );

		if ( empty( $items ) ) {
			return $content;
		}

		$html = self::render_list(
			$items,
			array(
				'show_labels' => $display['show_labels'],
				'image_size'  => $display['image_size'],
				'post_type'   => $post->post_type,
				'context'     => 'post',
				'class'       => 'acm-post-fields--auto',
			)
		);

		if ( '' === $html ) {
			return $content;
		}

		$appended[ $post->ID ] = true;

		ACM_Assets::enqueue_frontend_assets();

		return $content . $html;
	}

	/**
	 * Queue validation messages for the author editor.
	 *
	 * @param int   $post_id  Post ID.
	 * @param array $messages Messages.
	 * @return void
	 */
	private static function queue_author_notice( $post_id, $messages ) {
		$key     = 'acm_author_notices_' . get_current_user_id();
		$existing = get_transient( $key );

		if ( is_array( $existing ) && ! empty( $existing['messages'] ) ) {
			$messages = array_merge( (array) $existing['messages'], $messages );
		}

		set_transient(
			$key,
			array(
				'post_id'  => (int) $post_id,
				'messages' => array_values( array_unique( $messages ) ),
			),
			5 * MINUTE_IN_SECONDS
		);
	}

	/**
	 * Queue validation messages for custom post type fields.
	 *
	 * @param int   $post_id  Post ID.
	 * @param array $messages Messages.
	 * @return void
	 */
	private static function queue_notice( $post_id, $messages ) {
		set_transient(
			'acm_custom_notices_' . get_current_user_id(),
			array(
				'post_id'  => (int) $post_id,
				'messages' => array_values( array_unique( $messages ) ),
			),
			5 * MINUTE_IN_SECONDS
		);
	}

	/**
	 * Show validation messages for custom post fields.
	 *
	 * @return void
	 */
	public static function render_notices() {
		$key     = 'acm_custom_notices_' . get_current_user_id();
		$notices = get_transient( $key );

		if ( ! is_array( $notices ) || empty( $notices['messages'] ) ) {
			return;
		}

		delete_transient( $key );

		echo '<div class="notice notice-error is-dismissible"><p><strong>' . esc_html__( 'Some custom fields were not saved:', 'author-content-manager' ) . '</strong></p><ul class="ul-disc">';

		foreach ( (array) $notices['messages'] as $message ) {
			echo '<li>' . esc_html( $message ) . '</li>';
		}

		echo '</ul></div>';
	}

	/**
	 * Limit a string by characters without breaking multibyte sequences.
	 *
	 * @param string $text  Text.
	 * @param int    $limit Maximum characters.
	 * @return string
	 */
	private static function limit_text( $text, $limit ) {
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
}
