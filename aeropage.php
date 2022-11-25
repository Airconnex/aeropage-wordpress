<?php
/**
 * Plugin Name: Aeropage Sync for Airtable
 * Plugin URI: https://tools.aeropage.io/api-connector/
 * Description: Airtable to Wordpress Custom Post Type Sync Plugin
 * Version: 1.0.2
 * Author: Aeropage
 * Author URI: https://tools.aeropage.io/
 * License: GPL2
 * Requires PHP: 7.0.0
*/

//Add the cron job to the list of cron jobs upon activation of the function
//Cron job has an hourly schedule
register_activation_hook( __FILE__, "aero_plugin_activate" );
function aero_plugin_activate()
{
  if (!wp_next_scheduled ( "aero_hourly_sync" )) {
    wp_schedule_event(time(), "hourly", "aero_hourly_sync");
  }
}

//Remove the cron job from the list upon deactivation of the funciton
register_deactivation_hook( __FILE__, "aero_plugin_deactivate" );
function aero_plugin_deactivate() 
{
  wp_clear_scheduled_hook( "aero_hourly_sync" );
}

//Function that runs hourly
add_action("aero_hourly_sync", "aero_hourly_sync");
add_action("wp_ajax_testCronFunction", "aero_hourly_sync");
function aero_hourly_sync()
{
  try{
    //Get the posts where the auto sync is enabled
    $aeroPosts = get_posts([
      'meta_key' => 'aero_auto_sync',
      'meta_value' => 1,
      'post_type' => 'aero-template', 
      'post_status' => 'private',
      'numberposts' => -1
    ]);

    //Loop through the post
    foreach ($aeroPosts as $post)
    {
      //Get the token
      $token = get_post_meta($post->ID, "aero_token",true);
      //Check if there are new/modified records
      $response = aeropageModCheckApiCall($token);
      
      //If there's an error, we skip
      if($response["status"] !== "success") continue;

      //if there are new/modified records, we sync it
      if($response["has_new_records"] == 1){
        aeropageSyncPosts($post->ID);
      }
    }
    
  }catch(Exception $e){
    die(json_encode(
      array(
        "status" => "error",
        "message" => $e->getMessage()
      )
    ));
  }
}

add_action('admin_menu', 'aeropage_plugin_menu');
 
function aeropage_plugin_menu(){
  add_menu_page( 
    'Aeropage Sync for Airtable', 
    'Aeropage', 
    'manage_options', 
    'aeropage' , 
    'aeroplugin_admin_page', 
    plugin_dir_url( __FILE__ ) . 'assets/aeropage-icon-white-20px.svg', 
    61 
  );
}

/**
 * Init Admin Page.
 *
 * @return void
 */
function aeroplugin_admin_page() {
  require_once plugin_dir_path( __FILE__ ) . 'templates/app.php';
  
  //aeropageList();
  
}
add_action( 'admin_enqueue_scripts', 'aeroplugin_admin_enqueue_scripts' );

/**
 * Enqueue scripts and styles.
 *
 * @return void
 */
function aeroplugin_admin_enqueue_scripts() {
  wp_enqueue_style( 'aeroplugin-style', plugin_dir_url( __FILE__ ) . 'build/index.css' );
  wp_enqueue_script( 'aeroplugin-script', plugin_dir_url( __FILE__ ) . 'build/index.js', array( 'wp-element' ), date("h:i:s"), true );
  wp_add_inline_script( 'aeroplugin-script', 'const MYSCRIPT = ' . json_encode( array(
      'ajaxUrl' => admin_url( 'admin-ajax.php' ),
      'plugin_admin_path' => parse_url(admin_url())["path"],
      'plugin_name' => "aeropage" //This is the name of the plugin.
  ) ), 'before' );
}

add_action("wp_ajax_aeropageList", "aeropageList");
function aeropageList()
{

  $aeroPosts = get_posts(['post_type' => 'aero-template','post_status' => 'private','numberposts' => -1]);
	
	foreach ($aeroPosts as $post)
	{
    $post->sync_status = get_post_meta($post->ID, "aero_sync_status",true);
    $post->sync_time = get_post_meta($post->ID, "aero_sync_time",true);
    $post->sync_message = get_post_meta($post->ID, "aero_sync_message",true);
	}
	
  // this is for react...

  header('Content-Type: application/json');
  die(json_encode($aeroPosts));
}



add_action( 'wp_ajax_aeropageEditorMeta', 'aeropageEditorMeta');
//Gets the aero page token when in the edit post
function aeropageEditorMeta(){
  $pid = intval($_POST["id"]);
  $token = get_post_meta($pid, "aero_token");
  $status = get_post_meta($pid, "aero_sync_status");
  $sync_time = get_post_meta($pid, "aero_sync_time");
  $auto_sync = get_post_meta($pid, "aero_auto_sync");
  die(json_encode(array("token" => $token,"status" => $status, "sync_time" => $sync_time, "auto_sync" => $auto_sync)));
}


// make sure all the custom post types are registered.

add_action( 'init', 'aeroRegisterTypes' );

function aeroRegisterTypes()
{

  try{
    $flush = null;

		$aeroPosts = get_posts(['post_type' => 'aero-template','post_status' => 'private','numberposts' => -1]);

		foreach ($aeroPosts as $template)
		{

		$title = $template->post_title;

		$slug = $template->post_name; // eg Headphones

		if (!post_type_exists($slug) && $slug)
		{

    // echo "SLUG: ";
    // echo $slug;
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
  }catch(Exception $e){
    echo esc_attr($e->getMessage());
  }
}



add_action("wp_ajax_aeropageEdit", "aeropageEdit");
function aeropageEdit() // called by ajax, adds the cpt
{
  $post_id = null;

  if($_POST['id'])
  {
    $post_id = intval($_POST['id']);
  }

  // can be passed an id (edit) or empty to create new
  // wordpress will automatically increment the slug if its already used.
  $template_post = array(
    'ID' => $post_id,
    'post_title' => sanitize_text_field($_POST['title']),
    'post_name' => sanitize_text_field($_POST['slug']),
    'post_excerpt'=> sanitize_text_field($_POST['dynamic']),
    'post_type' => 'aero-template',
    'post_status' => 'private'
  );

  $id = wp_insert_post($template_post);

  if ($id)
  {
    $auto_sync = false;
    
    if($_POST['auto_sync'] === "true"){
      $auto_sync = true;
    }

    update_post_meta ($id,'aero_token', sanitize_text_field($_POST['token']));
    update_post_meta ($id,'aero_auto_sync', $auto_sync);
    aeropageSyncPosts($id);
  }

  die(json_encode(array("status" => "success", "post_id" => $id)));
	
}

// function aeropageTokenCheck()
// {
//   $token = $_POST["token"];
//   aeropageTokenApiCall($token);
// }

add_action("wp_ajax_aeropageDeletePost", "aeropageDeletePost");
function aeropageDeletePost() 
{

  global $wpdb;

  $post_id = null;

  if($_POST['id'])
  {
    $post_id = intval($_POST['id']);
  }

  $parent = get_post($post_id);
  $slug = $parent->post_name;

  //Delete all the posts for that post type
  $wpdb->query($wpdb->prepare(
    "
    DELETE a,b,c
    FROM wp_posts a
    LEFT JOIN wp_term_relationships b
        ON (a.ID = b.object_id)
    LEFT JOIN wp_postmeta c
        ON (a.ID = c.post_id)
    WHERE a.post_type = %s;
    "
  , $slug));

  // Unregister the post type first
  unregister_post_type($slug);

  // Remove the post
  wp_delete_post($post_id, true);

  die(json_encode(array("status" => "success"))); 
}

add_action("wp_ajax_aeropageSyncPosts", "aeropageSyncPosts");
function aeropageSyncPosts($parentId)
{

  if(!$parentId)
  {
    $isAjax = true;
    $parentId = intval($_POST["id"]);
  }

  if(!$parentId)
  {
    die(json_encode(array("status" => "error", "message" => "No parent ID was passed.")));
  }
 

  global $wpdb;

  $parent = get_post($parentId);

  $token = get_post_meta($parentId,'aero_token',true);

  $apiData = aeropageTokenApiCall($token);

  $response = array();

  if ($apiData['status']['type'] == 'success' and $apiData['records'])
  {
	$response['status'] = 'success';
	update_post_meta ($parentId,'aero_sync_status','success');
  $sync_time = time();
	update_post_meta ($parentId,'aero_sync_time', $sync_time);
  // trash posts 

  $trash = "
    UPDATE $wpdb->posts p
        INNER JOIN $wpdb->postmeta pm ON (p.ID = pm.post_id AND pm.meta_key = '_aero_cpt') 
    SET p.post_status = 'trash' 
    WHERE pm.meta_value = %d";
  $results = $wpdb->get_results($wpdb->prepare($trash, $parentId));
      
      
  foreach ($apiData['records'] as $record)
  {

  $record_id = sanitize_text_field($record['id']);
  $record_name = sanitize_text_field($record['name']); 
  $record_slug = sanitize_text_field($record['slug']); 
  
  $post_type = $parent->post_name;
  
  $dynamic = $parent->post_excerpt; //record_id or name
  
  if ($dynamic !== 'name')
  {
  $record_slug = $record_id;
  }
  
  $field_names = array_column($apiData['fields'], 'name'); // get just the types

  // find if theres a trashed post with this record id already

  $existing = get_posts([
    'post_type'=> $post_type,
    'post_status' => 'trash',
    'numberposts' => 1,
    'meta_key' => '_aero_id', 
    'meta_value' => $record_id 
  ]);

  //If there's a post, use that post ID otherwise just left it empty
  if ($existing){
    $existing_id = $existing[0]->ID;
  }else{
    $existing_id = "";
  }

  $record_post = array(
      'ID' => $existing_id,
      'post_title' => $record_name,
      'post_name' => $record_slug,
      'post_parent' => '',
      'post_type' => $post_type,
      'post_status' => 'publish'
  );
    
  $record_post_id = wp_insert_post($record_post);

  update_post_meta ($record_post_id, '_aero_cpt', $parentId);
  update_post_meta ($record_post_id, '_aero_id', $record_id);


  if ($existing)
  {
  $response['message'] .= "<br>record $record_id : $record_name already exists as $record_post_id and is being updated.";
  }
  else
  {
  $response['message'] .= "<br>record $record_id : $record_name has been created as $record_post_id.";
  }



  foreach ($record['fields'] as $key=>$value)
  {

  $field_index = array_search($key, $field_names);

  $type = $apiData['fields'][$field_index]['type'];

  //We sanitize the URL just to be sure...
  if ($type == 'attachment_img')
  {
  $value = sanitize_url($value[0]['thumbnails']['large']['url']);
  }elseif ($type == 'attachment_doc')
  {
  $value = sanitize_url($value[0]['url']);
  }else
  {
  $value = sanitize_text_field($value);
  }

  update_post_meta ($record_post_id, "aero_$key", $value);

  $response['message'] .= "<br> ---> field $key of type $type has been added.";

  }
  // end foreach field

  }
  // end foreach record
  update_post_meta ($parentId,'aero_sync_message', $response['message']);
  }
  else // some problem with api
  {
  $response['status'] = 'error';
  update_post_meta ($parentId,'aero_sync_status','error');
  $message = sanitize_text_field($apiData['status']['message']);
  echo "SYNC MESSAGE: ".$apiData['status']['message'];
  update_post_meta ($parentId,'aero_sync_message',$message);
  $response['message'] = $message;
  }

  //If doing AJAX

  if($isAjax)
  {
    $response["sync_time"] = $sync_time;
    die(json_encode($response));
  }
  else
  {
  return $response;
  }

}
// end function


function aeropageTokenApiCall($token)
{
	$api_url = "https://tools.aeropage.io/api/token/$token/";
  $result = json_decode(wp_remote_retrieve_body(wp_remote_get($api_url)), true);
  return $result;
}

function aeropageModCheckApiCall($token)
{
	$api_url = "https://tools.aeropage.io/api/modcheck/$token/";
  $result = json_decode(wp_remote_retrieve_body(wp_remote_get($api_url)), true);
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