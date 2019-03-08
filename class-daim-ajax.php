<?php

/*
 * this class should be used to include ajax actions
 */
class Daim_Ajax{
    
    protected static $instance = null;
    private $shared = null;
    
    private function __construct() {
        
        //assign an instance of the plugin info
        $this->shared = Daim_Shared::get_instance();
        
        //ajax requests --------------------------------------------------------
        
        //for logged-in and not-logged-in users --------------------------------
        add_action( 'wp_ajax_track_internal_link', array( $this, 'track_internal_link' ) );
        add_action( 'wp_ajax_nopriv_track_internal_link', array( $this, 'track_internal_link' ) );
        
        //for logged-in users --------------------------------------------------
        add_action( 'wp_ajax_update_interlinks_archive', array( $this, 'update_interlinks_archive' ) );
        add_action( 'wp_ajax_update_juice_archive', array( $this, 'update_juice_archive' ) );
        add_action( 'wp_ajax_generate_interlinks_suggestions', array( $this, 'generate_interlinks_suggestions' ) );
	    add_action( 'wp_ajax_generate_interlinks_optimization', array( $this, 'generate_interlinks_optimization' ) );
        
    }
    
    /*
     * return an istance of this class
     */
    public static function get_instance() {

        if ( null == self::$instance ) {
            self::$instance = new self;
        }

        return self::$instance;
            
    }
    
    /*
     * Ajax handler used to generate the interlinks archive in the "Dashboard"
     * menu
     */
    public function update_interlinks_archive(){

        //check the referer
        if(!check_ajax_referer( 'daim', 'security', false )){echo "Invalid AJAX Request"; die();}

        //check the capability
        if(!current_user_can(get_option( $this->shared->get('slug') . "_dashboard_menu_required_capability"))){echo "Invalid Capability"; die();}
        
        /*
         * Set the custom "Max Execution Time Value" defined in the options if
         * the 'Set Max Execution Time' option is set to "Yes"
         */
        if( intval( get_option( $this->shared->get('slug') . '_set_max_execution_time') , 10) == 1 ){
            ini_set('max_execution_time', intval(get_option("daim_max_execution_time_value"), 10));
        }
        
        /*
         * Set the custom "Memory Limit Value" ( in megabytes ) defined in the
         * options if the 'Set Memory Limit' option is set to "Yes"
         */
        if( intval( get_option( $this->shared->get('slug') . '_set_memory_limit') , 10) == 1 ){
            ini_set('memory_limit', intval(get_option("daim_memory_limit_value"), 10) . 'M');
        }
        
        /*
         * Create a query used to consider in the analysis only the post types
         * selected with the 'dashboard_post_types' option
         */
        $dashboard_post_types = preg_replace('/\s+/', '', get_option($this->shared->get('slug') . '_dashboard_post_types' ));
        $dashboard_post_types_a = explode(',', $dashboard_post_types);
        $post_types_query = '';
        foreach($dashboard_post_types_a as $key => $value){

            if (!preg_match("/[a-z0-9_-]+/", $value)) {continue;}

            $post_types_query .= "post_type = '" . $value . "'";
            if($key != ( count($dashboard_post_types_a) - 1 )){$post_types_query .= ' OR ';} 

        }
        
        /*
         * get all the manual internal links and save them in the archive db
         * table
         */
        global $wpdb;
        $table_name = $wpdb->prefix . "posts";
        $limit_posts_analysis = intval(get_option($this->shared->get('slug') . '_limit_posts_analysis'), 10);
        $safe_sql = "SELECT ID, post_title, post_type, post_date, post_content FROM $table_name WHERE ($post_types_query) AND post_status = 'publish' ORDER BY post_date DESC LIMIT " . $limit_posts_analysis;
        $posts_a = $wpdb->get_results($safe_sql, ARRAY_A);
        
        //delete the internal links archive database table content
        $table_name = $wpdb->prefix  . $this->shared->get('slug') . "_archive";
        $result = $wpdb->query("TRUNCATE TABLE $table_name");
        
        //init $archive_a
        $archive_a = array();
        
        foreach ($posts_a as $key => $single_post) {
            
            //set the post id
            $post_archive_post_id = $single_post['ID'];
            
            //get the post title
            $post_archive_post_title = $single_post['post_title'];
            
            //set the post type
            $post_archive_post_type = $single_post['post_type'];
            
            //set the post date
            $post_archive_post_date = $single_post['post_date'];
            
            //set the post content
            $post_content = $single_post['post_content'];
            
            //set the number of manual internal links
            $post_archive_manual_interlinks = $this->shared->get_manual_interlinks($post_content);
            
            //create a variable with the post content with autolinks included
            $post_content_with_autolinks = $this->shared->add_autolinks($post_content, false, $post_archive_post_type, $post_archive_post_id);
            
            //set the number of auto internal links
            $post_archive_auto_interlinks = $this->shared->get_autolinks_number($post_content_with_autolinks);
            
            //set the post content length
            $post_archive_content_length = mb_strlen(trim($post_content));
            
            //set the recommended interlinks
            $post_archive_recommended_interlinks = $this->shared->calculate_recommended_interlinks($post_archive_manual_interlinks + $post_archive_auto_interlinks, $post_archive_content_length);
            
            //set the optimization flag
            $optimization = $this->shared->calculate_optimization($post_archive_manual_interlinks + $post_archive_auto_interlinks, $post_archive_content_length);
            
            /*
             * save data in the $archive_a array ( data will be later saved into
             * the archive db table )
             */
            $archive_a[] = array(
                'post_id' => $post_archive_post_id,
                'post_title' => $post_archive_post_title,
                'post_type' => $post_archive_post_type,
                'post_date' => $post_archive_post_date,
                'manual_interlinks' => $post_archive_manual_interlinks,
                'auto_interlinks' => $post_archive_auto_interlinks,
                'content_length' => $post_archive_content_length,
                'recommended_interlinks' => $post_archive_recommended_interlinks,
                'optimization' => $optimization
            );
            
        }
        
        /*
         * Save data into the archive db table with multiple queries of 100
         * items each one.
         * It's a compromise for the following two reasons:
         * 1 - For performance, too many queries slow down the process
         * 2 - To avoid problem with queries too long the number of inserted
         * rows per query are limited to 100
         */
        $table_name = $wpdb->prefix  . $this->shared->get('slug') . "_archive";
        $archive_a_length = count($archive_a);
        $query_groups = array();
        $query_index = 0;
        foreach($archive_a as $key => $single_archive){
            
            $query_index = intval($key/100, 10);
            
            $query_groups[$query_index][] = $wpdb->prepare("( %d, %s, %s, %s, %d, %d, %d, %d, %d )",
                $single_archive['post_id'],
                $single_archive['post_title'],
                $single_archive['post_type'],
                $single_archive['post_date'],
                $single_archive['manual_interlinks'],
                $single_archive['auto_interlinks'],
                $single_archive['content_length'],
                $single_archive['recommended_interlinks'],
                $single_archive['optimization']
            );
            
        }
        
        /*
         * Each item in the $query_groups array includes a maximum of 100
         * assigned records. Here each group creates a query and the query is
         * executed
         */
        $query_start = "INSERT INTO $table_name (post_id, post_title, post_type, post_date, manual_interlinks, auto_interlinks, content_length, recommended_interlinks, optimization) VALUES ";
        $query_end = '';
        
        foreach($query_groups as $key => $query_values){
            
            $query_body = '';
            
            foreach($query_values as $single_query_value){
                
                $query_body .= $single_query_value . ',';
                
            }
            
            $safe_sql = $query_start . substr($query_body, 0, strlen($query_body) - 1) . $query_end;

            //save data into the archive db table
            $wpdb->query( $safe_sql );

        }
        
        //send output
        echo 'success';
        die();
        
    }
    
    /*
     * Ajax handler used to track internal links in the front-end
     */
    public function track_internal_link(){

        //check the referer
        if(!check_ajax_referer( 'daim', 'security', false )){echo "Invalid AJAX Request"; die();}

        //get data
        if($_POST['link_type'] == 'ail'){$link_type = 0;}else{$link_type = 1;}
        $source_post_id = intval($_POST['source_post_id'], 10);
        $target_url = mb_substr($_POST['target_url'],0, 2038);
        $date = current_time('mysql');
        $date_gmt = current_time('mysql', 1);
        
        /*
         * Remove all the filter associated with 'the_title' to get with the
         * function get_the_title() the raw title saved in the posts table
         */
        remove_all_filters('the_title');
        $post_title = get_the_title($source_post_id);
        
        //verify if the post with the link exists
        if ( get_post_status( $source_post_id ) === false ) { echo 'The post doesn\'t exists.'; die(); }
        
        //save into the database
        global $wpdb;
        $table_name = $wpdb->prefix . $this->shared->get('slug') . "_hits";
        $safe_sql = $wpdb->prepare("INSERT INTO $table_name SET 
            source_post_id = %d,
            post_title = %s,
            target_url = %s,
            link_type = %s,
            date = %s,
            date_gmt = %s",
            $source_post_id,
            $post_title,
            $target_url,
            $link_type,
            $date,
            $date_gmt
        );

        $query_result = $wpdb->query( $safe_sql );

        if($query_result === false){
            $result = 'error';
        }else{
            $result = 'success';
        }
        
        //send output
        echo $result;
        die();
        
    }
    
    /*
     * Ajax handler used to generate the juice archive in "Juice" menu
     */
    public function update_juice_archive(){

        //check the referer
        if(!check_ajax_referer( 'daim', 'security', false )){echo "Invalid AJAX Request"; die();}
        
        //check the capability
        if(!current_user_can(get_option( $this->shared->get('slug') . "_juice_menu_required_capability"))){echo "Invalid Capability"; die();}

        /*
         * Set the custom "Max Execution Time Value" defined in the options if
         * the 'Set Max Execution Time' option is set to "Yes"
         */
        if( intval( get_option( $this->shared->get('slug') . '_set_max_execution_time') , 10) == 1 ){
            ini_set('max_execution_time', intval(get_option("daim_max_execution_time_value"), 10));
        }
        
        /*
         * Set the custom "Memory Limit Value" ( in megabytes ) defined in the
         * options if the 'Set Memory Limit' option is set to "Yes"
         */
        if( intval( get_option( $this->shared->get('slug') . '_set_memory_limit') , 10) == 1 ){
            ini_set('memory_limit', intval(get_option("daim_memory_limit_value"), 10) . 'M');
        }
        
        //delete the juice db table content
        global $wpdb;
        $table_name = $wpdb->prefix  . $this->shared->get('slug') . "_juice";
        $wpdb->query("TRUNCATE TABLE $table_name");
        
        //delete the anchors db table content
        $table_name = $wpdb->prefix  . $this->shared->get('slug') . "_anchors";
        $wpdb->query("TRUNCATE TABLE $table_name");
        
        //update the juice archive ---------------------------------------------
        $juice_a = array();
        $juice_id = 0;
        
        /*
         * Create a query used to consider in the analysis only the post types
         * selected with the 'juice_post_types' option
         */
        $juice_post_types = preg_replace('/\s+/', '', get_option($this->shared->get('slug') . '_juice_post_types' ));
        $juice_post_types_a = explode(',', $juice_post_types);
        $post_types_query = '';
        foreach($juice_post_types_a as $key => $value){

            if (!preg_match("/[a-z0-9_-]+/", $value)) {continue;}

            $post_types_query .= "post_type = '" . $value . "'";
            if($key != ( count($juice_post_types_a) - 1 )){$post_types_query .= ' OR ';} 

        }
        
        /*
         * get all the manual and auto internal links and save them in an array
         */
        global $wpdb;
        $table_name = $wpdb->prefix . "posts";
        $limit_posts_analysis = intval(get_option($this->shared->get('slug') . '_limit_posts_analysis'), 10);
        $safe_sql = "SELECT ID, post_title, post_type, post_date, post_content FROM $table_name WHERE ($post_types_query) AND post_status = 'publish' ORDER BY post_date DESC LIMIT " . $limit_posts_analysis;
        $posts_a = $wpdb->get_results($safe_sql, ARRAY_A);
        
        foreach ($posts_a as $key => $single_post) {
            
            //set the post content
            $post_content = $single_post['post_content'];
            
            //remove the HTML comments
            $post_content = $this->shared->remove_html_comments($post_content);
            
            //remove script tags
            $post_content = $this->shared->remove_script_tags($post_content);
            
            //Apply the auto interlinks to the post content
            $post_content_with_autolinks = $this->shared->add_autolinks($post_content, false, $single_post['post_type'], $single_post['ID']);
            
            /*
             * Get the website url and quote and escape the regex character. # and 
             * whitespace ( used with the 'x' modifier ) are not escaped, thus
             * should not be included in the $site_url string
             */
            $site_url = preg_quote(get_home_url());
            
            /*
             * find all the manual and auto interlinks matches with a regular
             * expression and add them in the $juice_a array
             */
            preg_match_all(
                '{<a                                #1 Begin the element a start-tag
                [^>]+                               #2 Any character except > at least one time
                href\s*=\s*                         #3 Equal may have whitespaces on both sides
                ([\'"]?)                            #4 Match double quotes, single quote or no quote ( captured for the backreference \1 )
                (' . $site_url . '[^\'">\s]+ )      #5 The site URL ( Scheme and Domain ) and the rest of the URL ( Path and/or File ) ( captured )
                \1                                  #6 Backreference that matches the href value delimiter matched at line 4     
                [^>]*                               #7 Any character except > zero or more times
                >                                   #8 End of the start-tag
                (.*?)                               #9 Link text or nested tags. After the dot ( enclose in parenthesis ) negative lookbehinds can be applied to avoid specific stuff inside the link text or nested tags. Example with single negative lookbehind (.(?<!word1))*? Example with multiple negative lookbehind (.(?<!word1)(?<!word2)(?<!word3))*?
                <\/a\s*>                            #10 Element a end-tag with optional white-spaces characters before the >
                }ix',
            $post_content_with_autolinks, $matches, PREG_OFFSET_CAPTURE);
            
            //save the URLs, the juice value and other info in the array
            $captures = $matches[2];
            foreach($captures as $key => $single_capture){
                
                //get the link position
                $link_position = $matches[0][$key][1];
                
                //save the captured URL
                $url = $single_capture[0];
                
                /*
                 * remove link to anchor from the URL ( if enabled through the
                 * options )
                 */
                if( intval( get_option( $this->shared->get('slug') . '_remove_link_to_anchor'), 10) == 1 ){
                    $url = $this->shared->remove_link_to_anchor($url);
                }
                
                /*
                 * remove the URL parameters ( if enabled through the options )
                 */
                if( intval( get_option( $this->shared->get('slug') . '_remove_url_parameters'), 10) == 1 ){
                    $url = $this->shared->remove_url_parameters($url);
                }
                
                $juice_a[$juice_id]['url'] = $url;
                $juice_a[$juice_id]['juice'] = $this->shared->calculate_link_juice($post_content_with_autolinks, $single_post['ID'], $link_position);
                $juice_a[$juice_id]['anchor'] = $matches[3][$key][0];
                $juice_a[$juice_id]['post_id'] = $single_post['ID'];
                $juice_a[$juice_id]['post_title'] = $single_post['post_title'];
                
                $juice_id++;
                        
            }
            
        }
        
        /*
         * Save data into the anchors db table with multiple queries of 100
         * items each one.
         * It's a compromise for the following two reasons:
         * 1 - For performance, too many queries slow down the process
         * 2 - To avoid problem with queries too long the number of inserted
         * rows per query are limited to 100
         */
        $table_name = $wpdb->prefix  . $this->shared->get('slug') . "_anchors";
        $juice_a_length = count($juice_a);
        $query_groups = array();
        $query_index = 0;
        foreach($juice_a as $key => $single_juice){

            $query_index = intval($key/100, 10);

            $query_groups[$query_index][] = $wpdb->prepare("( %s, %s, %d, %d, %s )",
                $single_juice['url'],
                $single_juice['anchor'],
                $single_juice['post_id'],
                $single_juice['juice'],
                $single_juice['post_title']
            );

        }
        
        /*
         * Each item in the $query_groups array includes a maximum of 100
         * assigned records. Here each group creates a query and the query is
         * executed
         */
        $query_start = "INSERT INTO $table_name (url, anchor, post_id, juice, post_title) VALUES ";
        $query_end = '';
        
        foreach($query_groups as $key => $query_values){
            
            $query_body = '';
            
            foreach($query_values as $single_query_value){
                
                $query_body .= $single_query_value . ',';
                
            }
            
            $safe_sql = $query_start . substr($query_body, 0, strlen($query_body) - 1) . $query_end;

            //save data into the archive db table
            $wpdb->query( $safe_sql );

        }
        
        //prepare data that should be saved in the juice db table --------------
        $juice_a_no_duplicates = array();
        $juice_a_no_duplicates_id = 0;
        
        /*
         * Reduce multiple array items with the same URL to a single array item
         * with a sum of iil and juice
         */
        foreach($juice_a as $key => $single_juice){

            $duplicate_found = false;

            //verify if an item with this url already exist in the $juice_a_no_duplicates array
            foreach($juice_a_no_duplicates as $key => $single_juice_a_no_duplicates){

                if($single_juice_a_no_duplicates['url'] == $single_juice['url']){
                    $juice_a_no_duplicates[$key]['iil']++;
                    $juice_a_no_duplicates[$key]['juice'] = $juice_a_no_duplicates[$key]['juice'] + $single_juice['juice'];
                    $duplicate_found = true;
                }

            }

            /*
             * if this url doesn't already exist in the array save it in
             * $juice_a_no_duplicates
             */
            if(!$duplicate_found){
                
                $juice_a_no_duplicates[$juice_a_no_duplicates_id]['url'] = $single_juice['url'];
                $juice_a_no_duplicates[$juice_a_no_duplicates_id]['iil'] = 1;
                $juice_a_no_duplicates[$juice_a_no_duplicates_id]['juice'] = $single_juice['juice'];
                $juice_a_no_duplicates_id++;
                
            }

        }

        /*
         * calculate the relative link juice on a scale between 0 and 100,
         * the maximum value found corresponds to the 100 value of the
         * relative link juice
         */
        $max_value = 0;
        foreach($juice_a_no_duplicates as $key => $juice_a_no_duplicates_single){
            if($juice_a_no_duplicates_single['juice'] > $max_value){
                $max_value = $juice_a_no_duplicates_single['juice'];
            }
        }
        
        //set the juice_relative index in the array
        foreach($juice_a_no_duplicates as $key => $juice_a_no_duplicates_single){
            $juice_a_no_duplicates[$key]['juice_relative'] = ( 140 * $juice_a_no_duplicates_single['juice'] ) / $max_value;
        }
        
        /*
         * Save data into the juice db table with multiple queries of 100
         * items each one.
         * It's a compromise for the following two reasons:
         * 1 - For performance, too many queries slow down the process
         * 2 - To avoid problem with queries too long the number of inserted
         * rows per query are limited to 100
         */
        $table_name = $wpdb->prefix  . $this->shared->get('slug') . "_juice";
        $juice_a_no_duplicates_length = count($juice_a_no_duplicates);
        $query_groups = array();
        $query_index = 0;
        foreach($juice_a_no_duplicates as $key => $value){

            $query_index = intval($key/100, 10);

            $query_groups[$query_index][] = $wpdb->prepare("( %s, %d, %d, %d )",
                $value['url'],
                $value['iil'],
                $value['juice'],
                $value['juice_relative']
            );

        }
        
        /*
         * Each item in the $query_groups array includes a maximum of 100
         * assigned records. Here each group creates a query and the query is
         * executed
         */
        $query_start = "INSERT INTO $table_name (url, iil, juice, juice_relative) VALUES ";
        $query_end = '';
        
        foreach($query_groups as $key => $query_values){
            
            $query_body = '';
            
            foreach($query_values as $single_query_value){
                
                $query_body .= $single_query_value . ',';
                
            }
            
            $safe_sql = $query_start . substr($query_body, 0, strlen($query_body) - 1) . $query_end;

            //save data into the archive db table
            $wpdb->query( $safe_sql );

        }

        //send output
        return 'success';
        die();
        
    }
    
    /*
     * Ajax handler used to generate a list of suggestions in the "Interlinks
     * Suggestions" meta box
     */
    public function generate_interlinks_suggestions(){

        //check the referer
        if(!check_ajax_referer( 'daim', 'security', false )){echo "Invalid AJAX Request"; die();}
        
        //check the capability
        if(!current_user_can(get_option( $this->shared->get('slug') . "_interlinks_suggestions_mb_required_capability"))){echo "Invalid Capability"; die();}

        /*
         * Set the custom "Max Execution Time Value" defined in the options if
         * the 'Set Max Execution Time' option is set to "Yes"
         */
        if( intval( get_option( $this->shared->get('slug') . '_set_max_execution_time') , 10) == 1 ){
            ini_set('max_execution_time', intval(get_option("daim_max_execution_time_value"), 10));
        }
        
        /*
         * Set the custom "Memory Limit Value" ( in megabytes ) defined in the
         * options if the 'Set Memory Limit' option is set to "Yes"
         */
        if( intval( get_option( $this->shared->get('slug') . '_set_memory_limit') , 10) == 1 ){
            ini_set('memory_limit', intval(get_option("daim_memory_limit_value"), 10) . 'M');
        }
        
        //get the post id for which the suggestions should be generated
        $post_id = intval($_POST['post_id'], 10);
        
        //get the options values
        $option_title = get_option( $this->shared->get('slug') . '_suggestions_titles');//consider, ignore
        $option_post_type = get_option( $this->shared->get('slug') . '_suggestions_post_type');//require, consider, ignore
        $option_categories = get_option( $this->shared->get('slug') . '_suggestions_categories');//require, consider, ignore
        $option_tags = get_option( $this->shared->get('slug') . '_suggestions_tags');//require, consider, ignore
        
        /*
         * Create a query to get the posts that belong to the selected
         * 'Pool Post Types'
         */
        $pool_post_types = preg_replace('/\s+/', '', get_option($this->shared->get('slug') . '_suggestions_pool_post_types' ));
        $post_types_a = explode(',', $pool_post_types);
        $pool_post_types_query = '';
        foreach($post_types_a as $key => $value){

            if (!preg_match("/[a-z0-9_-]+/", $value)) {continue;}

            $pool_post_types_query .= "post_type = '" . $value . "'";
            if($key != ( count($post_types_a) - 1 )){$pool_post_types_query .= ' or ';} 

        }
        if(strlen($pool_post_types_query) > 0){$pool_post_types_query = ' AND (' . $pool_post_types_query. ')';}
        
        /*
         * step1: $option_title
         * 
         * if $option_title is set to 'consider' compare each word that appears
         * in the current post title with the ones that appears in every other
         * available post and increase the score by 10 for each word
         * 
         * if $option_title is set to 'ignore' create an array with all the
         * posts and 0 as the score
         * 
         * The array that saves the score is the $posts_ranking_a array
         * 
         */
        if($option_title == 'consider'){
            
            //get the current post title
            $current_post_title = get_the_title($post_id);

            /*
             * extract all the words from the current post title and save them
             * in the $shared_words array
             */
            //$temp_post_title = $current_post_title;
            $shared_words = array();
                
            /*
             * Save in $shared_words all the single words available in the title
             * of the current post
             */
            $shared_words = explode(' ', $current_post_title);
            
            //remove empty elements from the array
            $shared_words = array_filter($shared_words);     
            
            /*
             * Execute the query to get the posts that belong to the selected
             * 'Pool Post Types'
             */
            global $wpdb;
            $table_name = $wpdb->prefix . "posts";
            $limit_posts_analysis = intval(get_option($this->shared->get('slug') . '_limit_posts_analysis'), 10);
            $results = $wpdb->get_results("SELECT ID, post_type, post_title FROM $table_name WHERE post_status = 'publish' $pool_post_types_query ORDER BY post_date DESC LIMIT $limit_posts_analysis", ARRAY_A);

            /*
            * Compare each word that appears in the current post title with the 
            * ones that appears in every other available post and increase the
            * score by 10 for each word
             */
            foreach($results as $key => $single_result){

                $score = 0;

                //assign 10 points for the word matches
                foreach($shared_words as $key => $needle){
                    if( strpos($single_result['post_title'], $needle) !== false ){$score = $score + 10;};
                }

                //save post data in the $posts_ranking_a array
                $posts_ranking_a[] = array(
                    'id' => $single_result['ID'],
                    'post_type' => $single_result['post_type'],
                    'score' => $score
                );

            }
            
        }else{
            
            //create an array with all the posts and 0 as score ----------------
            global $wpdb;
            $table_name = $wpdb->prefix . "posts";
            $limit_posts_analysis = intval(get_option($this->shared->get('slug') . '_limit_posts_analysis'), 10);
            $results = $wpdb->get_results("SELECT ID, post_type FROM $table_name WHERE post_status = 'publish' $pool_post_types_query ORDER BY post_date DESC LIMIT $limit_posts_analysis", ARRAY_A);

            //cycle through all the posts
            foreach($results as $key => $single_result){
                
                //save post data in the $posts_ranking_a array
                $posts_ranking_a[] = array(
                    'id' => $single_result['ID'],
                    'post_type' => $single_result['post_type'],
                    'score' => 0
                );
                
            }
        }
        
        /*
         * step2: $option_post_type
         * 
         * If $option_post_type is set to 'require' remove from the array
         * $posts_ranking_a all the posts that don't belong to this post type
         * 
         * If $option_post_type is set to 'consider' add 20 to all the posts
         * that belong to this post type on the $posts_ranking_a array
         * 
         * If $option_post_type is set to 'ignore' do nothing
         * 
         */
        
        //proceed with this step only if the $posts_ranking_a exists and it's not empty
        if( isset($posts_ranking_a) and ( count($posts_ranking_a) > 0 ) ){
        
            //get the post type of this post
            $current_post_type = get_post_type($post_id);

            switch ($option_post_type){

                case 'require':

                    foreach($posts_ranking_a as $pra_key => $pra_value){
                        if( $pra_value['post_type'] != $current_post_type ){
                            unset($posts_ranking_a[$pra_key]);
                        }
                    }

                    break;

                case 'consider':

                    foreach($posts_ranking_a as $pra_key => $pra_value){
                        if( $pra_value['post_type'] == $current_post_type ){
                            $posts_ranking_a[$pra_key]['score'] = $posts_ranking_a[$pra_key]['score'] + 20; 
                        }
                    }

                    break;

                case 'ignore':

                    break;

            }
            
        }
        
        /*
         * step3: $option_categories
         * 
         * If $option_categories is set to 'require' remove from the
         * $posts_ranking_a array all the posts that don't have any category 
         * that the current post have
         * 
         * If the $option_categories is set to 'consider' add 20 to all the
         * posts that have the category that the current post have ( add 20 for
         * each category found )
         * 
         * if $option_categories is set to 'ignore' do nothing
         * 
         * Please note that this option is applied only to the posts that have
         * the "category" taxonomy and that are associated with one or more
         * categories
         */
        
        //proceed with this step only if the $posts_ranking_a exists and it's not empty
        if( isset($posts_ranking_a) and ( count($posts_ranking_a) > 0 ) ){
        
            if( in_array( 'category', get_object_taxonomies( get_post_type($post_id) ) ) ){

                //get an array with a list of the id of the categories
                $current_post_categories = wp_get_post_categories($post_id);

                if( is_array($current_post_categories) and count($current_post_categories) > 0 ){

                    switch ($option_categories){

                        case 'require':

                            foreach($posts_ranking_a as $pra_key => $pra_value){
                                $found = false;
                                $iterated_post_categories = wp_get_post_categories($pra_value['id']);
                                foreach($current_post_categories as $cpc_key => $cpc_value){
                                    if(in_array($cpc_value, $iterated_post_categories)){
                                       $found = true; 
                                    }
                                }
                                if(!$found){
                                    unset($posts_ranking_a[$pra_key]);
                                }
                            }

                            break;

                        case 'consider':

                            foreach($posts_ranking_a as $pra_key => $pra_value){
                                $found = false;
                                $iterated_post_categories = wp_get_post_categories($pra_value['id']);
                                foreach($current_post_categories as $cpc_key => $cpc_value){
                                    if(in_array($cpc_value, $iterated_post_categories)){
                                       $found = true; 
                                    }
                                }
                                if($found){
                                    $posts_ranking_a[$pra_key]['score'] = $posts_ranking_a[$pra_key]['score'] + 20;
                                }
                            }

                            break;

                        case 'ignore':

                            break;

                    }

                }

            }
            
        }
        
        /*
         * step4: $option_tags
         * 
         * If $option_tags is set to 'require' remove from the $posts_ranking_a
         * array all posts that don't have any tag that the current post have
         * 
         * If the $option_tags is set to 'consider' add 20 to all the
         * posts that have the tag that the current post have ( add 20 for
         * each tag found )
         * 
         * if $option_tags is set to 'ignore' do nothing
         * 
         * Please note that this option is applied only to the posts that have
         * the "post_tag" taxonomy and that are associated with one or more
         * tags
         */
        
        //proceed with this step only if the $posts_ranking_a exists and it's not empty
        if( isset($posts_ranking_a) and ( count($posts_ranking_a) > 0 ) ){
        
            if( in_array( 'post_tag', get_object_taxonomies( get_post_type($post_id) ) ) ){

                //get an array with a list of the id of the categories
                $current_post_tags = wp_get_post_tags($post_id);

                if( is_array($current_post_tags) and count($current_post_tags) > 0 ){

                    switch ($option_tags){

                        case 'require':

                            foreach($posts_ranking_a as $pra_key => $pra_value){
                                $found = false;
                                $iterated_post_tags = wp_get_post_tags($pra_value['id']);
                                foreach($current_post_tags as $cpt_key => $cpt_value){
                                    if(in_array($cpt_value, $iterated_post_tags)){
                                       $found = true; 
                                    }
                                }
                                if(!$found){
                                    unset($posts_ranking_a[$pra_key]);
                                }
                            }

                            break;

                        case 'consider':

                            foreach($posts_ranking_a as $pra_key => $pra_value){
                                $found = false;
                                $iterated_post_tags = wp_get_post_tags($pra_value['id']);
                                foreach($current_post_tags as $cpt_key => $cpt_value){
                                    if(in_array($cpt_value, $iterated_post_tags)){
                                       $found = true; 
                                    }
                                }
                                if($found){
                                    $posts_ranking_a[$pra_key]['score'] = $posts_ranking_a[$pra_key]['score'] + 20;
                                }
                            }

                            break;

                        case 'ignore':

                            break;

                    }

                }

            }
            
        }
        
        if( !isset($posts_ranking_a) or count($posts_ranking_a) <= 5){
            
            $output = '<p>' . esc_attr__('There are no interlinks suggestions at the moment, please use this functionality when you have at least five posts (other than the current one) that match the criteria you defined in the "Suggestions" options.', 'daim') . '</p>';
            
            //send the output
            echo $output;
            die();
            
        }
        
        /*
         * Remove the current post from the $post_ranking_a ( The current post
         * obviously should not be displayed as a interlinks suggestion )
         */
        foreach($posts_ranking_a as $key => $value){
            if($value['id'] == $post_id){
                unset($posts_ranking_a[$key]);
            }
        }
        
        /*
         * Order the $post_ranking_a with descending order based on the 'score'
         */
        usort($posts_ranking_a, array($this->shared, 'usort_callback_1'));
        
        /*
         * Create the $id_list_a[] array with the reference to the first
         * $pool_size elements of $posts_ranking_a
         */
        $id_list_a = array();
        $counter = 1;
        $pool_size = intval(get_option( $this->shared->get('slug') . '_suggestions_pool_size'), 10);
        foreach($posts_ranking_a as $key => $value){
            if($counter > $pool_size){continue;}
            $id_list_a[] = $value['id'];
            $counter++;
        }
        
        /*
         * Get the post URLs and anchors and generate the HTML content of the list
         * based on the $id_list_a
         */
        
	//generate the list content and take 5 random posts from the pool $id_list_a
        $output = '';
        $random_id_a = array();
        for($i=1;$i<=5;$i++){
            
            /*
             * avoid to include the same id multiple times in the list of random
             * IDs taken from the pool
             */
            do{
                $rand_key = array_rand($id_list_a, 1);
                $random_id = $id_list_a[$rand_key];
            }while(in_array($random_id, $random_id_a));

            $output .= '<div class="daim-interlinks-suggestions-link"><a href="' . esc_url( get_permalink($random_id)) . '">' . esc_attr( get_the_title($random_id) ) . '</a></div>';
            $random_id_a[] = $random_id;
            
        }
            
        //send the output
        echo $output;
        die();
        
    }

	/*
	 * Ajax handler used to generate the content of the "Interlinks Optimization" meta box.
	 */
	public function generate_interlinks_optimization(){

		//check the referer
		if(!check_ajax_referer( 'daim', 'security', false )){echo "Invalid AJAX Request"; die();}

		//check the capability
		if(!current_user_can(get_option( $this->shared->get('slug') . "_interlinks_optimization_mb_required_capability"))){echo "Invalid Capability"; die();}

		//get data
		$post_id = intval($_POST['post_id'], 10);

		//generate the HTML of the meta-box
		$output = $this->shared->generate_interlinks_optimization_metabox_html(get_post($post_id));

		//send the output
		echo $output;
		die();

	}
    
}