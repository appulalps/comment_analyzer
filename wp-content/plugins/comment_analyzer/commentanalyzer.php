<?php
/*
Plugin Name: Comment Analyzer
Plugin Script: commentanalyzer.php
Plugin URI: 
Description:  It is a comment analyzer tool which shows the inner meaning of a comment. 
Version: 1.0
Author: Appulal Pushpalal Sinika
Author URI: http://appscafe.epizy.com/wordpress/
Template by: http://appscafe.epizy.com/wordpress/

=== RELEASE NOTES ===
2019-05-31 - v1.0 - first version
*/

include_once(dirname(__FILE__).'/admin.php');

register_activation_hook( __FILE__, 'activate_comment_analyzer');
register_deactivation_hook( __FILE__, 'deactivate_comment_analyzer');
register_uninstall_hook( __FILE__, 'uninstall_comment_analyzer');


add_action('admin_menu', 'comment_analyzer_menu');
add_action( 'comment_post','save_comment_analyzer_value' );
add_action( 'edit_comment','update_comment_analyzer_value' );
add_action( 'current_screen','total_comment_analyzer_count' );

//add_action( 'comment_analyzer_cron', 'find_all_comment_analyzer_value' );
add_filter( 'comment_text','add_comment_analyzer_images_with_comments', 10, 2);
add_filter( 'plugin_action_links', 'add_comment_analyzer_settings_link', 10, 2 );

if (class_exists("CommentAnalyzerAdmin")) {
	$ObjCommentAnalyzerPlugin = new CommentAnalyzerAdmin();
}

function activate_comment_analyzer(){
	global $wpdb;
	$tableName = $wpdb->prefix . "comments";
	$sql = "ALTER TABLE ".$tableName." ADD comment_analyzer_value varchar(20)";
	$wpdb->query($sql);
}

function deactivate_comment_analyzer() {
	$commentAnalyzerOptions = get_option('comment_analyzer_options');
	update_option( 'comment_analyzer_options', $commentAnalyzerOptions );
}

function uninstall_comment_analyzer() {
	global $wpdb;
	$tableName = $wpdb->prefix . "comments";
	$sql = "ALTER TABLE ".$tableName." DROP comment_analyzer_value ";
	$wpdb->query($sql);
	delete_option( 'comment_analyzer_options');
}

function total_comment_analyzer_count($screen) {
	if ( $screen->id != 'edit-comments' )
        return;
	add_filter( 'comment_status_links', 'comment_status_links_with_analyzer_values' );
}

function comment_status_links_with_analyzer_values($status_links) {
	if (is_admin()){
		$commentAnalyzerOptions = get_option('comment_analyzer_options');
		if ($commentAnalyzerOptions['admin']) {
			$comment_status = isset( $_REQUEST['comment_status'] ) ? $_REQUEST['comment_status'] : 'all';
			if ( !in_array( $comment_status, array( 'all', 'moderated', 'approved', 'spam', 'trash' ) ) )
				$comment_status = 'all';
			$post_id		= ($_REQUEST['p']) ? $_REQUEST['p'] : '';
			$search			= ($_REQUEST['s']) ? $_REQUEST['s'] : '';
			$commentType	= ($_REQUEST['comment_type']) ? $_REQUEST['comment_type'] : '';
			$status_map 	= array(
								'moderated' => 'hold',
								'approved' => 'approve',
								'all' => '',);
			$arg		= array('status' => isset( $status_map[$comment_status] ) ? $status_map[$comment_status] : $comment_status,
								'post_id' => $post_id,
								'search' => $search,
								'type' => $commentType,);
			$comments 	= get_comments( $arg );
			$neutral	= $good = $bad = 0;
			foreach($comments as $sentiment)
			{
				if($sentiment->comment_sentiment_value == 'good'){
					$good	+=1;
				}else if ($sentiment->comment_sentiment_value == 'bad'){
					$bad +=1;
				}else if ($sentiment->comment_sentiment_value == 'neutral'){
					$neutral +=1;
				}
			}
			$status_links['sentiment']	= '&nbsp;&nbsp;&nbsp;<a href="javascript:void(0);" style="cursor:default;"><img src="'.plugins_url('images/bad.png', __FILE__ ).'" title="Bad Comments" align="absmiddle"><span class="count">&nbsp;(<span class="bad-count">'.$bad.'</span>)</span></a>
			<a href="javascript:void(0);" style="cursor:default;"><img src="'.plugins_url('images/neutral.png', __FILE__ ).'" title="Neutral Comments" align="absmiddle"><span class="count">&nbsp;(<span class="neutral-count">'.$neutral.'</span>)</span></a>
			<a href="javascript:void(0);" style="cursor:default;"><img src="'.plugins_url('images/good.png', __FILE__ ).'"  title="Good Comments" align="absmiddle"><span class="count">&nbsp;(<span class="good-count">'.$good.'</span>)</span></a>
			';
		}
	}
	return $status_links;
}
function add_comment_analyzer_settings_link($links, $file){
	if ( plugin_basename( __FILE__ ) == $file ) {
		$settings_link = '<a href="' . admin_url( 'admin.php?page=comment-analyzer' ) . '">' . __( 'Settings', 'comment-analyzer' ) . '</a>';
		array_push( $links, $settings_link );
	}
	return $links;
}


?>