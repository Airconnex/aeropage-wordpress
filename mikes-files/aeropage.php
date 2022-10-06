<?php
/**
 * Plugin Name: Aeropage Sync for Airtable
 * Plugin URI: https://tools.aeropage.io/airwordpress/
 * Description: Airtable to Wordpress Custom Post Type Sync Plugin
 * Version: 1.0
 * Author: Mike San Marzano
 * Author URI: https://tools.aeropage.io/
 * License: GPL2
*/



add_action('admin_menu', 'aeropage_plugin_menu');
 
function aeropage_plugin_menu(){
add_menu_page( 'Aeropage Sync for Airtable', 'Aeropage', 'manage_options', 'aeropage' , 'aeropage_plugin_admin' );
}



function aeropage_plugin_admin()
{
global $wpdb;

$aero_posts = get_posts(['post_type' => 'aero-template','post_status' => 'private','numberposts' => -1]);

//var_dump($aero_posts);


$response = aeropageSyncPosts(71);

echo $response['message'];


//$aero_record_posts = get_posts(['post_type'=> 'aero-template','post_status' => 'private','numberposts' => 1,'meta_key' => 'aero_record']);


//var_dump($aero_record_posts);


}



function aeropageList()
{

$aeroPosts = get_posts(['post_type' => 'aero-template','post_status' => 'private','numberposts' => -1]);

// this is for react...

header('Content-Type: application/json');
echo json_encode($aeroPosts);

}




// make sure all the custom post types are registered.

add_action( 'init', 'aeroRegisterTypes' );

function aeroRegisterTypes()
{

$flush = null;

		$aeroPosts = get_posts(['post_type' => 'aero-template','post_status' => 'private','numberposts' => -1]);

		foreach ($aeroPosts as $template)
		{

		$title = $template->post_title;

		$slug = $template->post_name; // eg Headphones

		if (!post_type_exists($slug))
		{

		$flush = true;

		register_post_type( "$slug", //airconnex_templates
				array(
					"labels" => array(
						"name"=> _("$title"),
						"singular_name" => _("$title")
					),
					'hierarchical' => true,
					"has_archive" => false,
					"rewrite" => array( "slug" => "$slug" ), // my custom slug
					"supports" => array( "title","editor","thumbnail" ), // editor page settings
					"show_in_rest" => true, // gutenberg
					"description" => "$title",
					"public" => true, // All the relevant settings below inherit from this setting
					"publicly_queryable" => true, // When a parse_request() search is conducted, should it be included?
					"show_ui" => true, // Should the primary admin menu be displayed?
					"show_in_nav_menus" => true, // Should it show up in Appearance > Menus?
					"show_in_menu" => true, // This inherits from show_ui, and determines *where* it should be displayed in the admin
					"show_in_admin_bar" => true, // Should it show up in the toolbar when a user is logged in?
				)
				
			);

		}

		}

		if ($flush){flush_rewrite_rules();}

}




function aeropageEdit() // called by ajax, adds the cpt
{

// can be passed an id (edit) or empty to create new.

// wordpress will automatically increment the slug if its already used.

$template_post = array(
        'ID' => $_POST['id'],
        'post_title' => $_POST['title'],
        'post_name' => $_POST['slug'],
		'post_content'=> $_POST['token'],
        'post_excerpt'=> $_POST['dynamic'],
        'post_type' => 'aero-template',
        'post_status' => 'private'
    );

$id = wp_insert_post($template_post);

if ($id)
{
update_post_meta ($id,'aero_token',$_POST['token']);
}

return $id;
	
}
	


function aeropageSyncPosts($parentId)
{

global $wpdb;

$parent = get_post($parentId);

$token = get_post_meta($parentId,'_aero_token',true);

$apiData = aeropageTokenApiCall($token);

$response = array();

if ($apiData['status']['type'] == 'success' and $apiData['records'])
{


// trash posts 

$trash = "
		UPDATE $wpdb->posts p
        INNER JOIN $wpdb->postmeta pm ON (p.ID = pm.post_id AND pm.meta_key = '_aero_cpt') 
		SET p.post_status = 'trash' 
		WHERE pm.meta_value = '$parentId'"
		;
$results = $wpdb->get_results($trash);
		
		
foreach ($apiData['records'] as $record)
{

$record_id = $record['id'];
$record_name = $record['id']; //CHANGE THIS TO NAME LATER.
$post_type = $parent->post_name;
$field_names = array_column($apiData['fields'], 'name'); // get just the types

// find if theres a trashed post with this record id already

$existing = get_posts(['post_type'=> $post_type,'post_status' => 'trash','numberposts' => 1,'meta_key' => '_aero_id', 'meta_value' => $record_id ]);

if ($existing){$existing_id = $existing[0]->id;}

$record_post = array(
        'ID' => $existing_id,
        'post_title' => $record_name,
        'post_name' => $record_name,
		'post_parent' => '',
        'post_type' => $post_type,
        'post_status' => 'publish'
    );
	
$record_post_id = wp_insert_post($record_post);


update_post_meta ($record_post_id, '_aero_cpt', $parentId);
update_post_meta ($record_post_id, '_aero_id', $record_id);


if ($existing)
{
$response['message'] .= "<br>record $record_id already exists as $record_post_id and is being updated.";
}
else
{
$response['message'] .= "<br>record $record_id has been created as $record_post_id.";
}



foreach ($record['fields'] as $key=>$value)
{

$field_index = array_search($key, $field_names);

$type = $apiData['fields'][$field_index]['type'];


if ($type == 'attachment_img')
{
$value = $value[0]['thumbnails']['large']['url'];
}

if ($type == 'attachment_doc')
{
$value = $value[0]['url'];
}

update_post_meta ($record_post_id, "aero_$key", $value);

$response['message'] .= "<br> ---> field $key of type $type has been added.";

}
// end foreach field


}
// end foreach record

}
else // some problem with api
{
$response['message'] .= "There was an error";
}


return $response;


}
// end function





function aeropageTokenApiCall($token)
{

	$api_url = "https://tools.aeropage.io/api/token/$token/";

	$ch = curl_init($api_url);
	
    // ONLY FOR LOCAL / DEVELOPMENT!!!!
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	
	$headers = array("Content-Type: application/json");
	
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	
    $result = json_decode(curl_exec($ch), true);
	
    curl_close($ch);

    return $result;


}





  /* WOOCOMMERCE (FUTURE)
	$product = new WC_Product_Simple();
    $product->set_name( 'Photo: ' . get_the_title( $image_id ) );
    $product->set_status( 'publish' ); 
    $product->set_catalog_visibility( 'visible' );
    $product->set_price( 19.99 );
    $product->set_regular_price( 19.99 );
    $product->set_sold_individually( true );
    $product->set_image_id( $image_id );
    $product->set_downloadable( true );
    $product->set_virtual( true );      
   
 
	$src_img = wp_get_attachment_image_src( $image_id, 'full' );
    $file_url = reset( $src_img );
    $file_md5 = md5( $file_url );
	$download = new WC_Product_Download();
    $download->set_name( get_the_title( $image_id ) );
    $download->set_id( $file_md5 );
    $download->set_file( $file_url );
    $downloads[$file_md5] = $download;
    $product->set_downloads( $downloads );
	*/
	
	

