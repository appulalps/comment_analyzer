<?php
if (!class_exists("CommentAnalyserAdmin")) {
	class CommentAnalyzerAdmin {
		var $adminOptionsName = "comment_analyzer_options";

		function __construct() { 
			$this->init();
		}

		function init() {
			$this->get_admin_options();
			
		}

		function get_admin_options() {
			$commentAnalyzerAdminOptions 	= array('admin' => '',
													'frontend' => '',
													'apikey' => '',
													'apiurl' => ''
													);
			$commentAnalyzerOptions 		= get_option($this->adminOptionsName);
			if (!empty($commentAnalyzerOptions)) {
				foreach ($commentAnalyzerOptions as $key => $option)
					$commentAnalyzerAdminOptions[$key] = $option;
			}
			update_option($this->adminOptionsName, $commentAnalyzerAdminOptions);
			return $commentAnalyzerAdminOptions;
		}

		function printAdminPage() {
			$commentAnalyzerOptions = $this->get_admin_options();
			if (isset($_POST['update_commentAnalyzerSettings'])) {
				$commentAnalyzerOptions['admin'] 		= apply_filters('keyword_save_pre', $_POST['admin']);
				$commentAnalyzerOptions['frontend'] 	= apply_filters('keyword_save_pre', $_POST['frontend']);
				$commentAnalyzerOptions['apikey'] 		= apply_filters('keyword_save_pre', $_POST['apikey']);
				$commentAnalyzerOptions['apiurl'] 		= apply_filters('keyword_save_pre', $_POST['apiurl']);
				update_option($this->adminOptionsName, $commentAnalyzerOptions);
				?>
				<div id="message" class="updated settings-error notice is-dismissible">
				 <p><strong><?php _e('Settings Updated.') ?></strong></p>
				</div>
				<?php
				} 
				?>
				<style>
					.wrap .input_kwd{width:300px;}
					.analyzer-settings th {width:380px;}
					.analyzer-settings input {width:auto;}
				</style>
				<div class="wrap">
				
					<div style="float:left;width:100%">
					<form method="post" action="<?php echo admin_url('admin.php?page=comment-analyzer')?>">
						<h2><img src="<?php echo plugins_url('images/settings.png', __FILE__ )?>" align="absbottom" />&nbsp;Comment Analyzer</h2>
						<h3>Comment Analyzer Settings</h3>
						<table class="form-table analyzer-settings">
							<tbody>
							 <tr class="form-field form-required">
							  <th scope="row">Show analyzer icon on front end </th>
							  <td><label for="send_password"><input type="checkbox" name="frontend" <?php checked('1', $commentAnalyzerOptions['frontend']); ?> value="1" ></label></td>
							 </tr>
							 <tr class="form-field form-required">
							  <th scope="row">Show comment analyzer icon on admin side </th>
							  <td><input type="checkbox" name="admin" <?php checked('1', $commentAnalyzerOptions['admin']); ?> value="1" ></td>
							 </tr>
							 <tr class="form-field form-required">
							  <th scope="row">Meaningcloud.com API key <span class="description">(required)</span></th>
							  <td><input type="text" name="apikey" class="input_kwd"  value="<?php echo esc_attr($commentAnalyzerOptions['apikey']); ?>" required />
								<br>  <p class="description">Please request for api key <a href="https://www.meaningcloud.com/developer/login/" target="_blank">here</a></p></td>
							 </tr>
							 <tr class="form-field form-required">
							  <th scope="row">Sentimental analysis API Url <span class="description">(required)</span></th>
							  <td><input type="text" name="apiurl" class="input_kwd" value="<?php echo esc_attr($commentAnalyzerOptions['apiurl']); ?>" required />
								  <br> <p class="description">Sample Url - https://api.meaningcloud.com/sentiment-2.1</p></td>
							 </tr>
							</tbody>
						</table>
						<p class="submit">
						<input type="submit" name="update_commentAnalyzerSettings" value="Update Settings" class="button button-primary"></p>
					</form>
				</div>
			</div>
		<?php
	  }

	  function save_comment_sentimental_value() {
			global $wpdb;
			$comments		= get_comments();
			$comment		= (array) $comments[0];
			$lastComment	= $comment['comment_content'];
			$lastCommentID	= $comment['comment_ID'];
			$postID			= $comment['comment_post_ID'];
			$posts			= get_post($postID);
			$postDetails	= array ($posts);
			$sentimentOptions = get_option('sentiment_analysis_options');
			$ipAddress		= $_SERVER['REMOTE_ADDR'];
			$xml			= '<?xml version="1.0"?>
								 <root>
								 <apikey>'.$sentimentOptions['apikey'].'</apikey>
								 <QueryItems>
								  <query>
									<id>1</id>
									<brandname><![CDATA['.$postDetails[0]->post_title.']]></brandname>
									<ipaddress><![CDATA['.$ipAddress.']]></ipaddress>
									<paragraph><![CDATA['.$lastComment.']]></paragraph>
								  </query>
								</QueryItems>
								</root>';
			$params 		= array('searchXML' => $xml);
			$client 		= new SoapClient($sentimentOptions['apiurl']."?wsdl");
			$response 		= $client->GetScore($params);
			$apiResult		= (array) $response;
			$xmlData		= simplexml_load_string($apiResult['GetScoreResult']);
			$apiSentimentResult	= $xmlData->result;
			if ( $apiSentimentResult == 'Invalid API Key.' )
				$sentiments	= '';
			else if( $apiSentimentResult >= -0.25 && $apiSentimentResult <= 0.25 )
				$sentiments	= 'neutral';
			else if ( $apiSentimentResult < -0.25 )
				$sentiments	= 'bad';
			else if ( $apiSentimentResult > 0.25 )
				$sentiments	= 'good';
			$tableName		= $wpdb->prefix . "comments";
			$data			= array ('comment_sentiment_value' => $sentiments);
			$where			= array ('comment_ID' => $lastCommentID);
			$wpdb->update( $tableName, $data, $where, $format = null, $where_format = null );
		}
	}
}

function comment_analyzer_menu() {
	add_menu_page( 'Sentiment Analysis Settings', 'Analyzer', 'manage_options', 'comment-analyzer', 'display_comment_analyzer_options', plugins_url('images/menu.png', __FILE__));
	add_options_page( 'Sentiment Analysis Settings', 'Analyzer', 'manage_options', 'comment-analyzer', 'display_comment_analyzer_options' );
}

function display_comment_analyzer_options() {
	if ( !current_user_can( 'manage_options' ) ) {
		wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
	}
	global $ObjCommentAnalyzerPlugin;
	if (!isset($ObjCommentAnalyzerPlugin)) {
			return;
	}
	$ObjCommentAnalyzerPlugin->printAdminPage();
}

function add_comment_analyzer_images_with_comments($comment_text,$comment_ID) {
	$commentAnalyzerOptions 	= get_option('comment_analyzer_options');
	$comment			= get_comment($comment_ID);
	if (is_admin()){
		if ($commentAnalyzerOptions['admin']) {
			if ($comment->comment_analyzer_value == 'neutral')
				$commentAnalyzerImage = '<img src="'.plugins_url('images/neutral.png', __FILE__).'" title="Neutral">';
			else if ($comment->comment_analyzer_value == 'good')
				$commentAnalyzerImage = '<img src="'.plugins_url('images/good.png', __FILE__).'" title="Good">';
			else if ($comment->comment_analyzer_value == 'bad')
				$commentAnalyzerImage = '<img src="'.plugins_url('images/bad.png', __FILE__).'" title="Bad">';
			else
				$commentAnalyzerImage = '';
			return $commentAnalyzerImage.' '.$comment_text;
		}else{
			return $comment_text;
		}
	}
	else{
		if ($commentAnalyzerOptions['frontend']) {
			if ($comment->comment_analyzer_value == 'neutral')
				$commentAnalyzerImage = '<img src="'.plugins_url('images/neutral.png', __FILE__).'" title="Neutral">';
			else if ($comment->comment_analyzer_value == 'good')
				$commentAnalyzerImage = '<img src="'.plugins_url('images/good.png', __FILE__).'" title="Good">';
			else if ($comment->comment_analyzer_value == 'bad')
				$commentAnalyzerImage = '<img src="'.plugins_url('images/bad.png', __FILE__).'" title="Bad">';
			else
				$commentAnalyzerImage = '';
			return $commentAnalyzerImage.' '.$comment_text;
		}else{
			return $comment_text;
		}
	}
}