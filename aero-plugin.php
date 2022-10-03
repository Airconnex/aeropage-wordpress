
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
    $get_data = callAPI('GET', 'https://api.aeropage.io/api/v3/token/'.$token, false);
    $response = json_decode($get_data, true);
    $errors = $response['response']['errors'];
    $data = $response['response']['data'][0];
    print_r($response);
}

function aeroplugin_myAction() {
    // echo "Hello World!";
    // echo $_POST['title'];
    // echo $_POST['dynamic'];
    // echo $_POST['token'];
    aeroFetchToken($_POST['dynamic'], $_POST['token'] );

}

add_action( 'wp_ajax_myAction', 'aeroplugin_myAction' );




