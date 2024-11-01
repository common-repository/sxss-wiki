<?php
/*
Plugin Name: sxss Wiki
Plugin URI: http://sxss.info
Description: Adds a contents box on the top of a page
Author: sxss
Version: 1.0
*/

		$sxss_wiki_fields[] = "sxss_wiki_active";
		$sxss_wiki_fields[] = "sxss_wiki_tag";
		$sxss_wiki_fields[] = "sxss_wiki_title";

	/* =============================================================================
	   Internationalization
	   ========================================================================== */

	load_plugin_textdomain('sxss_wiki', false, basename( dirname( __FILE__ ) ) . '/languages' );

	/* =============================================================================
	   Register Meta Box
	   ========================================================================== */

	add_action('admin_menu', 'sxss_wiki_add_box');

	function sxss_wiki_add_box() {

		add_meta_box('sxss_next_page', __('sxss Wiki' , 'sxss_wiki'), 'sxss_wiki_display_box', 'page', 'side', 'default');
		add_meta_box('sxss_next_page', __('sxss Wiki' , 'sxss_wiki'), 'sxss_wiki_display_box', 'post', 'side', 'default');

	}

	/* =============================================================================
	   Meta Box
	   ========================================================================== */

	// Callback function to show fields in meta box
	function sxss_wiki_display_box() {

		global $meta_box, $post, $sxss_wiki_fields;

		foreach($sxss_wiki_fields as $key) {

			$meta[$key] = get_post_meta($post->ID, $key, true);

		}

		?>

		<input type="hidden" name="sxss_wiki_box_nonce" value="<?php echo wp_create_nonce(basename(__FILE__)); ?>" />

		<table cellpadding="3">

			<tr>

				<td><input type="checkbox" id="sxss_wiki_active" name="sxss_wiki_active" value="1" <?php if($meta["sxss_wiki_active"] == "1") echo "checked"; ?> /></td>
				
				<td><?php echo __('Generate index', 'sxss_wiki'); ?></td>

			</tr>

			<tr>

				<td>
				
					<select name="sxss_wiki_tag">
					
						<option value="h1" <?php if( $meta[sxss_wiki_tag] == "h1" ) echo 'selected'; ?>>&lt;h1&gt;</option>
						<option value="h2" <?php if( $meta[sxss_wiki_tag] == "h2" ) echo 'selected'; ?>>&lt;h2&gt;</option>
						<option value="h3" <?php if( $meta[sxss_wiki_tag] == "h3" ) echo 'selected'; ?>>&lt;h3&gt;</option>
						<option value="h4" <?php if( $meta[sxss_wiki_tag] == "h4" ) echo 'selected'; ?>>&lt;h4&gt;</option>
						<option value="strong" <?php if( $meta[sxss_wiki_tag] == "strong" ) echo 'selected'; ?>>&lt;strong&gt;</option>
						<option value="em" <?php if( $meta[sxss_wiki_tag] == "em" ) echo 'selected'; ?>>&lt;em&gt;</option>
						<option value="bla" <?php if( $meta[sxss_wiki_tag] == "bla" ) echo 'selected'; ?>>&lt;bla&gt;</option>
				
					</select>
				
				
				</td>
				
				<td><?php echo __('Use this HTML tag', 'sxss_wiki'); ?></td>

			</tr>
			
			<tr>

				<td><input type="text" id="sxss_wiki_title" name="sxss_wiki_title" value="<?php echo $meta["sxss_wiki_title"]; ?>" /></td>
				
				<td><?php echo __('Caption of the list', 'sxss_wiki'); ?></td>

			</tr>

		</table>

	<?php

	}

	/* =============================================================================
	   Save Metabox Content
	   ========================================================================== */

	add_action('save_post', 'sxss_wiki_save_data');

	// Save data from meta box
	function sxss_wiki_save_data($post_id) {

		global $meta_box, $sxss_wiki_fields;

		// verify nonce
		if ( false == wp_verify_nonce($_POST['sxss_wiki_box_nonce'], basename(__FILE__) ) ) {

			return $post_id;

		}

		// check autosave
		if ( true == defined('DOING_AUTOSAVE') && true == DOING_AUTOSAVE) {

			return $post_id;

		}

		// check permissions
		if ('page' == $_POST['post_type']) {

			if ( false == current_user_can('edit_page', $post_id) ) {

				return $post_id;

			}

		} elseif ( false == current_user_can('edit_post', $post_id) ) {

			return $post_id;

		}
		
		$sxss_wiki_fields[] = "sxss_wiki_active";
		$sxss_wiki_fields[] = "sxss_wiki_tag";
		
		// sxss wiki check
		if( 
			( $_POST["sxss_wiki_active"] == 1 || $_POST["sxss_wiki_active"] == 0 ) &&
			( $_POST["sxss_wiki_tag"] == "h1" || $_POST["sxss_wiki_tag"] == "h2" || $_POST["sxss_wiki_tag"] == "h3" || $_POST["sxss_wiki_tag"] == "h4" || $_POST["sxss_wiki_tag"] == "strong" || $_POST["sxss_wiki_tag"] == "em")
			) {}
		else wp_die( __('Error while saving. Please go back.', 'sxss_wiki') );

		foreach($sxss_wiki_fields as $field) {

			$old = get_post_meta($post_id, $field, true);

			$new = wp_filter_post_kses( $_POST[$field] );
			$new = strip_tags( $new );

			if ($new && $new != $old) {

				update_post_meta($post_id, $field, $new);

			} elseif ('' == $new && $old) {

				delete_post_meta($post_id, $field, $old);

			}

		}
	}
	
	/* =============================================================================
	   Generate Slug
	   ========================================================================== */

	function sxss_wiki_slug($string){
	
		$string = preg_replace("/[^0-9a-zA-Z]/","",$string);
		
		$string = strtolower($string);
		
		return $string;
	}
	
	/* =============================================================================
	   Manipulate Tag
	   ========================================================================== */
	
	function sxss_wiki_replace($string, $tag) {
	
		return '<a name="' . sxss_wiki_slug($string) . '"></a><' . $tag . '>' . $string . '</' . $tag . '>';
	
	}
	
	function sxss_wiki_manipulate_tag($content, $tag){
	
		// e modifier am ende für php im replace
		$search = '/<' . $tag . '>(.*?)<\/' . $tag . '>/e';
		
		$content = preg_replace($search, "sxss_wiki_replace('$1', $tag)", $content);

		
		return $content;
	}
	
	/* =============================================================================
	   Search Content For h2
	   ========================================================================== */

	function sxss_wiki_tags($string, $tagname){
	
		$string = utf8_decode($string);
	
		$d = new DOMDocument();
		
		$d->loadHTML($string);
		
		$return = array();
		
		$i = 0;
		
		foreach($d->getElementsByTagName($tagname) as $item){
			
			$return[$i]["slug"] = sxss_wiki_slug( utf8_encode( $item->textContent ) );
			$return[$i]["title"] = $item->textContent;
			
			$i++;
		
		}
		
		return $return;
	}



	/* =============================================================================
	   Add Next Page Buttons To The Page
	   ========================================================================== */

	function sxss_wiki_display($content)
	{
		global $post;
		
		$return = "";

		if( get_post_meta($post->ID, "sxss_wiki_active", true) == 1 )
		{
		
			$tag = get_post_meta($post->ID, "sxss_wiki_tag", true);
		
			$sections = sxss_wiki_tags($content, $tag);
			
			if( sizeof($sections) > 0)
			{
			
				$title = get_post_meta($post->ID, "sxss_wiki_title", true);
			
				if( $title == '' ) $headline =  __('Contents', 'sxss_wiki');
				else $headline = $title;
			
				$return .= '
				
							<style>
							
								#sxss_wiki_contents {
									float: left; 
									background-color: #F9F9F9; 
									border: 1px solid #AAAAAA; 
									font-size: 90%; 
									padding: 7px; 
									margin-bottom: 10px;
								}
								
								#sxss_wiki_contents ol,
								#sxss_wiki_contents ul {
									margin: 0 0 0 2em;
									padding: 0;
								}
							
							</style>
							
							<a name="contents"></a>
							
							<div id="sxss_wiki_contents">
							
							<strong>' . $headline . ':</strong><br />
							
							<ol>';
			
				foreach($sections as $section)
				{
					$return .= '<li style="margin: 0; padding: 0;"><a href="#' . $section["slug"] . '">' . $section["title"] . '</a></li>';
				}
				
				$return .= '</ol></div><br style="clear: both;">';
				
				$content = sxss_wiki_manipulate_tag($content, $tag);
				
			}
		

		}

		
		return $return . $content;
	}

	add_action('the_content', 'sxss_wiki_display');
?>