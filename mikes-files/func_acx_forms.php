<?php



function acx_setup_template($stage,$convars ='')
{

global $wpdb;
global $plugins_page;

$cid = null;
$pid = null;
$bid = null;
$next = null;
$body_html = null;
$links = null;
$button = null;
$image = null;
$instructions = null;
$below = null;
$paragraph = null;
$title = null;
$connection = null;
$loop_html = null;
$value = null;
$form = null;
$notes = null;
$conn_sync_link = null;
$conn_airtable_link = null;
$conn_title = null;
$conn_error = null;
$selected = null;
$goback_url = null;

if (isset($_GET['cid']))
{
$cid = $_GET['cid']; // steps in the url override anything else
}
if (isset($_GET['pid']))
{
$pid = $_GET['pid'];
}
if (isset($_GET['bid']))
{
$bid = $_GET['bid'];
}

if (is_array($convars))
{
extract($convars); // can contain bid / cid / pid
}

	
if ($cid)
{
$connection_data = acx_connection_array($cid);
$connection = $connection_data[$cid];
extract($connection,EXTR_PREFIX_ALL,'conn'); //refer to admin_pages acx_connection_array for list of vars this creates

// if the conneciton has an error, redirect to a warning for anything except deletion

if ($stage !== 'CON_DELETE' and $conn_error) 
{
if ($stage == 'LOOP_MAPPING' and $conn_error == 'NO_MAPPING')
{
$stage = 'LOOP_MAPPING';
}
else
{
$stage = 'LOOP_ERROR';
}
}


}
// end if cid
	

$error_msg = get_post_meta($cid,'acx_error',true);

if (isset($_SERVER['HTTP_REFERER']))
{
$goback_url = $_SERVER['HTTP_REFERER'];
}
$goback_txt = "Go Back";



//--------------------

    if ($stage == 'PORTAL_CREATE')
    {
        // check user groups array
        $user_groups = acx_user_groups_array(); // get all pages & data

        // if empty, show a message and a linke
        if (!$user_groups)
        {
            $stage = 'PORTAL_WARNING';
        }
    }


    $contents = array();

    //--------------------------

    $contype = 'CON_APIKEY';

    $contents[$contype]['title'] = "Your Airtable Account.";
    $contents[$contype]['paragraph'] = "To connect to your airtable account, please submit your API Key below.";

    $contents[$contype]['form'][0]['name'] = 'api_key';
    $contents[$contype]['form'][0]['label'] = "Your API Key *";
    $contents[$contype]['form'][0]['type'] = "password";
    $contents[$contype]['form'][0]['options'] = "required autocomplete='off'";

    $haskey = acx_sync_api_key('check');

    if (!$haskey)
    {
    $contents[$contype]['form'][0]['empty'] = true;
    }

    $contents[$contype]['below'] = "<a target='_blank' href='https://airtable.com//account'>Open Airtable ...</a>";


    $contents[$contype]['instructions'] = "<h4>Instructions</h4>

    <p>Your Airtable API key is used to let third party applications connect to your airtable data. It can be found on your Airtable Account page</p>

    <br>";

    $contents[$contype]['image'] = "https://dl.airtable.com/.attachments/041ae0e563747f07a5a6a870150fe8a3/3657fe01/a3ZvB9KFjf.gif";


    //--------------------------

    $contype = 'CON_APP';

    $contents[$contype]['title'] = "Choose a Base";
    $contents[$contype]['paragraph'] = "Choose a base from Airtable to begin setting up the connection.";

    $contents[$contype]['form'][0]['name'] = 'acx_bid';
    $contents[$contype]['form'][0]['label'] = "Choose an Application *";
    $contents[$contype]['form'][0]['type'] = "select_base";
    $contents[$contype]['form'][0]['options'] = "required";

    $contents[$contype]['below'] = "<a href='$plugins_page&stage=CON_APP_NEW'>+ New Base</a>";

    //$contents[$contype]['image'] = "https://dl.airtable.com/.attachmentThumbnails/998bb32fbe5a78160a2fcaed768bff50/353646de";


    //--------------------------

    $contype = 'CON_APP_NEW';

    $contents[$contype]['title'] = "Connect a Base";
    $contents[$contype]['paragraph'] = "Add a base from Airtable to begin setting up the connection.";


    $contents[$contype]['form'][1]['name'] = 'acx_name';
    $contents[$contype]['form'][1]['label'] = "Name *";
    $contents[$contype]['form'][1]['type'] = "text";
    $contents[$contype]['form'][1]['options'] = "required";


    $contents[$contype]['form'][2]['name'] = 'acx_color';
    $contents[$contype]['form'][2]['label'] = "Color *";
    $contents[$contype]['form'][2]['type'] = "color";
    $contents[$contype]['form'][2]['options'] = "required";


    $contents[$contype]['form'][3]['name'] = 'acx_app';
    $contents[$contype]['form'][3]['label'] = "Application ID *";
    $contents[$contype]['form'][3]['type'] = "text";
    $contents[$contype]['form'][3]['options'] = "required";

    $contents[$contype]['instructions'] = "<h4>Instructions</h4>

    <p>To connect to Airtable we need to specify the base using an application id. Because the id is just a code, you should add the <b>name and the color</b> to match those used in Airtable so you can keep track of the connections.</p>

    <p>To find the <b>Application ID</b>, open your base in Airtable and click the help icon at the top right. 
    Then click API Documentation. The app id can be found in the url as shown, and at the beginning 
    of the documentation.</p>

    <br>";

    $contents[$contype]['image'] = "https://dl.airtable.com/.attachmentThumbnails/998bb32fbe5a78160a2fcaed768bff50/353646de";


    //Open your base in Airtable> Click the (?) icon at the top right > Click on 'API Documentation' and your application id will be shown on the page.

    //--------------------------

    $contype = 'CON_TBLVW';

    $contents[$contype]['title'] = "Add Table & View";
    $contents[$contype]['paragraph'] = "You can copy and paste these from your base in Airtable. Only records visible in the view will be syncronized.";

    $contents[$contype]['form'][0]['name'] = 'acx_connect_url';
    $contents[$contype]['form'][0]['label'] = "Table / View URL";
    $contents[$contype]['form'][0]['type'] = "text";
    $contents[$contype]['form'][0]['options'] = "required";


    $contents[$contype]['form'][1]['name'] = 'acx_connect_label';
    $contents[$contype]['form'][1]['label'] = "Connection Name *";
    $contents[$contype]['form'][1]['type'] = "text";
    $contents[$contype]['form'][1]['options'] = "required";
    $contents[$contype]['form'][1]['notes'] = "Show data from Airtable or link to external urls ";




    $contents[$contype]['instructions'] = "<h4>Instructions</h4>

    <p>The last step is to add a specific table / view which contains the data you want to import.</p>

    <p>To find the URL just open Airtable in a browser so that you can see the view you want to import - and copy / paste the url into the form.</p>

    <p>The connection name is a label for your own reference. It can be match the table name, or the table and view (if both are relevant).</p>

    <br>";



    //-------------------------
	
	$contype = 'CON_EDIT';
    $contents[$contype]['title'] = "Edit $conn_title";
	
	$paragraph_html = "<p>$conn_airtable_link</p>";
		
    $contents[$contype]['paragraph'] = $paragraph_html;

	$contents[$contype]['form'][0]['name'] = 'connection_title';
    $contents[$contype]['form'][0]['label'] = "Title";
    $contents[$contype]['form'][0]['type'] = "text";
    $contents[$contype]['form'][0]['options'] = "required";
	
	
	//-----------------

    $contype = 'CON_TYPE'; // no longer used

    $contents[$contype]['title'] = "Create with Wordpress";
    $contents[$contype]['paragraph'] = "You can copy and paste these from your base in Airtable. Only records visible in the view will be syncronized.";

    //Just an option at the end of the setup process. Any connection can be setup for multiple types.
    // users, pages, links

    $contents[$contype]['links'][0]['text'] = 'Dynamic Posts';
    $contents[$contype]['links'][0]['icon'] = 'dashicons-media-code';
    $contents[$contype]['links'][0]['stage'] = 'PAGE_CREATE';
    $contents[$contype]['links'][0]['notes'] = "Show data from Airtable or link to external urls ";


    $contents[$contype]['links'][1]['text'] = 'Users Accounts';
    $contents[$contype]['links'][1]['icon'] = 'dashicons-admin-users';
    $contents[$contype]['links'][1]['stage'] = 'USER_MAPPING';
    $contents[$contype]['links'][1]['notes'] = "Create a user for each record, with an email field.";

    $contents[$contype]['button'] = 'Submit'; // 


    //--------------------------

    $contype = 'LOOP_MAPPING';

    $contents[$contype]['title'] = "Loop Mapping";

	$paragraph_html = "Select the fields to use by default.";
	$paragraph_html .= "<p>$conn_sync_link &nbsp;&nbsp;|&nbsp;&nbsp; $conn_airtable_link</p>";
		
    $contents[$contype]['paragraph'] = $paragraph_html;
	
    $contents[$contype]['form'][0]['name'] = 'acx_field_h';
    $contents[$contype]['form'][0]['label'] = "Heading*";
    $contents[$contype]['form'][0]['type'] = "select";
    $contents[$contype]['form'][0]['options'] = "required"; // only name / heading is required
	$contents[$contype]['form'][1]['filter'] = "string"; // prevent invalid field types from being shown

    $contents[$contype]['form'][1]['name'] = 'acx_field_p';
    $contents[$contype]['form'][1]['label'] = "Paragraph";
    $contents[$contype]['form'][1]['type'] = "select";
    //$contents[$contype]['form'][1]['options'] = "required";

    $contents[$contype]['form'][2]['name'] = 'acx_field_i';
    $contents[$contype]['form'][2]['label'] = "Image";
    $contents[$contype]['form'][2]['type'] = "select";

    $contents[$contype]['form'][3]['name'] = 'acx_field_l';
    $contents[$contype]['form'][3]['label'] = "URL";
    $contents[$contype]['form'][3]['type'] = "select";

    $contents[$contype]['button'] = 'Preview'; // 
    $contents[$contype]['next'] = '&stage=CON_TYPE'; // 
	
	
	//---------------------
	
	$contype = 'LOOP_ERROR';
	
	$contents[$contype]['title'] = "Error : No Fields in $conn_title";
	
	$paragraph_html = "The data was fetched but no records or fields were found. Please make sure every field has at least one value and syncronize again.";
	//$paragraph_html .= "<p>$conn_sync_link &nbsp;&nbsp;|&nbsp;&nbsp; $conn_airtable_link</p>";

	$contents[$contype]['paragraph'] = $paragraph_html;

	//$contents[$contype]['button'] = 'Details'; // 
	//$contents[$contype]['next'] = "&main=data&cid=$cid"; // 
	
	$contents[$contype]['links'][0]['text'] = 'View Details';
    $contents[$contype]['links'][0]['icon'] = 'dashicons-media-code';
    $contents[$contype]['links'][0]['url'] = "&main=data&cid=$cid";

    //--------------------------


    $contype = 'USER_MAPPING';

	$contents[$contype]['title'] = "User Mapping from $conn_title (#$cid)";
	
	$paragraph_html = "Select the field values to use when creating users, then click preview to see the results and continue.";
	$paragraph_html .= "<p>$conn_sync_link &nbsp;&nbsp;|&nbsp;&nbsp; $conn_airtable_link</p>";
		
    $contents[$contype]['paragraph'] = $paragraph_html;

    $contents[$contype]['form'][1]['name'] = 'acx_user_e';
    $contents[$contype]['form'][1]['label'] = "Email Address*";
    $contents[$contype]['form'][1]['type'] = "select";
	$contents[$contype]['form'][1]['filter'] = "string"; // prevent invalid field types from being shown
    $contents[$contype]['form'][1]['options'] = "required";

    $contents[$contype]['form'][0]['name'] = 'acx_user_n';
    $contents[$contype]['form'][0]['label'] = "Name";
    $contents[$contype]['form'][0]['type'] = "select";
	$contents[$contype]['form'][0]['filter'] = "string"; // prevent invalid field types from being shown

    $contents[$contype]['form'][2]['name'] = 'acx_user_i';
    $contents[$contype]['form'][2]['label'] = "Profile Image";
    $contents[$contype]['form'][2]['type'] = "select";
    //$contents[$contype]['form'][2]['options'] = "required";

    $contents[$contype]['button'] = 'Preview'; // 
    $contents[$contype]['next'] = '&stage=USER_CREATE'; // 


    //--------------------------

    $contype = 'USER_CREATE'; // this step doesnt sync it always redirects to the next step, next step syncs

    $contents[$contype]['title'] = "Users Connected.";


	$paragraph_html = "The users have been succesfully imported. You can see the results in the right panel.";
	$paragraph_html .= "<p>$conn_sync_link &nbsp;&nbsp;|&nbsp;&nbsp; $conn_airtable_link</p>";
		
    $contents[$contype]['paragraph'] = $paragraph_html;


    $contents[$contype]['form'] = true;

    $contents[$contype]['button'] = 'Continue'; // 

    //----------------------------

    $contype = 'PAGE_ADD_CONN';

    $contents[$contype]['title'] = "Choose a Connection.";
    $contents[$contype]['paragraph'] = "To create dynamic pages from your airtable data, choose the connection that contains the records that will become pages.";

    $contents[$contype]['form'][0]['name'] = 'acx_page_connection';
    $contents[$contype]['form'][0]['label'] = "Airtable Connection *";
    $contents[$contype]['form'][0]['type'] = "select_connection";
    $contents[$contype]['form'][0]['options'] = "required";


    //--------------------------

    $contype = 'PAGE_CREATE'; // this step doesnt sync it always redirects to the next step, next step syncs


    $contents[$contype]['title'] = "Create Dynamic Pages.";
    $contents[$contype]['paragraph'] = "We can now syncronize the data so you can use it in your website. Click the button below to begin syncronizing, and keep the window open while the process runs. It can take a little while depending how much data you have in the view.";

    $contents[$contype]['form'][0]['name'] = 'acxp_title';
    $contents[$contype]['form'][0]['label'] = "Title *";
    $contents[$contype]['form'][0]['type'] = "text";
    $contents[$contype]['form'][0]['options'] = "required";

    $contents[$contype]['form'][1]['name'] = 'acxp_name';
    $contents[$contype]['form'][1]['label'] = "Dynamic URL *";
    $contents[$contype]['form'][1]['type'] = "text_half";
    $contents[$contype]['form'][1]['options'] = "required";

    $contents[$contype]['form'][2]['name'] = 'acxp_dynamic';
    $contents[$contype]['form'][2]['label'] = "Field / Value *";
    $contents[$contype]['form'][2]['type'] = "select_half";
    $contents[$contype]['form'][2]['options'] = "required";


    //----------------------------------------------------

    $contype = 'PORTAL_WARNING';

    $contents[$contype]['title'] = "No User Groups!";

    $contents[$contype]['paragraph'] = "You don't have any user groups setup yet. To create a user group click the link below, and come back to add portals once done.";

    $contents[$contype]['links'][0]['text'] = 'User Groups';
    $contents[$contype]['links'][0]['icon'] = 'dashicons-admin-users';
    $contents[$contype]['links'][0]['url'] = '&main=users';

    //----------------------------------------------------

    $contype = 'PORTAL_CREATE'; //

    $contents[$contype]['title'] = "Create a User Portal.";
    $contents[$contype]['paragraph'] = "
    A user portal is limited to users imported from Airtable. The accounts are stored in Wordpress but connected to an Airtable record which can be used to store extra data and customize the portal.
    ";
    

    $contents[$contype]['form'][0]['name'] = 'acxp_title';
    $contents[$contype]['form'][0]['label'] = "Title *";
    $contents[$contype]['form'][0]['type'] = "text";
    $contents[$contype]['form'][0]['options'] = "required";

    $contents[$contype]['form'][1]['name'] = 'acxp_name';
    $contents[$contype]['form'][1]['label'] = "Page URL *";
    $contents[$contype]['form'][1]['type'] = "text_half";
    $contents[$contype]['form'][1]['options'] = "required";


    // EDITING THE ACCESS FOR AN EXISTING PAGE

    $contype = 'PAGE_ACCESS';

    $contents[$contype]['title'] = "Page Access.";
    $contents[$contype]['paragraph'] = "Choose the user group to have access to the page.";

    $contents[$contype]['form'][0]['name'] = 'acx_page_access';
    $contents[$contype]['form'][0]['label'] = "User Group *";
    $contents[$contype]['form'][0]['type'] = "select_connection";
    $contents[$contype]['form'][0]['options'] = "required";


        // EDITING THE ACCESS FOR AN EXISTING PAGE

        $contype = 'PAGE_ACCESS_DEL';

        $contents[$contype]['title'] = "Remove Page Access Control?";
        $contents[$contype]['paragraph'] = "Are you sure you want to remove page access for this page? If you proceed the page will become publicly visible and variable placeholders should be removed from the content once done.";
    
        $contents[$contype]['form'] = true; // 
        $contents[$contype]['button'] = 'Delete'; // 
    
    //--------------------------


    $contype = 'PAGE_DELETE';

    $contents[$contype]['title'] = "Delete this Dynamic Page?";
    $contents[$contype]['paragraph'] = "Are you sure you want to delete this Dynamic Page?</p><p>We can now syncronize the data so you can use it in your website. Click the button below to begin syncronizing, and keep the window open while the process runs. It can take a little while depending how much data you have in the view.";
    $contents[$contype]['form'] = true; // 
    $contents[$contype]['button'] = 'Delete'; // 


    //--------------------------

    $contype = 'CON_SYNC';

    $contents[$contype]['title'] = "Connected.";
    $contents[$contype]['paragraph'] = "Good job, we have made a connection!</p><p>We can now syncronize the data so you can use it in your website. Click the button below to begin syncronizing, and keep the window open while the process runs. It can take a little while depending how much data you have in the view.";
    $contents[$contype]['form'] = true; // 
    $contents[$contype]['sync'] = true; // we need the connection id at this point
    $contents[$contype]['button'] = 'Sync'; // 


    //--------------------------

    $contype = 'CON_DELETE';

    $contents[$contype]['title'] = "Delete this connection?";
    $contents[$contype]['paragraph'] = "Are you sure you want to delete this connection and all the associated data?";
    $contents[$contype]['form'] = true; // 
    $contents[$contype]['button'] = 'Delete'; // 

   $contype = 'USERS_DELETE';

    $contents[$contype]['title'] = "Remove $conn_title users?";
    $contents[$contype]['paragraph'] = "<p>Are you sure you want to delete these users?</p><p>This will not remove the wordpress user accounts - it only disconnects the users from the designated Airtable connection.</p>.";
    $contents[$contype]['form'] = true; // 
    $contents[$contype]['button'] = 'Delete'; // 


   $contype = 'PAGE_DELETE';

    $contents[$contype]['title'] = "Remove dynamic posts?";
    $contents[$contype]['paragraph'] = "Are you sure you want to delete this connection?.";
    $contents[$contype]['form'] = true; // 
    $contents[$contype]['button'] = 'Delete'; // 


//--------------------------



if ($stage)
{
$content = $contents[$stage]; extract($content);
}



//-------------------

$page_title = $title;

$body_html .= "<div class='bground'>";

//$body_html .= "<h4 style='color:white'>xx</h4>";

$body_html .= "<div class='center'>";

$body_html .= "<div class='main-box'>";


$body_html .= "<div class='row'>";
//-------
$body_html .= "<div class='col-md-6' style='background-color:white;'>";
$body_html .= "<H2 style='margin-top:0px;font-size:x-large;'>$title</H2>"; 
$body_html .= "<p style=''>$paragraph</p>";


if (isset($links) and is_array($links))
{

    foreach ($links as $link)
    {
	
	
	if (is_array($link))
	{
	
	$link_stage = null;
	$link_url = null;
	$link_icon = null;
	$link_text = null;
	$link_notes = null;
	
	extract($link,EXTR_PREFIX_ALL,'link');
	
	if (!$link_url)
	{
	$link_url = "&cid=$cid&stage=$link_stage";
	}
	
	
    $body_html .= "<a href='$plugins_page{$link_url}'><div style='margin-right:20px;'  class='acx-button acx-outline'><span style='margin-right:10px;' class='dashicons ".$link_icon."'></span>".$link_text."</div></a>";
    
	if ($link_notes)
	{
	$body_html .= "<p>".$link_notes."</p>";
	}
	}
	// end isset($link
	
    }
	// end foreach link

}
// end $links


if ($form)
    {
    // add the outer form

    if ($error_msg and $stage == 'CON_TBLVW'){$below = "<span style='color:red'>$error_msg</span>";  }

    $body_html .= "<div style='padding:10px 0;bottom: 150px;'>";

    $body_html .= "<form id='acx_mgmt_form' enctype='multipart/form-data' method='post'>";

    if (is_array($form))
    {

        foreach ($form as $key => $form_input) // the fields
        {
		
			$empty = false; // can be null
            $filter = null;
			
			$initial_value = null;
			
			extract($form_input);

            if ($bid){$value = get_post_meta($bid,$name,true);}
            if ($cid){$value = get_post_meta($cid,$name,true);}
            if ($pid){$value = get_post_meta($pid,$name,true);} 


            if ($name == 'connection_title'){$value = $conn_title;} // this will not change the value in the databse
            if ($type == 'password' and $empty != true){$value ='xxxxxxxxxxxxxxxxx';} // this will not change the value in the databse


            if ($type == 'text' or $type == 'password')
            {
            $body_html .= "<h4 style='color:grey'>$label</h4>";
            $body_html .= "<div class='form-group'><input type='$type' name='$name' class='acx-input' placeholder='$label' value='$value' $options /></div>";
            }

            if ($type == 'color')
            {
                $body_html .= "<h4 style='color:grey'>$label</h4>";
                $body_html .= "<div ><input type='color' name='$name'  placeholder='$label' value='$value' $options /></div>";
            }


            if ($type == 'text_half')
            {
            $body_html .= "<h4 style='color:grey'>$label</h4>";
            $body_html .= "<div class='form-group-half'><input type='text' name='$name' class='acx-input' placeholder='' value='$value' $options /></div>";
            }

            $select_class = "form-group";

            if ($type == 'select' or $type == 'select_half') 
            {

                if ($type == 'select')
                {
                $body_html .= "<h4 style='color:grey'>$label</h4>";
                }

                if ($type == 'select_half')
                {
                $body_html .= "<div class='form-group-break'> / </div>";
                $select_class = "form-group-half";
                }

                
                $body_html .= "<div class='$select_class'><select name='$name' class='acx-select'>";


                if ($options != 'required'){$body_html .= "<option value='' >-- none --</option>";}

                // by default, options are fields...
               
				$options = $conn_fields; 
				
				
                foreach ($options as $key=>$fvalue)
                {
				
				$disabled = null;
				$disabled_msg = null;
				
				//var_dump($fvalue);
				
                if ($key == $value){$selected = "selected='selected'";}else{$selected = '';};
				
				if ($filter) // filter invalid field types from selection
				{
				//var_dump ($type_array);
			
				$type_array = airconnex_field_typer($fvalue);
				$show_type = $type_array['show_type'];
				
				if ($filter !== $show_type) {$disabled = "disabled"; $disabled_msg = "* $show_type fields cannot be used  ";}
				
				}
				
				if (!$disabled)
				{
                $body_html .= "<option value='$key' $disabled $selected >[$key] $disabled_msg</option>";
                }
				
                }

                $body_html .= "</select></div>";


            }



            if ($type == 'select_base')
            {

                $options = acx_bases_array();

                $body_html .= "<div class='$select_class'><select name='$name' class='acx-select'>";

                foreach ($options as $key=>$value)
                {
                $select_label = $value['name'];
                $body_html .= "<option value='$key' >$select_label</option>";
                }
                $body_html .= "</select></div>";



            }

            if ($type == 'select_connection')
            {

                // all connections ...
                $connections = acx_connection_array();

                // user groups only ...

                    if ($name == 'acx_page_access')
                    {
                        $connections = acx_user_groups_array();
                    }

                    if ($name == 'acx_page_access')
                    {
                        $connections = acx_user_groups_array();
                    }


                $body_html .= "<div class='$select_class'><select name='$name' class='acx-select'>";

                foreach ($connections as $connection_id => $connection)
                {
                //$connection_app_name = substr($connection['app_name'],0,2);
                $connection_app_name = $connection['app_name'];
                $connection_title = $connection['title'];

                if ($connection_id == $value){$selected = "selected='selected'";}else{$selected = '';};
                $body_html .= "<option value='$connection_id' $selected >$connection_app_name > $connection_title</option>";

                }

                $body_html .= "</select></div>";

            }

        }

    }

    $body_html .= "<div id='form-response-error' style='margin-top:20px'><h4>$below</h4></div>"; // a block of html to show below the form
	


    $body_html .= "</div>";

}

//----------- BUTTON DIV AT BOTTOM------------------------

$body_html .= "<div class='acx-bottom-fixed'>";

// float left
$body_html .= "<span style='float:left;min-width: 125px;'><h4 style='color:silver'><a href='$goback_url'>$goback_txt</a></h4></span>"; // AJAX RETURN MESSAGE HERE

if (!$button){$button = 'Next';}

// float right
if (!$links)
{
$body_html .= "<input id='acx-form-submit' type='submit' style='float:right;min-width: 125px;' value='$button' class='acx-button acx-solid' name='acx_mgmt_form_submit' />";
$body_html .= "<input type='hidden' name='action' value='acx_mgmt_form'>".wp_nonce_field("acx_mgmt_form", "acx_mgmt_form_nonce", true, false);
}

$body_html .= "</div>";

//-------------------------------------

if ($form)
{
$body_html .= "</form>";
}


$body_html .= "</div>";


//--------------  RIGHT COLUMN ----------------------------

$body_html .= "<div id='form-response-message' class='col-md-6' style='background-color:#c8bbf2' >";


    if ($stage == 'USER_MAPPING')
    {
    $loop_html = acx_loop_compiler($cid,1000,'USER_MAPPING');
    }
	
	if ($stage == 'USER_CREATE')
	{
	$sync_users = acx_sync_users($cid); // sync first then display
	$loop_html = acx_loop_compiler($cid,1000,'USERS'); 
	$next = "&main=users";
	}
    
	if ($stage == 'LOOP_MAPPING')
    {
    $loop_html = acx_loop_compiler($cid,1000,'PREVIEW');
	$next = "&main=data";
    }


    if ($loop_html)
    {

    $body_html .= $loop_html;

    //----------- BUTTON DIV AT BOTTOM------------------------

    $body_html .= "<div class='acx-bottom-fixed'>";

    // continue to next step...
	
    $body_html .= "<a href='$plugins_page&cid=$cid{$next}' style='float:right;' class='acx-button acx-outline'>Continue</a>";

    $body_html .= "</div>";


    }
    // end if have loop








//-----------------------------------------

if ($image or $instructions)
{
$body_html .= "<div style='position: relative;height: 75%;'>"; 
$body_html .= $instructions;
$body_html .= "<img style=' max-height: 100%;
    max-width: 100%;
    width: auto;
    height: auto;
    margin: auto;' src='$image' >"; 
$body_html .= "</div>";
}



//----------------------------------------------------

$body_html .= "</div>";
$body_html .= "</div>";
$body_html .= "</div>";
$body_html .= "</div>";
$body_html .= "</div>";



$ajaxurl = admin_url('admin-ajax.php');


$script = "<script type='text/javascript'>
            (function($){
                $('#acx_mgmt_form').submit(function(e){
                    var fd = new FormData($(this)[0])
					
					fd.append('stage', '$stage')
					fd.append('cid', '$cid')
					fd.append('pid', '$pid')
					fd.append('bid', '$bid')
					

                    response = call_ajax(fd)

                    $('#form-response-error h4').text('Processing...')
                    $('#form-response-error').fadeIn()
                    
                  response.success(function(re_data){
                        var da = $.parseJSON(re_data),
                        url = da['url'],
                        action = da['action'],
                        error = da['error'],
						message = da['message']
					
                        $('#form-response-error').html(error)
                        $('#form-response-message').html(message)   
                        				
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

return array($body_html,$script);

}


function acx_connection_info($cid)
{

if ($cid)
{
$return = array();

$conn_bid = get_post_meta($cid,'acx_bid',true);
$conn_app = get_post_meta($conn_bid,'acx_app',true);
$connection_data = acx_connection_array($cid);

$connection = $connection_data[$cid];
extract($connection,EXTR_PREFIX_ALL,'conn'); //$conn_title conn_tbl, $conn_table/$conn_view

$airtable_link = "<a target='_blank' href='https://airtable.com/$conn_app/$conn_table/$conn_view'>View in Airtable</a>";
$sync_btn = airconnex_sync_button($cid,'link');

$return['conn_title'] = $conn_title; // the connection title
$return['conn_title'] = $sync_btn; // the sync button
$return['conn_title'] = $conn_title; // the connection title

return $return;

}

}
// end func
	
