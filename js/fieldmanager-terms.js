( function( $ ) {

var fm_terms_element;

fm_terms_suggest = function( $element ) {
	// If the post title and content are both empty, do nothing
	if ( $("#title").val() != "" || tinymce.activeEditor.getContent() != "" ) {
		// Store the element we are working with
		fm_terms_element = $element.data('relatedElement');
		// Query for matching terms
		$.post( ajaxurl, { action: 'fm_terms_extract', post_title: $("#title").val(), post_content: tinymce.activeEditor.getContent(), taxonomy: $element.data("taxonomy"), fm_terms_extract_nonce: fm_terms.nonce }, function ( result ) {
			resultObj = JSON.parse( result );
			// Check if there were results
			if( !$.isEmptyObject( resultObj ) && fm_terms_element != "" ) {
				// Iterate over the matches
				$.each( resultObj, function( taxonomy, terms ) {
					$.each( terms, function( index, term_id ) {
						// Build the selector
						var selector = '#' + fm_terms_element;
						if( $( selector + ' optgroup' ).length != 0 ) selector = selector + ' optgroup[label="' + taxonomy + '"]';
						selector = selector + ' option[value="' + term_id + '"]';

						// Select the element
						$(selector).attr('selected', 'selected');
					});
				});
				
				// Tell chosen to update the form display
				$( "#" + fm_terms_element ).trigger( "liszt:updated" );
				
				// Also trigger a jQuery event other custom theme scripts can bind to if needed
				$( "#" + fm_terms_element ).trigger( 'fm_terms_suggest', resultObj );

			}
			
			// Clear the terms element since this was used solely for this request
			fm_terms_element = "";
		});
	}
}

$( document ).ready( function () {
	$( '.fm-terms-suggest' ).live( 'click', function( e ) {
		fm_terms_suggest( $(this) );
	} );
} );

} )( jQuery );