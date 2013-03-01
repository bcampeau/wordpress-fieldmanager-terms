( function( $ ) {

var fm_terms_element;

fm_terms_suggest = function( $element ) {
	// If the post title and content are both empty, do nothing
	if ( tinymce.activeEditor != null && ( $("#title").val() != "" || tinymce.activeEditor.getContent() != "" ) ) {
		// Store the element we are working with
		fm_terms_element = $element.data('relatedElement');
		// Query for matching terms
		$.post( ajaxurl, { action: 'fm_terms_extract', post_title: $("#title").val(), post_content: tinymce.activeEditor.getContent(), taxonomy: $element.data("taxonomy"), fm_terms_extract_nonce: fm_terms.nonce }, function ( result ) {
			resultObj = JSON.parse( result );
			// Check if there were results
			if( !$.isEmptyObject( resultObj ) && fm_terms_element != "" ) {
				// Iterate over the matches
				$.each( resultObj, function( taxonomy, terms ) {
					$.each( terms, function( index, term ) {
						// Build the selector
						var element_selector = '#' + fm_terms_element;
						if( $( element_selector + ' optgroup' ).length != 0 ) { 
							// Add the optgroup to the selector
							var optgroup_selector = element_selector + ' optgroup[label="' + taxonomy + '"]';
							
							// Determine if the optgroup exists. If not, create it.
							if( $(optgroup_selector).length == 0 ) {
								$(element_selector).append(
									$("<optgroup></optgroup>" )
										.attr( "label", taxonomy )
								);
							}
							
							element_selector = optgroup_selector;
						}
							
						var option_selector = element_selector + ' option[value="' + term.term_id + '"]';
						
						// Determine if the option exists. If not, create it.
						if( $(option_selector).length == 0 ) {
							$(element_selector).append(
								$("<option></option>" )
									.attr( "value", term.term_id )
									.text( term.name )
							);
						}
	
						// Select the element
						$(option_selector).attr('selected', 'selected');
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