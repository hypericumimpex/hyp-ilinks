<?php

        if ( !current_user_can(get_option( $this->shared->get('slug') . "_juice_menu_required_capability")) )  {
                wp_die( esc_attr__( 'You do not have sufficient permissions to access this page.' ) );
        }

        ?>

        <!-- process data -->

        <!-- output -->

        <div class="wrap">

            <div id="daext-header-wrapper" class="daext-clearfix">

                <h2><?php esc_attr_e('Interlinks Manager - Juice', 'daim'); ?></h2>

                <form action="admin.php" method="get">
                    <input type="hidden" name="page" value="daim-juice">
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
                
                <!-- list of subscribers -->
                <div class="juice-container">

                    <?php

                    //create the query part used to filter the results when a search is performed
                    if (isset($_GET['s']) and strlen(trim($_GET['s'])) > 0) {
                        $search_string = $_GET['s'];
                        global $wpdb;
                        $filter = $wpdb->prepare('WHERE (url LIKE %s)', '%' . $search_string . '%');
                    } else {
                        $filter = '';
                    }

                    //default pagination -----------------------------------

                    //retrieve the total number of events
                    global $wpdb;
                    $table_name=$wpdb->prefix . $this->shared->get('slug') . "_juice";
                    $total_items = $wpdb->get_var( "SELECT COUNT(*) FROM $table_name $filter");

                    //Initialize the pagination class
                    require_once( $this->shared->get('dir') . '/admin/inc/class-daim-pagination.php' );
                    $pag = new daim_pagination();
                    $pag->set_total_items( $total_items );//Set the total number of items
                    $pag->set_record_per_page( intval(get_option($this->shared->get('slug') . '_pagination_juice_menu'), 10) ); //Set records per page
                    $pag->set_target_page( "admin.php?page=" . $this->shared->get('slug') . "-juice" );//Set target page
                    $pag->set_current_page();//set the current page number from $_GET

                    ?>

                    <!-- Query the database -->
                    <?php
                    $query_limit = $pag->query_limit();//die("SELECT * FROM $table_name " . $filter . " ORDER BY $sort_by $order $query_limit ");
                    $results = $wpdb->get_results("SELECT * FROM $table_name $filter ORDER BY juice DESC $query_limit ", ARRAY_A); ?>

                    <?php if( count($results) > 0 ) : ?>

                        <div class="daext-items-container">

                            <table class="daext-items">
                                <thead>
                                    <tr>
                                        <th>
                                            <div><?php esc_attr_e('URL', 'daim'); ?></div>
                                            <div class="help-icon" title="<?php esc_attr_e('The URL that receives the link juice.', 'daim'); ?>"></div>
                                        </th>
                                        <th>
                                            <div><?php esc_attr_e('IIL', 'daim'); ?></div>
                                            <div class="help-icon" title="<?php esc_attr_e('The number of internal inbound links received by the URL.', 'daim'); ?>"></div>
                                        </th>
                                        <th>
                                            <div><?php esc_attr_e('Juice (Value)', 'daim'); ?></div>
                                            <div class="help-icon" title="<?php esc_attr_e('The link juice received by the URL.', 'daim'); ?>"></div>
                                        </th>
                                        <th>
                                            <div><?php esc_attr_e('Juice (Visual)', 'daim'); ?></div>
                                            <div class="help-icon" title="<?php esc_attr_e('The visual representation of the link juice received by the URL.', 'daim'); ?>"></div>
                                        </th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>

                                    <?php foreach($results as $result) : ?>

                                        <tr>
                                            <td><a href="<?php echo esc_url($result['url']); ?>"><?php echo esc_url($result['url']); ?></a></td>
                                            <td><?php echo $result['iil']; ?></td>
                                            <td><?php echo $result['juice']; ?></td>
                                            <td>
                                                <div id="juice-relative-container">
                                                    <div id="juice-relative" style="width: <?php echo $result['juice_relative']; ?>px"></div>
                                                </div>
                                            </td>
                                            <td class="icons-container">
                                                <form method="POST" action="admin.php?page=daim-juice">
                                                    <input type="hidden" name="anchors_url" value="<?php echo esc_url($result['url']); ?>">
                                                    <input class="menu-icon download help-icon" name="export_anchors_csv" class="button" type="submit" value="" title="<?php esc_attr_e('Download a CSV file that includes a list of the links that contribute to generate the overall link juice of this URL.', 'daim'); ?>">
                                                </form>
                                            </td>
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
                            echo '<p>' . esc_attr__('There are no data at moment, click on the "Generate Data" button to generate data about the flow of "Link Juice" on the URLs of your website.', 'daim') . '</p>';
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
                        
                        <h3 class="daext-widget-title"><?php esc_attr_e('Juice Data', 'daim'); ?></h3>

                        <div class="daext-widget-content">
                            
                            <p><?php esc_attr_e('This procedure allows you to generate data about the flow of "Link Juice" on the URLs of your website.', 'daim'); ?></p>

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
                            <form method="POST" action="admin.php?page=daim-juice">
                            
                                <div class="daext-widget-submit">
                                    <input name="export_csv" class="button" type="submit" value="<?php esc_attr_e('Download', 'daim'); ?>" <?php if($this->shared->number_of_records_in_juice() == 0){echo 'disabled="disabled"';} ?>>
                                </div>
                            
                            </form>
                    
                    </div>
                    

                </div>  
                
            </div>

        </div>