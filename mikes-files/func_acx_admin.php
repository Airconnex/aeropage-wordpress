<?php



// call the shortcode on any page to echo returns from functions

add_shortcode('acx_echo', 'airconnex_echo_tester'); 

function airconnex_echo_tester()
{

	global $wpdb;
	global $plugins_page;

	$config = airconnex_sync_config('217'); //func_acx_sync
		
	extract($config); //$app_id,$table,$view

	//echo "$app_id $table $view";
	
	//app1Nz4zLQCJ4ORUr tblTj7nEZenGFewI1 viwTBVIKItH7oK51T

	$response = acx_sync_connect($app_id,$table,$view);
	
	extract($response);
	
	//$result;

    echo "<pre>";
	print_r($records);
	echo "</pre>";
	

}
	
	
// takes a single (raw airtable) value and returns the extracted value and type...

function airconnex_field_typer($o_value)
{

if (is_serialized($o_value))
{
$value = unserialize($o_value);
}
else 
{
$value = $o_value;
}
//if (is_string($o_value)){$value = unserialize($o_value,['allowed_classes' => false]);} // we unserialize it

if (is_array($value)){$o_value = $value;} // if we end up with an array, we use the array now...


// STRING (default)

$form_type = 'text'; // the input to show on forms 
$show_type = 'string';
$show_value = $o_value;


// ATTACHMENT

if (is_array($o_value[0]))
{
if (array_key_exists( "filename", $o_value[0] ) )
{

$form_type = 'attachment'; // the input to show on forms (a file upload)

$file_type = $o_value[0]['type']; 

$show_value = $o_value[0]['url']; // generally the url to the file


if (substr($file_type,0,5) == 'image')
{
$show_type = 'img_attachment';
$show_value = $o_value[0]['thumbnails']['large']['url']; // only images use the thumbnail values
}

// video file attachments video/mp4

if (substr($file_type,0,5) == 'video')
{
$show_type = 'video';
}

// PDF file attachments

if ($file_type == 'application/pdf')
{
$show_type = 'pdf';
}


//Microsoft Word (docx) "type": "application/vnd.openxmlformats-officedocument.wordprocessingml.document"

if ($file_type == 'application/msword' or $file_type == 'application/vnd.openxmlformats-officedocument.wordprocessingml.document')
{
$show_type = 'doc';
}


}
}

// ARRAY (LOOKUP, MULTISELECT, LINKED)

if ($form_type !== 'attachment' and is_array($o_value)) // is array but not file attachment (usually, lookup)
{

$o_value_first = $o_value[0]; // check the first value

$form_type = 'multi_select'; // the input to show on forms 

$show_type = 'array'; // the way to render generally

$show_value = implode(', ',$o_value); // implode array to comma separated

if (strlen($o_value_first) == 17 and substr($o_value_first,0,3) == 'rec') // if its a record id, consider this to be a linked field...
{
$form_type = 'linked_select'; // the input to show on forms 
$show_type = 'linked'; // the way to render generally
// put a function here that looks up the values from linked fields ...
}


}


// BOOLEAN (TRUE / FALSE)

if ($o_value === true) 
{
$form_type = 'checkbox'; // the input to show on forms 
$show_type = 'true_false'; // the way to render generally
}


// MULTILINE STRINGS (possibly rich text)
 
if ($show_type == 'string')
{


if (is_numeric($o_value))
{
$form_type = 'number'; // this is still a string input but tells airtable to take a number
}
else
{
$show_value = htmlspecialchars_decode($show_value); // decode html for multilines
}

// THERES ANY LINEBREAKS ITS A MULTILINE (PARSE DOWN HTML)

if(strpos($o_value, PHP_EOL) !== FALSE){

$form_type = 'textarea'; // the input to show on forms 


    //Remove break tags first to avoid adding a new br tag.
    //$o_value = str_ireplace(array("<br>", "<br >", "<br/>", "<br />"), "", $o_value);

    //require_once(get_stylesheet_directory()."/airconnex/parsedown.php");
    //$markdown_parser = new Parsedown();
	//$o_value = $markdown_parser->setBreaksEnabled(true)->text($o_value);
	
    //Remove paragraphs and add br tag
    //$o_value = str_replace("<p>", "", $o_value);
    //$o_value = str_replace("</p>", "<br><br>", $o_value);
	
}



}


return array('form_type' => $form_type,'show_type' => $show_type, 'show_value' => $show_value, 'raw_value' => $o_value);

}



// takes the array from page check and renders to blocks

function acx_page_block_render($data)
{
		// for editor and by default, dont parse blocks

		$blocks = parse_blocks($data['content']);
		
		if (isset($blocks[0]))
		{
		if ($blocks[0]['blockName'] !== null && is_admin() == false)
		{
			ob_start();

			//echo "<H3>sssss</h3>";

			// var_dump($blocks);

			foreach ($blocks as $block) {

				//echo var_dump($block);
				
				//$acx_block_id = 'acx-block-'.$block_id;

				//echo "<div id='$acx_block_id'>";

				echo render_block($block);

				//echo "</div>";

			}

			$blocks_content = ob_get_clean();

			$data['content'] = $blocks_content;
		}

		
		}

return $data;

}




function airconnex_get_dynamic_link($record_post_id,$dynamic_slug,$dynamic_field)
{

$base_url = get_bloginfo( 'url' );
$dynamic_value = get_post_meta($record_post_id,$dynamic_field,true);
$dynamic_value = sanitize_title($dynamic_value);
$content_link = "$base_url/$dynamic_slug/$dynamic_value ";

return $content_link;

}


// connection id and record (post) id - send back heading / image values and fields (display) and fields_raw (processing)

function airconnex_get_record_values($cid,$rid)
{

$data = array();


$cnx_fields = get_post_meta($cid,'acx_fields',true); // connection fields

$record_values = get_post_meta($rid); // record fields

$fields = array();
$fields_raw = array();
		
		foreach($cnx_fields as $key => $sample_value)
		{
		
		if (isset($record_values[$key][0]))
		{
		$value = $record_values[$key][0];
		$typearray = airconnex_field_typer($value); // check the value type
		$fields[$key] = $typearray['show_value']; // display values 
		$fields_raw[$key] = $typearray['raw_value']; // raw (unchanged) values arrays / etc...
		$fields_showtype[$key] = $typearray['show_type']; // raw (unchanged) values arrays / etc...
		}
		
		}

		$h_field = get_post_meta($cid,'acx_field_h',true);
        $i_field = get_post_meta($cid,'acx_field_i',true);

		$data['fields'] = $fields;
		$data['fields_show'] = $fields_showtype;
		$data['fields_raw'] = $fields_raw;
		
		if (isset($fields[$h_field]))
		{
		$data['heading'] = $fields[$h_field]; // the heading value is required
		}
		
		if (isset($fields[$i_field])) // image is not required, so maybe empty index
		{
		$data['image'] = $fields[$i_field]; // the image value
		}
		//$data['dynamic_url'] = $fields[$i_field]; // commented out, seems unused

return $data;



}


// important - this is for frontend pages, not menus inside the admin

function acx_page_check($post_id ='')
{

	global $wpdb;

	$data = array();

	$post = get_post($post_id);

	if (!$post)
	{
	global $post;
	}


if (isset($post))
{

$post_id = $post->ID;

	$post_title = $post->post_title;

	$data['title'] = $post_title; // non dynamic, non user access

	$acx_access = get_post_meta($post_id,'acx_access',true); // do not move this!

	$content = $post->post_content;

	// takes a post, checks if its dynamic, has access control

	$acx_tid = get_post_meta($post_id,'acx_template',true); // template id

	if ($acx_tid) // its a dynamic post
	{
		$acx_tob = get_post($acx_tid); // template object

		$cid = $acx_tob->post_parent; // connection is parent of template

		$acx_access = get_post_meta($acx_tid,'acx_access',true); // access applied to template

		$acx_rid = get_post_meta($post_id,'acx_record',true);

		$data['title'] = $acx_tob->post_title;  // this can be edited by the user, contain variables etc.

		$data['x_title'] = get_post_meta($acx_tid,'acxp_title',true); // this is the original (unchanged) value / custom post type
		
		$content = $acx_tob->post_content; // template content

		$data['dynamic'] = $acx_tid; // dynamic post template id
		
		// FIELDS ------------
		
		
		$data['record'] = $acx_rid; // record id
			
		$data['connection'] = $cid;
		
		$field_values = airconnex_get_record_values($cid,$acx_rid);
		
		// send the record (post) id and $cid to a function, get back heading / image and fields (display) and fields_raw (processing)

		$data['fields'] = $field_values['fields'];
		$data['fields_raw'] = $field_values['fields_raw'];
		$data['fields_show'] = $field_values['fields_show'];
		$data['heading'] = $field_values['heading'];
		
		if (isset($field_values['image']))
		{
		$data['image'] = $field_values['image'];
		}
		// LINK TO DYNAMIC PAGE ----------
		
		$data['dynamic_slug'] = get_post_meta($acx_tid,'acxp_name',true);// eg shoes		
        $data['dynamic_field'] = get_post_meta($acx_tid,'acxp_dynamic',true);// eg [Brand] 

	}


	

	if (is_numeric($acx_access)) // its has access controls 
	{
		/*
		$data['title'] = $post->post_title; // the original title never gets saved over

		$data['x_title'] = get_post_meta($post_id,'acxp_title',true); // this can be edited by the user, contain variables etc.

		$data['access'] = $acx_access; // the connection

		// what if theres access control but no users?
		$data['users']  = get_post_meta($post_id,'acx_users',true); // no of users in the connection

		$data['u_access'] = acx_get_subuser_record($post_id); // returns array result & record
		
		$u_record = $data['u_access']['record']; //

		if ($u_record)
		{
		//$u_fields = get_post_meta($u_record);// gets all the meta for the user
		$field_values = airconnex_get_record_values($cid,$u_record);
		$data['u_fields'] = $field_values['fields'];
		}
		*/

	}

	// check if its an elementor page

	$is_elementor = get_post_meta($post_id,'_elementor_data',true); // check if its elementor

	if ($is_elementor)
	{
		$data['elementor'] = true;
	}
	
	$data['content'] = $content; 
	
}	
	
	return $data;

}



// DEPRECATED --- returns the record (post id) for current subuser (emulated or real)
function acx_get_subuser_record($post_id)
{

$return = array();

		$acx_access = get_post_meta($post_id,'acx_access',true);

		// admin user - check for login_as
		if ( current_user_can('editor') || current_user_can('administrator') )
		{
		
		$return['result'] = 'is_admin';
		
		$u_record = get_post_meta($post_id,'acx_login_as',true); // get the emulated user
		
		if ($u_record)
		{
		$return['record'] = $u_record;
		$return['result'] = 'is_member';
		}
		
		}
		else // frontend user - check that the user is a member of the user group
		{
		$user = wp_get_current_user();
		$user_id = $user->ID;
		
		if (!$user_id)
		{
		$return['result'] = 'not_user'; // not logged in
		}
		else
		{
		$return['result'] = 'not_member';
		}
		// check and override the result if we are a member
			if ( in_array( 'Airconnex', (array) $user->roles ) ) //is an airconnex user
			{    
			$return['record'] = get_user_meta($user_id,"acx_{$acx_access}_record",true);
			$return['result'] = 'is_member';
			}

		}
		
	return $return;

}
		

// DEPRECATED --- get extended profile and mapping for subuser from the record id

function acx_subuser_check($post_id) 
{
	global $wpdb;

	$data = array();
	$post = get_post($post_id);

	$connection = $post->post_parent;

	$fields = get_post_meta($post_id); // get all the fields / values

	foreach($fields as $key => $value)
	{
	$fields[$key] = $value[0];
	}

	$name_field = get_post_meta($connection,'acx_user_n',true);
	$email_field = get_post_meta($connection,'acx_user_e',true);
	$image_field = get_post_meta($connection,'acx_user_i',true);

	$data['name'] = get_post_meta($post_id,$name_field,true);
	$data['email'] = get_post_meta($post_id,$email_field,true);
	$data['image'] = get_post_meta($post_id,$image_field,true);
	$data['image'] = $data['image'][0]['thumbnails']['large']['url'];
	$data['fields'][$image_field] = $data['image']; // replace the array value with the large thumb value

	//$data['image'] = $img_arr; //

	$data['connection'] = $connection;
	$data['fields'] = $fields;
	
	return $data;

}




// checks the current user (or emulated user) and returns the data as [cid] -> [rid]

function acx_user_data()
{

$response = array();

$user_data = wp_get_current_user();
$user_id = $user_data->ID;
$user_meta = get_user_meta($user_id);
if (is_array($user_meta))
{
$user_meta = array_combine(array_keys($user_meta), array_column($user_meta, '0'));
}
// check if the user is an editor / admin or not

if (current_user_can('editor') || current_user_can('administrator'))
{

$acx_user = get_user_meta($user_id,'acx_login_as',true); //check which user (id) they're emulating (acx_login_as) 

if (!$acx_user)
{
$response['login_as'] = true; // indicates that we need the editor to choose a user...
}

}
else
{
$acx_user = $user_id; // else proceed with the current user id
}


if (is_numeric($acx_user)) // find out which connections the user is a member of ..
{

$response['acx_id'] = $acx_user;

// get all user connections (id)

$user_groups = acx_user_groups_array(); //

foreach ($user_groups as $cid => $data)
{

$record = get_user_meta($acx_user,"acx_{$cid}_record"); // record post id

if ($record)
{
$response['data'][$cid]['title'] = $data['title'];
$response['data'][$cid]['record'] = $record[0]; // build a list of records foreach connection
$conn_fields = airconnex_get_record_values($cid,$record[0]);
$response['data'][$cid]['fields'] = $conn_fields['fields'];
}

}
// end foreach

if (empty($response['data'])){$response['error'] = "No connections for this user";};

}


return $response;

}



// SHOW THE PLUGIN ADMIN PAGE & MENUS

function acx_plugin_admin()
{


	global $wpdb;
	global $plugins_page;

	$data_class = null;
	$pages_class = null;
	$users_class = null;
	$forms_class = null;
	$convars = null;

	if (isset($_GET['cid'])){$cid = $_GET['cid'];}ELSE{$cid = '';}

	if (isset($_GET['main'])){$main = $_GET['main'];}ELSE{$main = '';}

	if (isset($_GET['stage'])){$contype = $_GET['stage'];}ELSE{$contype = '';} // steps in the url override anything else

		$data_class = '';
		$pages_class = '';

	if (!$main)
	{
	$main ='data';
	}

	if (!$main or $main =='data')
	{
	$data_class = "acx-main-active";
	}

	if ($main =='pages')
	{
	$pages_class = "acx-main-active";
	}

	if ($main =='users')
	{
	$users_class = "acx-main-active";
	}

	if ($main =='forms')
	{
	$forms_class = "acx-main-active";
	}


		// LOGO & PAGES / DATA BUTTONS

		echo "
		<div style='display:block;'>

		<div>
		<a href='$plugins_page'>
		<img style='margin-top:45px;margin-bottom:15px;max-width:150px !important;' 
		src='https://dl.airtable.com/.attachmentThumbnails/137ca4314bfd0c95c563e180ace1a4d5/cb28d12f' />
		</a>
		</div>

		<div style='margin-bottom:20px;'>
		<a href='$plugins_page&main=data' class='acx-main acx-main-left $data_class'>Data</a>
		<a href='$plugins_page&main=pages' class='acx-main acx-main-middle $pages_class' >Posts</a>
		<a href='$plugins_page&main=users' class='acx-main acx-main-middle $users_class'>Users</a>
		<a href='$plugins_page&main=forms' class='acx-main acx-main-right $forms_class'>Forms</a>

		</div>

		</div>
		";

	$haskey = acx_sync_api_key('check');
	$bases_array = acx_bases_array();
	$connections_array = acx_connection_array(); // get all pages & data

	if (!$haskey)
	{
	$contype = 'CON_APIKEY';
	}

	if (!$bases_array and !$contype)
	{
	$contype = 'CON_APP_NEW'; //add a base
	}


	if (!$connections_array and !$contype)
	{
	$contype = 'CON_TBLVW';
	$convars['bid'] = array_key_first($bases_array); // send the bid to the
	}


	

	
	
 // MAIN VIEW IF NO FORM 

	if (!$contype)
	{
		if ($main =='data')
		{

			echo "<div class='acx-pages-wrapper'>";

			echo "<div class='acx-pages-container-left'>";

			echo acx_manager_listing($connections_array,'data'); // generate each as an html card
				
			echo "</div>";

			// SELECTED

			echo "<div class='acx-pages-container-right'>";

			// for when a form needs to return to the specific page
			if (isset($_GET['cid']))
			{
				$cid = $_GET['cid'];
			}else {
				$cid = 0;
			}

			echo acx_connection_single($cid);

			echo "</div>";

			echo "</div>";


			
		}

		if ($main=='pages') // NO CONNECTION, SHOW PAGES
		{

			// PAGES LISTING

			echo "<div class='acx-pages-wrapper'>";

			echo "<div class='acx-pages-container-left'>";

			$page_data = acx_page_manager_array(); // get all pages & data

			echo acx_manager_listing($page_data,'pages'); // generate each as an html card
				
			echo "</div>";

			// PAGE AREA

			echo "<div class='acx-pages-container-right'>";

			// for when a form needs to return to the specific page
			if (isset($_GET['pid']))
			{
				$pid = $_GET['pid'];
			}else {
				$pid = 0;
			}

			echo acx_page_manager_single($pid);

			echo "</div>";

			echo "</div>";

		}

		if ($main=='users') // NO CONNECTION, SHOW PAGES
		{

			// USERS LISTING

			echo "<div class='acx-pages-wrapper'>";

			echo "<div class='acx-pages-container-left'>";

			$user_groups = acx_user_groups_array(); // get all pages & data

			echo acx_manager_listing($user_groups,'users'); // generate each as an html card
				
			echo "</div>";

			// SELECTED

			echo "<div class='acx-pages-container-right'>";

			// for when a form needs to return to the specific page
			if (isset($_GET['cid']))
			{
				$cid = $_GET['cid'];
			}else {
				$cid = 0;
			}

			echo acx_user_group_single($cid);

			echo "</div>";

			echo "</div>";

		}

		if ($main=='forms') // NO CONNECTION, SHOW PAGES
		{
			echo "<div style='padding:50px;text-align:center;' class='acx-pages-wrapper'>";
			echo "<h2>Coming Soon...</h2>";
			echo "<p>This feature will be added shortly.</p>";
			echo "</div>";
		}
		


	}



	if ($contype) // showing a form
	{
					$setup = acx_setup_template($contype,$convars);
					
					echo $setup[0]; //html
					echo $setup[1]; // script
					
	}




	
} // end function



// SHOW THE FRONTEND / EDITOR AIRCONNEX MENUS

add_action("wp_footer", "airconnex_menu_editor");
add_action("admin_footer", "airconnex_menu_editor");

// checks if the wp_user is an admin, only show floating menus on frontend if yes
function airconnex_menu_editor()
{

  // if admin or editor is logged in, show the menus

    if ( current_user_can('editor') || current_user_can('administrator') )
    {

      global $pagenow;
	  
	  $menu_config = null;
      
          if ($pagenow == 'post.php') 
          {
          $menu_config = 'editor';
          }
          
          if ( ! is_admin() ) 
          {
          $menu_config = 'frontend';
          }

          if($menu_config)
          {
          echo airconnex_click_copy_css();
          echo airconnex_fontawesome();
          echo airconnex_menu_html_js(); // script for the menu
          echo airconnex_menu_float($menu_config);
          }
    }

}




// used to get labels for select field
function acx_application_label($bid)
{

	$app_name = get_post_meta($bid,'acx_name',true);
	$app_color = get_post_meta($bid,'acx_color',true);

	$label = "<div style='display:inline-block'><span style='height: 25px;
	width: 25px;
	background-color: $app_color;
	border-radius: 50%;border:1px solid silver;
	display: inline-block;'></span><span style='line-height:25px;'>$app_name</span></div>";

	return $label;

}







