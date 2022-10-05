<?php



add_action('wp_ajax_airconnex_menu_html', 'airconnex_menu_html');

function airconnex_menu_html()
{

  global $wpdb;
  global $plugins_page;
   // to get the post id from frontend url inside ajax

   $url = wp_get_referer();
   $pid = url_to_postid( $url ); 

   if (!$pid) // no result, we must be from the editor
   {
    $parts = parse_url($url);
    parse_str($parts['query'], $query);
    $pid = $query['post'];
    $is_admin = true;
   }
		
    $show = $_POST['show'];

    $vid = $_POST['vid']; //value id when a specific one is being passed

  

    $response = array(); // response goes back to the form / js

    //Always check the nonce for security purposes!!!
    if(isset($_POST['nonce']) && wp_verify_nonce($_POST['nonce'], $_POST['action'])){


		if ($show == 'block'){
		 
		$response['after'] = 'show_block'; // show the block div (will hide the float div)
		 
		}

// show sync button for each connection

          if ($show == 'data'){
                // get array of connections
                

                $html .= "<p><strong>Data / Connections</strong></p>";
               
                $connection_data = acx_connection_array();

                foreach ($connection_data as $cid=>$connection)
                {
                  extract($connection);

                  $html .= "
                  <a href='javascript:void(0);' id='acx-sync-button' data-wpend='front' data-cid='{$cid}'>
                  <div style='display:inline-block;width:100%;border:1px solid $app_color;padding:10px;border-radius:5px;'>
                  <div style='float:left;color:$app_color'>
                  <h5>$title</h5>$app_name
                  </div>
                  <div style='float:right;color:$app_color'>
                  <span style='font-size:25px;width:25px;height:25px;' id='acx-sync-icon-$cid' class='dashicons dashicons-image-rotate'></span>
                  </div>
                  </div></a>
                  ";
                }
                
                $html .= "</br></br><p><a href='$plugins_page&main=data'> Manage data</a></p>"; // 

              

          }

          //-----------------------

          if ($show == 'page'){

			$page_data = acx_page_check($pid);

			// the context of this has changed -- page only shows data about the page (not click to copy) 
			
			  $html .= "<div style='text-align:center'>";

            if (is_array($page_data))
            {

              extract($page_data, EXTR_PREFIX_ALL, 'page');

              if (is_array($page_fields))
              {

                if ($page_image)
                {
                $html .= "<img style='margin:20px;height:80px;width:80px;object-fit:cover;border-radius:50px;' src='$page_image' />"; // 
                }

                $html .= "<h4>$page_heading</h4>"; // 

                $html .= "<ul class='zoom-card-content'>";

                foreach($page_fields as $key => $value)
                {
                $html .= "<li><b>$key</b> $value</li>"; // DISPLAY THE VALUE
                }
                $html .= "</ul>";
              }
              else 
              {
                $html .= "<h4>No dynamic values.</h4>"; // 
                $html .= "<p>This page does not appear to be a dynamic page. You can create dynamic pages in the <a href='$plugins_page&main=pages'>Airconnex Pages</a> area of your wordpress admin.</p>"; // 
              }

            }
            
            $html .= "</div>";
			

          }

          //-----------------------

          if ($show == 'logout_as') // -- removes the login as value
          {
          update_user_meta(get_current_user_id(),'acx_login_as','');
            if (!$is_admin)
            {
              $html = "<h4 style='text-align:center'>Logging out ...</h4>"; // 
              $response['after'] = 'reload'; // reload after response if frontend
            } 
            else{$show = 'user';} // otherwise show the user login as again
          }



          // shows login as, or user click / copy
		  
		  if ($show == 'user')
		  {
		  
		  $user_data = acx_user_data(); //
		  
		  if (is_array($user_data))
		  {
		  
		  if ($vid) // if we received a record (post) id then update the 'login_as'
          {
          update_user_meta(get_current_user_id(),'acx_login_as',$vid);
          if (!$is_admin){$response['after'] = 'reload';} // reload after response if frontend
          }
		  
		  if (isset($user_data['error']))
		  {
		  $html .= "<h4 style='text-align:center'>".$user_data['error']."</h4>"; // 
		  }
		  		  
		  if (isset($user_data['login_as']))
          {
              $html .= "<h4 style='text-align:center'>Choose a User ...</h4>"; // 
              $html .= acx_loop_compiler('LOGIN_AS');
          }
		  
		  if (isset($user_data['acx_id'])) // we are logged in as a user...
		  {
		  
		  $html .= "<h3 style='text-align:center'>User #".$user_data['acx_id']."</h3>"; // 

		  foreach($user_data['data'] as $cid => $data)
			{
				$html .= "<h4 style='text-align:center'>".$data['title']."</h4>"; // 
				
				$html .= "<ul class='zoom-card-content'>";
				
                foreach($data['fields'] as $key => $value)
                {
                $html .= "<li><b>$key</b> $value</li>"; // DISPLAY THE VALUE
				}
				//end foreach fields
				$html .= "</ul>";
				
			}
			// end foreach records
				 
				 
				$html .= "<div style='margin: 20px;text-align: center;'>"; 
              
                $html .= "<a class='btn btn-sm btn-outline-primary' href='javascript:void(0);' id='acx-menu-link' data-show='logout_as'>Logout</a>"; 

                $html .= "</div>";

		  
		  }
		  // isset($user_acx_id
		  
		  }
		  // end is_array($user_check))
		  
		  }
		  // end if user...
		  

          if ($show == 'userxxxx') // DEPRECATED@
		  {
          
            if ($vid) // if we received a record (post) id then update the 'login_as'
            {
            update_post_meta($pid,'acx_login_as',$vid);
            if (!$is_admin){$response['after'] = 'reload';} // reload after response if frontend
            }

            $page_data = acx_page_check($pid);

            extract($page_data, EXTR_PREFIX_ALL, 'page');

            if ($page_access)
            {
              if ($page_u_access['result'] == 'is_admin')
              {
              $html .= "<h4 style='text-align:center'>Choose a User ...</h4>"; // 
              $html .= acx_loop_compiler($page_access,'100','USER');
              }

              // if yes -- show the user, their meta and the 'logout' link

              if ($page_u_access['result'] == 'is_member')
              {

                $html .= "<div style='text-align:center'>";

                $subuser_data = acx_subuser_check($page_u_access['record']);

                extract($subuser_data, EXTR_PREFIX_ALL, 'subuser');

                if ($subuser_image)
                {
                $html .= "<img style='margin:20px;height:80px;width:80px;object-fit:cover;border-radius:50px;' src='$subuser_image' />"; // 
                }
                
                $html .= "<h4>$subuser_name</h4>"; // 
                //$html .= "<p>$page_u_access</p>"; // 

                if ($is_admin)
                {
                  foreach($subuser_fields as $key => $value)
                  {
                  $html .= "<div class='click-copy-js' >u.$key</div>"; // DISPLAY THE VALUE
                  }
                }
              
                
                $html .= "<a class='acx-button acx-outline ' href='javascript:void(0);' id='acx-menu-link' data-show='logout_as'>Logout</a>"; 

                $html .= "</>";

              }
            }
            else {
              $html .= "<div style='text-align:center'>";
              $html .= "<h4>No user values.</h4>"; // 
              $html .= "<p>This page does not appear to be a user portal. You can make this page a portal from the <a href='$plugins_page&main=users'>Airconnex Users</a> area of your wordpress admin.</p>"; // 
              $html .= "</div>";
            }


          }
          
     
          //sync_page

          

          //--------------
              
          $response['html'] = "<div style=''>$html</div>";
          

          

          wp_die(json_encode($response));

          }else{
          //Return an error status if things result to a bad state.
          //You can omit this line and just return an HTML data with the error message instead.
          wp_die(json_encode([
            "status" => "error",
            "message" => "invalid request."
          ]));
          }
}



function airconnex_menu_html_js() {

		$action = 'airconnex_menu_html'; //Action for the ajax function
		$nonce = wp_create_nonce($action); //Nonce for security

		?>
		<script type="text/javascript" >
			jQuery(document).ready(function($) {

      ajaxurl = '<?php echo admin_url( 'admin-ajax.php' ) ?>'; // get ajaxurl

			$(document).on('click', '#acx-menu-link', function(){
				var show = $(this).data('show')
        var vid = $(this).data('vid')
				var data = {
				action: '<?=$action?>',
				show: show,
        vid: vid,
				nonce: '<?=$nonce?>'
				};


			$('#acx-menu-load').removeClass('hidden');
			$("#acx-menu-float").addClass('hidden');
			$("#acx-menu-block").addClass('hidden');
			

			$.post(ajaxurl, data, function(response) {

			var data = $.parseJSON(response),after = data['after'],html = data['html']

			$("#acx-menu-load").addClass('hidden');

			if (after == 'show_block')
			{
			$('#acx-menu-block').removeClass('hidden');
			$("#acx-menu-float").addClass('hidden');
			}
			else
			{
			$('#acx-menu-float').removeClass('hidden');
			$("#acx-menu-block").addClass('hidden');
			$('#acx-menu-float').html(html)
			}
			
			if (after == 'reload')
			{
			location.reload();
			}
       

				});
			});
			});
		</script>
		<?php

}


// preloads all connection / fields into the click to copy menu

function div_block_fields($page_data)
{

$page_fields = null;

  $html = "<ul class='zoom-card-content'>";
  
	 // get all connections, and show the fields for them as click / copy 
		 
		 $connection_array = acx_connection_array();

                foreach ($connection_array as $cid=>$connection)
                {
				
				
                  
				$html .= "<div id ='acx-block-fields-$cid' class='hidden' style='text-align:center;border-bottom:1px solid silver;margin-bottom:20px;'>"; //padding:20px;

				if (is_array($connection))
				{
	
				extract($connection, EXTR_PREFIX_ALL, 'connection');

              if (is_array($connection_fields))
              {
				$html .= "<h4>Block Data</h4>"; //connection
				
                foreach($connection_fields as $key => $value)
                {
				$html .= "<li class='click-copy-js' data-copyval='$key' ><i style='margin-right:5px' class='fas fa-th'></i>$key</li>";
                }
                
              }
        

            }
			$html .= "</div>";
			}
            // end foreach connection
			
			
			// PAGE DYNAMIC DATA  --------
			
            if (is_array($page_data))
            {
			$html .= "<div style='text-align:center;'>";//padding:20px;
             
			extract($page_data, EXTR_PREFIX_ALL, 'page');
			
			if (isset($page_x_title))
			{
						
			$html .= "<h4>Page &rarr; ".$page_x_title."</h4>"; //connection

              if (is_array($page_fields))
              {

                foreach($page_fields as $key => $value)
                {
                //$html .= "<li class='click-copy-js' >p.$key</li>"; // DISPLAY THE VALUE
				
				$html .= "<li class='click-copy-js' data-copyval='p.$key' ><i style='margin-right:5px' class='fas fa-file'></i>$key</li>";

                }
                
              }
			$html .= "</div>";
            }
            }
			// end if template title 
			
            
			// USER DATA  --------
			
			$user_data = acx_user_data(); //
			
			if (is_array($user_data['data']))
			{
						
			$html .= "<div style='text-align:center;'>";//padding:20px;
			
			
			foreach($user_data['data'] as $cid => $data)
			{
			$html .= "<h4>User &rarr; ".$data['title']."</h4>"; //connection
			
			foreach($data['fields'] as $key => $value)
            {
			$html .= "<li class='click-copy-js' data-copyval='u.$cid.$key'><i style='margin-right:5px' class='fas fa-user'></i>$key</li>"; // DISPLAY THE VALUE
			}
			
			}
			
			$html .= "</div>";
			
			}
			
			
			$html .= "</ul>";
	
			return $html;

}




function airconnex_menu_float($menu_config){

  global $post;

if (isset($post))
{
  
  $block_fields = NULL;
  $loading_spinner = NULL;

  $post_id = $post->ID;
  
  $page_data = acx_page_check($post_id);
  
    if ($menu_config =='editor')
    {
	
	$right_offset = 320;

        // if the post has a cid (is dynamic) 
        // if it has page_access (user click to copy)
        // show the sync
        $buttons = "
        <li><a href='javascript:void(0);' id='acx-menu-link' data-show='user' class='zoom-fab zoom-btn-sm zoom-btn-user scale-transition scale-out'><i class='fas fa-user'></i></a></li>
        <li><a href='javascript:void(0);' id='acx-menu-link' data-show='page' class='zoom-fab zoom-btn-sm zoom-btn-code scale-transition scale-out'><i class='fas fa-file'></i></a></li>
        <li><a href='javascript:void(0);' id='acx-menu-link' data-show='data' class='zoom-fab zoom-btn-sm zoom-btn-data scale-transition scale-out'><i class='fas fa-database'></i></a></li>
        <li><a href='javascript:void(0);' id='acx-menu-link' data-show='block' class='zoom-fab zoom-btn-sm zoom-btn-data scale-transition scale-out'><i class='fas fa-bolt'></i></a></li>
		";
		
		// ONLY IN THE EDITOR SHOW THE BLOCK / CLICK TO COPY DIV ------
		
		$block_fields = "<div id='acx-menu-block'>".div_block_fields($page_data)."</div>"; //<div id='acx-menu-block-none'>Choose a Block...</div>
		
		$loading_spinner = "<div id='acx-menu-load' style='text-align:center;padding:20px;'><h4>Loading</h4><span class='dashicons dashicons-image-rotate dashicons-spin'></span></div>";
    }

    if ($menu_config =='frontend')
    {
	
	$right_offset = 80;
	
        //Always show sync
        //page (if dynamic) sync single record.
        //user > login as
        $buttons = "
        <li><a href='javascript:void(0);' id='acx-menu-link' data-show='user' class='zoom-fab zoom-btn-sm zoom-btn-user scale-transition scale-out'><i class='fas fa-user'></i></a></li>
        <li><a href='javascript:void(0);' id='acx-menu-link' data-show='page' class='zoom-fab zoom-btn-sm zoom-btn-code scale-transition scale-out'><i class='fas fa-file'></i></a></li>
        <li><a href='javascript:void(0);' id='acx-menu-link' data-show='data' class='zoom-fab zoom-btn-sm zoom-btn-data scale-transition scale-out'><i class='fas fa-database'></i></a></li>
        ";
    }

    ?>

    <div class="zoom">
      <a class="zoom-fab zoom-btn-large" id="zoomBtn"><i class="fas fa-bars"></i></a>
      <ul class="zoom-menu">
    <?php echo $buttons; ?>
      </ul>
      <div class="zoom-card scale-transition scale-out">
	  <?php echo $loading_spinner; ?>
	  <div id='acx-menu-float'></div>
	  <?php echo $block_fields; ?>
	  </div>
    </div>

    <script type='text/javascript'>

    (function($){
      $('#zoomBtn').click(function() {
          $('.zoom-btn-sm').toggleClass('scale-out');
          if (!$('.zoom-card').hasClass('scale-out')) {
            $('.zoom-card').toggleClass('scale-out');
          }
        });
        
        $('.zoom-btn-sm').click(function() {
          var btn = $(this);
          var card = $('.zoom-card');
          if ($('.zoom-card').hasClass('scale-out')) {
            $('.zoom-card').toggleClass('scale-out');
          }

 
        });
      })(jQuery)

    </script>


    <style>

              .zoom {
                position: fixed;
                bottom: 30px;
                right: <?php echo $right_offset; ?>px;
                height: 70px;
                z-index: 10000;
				
              }
			  
			  h3
			  {
			  font-size:18px;line-height:300%;margin-bottom:5px !important;
			  }
			  h4
			  {
			  font-size:16px;line-height:300%;margin-bottom:5px !important;
			  }
			  h5
			  {
			  font-size:14px;line-height:300%;margin-bottom:5px !important;
			  }
			
              
              .zoom-fab {
                display: inline-block;
                width: 40px;
                height: 40px;
                line-height: 40px;
                border-radius: 50%;
                background-color: #e605aa;
                vertical-align: middle;
                text-decoration: none;
                text-align: center;
                transition: 0.2s ease-out;
                box-shadow: 0 2px 2px 0 rgba(0, 0, 0, 0.14), 0 1px 5px 0 rgba(0, 0, 0, 0.12), 0 3px 1px -2px rgba(0, 0, 0, 0.2);
                cursor: pointer;
                color: #FFF;
              }
              
              .zoom-fab:hover {
                background-color: #a5147e;
                color: #FFF;
                box-shadow: 0 3px 3px 0 rgba(0, 0, 0, 0.14), 0 1px 7px 0 rgba(0, 0, 0, 0.12), 0 3px 1px -1px rgba(0, 0, 0, 0.2);
              }
              
              .zoom-btn-large {
                width: 60px;
                height: 60px;
                line-height: 60px;
              }
              
              .zoom-menu {
                position: absolute;
                right: 70px;
                left: auto;
                top: 50%;
                transform: translateY(-50%);
                height: 100%;
                width: 500px;
                list-style: none;
                text-align: right;
              }
              
              .zoom-menu li {
                display: inline-block;
                margin-right: 10px;
              }
              
              .zoom-card {
				font-size: 12px;
                position: absolute;
                right: 20%;
                bottom: 80px;
                min-width:220px;
                min-height:300px;
                transition: box-shadow 0.25s;
                padding: 24px;
                border-radius: 15px;
                background-color: #e605aa;
                box-shadow: 0 2px 2px 0 rgba(0, 0, 0, 0.14), 0 1px 5px 0 rgba(0, 0, 0, 0.12), 0 3px 1px -2px rgba(0, 0, 0, 0.2);
                background-color: white;
              }
              
              .zoom-card ul {
                -webkit-padding-start: 0;
                list-style: none;
                text-align: left;
				overflow-y: scroll;
				max-height: 50vh;
              }

              ::-webkit-scrollbar
              {
              width: 10px;  /* for vertical scrollbars */
              height: 10px; /* for horizontal scrollbars */
              }
              ::-webkit-scrollbar-track
              {
              background: white;
              }
              ::-webkit-scrollbar-thumb
              {
              border-radius:5px;
              background: #610979;
              }

                .zoom-btn-user { background-color: #64c713; }

                .zoom-btn-user:hover { background-color: #538828; }

                .zoom-btn-code { background-color: #9110b4; }

                .zoom-btn-code:hover { background-color: #722a86; }

                .zoom-btn-data { background-color: #2cbfec; }

                .zoom-btn-data:hover { background-color: #315c9a; }

                .zoom-btn-report { background-color: #457ca8; }

                .zoom-btn-report:hover { background-color: #64b5f6; }

                .zoom-btn-feedback { background-color: #9c27b0; }

                .zoom-btn-feedback:hover { background-color: #ba68c8; }


              .scale-transition { transition: transform 0.3s cubic-bezier(0.53, 0.01, 0.36, 1.63) !important; }

                .scale-transition.scale-out {
                transform: scale(0);
                transition: transform 0.2s !important;
                }

                .scale-transition.scale-in { transform: scale(1); }
    </style>

    <?php
	
	}
	// end if post
	
}




function airconnex_fontawesome()
{
  ?>
  <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.7.1/css/all.css" integrity="sha384-fnmOCqbTlWIlj8LyTjo7mOUStjsKC4pOpQbqyi7RrhN7udi9RwhKkMHpvLbHG9Sr" crossorigin="anonymous">
  <?php

}







function airconnex_click_copy_css(){

	echo "
    <style>
       
	    .click-copy-js {
                display: block;
                border: 2px solid;
                font-weight: normal;
                border-radius: 5px;
                padding: 5px 15px;
                cursor: pointer;
                user-select: none;
                margin: 5px 0;
                font-size: 14px;
                text-align: left;
            }

 
            .click-copy-js-copied:after{
				content: 'copied';
				margin-left: 5px ;
                font-weight: bold;
				color : green;
            }


        
    </style>";

}


