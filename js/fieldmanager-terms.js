( function( $ ) {

var fm_terms_element;

$( document ).ready( function () {
	$( '.fm-terms-suggest' ).live( 'click', function( e ) {
		// If the post title and content are both empty, do nothing
		if ( $("#title").val() != "" || $("#content").val() != "" ) {
			// Store the element we are working with
			fn_terms_element = $(this).data('relatedElement');
		
			// Query for matching terms
			$.post( ajaxurl, { action: 'fm_terms_extract', post_title: $("#title").val(), post_content: $("#content").val(), fm_terms_extract_nonce: fm_terms.nonce }, function ( result ) {
				resultObj = JSON.parse( result );
				// Check if there were results
				if( resultObj.length > 0 ) {
					// Iterate over the matches
					$.each( resultObj, function( taxonomy, terms ) {
						$.each( terms, function( index, term_id ) {
							// Build the selector
							var selector = '#' + fm_terms_element;
							if( $( selector + ' optgroup' ).length != 0 ) selector = selector + ' optgroup[value="' + taxonomy + '"]';
							selector = selector + ' option[value="' + term_id + '"]';
						
							// Select the element
							$(selector).attr('selected', 'selected');
							
						});
					});
					
					// Tell chosen to update the form display
					$(fm_terms_element).trigger("liszt:updated");
				}
				
				// Clear the terms element since this was used solely for this request
				fm_terms_element = "";
			});
		}
		
	} );
} );

} )( jQuery );