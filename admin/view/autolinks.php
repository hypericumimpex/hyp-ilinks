<?php

        if ( !current_user_can(get_option( $this->shared->get('slug') . "_ail_menu_required_capability")) )  {
                wp_die( esc_attr__( 'You do not have sufficient permissions to access this page.' ) );
        }

        ?>

        <!-- process data -->

        <?php
        
         if( isset( $_POST['update_id'] ) or isset($_POST['form_submitted']) ){
             
            extract($_POST);
             
            $invalid_data_message = '';
             
            //validation on "Keyword"
            if( strlen( trim($keyword) ) == 0 or strlen($keyword) > 255 ){
                $invalid_data_message .= '<div class="error settings-error notice is-dismissible below-h2"><p>' . esc_attr__('Please enter a valid value in the "Keyword" field.', 'daim') . '</p></div>';
                $invalid_data = true;
            }

	         /*
              * Do not allow to create specific keywords that would be able to replace the start delimiter of the
	          * protected block [pb], part of the start delimiter, the end delimited [/pb] or part of the end delimiter.
              */
	         if(preg_match('/^\[$|^\[p$|^\[pb$|^\[pb]$|^\[\/$|^\[\/p$|^\[\/pb$|^\[\/pb\]$|^\]$|^b\]$|^pb\]$|^\/pb\]$|^p$|^pb$|^pb\]$|^\/$|^\/p$|^\/pb$|^\/pb]$|^b$|^b\$/i', $keyword) === 1){
		         $invalid_data_message .= '<div class="error settings-error notice is-dismissible below-h2"><p>' . esc_attr__('The specified keyword is not allowed.',
				         'daim') . '</p></div>';
		         $invalid_data         = true;
		         $specified_keyword_not_allowed = true;
	         }

	         /*
              * Do not allow to create specific keywords that would be able to replace the start delimiter of the
	          * autolink [ail], part of the start delimiter, the end delimited [/ail] or part of the end delimiter.
              */
	         if(!isset($specified_keyword_not_allowed) and preg_match('/^\[$|^\[a$|^\[ai$|^\[ail$|^\[ail\]$|^a$|^ai$|^ail$|^ail\]$|^i$|^il$|^il\]$|^l$|^l\]$|^\]$|^\[$|^\[\/$|^\[\/a$|^\[\/ai$|^\[\/ail$|^\[\/ail\]$|^\/$|^\/]$|^\/a$|^\/ai$|^\/ail$|^\/ail\]$/i', $keyword) === 1){
		         $invalid_data_message .= '<div class="error settings-error notice is-dismissible below-h2"><p>' . esc_attr__('The specified keyword is not allowed.',
				         'daim') . '</p></div>';
		         $invalid_data         = true;
	         }
            
            //validation on "Target"
            if( strlen( trim($url) ) == 0 or
            strlen($keyword) > 2083 or
            !preg_match('/^(?!(http|https|fpt|file):\/\/)[-A-Za-z0-9+&@#\/%?=~_|$!:,.;]+$/', $url) ){
                $invalid_data_message .= '<div class="error settings-error notice is-dismissible below-h2"><p>' . esc_attr__('Please enter a valid URL Path and/or File in the "Target" field. ( the URL scheme and domain are implied )', 'daim') . '</p></div>';
                $invalid_data = true;
            }

             //validation on "Title"
             if( strlen($title) > 1024 ){
                 $invalid_data_message .= '<div class="error settings-error notice is-dismissible below-h2"><p>' . esc_attr__('Please enter a valid value in the "Title" field.', 'daim') . '</p></div>';
                 $invalid_data = true;
             }
            
            //validation on "Activate Post Types"
            if( !preg_match($this->shared->regex_list_of_post_types, $activate_post_types) ){
                $invalid_data_message .= '<div class="error settings-error notice is-dismissible below-h2"><p>' . esc_attr__('Please enter a valid list of post types separated by a comma in the "Post Types" field.', 'daim') . '</p></div>';
                $invalid_data = true;
            }
            
            //validation on "Max Number AIL"
            if( !preg_match($this->shared->regex_number_ten_digits, $max_number_autolinks) or intval($max_number_autolinks, 10) < 1 or intval($max_number_autolinks, 10) > 1000000 ){
                $invalid_data_message .= '<div class="error settings-error notice is-dismissible below-h2"><p>' . esc_attr__('Please enter a number from 1 to 1000000 in the "Max Number AIL" field.', 'daim') . '</p></div>';
                $invalid_data = true;
            }
            
            //validation on "Priority"
            if( !preg_match($this->shared->regex_number_ten_digits, $priority) or intval($priority, 10) > 1000000 ){
                $invalid_data_message .= '<div class="error settings-error notice is-dismissible below-h2"><p>' . esc_attr__('Please enter a number from 0 to 1000000 in the "Priority" field.', 'daim') . '</p></div>';
                $invalid_data = true;
            } 
            
        }
        
        //update ---------------------------------------------------------------
        if( isset( $_POST['update_id'] ) and !isset($invalid_data) ){
            
            //update the database
            global $wpdb;
            $table_name = $wpdb->prefix . $this->shared->get('slug') . "_autolinks";
            $safe_sql = $wpdb->prepare("UPDATE $table_name SET 
                keyword = %s,
                url = %s,
                title = %s,
                string_before = %d,
                string_after = %d,
                activate_post_types = %s,
                max_number_autolinks = %d,
                case_insensitive_search = %d,
                open_new_tab = %d,
                use_nofollow = %d,
                priority = %d
                WHERE id = %d",
                $keyword,
                $url,
                $title,
                $string_before,
                $string_after,
                $activate_post_types,
                $max_number_autolinks,
                $case_insensitive_search,
                $open_new_tab,
                $use_nofollow,
                $priority,
                $update_id);

            $query_result = $wpdb->query( $safe_sql );

            if($query_result !== false){
                $process_data_message = '<div class="updated settings-error notice is-dismissible below-h2"><p>The autolink has been successfully updated.</p></div>';
            }
            
        }else{
            
            //add ------------------------------------------------------------------
            if( isset($_POST['form_submitted']) and !isset($invalid_data) ){

                //insert into the database
                global $wpdb;
                $table_name = $wpdb->prefix . $this->shared->get('slug') . "_autolinks";
                $safe_sql = $wpdb->prepare("INSERT INTO $table_name SET 
                    keyword = %s,
                    url = %s,
                    title = %s,
                    string_before = %d,
                    string_after = %d,
                    activate_post_types = %s,
                    max_number_autolinks = %d,
                    case_insensitive_search = %d,
                    open_new_tab = %d,
                    use_nofollow = %d,
                    priority = %d",
                    $keyword,
                    $url,
                    $title,
                    $string_before,
                    $string_after,
                    $activate_post_types,
                    $max_number_autolinks,
                    $case_insensitive_search,
                    $open_new_tab,
                    $use_nofollow,
                    $priority
                    );

                $query_result = $wpdb->query( $safe_sql );

                if($query_result !== false){
                    $process_data_message = '<div class="updated settings-error notice is-dismissible below-h2"><p>' . esc_attr__('The autolink has been successfully added.', 'daim') . '</p></div>';
                }

            }
            
        }
        
        //delete an autolink
        if( isset( $_POST['delete_id']) ){

            global $wpdb;
            $delete_id = intval($_POST['delete_id'], 10);
                
            //delete this game
            $table_name = $wpdb->prefix . $this->shared->get('slug') . "_autolinks";
            $safe_sql = $wpdb->prepare("DELETE FROM $table_name WHERE id = %d ", $delete_id);

            $query_result = $wpdb->query( $safe_sql ); 

            if($query_result !== false){
                $process_data_message = '<div class="updated settings-error notice is-dismissible below-h2"><p>The autolink has been successfully deleted.</p></div>';
            }
            
        }
        
        //get the autolink data
        if(isset($_GET['edit_id'])){
            $edit_id = intval($_GET['edit_id'], 10);
            global $wpdb;
            $table_name = $wpdb->prefix . $this->shared->get('slug') . "_autolinks";
            $safe_sql = $wpdb->prepare("SELECT * FROM $table_name WHERE id = %d ", $edit_id);
            $autolink_obj = $wpdb->get_row($safe_sql); 
        }
        
        ?>
        
        <!-- output -->

        <div class="wrap">

            <div id="daext-header-wrapper" class="daext-clearfix">

                <h2><?php esc_attr_e('Interlinks Manager - AIL', 'daim'); ?></h2>

                <form action="admin.php" method="get">
                    <input type="hidden" name="page" value="daim-autolinks">
                    <?php
                    if (isset($_GET['s']) and strlen(trim($_GET['s'])) > 0) {
                        $search_string = $_GET['s'];
                    } else {
                        $search_string = '';
                    }
                    ?>
                    <input type="text" name="s" placeholder="<?php esc_attr_e('Search...', 'daim'); ?>"
                           value="<?php echo esc_attr(stripslashes($search_string)); ?>" autocomplete="off" maxlength="255">
                    <input type="submit" value="">
                </form>

            </div>

            <div id="daext-menu-wrapper">

            <?php if(isset($invalid_data_message)){echo $invalid_data_message;} ?>
            <?php if(isset($process_data_message)){echo $process_data_message;} ?>
            
            <!-- table -->

            <?php

            //create the query part used to filter the results when a search is performed
            if (isset($_GET['s']) and strlen(trim($_GET['s'])) > 0) {
                $search_string = $_GET['s'];
                global $wpdb;
                $filter = $wpdb->prepare('WHERE (keyword LIKE %s OR url LIKE %s)', '%' . $search_string . '%', '%' . $search_string . '%');
            } else {
                $filter = '';
            }

            //retrieve the total number of autolinks
            global $wpdb;
            $table_name=$wpdb->prefix . $this->shared->get('slug') . "_autolinks";
            $total_items = $wpdb->get_var( "SELECT COUNT(*) FROM $table_name $filter");

            //Initialize the pagination class
            require_once( $this->shared->get('dir') . '/admin/inc/class-daim-pagination.php' );
            $pag = new daim_pagination();
            $pag->set_total_items( $total_items );//Set the total number of items
            $pag->set_record_per_page( intval(get_option($this->shared->get('slug') . '_pagination_ail_menu'), 10) ); //Set records per page
            $pag->set_target_page( "admin.php?page=" . $this->shared->get('slug') . "-autolinks" );//Set target page
            $pag->set_current_page();//set the current page number from $_GET

            ?>

            <!-- Query the database -->
            <?php
            $query_limit = $pag->query_limit();
            $results = $wpdb->get_results("SELECT * FROM $table_name $filter ORDER BY id DESC $query_limit", ARRAY_A); ?>

            <?php if( count($results) > 0 ) : ?>

                <div class="daext-items-container">

                    <!-- list of tables -->
                    <table class="daext-items">
                        <thead>
                            <tr>
                                <th>
                                    <div><?php esc_attr_e('Keyword', 'daim'); ?></div>
                                    <div class="help-icon" title="<?php esc_attr_e('The keyword that will be converted to a link.', 'daim'); ?>"></div>
                                </th>
                                <th>
                                    <div><?php esc_attr_e('Target', 'daim'); ?></div>
                                    <div class="help-icon" title="<?php esc_attr_e('The target of the link automatically generated on the keyword.', 'daim'); ?>"></div>
                                </th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>

                        <?php foreach($results as $result) : ?>
                            <tr>
                                <td><?php echo esc_attr(stripslashes($result['keyword'])); ?></td>
                                <td><a href="<?php echo esc_url( get_home_url() . $result['url'] ); ?>"><?php echo esc_url( get_home_url() . $result['url'] ); ?></a></td>
                                <td class="icons-container">
                                    <a class="menu-icon edit" href="admin.php?page=<?php echo $this->shared->get('slug'); ?>-autolinks&edit_id=<?php echo $result['id']; ?>"></a>
                                    <form method="POST" action="admin.php?page=<?php echo $this->shared->get('slug'); ?>-autolinks">
                                        <input type="hidden" value="<?php echo $result['id']; ?>" name="delete_id" >
                                        <input class="menu-icon delete" type="submit" value="">
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>

                        </tbody>

                    </table>

                </div>

                <!-- Display the pagination -->
                <?php if($pag->total_items > 0) : ?>
                    <div class="daext-tablenav daext-clearfix">
                        <div class="daext-tablenav-pages">
                            <span class="daext-displaying-num"><?php echo $pag->total_items; ?> <?php esc_attr_e('items', 'daim'); ?></span>
                            <?php $pag->show(); ?>
                        </div>
                    </div>
                <?php endif; ?>

            <?php else : ?>

                <?php

                if(strlen(trim($filter)) > 0){
                    echo '<p class="no-search-results">' . esc_attr__('There are no results that match your search.', 'daim') . '</p>';
                }

                ?>

            <?php endif; ?>

             <form method="POST" action="admin.php?page=<?php echo $this->shared->get('slug'); ?>-autolinks" autocomplete="off">

                <input type="hidden" value="1" name="form_submitted">
                 
                <?php if(isset($_GET['edit_id'])) : ?>

                    <!-- Edit an Autolink -->

                    <div class="daext-form-container">

                        <h3 class="daext-form-title"><?php esc_attr_e('Edit AIL', 'daim'); ?> <?php echo $autolink_obj->id; ?></h3>

                        <table class="daext-form">

                            <input type="hidden" name="update_id" value="<?php echo $autolink_obj->id; ?>" />

                             <!-- Keyword -->
                             <tr valign="top">
                                 <th scope="row"><label for="keyword"><?php esc_attr_e('Keyword', 'daim'); ?></label></th>
                                 <td>
                                     <input value="<?php echo esc_attr(stripslashes($autolink_obj->keyword)); ?>" type="text" id="keyword" maxlength="255" size="30" name="keyword" placeholder="<?php esc_attr_e('The Keyword', 'daim'); ?>" />
                                     <div class="help-icon" title="<?php esc_attr_e('The keyword that will be converted to a link.', 'daim'); ?>"></div>
                                 </td>
                             </tr>

                             <!-- URL -->
                             <tr valign="top">
                                 <th scope="row"><label for="url"><?php esc_attr_e('Target (URL Path and/or File)', 'daim'); ?></label></th>
                                 <td>
                                     <input value="<?php echo esc_attr(stripslashes($autolink_obj->url)); ?>" type="text" id="url" maxlength="2083" size="30" name="url" placeholder="<?php esc_attr_e('/hello-world/', 'daim'); ?>" />
                                     <div class="help-icon" title="<?php esc_attr_e('The target of the link automatically generated on the keyword. Please note that the URL scheme and domain are implied.', 'daim'); ?>"></div>
                                 </td>
                             </tr>

                            <!-- Title -->
                            <tr valign="top">
                                <th scope="row"><label for="title"><?php esc_attr_e('Title', 'daim'); ?></label></th>
                                <td>
                                    <input value="<?php echo esc_attr(stripslashes($autolink_obj->title)); ?>" type="text" id="title" maxlength="1024" size="30" name="title" />
                                    <div class="help-icon" title="<?php esc_attr_e('The title attribute of the link automatically generated on the keyword.', 'daim'); ?>"></div>
                                </td>
                            </tr>

                            <!-- Open New Tab -->
                            <tr>
                                <th scope="row"><?php esc_attr_e('Open New Tab', 'daim'); ?></th>
                                <td>
                                    <select id="open-new-tab" name="open_new_tab" class="daext-display-none">
                                        <option value="0" <?php selected($autolink_obj->open_new_tab, 0); ?>><?php esc_attr_e('No', 'daim'); ?></option>
                                        <option value="1" <?php selected($autolink_obj->open_new_tab, 1); ?>><?php esc_attr_e('Yes', 'daim'); ?></option>
                                    </select>
                                    <div class="help-icon" title='<?php esc_attr_e('If you select "Yes" the link generated on the defined keyword opens the linked document in a new tab.', 'daim'); ?>'></div>
                                </td>
                            </tr>

                            <!-- Use Nofollow -->
                            <tr>
                                <th scope="row"><?php esc_attr_e('Use Nofollow', 'daim'); ?></th>
                                <td>
                                    <select id="use-nofollow" name="use_nofollow" class="daext-display-none">
                                        <option value="0" <?php selected($autolink_obj->use_nofollow, 0); ?>><?php esc_attr_e('No', 'daim'); ?></option>
                                        <option value="1" <?php selected($autolink_obj->use_nofollow, 1); ?>><?php esc_attr_e('Yes', 'daim'); ?></option>
                                    </select>
                                    <div class="help-icon" title='<?php esc_attr_e('If you select "Yes" the link generated on the defined keyword will include the rel="nofollow" attribute.', 'daim'); ?>'></div>
                                </td>
                            </tr>

                            <!-- Activate Post Types  -->
                            <tr valign="top">
                                <th scope="row"><label for="activate-post-types"><?php esc_attr_e('Post Types', 'daim'); ?></label></th>
                                <td>
                                    <input value="<?php echo esc_attr(stripslashes($autolink_obj->activate_post_types)); ?>" type="text" id="activate-post-types" maxlength="1000" size="30" name="activate_post_types" placeholder="<?php esc_attr_e('The Activate Post Types', 'daim'); ?>" />
                                    <div class="help-icon" title="<?php esc_attr_e('Enter a list of post types separated by a comma. With this option you are able to determine in which post types the defined keyword will be automatically converted to a link.', 'daim'); ?>"></div>
                                </td>
                            </tr>

                            <!-- Case Insensitive Search -->
                            <tr>
                                <th scope="row"><?php esc_attr_e('Case Insensitive Search', 'daim'); ?></th>
                                <td>
                                    <select id="case-insensitive-search" name="case_insensitive_search" class="daext-display-none">
                                        <option value="0" <?php selected($autolink_obj->case_insensitive_search, 0); ?>><?php esc_attr_e('No', 'daim'); ?></option>
                                        <option value="1" <?php selected($autolink_obj->case_insensitive_search, 1); ?>><?php esc_attr_e('Yes', 'daim'); ?></option>
                                    </select>
                                    <div class="help-icon" title='<?php esc_attr_e('If you select "Yes" your keyword will match both lowercase and uppercase variations.', 'daim'); ?>'></div>
                                </td>
                            </tr>

                            <!-- Left Boundary -->
                            <tr>
                                <th scope="row"><?php esc_attr_e('Left Boundary', 'daim'); ?></th>
                                <td>
                                    <select id="left-boundary" name="string_before" class="daext-display-none">
                                        <option value="1" <?php selected($autolink_obj->string_before, 1); ?>><?php esc_attr_e('Generic', 'daim'); ?></option>
                                        <option value="2" <?php selected($autolink_obj->string_before, 2); ?>><?php esc_attr_e('White Space', 'daim'); ?></option>
                                        <option value="3" <?php selected($autolink_obj->string_before, 3); ?>><?php esc_attr_e('Comma', 'daim'); ?></option>
                                        <option value="4" <?php selected($autolink_obj->string_before, 4); ?>><?php esc_attr_e('Point', 'daim'); ?></option>
                                        <option value="5" <?php selected($autolink_obj->string_before, 5); ?>><?php esc_attr_e('None', 'daim'); ?></option>
                                    </select>
                                    <div class="help-icon" title='<?php esc_attr_e('The "Left Boundary" option can be used to target keywords preceded by a generic boundary or by a specific character.', 'daim'); ?>'></div>
                                </td>
                            </tr>

                            <!-- Right Boundary -->
                            <tr>
                                <th scope="row"><?php esc_attr_e('Right Boundary', 'daim'); ?></th>
                                <td>
                                    <select id="right-boundary" name="string_after" class="daext-display-none">
                                        <option value="1" <?php selected($autolink_obj->string_after, 1); ?>><?php esc_attr_e('Generic', 'daim'); ?></option>
                                        <option value="2" <?php selected($autolink_obj->string_after, 2); ?>><?php esc_attr_e('White Space', 'daim'); ?></option>
                                        <option value="3" <?php selected($autolink_obj->string_after, 3); ?>><?php esc_attr_e('Comma', 'daim'); ?></option>
                                        <option value="4" <?php selected($autolink_obj->string_after, 4); ?>><?php esc_attr_e('Point', 'daim'); ?></option>
                                        <option value="5" <?php selected($autolink_obj->string_after, 5); ?>><?php esc_attr_e('None', 'daim'); ?></option>
                                    </select>
                                    <div class="help-icon" title='<?php esc_attr_e('The "Right Boundary" option can be used to target keywords followed by a generic boundary or by a specific character.', 'daim'); ?>'></div>
                                </td>
                            </tr>

                            <!-- Max Number Autolinks  -->
                            <tr valign="top">
                                <th scope="row"><label for="max-number-autolinks"><?php esc_attr_e('Limit', 'daim'); ?></label></th>
                                <td>
                                    <input value="<?php echo esc_attr(stripslashes($autolink_obj->max_number_autolinks)); ?>" type="text" id="max-number-autolinks" maxlength="7" size="30" name="max_number_autolinks" placeholder="<?php esc_attr_e('The Max Number of Autolinks', 'daim'); ?>" />
                                    <div class="help-icon" title="<?php esc_attr_e('With this option you can determine the maximum number of matches of the defined keyword automatically converted to a link.', 'daim'); ?>"></div>
                                </td>
                            </tr>

                            <!-- Priority -->
                            <tr valign="top">
                                <th scope="row"><label for="priority"><?php esc_attr_e('Priority', 'daim'); ?></label></th>
                                <td>
                                    <input value="<?php echo intval($autolink_obj->priority, 10); ?>" type="text" id="priority" maxlength="7" size="30" name="priority" placeholder="<?php esc_attr_e('The Priority', 'daim'); ?>" />
                                    <div class="help-icon" title='<?php esc_attr_e('The priority value determines the order used to apply the AIL on the post.', 'daim'); ?>'></div>
                                </td>
                            </tr>
                            
                        </table>

                        <!-- submit button -->
                        <div class="daext-form-action">
                            <input class="button" type="submit" value="<?php esc_attr_e('Update Autolink', 'daim'); ?>" >
                            <input id="cancel" class="button" type="submit" value="<?php esc_attr_e('Cancel', 'daim'); ?>">
                        </div>

                <?php else : ?>

                    <!-- Create New Autolink -->

                    <div class="daext-form-container">

                        <div class="daext-form-title"><?php esc_attr_e('Create New AIL', 'daim'); ?></div>

                             <table class="daext-form">

                                 <!-- Keyword -->
                                 <tr valign="top">
                                     <th scope="row"><label for="keyword"><?php esc_attr_e('Keyword', 'daim'); ?></label></th>
                                     <td>
                                         <input type="text" id="keyword" maxlength="255" size="30" name="keyword" placeholder="<?php esc_attr_e('The Keyword', 'daim'); ?>" />
                                         <div class="help-icon" title="<?php esc_attr_e('The keyword that will be converted to a link.', 'daim'); ?>"></div>
                                     </td>
                                 </tr>
                                 
                                 <!-- URL -->
                                 <tr valign="top">
                                     <th scope="row"><label for="url"><?php esc_attr_e('Target (URL Path and/or File)', 'daim'); ?></label></th>
                                     <td>
                                         <input type="text" id="url" maxlength="2083" size="30" name="url" placeholder="<?php esc_attr_e('/hello-world/', 'daim'); ?>" />
                                         <div class="help-icon" title="<?php esc_attr_e('The target of the link automatically generated on the keyword. Please note that the URL scheme and domain are implied.', 'daim'); ?>"></div>
                                     </td>
                                 </tr>

                                 <!-- Title -->
                                 <tr valign="top">
                                     <th scope="row"><label for="title"><?php esc_attr_e('Title', 'daim'); ?></label></th>
                                     <td>
                                         <input value="" type="text" id="title" maxlength="1024" size="30" name="title" />
                                         <div class="help-icon" title="<?php esc_attr_e('The title attribute of the link automatically generated on the keyword.', 'daim'); ?>"></div>
                                     </td>
                                 </tr>

                                 <!-- Open New Tab -->
                                 <tr>
                                     <th scope="row"><?php esc_attr_e('Open New Tab', 'daim'); ?></th>
                                     <td>
                                         <select id="open-new-tab" name="open_new_tab" class="daext-display-none">
                                             <option value="0" <?php selected(intval(get_option($this->shared->get('slug') . '_default_open_new_tab'), 10), 0); ?>><?php esc_attr_e('No', 'daim'); ?></option>
                                             <option value="1" <?php selected(intval(get_option($this->shared->get('slug') . '_default_open_new_tab'), 10), 1); ?>><?php esc_attr_e('Yes', 'daim'); ?></option>
                                         </select>
                                         <div class="help-icon" title='<?php esc_attr_e('If you select "Yes" the link generated on the defined keyword opens the linked document in a new tab.', 'daim'); ?>'></div>
                                     </td>
                                 </tr>

                                 <!-- Use Nofollow -->
                                 <tr>
                                     <th scope="row"><?php esc_attr_e('Use Nofollow', 'daim'); ?></th>
                                     <td>
                                         <select id="use-nofollow" name="use_nofollow" class="daext-display-none">
                                             <option value="0" <?php selected(intval(get_option($this->shared->get('slug') . '_default_use_nofollow'), 10), 0); ?>><?php esc_attr_e('No', 'daim'); ?></option>
                                             <option value="1" <?php selected(intval(get_option($this->shared->get('slug') . '_default_use_nofollow'), 10), 1); ?>><?php esc_attr_e('Yes', 'daim'); ?></option>
                                         </select>
                                         <div class="help-icon" title='<?php esc_attr_e('If you select "Yes" the link generated on the defined keyword will include the rel="nofollow" attribute.', 'daim'); ?>'></div>
                                     </td>
                                 </tr>

                                 <!-- Activate Post Types  -->
                                 <tr valign="top">
                                     <th scope="row"><label for="activate-post-types"><?php esc_attr_e('Post Types', 'daim'); ?></label></th>
                                     <td>
                                         <input value="<?php echo esc_attr(get_option($this->shared->get('slug') . '_default_activate_post_types')); ?>" type="text" id="activate-post-types" maxlength="1000" size="30" name="activate_post_types" placeholder="<?php esc_attr_e('The Activate Post Types', 'daim'); ?>" />
                                         <div class="help-icon" title="<?php esc_attr_e('Enter a list of post types separated by a comma. With this option you are able to determine in which post types the defined keyword will be automatically converted to a link.', 'daim'); ?>"></div>
                                     </td>
                                 </tr>

                                 <!-- Case Insensitive Search -->
                                 <tr>
                                     <th scope="row"><?php esc_attr_e('Case Insensitive Search', 'daim'); ?></th>
                                     <td>
                                         <select id="case-insensitive-search" name="case_insensitive_search" class="daext-display-none">
                                             <option value="0" <?php selected(intval(get_option($this->shared->get('slug') . '_default_case_insensitive_search'), 10), 0); ?>><?php esc_attr_e('No', 'daim'); ?></option>
                                             <option value="1" <?php selected(intval(get_option($this->shared->get('slug') . '_default_case_insensitive_search'), 10), 1); ?>><?php esc_attr_e('Yes', 'daim'); ?></option>
                                         </select>
                                         <div class="help-icon" title='<?php esc_attr_e('If you select "Yes" your keyword will match both lowercase and uppercase variations.', 'daim'); ?>'></div>
                                     </td>
                                 </tr>

                                <!-- Left Boundary -->
                                <tr>
                                    <th scope="row"><?php esc_attr_e('Left Boundary', 'daim'); ?></th>
                                    <td>
                                        <select id="left-boundary" name="string_before" class="daext-display-none">
                                            <option value="1" <?php selected(intval(get_option($this->shared->get('slug') . "_default_string_before")), 1); ?>><?php esc_attr_e('Generic', 'daim'); ?></option>
                                            <option value="2" <?php selected(intval(get_option($this->shared->get('slug') . "_default_string_before")), 2); ?>><?php esc_attr_e('White Space', 'daim'); ?></option>
                                            <option value="3" <?php selected(intval(get_option($this->shared->get('slug') . "_default_string_before")), 3); ?>><?php esc_attr_e('Comma', 'daim'); ?></option>
                                            <option value="4" <?php selected(intval(get_option($this->shared->get('slug') . "_default_string_before")), 4); ?>><?php esc_attr_e('Point', 'daim'); ?></option>
                                            <option value="5" <?php selected(intval(get_option($this->shared->get('slug') . "_default_string_before")), 5); ?>><?php esc_attr_e('None', 'daim'); ?></option>
                                        </select>
                                        <div class="help-icon" title='<?php esc_attr_e('The "Left Boundary" option can be used to target keywords preceded by a generic boundary or by a specific character.', 'daim'); ?>'></div>
                                    </td>
                                </tr>
                                 
                                <!-- Right Boundary -->
                                <tr>
                                    <th scope="row"><?php esc_attr_e('Right Boundary', 'daim'); ?></th>
                                    <td>
                                        <select id="right-boundary" name="string_after" class="daext-display-none">
                                            <option value="1" <?php selected(intval(get_option($this->shared->get('slug') . "_default_string_after")), 1); ?>><?php esc_attr_e('Generic', 'daim'); ?></option>
                                            <option value="2" <?php selected(intval(get_option($this->shared->get('slug') . "_default_string_after")), 2); ?>><?php esc_attr_e('White Space', 'daim'); ?></option>
                                            <option value="3" <?php selected(intval(get_option($this->shared->get('slug') . "_default_string_after")), 3); ?>><?php esc_attr_e('Comma', 'daim'); ?></option>
                                            <option value="4" <?php selected(intval(get_option($this->shared->get('slug') . "_default_string_after")), 4); ?>><?php esc_attr_e('Point', 'daim'); ?></option>
                                            <option value="5" <?php selected(intval(get_option($this->shared->get('slug') . "_default_string_after")), 5); ?>><?php esc_attr_e('None', 'daim'); ?></option>
                                        </select>
                                        <div class="help-icon" title='<?php esc_attr_e('The "Right Boundary" option can be used to target keywords followed by a generic boundary or by a specific character.', 'daim'); ?>'></div>
                                    </td>
                                </tr>

                                 <!-- Max Number Autolinks  -->
                                 <tr valign="top">
                                     <th scope="row"><label for="max-number-autolinks"><?php esc_attr_e('Limit', 'daim'); ?></label></th>
                                     <td>
                                         <input value="<?php echo intval(get_option($this->shared->get('slug') . '_default_max_number_autolinks_per_keyword'), 10); ?>" type="text" id="max-number-autolinks" maxlength="7" size="30" name="max_number_autolinks" placeholder="<?php esc_attr_e('The Max Number of Autolinks', 'daim'); ?>" />
                                         <div class="help-icon" title="<?php esc_attr_e('With this option you can determine the maximum number of matches of the defined keyword automatically converted to a link.', 'daim'); ?>"></div>
                                     </td>
                                 </tr>

                                 <!-- Priority -->
                                 <tr valign="top">
                                     <th scope="row"><label for="priority"><?php esc_attr_e('Priority', 'daim'); ?></label></th>
                                     <td>
                                         <input value="<?php echo intval(get_option($this->shared->get('slug') . '_default_priority'), 10); ?>" type="text" id="priority" maxlength="7" size="30" name="priority" placeholder="<?php esc_attr_e('The Priority of this Keyword', 'daim'); ?>" />
                                        <div class="help-icon" title='<?php esc_attr_e('The priority value determines the order used to apply the AIL on the post.', 'daim'); ?>'></div>
                                     </td>
                                 </tr>

                            </table>
                        
                            <!-- submit button -->
                            <div class="daext-form-action">
                                <input class="button" type="submit" value="<?php esc_attr_e('Add Autolink', 'daim'); ?>" >
                            </div>

                        <?php endif; ?>

                    </div>

            </form>

        </div>

    </div>