<?php
/*
Plugin Name: PDF to Post
Author: Kelsey Barmettler
Description: A small tool to automatically create a new post for each PDF uploaded to the Media library
*/

// Exit if accessed directly
if (!defined('ABSPATH')) exit;

// Add link to use this tool in its plugin description straight from Plugins page
function p2p_plugin_tool_page($links) {
	$settings_link = '<a href="' . admin_url('tools.php?page=pdf-to-post') . '">' . __('Uploader') . '</a>';
	array_push($links, $settings_link);
	return $links;
}
$plugin = plugin_basename(__FILE__);
add_filter("plugin_action_links_$plugin", 'p2p_plugin_tool_page');

function pdf_to_post_menu() {
	add_management_page(
		'PDF to Post',
		'PDF to Post',
		'manage_options',
		'pdf-to-post',
		'pdf_to_post_page',
		'dashicons-media-document'
	);
}
add_action('admin_menu', 'pdf_to_post_menu');

function pdf_to_post_page() {
?>
	<div class="wrap">
		<h1>PDF to Post</h1>
		<form method="post" enctype="multipart/form-data">
			<p>
				<label for="pdf-files">Select PDF files:</label>
				<input type="file" id="pdf-files" name="pdf-files[]" multiple="multiple" accept="application/pdf" />
			</p>
	
			<p>
				<input type="submit" value="Upload PDF Files" />
			</p>
		</form>
	</div>
<?php
}
// Handle file uploads
function p2p_handle_file_uploads() {
	if ( ! isset( $_FILES['pdf-files'] ) ) {
	return;
	}
	// Loop through each uploaded file
	foreach ( $_FILES['pdf-files']['name'] as $key => $name ) {
		// Skip empty uploads
		if ( empty( $name ) ) {
			continue;
		}

		$file = array(
			'name'     => $name,
			'type'     => $_FILES['pdf-files']['type'][$key],
			'tmp_name' => $_FILES['pdf-files']['tmp_name'][$key],
			'error'    => $_FILES['pdf-files']['error'][$key],
			'size'     => $_FILES['pdf-files']['size'][$key]
		);

		// Use WordPress' built-in file handling functions to upload the file
		$id = media_handle_sideload( $file, 0 );

		if ( ! is_wp_error( $id ) ) {
			// Add file to the media library
			wp_update_attachment_metadata( $id, wp_generate_attachment_metadata( $id, get_attached_file( $id ) ) );

			// Prepare the metadata!
			$filename = $file['name'];
			$post_title = str_replace('.pdf', '', $filename);
			$media_url = wp_get_attachment_url($id);

			// Prepare the post
			$pdf_post = array(
				'post_title'    => $post_title,
				'post_content'  => '<!-- wp:paragraph --><p class="pdf-download-wrapper"><a href="' . $media_url . '" class="pdf-download">' . $post_title . '</a></p><!-- /wp:paragraph -->',
				'post_status'   => 'publish',
				'post_author'   => 1,
				'post_type'     => 'post',
			);

			// Insert the post into the database
			wp_insert_post($pdf_post);

			return $file;
		}
}
}
add_action( 'admin_init', 'p2p_handle_file_uploads');