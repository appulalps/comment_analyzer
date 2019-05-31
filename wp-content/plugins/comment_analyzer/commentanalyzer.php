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

add_action( 'admin_menu', 'comment_analyzer_menu' );
add_action( 'comment_post','save_comment_analizer_value' );
add_action( 'edit_comment','update_comment_analyzer_value' );
add_action( 'current_screen','total_comment_analyzer_count' );

//add_action( 'comment_analyzer_cron', 'find_all_comment_analyzer_value' );
//add_filter( 'comment_text','add_comment_analyzer_images_with_comments', 10, 2);
//add_filter( 'plugin_action_links', 'add_comment_analyzer_settings_link', 10, 2 );

if (class_exists("CommentAnalizerAdmin")) {
	$ObjCommentAnalyzerPlugin = new CommentAnalizerAdmin();
}

function activate_comment_analyzer(){
	global $wpdb;
	$tableName = $wpdb->prefix . "comments";
	$sql = "ALTER TABLE ".$tableName." ADD comment_analyzer_value varchar(20)";
	$wpdb->query($sql);
}

function deactivate_comment_analyzer() {
	$sentimentOptions = get_option('comment_analyzer_options');
	update_option( 'comment_analyzer_options', $commentAnalyzerOptions );
}

function uninstall_comment_analyzer() {
	global $wpdb;
	$tableName = $wpdb->prefix . "comments";
	$sql = "ALTER TABLE ".$tableName." DROP comment_analyzer_value ";
	$wpdb->query($sql);
	delete_option( 'comment_analyzer_options');
}

?>