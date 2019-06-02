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
							  <th scope="row">Show sentimental icon on front end </th>
							  <td><label for="send_password"><input type="checkbox" name="frontend" <?php checked('1', $commentAnalyzerOptions['frontend']); ?> value="1" ></label></td>
							 </tr>
							 <tr class="form-field form-required">
							  <th scope="row">Show sentimental icon on admin side </th>
							  <td><input type="checkbox" name="admin" <?php checked('1', $commentAnalyzerOptions['admin']); ?> value="1" ></td>
							 </tr>
							 <tr class="form-field form-required">
							  <th scope="row">Meaningcloud.com API key <span class="description">(required)</span></th>
							  <td><input type="text" name="apikey" class="input_kwd"  value="<?php echo esc_attr($commentAnalyzerOptions['apikey']); ?>" required />
								<br>  <p class="description">Please request for api key <a href="https://www.meaningcloud.com/developer/login/" target="_blank">here</a></p></td>
							 </tr>
							 <tr class="form-field form-required">
							  <th scope="row">Meaningcloud.com API Url <span class="description">(required)</span></th>
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
function save_comment_analyzer_value() {

			global $wpdb;
			$comments		= get_comments();
			$comment		= (array) $comments[0];
			$lastComment	= $comment['comment_content'];
			$lastCommentID	= $comment['comment_ID'];
			$postID			= $comment['comment_post_ID'];
			$posts			= get_post($postID);
			$postDetails	= array ($posts);
			$commentAnalyzerOptions = get_option('comment_analyzer_options');
	
			$curl = curl_init();

			curl_setopt_array($curl, array(
			  CURLOPT_URL => $commentAnalyzerOptions['apiurl'],
			  CURLOPT_RETURNTRANSFER => true,
			  CURLOPT_ENCODING => "",
			  CURLOPT_MAXREDIRS => 10,
			  CURLOPT_TIMEOUT => 30,
			  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
			  CURLOPT_CUSTOMREQUEST => "POST",
			  CURLOPT_POSTFIELDS => "key=".$commentAnalyzerOptions['apikey']."&lang=en&txt=".$lastComment,
			  CURLOPT_HTTPHEADER => array(
			    "content-type: application/x-www-form-urlencoded"
			  ),
			));
			$response 	= curl_exec($curl);
			$err 		= curl_error($curl);

			curl_close($curl);

			if ($err) {
			 	$apiSentimentResult	= '';
			} else {
			  	$data = json_decode($response); 
			  	
			  	if(isset($data->score_tag)){
			  		$apiSentimentResult	= $data->score_tag;
			  	} else {
					$apiSentimentResult	= '';
			  	}
			}
			if ( $apiSentimentResult == '' )
				$sentiments	= '';
			else if( $apiSentimentResult == 'P+' || $apiSentimentResult == 'P' )
				$sentiments	= 'good';
			else if ( $apiSentimentResult == 'NEU' || $apiSentimentResult == 'NONE' )
				$sentiments	= 'neutral';
			else if ( $apiSentimentResult == 'N' || $apiSentimentResult == 'N+' )
				$sentiments	= 'bad';

			$tableName		= $wpdb->prefix . "comments";
			$data			= array ('comment_analyzer_value' => $sentiments);
			$where			= array ('comment_ID' => $lastCommentID);
			$wpdb->update( $tableName, $data, $where, $format = null, $where_format = null );
		}

		function update_comment_analyzer_value($comment_ID) {
			global $wpdb;
			$comment		= get_comment($comment_ID);
			$lastComment	= $comment->comment_content;
			$lastCommentID	= $comment->comment_ID;
			$postID			= $comment->comment_post_ID;
			$posts			= get_post($postID);
			$postDetails	= array ($posts);
			$commentAnalyzerOptions = get_option('comment_analyzer_options');
			$curl = curl_init();

			curl_setopt_array($curl, array(
			  CURLOPT_URL => $commentAnalyzerOptions['apiurl'],
			  CURLOPT_RETURNTRANSFER => true,
			  CURLOPT_ENCODING => "",
			  CURLOPT_MAXREDIRS => 10,
			  CURLOPT_TIMEOUT => 30,
			  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
			  CURLOPT_CUSTOMREQUEST => "POST",
			  CURLOPT_POSTFIELDS => "key=".$commentAnalyzerOptions['apikey']."&lang=en&txt=".$lastComment,
			  CURLOPT_HTTPHEADER => array(
			    "content-type: application/x-www-form-urlencoded"
			  ),
			));
			$response 	= curl_exec($curl);
			$err 		= curl_error($curl);

			curl_close($curl);

			if ($err) {
			 	$apiSentimentResult	= '';
			} else {
			  	$data = json_decode($response); 
			  	
			  	if(isset($data->score_tag)){
			  		$apiSentimentResult	= $data->score_tag;
			  	} else {
					$apiSentimentResult	= '';
			  	}
			}
			if ( $apiSentimentResult == '' )
				$sentiments	= '';
			else if( $apiSentimentResult == 'P+' || $apiSentimentResult == 'P' )
				$sentiments	= 'good';
			else if ( $apiSentimentResult == 'NEU' || $apiSentimentResult == 'NONE' )
				$sentiments	= 'neutral';
			else if ( $apiSentimentResult == 'N' || $apiSentimentResult == 'N+' )
				$sentiments	= 'bad';

			$tableName		= $wpdb->prefix . "comments";
			$data			= array ('comment_analyzer_value' => $sentiments);
			$where			= array ('comment_ID' => $lastCommentID);
			$wpdb->update( $tableName, $data, $where, $format = null, $where_format = null );
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