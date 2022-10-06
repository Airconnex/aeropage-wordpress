
<?php
/**
* Plugin Name: aero-plugin
* Author: Alan Kazek
* Author URI: https://github.com/Sergiiio98
* Description: Test.
* Version: 1.0.0
* Text-Domain: react-aero
**/

add_action( 'admin_menu', 'aeroplugin_init_menu' );

/**
 * Init Admin Menu.
 *
 * @return void
 */
function aeroplugin_init_menu() {
    add_menu_page( __( 'Aero plugin', 'aeroplugin'), __( 'Aero plugin', 'aeroplugin'), 'manage_options', 'aeroplugin', 'aeroplugin_admin_page', 'dashicons-admin-post', '2.1' );
}

/**
 * Init Admin Page.
 *
 * @return void
 */
function aeroplugin_admin_page() {
    require_once plugin_dir_path( __FILE__ ) . 'templates/app.php';
}

add_action( 'admin_enqueue_scripts', 'aeroplugin_admin_enqueue_scripts' );


/**
 * Enqueue scripts and styles.
 *
 * @return void
 */
function aeroplugin_admin_enqueue_scripts() {
    wp_enqueue_style( 'aeroplugin-style', plugin_dir_url( __FILE__ ) . 'build/index.css' );
    wp_enqueue_script( 'aeroplugin-script', plugin_dir_url( __FILE__ ) . 'build/index.js', array( 'wp-element' ), '1.0.0', true );
    wp_add_inline_script( 'aeroplugin-script', 'const MYSCRIPT = ' . json_encode( array(
        'ajaxUrl' => admin_url( 'admin-ajax.php' ),
        'plugin_admin_path' => parse_url(admin_url())["path"],
        'otherParam' => 'some value',
    ) ), 'before' );
}


function callAPI($method, $url, $data){
    $curl = curl_init();
    switch ($method){
       case "POST":
          curl_setopt($curl, CURLOPT_POST, 1);
          if ($data)
             curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
          break;
       case "PUT":
          curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "PUT");
          if ($data)
             curl_setopt($curl, CURLOPT_POSTFIELDS, $data);			 					
          break;
       default:
          if ($data)
             $url = sprintf("%s?%s", $url, http_build_query($data));
    }
    // OPTIONS:
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_HTTPHEADER, array(
       'APIKEY: 111111111111111111111',
       'Content-Type: application/json',
    ));
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
    // EXECUTE:
    $result = curl_exec($curl);
    if(!$result){die("Connection Failure");}
    curl_close($curl);
    return $result;
 }


function aeroFetchToken($dynamic, $token) {
    echo "AeroFetchToken from PHP";
    $get_data = callAPI('GET', 'https://tools.aeropage.io/api/token/'.$token+'/', false);
    $response = json_decode($get_data, true);
    $errors = $response['response']['errors'];
    $data = $response['response']['data'][0];
    // print_r($response);
   

}

function insertPost($title, $slug, $dynamic, $token, $id){
    $template_post = array(
        'ID' => $id,
        'post_title' => $title,
        'post_name' => $slug,
        'post_excerpt'=> $dynamic,
        'post_type' => 'aero-template',
        'post_status' => 'private'
    );
    $pid = wp_insert_post($template_post);
    $post_meta = add_post_meta($pid, "aero_token", $token);
    return $pid;
}

function aeroplugin_myAction() {
    // echo "Hello World!";
    // echo $_POST['title'];
    // echo $_POST['slug'];
    // echo $_POST['dynamic'];
    // echo $_POST['token'];
    // aeroFetchToken($_POST['dynamic'], $_POST['token'] );
    header('Content-Type: application/json');

    try{
      $pid = insertPost($_POST['title'],$_POST['slug'],$_POST['dynamic'],$_POST['token'],$_POST['id'] );
      echo json_encode(array("status" => "success", "post_id" => $pid));
    }catch(Exception $e){
      echo json_encode(array("status" => "error", "message" => $e->getMessage()));
    }

    die();
    // $airconnex_posts = get_posts(['post_type' => 'aero-template','post_status' => 'private','numberposts' => -1]);
    // print_r($airconnex_posts);
}

function aeroplugin_myAction2(){
    $aero_posts = get_posts(['post_type' => 'aero-template','post_status' => 'private','numberposts' => -1]);
    header('Content-Type: application/json');
    echo json_encode($aero_posts);
    die();
}

function aeroplugin_myAction3(){
    echo "myAction3";
    aeropageSync($_POST['id']);
}

function aeroplugin_get_token(){
  $pid = $_POST["id"];
  $token = get_post_meta($pid, "aero_token");
  die(json_encode(array("token" => $token)));
}

add_action( 'wp_ajax_myAction', 'aeroplugin_myAction' );
add_action( 'wp_ajax_myAction2', 'aeroplugin_myAction2' );
add_action( 'wp_ajax_myAction3', 'aeroplugin_myAction3' );
add_action( 'wp_ajax_get_token', 'aeroplugin_get_token');

function aeropageSync($id){
  echo($id);
  $response = array(); 
  sleep(3); 
  $response["status"] = "success"; 
  // $response[‘status’] = ‘error’;
  echo(json_encode($response));
  die();
}







