<?php
/**
 * Plugin Name: Aeropage Sync for Airtable
 * Plugin URI: https://tools.aeropage.io/api-connector/
 * Description: Airtable to Wordpress Custom Post Type Sync Plugin
 * Version: 1.2.3
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
  //Enqueue only in the plugin page.
  if(isset($_GET['page']) && $_GET['page'] === "aeropage"){
    wp_enqueue_style( 'aeroplugin-style', plugin_dir_url( __FILE__ ) . 'build/index.css', array(), '1.2.3' );
    wp_enqueue_script( 'aeroplugin-script', plugin_dir_url( __FILE__ ) . 'build/index.js', array( 'wp-element' ), date("h:i:s"), true );
    wp_add_inline_script( 'aeroplugin-script', 'const MYSCRIPT = ' . json_encode( array(
        'ajaxUrl' => admin_url( 'admin-ajax.php' ),
        'plugin_admin_path' => parse_url(admin_url())["path"],
        'plugin_name' => "aeropage" //This is the name of the plugin.
    ) ), 'before' );
  }
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
    $post->connection = get_post_meta($post->ID, "aero_connection", true);
	}
	
  // this is for react...

  header('Content-Type: application/json');
  die(json_encode($aeroPosts));
}

add_action( 'admin_bar_menu', 'aeroAddAdminBar', 100 );
function aeroAddAdminBar( $admin_bar ){
  global $post;

  if(!$post) return;

  $aeroCPT = get_post_meta($post->ID, '_aero_cpt', true);
  //If there's a value for _aero_cpt, we add this nav bar item
  //Will only show if user is an admin, we don't want it to show for 'members' only
  if($aeroCPT && current_user_can( 'manage_options' )){
    $admin_bar->add_menu( 
      array( 
        'id'=>'aero-sync-bar',
        'title'=>'
          <div
            style="display: flex;"
            id="aero-page-sync-container"
          >
            <svg
              style="margin-right: 2px;"
              xmlns="http://www.w3.org/2000/svg"
              width="14"
              height="14"
              viewBox="0 0 24 24"
              fill="none"
              stroke="#FFF"
              stroke-width="2"
              stroke-linecap="round"
              stroke-linejoin="round"
              class="feather feather-refresh-cw"
            >
              <polyline points="23 4 23 10 17 10"></polyline>
              <polyline points="1 20 1 14 7 14"></polyline>
              <path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"></path>
            </svg>&nbsp;Resync Aeropage
          </div>
        ',
        'href'=>'',
      ) 
    );
  }
}

add_action( 'wp_footer', 'aeroSyncScript' );
function aeroSyncScript() {
  global $post;
  $aeroCPT = get_post_meta($post->ID, '_aero_cpt', true);
  
  //If there's a value for aero_cpt, we add this script to the footer in the
  //actual wordpress site. This will only be shown if the user is an admin
  if($aeroCPT && current_user_can( 'manage_options' )){
  ?> 
    <script type="text/javascript">
      document.getElementById("aero-page-sync-container").onclick = function () {
        var ajaxurl = "<?php echo admin_url('admin-ajax.php'); ?>";
        var params = new URLSearchParams();
        var xhttp = new XMLHttpRequest();

        params.append("action", "aeropageSyncPosts");
        params.append("id", "<?php echo $aeroCPT; ?>");

        xhttp.open("POST", ajaxurl, false);
        xhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
        xhttp.send(params);

        location.reload();
      }
    </script>
  <?php
  }
}

add_action( 'wp_ajax_aeropageEditorMeta', 'aeropageEditorMeta');
//Gets the aero page token when in the edit post
function aeropageEditorMeta(){
  $pid = intval($_POST["id"]);
  $token = get_post_meta($pid, "aero_token");
  $status = get_post_meta($pid, "aero_sync_status");
  $sync_time = get_post_meta($pid, "aero_sync_time");
  $auto_sync = get_post_meta($pid, "aero_auto_sync");
  $record_post_status = get_post_meta($pid, "aero_post_status");
  die(json_encode(
    array(
      "token" => $token,
      "status" => $status, 
      "sync_time" => $sync_time, 
      "auto_sync" => $auto_sync,
      "post_status" => $record_post_status
    )
  ));
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
    update_post_meta ($id,'aero_connection', sanitize_text_field($_POST["app"])."/".sanitize_text_field($_POST["table"])."/".sanitize_text_field($_POST["view"]));
    update_post_meta ($id, 'aero_post_status', sanitize_text_field($_POST["post_status"]));
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

  $record_post_status = get_post_meta($parentId, 'aero_post_status', true);

  $apiData = aeropageTokenApiCall($token);

  $response = array();

  if ($apiData['status']['type'] == 'success' and $apiData['records'])
  {
	$response['status'] = 'success';
	update_post_meta ($parentId,'aero_sync_status','success');
  $sync_time = time();
	update_post_meta ($parentId,'aero_sync_time', $sync_time);
  // trash posts 
  
  
  // fields are indexed numerically - iterate to create an array of types, indexed by name
  
  $fieldsByName = array();
  
  foreach ($apiData['fields'] as $key=>$datafield)
  {
  $name = sanitize_text_field($datafield['name']);
  $type = sanitize_text_field($datafield['type']);
  $fieldsByName[$name] = $type;
  }
  
  update_post_meta ($parentId,'aero_sync_fields', $fieldsByName); // add to the parent cpt


  $trash = "
    UPDATE $wpdb->posts p
        INNER JOIN $wpdb->postmeta pm ON (p.ID = pm.post_id AND pm.meta_key = '_aero_cpt') 
    SET p.post_status = 'trash' 
    WHERE pm.meta_value = %d";
  $results = $wpdb->get_results($wpdb->prepare($trash, $parentId));
  $count = 1;
  
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
  
  $post_status = $record_post_status;

  //If there's a post, use that post ID otherwise just left it empty
  if ($existing){
    $existing_id = $existing[0]->ID;
    $post_status = "publish";
  }else{
    $existing_id = "";
  }
  
  
  if (strlen($record['post_title']) > 0)
  {
  $post_title = sanitize_text_field($record['post_title']);
  $post_title_msg = "<br>--> adding custom post_title as $post_title.";
  }
  else
  {
  $post_title = $record_name;
  $post_title_msg = "<br>--> no custom post title.";
  }
  
  if (strlen($record['post_excerpt']) > 0)
  {
  $post_excerpt = sanitize_text_field($record['post_excerpt']);
  $post_excerpt_msg = "<br>--> adding custom post_excerpt as $post_excerpt.";
  }
  else
  {
  $post_excerpt = $record_name;
  $post_excerpt_msg = "<br>--> no custom post excerpt.";
  }


  $record_post = array(
    'ID' => $existing_id,
    'post_title' => $post_title,
    'post_excerpt' => $post_excerpt,
    'post_name' => $record_slug,
    'post_parent' => '',
    'post_type' => $post_type,
    'post_status' => $post_status
  );
    
  $record_post_id = wp_insert_post($record_post);
  
  $count++;

  update_post_meta ($record_post_id, '_aero_cpt', $parentId);
  update_post_meta ($record_post_id, '_aero_id', $record_id);


  if ($existing)
  {
  $response['message'] .= "<br>record $record_id : $record_name already exists as $record_post_id and is being updated.".$post_title_msg.$post_excerpt_msg;
  }
  else
  {
  $response['message'] .= "<br>record $record_id : $record_name has been created as $record_post_id.".$post_title_msg.$post_excerpt_msg;
  }


 // featured image download
	if (strlen($record['post_image']) > 0)
  {
    $image_value = sanitize_url($record['post_image']);
    $thumbnail_id = get_post_meta( $record_post_id, '_thumbnail_id',true ); // check if this post already has thumbnails...

    if (!$thumbnail_id) // if we dont already have the thumb for this post
    {
      $response['message'] .= "<br>--> There is a post_image, but no thumbnail found. downloading now.";
      $thumbnail_id = aeropage_external_image($image_value,$record_post_id);

      //Set the attachment as featured image.
      delete_post_meta( $record_post_id, '_thumbnail_id' );
      add_post_meta( $record_post_id , '_thumbnail_id' , $thumbnail_id, true );

    }
    unset($thumbnail_id);
  }


  foreach ($record['fields'] as $key=>$value)
  {
  $type = $fieldsByName[$key]; // get the type

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
  // $api_url = "http://localhost:3002/api/token/$token";
  $result = json_decode(wp_remote_retrieve_body(wp_remote_get($api_url)), true);
  return $result;
}

function aeropageModCheckApiCall($token)
{
	$api_url = "https://tools.aeropage.io/api/modcheck/$token/";
  // $api_url = "http://localhost:3002/api/modcheck/$token";
  $result = json_decode(wp_remote_retrieve_body(wp_remote_get($api_url)), true);
  return $result;
}


function aeropage_external_image($ext_url,$parent)
{
  global $wpdb;
	$filename = "$parent-featured";
  //$ext_url -- the external url
  //$parent -- the parent post to attach to  
  $extension = pathinfo(parse_url($ext_url, PHP_URL_PATH), PATHINFO_EXTENSION);
  // new filename for local
  
  $upload_dir = wp_upload_dir();
  $upload_folder = $upload_dir['basedir'].'/aeropage/';
        
  if(!file_exists($upload_folder)) wp_mkdir_p($upload_folder);
    
  $ext_img = wp_remote_get( $ext_url ); // check the url to make sure its valid

  // We get the extension from content type header from the response
  if(!$extension){
    $content_type = $ext_img['headers']['content-type'];
    $exploded = explode("/", $content_type);
    $extension = $exploded[1]; //Contains the extension i.e. "jpeg, png, etc"
  }

  $image_filename = sanitize_file_name( $filename.'.'.$extension);

  if (! is_wp_error( $ext_img ) ) 
  {
    $img_content = wp_remote_retrieve_body( $ext_img ); // get the image file
    $fp = fopen( $upload_folder.'/'.$image_filename , 'w' ); // set the path to save
    fwrite( $fp, $img_content ); // write the contents to the file
    fclose( $fp ); // close the path
    $wp_filetype = wp_check_filetype( $image_filename , null ); // check the filename
    $attachment = array(
      'post_mime_type' => $ext_img['headers']['content-type'], //We'll use the content type returned from response since this is more reliable //$wp_filetype['type'], // mimetype
      'post_title' => preg_replace( '/\.[^.]+$/', '', $image_filename ),
      'post_content' => '',
      'post_status' => 'inherit'
    );
    
    $image_filepath = $upload_folder.'/'.$image_filename;

    //require for wp_generate_attachment_metadata which generates image related meta-data also creates thumbs
    
    require_once ABSPATH . 'wp-admin/includes/image.php';
    
    $thumbnail_id = wp_insert_attachment( $attachment, $image_filepath, $parent );
    
    if ($thumbnail_id)
    {
    //Generate post thumbnail of different sizes.
    $attach_data = wp_generate_attachment_metadata( $thumbnail_id , $image_filepath);
    wp_update_attachment_metadata( $thumbnail_id,  $attach_data );
    
    return $thumbnail_id; 
    } 
  }
}
// end function



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