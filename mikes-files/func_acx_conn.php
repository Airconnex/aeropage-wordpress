<?php

// fetch or confirm the existence of a user's api key

function acx_sync_api_key($type)
{

// get the meta -- acx_api_key

	$airconnex_owner = get_option('airconnex_owner');
	
	if (!$airconnex_owner and current_user_can('manage_options'))
    {
	$airconnex_owner = get_current_user_id();
	update_option('airconnex_owner', $airconnex_owner ); // set the user id of the owner into options
	}

    $acx_api_key = get_user_meta( $airconnex_owner,'acx_api_key', 'true' );

    // check for user meta acx_api_key

    if ($type == 'check' and strlen($acx_api_key) == 17 ) // just confirm it exists
    {
    return true;
    }

    if ($type == 'get') // return the value
    {
    return $acx_api_key;
    }

    


}



// make the api call to airtable and return the response...
// do not convert this to a connection id - we need to run this function prior to creating the connection (to test it works)

function acx_sync_connect($app_id,$table,$view,$send_data = '')
{

$airtable_api_key = acx_sync_api_key('get'); // get the api key for syncing


if ($send_data)
{

$method = $view; // set the curl method

if ($view == 'PATCH')
{
$airtable_data = json_encode(array("fields" => $send_data["fields"])); //get the fields as a separate array
$record_id = $send_data["id"].'/'; // add the record id to the api url
}

if ($view == 'POST')
{
$airtable_data = json_encode($send_data); // the data only has fields
}

}
else
{
$queries["view"] = $view;
$queries["pageSize"] = 100; 
}

//------- THE API SETUP AND CALL --------------

$response["records"] = array(); // make an array to hold offsets and merge

$offset = 0;

while( !is_null( $offset ) )
{

if($offset){$queries["offset"] = $offset;} // offset to loop past 100 limit

    $api_url = "https://api.airtable.com/v0/".$app_id."/".$table."/".$record_id."?".preg_replace('/%5B[0-9]+%5D/simU', '%5B%5D', http_build_query( $queries ));

    $headers = array(
    "Authorization: Bearer $airtable_api_key",
    "Content-Type: application/json"
    );

    $ch = curl_init($api_url);
	
	//Disable CURLOPT_SSL_VERIFYHOST and CURLOPT_SSL_VERIFYPEER by
    //setting them to false.
    // ONLY FOR LOCAL / DEVELOPMENT!!!!
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	
	
	if ($method and $airtable_data) // POST or PATCH
	{
        curl_setopt( $ch, CURLOPT_CUSTOMREQUEST, $method); //PATCH will only update the specified fields // "PUT" method for new row or destructive update
        curl_setopt( $ch, CURLOPT_POSTFIELDS,$airtable_data);
        curl_setopt( $ch, CURLOPT_POST, 1);
    }
	
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	
    $airtable_records = json_decode(curl_exec($ch), true);
	
    curl_close($ch);

    //--------------


    $response["records"] = array_merge($response["records"] , $airtable_records["records"]);


/*
//Single Row -- Add the return array
        $return_array["records"][0]['id'] = $airtable_records['id']; // only one record was returned
        $return_array["records"][0]['fields'] = $airtable_records['fields']; // only one record was returned
        $return_array["records"][0]['createdTime'] = $airtable_records['createdTime']; // only one record was returned
        $return_array["success"] = 1;
*/		
		

$offset = (isset( $airtable_records["offset"] )) ? $airtable_records["offset"] : null;


}
//END THE OFFSET LOOP


    if (array_key_exists("records", $airtable_records)) // success... multiple records returned
    {
    $response["result"] = 'success';
	}
	
	
	if (array_key_exists("fields", $airtable_records)) // success...single record returned
    {
    $response["result"] = 'success';
	$response["records"] = array($airtable_records); // put single record into array
	}
	

if ($response["result"] !== 'success')
{
	if ($airtable_records['error']) // reponse with an error
    {

    $response["result"] = 'error';

    $error = $airtable_records['error'];

    if (is_array($error))
    {
    extract($error);
    $response["message"] = "$type<br>$message"; // TABLE_NOT_FOUND VIEW_NAME_NOT_FOUND
    }
    elseif ($error == 'NOT_FOUND')
    {
    $response["message"] = "APP_NOT_FOUND";
    }


    }
    else // no response from airtable
    {
    $response["result"] = 'error';
    $response["message"] = "NO_RESPONSE";
    }
}


if ($response["result"] == 'error')
{
$response["message"] .= "<br><br>$api_url <br><br>";
}


return $response;

}


// take the records and syncronize them into acx-record posts

function acx_sync_records($connection,$records,$single = '')
{

    global $wpdb;

    $menu_order = 0;

    $fields = array();

    // update all to trash - any that remain will be reinstated in the loop...
	
	if (!$single) // if its a single row, dont update the row order
    {
    $trash_update = "
		UPDATE $wpdb->posts SET post_status = 'trash' 
		WHERE post_type = 'acx-record' and post_parent = $connection "
		;
	$results = $wpdb->get_results($trash_update);
    }
		
	

    foreach ($records as $record)
    {

    $record_id = $record['id'];
    $record_fields = $record['fields'];
	
    $fields = array_merge($fields,$record_fields);

    //------------ check for existing

    $query = "SELECT * FROM $wpdb->posts WHERE post_name = '$record_id' and post_type = 'acx-record' LIMIT 1";
    $existing = $wpdb->get_results($query); 

    $record_post_id = $existing[0]->ID; // get the post id so it will update instead of adding a new one
    $exist_post_date = $existing[0]->post_date; // reuse the original posts date


    if ($single) // if its a single row, dont update the row order
    {
    $menu_order = $results[0]->menu_order; // reuse the original menu order
    }


    $acx_post_array = array(
      'ID' => "$record_post_id",
      'post_title'    => "$record_id",
      'post_date' => "$exist_post_date",
      'post_status'   => 'private',
      'post_type' => "acx-record",
      'post_name' => "$record_id",
      'post_parent' => "$connection",
      'menu_order' => "$menu_order"
    );

	//remove_all_filters("content_save_pre"); //make sure this is on or the 
	
    $record_post_id = wp_insert_post($acx_post_array,true);

    // DELETE EXISTING META 

    // for some reason, every second sync the meta doesnt get added back ?
    // set the values empty instead?

    //$query = "DELETE FROM $wpdb->postmeta WHERE post_id = '$record_post_id'";
    //$results = $wpdb->get_results($query); 


    update_post_meta($record_post_id,'record_id',$record_id);

    // UPDATE FIELDS AS META

    foreach ($record_fields as $key => $value)
    {
	// htmlspecialchars  to handle apostrophe /comma etc..
	if (!is_array($value)){$value = htmlspecialchars($value, ENT_QUOTES );}
	update_post_meta($record_post_id,$key,$value);
    }

	//update_post_meta($record_post_id,'_TEST','xxxx');

    //--------------------------------

    $menu_order++;

    }
    // end foreach record


    // add record_id at the beginning
    //$fields = array_reverse($fields);
    //$fields['record_id'] = $record_id;
    //$fields = array_reverse($fields);
      

    //if (count($fields) > 0){update_post_meta($connection,'acx_fields',$fields);}
	
	
	if (!$single and count($fields) > 0) // if not updating a single record, save the field samples to the connection
	{
    $fields = array_reverse($fields);
    $fields = array_reverse($fields);
    update_post_meta($connection,'acx_fields',$fields);
	update_post_meta($connection,'acx_count',$menu_order);
	
	}
	

    return $menu_order; //THIS SHOULD INDICATE SUCCESS...


}


//--------------------------------------------------------

// function to query user posts, filters out any without email values
// accepts a limit


function acx_wp_users()
{

$args = array(
    'role'    => 'Airconnex',
    'orderby' => 'user_nicename',
    'order'   => 'ASC'
);

$users = get_users( $args );

return $users;

}


function acx_user_query($cid,$limit = '')
{

    global $wpdb;
	
    $acx_user_e = get_post_meta($cid,'acx_user_e',true);

    if (!$limit){$limit = '-1';}

    // select all the records for the connection
    $records = get_posts([
    'post_type' => 'acx-record',
    'post_parent' => $cid,
    'post_status' => 'private',
    // 'meta_key' => $acx_user_e,'meta_value' => $cid,
    'numberposts' => $limit,
	'orderby' => 'menu_order',
	'order'=> 'ASC',
    'meta_query' => [
            'relation' => 'AND',
            [
              'key' => $acx_user_e,
              'compare' => 'EXISTS',
            ],
            [
              'key' => $acx_user_e,
              'compare' => '!=',
              'value' => ''
            ]
          ]

    ]);

 
    return $records;


}


// function to query record posts with filters

function acx_record_query($cid,$limit = '',$filter = '')
{

$dynamic_filter = null;
$text_filter = null;


if ($cid)
{
    global $wpdb;

    if (!$limit){$limit = '-1';} // calls when syncing pages and users - no limit

    $args = array('post_type' => 'acx-record','post_parent' => $cid,'post_status' => 'private','orderby' => 'menu_order','order'=> 'ASC','numberposts' => $limit);

    // dynamic / linked filters
	
    if ($filter['dynamic'])
    {
 
      global $post;

      $post_id = $post->ID;

	  // by default [dynamic] => page
	
      $record_post = get_post_meta($post_id,'acx_record',true);
      

      if ($filter['dynamic'] == 'user') // if its a user get the user instead
      {
      $record_post = acx_get_subuser_record($post_id);
      }

      $record_id = get_post_meta($record_post,'record_id',true);

		// this will check ALL meta for the existence of a value like the current posts record id..,
		
        $dynamic_filter = array(
          array(
            'value'	  	=> "$record_id",
            'compare' 	=> 'LIKE',
          )
        );
    }

    // filters from the frontent
    if ($filter['text'])
    {
      $acx_field_h = get_post_meta($cid,'acx_field_h',true);
      $acx_field_p = get_post_meta($cid,'acx_field_p',true);

      $text_filter = array(
        'relation'		=> 'OR',
        array(
          'key'	 	=> $acx_field_h,
          'value'	  	=> $filter['text'],
          'compare' 	=> 'LIKE',
        ),
        array(
          'key'	 	=> $acx_field_p,
          'value'	  	=> $filter['text'],
          'compare' 	=> 'LIKE',
        ),
        );

    }

    if ($dynamic_filter)
    {
      $args['meta_query'] = $dynamic_filter;
    }

    if ($text_filter)
    {
      $args['meta_query'] = $text_filter;
    }

    // if both, override
    if ($dynamic_filter and $text_filter)
    {
      $args['meta_query'] = array('relation' => 'AND', $dynamic_filter, $text_filter) ;
    }

    //return $args;

    $records = get_posts($args);
	
	if (!$records){return $args;} // no records return the args instead

    return $records;
	
}
// end if cid
	
}


// fetches records for a connection and turns them into wp users

function acx_sync_users($cid)
{


        global $wpdb;

        $count = 0;

        $acx_user_e = get_post_meta($cid,'acx_user_e',true);
        $acx_user_n = get_post_meta($cid,'acx_user_n',true);
		
		
		// delete all user_meta for the current connection (will get added back if the user is still present in the records set)
		
		$clear_meta = "UPDATE $wpdb->usermeta SET meta_value = '' WHERE meta_key LIKE 'acx_{$cid}_%' ";
		$results = $wpdb->get_results($clear_meta);
				

        if ($acx_user_e) // only proceed if we have an email field
        {

          $records = acx_user_query($cid); // user query with filter (only with email values)

          // foreach record
          foreach ($records as $record)
          {

              $record_post_id = $record->ID;
              $user_email = get_post_meta($record_post_id,$acx_user_e,true); // get the value of the email field for the record..
              $user_name = get_post_meta($record_post_id,$acx_user_n,true); 


            if ( is_email( $user_email ) ) // only if we get a valid email 
            {

                  $user_id = email_exists($user_email); // check if the user already exists
                  
				  // CREATE USERS if this user does not already exist (by email)
                  if (!$user_id) 
                  {
				  
				  if (!$user_name) {$user_name = sanitize_user($user_email); } // make the username same as email 

				  unset($random_password); // remove the previous random password

                  $random_password = wp_generate_password($length = 12, $include_standard_special_chars = false );
                  
                   //$user_id = wp_create_user( $user_name, $random_password, $user_email );
                    
                  $userdata = array(
                      'ID'                    => $user_id,    //(int) User ID. If supplied, the user will be updated.
                      'user_pass'             => $random_password,   //(string) The plain-text user password.
                      'user_login'            => $user_email,   //(string) The user's login username.
                      'user_nicename'         => $user_name,   //(string) The URL-friendly user name.
                    // 'user_url'              => '',   //(string) The user URL.
                      'user_email'            => $user_email,   //(string) The user email address.
                      'display_name'          => $user_name,   //(string) The user's display name. Default is the user's username.
                    //  'nickname'              => '',   //(string) The user's nickname. Default is the user's username.
                    // 'first_name'            => '',   //(string) The user's first name. For new users, will be used to build the first part of the user's display name if $display_name is not specified.
                    // 'last_name'             => '',   //(string) The user's last name. For new users, will be used to build the second part of the user's display name if $display_name is not specified.
                    //  'description'           => '',   //(string) The user's biographical description.
                    // 'rich_editing'          => '',   //(string|bool) Whether to enable the rich-editor for the user. False if not empty.
                      //'syntax_highlighting'   => '',   //(string|bool) Whether to enable the rich code editor for the user. False if not empty.
                    // 'comment_shortcuts'     => '',   //(string|bool) Whether to enable comment moderation keyboard shortcuts for the user. Default false.
                    // 'admin_color'           => '',   //(string) Admin color scheme for the user. Default 'fresh'.
                    //  'use_ssl'               => '',   //(bool) Whether the user should always access the admin over https. Default false.
                    // 'user_registered'       => '',   //(string) Date the user registered. Format is 'Y-m-d H:i:s'.
                    //  'show_admin_bar_front'  => 'false',   //(string|bool) Whether to display the Admin Bar for the user on the site's front end. Default true.
                      'role'                  => 'Airconnex',   //(string) User's role.
                    // 'locale'                => '',   //(string) User's locale. Default empty.
                  
                  );	
				  
                  $user_id = wp_insert_user($userdata);
				  
				  // --- THIS SEEMS TO SLOW / FREEZE IF NOT PROPERLY CONFIGURED
				  
				  //wp_new_user_notification( $user_id, $random_password); // send wp user notification email...
				  
				  }
                  
                  update_user_meta($user_id,"acx_{$cid}_record",$record_post_id); // link to the record with meta
				  
				  //$random_password
				  
				  // RECORD META ADDED TO ALLOW SEARCH RESULTS, NOT USED IN DYNAMIC SYSTEMS // prefix keys with acx: to avoid breaking other meta

					$record_meta = get_post_meta($record_post_id);
					$record_meta = array_combine(array_keys($record_meta), array_column($record_meta, '0'));

					foreach ($record_meta as $key => $value)
					{
					$key = "acx_{$cid}_".$key;
					$value_x = airconnex_field_typer($value);
					$value = $value_x['show_value'];
					update_user_meta($user_id,$key,$value);
					}

                  $count++;
				  
                  unset($user_id); // end of loop, unset user id

            }

            // end is email

            

          }

		update_post_meta($cid,'acx_users',$count);
			
        }
        //return $return;

        
        return $count;


}


// for a connection, finds any associated templates and creates wp_post->pages for each template
// can be called to create pages, or after a sync to update them

function acx_sync_pages($cid,$pid ='')
{

        global $wpdb;

        $count = 1;

        $acx_app  = get_post_meta($cid,'acx_app',true); //
		
		// default fields are property of the connection
		$conn_h_field = get_post_meta($cid,'acx_field_h',true);
        $conn_i_field = get_post_meta($cid,'acx_field_i',true);


        if ($pid) // syncing just one page (generally on initial creation or some value being edited )
        {
        $template = get_post($pid);
        $templates = array($template);
        }
        ELSE // if no post id is provided -- sync all pages that are a child of the connection
        {
        $templates = get_posts(['post_type' => 'acx-template','post_parent' => $cid,'post_status' => 'private','numberposts' => -1]);
        }


        foreach ($templates as $template)
        {

        $template_id = $template->ID;
		$template_title = $template->post_title; //  use the template title
		$template_content = $template->post_content; //  use the template content
		$template_content = addslashes($template_content); // add slashes to counter wordpress stripslashes
		
        $template_post_type = get_post_meta($template_id,'acxp_name',true);// eg categories
        $template_slug_field = get_post_meta($template_id,'acxp_dynamic',true);// eg [Brand]
		
		// TRASH ALL FIRST
		
		$update = "
		UPDATE $wpdb->posts p
        INNER JOIN $wpdb->postmeta pt ON (p.ID = pt.post_id AND pt.meta_key = 'acx_template') 
		SET p.post_status = 'trash' 
		WHERE p.post_type ='$template_post_type' and pt.meta_value = '$template_id'"
		;
		
		$results = $wpdb->get_results($update);
		
        // select all the records for the connection
		
        $records = acx_record_query($cid);

        // foreach record
        foreach ($records as $record)
        {

        $record_post_id = $record->ID;
        $menu_order = $record->menu_order;

        $dynamic_post_slug = get_post_meta($record_post_id,$template_slug_field,true);// get the dynamic value

        $query = "
        SELECT * FROM $wpdb->posts p 
        INNER JOIN $wpdb->postmeta pr ON (p.ID = pr.post_id AND pr.meta_key = 'acx_record')
        INNER JOIN $wpdb->postmeta pt ON (p.ID = pt.post_id AND pt.meta_key = 'acx_template')
        WHERE p.post_type ='$template_post_type' and pt.meta_value = '$template_id' AND pr.meta_value = '$record_post_id' LIMIT 1
        ";
		// this checks based on record post id & template (not slug)

        $results = $wpdb->get_results($query); 

        $post_exists = $results[0]->ID; // get the post id so it will update instead of adding a new one
		
		$dynamic_post_array = array(
			  'ID' => "$post_exists",
			  'post_title'    => "$template_title",
			  'post_status'   => 'publish',
			  'post_content'   => "$template_content", // must have a value or wont be inserted
			  'post_type' => "$template_post_type", // eg shoes
			  'post_name' => "$dynamic_post_slug", // eg nike
			  'menu_order' => "$menu_order"
			);
			
		
		remove_all_filters("content_save_pre"); //!! important -- make sure this is on or the template will be overwritten by wp_insert_post when saving

		$dynamic_post_id = wp_insert_post($dynamic_post_array,true); //add or update the post

		unset($post_exists); // reset the condition that checks if a post exists

        update_post_meta($dynamic_post_id,'acx_record',$record_post_id); // link to the record with meta
        update_post_meta($dynamic_post_id,'acx_template',$template_id); // link to the template
		
		
		// RECORD META ADDED TO ALLOW SEARCH RESULTS, NOT USED IN DYNAMIC SYSTEMS // prefix keys with acx: to avoid breaking other meta

			$record_meta = get_post_meta($record_post_id);
			$record_meta = array_combine(array_keys($record_meta), array_column($record_meta, '0'));

			foreach ($record_meta as $key => $value)
			{
			$key = "acx_".$key;
			$value_x = airconnex_field_typer($value);
			$value = $value_x['show_value'];
			update_post_meta($dynamic_post_id,$key,$value);
			}



// THIS MAY BE ADDED LATER, WE WONT DOWNLOAD ATTACHMENTS IN THIS VERSION ---------
/*
        if ($template_i_field)
            {

            $image_value = get_post_meta($record_post_id,$template_i_field,true); // get the value of the image field for the record..

            // its an attachment, get the first image - large thumb
            if (array_key_exists( "filename", $image_value[0] ) )
            {
            $image_value = $image_value[0]['thumbnails']['large']['url'];
            }

            $thumbnail_id = get_post_meta( $dynamic_post_id, '_thumbnail_id',true ); // check if this post already has thumbnails...

            $filename = "$record_post_id-$template_i_field";

            $upload_subfolder = $acx_app; // put thumbs into subfolders based on app id.

            $attach_post = $dynamic_post_id;


            if (!$thumbnail_id) // if we dont already have the thumb for this post
            {

            $attach_id = acx_download_external_image($image_value,$filename,$upload_subfolder,$attach_post,$ext_extension = '');

            //Set the attachment as featured image.
            delete_post_meta( $dynamic_post_id, '_thumbnail_id' );
            add_post_meta( $dynamic_post_id , '_thumbnail_id' , $attach_id, true );

            }

            unset($thumbnail_id);

        }

*/
        //---------------

        $count++;

        }
		// END FOREACH RECORD


        }
        // end foreach template

        return $count;

}


// REGISTER CUSTOM DYNAMIC POSTS

function acx_dynamic_post_types()
{
$flush = null;

		add_role(
		'Airconnex',
		__( 'Airconnex' ),
		array(
		'read'         => true,  // true allows this capability
		'edit_posts'   => false,
		)
		);


		/*
		if (!post_type_exists('acx-portal'))
		{
			$public_pt_args = array(
				'label' => 'Airconnex Portals',
				'public' => true,
				'publicly_queryable' => true,
				'exclude_from_search' => false,
				'show_ui' => true,
				'show_in_menu' => true,
				'has_archive' => true,
				'rewrite' => true,
				'query_var' => true,
			);
			register_post_type( 'acx-portal', $public_pt_args );
		}

		*/

		$airconnex_templates = get_posts(['post_type' => 'acx-template','post_status' => 'private','numberposts' => -1]);

		foreach ($airconnex_templates as $template)
		{

		$template_id = $template->ID;

		// dont use post_title -- it will contain dynamic placeholder

		$cpt_title = get_post_meta($template_id,'acxp_title',true);

		//$cpt_title = $template->post_title; // eg Headphones

		$cpt_slug = $template->post_name; // eg Headphones

		//$return .= "$cpt_title $cpt_slug";

		if (!post_type_exists($cpt_slug))
		{

		$flush = true;

		register_post_type( "$cpt_slug", //airconnex_templates
				array(
					"labels" => array(
						"name"=> _("$cpt_title"),
						"singular_name" => _("$cpt_title")
					),
					'hierarchical' => true,
					"has_archive" => false,
					"rewrite" => array( "slug" => "$cpt_slug" ), // my custom slug
					"supports" => array( "title","editor","thumbnail" ), // editor page settings
					"show_in_rest" => true, // gutenberg
					"description" => "$cpt_title",
					"public" => true, // All the relevant settings below inherit from this setting
					//"exclude_from_search" => true, // When a search is conducted through search.php, should it be excluded?
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

add_action( 'init', 'acx_dynamic_post_types' );






function acx_download_external_image($image_value,$filename,$upload_subfolder,$attach_post,$ext_extension = '')
{

    global $wpdb;

    //$filename -- desired name of local file
    //$upload_subfolder -- subfolder in airconnex
    //$image_value -- the external url
    //$attach_post -- the parent post to attach to
    //$ext_extension -- for apis can predefine this

        $ext_url = sanitize_text_field( $image_value ); // the full url to external
        
        // if not predefined, get the extension from the url
        if (!$ext_extension){$ext_extension = pathinfo(parse_url($ext_url, PHP_URL_PATH), PATHINFO_EXTENSION);}

        // new filename for local
        $image_filename = sanitize_file_name( $filename.'.'.$ext_extension);
        
        $upload_dir = wp_upload_dir();
        $upload_folder = $upload_dir['basedir'].'/airconnex/'.$upload_subfolder;
        if(!file_exists($upload_folder)) wp_mkdir_p($upload_folder);

        
        //$img_name = end(explode('/',$image)); // this uses the filename from the url... we want to use the post?
        
        $ext_img = wp_remote_get( $ext_url ); // check the url to make sure its valid

        if (! is_wp_error( $ext_img ) ) 
        {
          $img_content = wp_remote_retrieve_body( $ext_img ); // get the image file
          $fp = fopen( $upload_folder.'/'.$image_filename , 'w' ); // set the path to save
          fwrite( $fp, $img_content ); // write the contents to the file
          fclose( $fp ); // close the path
          $wp_filetype = wp_check_filetype( $image_filename , null ); // check the filename
          $attachment = array(
            'post_mime_type' => $wp_filetype['type'], // mimetype
            'post_title' => preg_replace( '/\.[^.]+$/', '', $image_filename ),
            'post_content' => '',
            'post_status' => 'inherit'
          );
          
          //$image_filename = $image_name.'.'.$wp_filetype['ext'] ; // define the full filename
          $image_filepath = $upload_folder.'/'.$image_filename;

          //require for wp_generate_attachment_metadata which generates image related meta-data also creates thumbs
          
          require_once ABSPATH . 'wp-admin/includes/image.php';
          
          $thumbnail_id = wp_insert_attachment( $attachment, $image_filepath, $attach_post );
          
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


//https://dummyimage.com/600x400/000/fff

function acx_fetch_dummy_images($connection)
{

    // $connection (post id)

    // get the acx_fields

    $acx_fields = get_post_meta($connection,'acx_fields',true);

    foreach ($acx_fields as $key=>$value)
    {

    if (is_array($value[0]))
    {

    if (array_key_exists('thumbnails',$value[0]))
    {

    // add this fieldname to the connection as an image field.

    add_post_meta($connection,'acx_image',$key);

    $image_value = "https://dummyimage.com/600x400/000/fff&text=$key";
    $filename = "$connection-$key";
    $upload_subfolder = "placeholders";
    $attach_post = $connection;
    $ext_extension = "png";

    $thumbnail_id = acx_download_external_image($image_value,$filename,$upload_subfolder,$attach_post,$ext_extension);

    $result .= "$filename attached $thumbnail_id for $connection : $fieldname <br>";

    }
    // end if image attachment
    }

    }
    // foreach field


    return $result;

}





