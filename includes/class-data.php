<?php

class GravityView_View_Data {

	static $instance = NULL;

	static $views = array();

	function __construct( $passed_post = NULL ) {

		if( !empty( $passed_post ) ) {

			if( $passed_post instanceof WP_Post ) {

				if( ( get_post_type( $passed_post ) === 'gravityview' ) ) {
					self::add_view( $passed_post->ID );
				} else{
					self::parse_post_content( $passed_post->post_content );
				}
			} elseif( is_string( $passed_post ) ) {
				self::parse_post_content( $passed_post );
			} else {
				self::parse_atts( $passed_post );
			}
		}
	}

	static function getInstance() {

		if( !empty( self::$instance ) ) {
			return self::$instance;
		} else {
			return new GravityView_View_Data;
		}
	}

	static function get_views() {
		return self::$views;
	}

	static function get_view( $view_id ) {

		if ( empty( self::$views[ $view_id ] ) ) {
			do_action('gravityview_log_debug', sprintf('GravityView_View_Data[get_view] Returning; View #%s does not exist.', $view_id) );
			return false;
		}

		return self::$views[ $view_id ];

	}

	static function add_view( $view_id ) {

		if ( !empty( self::$views[ $view_id ] ) ) {
			do_action('gravityview_log_debug', sprintf('GravityView_View_Data[add_view] Returning; View #%s already exists.', $view_id) );

			return self::$views[ $view_id ];
		}

		$data = array(
			'id' => $view_id,
			'view_id' => $view_id,
			'form_id' => gravityview_get_form_id( $view_id ),
			'template_id' => gravityview_get_template_id( $view_id ),
			'atts' => gravityview_get_template_settings( $view_id ),
			'fields' => self::get_fields( $view_id ),
			'widgets' => get_post_meta( $view_id, '_gravityview_directory_widgets', true ),
		);

		self::$views[ $view_id ] = $data;

		return self::$views[ $view_id ];
	}

	static function get_fields( $view_id ) {

		$dir_fields = gravityview_get_directory_fields( $view_id );
		do_action( 'gravityview_log_debug', '[render_view] Fields: ', $dir_fields );

		// remove fields according to visitor visibility permissions (if logged-in)
		$dir_fields = self::filter_fields( $dir_fields );
		do_action( 'gravityview_log_debug', '[render_view] Fields after visibility filter: ', $dir_fields );

		return $dir_fields;
	}

	/**
	 * Filter area fields based on specified conditions
	 *
	 * @access public
	 * @param array $dir_fields
	 * @return void
	 */
	static private function filter_fields( $dir_fields ) {

		if( empty( $dir_fields ) || !is_array( $dir_fields ) ) {
			return $dir_fields;
		}

		foreach( $dir_fields as $area => $fields ) {
			foreach( $fields as $uniqid => $properties ) {

				if( self::hide_field_check_conditions( $properties ) ) {
					unset( $dir_fields[ $area ][ $uniqid ] );
				}

			}
		}

		return $dir_fields;

	}


	/**
	 * Check wether a certain field should not be presented based on its own properties.
	 *
	 * @access public
	 * @param array $properties
	 * @return true (field should be hidden) or false (field should be presented)
	 */
	static private function hide_field_check_conditions( $properties ) {

		// logged-in visibility
		if( !empty( $properties['only_loggedin'] ) && !current_user_can( $properties['only_loggedin_cap'] ) ) {
			return true;
		}

		return false;
	}

	static function parse_atts( $atts ) {

		$atts = is_array( $atts ) ? $atts : shortcode_parse_atts( $atts );

		// Get the settings from the shortcode and merge them with defaults.
		$atts = wp_parse_args( $atts, self::get_default_args() );

		$view_id = !empty( $atts['view_id'] ) ? $atts['view_id'] : NULL;

		if( empty( $atts['view_id'] ) ) {
			do_action('gravityview_log_error', 'GravityView_View_Data[parse_atts] Returning; no ID defined (Atts)', $atts );
			return;
		}

		return self::add_view( $atts['view_id'] );
	}

	static function parse_post_content( $content ) {

		$shortcodes = gravityview_has_shortcode_r( $content, 'gravityview' );

		if( empty( $shortcodes ) ) { return array(); }

		foreach ($shortcodes as $key => $shortcode) {

			// Get the settings from the shortcode and merge them with defaults.
			$shortcode_atts = wp_parse_args( shortcode_parse_atts( $shortcode[3] ), self::get_default_args() );

			if( empty( $shortcode_atts['id'] ) ) {
				do_action('gravityview_log_error', sprintf( 'GravityView_View_Data[parse_post_content] Returning; no ID defined in shortcode atts for Post #%s (Atts)', $post->ID ), $shortcode_atts );
				return false;
			}

			self::add_view( $shortcode_atts['id'] );
		}

	}

	static function r( $content = '', $die = false, $title ='') {
		if( !empty($title)) { echo "<h3>{$title}</h3>"; }
		echo '<pre>'; print_r($content); echo '</pre>';
		if($die) { die(); }
	}

	/**
	 * Retrieve the default args for shortcode and theme function
	 *
	 * @access public
	 * @static
	 * @return void
	 */
	public static function get_default_args() {

		$defaults = array(
			'id' => NULL,
			'lightbox' => true,
			'page_size' => NULL,
			'sort_field' => NULL,
			'sort_direction' => 'ASC',
			'start_date' => NULL,
			'end_date' => 'now',
			'class' => NULL,
			'search_value' => NULL,
			'search_field' => NULL,
			'single_title' => NULL,
			'back_link_label' => NULL,
			'hide_empty' => true,
		);

		return $defaults;
	}

	static function shortcode_atts( $atts ) {

		do_action( 'gravityview_log_debug', 'GravityView_View_Data[shortcode_atts] Init Shortcode. Attributes: ',  $atts );

		//confront attributes with defaults
		$args = shortcode_atts( self::get_default_args() , $atts, 'gravityview' );

		do_action( 'gravityview_log_debug', 'GravityView_View_Data[shortcode_atts] Init Shortcode. Merged Attributes: ', $args );

	}

}
