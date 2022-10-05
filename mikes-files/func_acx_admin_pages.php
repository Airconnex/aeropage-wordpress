<?php

function acx_connection_single($cid) // viewing the connection in the manager (right) window...
{

$html = null;
$last_sync = null;
$fields = null;
$error = null;
$error_msg = null;

global $plugins_page;



	if ($cid == 0)
	{
		$html .= "<h2>Add Data</h2>";
		$html .= "<p>To begin adding from Airtable, choose which base you want to connect to.</p>";
		$html .= "<a class='acx-button acx-outline' href='$plugins_page&stage=CON_APP'>+ New Connection</a><br>";
		$html .= "<p>To change your api key, <a class='acx-text' href='$plugins_page&stage=CON_APIKEY'>click here.</a></p>";
	}
	else 
	{
		$connection_data = acx_connection_array($cid);
		$connection = $connection_data[$cid];
		extract ($connection);
		
	    $delete_btn = "<a style='color:red;border:2px solid red;' href='$plugins_page&stage=CON_DELETE&cid=$cid' class='acx-button acx-small acx-outline'>
		<span style='font-size:16px;line-height:18px;margin-right:5px;' class='dashicons dashicons-trash'></span>
		Delete
		</a>";
		
		$edit_link = "<a href='$plugins_page&stage=CON_EDIT&cid=$cid' >Edit Details</a>";
				
		$html .= "<h2>$title (#$cid)</h2>";

		$html .= "<p>$sync_link&nbsp;&nbsp;|&nbsp;&nbsp;$airtable_link&nbsp;&nbsp;|&nbsp;&nbsp;$edit_link</p>";

		if ($error == 'NO_FIELDS' OR $error == 'NO_RECORDS')
		{
		$html .= $error_msg;
		}
		
		if ($error == 'NO_MAPPING')
		{
		$html .= $error_msg;
		$html .= "<p><a href='$plugins_page&stage=LOOP_MAPPING&cid=$cid' >Field Mapping</a></p></br>";
		}
		
		if ($error)
		{
		$html .= $delete_btn;
		return $html; // exit here
		}
		

		$html .= "<br><span style='color:grey;'>Last sync : $last_sync</span><br><br>";
		
			$html .= "<h3>Fields & Values</h3>";

			$html .= "<p><a href='$plugins_page&stage=LOOP_MAPPING&cid=$cid' >Field Mapping</a></p></br>";
			

	


		$html .= "<h2>Preview ($count Records)</h2>
		<p>$app_name</p>
		";
		
		// special loop type (USER / LOGIN_AS)
		$html .= acx_loop_compiler($cid,'1000','PREVIEW'); 
		
		$html .= $delete_btn;
		
	}

	

	return $html;
}


function acx_page_manager_single($pid)
{

	global $plugins_page;
	
			$delete_btn = "<a style='color:red;border:2px solid red;' href='$plugins_page&stage=PAGE_DELETE&pid=$pid' class='acx-button acx-small acx-outline'>
		<span style='font-size:16px;line-height:18px;margin-right:5px;' class='dashicons dashicons-trash'></span>
		Delete
		</a>";


	if ($pid == 0)
	{
		$html = "<h2>Add Dynamic Posts</h2>";

		$links = array();

		//$links[0]['text'] = 'User Portal';
		//$links[0]['icon'] = 'dashicons-media-code';
		//$links[0]['stage'] = 'PORTAL_CREATE';
		//$links[0]['notes'] = "Create a page where users can login before seeing the content. Use values from the user record in the page content to customize dashboards.";

		$links[1]['text'] = 'Create Posts';
		$links[1]['icon'] = 'dashicons-media-code';
		$links[1]['stage'] = 'PAGE_ADD_CONN';
		$links[1]['notes'] = "Create a post for each record in your Airtable base, then map values into the page to create unique content.";

		foreach ($links as $key => $value)
		{
			extract ($value);

			$html .= "<a href='$plugins_page&stage=$stage' class='acx-button acx-outline' >+ $text</a><p>$notes</p><br>";
		}

		// normal page
		// portal page? -- add page & page access control
		// dynamic page -- dynamic sequence
	}
	else 
	{
		$page_data = acx_page_manager_array($pid);

		$page = $page_data[$pid];

		extract ($page); //
	
		$html = "<h2>$title <span style='font-size:14px;'>($pid)</span></h2>";


		if ($dynamic)
		{
			$view_link = admin_url()."edit.php?post_type=$type";
			$type = 'Dynamic Post';
			
		}
		else 
		{
			$view_link = get_post_permalink($pid);
		}

		$type = ucwords($type);

		$html .= "<br><h3>Page Type</h3>
		<p><span class='dashicons $icon' style='margin-right:5px'></span><b>$type</b>
		<a target='_blank' href='$view_link'>[view]</a></p>"; //target='blank'


		foreach ($page as $key => $value)
		{
			//$html .= "<h4>".ucwords($key)."</h4>";
			//$html .= "<p>$value</p>";
		}


		//---------------------------------------------------------------------------------


		//$access -- the id of the connection (users) with access to this page

	
		
		$access_icon = 'dashicons-admin-site-alt3';
		$access_title = 'Public';
		$access_link = "$plugins_page&stage=PAGE_ACCESS&pid=$pid";
		$access_css = null;
		
		if ($access)
		{
		
		$access_users = acx_connection_array($access); // get all connections
		
		if (isset($access_users[$access]))
		{
		$access_users = $access_users[$access];
		}
		
		if (is_array($access_users))
		{
		
		extract($access_users, EXTR_PREFIX_ALL, "access");
			
				$access_css = "color:$access_app_color !important;";
				$access_css = "color:$access_app_color !important;";
				$access_icon = 'dashicons-admin-users';
				$access_title = "$access_app_name > $access_title";
				// removes page control (meta) from the post and refreshes.
				$access_link = "$plugins_page&stage=PAGE_ACCESS_DEL&pid=$pid";
			
		}
		
		}
		//dashicons-admin-users

		$html .= "<br><Br><h3>Page Access</h3><p><span class='dashicons $access_icon' style='$access_css;margin-right:5px'></span>
		<b>$access_title</b>  <a href='$access_link'>[edit]</a></p>";
		//$html .= $access_link;
		
		
		$html .= "<br><Br>".$delete_btn;


	}
	

	return $html;

}


function acx_user_group_single($cid)
{
	global $plugins_page;
	
	$users_list = null;
	
		$delete_btn = "<a style='color:red;border:2px solid red;' href='$plugins_page&stage=USERS_DELETE&cid=$cid' class='acx-button acx-small acx-outline'>
		<span style='font-size:16px;line-height:18px;margin-right:5px;' class='dashicons dashicons-trash'></span>
		Delete
		</a>";

	if ($cid == 0)
	{
		$html = "<h2>Add Users</h2>";
		$html .= "<p>To begin adding users from your Airtable data, choose which connection to import users from.</p>";

		$connections = acx_connection_array(); // get all connections

		foreach ($connections as $id => $connection)
		{
			extract ($connection);
			
			if (!$users or $users === 0) //only show ones without a user group
			{
				$users_list .= "<a class='acx-button acx-outline' href='$plugins_page&stage=USER_MAPPING&cid=$id'>$title</a><br>";
			}
		}

		if ($users_list)
		{
			$html .= $users_list;
		}
		else {
			$html .= "<br><h3 style='color:silver'>No connections...</h3>
			<p style='color:silver'>Each connection can be used for one user group. To add more users you will need more connections.</p></br>";
			$html .= "<a  href='$plugins_page&main=data'>+ View data</a><br>";
		}
		// foreach show a link to 

	}
	else // SHOWING THE LIST FOR A CHOSEN CONNECTION
	{

	$connection_data = acx_connection_array($cid);
	$connection = $connection_data[$cid];
	extract ($connection);


		$html = "<h2>$title ($users Users)</h2>";
		$html .= "<p>Any records without an email value will be automatically removed from the results below.</p>";

		$html .= "<p>$sync_link &nbsp;&nbsp;|&nbsp;&nbsp; $airtable_link</p>";

		// special loop type (USER / LOGIN_AS)
		$html .= acx_loop_compiler($cid,'1000','USERS'); 
		
		
		$html .= $delete_btn;
	
	}

	return $html;
}



// this ajax is called by user / page / data selection
// gets sent 'view' (page,user,data) and 'vid' (generic id)

add_action('wp_ajax_airconnex_admin_select', 'airconnex_admin_select');

function airconnex_admin_select()
{
		
		$vid = $_POST['vid']; // (generic id)
		$view = $_POST['view'];
		
		$response = array(); // response goes back to the form / js

		//Always check the nonce for security purposes!!!
		if(isset($_POST['nonce']) && wp_verify_nonce($_POST['nonce'], $_POST['action'])){

			if ($view == 'pages')
			{
			$response['html'] =  acx_page_manager_single($vid);
			}

			if ($view == 'users')
			{
			$response['html'] =  acx_user_group_single($vid);
			}

			if ($view == 'data')
			{
			$response['html'] =  acx_connection_single($vid);
			}
			
			$response['status'] = 'success';

			wp_die(json_encode($response));

		}else{
		//Return an error status if things result to a bad state.
		//You can omit this line and just return an HTML data with the error message instead.
		wp_die(json_encode([
			"status" => "error",
			"html" => "invalid request."
		]));
		}
}





// get the data for pages listing
function acx_page_manager_array($id = '')
{

		global $wpdb;

		$html = '';

		if (get_option( 'show_on_front') == 'page')
		{
		$home_id = get_option( 'page_on_front' );
		}
		else {
		$home_id = '';
		}

		if ($id)
		{
		// get only the requested page...

		$airconnex_pages = array();

		$airconnex_pages[$id] = get_post($id);

		}else {
				// get all normal pages that are published

				$airconnex_pages = get_posts(['post_type' => 'page','post_status' => 'publish','numberposts' => -1]);

				// get all airconnex dynamic pages

				$airconnex_posts = get_posts(['post_type' => 'acx-template','post_status' => 'private','numberposts' => -1]);

				if (is_array($airconnex_posts))
				{
				$airconnex_pages = array_merge($airconnex_pages,$airconnex_posts);
				}

		}

        $page_data = array();

		foreach ($airconnex_pages as $post)
		{
			$post_id = $post->ID;
			$post_title = $post->post_title;
			$post_type = $post->post_type;
			$post_slug = $post->post_name;
			
			$post_dynamic = null;
			$page_access = null;
			$post_dynamic_title = null;

			if ($post_type=='acx-template')
			{
			$post_dynamic_title = $post_title;
			$post_type = $post_slug; // system registers custom post type with template slug name
			$post_dynamic = get_post_meta($post_id,'acxp_dynamic',true);
			$post_title = get_post_meta($post_id,'acxp_title',true);
			$post_slug = $post_slug.'/['.$post_dynamic.']';
			}

			$access = get_post_meta($post_id,'acx_access',true); // connection id of user group
			$login_as = get_post_meta($post_id,'acx_login_as',true); // record id of the sample user 

			if ($access)
			{
			//$post_title = get_post_meta($post_id,'acxp_title',true);
			}


			//$accs_icon = 'dashicons-admin-site-alt3'; // default to public

			$icon = 'dashicons-tablet'; // default to normal page

			if ($home_id == $post_id)
			{
			$icon = 'dashicons-admin-home';
			}
					
			if ($post_dynamic){$icon = 'dashicons-star-empty';}
			
		
            $id = $post_id;
			$page_data[$id]['title'] = $post_title;
			$page_data[$id]['dynamic_title'] = $post_dynamic_title;
			$page_data[$id]['type'] = $post_type;
			$page_data[$id]['slug'] = $post_slug;
			$page_data[$id]['dynamic'] = $post_dynamic;
            $page_data[$id]['icon'] = $icon;
            $page_data[$id]['access'] = $access;
			$page_data[$id]['login_as'] = $login_as;

		}

        return $page_data;

}



// get the data for users (groups) listing

function acx_user_groups_array($id = '')
{

	$user_groups = array();
	
	$connections = acx_connection_array(); //

	// foreach connection, see if it has users (config)

	foreach ($connections as $id => $values)
		{
		if ($values['users'] > 0)
		{
			$user_groups[$id] = $values; //add to the array
		}
		}

		return $user_groups;

}




function acx_manager_listing($list_data,$list_type = '')
{

	global $plugins_page;
	

	$icon = null;
	$icon_css = null;

	// append add button to the top

		$list_data[0] = 'add'; // add a 0 

		ksort($list_data);


    foreach ($list_data as $id => $values)
    {

	if ($list_type == 'pages')
	{
	$urlid = 'pid';

		if ($id == 0)
		{
			$title = 'Add Dynamic Posts';
			$subtitle = 'Create new posts...'; //has_users
			$icon = 'dashicons-plus';
		}
		else {

			$title = ucwords($values['title']);
			$icon = $values['icon'];
			$subtitle = $values['slug'];

			if ($values['access'])
			{
				$access = $values['access'];
				$title = "<span style = 'color:#e605aa;margin:0 5px 0 -2px;font-size:18px;' class='dashicons dashicons-lock'></span>$title";
			}

		}
		
	}

	if ($list_type == 'users')
	{
	$urlid = 'cid';
	
		if ($id == 0)
		{
			$title = 'Add Users';
			$subtitle = 'Create wordpress users'; //has_users
			$icon = 'dashicons-plus';
		}
		else {
			$title = $values['title'];
			$subtitle = $values['table']; //has_users
			$icon = 'dashicons-buddicons-buddypress-logo';
		}
		
	}

	if ($list_type == 'data')
	{
	$urlid = 'cid';

		if ($id == 0)
		{
			$title = 'Add Data';
			$subtitle = 'Create a connection to Airtable'; //has_users
			$icon = 'dashicons-plus';
		}
		else {
			
			$title = $values['title'];
			$color = $values['app_color'];
			$subtitle = $values['app_name']; //has_users
			$icon = 'dashicons-database';
			
			if (isset($values['error'])) // not stored in database, is returned by conneciton array
			{
			$color = 'orange';
			$icon = 'dashicons-warning';
			}
			
			$icon_css = "color:$color";
			
		}


	}
	//   <div class='acx-page-icon-box'><span id='acx-page-icon' class='dashicons '></span></div>

	//id='acx-page-button-$id' 
	
	//			<a href='javascript:void(0);' id='acx-page-button' data-view='{$list_type}' data-vid='{$id}'>
//id='acx-page-button-$id' 

//<span class='dashicons dashicons-arrow-right-alt2'></span>
	
	if (!isset($html)){$html = null;}
	
	if (isset($_GET[$urlid])){$vid = $_GET[$urlid];}ELSE{$vid = '';}

	if ($vid == $id){$border_css = 'border:2px black solid;';}else{$border_css = 'border:1px silver solid';}

    $html .= "
    <div class='acx-page-row'>
	<a style='color:black;' href= '$plugins_page&main=$list_type&$urlid=$id'>
    <div style='display: inline-block;$border_css' >
    <div style='border-left:2px solid #ebeff3' class='acx-page-icon-box'>
	<span id='acx-page-icon' style='$icon_css' class='dashicons $icon'></span>
	</div>
	
        <div class='acx-page-box '>
            <h4>$title</h4>
            <p>$subtitle</p>
			
        </div>
		
    </div>
	</a>
    </div>
	";
    }

	return $html;

}

// shows in connection 'add connection' initial page
function acx_bases_array()
{
    global $wpdb;

	$return = array();

	$applications = get_posts(['post_type' => 'acx-application','post_status' => 'private','numberposts' => -1]);

	foreach ($applications as $application)
	{

	$acx_bid = $application->ID;

	$return[$acx_bid]['app'] = get_post_meta($acx_bid,'acx_app',true);
	$return[$acx_bid]['name'] = get_post_meta($acx_bid,'acx_name',true);
	$return[$acx_bid]['color'] = get_post_meta($acx_bid,'acx_color',true);


	}

	return $return;


}


function acx_connection_array($id = '')
{
    global $wpdb;


	if ($id) // get only the requested page...
	{
	$airconnex_connections = array();
	$airconnex_connections[$id] = get_post($id);
	}
	else 
	{
    $airconnex_connections = get_posts(['post_type' => 'acx-connection','post_status' => 'private','numberposts' => -1]);
	}

    $c_array = array();

    foreach ($airconnex_connections as $connection)
    {
            $cid = $connection->ID;
			$bid = get_post_meta($cid,'acx_bid',true);
			
			$conn_app = get_post_meta($bid,'acx_app',true); // app id from airtable
			$conn_table = get_post_meta($cid,'acx_table',true); 
			$conn_view = get_post_meta($cid,'acx_view',true); 
			$conn_fields = get_post_meta($cid,'acx_fields',true); 
			$conn_count = get_post_meta($cid,'acx_count',true);
			
			$c_array[$cid]['app'] = $conn_app;
            $c_array[$cid]['app_name'] = get_post_meta($bid,'acx_name',true);
            $c_array[$cid]['app_color'] = get_post_meta($bid,'acx_color',true);
            $c_array[$cid]['title'] = $connection->post_title;
            $c_array[$cid]['table'] = $conn_table; 
            $c_array[$cid]['view'] = $conn_view; 
            $c_array[$cid]['fields'] = $conn_fields;
            $c_array[$cid]['airtable_link'] = "<a target='_blank' href='https://airtable.com/$conn_app/$conn_table/$conn_view'>View in Airtable</a>";
			$c_array[$cid]['users'] = get_post_meta($cid,'acx_users',true);
			$c_array[$cid]['count'] = $conn_count;
			


			$conn_field_h = get_post_meta($cid,'acx_field_h',true); // default mapping (required) ...
            $conn_user_e = get_post_meta($cid,'acx_user_e',true); // default user fields...
            $conn_user_n = get_post_meta($cid,'acx_user_n',true);
			

			$c_array[$cid]['sync_link'] = airconnex_sync_button($cid,'link');

			$conn_last_sync = get_post_meta($cid,'acx_last_sync',true);

			if (is_numeric($conn_last_sync))
			{
			$nowtime = time();
			$secs = $nowtime-$conn_last_sync;
			$time_elapsed = acx_time_elapsed($secs);
			$c_array[$cid]['last_sync'] = $time_elapsed; // time since last sync
			}
			
			$warning_icon = "<span style='margin-right:5px;color:orange;' class='dashicons dashicons-warning'></span>";
			
			
			
			if (!is_array($conn_fields))
			{
			$c_array[$cid]['error'] = 'NO_FIELDS';
			$c_array[$cid]['error_msg'] = "<br><p>$warning_icon <b>Something is wrong with this connection...</b></p><p> We got a response from airtable but it contained no data. Please make sure every field has at least one record with a value and syncronize again.</p>";
			}
			
			if (!$conn_count)
			{
			$c_array[$cid]['error'] = 'NO_RECORDS';
			$c_array[$cid]['error_msg'] = "<br><p>$warning_icon <b>Something is wrong with this connection...</b></p><p> We got a response from airtable but it contained no data. Please make sure every field has at least one record with a value and syncronize again.</p>";
			}
			
					
			if (!$conn_field_h)
			{
			$c_array[$cid]['error'] = 'NO_MAPPING';
			$c_array[$cid]['error_msg'] = "<br><p>$warning_icon This connection hasn't been configured with a primary name field.</p>";
			}
			
			
			
            
     }

     return $c_array;

}


function acx_time_elapsed($secs){
    
if ($secs > 0)
{
	$bit = array(
        ' year'        => $secs / 31556926 % 12,
        ' week'        => $secs / 604800 % 52,
        ' day'        => $secs / 86400 % 7,
        ' hour'        => $secs / 3600 % 24,
        ' minute'    => $secs / 60 % 60,
        ' second'    => $secs % 60
        );
       
    foreach($bit as $k => $v){
        if($v > 1)$ret[] = $v . $k . 's';
        if($v == 1)$ret[] = $v . $k;
        }

	if (is_array($ret))
	{
	if (count($ret) > 1){$join = 'and';}else{$join = null;}
    array_splice($ret, count($ret)-1, 0, $join);
    
	$ret[] = 'ago.';
    return join(' ', $ret);
	}

}
else
{
return 'Now...';
}
	
}
	








add_action('admin_head', 'acx_selector_javascript');

function acx_selector_javascript() 
{
		$action = 'airconnex_admin_select'; //Action for the ajax function
		$nonce = wp_create_nonce($action); //Nonce for security
		?>
		<script type="text/javascript" >
			jQuery(document).ready(function($) {
			$(document).on('click', '#acx-page-button', function(){
				var vid = $(this).data('vid')
				var view = $(this).data('view')
				var data = {
				action: '<?=$action?>',
				vid: vid,
				view: view,
				nonce: '<?=$nonce?>'
				};

				// remove acx-page-button-active from any child of acx-page-button

				$("#acx-page-button .acx-page-button-active").removeClass("acx-page-button-active");
				
				$('#acx-page-button-' + vid).addClass('acx-page-button-active');

				$.post(ajaxurl, data, function(response) {
				var data = $.parseJSON(response),status = data['status'],html = data['html']
				

				if (status == 'success')
				{
					
					//$('#acx-page-button-' + pid).addClass('dashicons-yes-alt');
					//$('#acx-page-button-' + pid).addClass('acx-sync-success');

					$('.acx-pages-container-right').html(html)
				

				}

				

				});
			});
			});
		</script>
		<?php

}

