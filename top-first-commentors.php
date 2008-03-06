<?php
/*
Plugin Name: Top First Comments
Plugin URI: http://fairyfish.net/2008/03/06/top-first-commentors/
Description: The plugin will show the top first commentors (the numner is number is set by user) of your blog.
Version: 0.1
Author: Denis
Author URI: http://fairyfish.net/
*/

function update_top_first_commentors(){
	global $wpdb;
	
	$top_first_commentors_options = get_option("top_first_commentors");
	$first_commentors = array(); 

	$q = "SELECT DISTINCT comment_post_id FROM $wpdb->comments WHERE comment_type ='' AND comment_approved = '1'"; 
	$have_comment_post_ids = $wpdb->get_results($q); 
	foreach ($have_comment_post_ids as $have_comment_post_id){
		$q = "SELECT comment_author,user_id FROM $wpdb->comments WHERE comment_type ='' AND comment_approved = '1' AND comment_post_id = $have_comment_post_id->comment_post_id order by comment_date limit 1";
		$first_comment = $wpdb->get_results($q);  

		$top_first_commentors_exclude = $top_first_commentors_options["exclude"];
		
		if(!$top_first_commentors_exclude){
			array_push($first_commentors,$first_comment[0] -> comment_author); 
		}else{
			$top_first_commentors_exclude = explode(",",$top_first_commentors_exclude);
			if(!in_array($first_comment[0] -> user_id,$top_first_commentors_exclude)){
				array_push($first_commentors,$first_comment[0] -> comment_author); 
			}
		}
	}
	
	$first_commentors = (array_count_values ($first_commentors)); 
	arsort($first_commentors); 
	
	$first_commentors_author = array_keys($first_commentors);
	
	$top_first_commentors = array(); 
	
	$top_first_commentors_number = $top_first_commentors_options["number"];
	if(!$top_first_commentors_number) $top_first_commentors_number = 3;
	
	$top_first_commentors_titles = $top_first_commentors_options["titles"];
	if($top_first_commentors_titles)
		$top_first_commentors_titles = explode(",",$top_first_commentors_titles);
		
	for($i=0; $i<$top_first_commentors_number; $i++){
		
		$q = "SELECT comment_author_url FROM $wpdb->comments WHERE comment_type ='' AND user_id = 0 AND comment_approved = '1' AND comment_author_url !='' AND comment_author = '$first_commentors_author[$i]' limit 1";
		$first_comment_url = $wpdb->get_results($q); 
		
		$top_first_commentors_title = $top_first_commentors_titles[$i];
		if($top_first_commentors_title) $top_first_commentors_title .= " : ";
		if($first_comment_url){
			$top_first_commentors[$i] = $top_first_commentors_title . '<a href="' . $first_comment_url[0] -> comment_author_url . '" title="' . $first_commentors_author[$i].'">' . $first_commentors_author[$i] . '</a>(' . $first_commentors["$first_commentors_author[$i]"].')';
		} else {
			$top_first_commentors[$i] = $top_first_commentors_titles[$i] .$first_commentors_author[$i] . '('.$first_commentors["$first_commentors_author[$i]"] . ')';
		}
	}
	wp_cache_set("top_first_commentors", $top_first_commentors, "tfc", 36000); 
		
	return $top_first_commentors;
}

function get_top_first_commentors(){
	$output = ""; 
	$output .= '<ul class="top_first_commentors">';
	
	$top_first_commentors =  wp_cache_get("top_first_commentors", 'tfc');	
	if (false == $top_first_commentors) {
		$top_first_commentors = update_top_first_commentors();
		
	}
	foreach($top_first_commentors as $top_first_commentor){
		$output .= '<li>'. $top_first_commentor .'</li>';
	}
	
	$output .= '</ul>';	
	return $output;
}

function top_first_commentors(){
	$output = get_top_first_commentors();
	echo $output;
}

function widget_sidebar_top_first_commentors() {
	if ( !function_exists('register_sidebar_widget') || !function_exists('register_widget_control') )
		return;

	function widget_top_first_commentors($args) {
	    extract($args);
		echo $before_widget;
		
		$top_first_commentors_options = get_option('widget_top_first_commentors');
		$title = $top_first_commentors_options['title'];

		if ( empty($title) )	$title = 'Top First Commentors'; 
		
		echo $before_title . $title . $after_title;

		$output = get_top_first_commentors();
		echo $output;

		echo $after_widget;
	}

	register_sidebar_widget('Top First Commentors', 'widget_top_first_commentors');
	
	function widget_top_first_commentors_options() {			
		$top_first_commentors_options = $new_top_first_commentors_options = get_option('widget_top_first_commentors'); 
		if ( $_POST["top_first_commentors_submit"] ) { 
			$new_top_first_commentors_options['title'] = strip_tags(stripslashes($_POST["top_first_commentors_title"]));
			if ( $top_first_commentors_options != $new_top_first_commentors_options ) { 
				$top_first_commentors_options = $new_top_first_commentors_options;
				update_option('widget_top_first_commentors', $top_first_commentors_options);
			}
		}
		$title = attribute_escape($top_first_commentors_options['title']);
?>
		<p><label for="top_first_commentors_title"><?php _e('Title:'); ?> <input style="width: 250px;" id="top_first_commentors_title" name="top_first_commentors_title" type="text" value="<?php echo $title; ?>" /></label></p>
		<input type="hidden" id="top_first_commentors_submit" name="top_first_commentors_submit" value="1" />
<?php
	}
	register_widget_control('Top First Commentors', 'widget_top_first_commentors_options', 300, 90);
}

add_action('plugins_loaded', 'widget_sidebar_top_first_commentors');

function top_first_commentors_options(){
	$message='更新成功';
	if($_POST['update_top_first_commentors_option']){
		
		$top_first_commentors_saved = get_option("top_first_commentors");
		
		$top_first_commentors = array (
			"exclude" => $_POST['top_first_commentors_exclude_option'],
			"number"  => $_POST['top_first_commentors_number_option'],
			"titles"   => $_POST['top_first_commentors_titles_option']
		);
		
		if ($top_first_commentors_saved != $top_first_commentors)
			if(!update_option("top_first_commentors",$top_first_commentors))
				$message='Update failed';
		
		update_top_first_commentors();
		
		echo '<div class="updated"><strong><p>'. $message . '</p></strong></div>';
	}
	
	$top_first_commentors_options = get_option("top_first_commentors");
?>
<div class=wrap>
	<form method="post" action="">
		<h2>Top First Commentors</h2>
		<fieldset name="wp_basic_options"  class="options">
		<table>
			<tr>
                <td valign="top" align="right">Exclude: </td>
				<td><input type="text" name="top_first_commentors_exclude_option" value="<?php echo $top_first_commentors_options["exclude"];  ?>" /> Exclude the users, input the user id, seprate with comma.  </td>
			</tr>
			<tr>
                <td valign="top" align="right">Number: </td>
                <td><input type="text" name="top_first_commentors_number_option" value="<?php echo $top_first_commentors_options["number"]; ?>" /> Set the number of top first commentors.</td>
            </tr>
			<tr>
                <td valign="top" align="right">Title: </td>
				<td><input type="text" name="top_first_commentors_titles_option" value="<?php echo $top_first_commentors_options["titles"]; ?>" /> Set the title for every top first commentor, for example: First,Second,Third</td>
            </tr>
		</table>		
			
		</fieldset>
		<p class="submit"><input type="submit" name="update_top_first_commentors_option" value="Update Options &raquo;" /></p>
	</form>
</div>
<?php
}

function top_first_commentors_options_admin(){
	add_options_page('Top First Commentors', 'Top First Commentors', 5,  __FILE__, 'top_first_commentors_options');
}

add_action('admin_menu', 'top_first_commentors_options_admin');
?>