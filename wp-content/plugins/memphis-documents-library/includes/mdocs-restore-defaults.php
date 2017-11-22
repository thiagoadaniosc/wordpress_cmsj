<?php
function mdocs_restore_defaults() {
	mdocs_list_header();
	?>
	<div class="panel panel-primary">
		<div class="panel-heading">
			<h3 class="panel-title"><?php _e('Restore Memphis Document Library\'s to Defaults','memphis-documents-library'); ?></h3>
		</div>
		<div class="panel-body">
			<p><?php _e('This will return Memphis Documents Library to its default install state.  This means that all you files, post, and categories will be remove and all setting will return to their default state. <b>Please backup your files before continuing.</b>','memphis-documents-library'); ?></p>
			<div class="mdocs-clear-both"></div>
			<br>
			<form enctype="multipart/form-data" method="post" action="#" class="mdocs-setting-form" id="mdocs-retore-defaults-form">
				<input type="hidden" name="mdocs-restore-default" value="clean-up" />
				<?php wp_nonce_field( 'mdocs-restore-defaults' ); ?>
				<input type="button" class="btn btn-primary" onclick="mdocs_restore_default()" value="<?php _e('Restore To Default','memphis-documents-library') ?>" />
			</form>
		</div>
	</div>
	<?php
}
function mdocs_restore_default() {
	if(isset($_POST['type']) && $_POST['type'] == 'restore') {
		$blog_id = intval($_POST['blog_id']);
		if ( is_main_site($blog_id) ) mdocs_single_site_remove();
		else mdocs_single_site_remove($blog_id);
	} else { 
		if (is_multisite()) {
			 mdocs_multi_site_remove();
		} else {
			mdocs_single_site_remove();
		}
	}
}

function mdocs_multi_site_remove() {
	global $wpdb;
	$blogs = $wpdb->get_results("SELECT blog_id FROM {$wpdb->blogs}", ARRAY_A);
	if ($blogs) {
		$init_blog = true;
		foreach($blogs as $blog) {
			switch_to_blog($blog['blog_id']);
			$upload_dir = wp_upload_dir();
			$mdocs_list = get_option('mdocs-list');
			if(is_array($mdocs_list)) {
				foreach($mdocs_list as $the_doc) {
					wp_delete_attachment( intval($the_doc['id']), true );
					wp_delete_post( intval($the_doc['parent']), true );
				}
			}
			if($init_blog) $results = $wpdb->get_results( 'SELECT * FROM wp_options WHERE option_name LIKE "mdocs%" ', ARRAY_A );
			else $results = $wpdb->get_results( 'SELECT * FROM wp_'.$blog['blog_id'].'_options WHERE option_name LIKE "mdocs%" ', ARRAY_A );
			foreach($results as $result) delete_option($result['option_name']);
			$files = glob($upload_dir['basedir'].'/mdocs/*'); 
			foreach($files as $file) if(is_file($file)) unlink($file);
			$files = glob($upload_dir['basedir'].'/mdocs/.*'); 
			foreach($files as $file) if(is_file($file)) unlink($file);
			if(is_dir($upload_dir['basedir'].'/mdocs/')) rmdir($upload_dir['basedir'].'/mdocs/');
			$query = new WP_Query('pagename=mdocuments-library');
			wp_delete_post( $query->post->ID, true );
			$init_blog = false;
		}
		restore_current_blog();
	}
}
function mdocs_single_site_remove($blog_id=null) {
	global $wpdb;
	$upload_dir = wp_upload_dir();
	$mdocs_list = get_option('mdocs-list');
	if(is_array($mdocs_list)) {
		foreach($mdocs_list as $the_doc) {
			wp_delete_attachment( intval($the_doc['id']), true );
			wp_delete_post( intval($the_doc['parent']), true );
		}
	}
	if($blog_id == null) $results = $wpdb->get_results( 'SELECT * FROM wp_options WHERE option_name LIKE "mdocs%" ', ARRAY_A );
	else $results = $wpdb->get_results( 'SELECT * FROM wp_'.$blog_id.'_options WHERE option_name LIKE "mdocs%" ', ARRAY_A ); 
	foreach($results as $result) delete_option($result['option_name']);
	$files = glob($upload_dir['basedir'].'/mdocs/*'); 
	foreach($files as $file) if(is_file($file)) unlink($file);
	if(is_dir($upload_dir['basedir'].'/mdocs/')) rmdir($upload_dir['basedir'].'/mdocs/');
	$query = new WP_Query('pagename=mdocuments-library');
	wp_delete_post( $query->post->ID, true );
}

?>
