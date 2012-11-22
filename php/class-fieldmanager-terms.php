<?php

class Fieldmanager_Terms {

	/** @type string Fieldmanager field name to augment with term extraction */ 
	public $field_name;
	
	/** @type int Specify a minimum length under which content would not searched for terms */ 
	public $minimum_content_length;
	
	/** @type string Label for term extraction button */ 
	public $button_label = "Suggest Terms";
	
	/** @type array List of common words to strip out before term extraction */ 
	private $most_common_words = array( 'the','be','to','of','and','a','in','that','have','I','it','for','not','on','with','he','as','you','do','at','this','but','his','by','from','they','we','say','her','she','or','an','will','my','one','all','would','there','their','what','so','up','out','if','about','who','get','which','go','me','when','make','can','like','time','no','just','him','know','take','people','into','year','your','good','some','could','them','see','other','than','then','now','look','only','come','its','over','think','also','back','after','use','two','how','our','work','first','well','way','even','new','want','because','any','these','give','day','most','us' );

	public function __construct( $options = array() ) {
		// Extract options
		foreach ( $options as $k => $v ) {
			try {
				$reflection = new ReflectionProperty( $this, $k ); // Would throw a ReflectionException if item doesn't exist (developer error)
				if ( $reflection->isPublic() ) $this->$k = $v;
				else throw new Exception; // If the property isn't public, don't set it (rare)
			} catch ( Exception $e ) {
				$message = sprintf(
					__( 'You attempted to set a property <em>%1$s</em> that is nonexistant or invalid for an instance of <em>%2$s</em> named <em>%3$s</em>.' ),
					$k, __CLASS__, !empty( $options['name'] ) ? $options['name'] : 'NULL'
				);
				$title = __( 'Nonexistant or invalid option' );
				wp_die( $message, $title );
			}
		}
		
		// Add the action hook for term extraction handling via AJAX
		add_action( 'wp_ajax_fm_terms_extract', array( $this, 'ajax_extract_terms' ) );
		
		// Add the filter required for handling addition of a suggest terms button
		add_filter( 'fm_element_markup_end', array( $this, 'modify_form_element' ), 10, 2 ); 
		
		// Add the Fieldmanager Terms javascript library
		fm_add_script( 'fm_terms_js', 'js/fieldmanager-terms.js', array(), false, false, 'fm_terms', array( 'nonce' => wp_create_nonce( 'fm_terms_extract_nonce' ) ), fieldmanager_terms_get_baseurl() );

	}
	
	/**
	 * Handle the AJAX request for term extraction 
	 *
	 * @params string $post_type
	 * @return void
	 */
	public function ajax_extract_terms() {
		// Check the nonce before we do anything
		check_ajax_referer( 'fm_terms_extract_nonce', 'fm_terms_extract_nonce' );
		
		// Create an array to hold the results.
		$result = array();
				
		// Pass the post title and content to term extraction if one if them is not empty. 
		// Otherwise return the empty array.
		if( trim( ( $_POST['post_title'] ) != "" || trim( $_POST['post_content'] ) != "" ) && $_POST['taxonomy'] != "" ) {
			$result  = $this->extract_terms( $_POST['post_title'],  $_POST['post_content'], explode( ",", $_POST['taxonomy'] ) );
		}

		echo json_encode( $result );
		
		die();
	}

	/**
	 * Handle auto term extraction for posts
	 *
	 * @params string $post_type
	 * @return void
	 */
	public function extract_terms( $post_title, $post_content, $taxonomy ) {

		// Merge the post title and content and strip all tags
		$filtered_content = strip_tags( $post_title . " " . $post_content );
		
		// Replace any curly quotes with straight quotes
		$filtered_content = str_replace( "’", "'", $filtered_content );
		
		// Strip all non-essential punctuation from the post
		$filtered_content = preg_replace( "/[^a-z0-9-' ]+/i", ' ', $filtered_content );
	
		// Strip the most common words from the text
		$most_common_words = array();
		foreach( $this->most_common_words as $common_word ) {
			$most_common_words[] = '/(^|\s+)' . $common_word . '(\s+|$)/i';
		}
		$filtered_content = preg_replace( $most_common_words, " ", $filtered_content );
						
		$term_matches = array();
		
		// Check if the minimum content length is set. If the content is under this limit, return an empty array.
		if( isset( $this->minimum_content_length ) && is_numeric( $this->minimum_content_length ) && strlen( $filtered_content ) < $this->minimum_content_length ) return $term_matches;
		
		// Check if taxonomies are defined. Do not proceed if not.
		// Also ensure this is an array.
		if( $taxonomy == null || empty( $taxonomy ) ) return $term_matches;
		if( !is_array( $taxonomy ) ) $taxonomy = array( $taxonomy );
		
		foreach( $taxonomy as $taxonomy_name ) {

			// Get data for the taxonomy
			$taxonomy_data = get_taxonomy( $taxonomy_name );
				
			// Get all terms for this taxonomy (even the terms with no posts associated with them)
			$terms = get_terms( 
				$taxonomy_name, 
				array( 
					'hide_empty' => 0 
				) 
			);

			foreach( $terms as $term ) {

				// If the term was matched, store it in a taxonomy-specific array
				if( preg_match( '/(^|\s+)' . $term->name . '(\s+|$)/i', $filtered_content ) ) { 
					$term_matches[$taxonomy_data->label][] = $term->term_id;
					// Apply a filter to allow for additional processing on this match
					$term_matches = apply_filters( 'fm_terms_match', $term_matches, $term );
				}
				
			}
						
		}
		
		return $term_matches;
		
	}
	
	/**
	 * Handles modifying the Fieldmanager field to add the suggest terms button
	 * @return string Modified Fieldmanager form element
	 */
	public function modify_form_element( $value, $field ) {
		// Verify the field name matches the one being modified and that it is the correct type of element to be used with this plugin.
		// Currently this functionality is only enabled for Fieldmanager_Select but may be introduced to other Fieldmanager_Option classes in the future.
		// If so, add the suggest button. Otherwise return the element unmodified.
		if ( $field->name == $this->field_name 
			&& get_class( $field ) == "Fieldmanager_Select"
			&& isset( $field->taxonomy )
			&& !empty( $field->taxonomy ) ) $value .= $this->suggest_terms( $field->get_element_id(), $field->taxonomy );
		
		return $value;
	}
	
	/**
	 * Generates HTML for the "Suggest Terms" button.
	 * @return string button HTML.
	 */
	public function suggest_terms( $related_field_id, $taxonomy ) {
		$classes = array( 'fm-terms-suggest', 'fm-terms-suggest-' . $this->field_name );
		$out = '<div class="fm-terms-suggest-wrapper">';
		$out .= sprintf(
			'<input type="button" class="%s" value="%s" name="%s" id="%s" data-related-element="%s" data-taxonomy="%s" />',
			implode( ' ', $classes ),
			__( $this->button_label ),
			'fm_terms_suggest_' . $this->field_name,
			$related_field_id . "-suggest",
			$related_field_id,
			( is_array( $taxonomy ) ) ? implode( ",", $taxonomy ) : $taxonomy
		);
		$out .= '</div>';
		return $out;
	}


}