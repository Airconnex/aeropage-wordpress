<?php



//The ajax function that processes the AJAX request by the sync button
/**
 * @param
 *  $_POST (array) The global POST data
 * @return
 *  Mixed - can be an array or HTML data
 */


// function takes the connection post id and returns the values needed by the api to make a connection

function airconnex_sync_config($cid)
{

$config = array();

$config['table'] = get_post_meta($cid,'acx_table',true); 
$config['view'] = get_post_meta($cid,'acx_view',true);
$bid = get_post_meta($cid,'acx_bid',true); //base post id
$config['app_id'] = get_post_meta($bid,'acx_app',true); // base (app id)

return $config;

}


function airconnex_sync_process($cid)
{

$response = array(); // response goes back to the form / js

 // everything here should be a function so it can be called simply.

        $config = airconnex_sync_config($cid);
		
        extract($config); //$app_id,$table,$view
        
        $records = acx_sync_connect($app_id,$table,$view);

        // $records["message"] << only returned on an error
        // $records["records"] << only returned on success

        if ($records['message']) // error
        {
                $response['status'] = "error"; 
                $response['message'] = "$cid - $bid - $app_id - ".$records['message']; // the message from the sync
        }


        if ($records['records']) // success
        {
				// CREATE THE RECORDS AS ACX-RECORD WP_POSTS
                $record_count = acx_sync_records($cid,$records['records']); 
                
				
                // if the response is a number, will indicate how many records were synced
                if (is_numeric($record_count) and $record_count > 0)
                {

                  $nowtime = time();

                  update_post_meta($cid,'acx_last_sync',$nowtime);

                    $response['status'] = "success"; // success
                    $response['message'] = "<h4 style='color:#8CC152'>&#10004; $record_count Records Updated ...</h4>";

                    // check for dynamic pages, update them
                    
					$page_count = acx_sync_pages($cid); // the number of pages 
					
					if (is_numeric($record_count) and $record_count > 0) 
					{
                    $response['message'] = "<h4 style='color:#8CC152'>&#10004; $record_count Records & $page_count Pages Updated ...</h4>";
					}
					
					$user_connections = acx_sync_users($cid); // this will not do anything if the connection isnt setup with users
                    
                


                }
                

        }


return $response;


}
// end sync process


// syncronizes a connection from the floating menu...

add_action('wp_ajax_nopriv_airconnex_sync', 'airconnex_sync');
add_action('wp_ajax_airconnex_sync', 'airconnex_sync');

function airconnex_sync(){
  
  $cid = $_POST['cid'];
  $wpend = $_POST['wpend']; // will be 'front' if the button is on frontend / floating menu

  //Always check the nonce for security purposes!!!
  if(isset($_POST['nonce']) && wp_verify_nonce($_POST['nonce'], $_POST['action'])){
    
	// made into a function so can be called direclty during 'add conneciton' 
	$response = airconnex_sync_process($cid);
	
	if ($wpend == 'front' and $response['status'] == "success")
	{
	$response['after'] = 'reload';
	} // reload after response if frontend
	else 
	{
                    // error during sync
    $response['status'] = "error"; 
    $response['message'] = "Airtable responded, but there was an error syncing the records.";
    }
	

    die(json_encode($response));
  }else{
    die(json_encode([
      "status" => "error",
      "message" => "invalid request."
    ]));
  }
}


// this one only shows in the admin -- the frontend is in floating menu
function airconnex_sync_button($cid,$type = ''){

if ($type == 'link')
{
// wpend makes it refresh the page after
return  "<a href='javascript:void(0);'  id='acx-sync-button' data-wpend='front' data-cid='{$cid}'>
		<span style='font-size:16px;line-height:18px;margin-right:5px;' id='acx-sync-icon-$cid' class='dashicons dashicons-image-rotate'></span>
		Syncronize
		</a>";
}

return "<a href='javascript:void(0);' class='acx-button acx-outline' id='acx-sync-button' data-cid='{$cid}'><span id='acx-sync-icon-$cid' class='dashicons dashicons-image-rotate'></span></a>";


}





add_action('wp_head', 'acx_sync_javascript');
add_action('admin_head', 'acx_sync_javascript');
function acx_sync_javascript() {
      $action = 'airconnex_sync'; //Action for the ajax function
      $nonce = wp_create_nonce($action); //Nonce for security

      /**
        * JS EXPLANATION:
        * $(document).on('click', '.acx-sync-button', callback_function) 
        *   is very useful especially if you return
        *   HTML data that contains buttons that needs to send AJAX queries
        *   because this format is an event listener for dynamic data added to HTML structure by javascript.
        *   When a new HTML structure is added by JS, it will need to rebind the event listeners.
        *   This format does that.
        *
        * $(this).data('cid')
        *   The .data() extracts the value from data attribute. It accepts a string argument.
        *   The string argument is the word that comes after 'data-', 
        *   in the case of 'data-cid' used in the sync button, we will pass 'cid' to .data
        *   hence .data('cid').
        *   $(this) refers to the current element (the element/button clicked by the user) 
        * 
        * var data = $.parseJSON(response)
        *   Parses the JSON response from the ajax function. If you are returning HTML,
        *   You can just omit this line.
        * 
        * $('.data').html()
        *   If you want to HTML add the data to a <div> or any HTML, you can use .html().
        *   This is useful for instances that you return HTML data from the AJAX function.
        * 
        * $.post()
        *   Sends a POST request to the URL passed to this function, in this case, ajaxurl
      */
    ?>
      <script type="text/javascript" >
        jQuery(document).ready(function($) {
          $(document).on('click', '#acx-sync-button', function(){
            var cid = $(this).data('cid')
            var wpend = $(this).data('wpend')
            var data = {
              action: '<?=$action?>',
              cid: cid,
              wpend: wpend,
              nonce: '<?=$nonce?>'
            };
              
            $('#acx-sync-icon-' + cid).addClass('spin');

            $.post(ajaxurl, data, function(response) {
              var data = $.parseJSON(response),status = data['status'],message = data['message'],after = data['after']
              //alert('Got this from the server: ' +  message);
              
              $('#acx-sync-icon-' + cid).removeClass('spin');
              $('#acx-sync-icon-' + cid).removeClass('dashicons-image-rotate');

              if (status == 'success')
              {
                
                $('#acx-sync-icon-' + cid).addClass('dashicons-yes-alt');
                $('#acx-sync-icon-' + cid).addClass('acx-sync-success');
                
              //$('#acx-sync-message').html('<h4 style="color:#8CC152">&#10004;'+ message +'</h4>')
              }

              if (status == 'error')
              {
                $('#acx-sync-icon-' + cid).addClass('dashicons-no-alt');
                          //$('#acx-sync-message').html('<h4 style="color:#DA4453">&#10006;'+ message +'</h4>')
              }

              setTimeout( function() {
          
                $('#acx-sync-icon-' + cid).removeClass( 'dashicons-yes-alt' ); 
                $('#acx-sync-icon-' + cid).removeClass( 'acx-sync-success' ); 
                $('#acx-sync-icon-' + cid).addClass('dashicons-image-rotate');

              

              }, 2000 );

              if (after == 'reload')
                {
                  location.reload();
                }

              // $('.data').html("add the response from the ajax function here. It should be in HTML, though.")
            });
          });
        });
      </script>
    <?php

}
