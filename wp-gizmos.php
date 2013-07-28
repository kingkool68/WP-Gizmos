<?php 
/*
Plugin Name: Wp Gizmos
Description: Go go WP Gizmos go!
Version: 0.1
Author: Russell Heimlich
Author URI: http://www.russellheimlich.com
*/

class WP_Gizmo {
	public $gizmo_types = array();
	private $field_name = '';
	public $gizmos = array();
	public $add_new_label = 'Add New Gizmo';
	
	public function __construct() {
		$key = get_class($this);
		
		$this->field_name = 'wp-gizmo-' . $key;
		
		// Determine if we can load the metabox on the edit post screen.
		add_action( 'load-post.php', array( $this, 'pre_metabox' ) );
		add_action( 'load-post-new.php', array( $this, 'pre_metabox' ) );
		
		// Set-up an AJAX hook for adding new gizmo fields to the post edit screen.
		add_action('wp_ajax_add_new_gizmo', array( $this, 'add_new_gizmo') );
	}
	
	// Which post types should we show WP Gizmos in?
	public function post_types() {
		return array( 'post' );
	}
	
	public function admin_enqueue_scripts() {
		
	}
	
	public function pre_metabox() {
		//Figure out what the post_type is via http://themergency.com/wordpress-tip-get-post-type-in-admin/ 
		$post_type = null;
		global $post, $typenow, $current_screen;
		
		//we have a post so we can just get the post type from that
		if ( $post && $post->post_type ) {
			$post_type = $post->post_type;
		}
		
		//check the global $typenow - set in admin.php
		elseif( $typenow ) {
			$post_type = $typenow;
		}
		
		//check the global $current_screen object - set in sceen.php
		elseif( $current_screen && $current_screen->post_type ) {
			$post_type = $current_screen->post_type;
		}
	  
		//lastly check the post_type querystring
		elseif( isset( $_REQUEST['post_type'] ) ) {
			$post_type = sanitize_key( $_REQUEST['post_type'] );
		}
		
		// If the class has no metabox() method then we can't continue anyway so abort.
		if ( !method_exists( $this, 'metabox' ) ) {
			return;
		}
		
		if( in_array( $post_type, $this->post_types() ) ) {
			wp_enqueue_style( 'wp-gizmos', plugins_url( 'css/wp-gizmos.css', __FILE__ ), array(), 1.0, 'all' );
			wp_enqueue_script( 'wp-gizmos', plugins_url( 'js/wp-gizmos.js', __FILE__ ), array('jquery', 'jquery-ui-sortable'), 1.0, TRUE );
			
			wp_enqueue_media( array( 'post' => $post_type ) );
			
			add_action( 'add_meta_boxes', array( $this, 'add_metabox') );
			add_action( 'save_post', array( $this, 'save_metabox'), 10, 2 );	
		}
	}
	
	public function add_metabox( $post_type ) {
		
		$defaults = array(
			'title' => 'Untitled',
			'context' => 'normal',
			'priority' => 'high'
		);
		$args = wp_parse_args( $this->metabox(), $defaults );
		
		add_meta_box( $this->field_name , $args['title'], array( $this, 'render_metabox'), $post_type, $args['context'], $args['priority'] );
	}
	
	public function render_metabox($post, $box) {
		$gizmos = $this->get_gizmos($post->ID, $box['id']);
		if( !is_array($gizmos) || !isset($box['id']) ) {
			return;
		}
		?>
		<label for="add-new-<?php echo $box['id'];?>"><?php echo $this->add_new_label; ?></label>
		<select id="add-new-<?php echo $box['id'];?>" class="add-new-gizmo" data-gizmo-count="<?=count($gizmos);?>">
			<option value="0">--</option>
			<?php foreach( $this->gizmo_types as $key => $val ) { ?>
				<option value="<?php esc_attr_e($key); ?>"><?php echo $val ?></option>
			<?php } ?>
		</select>
		
		<div class="gizmos">
			<?php
			if( $gizmos ) {
				foreach( $gizmos as $count => $gizmo ){
					$func = 'render_' . $gizmo['_type'] . '_fields';
					if ( method_exists( $this, $func ) ) {
						
						$this->num = $count;
						
						$this->before_gizmo($gizmo['_type'], $count);
						call_user_func( array($this, $func), $count, $gizmo );
						$this->after_gizmo($gizmo['_type'], $count);
					}
				}
			}
			?>
		</div>
		
		<input type="hidden" name="wp-gizmo-nonce" value="<?php echo wp_create_nonce( 'GO GO WP Gizmos GO!' );?>">
		<?php
	}
	
	public function save_metabox($post_id) {

		// If WordPress is doing an autosave then abort.
		if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) {
			return $post_id;
		}
		
		
		if( defined('DOING_AJAX') && DOING_AJAX ) {
			return $post_id;
		}
		
		// If the save action is coming from a quick edit/batch edit then abort.
		if( strstr( $_SERVER['REQUEST_URI'], 'edit.php' ) ) { 
			return $post_id;
		}
		
		// If the nonce isn't set or can't be verified then abort.
		if ( !isset($_REQUEST['wp-gizmo-nonce']) || !wp_verify_nonce( $_REQUEST['wp-gizmo-nonce'], 'GO GO WP Gizmos GO!' ) ) {
			return;
		}
		
		if( isset($_REQUEST['wp-gizmos']) ) {
			//Loop over each Gizmo data, verify, and reindex the array before inserting it into the post_meta table.
			$gizmos = $this->get_gizmos($post_id);
			
			foreach( $_REQUEST['wp-gizmos'] as $key => $gizmo ) {
				$gizmos[$key] = $this->reindex_array( $gizmo );
			}
			
			update_post_meta( $post_id, 'wp-gizmos', $gizmos );
		}
	}
	
	// Via http://stackoverflow.com/a/1316978/1119655
	public function reindex_array($src) {
    	if( !is_array( $src ) ) {
			return $src;
		}
		
		$dest = array();
		foreach ($src as $value) {
			$dest[] = $value;
		}
	
		return $dest;
	}
	
	// Convenience function for constructing field names for the admin.  
	public function get_field_name($field_name = FALSE) {
		$base = 'wp-gizmos[' . $this->field_name . '][' . $this->num . ']';
		if( $fieldname === FALSE ) {
			return $base;
		}
		
		return $base . '[' . $field_name . ']';
	}
	
	
	public function before_gizmo($type = NULL, $num = 0) {
		if( !$type ) {
			return;
		}
	?>
		<div class="form-wrap closed">
			<h3><?php echo $type;?> <div title="Click to toggle" class="handlediv"><br></div></h3>
			<div class="form-field">
	<?php
	}
	
	public function after_gizmo($type = NULL, $num = 0) {
		if( !$type ) {
			return;
		}
	?>
				<input type="hidden" name="<?php echo $this->get_field_name('_type'); ?>" value="<?php esc_attr_e($type); ?>">
				<a href="#" class="delete hide-if-no-js">Delete</a>
			</div>
		</div>
	<?php
	}
	
	//An AJAX call that will get the HTML output for the gizmo admin field and send it back to the browser. 
	public function add_new_gizmo() {
		global $wp_gizmos;
		
		$type = $_REQUEST['gizmo-type'];
		$num = $_REQUEST['gizmo-num'];
		$gizmo_class_name = (string) $_REQUEST['gizmo-class'];
		
		if( !class_exists( $gizmo_class_name ) ) {
			die('Class Doesn\'t exist: ' . $gizmo_class_name);
		}
		$gizmo_class = $wp_gizmos[ $gizmo_class_name ];
		
		if( !$type ) {
			die();
		}
		
		$func = 'render_' . $type . '_fields';
		if( method_exists( $gizmo_class, $func ) ) {
			$gizmo_class->num = $num;
			
			$gizmo_class->before_gizmo($type, $num);
			call_user_func( array($gizmo_class, $func), $num );
			$gizmo_class->after_gizmo($type, $num);
		}
	
		die();
	}
	
	public function get_gizmos($post_id = FALSE, $key = -1) {
		if( !$post_id ) {
			global $post_id;
		}
		if( !$post_id ) {
			global $post;
			if( $post && $post->ID ) {
				$post_id = $post->ID;
			}
		}
		
		if( !$post_id ) {
			return false;
		}
		
		$gizmos = get_post_meta( $post_id, 'wp-gizmos', true );
		
		if( !$gizmos ) {
			return array();
		}
		
		if( $key !== -1 && isset( $gizmos[$key] ) ) {
			$gizmos = $gizmos[$key];
		}
		
		return $gizmos;
	}
	
	public function render($post_id = FALSE) {
		global $wp_gizmos;
		
		$gizmos = $this->get_gizmos($post_id);
		$gizmo_data = $gizmos[ $this->field_name ];
		
		$count = 0;
		foreach( $gizmo_data as $gizmo ) {
			$func = 'render_' . $gizmo['_type'];
			if ( method_exists( $this, $func ) ) {
				call_user_func( array($this, $func), $gizmo, $count );
				$count++;
			}
		}
	}
}


/*
 *   Helper Functions
 */
function register_gizmo( $gizmo_class ) {
	global $wp_gizmos;
	if( !isset( $wp_gizmos[$gizmo_class] ) ) { 
		$wp_gizmos[$gizmo_class] = new $gizmo_class();
	}
}

function render_gizmo( $gizmo_class ) {
	global $wp_gizmos;
	$wp_gizmos[$gizmo_class]->render();
}
function get_gizmos( $gizmo_class, $key = 0 ) {
	global $post_id, $wp_gizmos;
	if( !$gizmo_class || !is_string($gizmo_class) || !isset( $wp_gizmos[$gizmo_class] ) ) {
		return false;
	}
	
	if( $key !== 'all' ) {
		$key = 'wp-gizmo-' . $gizmo_class;
	}
	
	$class = $wp_gizmos[$gizmo_class];
	$results = $class->get_gizmos($post_id, $key);
	return $results;
}

//Kick things off...
function go_go_WP_Gizmo_go() {
	// Set a global variable called $wp_gizmos to hold references to Gizmo Classes.
	$GLOBALS['wp_gizmos'] = array();
	
	// Initiate the gizmo class.
	new WP_Gizmo();
	
	//Other gizmos can now extend the WP_Gizmo class.
	do_action( 'gizmo_init' );
}
add_action('plugins_loaded', 'go_go_WP_Gizmo_go');