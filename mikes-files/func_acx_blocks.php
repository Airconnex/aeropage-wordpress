<?php


add_filter( 'lzb/block_render/callback', 'acx_block_output', 10, 3 );


if ( ! function_exists( 'acx_block_output' ) ) :
function acx_block_output( $output, $attributes, $context ) {

$fields = null;
$u_fields = null;

    ob_start();

    $slug = $attributes['lazyblock']['slug'];
	
	// add page array to the attributes so it can be used in blocks
	
	global $post;

    $post_id = $post->ID;

    $page_array = acx_page_check($post_id);
	$user_array = acx_user_data();
	
    // LOOP
    if ($slug == 'lazyblock/airconnex-loop')
    {
        
		$cid = $attributes['connection'];
		
		
        if (!$cid)
        {
            $echo_this = "<h3 style='text-align:center;color:silver'>Select a Connection</h3>";
        }
        else 
        {
			$attributes['user_array'] = $user_array;
			$attributes['page_array'] = $page_array;
            $echo_this = acx_loop_compiler($attributes,24); //send all attributes and limit
        }
    }

    // IMAGE
    if ($slug == 'lazyblock/airconnex-image')
    {
        $url = $attributes['url'];

        if (!$url)
        {
            $echo_this = "<h3 style='text-align:center;color:silver'>Add a URL</h3>";
        }
        else 
        {
            $echo_this = acx_image_compiler($attributes); //send all attributes and limit
        }
    }
    
    // TEXT
    if ($slug == 'lazyblock/airconnex-text')
    {
    $echo_this = acx_text_compiler($attributes); //send all attributes and limit
    }
	
    // FORM   
	if ($slug == 'lazyblock/airconnex-form') // the code is in func_acx_form_block.php
    {
    $echo_this = acx_form_compiler($attributes); // this echoes inside the function
    }
	
	
	 // BREADCRUMB
   
	if ($slug == 'lazyblock/airconnex-navigation') // the code is in func_acx_form_block.php
    {
    $echo_this = acx_navigation_compiler($attributes); // this echoes inside the function
    }
	
	
	// replaces page and user data... (except for loop blocks)
	
	if ($slug !== 'lazyblock/airconnex-loop')
	{
	
	if (is_array($page_array))
	{
	extract($page_array);
	$echo_this = acx_replacer($echo_this,$fields,'p');
	}

	if (isset($user_array['data']))
	{
	$echo_this = acx_u_replacer($echo_this,$user_array['data']);
	}
	
	}
	

	echo $echo_this;
	
	//----------------------------------

    return ob_get_clean();
}
endif;



function acx_loop_compiler($attributes,$limit ='',$type = '') 
{

$page_array = null;
$u_fields = null;
$connection_type = null;
$loop_html = null;
$fields = null;
$acx_filter = null;
$filter = null;
$dynamic_slug = null;
$btnclass = null;
$body_html = null;
$body_html = null;
$card_html = null;
$filter_buttons = null; 
$filter_html = null;
$filter_btn_html = null; 

$heading = null; 
$image = null; 
$paragraph = null; 
$link_url = null; 
$imagesquare = null; 
$buttoncolor = null; 
$buttonstyle = null; 
$button_text = null; 
$content_heading = null;
$content_image = null; 
$content_link = null; 
$content_paragraph = null; 

$warning_icon = "<span style='color:orange;line-height: 30px;' class='dashicons dashicons-warning'></span>";

//echo "<pre>";print_r($attributes);echo "</pre>";

//var_dump($attributes);

        if (!is_array($attributes)) // hardcoded preview in setup area
        {
            $connection = $attributes;
            $layout = 'DATA_PREVIEW';
            // these dont actually do anything, just to stop errors from missing values
            $imagecols = 4;
            $columns = 1;
            $height = 120;
            $imagefit = 'cover';
            $lines = 3;
			
        }
        else // actual blocks will have an attributes array
        {
            extract($attributes);

            if (!$limit){$limit = '24';}

            // if a filter, offset etc is passed it will be added to these atts
            //acx_filter - filter by wors
                        //acx_offset - for pagination

            if ($acx_filter)
            {
                $filter['text'] = $acx_filter;
            }
            if ($dynamic_filter)
            {
                $filter['dynamic'] = $dynamic_filter;
            }

            //$filter['text'] = 'architect';
            //$filter['dynamic'] = 'page';

        }


        if (!is_numeric($connection))  // non numeric connections
        {
		
        $connection_a = explode('_',$connection);
		
		if (is_array($connection_a)) // P_XX_YY (TYPE_TEMPLATE_CONNECTION)
		{
		
        $connection_type = $connection_a[0];
		
		if ($connection_type == 'P')
        {
            $template_id = $connection_a[1];
			$connection = $connection_a[2];
            $dynamic_slug = get_post_meta($template_id,'acxp_name',true);// eg shoes		
            $dynamic_field = get_post_meta($template_id,'acxp_dynamic',true);// eg [Brand]
        }

		
        }
		
		if ($connection == 'LOGIN_AS')
		{
		$type = 'LOGIN_AS'; //
		}

		}
		
		
        


        if ($type == 'USERS' or $type == 'USER_MAPPING' or $type == 'LOGIN_AS') 
        {
            $layout = 'DATA_PREVIEW';
			
            $height = 50;
			
			if ($type == 'LOGIN_AS')
			{
			$layout = 'LOGIN_AS';
			$args = array('orderby' => 'user_nicename','order' => 'ASC');
			$records = get_users( $args );
			$h_field = 'x'; // dummy value to pass condition
			}
			else
			{
			
            $records = acx_user_query($connection,$limit);
            $h_field = get_post_meta($connection,'acx_user_e',true); // email field
            $p_field = get_post_meta($connection,'acx_user_n',true);
            $i_field = get_post_meta($connection,'acx_user_i',true);
			$l_field = null; // no links for users
			}
			
        }
        ELSE //LOOP
        {

			// MAKE THE QUERY TO GET THE RECORDS
			
            $records = acx_record_query($connection,$limit,$filter); 
            
			//echo "<pre>";print_r($filter);echo "</pre>";

            $h_field = get_post_meta($connection,'acx_field_h',true);
            $p_field = get_post_meta($connection,'acx_field_p',true);
            $i_field = get_post_meta($connection,'acx_field_i',true);
            $l_field = get_post_meta($connection,'acx_field_l',true);

        }


        if ($h_field or $p_field or $i_field)
        {

        // foreach record
        foreach ($records as $record)
        {
            //print_r($record);

        
		
		if ($type == 'LOGIN_AS')
		{
		$heading = $record->display_name;
		$paragraph = $record->user_email;
		$login_as = $record->ID;
		}
		else
		{
		$record_id = $record->ID;
		}


        // -- field mapping -- 
		
		if ($heading)
		{
		$content_heading = $heading;
		}
		elseif ($h_field)
		{
		$content_heading = get_post_meta($record_id,$h_field,true);
		}
        
        if ($image)
		{
		$content_image = $image;
		}
		elseif ($i_field)
		{
		$content_image = get_post_meta($record_id,$i_field,true);
		}
		
		if ($paragraph)
		{
		$content_paragraph = $paragraph;
		}
		elseif ($p_field)
		{
		$content_paragraph = get_post_meta($record_id,$p_field,true);
		}
		
		
		
        
        if ($dynamic_slug and $dynamic_field) // page links
        {
        $content_link = airconnex_get_dynamic_link($record_id,$dynamic_slug,$dynamic_field);
        }
		elseif($link_url) // custom value
		{
		$content_link = $link_url;
		if (substr($content_link,0,4) !== 'http')
		{
		$content_link = "https://".$content_link;
		}
		}
        elseif($l_field) // default mapping
        {
        $content_link = get_post_meta($record_id,$l_field,true);
        }
		

        // -- field prep -- 

        if (is_array($content_paragraph))
        {
        $content_paragraph = implode(',',$content_paragraph);
        }


		
        if (isset($content_image[0]['thumbnails']['large']['url']))
        {
        $content_image = $content_image[0]['thumbnails']['large']['url'];
        }
		elseif(is_array($content_image)) //image urls in a rollup / array
		{
		$content_image = $content_image[0];
		}
		
        if(substr($content_image,0,4) !== 'http')   // string but invalid url
        {
        $content_image = '';
        }

        //lzb weird seems to not save default values to the db
        if (!$columns){$columns = 3;}

        $b_cols = 12/$columns;

        // -- loop cards ---

        if ($layout == 'GRLE')
        {
        $txtcols = 12-$imagecols;
        $imgdivclass = "col-md-$imagecols";
        $txtdivclass = "col-md-$txtcols";
        }
        if ($layout == 'GRTO')
        {
        $imgdivclass = 'col-md-12';
        $txtdivclass = 'col-md-12';
        }

        if ($imagesquare)
        {
        $imgwidth = "{$height}px";
        }
        else {
        $imgwidth = '100%';
        }


        $btnstyle = "color:$buttoncolor"; // default - text only

        if ($buttonstyle == 'solid')
        {
        $btnclass = "btn btn-primary";
        $btnstyle = "background-color:$buttoncolor;border-color:$buttoncolor";
        }

        if ($buttonstyle == 'outline')
        {
        $btnclass = "btn btn-outline-primary";
        $btnstyle = "color:$buttoncolor;border-color:$buttoncolor";
        }
		
		if (!$button_text)
		{
		$button_text = 'Click here...';
		}

            //width:{$imagewidth}px



        if ($layout == 'CARD_PREVIEW' or $layout == 'DATA_PREVIEW' or $layout == 'LOGIN_AS') // PREVIEW / ADMIN WITHOUT BOOSTRAP STYLES
        {


            if ($layout == 'CARD_PREVIEW') // shows large card with button 
            {
            $t_width = 97; // default text full width
            $card_html .= "<div style='background-color:white;overflow:hidden;margin:10px;'>";
            $card_html .= "<div style='display:inline-block;width:25%'>";
			
            if ($content_image)
            {
            $t_width = 75;
            $card_html .= "<img style='height:150px;width:100%;object-fit:cover;' src='$content_image' />";
            }
            $card_html .= "</div>";
            $card_html .= "<div style='float:right;width:$t_width%'>";
            $card_html .= "<div style='padding:0 10px 5px 10px'>";
            $card_html .= "<h3 style ='margin: 0 0 10px 0;' >$content_heading</h3>";
            $card_html .= "<p style='display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient: vertical;overflow:hidden;'>
            $content_paragraph</p>";
            
			if ($content_link)
            {
            $card_html .= "<a style='margin: 0 0 10px 0 !important;color:#23bfc2;border-color:#23bfc2' class='acx-button acx-small acx-outline' href='#' role='button'>Click here...</a>";
            }
			
            $card_html .= "</div>";
            $card_html .= "</div>";
            $card_html .= "</div>";
            }

            if ($layout == 'DATA_PREVIEW') // shows horizontal rows
            {
            

			// checks the emails for validity - shows warning
						
			if ($type == 'USER_MAPPING' or $type == 'USERS')
			{
			
			//$content_heading is the email - check if the user exists, show the username
			
			$user_id = email_exists($content_heading);
			
			if ($user_id)
			{
			
			$has_record = get_user_meta($user_id,"acx_{$connection}_record_id",true);
						
			if ($has_record) // user exists and is connected
			{
			///
			$content_paragraph = "<a target='_blank' href='user-edit.php?user_id=$user_id'>User $user_id</a>"; //&#10004;&nbsp;
			}
		
			}
			
			
			if (!is_email($content_heading))
			{
			$content_icon = $warning_icon;
			$content_paragraph = "<span style='color:red'>Invalid Email</span>";
			}
			
			}
			
			
            $card_html .= "<div style='background-color:white;overflow:hidden;padding: 10px; border: 1px solid silver;margin:-1px 0'>";
            $card_html .= "<div style='display:inline-block;width:12%;text-align: center;'>";
			
			
			if (isset($content_icon))
			{
			$card_html .= "<div>$content_icon</div>";
			}
			elseif ($content_image)
            {
            $card_html .= "<img style='height:25px;width:25px;object-fit:cover;' src='$content_image' />";
            }
            $card_html .= "</div>";
            $card_html .= "<div style='float:right;width:85%'>";
            $card_html .= "<div style='position: relative;overflow:auto;'>";
            $card_html .= "<div style ='display:inline-block;line-height:25px;font-weight:500;font-size:14px;width:65%;overflow: hidden;'>$content_heading</div>";
			$card_html .= "<div style ='line-height:25px;display:inline-block;position:absolute;right:0;color:grey;font-size:14px;width:35%;overflow:hidden;text-align:right;'>$content_paragraph</div>";
			
		
			
			$card_html .= "</div>";
            $card_html .= "</div>";
            $card_html .= "</div>";
            
			
			unset($content_icon);
            
            }

			if ($layout == 'LOGIN_AS') // shows horizontal rows with click to login_as user...
            {
			 //$card_html .= "<a >"; 
            $card_html .= "<div style='display: flex;justify-content:space-between;background-color:white;overflow:hidden;padding: 10px; border: 1px solid silver;margin:-1px 0'>";
            
			$card_html .= "<div style='text-align: left;overflow: hidden;line-height:25px'>";
			
			$card_html .= "<span style ='font-weight:500;font-size:14px;'>$content_heading</span><span style ='margin-left:5px;color:grey;;font-size:14px;'>$content_paragraph</span>";
			
			$card_html .= "</div>";
			
			//$card_html .= "<div style ='line-height:25px;display:inline-block;;color:grey;font-size:14px;width:35%;overflow:hidden;text-align:right;'>$content_paragraph</div>";

	
			//$card_html .= "<div style='float:right;width:85%'>";
            $card_html .= "<a class='btn btn-sm btn-outline-primary' href='javascript:void(0);' id='acx-menu-link' data-vid='$login_as' data-show='user' role='button'>Login</a>";
            
            $card_html .= "</div>";
			
			}


        }
        else 
        {
            $card_html .= "<div class='col-md-$b_cols' style='text-align:$alignment;padding-bottom:40px;'>";

            $card_html .= "<div class='row' style='background-color:white;overflow:hidden;margin:10px;'>";
          
            $card_html .= "<div class='$imgdivclass' >";

            if($content_image)
            {
            $card_html .= "<img style='margin:auto;border-radius:{$imageradius}%;height:{$height}px;width:$imgwidth;object-fit:$imagefit;' src='$content_image' />";
            }

            $card_html .= "</div>";
            
            $card_html .= "<div class='$txtdivclass' style='position: relative;height:100%'>";
            $card_html .= "<h3 style='margin-top:20px;'>$content_heading</h3>";
			
			if ($content_paragraph and !$paragraph)
			{
            $card_html .= "<p style='display:-webkit-box;-webkit-line-clamp:$lines;-webkit-box-orient: vertical;overflow:hidden;'>$content_paragraph</p>";
			}
			else
			{
			$card_html .= $content_paragraph; // custom from wysiwg comes with p (or other) tags pre-added
			}
            
			if($content_link)
            {
            $card_html .= "<a style='$btnstyle' class='$btnclass' href='$content_link' role='button'>$button_text</a>";
            }
            
			$card_html .= "</div>";
            $card_html .= "</div>";

            $card_html .= "</div>";
        }


		// DO THE REPLACER FOR ALL PLACEHOLDERS 

		$record_data = airconnex_get_record_values($connection,$record_id);
		
		//$fields = acx_replacer_fields($record_data); // add html tags based on types
		
		$record_fields = $record_data['fields'];
		
		// we get types here and can also replace them into html
		
		$card_html = acx_replacer($card_html,$record_fields);
		
		$body_html .= $card_html;

		unset($record_data);
		
		$card_html = '';

        }
        // end foreach

    
        //----------------------------

        if (is_array($filter_buttons))
        {

                foreach ($filter_buttons as $filter_btn)
                {

                $filter_value = $filter_btn['value'];
                
                if ($filter_value)
                {
                $filter_btn_html .= "<a href='javascript:void(0);' id='acx-loop-render' data-filter = '$filter_value' data-blockclass = '$blockUniqueClass' data-blockid = '$blockId' >
                <div class='acx-loop-filter-btn'>$filter_value</div></a>"; 
                }

                }

                if ($filter_btn_html)
                {
                    $filter_html = "<div>
                    <a href='javascript:void(0);' id='acx-loop-render' data-filter = '' data-blockclass = '$blockUniqueClass' data-blockid = '$blockId' >
                    <div class='acx-loop-filter-btn'>All</div></a>
                    $filter_btn_html
                    </div>";
                }
         
        }
        
        // the containing div is added in the render loop not here
		
		
		
		

        $loop_html .= "<div class='row' style='background-color:transparent;'>";
        $loop_html .= $filter_html;
        $loop_html .= $body_html;
        $loop_html .= "</div>";


        //----------------------------


        if ($layout == 'DATA_PREVIEW' or $layout == 'LOGIN_AS') // OVERFLOW SCROLLING
        {

            $loop_html = "
            <style>
            ::-webkit-scrollbar
            {
            width: 10px;  /* for vertical scrollbars */
            height: 10px; /* for horizontal scrollbars */
            }
            ::-webkit-scrollbar-track
            {
            background: transparent;
            }
            ::-webkit-scrollbar-thumb
            {
            border-radius:5px;
            background: #610979;
            }
            </style>
            <div style='max-height:507px;min-width:480px;overflow-y:scroll;padding:2px;'>
            $body_html
            </div>";

        }


        }
        // end if have values



		// page array is added to attributes -- so should be available
	
		if (is_array($page_array))
		{
		extract($page_array);
		}
	
		$loop_html = acx_replacer($loop_html,$fields,'p');
		
		
		$user_data  = acx_user_data();
		
		if (is_array($user_data['data']))
		{
		foreach($user_data['data'] as $cid => $data)
		{
		$user_prefix = "u.$cid";
		$user_fields = $data['fields'];
        $loop_html = acx_replacer($loop_html,$user_fields,$user_prefix);
		}
		}
		//$loop_html = "$connection - $type";
		
        return $loop_html;

}




function airconnex_lzb_form_connections()
{

if (isset($_GET['post']))
{

$post_id = $_GET['post'];

    $x = 0;
    $choices = array();

    $connections = acx_connection_array();
	
	foreach ( $connections as $cid => $values)
    {
        $choices[$x]['label'] = "(Add) ".substr($values['app_name'],0,2).' > '.$values['title'];
        $choices[$x]['value'] = "A_{$cid}"; 
        $x++;
    }


	$acx_tid = get_post_meta($post_id,'acx_template',true); // template id
	
	
	
	
	if ($acx_tid) //is a dynamic page
    {
	
	$acx_tob = get_post($acx_tid); // template object
	$cid = $acx_tob->post_parent; // connection
	$title = get_post_meta($acx_tid,'acxp_title',true);
	
	$choices[$x]['label'] = "(Edit) Page Record";
    $choices[$x]['value'] = "P_{$cid}";  // dynamic template connection
	$x++;
	
	$aid = get_post_meta($acx_tid,'acx_access',true); // check page access on a template (dynamic page)
	
	}
	ELSE // check page access on a normal page
	{
	$aid = get_post_meta($post_id,'acx_access',true); // access applied to normal post / page 
	}
	
	
	if ($aid) // if theres page access 
    {
	$choices[$x]['label'] = "(Edit) User Record";
    $choices[$x]['value'] = "U_{$aid}"; // 
	$x++;
	}
	
return $choices;

}
// only run if we have a post id and are in the editor...

	
}


function airconnex_lzb_linked_fields()
{

$fields_raw = null;

$x = 0;

global $wpdb;

if (isset($_GET['post']))
{
$post_id = $_GET['post']; // get the current


$choices = array();

$page_array = acx_page_check($post_id);

if (isset($page_array['fields_raw']))
{

$fields_raw = $page_array['fields_raw'];

// loop, and only act on linked field types...


foreach ($fields_raw as $key => $value)
{

$values = airconnex_field_typer($value);

if ($values['show_type'] == 'linked')
{

$linked_records = $values['raw_value']; // this is an array of record ids...


foreach ($linked_records as $record_id)
{

// we want to get the id...

$record_page = get_posts(['post_type' => 'acx-record','name' => $record_id,'post_status' => 'private','numberposts' => 1]);

if (isset($record_page[0]))
{
$record_page_id = $record_page[0]->ID; // assume theres only one for now
}
else
{
$record_page_id = null;
}

$linked_page = $wpdb->get_results("SELECT * FROM $wpdb->postmeta WHERE meta_key = 'acx_record' AND  meta_value = '$record_page_id' LIMIT 1", ARRAY_A);

if (isset($linked_page[0]))
{
$linked_page_id = $linked_page[0]['post_id']; // assume theres only one for now
}
else
{
$linked_page_id = null;
}

$template_page_id = get_post_meta($linked_page_id,'acx_template',true); // assume theres only one for now

// we just get the page id to check if this field is valid for linking

if ($linked_page_id)
{

$choices[$x]['label'] = $key; // the field name (in this table)
$choices[$x]['value'] = $key; // the field name (in this table)

}



/*

// use the record post id to get the id of the dynamic post itself

$linked_page = $wpdb->get_results("SELECT * FROM $wpdb->postmeta WHERE meta_key = 'acx_record' AND  meta_value = '$record_page_id' LIMIT 1", ARRAY_A);

$linked_page_id = $linked_page[0]['post_id']; // assume theres only one for now

if ($linked_page_id)
{
$data = acx_page_check($linked_page_id);
$linked_page_record_name = $data['heading'];
$choices[$x]['label'] = $linked_page_record_name; // the linked connection
$choices[$x]['value'] = $linked_page_id.'_'.$record_page_id; // the linked connection
}



*/





}
// end foreach linked record

}
// end if linked

}
// end foreach field
}

return $choices;
}
// only run if we have a post id and are in the editor...


}


function airconnex_lzb_normal_pages() // select from normal wordpress pages
{


global $wpdb;

if (isset($_GET['post']))
{

$post_id = $_GET['post']; // get the current so we can exclude it


$choices = array();

$pages = get_pages();

$x = 0;
 
foreach ( $pages as $page ) 
{

if ($post_id !== $page->ID)
{
$title = $page->post_title;
if (!$title){$title = $page->post_name;}
$choices[$x]['label'] = $title;
$choices[$x]['value'] = $page->ID;
$x++;
}

}

return $choices;

}
// only run if we have a post id and are in the editor...

}

function airconnex_lzb_dynamic_filter()
{

if (isset($_GET['post']))
{
    $post_id = $_GET['post'];

    $x = 0;
    global $wpdb;

    $choices = array();


    $acx_tid = get_post_meta($post_id,'acx_template',true); // template id

    if ($acx_tid) //is a dynamic page
    {
        $acx_tob = get_post($acx_tid); // template object
		//$template_title = get_post_meta($acx_tid,'acxp_title',true); //the name of the page the filter comes from
        //$acx_rid = get_post_meta($post_id,'acx_record',true);
        $cid = $acx_tob->post_parent;
		$acx_cob = get_post($cid);
		$connection_title = $acx_cob->post_title;
        //$h_field = get_post_meta($cid,'acx_field_h',true);
        //$record_heading = get_post_meta($acx_rid,$h_field,true);
        
        //$choices[$x]['label'] = "$template_title > $record_heading";
		$choices[$x]['label'] = "$connection_title";
        $choices[$x]['value'] = "page";

        $x++;
    }

    $u_record = get_post_meta($post_id,'acx_login_as',true); // get the emulated user

    if ($u_record) //is a dynamic page
    {
    $u_post = get_post($u_record); // user record post
    $cid = $u_post->post_parent;    // the connection
    $name_field = get_post_meta($cid,'acx_user_n',true); // name field
    $username = get_post_meta($u_record,$name_field,true);

    $choices[$x]['label'] = "(U) $username";
    $choices[$x]['value'] = "user";
    }

// only run if we have a post id and are in the editor...


    return $choices;
}


}

// gets called in inc/lzb/classes/class-block.php #895

function airconnex_lzb_connections()
{

    $x = 0;
    $choices = array();

    $connections = acx_connection_array();

    foreach ( $connections as $cid => $values)
    {
        $choices[$x]['label'] = "(Data) ".substr($values['app_name'],0,2).' > '.$values['title'];
        $choices[$x]['value'] = $cid;
        $x++;
    }

    $dynamic_pages = get_posts(['post_type' => 'acx-template','post_status' => 'private','numberposts' => -1]);

    foreach ( $dynamic_pages as $page)
    {
        $tid = $page->ID;
        $cid = $page->post_parent; // the connection is the parent of the template
        $title = get_post_meta($tid,'acxp_title',true);

        $choices[$x]['label'] = "(Pages) ".$title;
        $choices[$x]['value'] = 'P_'.$tid.'_'.$cid;
        $x++;
    }

    return $choices;
}



// use AJAX for loop rendering, so we can update it (load more / filters) live

add_action('wp_ajax_nopriv_airconnex_loop_render', 'airconnex_loop_render');
add_action('wp_ajax_airconnex_loop_render', 'airconnex_loop_render');

function airconnex_loop_render()
{

    global $wpdb;

    $url = wp_get_referer();
    $post_id = url_to_postid( $url ); 
	$page_array = acx_page_check($post_id);

    $blockclass = $_POST['blockclass'];

    $filter = $_POST['filter']; //value id when a specific one is being passed

    $response = array(); // response goes back to the form / js

    //Always check the nonce for security purposes!!!

    if(isset($_POST['nonce']) && wp_verify_nonce($_POST['nonce'], $_POST['action'])){
                        
          // check if the current post is using a template

          $template = get_post_meta($post_id,'acx_template',true); // template id

            if ($template) // its a dynamic post
            {
            $post_id = $template;
            }

          // get the post 

          $post = get_post($post_id);

          $content = $post->post_content;

          $blocks = parse_blocks($content);

          foreach ($blocks as $block) {

          if ($block['attrs']['blockUniqueClass'] == $blockclass)
            {

                $attributes = $block['attrs'];
				
				$attributes['page_array'] = $page_array; //must be added for page replacers

                $attributes['acx_filter'] = $filter; //append and pass to render

                $filter_buttons = $attributes['filter_buttons'];
				
                $filter_buttons = urldecode($filter_buttons);
				
                $filter_buttons = json_decode($filter_buttons,true);
				
                $attributes['filter_buttons'] = $filter_buttons;

                $blocks_content .= acx_loop_compiler($attributes);
				

            }

          }

          $response['html'] = $blocks_content;
          
          wp_die(json_encode($response));

          }

}




function acx_text_compiler($attributes)
{

$block_html = $attributes['text'];

return $block_html;

}



function acx_image_compiler($attributes)
{

extract($attributes);

        if ($shape == 'or')
        {
            $fit = "object-fit:contain;";
            $fixed = "height:{$height}px;width:auto;";
        }

        if ($shape == 're')
        {
            $fit = "object-fit:cover;";
            $fixed = "height:{$height}px;width:{$width}px;";
        }

        if ($shape == 'sq' or $shape == 'ci')
        {
            $fit = "object-fit:cover;";

            if ($height < $width){$px = $height;}else{$px = $width;}
            
            $fixed = "height:{$px}px;width:{$px}px;";

            if ($shape == 'ci')
            {
                $fixed .= "border-radius:{$px}px;";
            }

        }


            $block_html .= "<div style='text-align: center;' >";
            $block_html .= "<img style='margin:auto;{$fit}{$fixed}' src='$url' />";
            $block_html .= "</div>";

            return $block_html;
}



function acx_navigation_compiler($attributes)
{

global $wpdb;

$block_html = '';

extract($attributes);


if ($normal_pages) // just one, page id.
{
$page = get_post($normal_pages);
$home_title = $page->post_title;
$home_link = get_permalink($normal_pages);
$block_html .= "<a style='display:inline-block;margin-bottom:25px;' href='$home_link' role='button'>$home_title</a>";
}
else
{
$home_link = get_home_url();
$block_html .= "<a style='display:inline-block;margin-bottom:25px;' href='$home_link' role='button'>Home</a>";
}


if ($linked_field) //its a field name
{

global $post;

$post_id = $post->ID;


$record_post_id = get_post_meta($post_id,'acx_record',true); // get the dynamic data

$linked_records = get_post_meta($record_post_id,$linked_field,true); // get the array of linked records

$record_id = $linked_records[0]; // just get one linked record id

//$linked_record_post = $wpdb->get_results("SELECT * FROM $wpdb->posts WHERE post_name LIKE '$linked_record' LIMIT 1", ARRAY_A);

$record_page = get_posts(['post_type' => 'acx-record','name' => $record_id,'post_status' => 'private','numberposts' => 1]);

$record_page_id = $record_page[0]->ID; // assume theres only one for now

$linked_page = $wpdb->get_results("SELECT * FROM $wpdb->postmeta WHERE meta_key = 'acx_record' AND  meta_value = '$record_page_id' LIMIT 1", ARRAY_A);

$linked_page_id = $linked_page[0]['post_id']; // get the id of the post this meta is assigned to... (the post itself)

$data = acx_page_check($linked_page_id);


	//echo "<pre> linked_record_id $record_id linked_page_id $linked_page_id ";print_r($page_array);echo "</pre>";



$content_heading = $data['heading'];
$content_image = $data['image'];

$dynamic_slug = $data['dynamic_slug'];
$dynamic_field = $data['dynamic_field'];

$content_link = airconnex_get_dynamic_link($record_page_id,$dynamic_slug,$dynamic_field);

$block_html .= " / <a style='display:inline-block;' href='$content_link' role='button'>$content_heading</a>";

}

// the current page ---

global $post;

$post_id = $post->ID;

$page_array = acx_page_check($post_id);

$page_title = $page_array['heading'];

$block_html .= " / $page_title";


return $block_html;

}
// end function

//-------------------------------------------------------------------





// script for injecting the rendered loop HTML into the loop block div / live update via AJAX 

add_action("wp_footer", "airconnex_loop_render_js");

function airconnex_loop_render_js() {

    $action = 'airconnex_loop_render'; //Action for the ajax function
    $nonce = wp_create_nonce($action); //Nonce for security

    ?>
    <script type="text/javascript" >
        jQuery(document).ready(function($) {

  ajaxurl = '<?php echo admin_url( 'admin-ajax.php' ) ?>'; // get ajaxurl

        $(document).on('click', '#acx-loop-render', function(){
            var filter = $(this).data('filter')
            var blockclass = $(this).data('blockclass')
            var data = {
            action: '<?=$action?>',
            filter: filter,
            blockclass: blockclass,
            nonce: '<?=$nonce?>'
            };

            $.post(ajaxurl, data, function(response) {

            var data = $.parseJSON(response),html = data['html']

              $('.' + blockclass).html(html)

            });
        });
        });
    </script>
    <?php

}



// filter for Editor output.

//add_filter( 'lazyblock/airconnex-loop/editor_callback', 'acx_editor_callback', 10, 2 );

function acx_editor_callback( $output, $attributes ) 
{


}







/*
add_action( 'wp_footer', 'my_footer_scripts' );
function my_footer_scripts(){
  ?>
  <script>
  
  wp.hooks.addFilter( 'lzb.editor.control.render', 'airconnex', function ( render, controlData, blockData ) {
    	
	console.log(controlData['data']['name'] );
	
    return render;
} );
  
  </script>
  <?php
}
*/




// filter for Frontend output.
//add_filter( 'lazyblock/airconnex-loop/frontend_callback', 'acx_block_output', 10, 2 );



