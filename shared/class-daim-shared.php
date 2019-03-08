<?php

/*
 * this class should be used to stores properties and methods shared by the
 * admin and public side of wordpress
 */
class Daim_Shared
{

	//properties used in add_autolinks()
	private $ail_id;
	private $ail_a;
	private $parsed_autolink;
	private $max_number_autolinks_per_post;
	private $same_url_limit = null;
	private $autolinks_ca = null;
	private $pb_id = null;
	private $pb_a = null;

    //regex
	public $regex_list_of_gutenberg_blocks = '/^(\s*([A-Za-z0-9-\/]+\s*,\s*)+[A-Za-z0-9-\/]+\s*|\s*[A-Za-z0-9-\/]+\s*)$/';
    public $regex_list_of_post_types = '/^(\s*([A-Za-z0-9_-]+\s*,\s*)+[A-Za-z0-9_-]+\s*|\s*[A-Za-z0-9_-]+\s*)$/';
    public $regex_number_ten_digits = '/^\s*\d{1,10}\s*$/';
    public $regex_list_of_tags = '/^(\s*([A-Za-z0-9]+\s*,\s*)+[A-Za-z0-9]+\s*|\s*[A-Za-z0-9]+\s*)$/';
    public $regex_capability = '/^\s*[A-Za-z0-9_]+\s*$/';
    
    protected static $instance = null;

    private $data = array();

    private function __construct()
    {

        //Set plugin textdomain
        load_plugin_textdomain('daim', false, 'interlinks-manager/lang/');
        
        $this->data['slug'] = 'daim';
        $this->data['ver'] = '1.21';
        $this->data['dir'] = substr(plugin_dir_path(__FILE__), 0, -7);
        $this->data['url'] = substr(plugin_dir_url(__FILE__), 0, -7);

    }

    public static function get_instance()
    {

        if (null == self::$instance) {
            self::$instance = new self;
        }

        return self::$instance;

    }

    //retrieve data
    public function get($index)
    {
        return $this->data[$index];
    }
    
    /*
     * Get the number of manual interlinks in a given string
     * 
     * @param string $string The string in which the search should be performed
     * @return int The number of internal links in the string
     */
    public function get_manual_interlinks($string){
        
        //remove the HTML comments
        $string = $this->remove_html_comments($string);
        
        //remove script tags
        $string = $this->remove_script_tags($string);
        
        /*
         * Get the website url and escape the regex character. # and 
         * whitespace ( used with the 'x' modifier ) are not escaped, thus
         * should not be included in the $site_url string
         */
        $site_url = preg_quote(get_home_url());
        
        //working regex
        $num_matches = preg_match_all(
            '{
            <a                      #1 Match the element a start-tag
            [^>]+                   #2 Match everything except > for at least one time
            href\s*=\s*             #3 Equal may have whitespaces on both sides
            ([\'"]?)                #4 Match double quotes, single quote or no quote ( captured for the backreference \1 )
            ' . $site_url . '       #5 The site URL ( Scheme and Domain )
            [^\'">\s]+              #6 The rest of the URL ( Path and/or File )
            (\1)                    #7 Backreference that matches the href value delimiter matched at line 4
            [^>]*                   #8 Any character except > zero or more times
            >                       #9 End of the start-tag
            .*?                     #10 Link text or nested tags. After the dot ( enclose in parenthesis ) negative lookbehinds can be applied to avoid specific stuff inside the link text or nested tags. Example with single negative lookbehind (.(?<!word1))*? Example with multiple negative lookbehind (.(?<!word1)(?<!word2)(?<!word3))*?
            <\/a\s*>                #11 Element a end-tag with optional white-spaces characters before the >
            }ix',                   
            $string, $matches);
        
        return $num_matches;
        
    }
    
    /*
     * Count the number of auto interlinks in the string
     * 
     * @param string $string The string in which the search should be performed
     * @return int The number of autolinks
     */
    public function get_autolinks_number($string){
        
        //remove the HTML comments
        $string = $this->remove_html_comments($string);
        
        //remove script tags
        $string = $this->remove_script_tags($string);
        
        /*
         * Get the website url and quote and escape the regex character. # and 
         * whitespace ( used with the 'x' modifier ) are not escaped, thus
         * should not be included in the $site_url string
         */
        $site_url = preg_quote(get_home_url());
        
        $num_matches = preg_match_all(
            '{
            <a\s+                   #1 The element a start-tag followed by one or more whitespace character
            data-ail="[\d]+"\s+     #2 The data-ail attribute followed by one or more whitespace character
            target="_[\w]+"\s+      #3 The target attribute followed by one or more whitespace character
            (?:rel="nofollow"\s+)?  #4 The rel="nofollow" attribute followed by one or more whitespace character, all is made optional by the trailing ? that works on the non-captured group ?:
            href\s*=\s*             #5 Equal may have whitespaces on both sides
            ([\'"]?)                #6 Match double quotes, single quote or no quote ( captured for the backreference \1 )
            ' . $site_url . '       #7 The site URL ( Scheme and Domain )
            [^\'">\s]+              #8 The rest of the URL ( Path and/or File )
            (\1)                    #9 Backreference that matches the href value delimiter matched at line 5
            [^>]*                   #10 Any character except > zero or more times
            >                       #11 End of the start-tag
            .+?                     #12 Any character one or more time with the quantifier lazy
            <\/a\s*>                #13 Element a end-tag with optional white-spaces characters before the >
            }ix',
            $string, $matches);
        
        return $num_matches;
        
    }
    
    /*
     * Get the raw post_content of the specified post
     * 
     * @param $post_id The ID of the post
     * @return string The raw post content
     */
    public function get_raw_post_content($post_id){
        
        global $wpdb;
        $table_name = $wpdb->prefix . "posts";
        $safe_sql = $wpdb->prepare("SELECT post_content FROM $table_name WHERE ID = %d", $post_id);
        $post_obj = $wpdb->get_row($safe_sql);
        
        return $post_obj->post_content;
        
    }
    
    /*
     * The optimization is calculated based on:
     * - the "Optimization Delta" option
     * - the number of interlinks
     * - the content length
     * True is returned if the content is optimized, False if it's not optimized
     * 
     * @param int $number_of_interlinks The overall number of interlinks ( manual interlinks + auto interlinks )
     * @param int $content_length The content length
     * @return bool True if is optimized, False if is not optimized
     */
    public function calculate_optimization($number_of_interlinks, $content_length){

        //get the values of the options
        $optimization_num_of_characters = (int) get_option($this->get('slug') . '_optimization_num_of_characters');
        $optimization_delta = (int) get_option($this->get('slug') . '_optimization_delta');
        
        //determines if this post is optimized
        $optimal_number_of_interlinks = (int) $content_length / $optimization_num_of_characters;
        if(
            ( $number_of_interlinks >= ( $optimal_number_of_interlinks - $optimization_delta ) ) and
            ( $number_of_interlinks <= ( $optimal_number_of_interlinks + $optimization_delta ) )
        ){
            $is_optimized = true;
        }else{
            $is_optimized = false;
        }
        
        return $is_optimized;
        
    }
    
    /*
     * The optimal number of interlinks is calculated by dividing the content
     * length for the value in the "Characters per Interlink" option and
     * converting the result to an integer
     * 
     * @param int $number_of_interlinks The overall number of interlinks ( manual interlinks + auto interlinks )
     * @param int $content_length The content length
     * @return int The number of recommended interlinks
     */
    public function calculate_recommended_interlinks($number_of_interlinks, $content_length){

        //get the values of the options
        $optimization_num_of_characters = get_option($this->get('slug') . '_optimization_num_of_characters');
        $optimization_delta = get_option($this->get('slug') . '_optimization_delta');
        
        //determines the optimal number of interlinks
        $optimal_number_of_interlinks = $content_length / $optimization_num_of_characters;
        
        return intval($optimal_number_of_interlinks, 10);
        
    }
    
    /*
     * The minimum number of interlinks suggestion is calculated by subtracting
     * half of the optimization delta from the optimal number of interlinks
     * 
     * @param int The post id
     * @return int The minimum number of interlinks suggestion
     */
    public function get_suggested_min_number_of_interlinks($post_id){

        //get the content length of the raw post
        $content_length = mb_strlen($this->get_raw_post_content($post_id));
        
        //get the values of the options
        $optimization_num_of_characters = intval( get_option($this->get('slug') . '_optimization_num_of_characters'), 10);
        $optimization_delta = intval( get_option($this->get('slug') . '_optimization_delta'), 10);
        
        //determines the optimal number of interlinks
        $optimal_number_of_interlinks = $content_length / $optimization_num_of_characters;
        
        //get the minimum number of interlinks
        $min_number_of_interlinks = intval( ( $optimal_number_of_interlinks - ( $optimization_delta / 2 ) ), 10);
        
        //set to zero negative values
        if( $min_number_of_interlinks < 0 ){ $min_number_of_interlinks = 0; }
        
        return $min_number_of_interlinks;
        
    }
    
    /*
     * The maximum number of interlinks suggestion is calculated by adding
     * half of the optimization delta to the optimal number of interlinks
     * 
     * @param int The post id
     * @return int The maximum number of interlinks suggestion
     */
    public function get_suggested_max_number_of_interlinks($post_id){

        //get the content length of the raw post
        $content_length = mb_strlen($this->get_raw_post_content($post_id));
        
        ///get the values of the options
        $optimization_num_of_characters = get_option($this->get('slug') . '_optimization_num_of_characters');
        $optimization_delta = get_option($this->get('slug') . '_optimization_delta');
        
        //determines the optimal number of interlinks
        $optimal_number_of_interlinks = $content_length / $optimization_num_of_characters;
        
        return intval( ( $optimal_number_of_interlinks + ( $optimization_delta / 2 ) ), 10);
        
    }
    
    /*
     * Get the number of hits related to a specific post
     * 
     * @param $post_id The post_id for which the hits should be counted
     * @return int The number of hits
     */
    public function get_number_of_hits($post_id){
        
        global $wpdb;
        $table_name = $wpdb->prefix . $this->get('slug') . "_hits";
        $safe_sql = $wpdb->prepare("SELECT COUNT(*) FROM $table_name WHERE source_post_id = %d", $post_id);
        $number_of_hits = $wpdb->get_var($safe_sql);
        
        return $number_of_hits;
        
    }
    
    /*
     * Add autolinks to the content based on the keyword created with the AIL
     * menu:
     * 
     * 1 - The protected blocks are applied with apply_protected_blocks()
     * 2 - The words to be converted as a link are temporarely replaced with [ail]ID[/ail]
     * 3 - The [ail]ID[/ail] identifiers are replaced with the actual links
     * 4 - The protected block are removed with the remove_protected_blocks()
     * 5 - The content with applied the autolinks is returned
     * 
     * @param $content The content on which the autolinks should be applied
     * @param $check_query This parameter is set to True when the method is
     * called inside the loop and is used to verify if we are in a single post 
     * @param $post_type If the autolinks are added from the back-end this
     * parameter is used to determine the post type of the content
     * $post_id This parameter is used if the method has been called outside
     * the loop
     * @return string The content with applied the autolinks
     * 
     */
    public function add_autolinks($content, $check_query = true, $post_type = '', $post_id = false ){
        
        //verify that we are inside a post, page or cpt
        if($check_query){
            if(!is_singular() or is_attachment() or is_feed()){return $content;}
        }
        
        /*
         * If the $post_id is not set means that we are in the loop and can be
         * retrieved with get_the_ID()
         */
        if($post_id === false){ $post_id = get_the_ID(); }

        //get the permalink
        $post_permalink = get_permalink($post_id);
            
        /*
         * Verify with the "Enable AIL" post meta data or ( if the meta data is
         * not present ) verify through the "Default Enable AIL" option if the
         * autolinks should be applied to this post
         */
        $enable_ail = get_post_meta( $post_id, '_daim_enable_ail', true );
        if(strlen(trim($enable_ail)) == 0){
            $enable_ail = get_option( $this->get('slug') . '_default_enable_ail_on_post');
        }
        if( intval($enable_ail, 10) == 0 ){return $content;}
        
        //protect the tags and the commented HTML with protected blocks
        $content = $this->apply_protected_blocks($content);
        
        //initialize properties
        $this->ail_id = 0;
        $this->ail_a = array();
        $this->post_id = $post_id;
        
        //get the max number of autolinks allowed per post
        $this->max_number_autolinks_per_post = $this->get_max_number_autolinks_per_post($this->post_id);

	    //Save the "Same URL Limit" as a class property
	    $this->same_url_limit = intval(get_option($this->get('slug') . '_same_url_limit'), 10);
        
        //initialize the counter of the autolinks applied
        $total_autolink_applied = 0;
        
        //get an array with the autolinks from the db table
        global $wpdb;
        $table_name = $wpdb->prefix . $this->get('slug') . "_autolinks";
        $sql = "SELECT * FROM $table_name ORDER BY priority DESC";
        $autolinks = $wpdb->get_results($sql, ARRAY_A);

	    /*
		 * To avoid additional database requests for each autolink in preg_replace_callback_2() save the data of the
		 * autolink in an array that uses the "autolink_id" as its index.
		 */
	    $this->autolinks_ca = $this->save_autolinks_in_custom_array($autolinks);

	    //Apply the Random Prioritization if enabled
	    if (intval(get_option($this->get('slug') . '_random_prioritization'), 10) === 1) {
		    $autolinks = $this->apply_random_prioritization($autolinks, $post_id);
	    }

        //cycle through all the defined autolinks
        foreach ($autolinks as $key => $autolink) {

            /*
             * Self AIL
             *
             * If the "Ignore Self AIL" option is set to true, do not apply the autolinks that have, as a target, the
             * post where they should be applied
             *
             * Compare $autolink['url'] with the the permalink ( with the get_home_url() removed ),
             * if the comparison returns true ( which means that the autolink url and the current url are the same ) do
             * not apply the autolink
             */
            if( intval(get_option( $this->get('slug') . '_ignore_self_ail'), 10) == 1 ){
                $home_url_length = abs( strlen( get_home_url() ) );
                if( $autolink['url'] == substr( $post_permalink, $home_url_length ) ){continue;}
            }

            $this->parsed_autolink = $autolink;
            
            //get the list of post types where the autolinks should be applied
            $activate_post_types = preg_replace('/\s+/', '', $autolink['activate_post_types']);
            $post_types_a = explode(",", $activate_post_types);
            
            /*
             * If we are adding the autolinks through the back-end to count
             * later their number compare the allowed post type with the
             * $post_type variable. If we are in the loop get the post type with
             * the get_post_type() function
             */
            if($post_type != ''){
                if( in_array($post_type, $post_types_a) === false ){continue;}
            }else{
                if( in_array(get_post_type(), $post_types_a) === false ){continue;}
            }

            //get the max number of autolinks per keyword
            $max_number_autolinks_per_keyword = $autolink['max_number_autolinks'];
            
            //apply a case insensitive search if the case_insensitive_flag is selected
            if($autolink['case_insensitive_search']){
                $modifier = 'iu';//enable case sensitive and unicode modifier
            }else{
                $modifier = 'u';//enable unicode modifier
            }
            
            $ail_temp = array();
            
            //find the left boundary
            switch($autolink['string_before']){
                case 1:
                    $string_before = '\b';
                    break;
                
                case 2:
                    $string_before = ' ';
                    break;
                
                case 3:
                    $string_before = ',';
                    break;
                
                case 4:
                    $string_before = '\.';
                    break;
                
                case 5:
                    $string_before = '';
                    break;
            }
            
            //find the right boundary
            switch($autolink['string_after']){
                case 1:
                    $string_after = '\b';
                    break;
                
                case 2:
                    $string_after = ' ';
                    break;
                
                case 3:
                    $string_after = ',';
                    break;
                
                case 4:
                    $string_after = '\.';
                    break;

                case 5:
                    $string_after = '';
                    break;
            }
            
            //escape regex characters and the '/' regex delimiter
            $autolink_keyword = preg_quote($autolink['keyword'], '/');
            
            /*
             * Step 1: "The creation of temporary identifiers of the sostitutions"
             * Replace all the matches with the [ail]ID[/ail] string, where the
             * ID is the identifier of the sostitution.
             * The ID is also used as the index of the $this->ail_a temporary array
             * used to store information about all the sostutions.
             * This array will be later used in "Step 2" to replace the
             * [ail]ID[/ail] string with the actual links
             */
            $content = preg_replace_callback(
            '/(' . ($string_before) . ')(' . $autolink_keyword . ')(' . ($string_after) . ')/' . $modifier,
            array($this, 'preg_replace_callback_1'),
            $content,
            $max_number_autolinks_per_keyword);
            
        }
        
        /*
         * Step 2: "The replacement of the temporary string [ail]ID[/ail]"
         * Replaces the [ail]ID[/ail] matches found in the $content with the
         * actual links by using the $this->ail_a array to find the identifier of the
         * sostitutions and by retrieving in the db table "autolinks" ( with the
         *  "autolink_id" ) additional information about the sostitution.
         */
        $content = preg_replace_callback(
        '/\[ail\](\d+)\[\/ail\]/',
        array($this, 'preg_replace_callback_2'),
        $content);
        
        //remove the protected blocks
        $content = $this->remove_protected_blocks($content);
        
        return $content;
        
    }
    
    /*
     * Replace the commented HTML and the specified tags with [pr]ID[/pr]
     * 
     * The replaced tags and URLs are saved in the property $pr_a, an array with
     * the ID used in the block as the index
     * 
     * @param $content string The unprotected $content
     * @return string The $content with applied the protected block
     */
    private function apply_protected_blocks($content){
        
        $this->pb_id = 0;
        $this->pb_a = array();

	    //Get the Gutenberg Protected Blocks
	    $protected_gutenberg_blocks   = get_option($this->get('slug') . '_protected_gutenberg_blocks');
	    $protected_gutenberg_blocks_a = maybe_unserialize($protected_gutenberg_blocks);
	    if ( ! is_array($protected_gutenberg_blocks_a)) {
		    $protected_gutenberg_blocks_a = array();
	    }

	    //Get the Protected Gutenberg Custom Blocks
	    $protected_gutenberg_custom_blocks   = get_option($this->get('slug') . '_protected_gutenberg_custom_blocks');
	    $protected_gutenberg_custom_blocks_a = array_filter(explode(',',
		    str_replace(' ', '', trim($protected_gutenberg_custom_blocks))));

	    //Get the Protected Gutenberg Custom Void Blocks
	    $protected_gutenberg_custom_void_blocks   = get_option($this->get('slug') . '_protected_gutenberg_custom_void_blocks');
	    $protected_gutenberg_custom_void_blocks_a = array_filter(explode(',',
		    str_replace(' ', '', trim($protected_gutenberg_custom_void_blocks))));

	    $protected_gutenberg_blocks_comprehensive_list_a = array_merge($protected_gutenberg_blocks_a,
		    $protected_gutenberg_custom_blocks_a, $protected_gutenberg_custom_void_blocks_a);

	    if (is_array($protected_gutenberg_blocks_comprehensive_list_a)) {

		    foreach ($protected_gutenberg_blocks_comprehensive_list_a as $key => $block) {

			    //Non-Void Blocks
			    if ($block === 'paragraph' or
			        $block === 'image' or
			        $block === 'heading' or
			        $block === 'gallery' or
			        $block === 'list' or
			        $block === 'quote' or
			        $block === 'audio' or
			        $block === 'cover-image' or
			        $block === 'subhead' or
			        $block === 'video' or
			        $block === 'code' or
			        $block === 'preformatted' or
			        $block === 'pullquote' or
			        $block === 'table' or
			        $block === 'verse' or
			        $block === 'button' or
			        $block === 'columns' or
			        $block === 'more' or
			        $block === 'nextpage' or
			        $block === 'separator' or
			        $block === 'spacer' or
			        $block === 'text-columns' or
			        $block === 'shortcode' or
			        $block === 'embed' or
			        $block === 'html' or
			        $block === 'core-embed/twitter' or
			        $block === 'core-embed/youtube' or
			        $block === 'core-embed/facebook' or
			        $block === 'core-embed/instagram' or
			        $block === 'core-embed/wordpress' or
			        $block === 'core-embed/soundcloud' or
			        $block === 'core-embed/spotify' or
			        $block === 'core-embed/flickr' or
			        $block === 'core-embed/vimeo' or
			        $block === 'core-embed/animoto' or
			        $block === 'core-embed/cloudup' or
			        $block === 'core-embed/collegehumor' or
			        $block === 'core-embed/dailymotion' or
			        $block === 'core-embed/funnyordie' or
			        $block === 'core-embed/hulu' or
			        $block === 'core-embed/imgur' or
			        $block === 'core-embed/issuu' or
			        $block === 'core-embed/kickstarter' or
			        $block === 'core-embed/meetup-com' or
			        $block === 'core-embed/mixcloud' or
			        $block === 'core-embed/photobucket' or
			        $block === 'core-embed/polldaddy' or
			        $block === 'core-embed/reddit' or
			        $block === 'core-embed/reverbnation' or
			        $block === 'core-embed/screencast' or
			        $block === 'core-embed/scribd' or
			        $block === 'core-embed/slideshare' or
			        $block === 'core-embed/smugmug' or
			        $block === 'core-embed/speaker' or
			        $block === 'core-embed/ted' or
			        $block === 'core-embed/tumblr' or
			        $block === 'core-embed/videopress' or
			        $block === 'core-embed/wordpress-tv' or
			        in_array($block, $protected_gutenberg_custom_blocks_a)
			    ) {

				    //escape regex characters and the '/' regex delimiter
				    $block = preg_quote($block, '/');

				    //Non-Void Blocks Regex
				    $content = preg_replace_callback(
					    '/
                    <!--\s+(wp:' . $block . ').*?-->        #1 Gutenberg Block Start
                    .*?                                     #2 Gutenberg Content
                    <!--\s+\/\1\s+-->                       #3 Gutenberg Block End
                    /ixs',
					    array($this, 'apply_single_protected_block'),
					    $content
				    );

				    //Void Blocks
			    } elseif ($block === 'categories' or
			              $block === 'latest-posts' or
			              in_array($block, $protected_gutenberg_custom_void_blocks_a)
			    ) {

				    //escape regex characters and the '/' regex delimiter
				    $block = preg_quote($block, '/');

				    //Void Blocks Regex
				    $content = preg_replace_callback(
					    '/
                    <!--\s+wp:' . $block . '.*?\/-->        #1 Void Block
                    /ix',
					    array($this, 'apply_single_protected_block'),
					    $content
				    );

			    }

		    }

	    }

        /*
         * Protect the commented sections, enclosed between <!-- and -->
         */
        $content = preg_replace_callback(
            '/
            <!--                                #1 Comment Start
            .*?                                 #2 Any character zero or more time with a lazy quantifier
            -->                                 #3 Comment End
            /ix',                               
            array($this,'apply_single_protected_block'),
            $content
        );
        
        //Get the list of the protected tags from the "Protected Tags" option
        $protected_tags_a = $this->get_protected_tags_option();
        foreach($protected_tags_a as $key => $single_protected_tag){

            /*
             * Validate the tag. HTML elements all have names that only use
             * characters in the range 0–9, a–z, and A–Z.
             */
            if( preg_match( '/^[0-9a-zA-Z]+$/' , $single_protected_tag) === 1 ){
                
                //make the tag lowercase
                $single_protected_tag = strtolower($single_protected_tag);
                
                /*
                 * Apply different treatment if the tag is a void tag or a
                 * non-void tag
                 */
                if( $single_protected_tag == 'area' or
                    $single_protected_tag == 'base' or
                    $single_protected_tag == 'br' or
                    $single_protected_tag == 'col' or
                    $single_protected_tag == 'embed' or
                    $single_protected_tag == 'hr' or
                    $single_protected_tag == 'img' or
                    $single_protected_tag == 'input' or
                    $single_protected_tag == 'keygen' or
                    $single_protected_tag == 'link' or
                    $single_protected_tag == 'meta' or
                    $single_protected_tag == 'param' or
                    $single_protected_tag == 'source' or
                    $single_protected_tag == 'track' or
                    $single_protected_tag == 'wbr'
                ){

                    //apply the protected block on void tags
                    $content = preg_replace_callback(
                        '/                                  
                        <                                   #1 Begin the start-tag
                        (' . $single_protected_tag . ')     #2 The tag name ( captured for the backreference )
                        (\s+[^>]*)?                         #3 Match the rest of the start-tag
                        >                                   #4 End the start-tag
                        /ix',                               
                        array($this,'apply_single_protected_block'),
                        $content
                    );

                }else{

                    //apply the protected block on non-void tags
                    $content = preg_replace_callback(
                        '/
                        <                                   #1 Begin the start-tag
                        (' . $single_protected_tag . ')     #2 The tag name ( captured for the backreference )
                        (\s+[^>]*)?                         #3 Match the rest of the start-tag
                        >                                   #4 End the start-tag
                        .*?                                 #5 The element content ( with the "s" modifier the dot matches also the new lines )
                        <\/\1\s*>                           #6 The end-tag with a backreference to the tag name ( \1 ) and optional white-spaces before the closing >
                        /ixs',                              
                        array($this,'apply_single_protected_block'),
                        $content
                    );

                }
                
            }
            
        }
        
        return $content;
        
    }
    
    /*
     * This method is used inside all the preg_replace_callback located in the
     * apply_protected_blocks() method.
     * 
     * What it does is:
     * 1 - save the match in the $pb_a array
     * 2 - return the protected block with the related identifier ( [pb]ID[/pb] )
     * 
     * @param $m An array with at index 0 the complete match and at index 1 the
     * capture group
     * @return string
     */
    private function apply_single_protected_block($m){
        
        //save the match in the $pb_a array
        $this->pb_id++;
        $this->pb_a[$this->pb_id] = $m[0];

        /*
         * replace the tag/URL with the protected block and the
         * index of the $pb_a array as the identifier
         */
        return '[pb]' . $this->pb_id . '[/pb]';
            
    }
    
    /*
     * Replace the block [pr]ID[/pr] with the related tags found in the
     * $pb_a property
     * 
     * @param $content string The $content with applied the protected block
     * return string The unprotected content
     */
    private function remove_protected_blocks($content){
        
        $content = preg_replace_callback(
            '/\[pb\](\d+)\[\/pb\]/',
            array($this, 'preg_replace_callback_3'),
            $content
        );
        
        return $content;
        
    }
    
    /*
     * Calculate the link juice of a links based on the given parameters.
     * 
     * @param $post_content_with_autolinks The post content ( with autolinks applied )
     * @param $post_id The post id
     * @param $link_postition The position of the link in the string ( the line where the link string starts )
     * @return int The link juice of the link
     */
    public function calculate_link_juice($post_content_with_autolinks, $post_id, $link_position){
        
        //Get the SEO power of the post
        $seo_power = get_post_meta( $post_id, '_daim_seo_power', true );
        if(strlen(trim($seo_power)) == 0){$seo_power = (int) get_option( $this->get('slug') . '_default_seo_power');}
        
        /*
         * Divide the SEO power for the total number of links ( all the links,
         * external and internal are considered )
         */
        $juice_per_link = $seo_power / $this->get_number_of_links($post_content_with_autolinks);
        
        /*
         * Calculate the index of the link on the post ( example 1 for the first
         * link or 3 for the third link )
         * A regular expression that counts the links on a string that starts
         * from the beginning of the post and ends at the $link_position is used
         */
        $post_content_before_the_link = substr($post_content_with_autolinks, 0, $link_position);
        $number_of_links_before = $this->get_number_of_links($post_content_before_the_link);
        
        /*
         * Remove a percentage of the $juice_value based on the number of links
         * before this one
         */
        $penality_per_position_percentage = (int) get_option( $this->get('slug') . '_penality_per_position_percentage');
        $link_juice = $juice_per_link - ( ( $juice_per_link / 100 * $penality_per_position_percentage ) * $number_of_links_before );
        
        //return the link juice or 0 if the calculated link juice is negative
        if($link_juice < 0){$link_juice = 0;}
        return $link_juice;
        
    }
    
    /*
     * Get the total number of links ( any kind of link: internal, external,
     * nofollow, dofollow ) available in the passed string
     * 
     * @param $s The string on which the number of links should be counted
     * @return int The number of links found on the string
     */
    public function get_number_of_links($s){
                
        //remove the HTML comments
        $s = $this->remove_html_comments($s);
        
        //remove script tags
        $s = $this->remove_script_tags($s);
        
        $num_matches = preg_match_all(
            '{<a                                #1 Begin the element a start-tag
            [^>]+                               #2 Any character except > at least one time
            href\s*=\s*                         #3 Equal may have whitespaces on both sides
            ([\'"]?)                            #4 Match double quotes, single quote or no quote ( captured for the backreference \1 )
            [^\'">\s]+                          #5 The site URL
            \1                                  #6 Backreference that matches the href value delimiter matched at line 4     
            [^>]*                               #7 Any character except > zero or more times
            >                                   #8 End of the start-tag
            .*?                                 #9 Link text or nested tags. After the dot ( enclose in parenthesis ) negative lookbehinds can be applied to avoid specific stuff inside the link text or nested tags. Example with single negative lookbehind (.(?<!word1))*? Example with multiple negative lookbehind (.(?<!word1)(?<!word2)(?<!word3))*?
            <\/a\s*>                            #10 Element a end-tag with optional white-spaces characters before the >
            }ix',
        $s, $matches);
        
        return $num_matches;
        
    }
    
    /*
     * Given a link returns it with the anchor link removed.
     * 
     * @param $s The link that should be analyzed
     * @return string The link with the link anchor removed
     */
    public function remove_link_to_anchor($s){
        
        $s = preg_replace_callback(
            '/([^#]+)               #Everything except # one or more times ( captured )
            \#.*                    #The # with anything the follows zero or more times
            /ux',
            array($this, 'preg_replace_callback_4'),
            $s
        );
            
        return $s;
        
    }
    
    /*
     * Given an URL the parameter part is removed
     * 
     * @param $s The URL
     * @return string The URL with the URL parameters removed
     */
    public function remove_url_parameters($s){
        
        $s = preg_replace_callback(
            '/([^?]+)               #Everything except ? one or more time ( captured )
            \?.*                    #The ? with anything the follows zero or more times
            /ux',
            array($this, 'preg_replace_callback_5'),
            $s
        );
            
        return $s;
        
    }
    
    /*
     * Callback of the preg_replace_callback() function
     * 
     * This callback is used to avoid an anonimus function as a parameter of the
     * preg_replace_callback() function for PHP backward compatibility
     * 
     * Look for uses of preg_replace_callback_1 to find which
     * preg_replace_callback() function is actually using this callback
     */
    public function preg_replace_callback_1($m){
                
        /*
         * do not apply the replacement ( and return the matches string )
         * if the max number of autolinks per post has been reached
         */
        if($this->max_number_autolinks_per_post == $this->ail_id or
           $this->same_url_limit_reached()){
            /*
             * return the captered text with related left and right boundaries
             * to not alter the content
             */
            return $m[1] . $m[2] . $m[3];
        }else{
            $this->ail_id++;
            $this->ail_a[$this->ail_id]['autolink_id'] = $this->parsed_autolink['id'];
	        $this->ail_a[$this->ail_id]['url'] = $this->parsed_autolink['url'];
            $this->ail_a[$this->ail_id]['text'] = $m[2];
            $this->ail_a[$this->ail_id]['left_boundary'] = $m[1];
            $this->ail_a[$this->ail_id]['right_boundary'] = $m[3];

            return '[ail]' . $this->ail_id . '[/ail]';
        }
        
    }
    
    /*
     * Callback of the preg_replace_callback() function
     * 
     * This callback is used to avoid an anonimus function as a parameter of the
     * preg_replace_callback() function for PHP backward compatibility
     * 
     * Look for uses of preg_replace_callback_2 to find which
     * preg_replace_callback() function is actually using this callback
     */
    public function preg_replace_callback_2($m){
        
        /*
         * Find the related text of the link from the $this->ail_a multidimensional
         * array by using the match as the index
         */
        $link_text = $this->ail_a[$m[1]]['text'];

        /*
         * Get the left and right boundaries
         */
        $left_boundary = $this->ail_a[$m[1]]['left_boundary'];
        $right_boundary = $this->ail_a[$m[1]]['right_boundary'];

	    //Get the autolink_id
        $autolink_id = $this->ail_a[$m[1]]['autolink_id'];

        //get the "url" value
        $link_url = $this->autolinks_ca[$autolink_id]['url'];

        //generate the title attribute HTML if the "title" field is not empty
        if(strlen(trim($this->autolinks_ca[$autolink_id]['title'])) > 0){
            $title_attribute = 'title="' . esc_attr(stripslashes($this->autolinks_ca[$autolink_id]['title'])) . '"';
        }else{
            $title_attribute = '';
        }

        //get the "open_new_tab" value
        if( intval($this->autolinks_ca[$autolink_id]['open_new_tab'], 10) == 1 ){$open_new_tab = 'target="_blank"';}else{$open_new_tab = 'target="_self"';}

        //get the "use_nofollow" value
        if( intval($this->autolinks_ca[$autolink_id]['use_nofollow'], 10) == 1 ){$use_nofollow = 'rel="nofollow"';}else{$use_nofollow = '';}

        //return the actual link
        return $left_boundary . '<a data-ail="' . $this->post_id . '" ' . $open_new_tab . ' ' . $use_nofollow . ' href="' . esc_url(get_home_url() . $link_url) . '" ' . $title_attribute . '>' . $link_text . '</a>' . $right_boundary;
            
    } 
    
    /*
     * Callback of the preg_replace_callback() function
     * 
     * This callback is used to avoid an anonimus function as a parameter of the
     * preg_replace_callback() function for PHP backward compatibility
     * 
     * Look for uses of preg_replace_callback_3 to find which
     * preg_replace_callback() function is actually using this callback
     */
    public function preg_replace_callback_3($m){
        
        /*
         * The presence of nested protected blocks is verified. If a protected
         * block is inside the content of a protected block the
         * remove_protected_block() method is applied recursively until there
         * are no protected blocks
         */
        $html = $this->pb_a[$m[1]];
        $recursion_ends = false;
        
        do{
            
            /*
             * if there are no protected blocks in content of the protected
             * block end the recursion, otherwise apply remove_protected_block()
             * again
             */
            if( preg_match('/\[pb\](\d+)\[\/pb\]/', $html) == 0 ){
                $recursion_ends = true;
            }else{
                $html = $this->remove_protected_blocks($html);
            }
            
        }while($recursion_ends === false);

        return $html;
            
    }
    
    /*
     * Callback of the preg_replace_callback() function
     * 
     * This callback is used to avoid an anonimus function as a parameter of the
     * preg_replace_callback() function for PHP backward compatibility
     * 
     * Look for uses of preg_replace_callback_4 to find which
     * preg_replace_callback() function is actually using this callback
     */
    public function preg_replace_callback_4($m){
        
        return $m[1];
            
    }
    
    /*
     * Callback of the preg_replace_callback() function
     * 
     * This callback is used to avoid an anonimus function as a parameter of the
     * preg_replace_callback() function for PHP backward compatibility
     * 
     * Look for uses of preg_replace_callback_5 to find which
     * preg_replace_callback() function is actually using this callback
     */
    public function preg_replace_callback_5($m){
        
        return $m[1];
            
    }
    
    /*
     * Callback of the preg_replace_callback() function
     * 
     * This callback is used to avoid an anonimus function as a parameter of the
     * preg_replace_callback() function for PHP backward compatibility
     * 
     * Look for uses of preg_replace_callback_6 to find which
     * preg_replace_callback() function is actually using this callback
     */
    public function preg_replace_callback_6($m){
                
        //replace '<a "' with '<a data-mil="[post-id]"' and return
        return '<a data-mil="' . get_the_ID() . '" ' . mb_substr($m[0], 3);
            
    }
    
    /*
     * Callback of the usort() function
     * 
     * This callback is used to avoid an anonimus function as a parameter of the
     * usort() function for PHP backward compatibility
     * 
     * Look for uses of usort_callback_1 to find which usort() function is
     * actually using this callback
     */
    public function usort_callback_1($a, $b){
        
        return $b['score'] - $a['score'];
        
    }
    
    /*
     * Remove the HTML comment ( comment enclosed between <!-- and --> )
     * 
     * @param $content The HTML with the comments
     * @return string The HTML without the comments
     */
    public function remove_html_comments($content){
        
        $content = preg_replace(
            '/
            <!--                                #1 Comment Start
            .*?                                 #2 Any character zero or more time with a lazy quantifier
            -->                                 #3 Comment End
            /ix',                               
            '',
            $content
        );
        
        return $content;
        
    }
    
    /*
     * Remove the script tags
     * 
     * @param $content The HTML with the script tags
     * @return string The HTML without the script tags
     */
    public function remove_script_tags($content){
        
        $content = preg_replace(
            '/
            <                                   #1 Begin the start-tag
            script                              #2 The script tag name
            (\s+[^>]*)?                         #3 Match the rest of the start-tag
            >                                   #4 End the start-tag
            .*?                                 #5 The element content ( with the "s" modifier the dot matches also the new lines )
            <\/script\s*>                       #6 The script end-tag with optional white-spaces before the closing >
            /ixs',                              
            '',
            $content
        );
        
        return $content;
        
    }

    /*
     * Get the number of records available in the "_archive" db table
     *
     * @return int The number of records in the "_archive" db table
     */
    public function number_of_records_in_archive(){

        global $wpdb;
        $table_name = $wpdb->prefix . $this->get('slug') . "_archive";
        $total_items = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");

        return $total_items;

    }

    /*
     * Get the number of records available in the "_juice" db table
     *
     * @return int The number of records in the "_juice" db table
     */
    public function number_of_records_in_juice(){

        global $wpdb;
        $table_name = $wpdb->prefix . $this->get('slug') . "_juice";
        $total_items = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");

        return $total_items;

    }

    /*
     * Get the number of records available in the "_hits" db table
     *
     * @return int The number of records in the "_hits" db table
     */
    public function number_of_records_in_hits(){

        global $wpdb;
        $table_name = $wpdb->prefix . $this->get('slug') . "_hits";
        $total_items = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");

        return $total_items;

    }

	/*
	 * If $needle is present in the $haystack array echos 'selected="selected"'.
	 *
	 * @param $haystack Array
	 * @param $needle String
	 */
	public function selected_array($array, $needle)
	{

		if (is_array($array) and in_array($needle, $array)) {
			return 'selected="selected"';
		}

	}

	/*
	 * If the number of times that the parsed autolink URL ($this->parsed_autolink['url']) is present in the array that
	 * includes the data of the autolinks already applied as temporary identifiers ($this->ail_a) is equal or
	 * higher than the limit estabilished with the "Same URL Limit" option ($this->same_url_limit) True is returned,
	 * otherwise False is returned.
	 *
	 * @return Bool
	 */
	public function same_url_limit_reached()
	{

		$counter = 0;

		foreach ($this->ail_a as $key => $value) {
			if ($value['url'] === $this->parsed_autolink['url']) {
				$counter++;
			}
		}

		if ($counter >= $this->same_url_limit) {
			return true;
		} else {
			return false;
		}

	}

	/*
	 * With versions lower than 1.19 the list of protected block is stored in the "daim_protected_tags" option as a
	 * comma separated list of tags and not as a serialized array.
	 *
	 * This method:
	 *
	 * 1 - Retrieves the "daim_protected_tags" option value
	 * 2 - If the value is a string (pre 1.19) the protected tags are converted to an array
	 * 3 - Returns the array of protected tags
	 *
	 * @return Array
	 */
	public function get_protected_tags_option(){

		$protected_tags_a = [];
		$protected_tags = get_option("daim_protected_tags");
		if(is_string($protected_tags)){
			$protected_tags = str_replace(' ', '', $protected_tags);
			if(strlen($protected_tags) > 0){
				$protected_tags_a = explode(',', str_replace(' ', '', $protected_tags));
			}
		}else{
			$protected_tags_a = $protected_tags;
		}

		return $protected_tags_a;

	}

	/*
	 * Returns the maximum number of AIL allowed per post by using the method explained below.
	 *
	 * If the "General Limit Mode" option is set to "Auto":
	 *
	 * The maximum number of autolinks per post is calculated based on the content length of this post divided for the
	 * value of the "General Limit (Characters per AIL)" option.
	 *
	 * If the "General Limit Mode" option is set to "Manual":
	 *
	 * The maximum number of AIL per post is equal to the value of "General Limit (Max AIL per Post)".
	 *
	 * @param $post_id int The post ID for which the maximum number AIL per post should be calculated.
	 * @return int The maximum number of AIL allowed per post.
	 */
	private function get_max_number_autolinks_per_post($post_id)
	{

		if (intval(get_option($this->get('slug') . '_general_limit_mode'), 10) === 0) {

			//Auto -----------------------------------------------------------------------------------------------------
			$post_obj                = get_post($post_id);
			$post_length             = mb_strlen($post_obj->post_content);
			$characters_per_autolink = intval(get_option($this->get('slug') . '_characters_per_autolink'),
				10);

			return intval($post_length / $characters_per_autolink, 10);

		} else {

			//Manual ---------------------------------------------------------------------------------------------------
			return intval(get_option($this->get('slug') . '_max_number_autolinks_per_post'), 10);

		}

	}

	/*
	 * Given the post object, the HTML content of the Interlinks Optimization meta-box is returned.
	 *
	 * @param $post The post object.
	 * @return String The HTML of the Interlinks Optimization meta-box.
	 */
	public function generate_interlinks_optimization_metabox_html($post){

		$html = '';

		$suggested_min_number_of_interlinks = $this->get_suggested_min_number_of_interlinks($post->ID);
		$suggested_max_number_of_interlinks = $this->get_suggested_max_number_of_interlinks($post->ID);
		$post_content_with_autolinks = $this->add_autolinks($post->post_content, false, $post->post_type, $post->ID);
		$number_of_manual_interlinks = $this->get_manual_interlinks($post->post_content);
		$number_of_autolinks = $this->get_autolinks_number($post_content_with_autolinks);
		$total_number_of_interlinks = $number_of_manual_interlinks + $number_of_autolinks;
		if($total_number_of_interlinks >= $suggested_min_number_of_interlinks and $total_number_of_interlinks <= $suggested_max_number_of_interlinks){
			$html .= '<p>' . esc_attr__('The number of interlinks included in this post is optimized.', 'daim') . '</p>';
		}else{
			$html .= '<p>' . esc_attr__('Please optimize the number of interlinks, this post currently has', 'daim') . '&nbsp' . $total_number_of_interlinks . '&nbsp' . _n('interlink', 'interlinks', $total_number_of_interlinks, 'daim') . '. (' . $number_of_manual_interlinks . '&nbsp' . _n('manual interlink', 'manual interlinks', $number_of_manual_interlinks, 'daim') . '&nbsp' . esc_attr__('and', 'daim') . '&nbsp' . $number_of_autolinks . '&nbsp' . _n('auto interlink', 'auto interlinks', $number_of_autolinks, 'daim') . ')</p>';
			if($suggested_min_number_of_interlinks === $suggested_max_number_of_interlinks){
				$html .= '<p>' . esc_attr__('Based on the content length and on your options their number should be', 'daim') . '&nbsp' . $suggested_min_number_of_interlinks . '.</p>';
			}else{
				$html .= '<p>' . esc_attr__('Based on the content length and on your options their number should be included between', 'daim') . '&nbsp' . $suggested_min_number_of_interlinks . '&nbsp' . esc_attr__('and', 'daim') . '&nbsp' . $suggested_max_number_of_interlinks . '.</p>';
			}
		}

		return $html;

	}

	/*
	 * To avoid additional database requests for each autolink in preg_replace_callback_2() save the data of the
	 * autolink in an array that uses the "autolink_id" as its index.
	 *
	 * @param $autolinks Array
	 * @return Array
	 */
	public function save_autolinks_in_custom_array($autolinks)
	{

		$autolinks_ca = array();

		foreach ($autolinks as $key => $autolink) {

			$autolinks_ca[$autolink['id']] = $autolink;

		}

		return $autolinks_ca;

	}

	/*
	 * Applies a random order (based on the hash of the post_id and autolink_id) to the autolinks that have the same
	 * priority. This ensures a better distribution of the autolinks.
	 *
	 * @param $autolink Array
	 * @param $post_id Int
	 * @return Array
	 */
	public function apply_random_prioritization($autolinks, $post_id)
	{

		//Initialize variables
		$autolinks_rp1 = array();
		$autolinks_rp2 = array();

		//Move the autolinks array in the new $autolinks_rp1 array, which uses the priority value as its index
		foreach ($autolinks as $key => $autolink) {

			$autolinks_rp1[$autolink['priority']][] = $autolink;

		}

		/*
		 * Apply a random order (based on the hash of the post_id and autolink_id) to the autolinks that have the same
		 * priority.
		 */
		foreach ($autolinks_rp1 as $key => $autolinks_a) {

			/*
			 * In each autolink create the new "hash" field which include an hash value based on the post_id and on the
			 * autolink id.
			 */
			foreach ($autolinks_a as $key2 => $autolink) {

				/*
				 * Create the hased value. Note that the "-" character is used to avoid situations where the same input
				 * is provided to the md5() function.
				 *
				 * Without the "-" character for example with:
				 *
				 * $post_id = 12 and $autolink['id'] = 34
				 *
				 * We provide the same input of:
				 *
				 * $post_id = 123 and $autolink['id'] = 4
				 *
				 * etc.
				 */
				$hash = hexdec(md5($post_id . '-' . $autolink['id']));

				/*
				 * Convert all the non-digits to the character "1", this makes the comparison performed in the usort
				 * callback possible.
				 */
				$autolink['hash']   = preg_replace('/\D/', '1', $hash, -1, $replacement_done);
				$autolinks_a[$key2] = $autolink;

			}

			//Sort $autolinks_a based on the new value of the "hash" field
			usort($autolinks_a, function ($a, $b) {

				return $b['hash'] - $a['hash'];

			});

			$autolinks_rp1[$key] = $autolinks_a;

		}

		/*
		 * Move the autolinks in the new $autolinks_rp2 array, which is structured like the original array, where the
		 * value of the priority field is stored in the autolink and it's not used as the index of the array that
		 * includes all the autolinks with the same priority.
		 */
		foreach ($autolinks_rp1 as $key => $autolinks_a) {

			for ($t = 0; $t < (count($autolinks_a)); $t++) {

				$autolink        = $autolinks_a[$t];
				$autolinks_rp2[] = $autolink;

			}

		}

		return $autolinks_rp2;

	}

}