<?php

        if ( !current_user_can(get_option( $this->shared->get('slug') . "_hits_menu_required_capability")) )  {
                wp_die( esc_attr__( 'You do not have sufficient permissions to access this page.' ) );
        }

        ?>

        <!-- process data -->

        <?php
        
        //reset hits
        if(isset($_POST['reset_hits'])){
            //delete the hits db table content
            global $wpdb;
            $table_name = $wpdb->prefix  . $this->shared->get('slug') . "_hits";
            $result = $wpdb->query("TRUNCATE TABLE $table_name");
            
            if($result !== false){
                $process_data_message = '<div class="updated settings-error notice is-dismissible below-h2"><p>' . esc_attr__('Data succesfully deleted.', 'daim') . '</p></div>';
            }
        }
        
        ?>
        
        <!-- output -->

        <div class="wrap">

                <div id="daext-header-wrapper" class="daext-clearfix">

                    <h2><?php esc_attr_e('Interlinks Manager - Hits', 'daim'); ?></h2>

                    <form action="admin.php" method="get">
                        <input type="hidden" name="page" value="daim-hits">
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

                <div id="daext-menu-wrapper" class="daext-clearfix">
                
                <?php if(isset($process_data_message)){echo $process_data_message;} ?>
                
                <!-- list of subscribers -->
                <div class="hits-container">

                    <?php

                    //create the query part used to filter the results when a search is performed
                    if (isset($_GET['s']) and strlen(trim($_GET['s'])) > 0) {
                        $search_string = $_GET['s'];
                        global $wpdb;
                        $filter = $wpdb->prepare('WHERE (post_title LIKE %s OR target_url LIKE %s)', '%' . $search_string . '%', '%' . $search_string . '%');
                    } else {
                        $filter = '';
                    }

                    //retrieve the total number of hits
                    global $wpdb;
                    $table_name = $wpdb->prefix . $this->shared->get('slug') . "_hits";
                    $total_items = $wpdb->get_var( "SELECT COUNT(*) FROM $table_name $filter");

                    //Initialize the pagination class
                    require_once( $this->shared->get('dir') . '/admin/inc/class-daim-pagination.php' );
                    $pag = new daim_pagination();
                    $pag->set_total_items( $total_items );//Set the total number of items
                    $pag->set_record_per_page( intval(get_option($this->shared->get('slug') . '_pagination_hits_menu'), 10) ); //Set records per page
                    $pag->set_target_page( "admin.php?page=" . $this->shared->get('slug') . "-hits" );//Set target page
                    $pag->set_current_page();//set the current page number from $_GET

                    ?>

                    <!-- Query the database -->
                    <?php
                    $query_limit = $pag->query_limit();//die("SELECT * FROM $table_name " . $filter . " ORDER BY $sort_by $order $query_limit ");
                    $results = $wpdb->get_results("SELECT * FROM $table_name $filter ORDER BY date DESC $query_limit", ARRAY_A); ?>

                    <?php if( count($results) > 0 ) : ?>

                        <div class="daext-items-container">

                            <table class="daext-items">
                                <thead>
                                    <tr>
                                        <th>
                                            <div><?php esc_attr_e('Post', 'daim'); ?></div>
                                            <div class="help-icon" title="<?php esc_attr_e('The post, page or custom post type that includes the link that received a click.', 'daim'); ?>"></div>
                                        </th>
                                        <th>
                                            <div><?php esc_attr_e('Date', 'daim'); ?></div>
                                            <div class="help-icon" title="<?php esc_attr_e('The date on which the link has been clicked.', 'daim'); ?>"></div>
                                        </th>
                                        <th>
                                            <div><?php esc_attr_e('Target', 'daim'); ?></div>
                                            <div class="help-icon" title="<?php esc_attr_e('The target of the clicked link.', 'daim'); ?>"></div>
                                        </th>
                                        <th>
                                            <div><?php esc_attr_e('Type', 'daim'); ?></div>
                                            <div class="help-icon" title='<?php esc_attr_e('The type of the clicked link, "MIL" for the manual internal links and "AIL" for the auto internal links.', 'daim'); ?>'></div>
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>

                                    <?php foreach($results as $result) : ?>

                                        <tr>
                                            <td>
                                                <?php
                                                if(get_post_status($result['source_post_id']) === false){
                                                    echo apply_filters('the_title', $result['post_title']);
                                                }else{
                                                    echo '<a href="' . get_permalink($result['source_post_id']) . '">' . apply_filters('the_title', $result['post_title']) . '</a>';
                                                }
                                                ?>
                                            </td>
                                            <td><?php echo mysql2date( get_option('date_format') , $result['date'] ); ?></td>
                                            <td><a href="<?php echo esc_url($result['target_url']); ?>"><?php echo esc_url($result['target_url']); ?></td>
                                            <td><?php echo $result['link_type'] == 0 ? 'AIL' : 'MIL'; ?></td>
                                        </tr>

                                    <?php endforeach; ?>

                                </tbody>
                            </table>

                        </div>

                    <?php else : ?>

                        <?php

                        if(strlen(trim($filter)) > 0){
                            echo '<p>' . esc_attr__('There are no results that match your search.', 'daim') . '</p>';
                        }else{
                            echo '<p>' . esc_attr__('There are no hits at the moment.', 'daim') . '</p>';
                        }

                        ?>

                    <?php endif; ?>

                    <!-- Display the pagination -->
                    <?php if($pag->total_items > 0) : ?>
                        <div class="daext-tablenav daext-clearfix">
                            <div class="daext-tablenav-pages">
                                <span class="daext-displaying-num"><?php echo $pag->total_items; ?> <?php esc_attr_e('items', 'daim'); ?></span>
                                <?php $pag->show(); ?>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                </div><!-- #subscribers-container -->

                <div class="sidebar-container">

                    <div class="daext-widget">
                        
                        <h3 class="daext-widget-title"><?php esc_attr_e('Reset Hits', 'daim'); ?></h3>

                        <div class="daext-widget-content">
                            
                            <p><?php esc_attr_e('This procedure allows you to reset the hits.', 'daim'); ?></p>
                            
                        </div><!-- .daext-widget-content -->
                        
                        <form method="POST" action="admin.php?page=daim-hits">
                        
                            <div class="daext-widget-submit">
                                <input name="reset_hits" class="button" type="submit" value="<?php esc_attr_e('Reset', 'daim'); ?>">
                            </div>
                            
                        </form>
                        
                    </div>
                    
                    <div class="daext-widget">
                    
                        <h3 class="daext-widget-title"><?php esc_attr_e('Export CSV', 'daim'); ?></h3>

                            <div class="daext-widget-content">

                                <p><?php esc_attr_e('The downloaded CSV file can be imported in your favorite spreadsheet software.', 'daim'); ?></p>

                            </div><!-- .daext-widget-content -->

                            <!-- the data sent through this form are handled by
                            the export_csv_controller() method called with the
                            WordPress init action -->
                            <form method="POST" action="admin.php?page=daim-hits">
                            
                                <div class="daext-widget-submit">
                                    <input name="export_csv" class="button" type="submit" value="<?php esc_attr_e('Download', 'daim'); ?>" <?php if($this->shared->number_of_records_in_hits() == 0){echo 'disabled="disabled"';} ?>>
                                </div>
                            
                            </form>
                    
                    </div>
                    
                </div>  
                
            </div>

        </div>