<?php


add_action("wp_ajax_acx_mgmt_form", "acx_mgmt_form");

function acx_mgmt_form(){

global $wpdb;
global $plugins_page;

$user_id = get_current_user_id();

$nonce = $_POST["acx_mgmt_form_nonce"];
$referer = $_POST["_wp_http_referer"];
$action = $_POST["action"];

$response = array(); // response goes back to the form / js

//----------------------------------------------------------------------------------------------------------------------------

if(isset($nonce) && wp_verify_nonce($nonce, $action)){

$stage = $_POST['stage'];

$bid = $_POST['bid']; //
$cid = $_POST['cid']; // connection id
$pid = $_POST['pid']; // page template id


//--------------

if ($stage == 'CON_APIKEY')
{

$api_key = $_POST[api_key];

// should be 17 characters length
if (strlen($api_key) !== 17)
{
$error = "Key is not valid length... ";
}

// should start with 'key'
if (substr($api_key,0,3) !== 'key')
{
$error = "Key must start with 'key' ... ";
}

if ($api_key == 'xxxxxxxxxxxxxxxxx')
{
$error = "Enter an API key... ";
}


if (!$error)
{
update_option('airconnex_owner', $user_id ); // set the user id of the owner into options
update_user_meta( $user_id,'acx_api_key', $api_key );
$response['url'] = "$plugins_page"; // after sync go back to main page
}



}




// if we added an app, create and redirect with it in the url ($bid)
if ($stage == 'CON_APP_NEW')
{

        $acx_app = $_POST['acx_app'];
        $acx_name = $_POST['acx_name'];
        $acx_color = $_POST['acx_color'];

        // should be 17 characters length
        if (strlen($acx_app) !== 17)
        {
        $error = "ID is not valid length... ";
        }
        // should start with 'app'
        if (substr($acx_app,0, 3) !== 'app')
        {
        $error = "ID must start with 'app' ... ";
        }

        if (!$error)
        {

        // add the post

        // Create post object
        $application_post = array(
        'ID' => $bid,
        'post_title' => $acx_name,
        'post_name' => $acx_app,
        'post_type' => 'acx-application',
        'post_status' => 'private'
        );

        // Insert the post into the database
        $bid = wp_insert_post( $application_post );

        // redirect to the post

        if ($bid)
        {
        update_post_meta($bid,'acx_app',$acx_app);
        update_post_meta($bid,'acx_name',$acx_name);
        update_post_meta($bid,'acx_color',$acx_color);
        $response['url'] = "$plugins_page&stage=CON_TBLVW&bid=$bid";
        }
        ELSE
        {
        $error = 'Error adding post';
        }

        }


}

// if we just selected one, redirect to 'table/ view' with the app in the url

if ($stage == 'CON_APP')
{

        $bid = $_POST['acx_bid'];

        $response['url'] = "$plugins_page&stage=CON_TBLVW&bid=$bid";

}


if ($stage == 'CON_EDIT') // just changes the post title
{

if ($cid)
{

$connection_title = $_POST['connection_title'];

                        $cid_update = array(
                        'ID' => $cid,
                        'post_title' => $connection_title,
                        );

                        $cid = wp_update_post( $cid_update );
						
						$response['url'] = "$plugins_page&main=data&cid=$cid"; // back to connection

}


}


if ($stage == 'CON_TBLVW')
{

        //$acx_table = $_POST['acx_table'];
        //$acx_view = $_POST['acx_view'];


        $acx_connect_label = $_POST['acx_connect_label'];

        $acx_connect_url = $_POST['acx_connect_url']; //https://airtable.com/tblTj7nEZenGFewI1/viwTBVIKItH7oK51T?blocks=hide

        $acx_connect_url_path = parse_url($acx_connect_url, PHP_URL_PATH);

        $acx_connect_url_array = explode('/',$acx_connect_url_path);

		//$url_app = $acx_connect_url_array[1]; //appAkLdoKEaCsWqx8
		
		//$url_app doesnt get used for now -- recently airtable added the app id into the url
		
		$url_tbl = $acx_connect_url_array[2];

        $url_viw = $acx_connect_url_array[3];


        if (substr($url_tbl,0,3) == 'tbl') //tblhEcZO50BkeIRPB
        {
        $acx_table = $url_tbl; 
        }
		
        if (substr($url_viw,0,3) == 'viw') //viwIXP5wREMV1dHq6
        {
        $acx_view = $url_viw; 
        }

     
        if (!$bid and $cid) // editing an existing connection
        {
        $bid = get_post_meta($cid,'acx_bid',true);
        } 

        $acx_app = get_post_meta($bid,'acx_app',true);
        $acx_name = get_post_meta($bid,'acx_name',true);

        // check if this table / view is already added 

        $args = array('post_type' => 'acx-connection','post_status' => 'private','numberposts' => 1);

	//$acx_table = 'tbl6Hr0R2fTW1Rt62';
	//$acx_view = 'viwH5f7P5s9lbyFkV';

	$meta_query = array(
        'relation'		=> 'AND',
        array(
          'key'	 	=> 'acx_table',
          'value'	  	=> $acx_table,
          'compare' 	=> '=',
        ),
        array(
          'key'	 	=> 'acx_view',
          'value'	  	=> $acx_view,
          'compare' 	=> '=',
        ),
        );

	$args['meta_query'] = $meta_query;

	$existing = get_posts($args);

	if (count($existing) > 0) // this connection exists already
        {
                unset($acx_table);
                unset($acx_viw);
                $error = "This connection already exists...";
        }

        //-------------
	if (!$error) // if theres already an error dont proceed 
	{
	
    if ($acx_app and $acx_table and $acx_view)
        {
                $check = acx_sync_connect($acx_app,$acx_table,$acx_view);
				

                if ($check['result'] == 'success' and $check['records'])
                {

                        // insert or update (only on success)
                        $cid_post = array(
                        'ID' => $cid,
                        'post_title' => $acx_connect_label,
                        'post_name' => "$acx_table-$acx_view",
                        'post_type' => 'acx-connection',
                        'post_status' => 'private'
                        );

                        $cid = wp_insert_post( $cid_post );

                        update_post_meta($cid,'acx_bid',$bid); // base id
                        update_post_meta($cid,'acx_table',$acx_table);
                        update_post_meta($cid,'acx_view',$acx_view);
						
						// performs a full sync -- function in acx_sync
                        $sync_count = airconnex_sync_process($cid); 

                        $response['url'] = "$plugins_page&stage=LOOP_MAPPING&cid=$cid";

                }
                else
                {

                        $error = $check['message'];

                        if (!$error){$error = 'Unknown error...';}

                        update_post_meta($cid,'acx_error',$error);

                }

        }
		ELSE // if we are missing an essential part of the connection
		{
		$error = "There's a problem with the Airtable URL, please check the instructions and if this error continues, contact plugin support (app $acx_app |tbl $acx_table |viw $acx_viw)";
		}
	}	
        
}



if ($stage == 'LOOP_MAPPING')
{

    $acx_field_h = $_POST['acx_field_h']; 
    $acx_field_p = $_POST['acx_field_p']; 
    $acx_field_i = $_POST['acx_field_i']; 
    $acx_field_l = $_POST['acx_field_l']; 

    update_post_meta($cid,'acx_field_h',$acx_field_h);
    update_post_meta($cid,'acx_field_p',$acx_field_p);
    update_post_meta($cid,'acx_field_i',$acx_field_i);
    update_post_meta($cid,'acx_field_l',$acx_field_l); // URL field mapping

    // redirect to self so that we can resbumit
    $response['url'] = "$plugins_page&stage=LOOP_MAPPING&cid=$cid"; // redirect to the next step

}



if ($stage == 'PAGE_ADD_CONN')
{
        $cid = $_POST['acx_page_connection'];
        $response['url'] = "$plugins_page&main=pages&stage=PAGE_CREATE&cid=$cid";
}


if ($stage == 'PAGE_CREATE') // adds the template with slug
{

        $acxp_title = $_POST['acxp_title'];
        $acxp_name = $_POST['acxp_name'];
        $acxp_dynamic = $_POST['acxp_dynamic'];

        // automatically add a dynamic placeholder as the title for the post..
        $dynamic_heading_field = get_post_meta($cid,'acx_field_h',true);
        $dynamic_title = "((p.$dynamic_heading_field))";

        // Create post object
        $template_post = array(
        'ID' => $pid,
        'post_title' => $dynamic_title,
        'post_name' => $acxp_name,
        'post_parent' => $cid,
        'post_type' => 'acx-template',
        'post_status' => 'private'
        );

        // Insert the post into the database

        $pid = wp_insert_post( $template_post );

        if ($pid)
        {

        update_post_meta($pid,'acxp_title',$acxp_title);
        update_post_meta($pid,'acxp_name',$acxp_name);
        update_post_meta($pid,'acxp_dynamic',$acxp_dynamic);


        if ($cid and $pid)
        {
        $sync_pages = acx_sync_pages($cid,$pid);
        $response['url'] = "$plugins_page&main=pages&pid=$pid"; // after sync go back to main page
        }

        if (!is_numeric($sync_pages)) // if no pages were synced 
        {
        $error = "Problem creating post $sync_pages";
        }

        //$response['url'] = "$plugins_page&stage=PAGE_MAPPING&cid=$cid&pid=$pid"; // redirect to the next step

        }
        ELSE
        {
        $error = "Problem creating template...";
        }


}


if ($stage == 'PAGE_ACCESS') // adds the template with slug
{
    $cid = $_POST['acx_page_access'];

    update_post_meta($pid,'acx_access',$cid);

    $response['url'] = "$plugins_page&main=pages&pid=$pid"; // redirect to the next step

}


if ($stage == 'USER_MAPPING') // sets mapping, does not create users
{

        $acx_user_n = $_POST['acx_user_n']; 
        $acx_user_e = $_POST['acx_user_e']; 
        $acx_user_i = $_POST['acx_user_i']; 

        update_post_meta($cid,'acx_user_n',$acx_user_n);
        update_post_meta($cid,'acx_user_e',$acx_user_e);
        update_post_meta($cid,'acx_user_i',$acx_user_i);
		
		

        // redirect to self so that we can resbumit
        $response['url'] = "$plugins_page&stage=USER_MAPPING&cid=$cid"; // redirect to same page to view or continue

}


if ($stage == 'USER_CREATE') // sets mapping and creates users (one step?)
{


        if ($cid)
        {
        $sync_users = acx_sync_users($cid);
        $response['url'] = "$plugins_page&main=users&cid=$cid"; // after sync go back to main page
        }

        if (!is_numeric($sync_users)) // if no pages were synced 
        {
        $error = "Problem creating users $sync_users";
        }

}




if ($stage == 'PAGE_DELETE') // 
{

$template = get_post($pid); // get the template
$message = acx_delete_dynamic($template);
$response['url'] = "$plugins_page&main=pages"; // redirect to the HOME

}



if ($stage == 'USERS_DELETE') // for when mapping is aborted before syncing, removes the mapping
{

$message = acx_delete_users($cid); // reponds with the message 
$response['url'] = "$plugins_page&main=users"; // redirect to the HOME

}



if ($stage == 'CON_DELETE')
{

        // TRASH CONNECTION----------------
		
        $delete_connection = array('ID' => $cid,'post_status' => 'trash');
        
		$cid = wp_update_post( $delete_connection );
        
		
		// DELETE TEMPLATES (CPT & DYNAMIC POSTS)---------------
		
		$airconnex_templates = get_posts(['post_type' => 'acx-template','post_parent' => $cid, 'post_status' => 'private','numberposts' => -1]);
		
		foreach ($airconnex_templates as $template)
		{
		$delete_posts = acx_delete_dynamic($template);
		}
		
	
		// DELETE USER CONFIG & DISCONNECT USERS---------------
		
		$delete_users = acx_delete_users($cid);
		

		$response['url'] = "$plugins_page"; // redirect to the HOME

}


// this only creates a page - doesn't set the access

if ($stage == 'PORTAL_CREATE') 
{

        $acxp_title = $_POST['acxp_title'];
        $acxp_name = $_POST['acxp_name'];

        // add the page ...

        // Create post object
        $portal_post = array(
        'ID' => $pid,
        'post_title' => $acxp_title,
        'post_name' => $acxp_name,
        //'post_parent' => $cid,
        // 'post_type' => 'acx-portal',
        'post_type' => 'page',
        'post_status' => 'publish'

        );

        // Insert the post into the database

        $pid = wp_insert_post( $portal_post );

        if ($pid)
        {
        update_post_meta($pid,'acxp_title',$acxp_title);
        update_post_meta($pid,'acxp_name',$acxp_name);

        $response['url'] = "$plugins_page&stage=PAGE_ACCESS&pid=$pid"; // redirect to the next step

        }
        ELSE
        {
        $error = "Problem creating template...";
        }


}






if ($stage == 'PAGE_ACCESS_DEL') // 
{
delete_post_meta($pid,'acx_access');
$response['url'] = "$plugins_page&main=pages&pid=$pid"; // redirect
}




//------------------------


//if(!$response['url']){$response['url'] = "$plugins_page&cid=$cid";} // by default go back to the admin page

if ($error)
{
$response['error'] = "<h4 style='color:#DA4453'>&#10006; $error</h4>";
}

if ($message)
{
$response['message'] = $message;
$response['error'] = ''; // remove the 'processing' message
}


if ($response['url'])
{
$response['action'] = 'redirect';
$response['error'] = "<h4 style='color:#8CC152'>&#10004; Success ...</h4>";
}

//------------------------

//$response['message'] = "<h4 style='color:#DA4453'>&#10006; $app_id,$table,$view</h4>";

}




die(json_encode($response));


}


// delete dynamic posts / custom post type

function acx_delete_dynamic($template){

global $wpdb;

/*
- takes a template
- deletes all posts
- unregisters the cpt
- deletes the template
*/

    $template_id = $template->ID; 
	$template_slug = $template->post_name; // eg Headphones
	
		
		if($template_id and $template_slug)
		{
	
        $query = "
        SELECT ID FROM $wpdb->posts p 
        INNER JOIN $wpdb->postmeta pt ON (p.ID = pt.post_id AND pt.meta_key = 'acx_template')
        WHERE pt.meta_value = '$template_id'
        ";

        $children = $wpdb->get_results($query);  // get all dynamic posts using this page template
		
		
		// delete all child posts and meta 

        if (count($children) > 0) 
		{
		$delete_count = 0;
		
		foreach($children as $post)
		{
		wp_delete_post($post->ID, true);
		$delete_count++;
		}
		
		}
		
		// unregister the custom post type
		
		unregister_post_type( $template_slug );
		
		// delete the template

		$delete_post = wp_delete_post($template_id,true);

        if(!$delete_post)
		{
		return 'Could not delete, there was an error...';
		}
			
		return "Deleted template #$template_id and $delete_count child posts..."; // returns the id of the deleted template
		
		}
		// end if template id and slug
		

}


// delete user meta for a connection -- does not delete actual user accounts!

function acx_delete_users($cid){

global $wpdb;

// if theres an acx_users count -- delete

delete_post_meta($cid,'acx_users');

// clear the config in the connection

delete_post_meta($cid,'acx_user_n');
delete_post_meta($cid,'acx_user_e');
delete_post_meta($cid,'acx_user_i');

// delete the user meta

$clear_meta = "DELETE FROM $wpdb->usermeta WHERE meta_key LIKE 'acx_{$cid}_%' ";
$results = $wpdb->get_results($clear_meta);

if ($results)
{
$count = count($results);
return "$count fields removed from user metadata...";
}
else
{
return "No user metadata was found...";
}

// end

}




?>