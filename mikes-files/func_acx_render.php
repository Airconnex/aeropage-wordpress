<?php


function acx_portal_login($page_data)
{

    extract($page_data, EXTR_PREFIX_ALL, 'page');

    $bg_color = get_background_color();

    if (!$bg_color){$bg_color = '#EEF2F7';}

    //background-color: $bg_color;

    $login_html = "
    <style>

    .acx-login-bg{
        position: fixed; 
        top: 0; 
        left: 0; 
        min-width: 100%;
        min-height: 100%;
        background-color: $bg_color;
        z-index: 10000;
    }

    .acx-login-container {
        margin-top:-25px;
        
    }
    .acx-login-center {
        
        z-index: 10000;
        position: relative;
        margin: auto;
        max-width: 400px;
        background:white;
        border-radius:15px;
        padding:30px;
        text-align: center;
        box-shadow:
        0 2.8px 2.2px rgba(0, 0, 0, 0.034),
        0 6.7px 5.3px rgba(0, 0, 0, 0.048),
        0 12.5px 10px rgba(0, 0, 0, 0.06),
        0 22.3px 17.9px rgba(0, 0, 0, 0.072),
        0 41.8px 33.4px rgba(0, 0, 0, 0.086),
        0 100px 80px rgba(0, 0, 0, 0.12)
    }
    .acx-login-center label{display: block;margin:10px 0;font-weight:600;}
    </style>
    ";

    $args = array(
        'echo'            => false,
        'redirect'        => get_permalink( get_the_ID() ),
        'remember'        => true,
        'value_remember'  => true,
    );
    
    $login_html .= "<div class='acx-login-container'>";
    $login_html .= "<div class='acx-login-bg'></div>";
    $login_html .= "<div class='acx-login-center'>";
    $login_html .= "<div style='max-width:180px;margin:auto;'>".get_custom_logo()."</div>";
    
	
	if ($page_u_access['result'] == 'is_admin')
	{
	$login_html .= "<h3 style='margin:40px 0;font-weight:bold;'>Login as...</h3>";
	//$login_html .= acx_loop_compiler($page_access,'100','USER');
	$login_html .= "<p>Hello Admin. This page has user access control and requires users to login to view the content. You can emulate any user using the menus at the bottom right of screen.</p>";
	$login_html .= "<p><strong>Choose a user from the airconnex menu.</strong></p>";

	}
	ELSE
	{
	$login_html .= "<h3 style='margin:40px 0;font-weight:bold;'>Login</h3>";
    $login_html .= wp_login_form( $args );
	}
	
    $login_html .= "</div>";
    $login_html .= "</div>";
    $login_html .= "";

    return $login_html;
  
}



function acx_portal_redirect()
{

$page_data = acx_page_check(); // check the current page

if(isset($page_data['access']) && !is_user_logged_in ())
    {
        $loginUrl = home_url('/login-page/');
        wp_redirect($loginUrl);
         exit(); 
    }

}
add_action( 'template_redirect', 'acx_portal_redirect' );




// 
function acx_render_post($post_id = '')
{

global $post;

$title = null;
$content = null;

if (!$post_id)
{
if (isset($post))
{
$post_id = $post->ID;
}
}

if (isset($post_id))
{
	//get all the data for this page...

    $page_data = acx_page_check($post_id);
    $page_data = acx_page_block_render($page_data); //takes that data and adds / renders gutenberg blocks
	
	//var_dump($page_data);
	
	$user_data = acx_user_data(); // user check tells us which connections the user has records for...
	

	if (is_array($page_data)) // make sure its valid
	{
	$acx_cid = null;
	$page_dynamic = null;
	$page_access = null;
	$page_title = null;
	$page_content = null;
	$page_elementor = null;
	
    extract($page_data, EXTR_PREFIX_ALL, 'page');
	
    
    //--------- DEFAULT CONTENT----------------
	
	if ($page_dynamic or $page_access)
	{
		$raw_title = $page_title;
        $title = $page_title; // shows on page
        $content = $page_content;
        $raw_content = $page_content;
	}

    //-------------------------

    if ($page_dynamic and !$page_elementor)
    {
        //------- image placeholder -----------------

        $image_placeholders = get_post_meta($acx_cid,'acx_image'); // array

        if (is_array($image_placeholders))
        {
            foreach ($image_placeholders as $imgfield)
            {

                $upload_dir = wp_upload_dir();
                $upload_url = $upload_dir['baseurl'].'/airconnex';

                $img_find = "$upload_url/placeholders/$acx_cid-$imgfield.png";

                $img_value = get_post_meta($acx_rid,$imgfield,true); 

                $img_replace = $img_value[0]['thumbnails']['large']['url'];

                if (!$img_replace){$img_replace = $img_value;}

                $content = str_replace( $img_find, $img_replace, $content );

                //$return .= "$img_find $img_replace <br>";

            }
        }
	}
	
	
        //-------------------------

        // do the replacer if page is dynamic
	if (is_array($page_fields))
	{
        $title = acx_replacer($title,$page_fields,'p');
        $content = acx_replacer($content,$page_fields,'p'); 
	}
   


    if ($page_access)
    {
	

		
    }



		// if we have records for the user, 

	if (isset($user_data['data']))
	{
		$title = acx_u_replacer($title,$user_data['data']);
		$content = acx_u_replacer($content,$user_data['data']);	
	}

    //--------
    
    // only return if we have all values, otherwise it will break non airconnex post types

    if (isset($title) and isset($content) and isset($raw_title) and isset($raw_content))
    {
    return array('title'=>$title,'content'=>$content,'raw_title'=>$raw_title,'raw_content'=>$raw_content);
    }

	}
	// if page data
	
	}
	// end if post (prevent warnings on author / non post page types

}





// for elementor, we use a different method and replicate edited data to every post...

add_action( 'elementor/editor/after_save', 'acx_elementor_data', 2 , 10);

function acx_elementor_data ( $post_id, $editor_data ) 
{

      global $wpdb;

      $acx_tid = get_post_meta($post_id,'acx_template',true); // template id

      //-----------------------------------------------------------------------------

      if ($acx_tid ) 
      {

      $post = get_post($post_id);
      $post_title = $post->post_title;
      $post_content = $post->post_content;

      $elementor_postmeta = get_post_meta($post_id); // get all the meta

      unset ($elementor_postmeta['acx_record']);
      unset ($elementor_postmeta['acx_template']);
      unset ($elementor_postmeta['_thumbnail_id']);

      $query = "
      SELECT ID FROM $wpdb->posts p 
      INNER JOIN $wpdb->postmeta pt ON (p.ID = pt.post_id AND pt.meta_key = 'acx_template')
      WHERE pt.meta_value = '$acx_tid' and p.ID != '$post_id'
      ";

      $dynamic_posts = $wpdb->get_results($query); 

      foreach ($dynamic_posts as $dynamic)
      {

      $dynamic_id = $dynamic->ID;

      //update_post_meta('2768','_elementor_data',$editor_data); // add to this one

      $dynamic_post = array(
            'ID'           => $dynamic_id,
            'post_title'   => "$post_title",
            'post_content' => "$post_content",
        );
      wp_update_post( $dynamic_post );


      foreach($elementor_postmeta as $key=>$value)
      {
      update_post_meta($dynamic_id,$key,$value[0]);
      }


      }

      }
      // end if template


}


// takes and user array and replaces all

function acx_u_replacer($string,$user_data)
{

		foreach($user_data as $cid => $data)
		{
		$user_prefix = "u.$cid";
		$user_fields = $data['fields'];
        $string = acx_replacer($string,$user_fields,$user_prefix);
		}
			
return $string;

}


function acx_replacer($string,$fields,$prefix ='')
{

if ($prefix){$prefix = $prefix.'.';} // add a . after the prefix

    if (is_array($fields))
    {

    foreach ($fields as $key=>$value)
    {
    $replace = "((".$prefix.$key."))";
    //$value = get_post_meta($acx_rid,$key,true); // get the value from record meta
    $string = str_replace( $replace, $value, $string );
    //unset($value);
    }

    }

    return $string;

}





add_action( 'elementor/frontend/the_content', 'airconnex_elementor_filter');

function airconnex_elementor_filter($block_content) 
{

    global $post; $post_id = $post->ID;

    $acx_tid = get_post_meta($post_id,'acx_template',true); // template id

    if ($acx_tid)
    {
    $acx_rid = get_post_meta($post_id,'acx_record',true); // record id
    $acx_tob = get_post($acx_tid); // template object
    $acx_cid = $acx_tob->post_parent; // connection is parent of template

    $block_content = acx_replacer($block_content,$acx_rid,$acx_cid);
    }

    return $block_content;

}




//add_action( 'save_post', 'acx_filter_post_data' );

add_filter( 'wp_insert_post_data' , 'acx_filter_post_data' , '99', 2 );

function acx_filter_post_data( $data , $postarr ) {

    $post_id = $postarr['ID'];
		
    $post_title = $data['post_title']; // the post title that was submitted with data...
	
    $post_content = $data['post_content'];
	
	$acx_tid = get_post_meta($post_id,'acx_template',true); // template id

	if (is_numeric($acx_tid))
	{
    $update_post = array(
        'ID'           => $acx_tid,
        'post_title' => "$post_title",
        'post_content' => "$post_content",
		//'post_excerpt' => "$ex_post_title | $post_title | $t_post_title",
    );
	
    wp_update_post( $update_post );
		
    }

    return $data;

}



// THIS APPEARS IN THE EDITOR ----------------

function acx_the_post_action( $post_object ) {

global $wpdb;

    $acx_post = acx_render_post();

    //----------------------

    if ($acx_post)
    {
	$post_id = $post_object->ID;
    $post_object->post_title = $acx_post['raw_title'];
    $post_object->post_content = $acx_post['raw_content'];
	
	// update the actual post with the template title, so that if we save without changes it gets passed
	
	$template_id = get_post_meta($post_id,'acx_template',true); // template id
	$template = get_post($template_id);
	$template_title = $template->post_title; // in that case fetch the title from the template
	
	//$t_post_title = htmlspecialchars_decode($t_post_title);
		
	//$update_post = $wpdb->query($wpdb->prepare("UPDATE $wpdb->posts SET 'post_title' = '$template_title'  WHERE ID = '$post_id' "));

	$update_post = $wpdb->update($wpdb->posts, array('post_title'=>$template_title), array('ID'=>$post_id));


	/* DONT USER THIS, IT GETS FILTERED BY THE DYNAMIC POST SAVING 
	$update_post = array(
        'ID'           => $post_id,
        'post_title' => "$template_title",
    );
    wp_update_post( $update_post );
	*/
	
    }

    return $post_object;

}
add_action( 'the_post', 'acx_the_post_action' );




// THIS APPEARS IN THE <TITLE> META TAG ----------------

add_filter( 'pre_get_document_title', 'acx_filter_title_tag' );

function acx_filter_title_tag( $title ) {

    $acx_post = acx_render_post();

    if ($acx_post)
    {
    $title = $acx_post['title'];
    }	

    return $title;

}


// THIS APPEARS IN THE FRONTEND AND ADMIN AREAS


add_filter('the_title', 'acx_the_title', 10, 2);

function acx_the_title($title, $id) 
{

    //id is passed becuase this appears in menus too

    $acx_post = acx_render_post($id);

    if ($acx_post)
    {
    $title = $acx_post['title'];
    }	
    

    return $title;

}

// THIS APPEARS IN THE FRONTEND 

add_filter('the_content', 'acx_the_content_filter');

function acx_the_content_filter($content)
{

    $acx_post = acx_render_post();
        
    if ($acx_post) 
    {
    $content = $acx_post['content'];	
    }
	
    return $content;

}



    