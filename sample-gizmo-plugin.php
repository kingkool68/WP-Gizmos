<?php
/*
	Plugin Name: Wp Gizmo Sample
	Description: Shows you how to use the WP Gizmo Class to make per-post widgets.
	Author: Russell Heimlich	
	Version: 0.1
 */
 
function my_gizmo_init() {
 
	 class RelatedLink extends WP_Gizmo {
		 
		 /* If you want to change the 'Add New Gizmo' label, uncomment the next line */
		 //public $add_new_label = 'Add New Gizmo';
		
		// Which post_types should these Gizmos apply to?
		function post_types() {
			return array('post', 'page');
		}
		
		// Change properies for the metabox that is rendered. 
		function metabox() {
			//Set the title, context, priority 
			return array(
				'title' => 'Related Link',
				'context' => 'normal',
			);
		}
		
		// Define different types of Gizmo widgety thingies that appear in the Add New dropdown. 
		public $gizmo_types = array(
			'link' => 'Link',
			'image_link' => 'Image Link' //The key can't have a - in it since it is tied to a function name that will be called. 
		);
		
		function render_link_fields($num, $data = NULL) {
			?>
			<p> 
				<label for="<?php echo $this->get_field_name('text');?>">Link Text</label>
				<input type="text" class="gizmo-title" name="<?php echo $this->get_field_name('text');?>" id="<?php echo $this->get_field_name('text');?>" value="<? echo $data['text'];?>">
			</p>
			
			<p> 
				<label for="<?php echo $this->get_field_name('link');?>">Link URL</label>
				<input type="text" class="gizmo-title" name="<?php echo $this->get_field_name('link');?>" id="<?php echo $this->get_field_name('link');?>" value="<? echo $data['link'];?>">
			</p>
			<?php
		}
		
		function render_link( $data, $count ) {
			extract($data);
			?>
			<li><a href="<?php echo $link; ?>"><?php echo $text ?></a></li>
			<?php
		}
		
		function render_image_link_fields($num, $data = NULL) {
			?>
			<p> 
				<label for="<?php echo $this->get_field_name('link');?>">Link URL</label>
				<input type="text" class="gizmo-title" name="<?php echo $this->get_field_name('link');?>" id="<?php echo $this->get_field_name('link');?>" value="<? echo $data['link'];?>">
			</p>
			
			<p> 
				<label for="<?php echo $this->get_field_name('image');?>">Image URL</label>
				<input type="text" class="" name="<?php echo $this->get_field_name('image');?>" id="<?php echo $this->get_field_name('image');?>" value="<? echo $data['image'];?>">
			</p>
			
			<p class="wp-media-buttons">
				<a class="button add_media gizmo-media" data-target-selector='input[name="<?php echo $this->get_field_name('image');?>"]' data-options='{"multiple": false }' data-return-property="url"><span class="wp-media-buttons-icon"></span> Select an Image</a>
			</p>
			
			<p> 
				<label for="<?php echo $this->get_field_name('alt_text');?>">Image Alt Text</label>
				<input type="text" name="<?php echo $this->get_field_name('alt_text');?>" id="<?php echo $this->get_field_name('alt_text');?>" value="<? echo $data['alt_text'];?>">
			</p>
			
			<p>
				<label for="<?php echo $this->get_field_name('alignment');?>">Alignment</label>
				<select name="<?php echo $this->get_field_name('alignment');?>">
					<?php
					$alignments = array('left', 'right', 'center');
					foreach( $alignments as $align ):
					?>
					<option value="<?php echo $align; ?>" <?php selected($data['alignment'], $align); ?>><?php echo ucfirst($align); ?></option>
					<?php endforeach; ?>
				</select>
			</p>
			<?php
		}
		
		function render_image_link( $data, $count ) {
			extract($data);
			?>
			<li>
				<a href="<?php echo $link; ?>">
					<img src="<?php echo $image;?>" class="align<?php echo $alignment ?>" alt="<?php echo $alt_text ?>">
				</a>
			</li>
			<?php
		}
	}
	
	// Initiates the class.
	register_gizmo('RelatedLink');
}

add_action('gizmo_init', 'my_gizmo_init');


// Just an example of how to render the gizmos in your theme via standard WordPress hooks though you can use render_gizmo() anywhere. 
function my_gizmo_content($content) {
	if( function_exists('get_gizmos') ) {
		if( $gizmos = get_gizmos('RelatedLink') ) {
			echo '<h2>' . count( $gizmos ) . ' Related Links</h2>';
			echo '<ul>';
			render_gizmo('RelatedLink');
			echo '</ul>';
		}
	}
	
	return $content;
}
add_filter('the_content', 'my_gizmo_content');