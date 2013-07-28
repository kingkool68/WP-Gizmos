jQuery(document).ready(function($) {
	$('.add-new-gizmo').change(function(e) {
		e.preventDefault();
		var $this = $(this);
		var $context = $this.parents('.postbox');
		var selected = $this.val();
		if( selected === '0' ) {
			return false;
		}
		
		var gizmoClass = $context.attr('id').replace(/wp-gizmo-/, '');
		var gizmoNum = parseInt( $this.attr('data-gizmo-count') );
		var data = {
			action: 'add_new_gizmo',
			'gizmo-class': gizmoClass,
			'gizmo-type': selected,
			'gizmo-num': gizmoNum
		};
		
		$this.attr('data-gizmo-count', gizmoNum + 1);		
		
		
		$.post(ajaxurl, data, function(response) {
			html = $(response).eq(0).wrap('<div/>').parent(); //Wrap the response in a <div> and go up to the root of the element.
			html.find('.closed').removeClass('closed'); //Remove the closed class so the gizmo is displayed open.
			$('.gizmos', $context).append( html.html() ); //Add the new HTML to the dom.
			$this.val(0);
		});
	});
	
	$('.gizmos').sortable({
		axis: 'y',
		placeholder: 'sortable-placeholder',
		forcePlaceholderSize: true,
		handle: 'h3',
		cursor: 'ns-resize'
	});
	
	$('.postbox').on('click', '.handlediv', function() {
		$(this).parents('.form-wrap').toggleClass('closed');
	});
	
	$('.gizmos').on('click', '.delete', function(e) {
		e.preventDefault();
		var sure = confirm('Are you sure you want to delete this gizmo?');
		if( sure ) {
			$(this).parents('.form-wrap').remove();
		}
	});
	
	$('.postbox').on('change', '.gizmo-title', function() {
		var $this = $(this);
		
		//Only the first .gizmo-title per metabox matters.
		if( $this.is( $this.parents('.form-field').find('.gizmo-title:eq(0)') ) ) {
			update_gizmo_title( $this );
		}
	});
	$('.gizmos .form-field').each(function() {
		$this = $(this).find('.gizmo-title:eq(0)');
		update_gizmo_title( $this );
	});
	
	
	
	function update_gizmo_title( $that ) {
		var gizmo_title = $that.val();
		var $h3 = $that.parents('.form-field').siblings('h3');
		$h3.find('i').remove();
		$h3.append('<i>' + gizmo_title + '</i>');
	}
	
	
	
	
	/* Media Library */
	
	// Bind to our click event in order to open up the new media experience.
	$('.gizmos').on('click.gizmo-media', '.gizmo-media', function(e){
		e.preventDefault();
		
		$this = $(this);
		var $context = $this.parents('.gizmos');
		var target = $this.data('target-selector');
		var $target_input = $(target, $context);
		var userLibraryOptions = $this.data('options');
		var returnProperty = $this.data('return-property');
		if( !returnProperty ) {
			returnProperty = 'url';
		}
		
		var defaultLibraryOptions = {
			className: 'media-frame gizmo-media-frame',
			frame: 'select', //'select' or 'post'
			multiple: false,
			//title: 'Dataset Upload', //I have no idea where this appears? 
			library: {
				//type: 'image' //Can be 'image', 'audio', 'video', 'file' or any other mimetype. Check the post_mime_type column in wp_posts table for examples.
			},
			button: {
				//text:  'Select Zip File'
			}
		}
		
		libraryOptions = {};
		$.extend(true, libraryOptions, defaultLibraryOptions, userLibraryOptions);

		gizmo_media_frame = wp.media.frames.gizmo_media_frame = wp.media( libraryOptions );

		gizmo_media_frame.on('select', function() {
			// Grab our attachment selection and construct a JSON representation of the model.
			var media_attachment = gizmo_media_frame.state().get('selection').first().toJSON();

			// Send the attachment URL to our custom input field via jQuery.
			$target_input.val(media_attachment[returnProperty]);
		});

		// Now that everything has been set, let's open up the frame.
		gizmo_media_frame.open();
	});
});