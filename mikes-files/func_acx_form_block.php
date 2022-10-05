<?php



// handle the form submissions via ajax

add_action("wp_ajax_acx_block_form", "acx_block_form_ajax");
add_action("wp_ajax_nopriv_acx_block_form", "acx_block_form_ajax");

function acx_block_form_ajax(){

global $wpdb;
global $plugins_page;

$user_id = get_current_user_id();

$nonce = $_POST["acx_block_form_nonce"];
$referer = $_POST["_wp_http_referer"];
$action = $_POST["action"];

$response = array(); // response goes back to the form / js

//----------------------------------------------------------------------------------------------------------------------------

if(isset($nonce) && wp_verify_nonce($nonce, $action)){

$stage = $_POST['stage'];

$bid = $_POST['bid']; // block
$connection = $_POST['cid']; // connection
$form_record_id = $_POST['rid']; // record

//------------------------

// get the fields from the connection
	
$fields = get_post_meta($connection,'acx_fields',true);

$field_array = acx_form_field_types($fields); 

// build an array of fields and values ------------------

$form_field_values = array(); 

foreach ($field_array as $key=>$field)
{
$value = $_POST[$key]; // we match the indexes
$fieldname = $field['name'];
$fieldtype = $field['form_type']; // some types need values to be modified (booleans, arrays)


if ($value) //-----------------
{

if ($fieldtype == 'number')
{
$value = floatval($value);
}

// remove slashes on single quotes
if ($fieldtype == 'text' or $fieldtype == 'textarea')
{
$value = stripslashes($value); 
}

$form_field_values[$fieldname] = $value;

$DEV_OUTPUT .= "$fieldname , $fieldtype , $value<br><br>";

}



}


$config = airconnex_sync_config($connection); //func_acx_sync
		
extract($config); //$app_id,$table,$view

if(!empty($form_record_id)) // update an existing record
{
$sync_type = "PATCH"; // UPDATE (PATCH) AN EXISTING RECORD ID
$airtable_data = array('id' => "$form_record_id", "fields"=> $form_field_values, "typecast" => true  ); // SEND THE RECORD ID & THE DATA
$response['action'] = 'refresh';
}
else
{
$sync_type = "POST"; // ADD (POST) A RECORD TO AIRTABLE
$airtable_data = array("fields"=> $form_field_values, "typecast" => true ); // SEND ONLY THE DATA
}

// REDIRECT TO A SPECIFIC URL AFTER ADDING.... (?)


//$response['action'] = 'refresh'; 

// send to airtable (patch or post)

$sync_result = acx_sync_connect($app_id,$table,$sync_type,$airtable_data);

//-----------------------

if ($sync_result["result"] == 'success') // success with the api call...
{

$sync_count = acx_sync_records($connection,$sync_result['records'],'single'); // sync the updated record into the wp db

// if the record we updated is connected to the page we are on, refresh the page

$response['message'] = "<div><h4 style='color:#8CC152'>&#10004; SUCCESS</h4></div>";

}
else
{

$error = $sync_result['message']; // get the airtable error if there is one

$response['message'] = "<div><h4 style='color:#DA4453'>&#10006; ERROR</h4>$error $DEV_OUTPUT</div>";

}


}




die(json_encode($response));


}



// render the form block

function acx_form_compiler($attributes)
{

    
        // in the editor (only) do the replacement inside the block

    global $post;

    $post_id = $post->ID;

    //$page_array = acx_page_check($post_id);

    //extract($page_array);
	
    extract($attributes);
	
	$bid = $blockId;
	
	// $form_connection X_{$CID}
	
	$form_config = explode('_',$form_connection);
	
	if (count($form_config) === 2)
	{
	
	$form_type = $form_config[0]; // A (add) P (edit page) U (edit user)
	$cid = $form_config[1]; // connection id
	
	
	// get the fields from the connection
	
	$fields = get_post_meta($cid,'acx_fields',true);
	
	
	if ($form_type == 'A')
	{
	// there is no record id... we are adding one.
	}
	
	if ($form_type == 'P')
	{
	// record id of the current dynamic page
	$record_id = get_post_meta($post_id,'acx_record',true); // record_id
	}
	
	if ($form_type == 'U')
	{
	// record id of the logged_in user or actual user (if not admin)
	$record_id = get_post_meta($post_id,'acx_login_as',true); // get the emulated user
	}
	
	// editing - we get the fields (from the connection) and record values to prefill...
	if ($record_id)
	{
	$existing_values = get_post_meta($record_id);
	
	//echo "<pre>";
	//print_r($existing_values);
	//echo "</pre>";
	
	$rid = $existing_values['record_id'][0]; // the airtable record id eg : recvw89279807

	}
	
	
	$form_fields = acx_form_fields($fields,$existing_values);
	
	
	//----------------------------------
	
	//echo "<pre>";print_r($form_fields);echo "</pre>";
	
	
	
	// FOREACH -- REPLACE THE PLACEHOLDER WITH A FORM INPUT 
	
    if (is_array($form_fields))
    {
    foreach ($form_fields as $key=>$value)
    {
	$fieldname = $value['name'];
    $placeholder = "(($fieldname))"; // the placeholder
	$form_field_html = $value['form_input'];
    $contents = str_replace( $placeholder, $form_field_html, $contents );
    }
	}
	

	
	$button_text = "Submit";
	
	// PUT THE CONTENTS INSIDE A FORM-------------------------------
	
	$submit_html = "
	<input id='acx-form-submit' type='submit' value='$button_text' class='acx-button acx-solid' name='acx_block_form_submit' />
	<input type='hidden' name='action' value='acx_block_form'>".wp_nonce_field("acx_block_form", "acx_block_form_nonce", true, false);

	
	$form_html = "
	
	 <form id='acx_form_$bid' enctype='multipart/form-data' method='post'>
                            $contents
							<div id='acx-form-message-$bid'></div>
                           $submit_html
                        </form>
	";
	
	
	$ajaxurl = admin_url('admin-ajax.php');

	$script = "<script type='text/javascript'>
				(function($){
					$('#acx_form_$bid').submit(function(e){
						var fd = new FormData($(this)[0])
						
						fd.append('bid', '$bid')
						fd.append('cid', '$cid')
						fd.append('rid', '$rid')
						
						response = call_ajax(fd)

						$('#acx-form-message-$bid').html('<h3>Processing...</h3>')
						$('#cacx-form-message-$bid').fadeIn()
						
					  response.success(function(re_data){
							var da = $.parseJSON(re_data),
							url = da['url'],
							action = da['action'],
							error = da['error'],
							message = da['message']
						
							$('#acx-form-message-$bid').html(message)   
							
							if (action == 'refresh')
							{
							window.location.href = window.location.href;
							}
				
							if (action == 'redirect')
							{
							window.location.href = url
							}
							

						})

						e.preventDefault()
					})
					function call_ajax(data){
						return $.ajax({
							url: '$ajaxurl',
							data: data,
							type: 'POST',
							contentType: false,
							processData: false,
							cache: false,
							headers: {
								'cache-control': 'no-cache'
							},
						})
					}

			   
				})(jQuery)            
			</script>";
	
//--------------------------------------------------
	

    return $form_html.$script;
	
}
// only if have required vards in array


}


function acx_form_field_types($fields)
{

$field_array = array();

$x = 0;

// foreach fields
foreach ($fields as $key=>$value)
{

$typearray = airconnex_field_typer($value);

$field_array[$x]['name'] = $key;
$field_array[$x]['show_type'] = $typearray['show_type']; // 
$field_array[$x]['form_type'] = $typearray['form_type']; // 

$x++;

}
return $field_array;

}

// create the fields / inputs replacer array 

function acx_form_fields($fields,$existing_values = '')
{

// creates an numerical array of fields with name / type

$field_array = acx_form_field_types($fields); 


// BUILD THE FORM -------------

foreach ($field_array as $key=>$field)
{

$fieldname = $field['name'];
$form_type = $field['form_type'];

//---

$existing_value = $existing_values[$fieldname];

if ($existing_value)
{
$value = $existing_value[0]; // wp meta comes as arrays
$serial_value = unserialize($value); // unserialize to see if serialized
if ($serial_value !== false){$existing_value = implode(',',$serial_value);}ELSE{$existing_value = $value;} // implode if serialized, else use original string
}

//---


if ($form_type == 'text' or $form_type == 'number')
{
$field_array[$key]['form_input'] .= "<input type='$form_type' name='$key' class='form-control' placeholder='' value='$existing_value' />";	
}

if ($form_type == 'textarea')
{
$field_array[$key]['form_input'] .= "<textarea style='min-height:100px;' class='form-control' name='$key' placeholder='' >$existing_value</textarea>";	
}



}

return $field_array;

}




// COPIED FROM OLD SYSTEM ----------------------------------------------------------------------------


function airconnex_url_type_check($url) // ANALYZES A URL TO SEE IF ITS AN IMAGE FILE...
{

$scheme = parse_url($url, PHP_URL_SCHEME);

// begins with http

if ($scheme == 'http' or $scheme == 'https')
{

$urlExt = pathinfo($url, PATHINFO_EXTENSION);

// ends with .jpg  .png

$imgExts = array("gif", "jpg", "jpeg", "png", "tiff", "tif");

//https://dl.airtable.com/7qZ9bxxaTZOscKQNs2kY_large_ERGONOMICS%20DIAGRAM1.jpg


// if its an airtable attachmentThumbnails url (some dont end with file extension)
if (strpos($url,'https://dl.airtable.com/.attachmentThumbnails/') === 0)
{
return 'image';
}

// if its a url and the extension is an image
if (in_array($urlExt, $imgExts)) 
{
return 'image';
}

// if its an airtable attachment url, and ends with docx

if (strpos($url,'https://dl.airtable.com/.attachments/') === 0 and $urlExt == 'docx')
{
return 'DOC';
}

if (strpos($url,'https://dl.airtable.com/.attachments/') === 0 and $urlExt == 'pdf')
{
return 'PDF';
}

}

}

// called by the replaced to check if a value is a time string (not the same as 'dates' from airtable)
function airconnex_timestring_check($value)
{

$is_date = true; 

// '2020-09-21T14:18:17.000Z';

// check an array of values in positions...

$time_vals = array('4' => '-','7' => '-','10' => 'T','13' => ':','16' => ':','19' => '.','23' => 'Z');

foreach ($time_vals as $pos => $val)
{
if (substr($value,$pos,1) !== $val){$is_date = false;}
}

if ($is_date !== false) // ITS A DATE
{
return 'is_time'; // return a true value
}


}


function airconnex_replacer($replace_array,$replace_string,$data = '')
{

if ($replacer_tags)
{

if ($value_type == 'image')
{
$value = "<img src='$value' />";
}

if ($value_type == 'PDF')
{
$value = "<a class='btn' target='_blank' href='$value'><i style='margin-right:10px;' class='fas fa-file-pdf'></i>View Document</a>";
}

if ($value_type == 'DOC')
{
$value = "<a target='_blank' href='$value'><i style='margin-right:10px;' class='fas fa-file-word'></i>View Document</a>";
}

}

$time_check = airconnex_timestring_check($value); //ANALYZE TO SEE IF THE VALUE IS A TIME

if ($time_check == 'is_time')
{

// this class uses a JS script to convert the time to local browser timezone
$value = "<div class='acx-js-time' data-time='$value' data-format='EU_12'>$value</div>";

}


}

// uses regex to find undefined placeholders [xxxx] in a string, interprets them and fetches the values
function airconnex_library_replacer($replace_string)
{

preg_match_all("/\\[(.*?)\\]/", $replace_string, $matches);
$matches = $matches[1];

$replacer_key_array = array();
$replacer_post_array = array();
$replacer_value_array = array();
$replacer_final_array = array();

$x = 0;
}


function airconnex_youtube_embed($youtube_url, $add_time_to_url = false, $time_in_seconds = 0){
    //Parse URL and get the video ID. We'll only get the query
    //The code will also sanitize the parameters to prevent XSS attacks
    $url_query = parse_url($youtube_url, PHP_URL_QUERY);
    parse_str($url_query, $youtube_params);
	
    $youtube_id = $youtube_params["v"];
	//$youtube_id = filter_var($youtube_id, FILTER_SANITIZE_STRING);
	
    //$youtube_embed = "https://youtu.be/".filter_var($youtube_id, FILTER_SANITIZE_STRING);

    //Add time if it exists in the URL or is passed to the function
    //The time_in_seconds is prioritized when setting the time to start in the video
    if($add_time_to_url){
        if($time_in_seconds){
            $youtube_embed .= "?t=".filter_var($time_in_seconds, FILTER_SANITIZE_STRING);
        }else{
            if(isset($youtube_params["t"])){
                $youtube_embed .= "?t=".filter_var($youtube_params["t"], FILTER_SANITIZE_STRING);
            }
        }
    }


$youtube_embed = '<iframe width="560" height="315" src="https://www.youtube.com/embed/'.$youtube_id.'" frameborder="0" allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>';


$html = "<div class='video-container''>$youtube_embed</div>";

    return $html;
}






?>