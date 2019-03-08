<?php

        if ( !current_user_can( get_option( $this->shared->get('slug') . '_dashboard_menu_required_capability') ) )  {
                wp_die( esc_attr__( 'You do not have sufficient permissions to access this page.' ) );
        }

        ?>

        <!-- process data -->

        <!-- output -->

        <div class="wrap">

            <h2>Interlinks Manager - Dashboard</h2>
            
            <div id="daext-menu-wrapper" class="daext-clearfix">
                
                <!-- list of subscribers -->
                <div class="interlinks-container">

                    <?php    

                    //optimization
                    if( isset($_GET['op']) and
                    ( trim($_GET['op']) != 'all' and ( intval($_GET['op'], 10) == 0 or intval($_GET['op'], 10) == 1 ) )
                    and ( strlen(trim($_GET['op'])) > 0 ) ){
                        $filter = "WHERE optimization = '" . intval($_GET['op'], 10) . "'";
                    }else{
                        $filter = '';
                    }

                    //search
                    if (isset($_GET['s']) and strlen(trim($_GET['s'])) > 0) {
                        $search_string = $_GET['s'];
                        global $wpdb;
                        if(strlen(trim($filter)) > 0){
                            $filter .= $wpdb->prepare(' AND (post_title LIKE %s)', '%' . $search_string . '%');
                        }else{
                            $filter .= $wpdb->prepare('WHERE (post_title LIKE %s)', '%' . $search_string . '%');
                        }
                    } else {
                        $filter .= '';
                    }

                    //sort -------------------------------------------------

                    //sort by
                    if(isset($_GET['sb'])){

                        /*
                         * verify if the value is valid, if the value is invalid
                         *  default to the "post_date"
                         */
                        switch ($_GET['sb']) {

                            case 'pd':
                                $sort_by = 'post_date';
                                break;
                            
                            case 'ti':
                                $sort_by = 'post_title';
                                break;

                            case 'mi':
                                $sort_by = 'manual_interlinks';
                                break;

                            case 'ai':
                                $sort_by = 'auto_interlinks';
                                break;

                            case 'pt':
                                $sort_by = 'post_type';
                                break;

                            case 'cl':
                                $sort_by = 'content_length';
                                break;

                            case 'op':
                                $sort_by = 'optimization';
                                break;

                            default:
                                $sort_by = 'post_date';
                                break;
                        }

                    }else{
                        $sort_by = 'post_date';
                    }

                    //order
                    if(isset($_GET['or']) and intval($_GET['or'], 10) == 0 ){
                        $order = "ASC";
                    }else{
                        $order = "DESC";
                    }

                    //retrieve the total number of events
                    global $wpdb;
                    $table_name=$wpdb->prefix . $this->shared->get('slug') . "_archive";
                    $total_items = $wpdb->get_var( "SELECT COUNT(*) FROM $table_name " . $filter);

                    //Initialize the pagination class
                    require_once( $this->shared->get('dir') . '/admin/inc/class-daim-pagination.php' );
                    $pag = new daim_pagination();
                    $pag->set_total_items( $total_items );//Set the total number of items
                    $pag->set_record_per_page( intval(get_option($this->shared->get('slug') . '_pagination_dashboard_menu'), 10) ); //Set records per page
                    $pag->set_target_page( "admin.php?page=" . $this->shared->get('slug') . "-dashboard" );//Set target page
                    $pag->set_current_page();//set the current page number from $_GET

                    ?>

                    <!-- Query the database -->
                    <?php
                    $query_limit = $pag->query_limit();
                    $results = $wpdb->get_results("SELECT * FROM $table_name " . $filter . " ORDER BY $sort_by $order $query_limit ", ARRAY_A); ?>

                    <?php if( count($results) > 0 ) : ?>

                        <div class="daext-items-container">

                            <table class="daext-items">
                                <thead>
                                    <tr>
                                        <th>
                                            <div><?php esc_attr_e('Post', 'daim'); ?></div>
                                            <div class="help-icon" title="<?php esc_attr_e('The post, page or custom post type title.', 'daim'); ?>"></div>
                                        </th>
                                        <th>
                                            <div><?php esc_attr_e('Date', 'daim'); ?></div>
                                            <div class="help-icon" title="<?php esc_attr_e('The post, page or custom post type publishing date.', 'daim'); ?>"></div>
                                        </th>
                                        <th>
                                            <div><?php esc_attr_e('PT', 'daim'); ?></div>
                                            <div class="help-icon" title="<?php esc_attr_e('The post type.', 'daim'); ?>"></div>
                                        </th>
                                        <th>
                                            <div><?php esc_attr_e('CL', 'daim'); ?></div>
                                            <div class="help-icon" title="<?php esc_attr_e('The length of the raw (with filters not applied) post content.', 'daim'); ?>"></div>
                                        </th>
                                        <th>
                                            <div><?php esc_attr_e('MIL', 'daim'); ?></div>
                                            <div class="help-icon" title="<?php esc_attr_e('The manual internal links of the post.', 'daim'); ?>"></div>
                                        </th>
                                        <th>
                                            <div><?php esc_attr_e('AIL', 'daim'); ?></div>
                                            <div class="help-icon" title="<?php esc_attr_e('The auto internal links of the post.', 'daim'); ?>"></div>
                                        </th>
                                        <th>
                                            <div><?php esc_attr_e('RI', 'daim'); ?></div>
                                            <div class="help-icon" title='<?php esc_attr_e('The recommended number of interlinks. This value is based on the post length and on the "Characters per Interlink" option that you defined on the plugin options.', 'daim'); ?>'></div>
                                        </th>
                                        <th>
                                            <div><?php esc_attr_e('VG', 'daim'); ?></div>
                                            <div class="help-icon" title='<?php esc_attr_e('The number of visits generated with the internal links of this post.', 'daim'); ?>'></div>
                                        <th>
                                            <div><?php esc_attr_e('OF', 'daim'); ?></div>
                                            <div class="help-icon" title='<?php esc_attr_e('The "Optimization Flag" is based on the post length, on the "Characters per Interlink" option and on the "Optimization Delta" option that you defined on the plugin options.', 'daim'); ?>'></div>
                                        </th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>

                                    <?php foreach($results as $result) : ?>
                      
                                        <tr>
                                            <td>
                                                <?php
                                                if(get_post_status($result['post_id']) === false){
                                                    echo apply_filters('the_title', $result['post_title']);
                                                }else{
                                                    echo '<a href="' . get_permalink($result['post_id']) . '">' . apply_filters('the_title', $result['post_title']) . '</a>';
                                                }
                                                ?>
                                            </td>
                                            <td><?php echo mysql2date( get_option('date_format') , $result['post_date'] ); ?></td>
                                            <td><?php echo esc_attr(stripslashes($result['post_type'])); ?></td>
                                            <td><?php echo $result['content_length']; ?></td>
                                            <td><?php echo $result['manual_interlinks']; ?></td>
                                            <td><?php echo $result['auto_interlinks']; ?></td>
                                            <td><?php echo $result['recommended_interlinks']; ?></td>
                                            <td><?php echo $this->shared->get_number_of_hits($result['post_id']); ?></td>
                                            <td><?php echo $result['optimization']; ?></td>
                                            <td class="icons-container">
                                                <?php if(get_post_status($result['post_id']) !== false) : ?>
                                                    <a class="menu-icon edit" href="post.php?post=<?php echo $result['post_id']; ?>&action=edit"></a>
                                                <?php endif; ?>
                                            </td>
                                        </tr>

                                    <?php endforeach; ?>

                                </tbody>
                            </table>

                        </div>

                    <?php else : ?>        

                        <?php

                        if(strlen(trim($filter)) > 0){
                            echo '<p>' . esc_attr__('There are no results that match your filter criteria.', 'daim') . '</p>';
                        }else{
                            echo '<p>' . esc_attr__('There are no data at moment, click on the "Generate Data" button to generate data and statistics about the internal links of your blog.', 'daim') . '</p>';
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
                        
                        <h3 class="daext-widget-title">Interlinks Data</h3>

                        <div class="daext-widget-content">
                            
                            <p><?php esc_attr_e('This procedure allows you to generate data and statistics about the internal links of your blog.', 'daim'); ?></p>
                            
                        </div><!-- .daext-widget-content -->
                        
                        <div class="daext-widget-submit">
                            <input id="ajax-request-status" type="hidden" value="inactive">
                            <input class="button" id="update-archive" type="button" value="<?php esc_attr_e('Generate Data', 'daim'); ?>">
                            <img id="ajax-loader" src="<?php echo $this->shared->get('url') . 'admin/assets/img/ajax-loader.gif'; ?>">
                        </div>
                        
                    </div>
                    
                    <div class="daext-widget">
                    
                        <h3 class="daext-widget-title"><?php esc_attr_e('Export CSV', 'daim'); ?></h3>

                        <div class="daext-widget-content">

                           <p><?php esc_attr_e('The downloaded CSV file can be imported in your favorite spreadsheet software.', 'daim'); ?></p>

                        </div><!-- .daext-widget-content -->

                        <!-- the data sent through this form are handled by
                        the export_csv_controller() method called with the
                        WordPress init action -->
                        <form method="POST" action="admin.php?page=daim-dashboard">

                            <div class="daext-widget-submit">
                                <input name="export_csv" class="button" type="submit" value="<?php esc_attr_e('Download', 'daim'); ?>" <?php if($this->shared->number_of_records_in_archive() == 0){echo 'disabled="disabled"';} ?>>
                            </div>

                        </form>
                    
                    </div>
                    
                    <div class="daext-widget" id="filter-and-sort">
                    
                        <h3 class="daext-widget-title"><?php esc_attr_e('Filter & Sort', 'daim'); ?></h3>

                        <form method="GET" action="admin.php">

                            <input type="hidden" name="page" value="<?php echo $this->shared->get('slug'); ?>-dashboard">
                            
                            <div class="daext-widget-content">

                                <h3><?php esc_attr_e('Search', 'daim'); ?></h3>
                                <p>
                                    <?php
                                    if (isset($_GET['s']) and strlen(trim($_GET['s'])) > 0) {
                                        $search_string = $_GET['s'];
                                    } else {
                                        $search_string = '';
                                    }
                                    ?>
                                    <input id="filter-and-sort-search" type="text" name="s" value="<?php echo esc_attr(stripslashes($search_string)); ?>" autocomplete="off" maxlength="255">
                                </p>

                                <h3><?php esc_attr_e('Optimization', 'daim'); ?></h3>
                                <p>
                                    <select name="op" id="op">
                                        <option value="all" <?php if(isset($_GET['op'])){selected( $_GET['op'], 'all' );} ?>><?php esc_attr_e('All', 'daim'); ?></option>
                                        <option value="0" <?php if(isset($_GET['op'])){selected( $_GET['op'], '0' );} ?>><?php esc_attr_e('Not Optimized', 'daim'); ?></option>
                                        <option value="1" <?php if(isset($_GET['op'])){selected( $_GET['op'], '1' );} ?>><?php esc_attr_e('Optimized', 'daim'); ?></option>
                                    </select>
                                </p>


                                <h3><?php esc_attr_e('Sort By', 'daim'); ?></h3>
                                <p>
                                    <select name="sb" id="sb">
                                        <option value="pd"><?php esc_attr_e('Date', 'daim'); ?></option>
                                        <option value="ti" <?php if( isset($_GET['sb']) ){selected( $_GET['sb'], 'ti' );} ?>><?php esc_attr_e('Title', 'daim'); ?></option>
                                        <option value="pt" <?php if( isset($_GET['sb']) ){selected( $_GET['sb'], 'pt' );} ?>><?php esc_attr_e('Post Type', 'daim'); ?></option>
                                        <option value="cl" <?php if( isset($_GET['sb']) ){selected( $_GET['sb'], 'cl' );} ?>><?php esc_attr_e('Content Length', 'daim'); ?></option>
                                        <option value="mi" <?php if( isset($_GET['sb']) ){selected( $_GET['sb'], 'mi' );} ?>><?php esc_attr_e('Manual Interlinks', 'daim'); ?></option>
                                        <option value="ai" <?php if( isset($_GET['sb']) ){selected( $_GET['sb'], 'ai' );} ?>><?php esc_attr_e('Auto Interlinks', 'daim'); ?></option>
                                        <option value="op" <?php if( isset($_GET['sb']) ){selected( $_GET['sb'], 'op' );} ?>><?php esc_attr_e('Optimization', 'daim'); ?></option>
                                    </select>
                                </p>


                                <h3><?php esc_attr_e('Order', 'daim'); ?></h3>
                                <p>
                                    <select name="or" id="or">
                                        <option value="1" <?php if( isset($_GET['sb']) ){selected( intval($_GET['or'], 10), 1 );} ?>><?php esc_attr_e('Descending', 'daim'); ?></option>
                                        <option value="0" <?php if( isset($_GET['sb']) ){selected( intval($_GET['or'], 10), 0 );} ?>><?php esc_attr_e('Ascending', 'daim'); ?></option>
                                    </select>
                                </p>

                            </div><!-- .daext-widget-content -->

                            <div class="daext-widget-submit">
                                <input class="button" type="submit" value="<?php esc_attr_e('Apply Query', 'daim'); ?>">
                            </div>
                            
                        </form>
                    
                    </div>
                    
                </div>  
                 
            </div>

        </div>