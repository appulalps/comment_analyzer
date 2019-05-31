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
											'apiurl' => '',
											);
			$commentAnalyzerOptions 		= get_option($this->adminOptionsName);
			if (!empty($commentAnalyzerOptions)) {
				foreach ($commentAnalyzerOptions as $key => $option)
					$commentAnalyzerAdminOptions[$key] = $option;
			}
			update_option($this->adminOptionsName, $commentAnalyzerAdminOptions);
			return $commentAnalyzerAdminOptions;
		}
	}
}