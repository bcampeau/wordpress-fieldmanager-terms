<?php

//class Fieldmanager_Terms {

/**
 * Handle auto term extraction for posts
 *
 * @params string $post_type
 * @return void
 */
function sn_extract_terms() {

	global $post;
	
	// Strip tags from the post content
	$filtered_content = strip_tags( $post->post_content );
	
	// Replace any curly quotes with straight quotes
	$filtered_content = str_replace( "’", "'", $filtered_content );
	
	// Strip all non-essential punctuation from the post
	$filtered_content = preg_replace( "/[^a-z0-9-' ]+/i", ' ', $filtered_content );

	// Strip the most common words from the text
	$most_common_words = array();
	foreach( sn_config( 'most_common_words' ) as $common_word ) {
		$most_common_words[] = '/(^|\s+)' . $common_word . '(\s+|$)/i';
	}
	$filtered_content = preg_replace( $most_common_words, " ", $filtered_content );
	
	// See if any taxonomy terms are contained within the content
	$term_matches = array();
	
	// Get all taxonomies defined for this post type
	foreach( get_object_taxonomies( $post->post_type ) as $taxonomy_name ) {

		// Non-public taxonomies should be excluded from search. Get taxonomy information to verify.
		$taxonomy = get_taxonomy( $taxonomy_name );
		if ( $taxonomy->public ) {

			// Get all terms for this taxonomy 
			$terms = get_terms( $taxonomy_name );
			
			foreach( $terms as $term ) {
			
				// If the term was matched, store it in a taxonomy-specific array
				if( preg_match( '/(^|\s+)' . $term->name . '(\s+|$)/i', $filtered_content ) ) { 
					//echo( "Passing term (" . $term->name . ") with term_matches (" . print_r($term_matches, true) . ") and count (" . $match_count . ")" );
					sn_add_term_match( $term, $term_matches );
					
					// Check if there are any parent term matches to associate automatically
					sn_match_parent_terms( $term, $term_matches );
				}
				
			}
			
		}
	
	}
	
	// For now, let's just see what was found
	//print_r( $term_matches );
	
}
//add_action( 'admin_footer', 'sn_extract_terms' );

//}