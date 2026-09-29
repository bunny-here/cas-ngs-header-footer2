<?php
/**
 * CAS Earth Theme functions and definitions.
 *
 * @link https://developer.wordpress.org/themes/basics/theme-functions/
 *
 * @package WordPress
 * @subpackage CAS_Earth_Theme
 * @since Twenty Twenty-Five 1.0
 */

if ( ! function_exists( 'casearththeme_post_format_setup' ) ) :
	/**
	 * Adds theme support for post formats.
	 *
	 * @since Twenty Twenty-Five 1.0
	 *
	 * @return void
	 */
	function casearththeme_post_format_setup() {
		add_theme_support( 'post-formats', array( 'aside', 'audio', 'chat', 'gallery', 'image', 'link', 'quote', 'status', 'video' ) );
	}
endif;
add_action( 'after_setup_theme', 'casearththeme_post_format_setup' );

if ( ! function_exists( 'casearththeme_editor_style' ) ) :
	/**
	 * Enqueues editor-style.css in the editors.
	 *
	 * @since Twenty Twenty-Five 1.0
	 *
	 * @return void
	 */
	function casearththeme_editor_style() {
		add_editor_style( 'assets/css/editor-style.css' );
	}
endif;
add_action( 'after_setup_theme', 'casearththeme_editor_style' );

if ( ! function_exists( 'casearththeme_enqueue_styles' ) ) :
	/**
	 * Enqueues the theme stylesheet on the front.
	 *
	 * @since Twenty Twenty-Five 1.0
	 *
	 * @return void
	 */
	function casearththeme_enqueue_styles() {
		$suffix = SCRIPT_DEBUG ? '' : '.min';
		$src    = 'style' . $suffix . '.css';

		wp_enqueue_style(
			'cas-earth-theme-style',
			get_parent_theme_file_uri( $src ),
			array(),
			wp_get_theme()->get( 'Version' )
		);
		wp_style_add_data(
			'cas-earth-theme-style',
			'path',
			get_parent_theme_file_path( $src )
		);
	}
endif;
add_action( 'wp_enqueue_scripts', 'casearththeme_enqueue_styles' );

if ( ! function_exists( 'casearththeme_block_styles' ) ) :
	/**
	 * Registers custom block styles.
	 *
	 * @since Twenty Twenty-Five 1.0
	 *
	 * @return void
	 */
	function casearththeme_block_styles() {
		register_block_style(
			'core/list',
			array(
				'name'         => 'checkmark-list',
				'label'        => __( 'Checkmark', 'cas-earth-theme' ),
				'inline_style' => '
				ul.is-style-checkmark-list {
					list-style-type: "\2713";
				}

				ul.is-style-checkmark-list li {
					padding-inline-start: 1ch;
				}',
			)
		);
	}
endif;
add_action( 'init', 'casearththeme_block_styles' );

if ( ! function_exists( 'casearththeme_pattern_categories' ) ) :
	/**
	 * Registers pattern categories.
	 *
	 * @since Twenty Twenty-Five 1.0
	 *
	 * @return void
	 */
	function casearththeme_pattern_categories() {

		register_block_pattern_category(
			'casearththeme_page',
			array(
				'label'       => __( 'Pages', 'cas-earth-theme' ),
				'description' => __( 'A collection of full page layouts.', 'cas-earth-theme' ),
			)
		);

		register_block_pattern_category(
			'casearththeme_post-format',
			array(
				'label'       => __( 'Post formats', 'cas-earth-theme' ),
				'description' => __( 'A collection of post format patterns.', 'cas-earth-theme' ),
			)
		);
	}
endif;
add_action( 'init', 'casearththeme_pattern_categories' );

if ( ! function_exists( 'casearththeme_register_block_bindings' ) ) :
	/**
	 * Registers the post format block binding source.
	 *
	 * @since Twenty Twenty-Five 1.0
	 *
	 * @return void
	 */
	function casearththeme_register_block_bindings() {
		register_block_bindings_source(
			'cas-earth-theme/format',
			array(
				'label'              => _x( 'Post format name', 'Label for the block binding placeholder in the editor', 'cas-earth-theme' ),
				'get_value_callback' => 'casearththeme_format_binding',
			)
		);
	}
endif;
add_action( 'init', 'casearththeme_register_block_bindings' );

if ( ! function_exists( 'casearththeme_format_binding' ) ) :
	/**
	 * Callback function for the post format name block binding source.
	 *
	 * @since Twenty Twenty-Five 1.0
	 *
	 * @return string|void Post format name, or nothing if the format is 'standard'.
	 */
	function casearththeme_format_binding() {
		$post_format_slug = get_post_format();

		if ( $post_format_slug && 'standard' !== $post_format_slug ) {
			return get_post_format_string( $post_format_slug );
		}
	}
endif;
