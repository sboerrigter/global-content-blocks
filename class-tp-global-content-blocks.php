<?php
/**
 * Plugin Name: Global content blocks
 * Description: Adds global content blocks to WordPress. Call them by using a template tag or a shortcode.
 *
 * Plugin URI: https://github.com/trendwerk/global-content-blocks
 * 
 * Author: Trendwerk
 * Author URI: https://github.com/trendwerk
 * 
 * Version: 1.0.1
 */

class TP_Global_Content_Blocks {
	function __construct() {
		add_action( 'plugins_loaded', array( $this, 'localization' ) );
		add_action( 'init' , array( $this, 'register' ) );
		
		add_shortcode( 'gc' , array( $this, 'display' ) );
		add_action( 'save_post', array( $this, 'save' ) );
		
		/**
		 * Custom column in the edit screen
		 */
		add_filter( 'manage_edit-gc_columns' , array( $this, 'add_column' ) );
		add_action( 'manage_posts_custom_column', array( $this, 'display_column' ) );
	}

	/**
	 * Load localization
	 */
	function localization() {
		load_muplugin_textdomain( 'gc', dirname( plugin_basename( __FILE__ ) ) . '/assets/lang/' );
	}
	
	/**
	 * Register post type
	 */
	function register() {
		$labels = array(
			'name'          => __( 'General' , 'gc' ),
			'singular_name' => __( 'Content' , 'gc' ),
			'add_new'       => __( 'Add content' , 'gc' ),
			'add_new_item'  => __( 'Add content' , 'gc' ),
		);
		
		$args = array(
			'labels'        => $labels,
			'public'        => false,
			'show_ui'       => true,
			'menu_position' => 21,
			'supports'      => array( 'title', 'editor', 'revisions' ),
		);
		
		register_post_type( 'gc' , apply_filters( 'tp_gc_args' , $args ) );
	}
	
	/**
	 * @column Add new column
	 */
	function add_column( $columns ) {
		$columns['template_tag'] = __( 'Template tag' , 'gc' );
		$columns['shortcode'] = __( 'Shortcode' , 'gc' );
		return $columns;
	}
	
	/**
	 * @column Display column content
	 */
	function display_column( $name ) {
		global $post;
		
		if( 'template_tag' == $name && 'gc' == $post->post_type ) {
			echo '<code>' . esc_attr('<?php the_gc( \'' . get_post_meta( $post->ID, '_gc_id', true ) . '\' ); ?>') . '</code>';
		} elseif( 'shortcode' == $name && 'gc' == $post->post_type ) {
			echo '<code>' . esc_attr('[gc name="' . get_post_meta( $post->ID, '_gc_id', true ) . '"]') . '</code>';
		}
	}

	function save( $post_id ) {
		/**
		 * Perform checks
		 */
		if( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) )
			return;

		if( isset( $_REQUEST['doing_wp_cron'] ) )
			return;
			
		if( isset( $_REQUEST['post_view'] ) )
		    return;

		if( ! isset( $_POST['post_type'] ) || 'gc' != $_POST['post_type'] )
			return;

		/**
		 * Save data
		 */
		$post = get_post( $post_id );

		if( ! get_post_meta( $post_id, '_gc_id', true ) && 'publish' == $post->post_status )
			update_post_meta( $post_id, '_gc_id', self::get_id( $post->post_title ) );
	}
	
	/**
	 * Display a block
	 */
	function display( $args ) {
		if( !isset( $args['name'] ) ) 
			return;
		
		global $post;
		
		if( self::exists( $args['name'] ) ) {
			$block = self::get( $args['name'] );
			return apply_filters( 'the_content' , $block->post_content );
		} else {
			self::create( $args['name'] );
		}
	}
	
	/**
	 * Check if a block exists
	 *
	 * @param string $name
	 *
	 * @abstract
	 */
	public static function exists( $name ) {
		if( self::get( $name ) ) return true;
	}
	
	/**
	 * Get a block
	 *
	 * @param string $name
	 *
	 * @abstract
	 */
	public static function get( $name ) {
		$posts = get_posts( array(
			'post_type'      => 'gc',
			'posts_per_page' => -1,
			'meta_query'     => array(
				array(
					'key'   => '_gc_id',
					'value' => self::get_id( $name )
				)
			),
		) );
		
		if( 0 < count( $posts ) ) return $posts[0];
		
		return false;
	}
	
	/**
	 * Create new block
	 *
	 * @param string $name
	 *
	 * @abstract
	 */
	public static function create( $name ) {
		$post_id = wp_insert_post( array(
			'post_type'   => 'gc',
			'post_status' => 'publish',
			'post_title'  => $name,
		) );
		
		update_post_meta( $post_id, '_gc_id', self::get_id( $name ) );
	}
	
	/**
	 * Get _gc_id based on a name
	 *
	 * @param string $name
	 *
	 * @abstract
	 */
	public static function get_id( $name ) {
		return sanitize_title_with_dashes( $name );
	}
} new TP_Global_Content_Blocks;

/**
 * Template tag
 *
 * @param string $name
 * @param bool $return
 */
function the_gc( $name, $return = false ) {
	$gc = do_shortcode( '[gc name="' . $name . '"]' );

	if( $return )
		return $gc;

	echo $gc;
}
