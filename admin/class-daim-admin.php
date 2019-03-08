<?php

/*
 * this class should be used to work with the administrative side of wordpress
 */
class Daim_Admin{

    protected static $instance = null;
    private $shared = null;
    
    private $screen_id_dashboard = null;
    private $screen_id_juice = null;
    private $screen_id_anchors = null;
    private $screen_id_hits = null;
    private $screen_id_autolinks = null;
    private $screen_id_options = null;
    
    private function __construct() {

        //assign an instance of the plugin info
        $this->shared = Daim_Shared::get_instance();
        
        //Load admin stylesheets and JavaScript
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_styles' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );
        
        //Write in back end head
        add_action('admin_head', array( $this, 'wr_admin_head' ));
        
        //Add the admin menu
        add_action( 'admin_menu', array( $this, 'me_add_admin_menu' ) );

        //Load the options API registrations and callbacks
        add_action('admin_init', array( $this, 'op_register_options' ) );
        
        //Add the meta box
        add_action( 'add_meta_boxes', array( $this, 'create_meta_box' ) );
        
        //Save the meta box
        add_action( 'save_post', array($this, 'daim_save_meta_interlinks_options') );
        
        //Export CSV controller
        add_action('init', array($this, 'export_csv_controller'));
        
        //this hook is triggered during the creation of a new blog
        add_action('wpmu_new_blog', array($this, 'new_blog_create_options_and_tables'), 10, 6);
        
        //this hook is triggered during the deletion of a blog
        add_action( 'delete_blog', array($this, 'delete_blog_delete_options_and_tables'), 10, 1 );

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
     * write in the admin head
     */
    public function wr_admin_head(){
        
        echo '<script type="text/javascript">';
            echo 'var daim_ajax_url = "' . admin_url('admin-ajax.php') . '";';
            echo 'var daim_nonce = "' . wp_create_nonce( "daim" ) . '";';
            echo 'var daim_admin_url ="' . get_admin_url() . '";';
        echo '</script>';
        
    }
    
    public function enqueue_admin_styles() {

        $screen = get_current_screen();
        
        //menu dashboard
        if ( $screen->id == $this->screen_id_dashboard ) {
            wp_enqueue_style( $this->shared->get('slug') .'-framework-menu', $this->shared->get('url') . 'admin/assets/css/framework/menu.css', array(), $this->shared->get('ver') );
            wp_enqueue_style( $this->shared->get('slug') .'-menu-dashboard', $this->shared->get('url') . 'admin/assets/css/menu-dashboard.css', array(), $this->shared->get('ver') );
            wp_enqueue_style( $this->shared->get('slug') .'-jquery-ui-tooltip', $this->shared->get('url') . 'admin/assets/css/jquery-ui-tooltip.css', array(), $this->shared->get('ver') );

	        //Chosen
	        wp_enqueue_style($this->shared->get('slug') . '-chosen',
		        $this->shared->get('url') . 'admin/assets/inc/chosen/chosen-min.css', array(),
		        $this->shared->get('ver'));
	        wp_enqueue_style($this->shared->get('slug') . '-chosen-custom',
		        $this->shared->get('url') . 'admin/assets/css/chosen-custom.css', array(), $this->shared->get('ver'));
        }
        
        //menu juice
        if ( $screen->id == $this->screen_id_juice ) {
            wp_enqueue_style( $this->shared->get('slug') .'-framework-menu', $this->shared->get('url') . 'admin/assets/css/framework/menu.css', array(), $this->shared->get('ver') );
            wp_enqueue_style( $this->shared->get('slug') .'-menu-juice', $this->shared->get('url') . 'admin/assets/css/menu-juice.css', array(), $this->shared->get('ver') );
            wp_enqueue_style( $this->shared->get('slug') .'-jquery-ui-tooltip', $this->shared->get('url') . 'admin/assets/css/jquery-ui-tooltip.css', array(), $this->shared->get('ver') );
        }
        
        //menu hits
        if ( $screen->id == $this->screen_id_hits ) {
            wp_enqueue_style( $this->shared->get('slug') .'-framework-menu', $this->shared->get('url') . 'admin/assets/css/framework/menu.css', array(), $this->shared->get('ver') );
            wp_enqueue_style( $this->shared->get('slug') .'-menu-hits', $this->shared->get('url') . 'admin/assets/css/menu-hits.css', array(), $this->shared->get('ver') );
            wp_enqueue_style( $this->shared->get('slug') .'-jquery-ui-tooltip', $this->shared->get('url') . 'admin/assets/css/jquery-ui-tooltip.css', array(), $this->shared->get('ver') );
        }
        
        //menu autolinks
        if ( $screen->id == $this->screen_id_autolinks ) {
            wp_enqueue_style( $this->shared->get('slug') .'-framework-menu', $this->shared->get('url') . 'admin/assets/css/framework/menu.css', array(), $this->shared->get('ver') );
            wp_enqueue_style( $this->shared->get('slug') .'-menu-autolinks', $this->shared->get('url') . 'admin/assets/css/menu-autolinks.css', array(), $this->shared->get('ver') );
            wp_enqueue_style( $this->shared->get('slug') .'-jquery-ui-tooltip', $this->shared->get('url') . 'admin/assets/css/jquery-ui-tooltip.css', array(), $this->shared->get('ver') );

	        //Chosen
	        wp_enqueue_style($this->shared->get('slug') . '-chosen',
		        $this->shared->get('url') . 'admin/assets/inc/chosen/chosen-min.css', array(),
		        $this->shared->get('ver'));
	        wp_enqueue_style($this->shared->get('slug') . '-chosen-custom',
		        $this->shared->get('url') . 'admin/assets/css/chosen-custom.css', array(), $this->shared->get('ver'));

        }
        
        //menu options
        if ( $screen->id == $this->screen_id_options ) {
            wp_enqueue_style( $this->shared->get('slug') .'-framework-options', $this->shared->get('url') . 'admin/assets/css/framework/options.css', array(), $this->shared->get('ver') );
            wp_enqueue_style( $this->shared->get('slug') .'-jquery-ui-tooltip', $this->shared->get('url') . 'admin/assets/css/jquery-ui-tooltip.css', array(), $this->shared->get('ver') );

	        //Chosen
	        wp_enqueue_style($this->shared->get('slug') . '-chosen',
		        $this->shared->get('url') . 'admin/assets/inc/chosen/chosen-min.css', array(),
		        $this->shared->get('ver'));
	        wp_enqueue_style($this->shared->get('slug') . '-chosen-custom',
		        $this->shared->get('url') . 'admin/assets/css/chosen-custom.css', array(), $this->shared->get('ver'));

        }
        
        /*
         * Load the post editor CSS if at least one of the three meta box is
         * enabled with the current $screen->id 
         */
        $load_post_editor_css = false;
        
        $interlinks_options_post_types = preg_replace('/\s+/', '', get_option( $this->shared->get('slug') . '_interlinks_options_post_types' ));
        $interlinks_options_post_types_a = explode(',', $interlinks_options_post_types);
        if(in_array($screen->id, $interlinks_options_post_types_a)){
            $load_post_editor_css = true;
        }
        
        $interlinks_optimization_post_types = preg_replace('/\s+/', '', get_option( $this->shared->get('slug') . '_interlinks_optimization_post_types' ));
        $interlinks_optimization_post_types_a = explode(',', $interlinks_optimization_post_types);
        if(in_array($screen->id, $interlinks_optimization_post_types_a)){
            $load_post_editor_css = true;
        }
        
        $interlinks_suggestions_post_types = preg_replace('/\s+/', '', get_option( $this->shared->get('slug') . '_interlinks_suggestions_post_types' ));
        $interlinks_suggestions_post_types_a = explode(',', $interlinks_suggestions_post_types);
        if(in_array($screen->id, $interlinks_suggestions_post_types_a)){
            $load_post_editor_css = true;
        }
        
        if($load_post_editor_css){

            //JQuery UI Tooltips
            wp_enqueue_style( $this->shared->get('slug') .'-jquery-ui-tooltip', $this->shared->get('url') . 'admin/assets/css/jquery-ui-tooltip.css', array(), $this->shared->get('ver') );

            //Post Editor CSS
            wp_enqueue_style( $this->shared->get('slug') .'-post-editor', $this->shared->get('url') . 'admin/assets/css/post-editor.css', array(), $this->shared->get('ver') );

	        //Chosen
	        wp_enqueue_style($this->shared->get('slug') . '-chosen',
		        $this->shared->get('url') . 'admin/assets/inc/chosen/chosen-min.css', array(),
		        $this->shared->get('ver'));
	        wp_enqueue_style($this->shared->get('slug') . '-chosen-custom',
		        $this->shared->get('url') . 'admin/assets/css/chosen-custom.css', array(), $this->shared->get('ver'));

        }

    }
    
    /*
     * enqueue admin-specific javascript
     */
    public function enqueue_admin_scripts() {

	    $wp_localize_script_data = array(
		    'chooseAnOptionText' => esc_attr__('Choose an Option ...', 'daim'),
	    );

        $screen = get_current_screen();
        
        //menu dashboard
        if ( $screen->id == $this->screen_id_dashboard ) {
            wp_enqueue_script( $this->shared->get('slug') . '-menu-dashboard', $this->shared->get('url') . 'admin/assets/js/menu-dashboard.js', 'jquery', $this->shared->get('ver') );
            wp_enqueue_script('jquery-ui-tooltip');
            wp_enqueue_script( $this->shared->get('slug') . '-jquery-ui-tooltip-init', $this->shared->get('url') . 'admin/assets/js/jquery-ui-tooltip-init.js', 'jquery', $this->shared->get('ver') );

	        //Chosen
	        wp_enqueue_script($this->shared->get('slug') . '-chosen',
		        $this->shared->get('url') . 'admin/assets/inc/chosen/chosen-min.js', array('jquery'),
		        $this->shared->get('ver'));
	        wp_enqueue_script($this->shared->get('slug') . '-chosen-init',
		        $this->shared->get('url') . 'admin/assets/js/chosen-init.js', array('jquery'),
		        $this->shared->get('ver'));
	        wp_localize_script($this->shared->get('slug') . '-chosen-init', 'objectL10n', $wp_localize_script_data);
        }
        
        //menu juice
        if ( $screen->id == $this->screen_id_juice ) {
            wp_enqueue_script( $this->shared->get('slug') . '-menu-juice', $this->shared->get('url') . 'admin/assets/js/menu-juice.js', 'jquery', $this->shared->get('ver') );
            wp_enqueue_script('jquery-ui-tooltip');
            wp_enqueue_script( $this->shared->get('slug') . '-jquery-ui-tooltip-init', $this->shared->get('url') . 'admin/assets/js/jquery-ui-tooltip-init.js', 'jquery', $this->shared->get('ver') );
        }
        
        //menu anchors
        if ( $screen->id == $this->screen_id_anchors ) {
            wp_enqueue_script('jquery-ui-tooltip');
            wp_enqueue_script( $this->shared->get('slug') . '-jquery-ui-tooltip-init', $this->shared->get('url') . 'admin/assets/js/jquery-ui-tooltip-init.js', 'jquery', $this->shared->get('ver') );
        }
        
        //menu hits
        if ( $screen->id == $this->screen_id_hits ) {
            wp_enqueue_script('jquery-ui-tooltip');
            wp_enqueue_script( $this->shared->get('slug') . '-jquery-ui-tooltip-init', $this->shared->get('url') . 'admin/assets/js/jquery-ui-tooltip-init.js', 'jquery', $this->shared->get('ver') );
        }
        
        //menu autolinks
        if ( $screen->id == $this->screen_id_autolinks ) {
	        wp_enqueue_script( $this->shared->get('slug') . '-menu-autolinks', $this->shared->get('url') . 'admin/assets/js/menu-autolinks.js', 'jquery', $this->shared->get('ver') );
            wp_enqueue_script('jquery-ui-tooltip');
            wp_enqueue_script( $this->shared->get('slug') . '-jquery-ui-tooltip-init', $this->shared->get('url') . 'admin/assets/js/jquery-ui-tooltip-init.js', 'jquery', $this->shared->get('ver') );

	        //Chosen
	        wp_enqueue_script($this->shared->get('slug') . '-chosen',
		        $this->shared->get('url') . 'admin/assets/inc/chosen/chosen-min.js', array('jquery'),
		        $this->shared->get('ver'));
	        wp_enqueue_script($this->shared->get('slug') . '-chosen-init',
		        $this->shared->get('url') . 'admin/assets/js/chosen-init.js', array('jquery'),
		        $this->shared->get('ver'));
	        wp_localize_script($this->shared->get('slug') . '-chosen-init', 'objectL10n', $wp_localize_script_data);

        }
        
        //menu options
        if( $screen->id == $this->screen_id_options ){
            wp_enqueue_script('jquery-ui-tooltip');
            wp_enqueue_script( $this->shared->get('slug') . '-jquery-ui-tooltip-init', $this->shared->get('url') . 'admin/assets/js/jquery-ui-tooltip-init.js', 'jquery', $this->shared->get('ver') );

	        //Chosen
	        wp_enqueue_script($this->shared->get('slug') . '-chosen',
		        $this->shared->get('url') . 'admin/assets/inc/chosen/chosen-min.js', array('jquery'),
		        $this->shared->get('ver'));
	        wp_enqueue_script($this->shared->get('slug') . '-chosen-init',
		        $this->shared->get('url') . 'admin/assets/js/chosen-init.js', array('jquery'),
		        $this->shared->get('ver'));
	        wp_localize_script($this->shared->get('slug') . '-chosen-init', 'objectL10n', $wp_localize_script_data);

        }
        
        /*
         * Load the post editor JS if at least one of the three meta box is
         * enabled with the current $screen->id 
         */
        $load_post_editor_js = false;
        
        $interlinks_options_post_types = preg_replace('/\s+/', '', get_option( $this->shared->get('slug') . '_interlinks_options_post_types' ));
        $interlinks_options_post_types_a = explode(',', $interlinks_options_post_types);
        if(in_array($screen->id, $interlinks_options_post_types_a)){
            $load_post_editor_js = true;
        }
        
        $interlinks_optimization_post_types = preg_replace('/\s+/', '', get_option( $this->shared->get('slug') . '_interlinks_optimization_post_types' ));
        $interlinks_optimization_post_types_a = explode(',', $interlinks_optimization_post_types);
        if(in_array($screen->id, $interlinks_optimization_post_types_a)){
            $load_post_editor_js = true;
        }
        
        $interlinks_suggestions_post_types = preg_replace('/\s+/', '', get_option( $this->shared->get('slug') . '_interlinks_suggestions_post_types' ));
        $interlinks_suggestions_post_types_a = explode(',', $interlinks_suggestions_post_types);
        if(in_array($screen->id, $interlinks_suggestions_post_types_a)){
            $load_post_editor_js = true;
        }
        
        if($load_post_editor_js){

            //JQuery UI Tooltips
	        wp_enqueue_script('jquery-ui-tooltip');
            wp_enqueue_script( $this->shared->get('slug') . '-jquery-ui-tooltip-init', $this->shared->get('url') . 'admin/assets/js/jquery-ui-tooltip-init.js', 'jquery', $this->shared->get('ver') );

            //Post Editor Js
            wp_enqueue_script( $this->shared->get('slug') . '-post-editor', $this->shared->get('url') . 'admin/assets/js/post-editor.js', 'jquery', $this->shared->get('ver') );

	        //Chosen
	        wp_enqueue_script($this->shared->get('slug') . '-chosen',
		        $this->shared->get('url') . 'admin/assets/inc/chosen/chosen-min.js', array('jquery'),
		        $this->shared->get('ver'));
	        wp_enqueue_script($this->shared->get('slug') . '-chosen-init',
		        $this->shared->get('url') . 'admin/assets/js/chosen-init.js', array('jquery'),
		        $this->shared->get('ver'));
	        wp_localize_script($this->shared->get('slug') . '-chosen-init', 'objectL10n', $wp_localize_script_data);

        }

    }
    
    /*
     * plugin activation
     */
    public function ac_activate($networkwide){
        
        /*
         * delete options and tables for all the sites in the network
         */
        if(function_exists('is_multisite') and is_multisite()) {

            /*
             * if this is a "Network Activation" create the options and tables
             * for each blog
             */
            if ($networkwide) {
            
                //get the current blog id
                global $wpdb;
                $current_blog = $wpdb->blogid;

                //create an array with all the blog ids
                $blogids = $wpdb->get_col("SELECT blog_id FROM $wpdb->blogs");

                //iterate through all the blogs
                foreach ($blogids as $blog_id){

                    //swith to the iterated blog
                    switch_to_blog($blog_id);

                    //create options and tables for the iterated blog
                    $this->ac_initialize_options();
                    $this->ac_create_database_tables();

                }

                //switch to the current blog
                switch_to_blog($current_blog);
                
            }else{
                
                /*
                 * if this is not a "Network Activation" create options and
                 * tables only for the current blog
                 */
                $this->ac_initialize_options();
                $this->ac_create_database_tables();
                
            }

        }else{

            /*
             * if this is not a multisite installation create options and
             * tables only for the current blog
             */
            $this->ac_initialize_options();
            $this->ac_create_database_tables();

        }
        
    }

    //create the options and tables for the newly created blog
    public function new_blog_create_options_and_tables($blog_id, $user_id, $domain, $path, $site_id, $meta ) {

        global $wpdb;

        /*
         * if the plugin is "Network Active" create the options and tables for
         * this new blog
         */
        if(is_plugin_active_for_network('interlinks-manager/init.php')){

            //get the id of the current blog
            $current_blog = $wpdb->blogid;

            //switch to the blog that is being activated
            switch_to_blog($blog_id);

            //create options and database tables for the new blog
            $this->ac_initialize_options();
            $this->ac_create_database_tables();

            //switch to the current blog
            switch_to_blog($current_blog);

        }

    }

    //delete options and tables for the deleted blog
    public function delete_blog_delete_options_and_tables($blog_id){

        global $wpdb;
        
        //get the id of the current blog
        $current_blog = $wpdb->blogid;

        //switch to the blog that is being activated
        switch_to_blog($blog_id);

        //create options and database tables for the new blog
        $this->un_delete_options();
        $this->un_delete_database_tables();

        //switch to the current blog
        switch_to_blog($current_blog);

    }

    /*
     * initialize plugin options
     */
    private function ac_initialize_options(){

        //database version -----------------------------------------------------
        add_option( $this->shared->get('slug') . "_database_version","0");
        
        //AIL ------------------------------------------------------------------
        add_option( $this->shared->get('slug') . '_default_open_new_tab', "0");
	    add_option( $this->shared->get('slug') . '_default_use_nofollow', "0");
	    add_option( $this->shared->get('slug') . '_default_activate_post_types', "post, page");
	    add_option( $this->shared->get('slug') . '_default_case_insensitive_search', "0");
	    add_option( $this->shared->get('slug') . '_default_string_before', "1");
	    add_option( $this->shared->get('slug') . '_default_string_after', "1");
        add_option( $this->shared->get('slug') . '_default_max_number_autolinks_per_keyword', "100");
        add_option( $this->shared->get('slug') . '_default_priority', "0");
        
        //suggestions
        add_option( $this->shared->get('slug') . '_suggestions_pool_post_types', "post, page");
        add_option( $this->shared->get('slug') . '_suggestions_pool_size', 50);
        add_option( $this->shared->get('slug') . '_suggestions_titles', "consider");
        add_option( $this->shared->get('slug') . '_suggestions_categories', "consider");
        add_option( $this->shared->get('slug') . '_suggestions_tags', "consider");
        add_option( $this->shared->get('slug') . '_suggestions_post_type', "consider");
        
        //optimization ---------------------------------------------------------
        add_option( $this->shared->get('slug') . '_optimization_num_of_characters', 1000);
        add_option( $this->shared->get('slug') . '_optimization_delta', 2);
        
        //juice ----------------------------------------------------------------
        add_option( $this->shared->get('slug') . '_default_seo_power', 1000);
        add_option( $this->shared->get('slug') . '_penality_per_position_percentage', '1');
        add_option( $this->shared->get('slug') . '_remove_link_to_anchor', "1");
        add_option( $this->shared->get('slug') . '_remove_url_parameters', "0");
        
        //tracking -------------------------------------------------------------
        add_option( $this->shared->get('slug') . '_track_internal_links', "1");
        
        //analysis ----------------------------------------------------------
        add_option( $this->shared->get('slug') . '_set_max_execution_time', "1");
        add_option( $this->shared->get('slug') . '_max_execution_time_value', "300");
        add_option( $this->shared->get('slug') . '_set_memory_limit', "0");
        add_option( $this->shared->get('slug') . '_memory_limit_value', "512");
        add_option( $this->shared->get('slug') . '_limit_posts_analysis', "1000");
        add_option( $this->shared->get('slug') . '_dashboard_post_types', "post, page");
        add_option( $this->shared->get('slug') . '_juice_post_types', "post, page");
        
        //meta boxes -----------------------------------------------------------
        add_option( $this->shared->get('slug') . '_interlinks_options_post_types', "post, page");
        add_option( $this->shared->get('slug') . '_interlinks_optimization_post_types', "post, page");
        add_option( $this->shared->get('slug') . '_interlinks_suggestions_post_types', "post, page");
        
        //capabilities ----------------------------------------------------------
        add_option( $this->shared->get('slug') . '_dashboard_menu_required_capability', "edit_others_posts");
        add_option( $this->shared->get('slug') . '_juice_menu_required_capability', "edit_others_posts");
        add_option( $this->shared->get('slug') . '_hits_menu_required_capability', "edit_others_posts");
        add_option( $this->shared->get('slug') . '_ail_menu_required_capability', "edit_others_posts");
        add_option( $this->shared->get('slug') . '_interlinks_options_mb_required_capability', "edit_others_posts");
        add_option( $this->shared->get('slug') . '_interlinks_optimization_mb_required_capability', "edit_posts");
        add_option( $this->shared->get('slug') . '_interlinks_suggestions_mb_required_capability', "edit_posts");

        //Advanced
        add_option( $this->shared->get('slug') . '_default_enable_ail_on_post', "1");
        add_option( $this->shared->get('slug') . '_filter_priority', "1");
	    add_option( $this->shared->get('slug') . '_ail_test_mode', "0");
	    add_option( $this->shared->get('slug') . '_random_prioritization', "0");
	    add_option( $this->shared->get('slug') . '_ignore_self_ail', "1");
	    add_option( $this->shared->get('slug') . '_general_limit_mode', "1");
	    add_option( $this->shared->get('slug') . '_characters_per_autolink', "200");
	    add_option( $this->shared->get('slug') . '_max_number_autolinks_per_post', "100");
	    add_option( $this->shared->get('slug') . '_same_url_limit', "100");

        //By default the following HTML tags are protected:
	    $protected_tags = array(
		    'h1',
		    'h2',
		    'h3',
		    'h4',
		    'h5',
		    'h6',
		    'a',
		    'img',
		    'ul',
		    'ol',
		    'span',
		    'pre',
		    'code',
		    'table',
		    'iframe',
		    'script'
	    );
	    add_option($this->shared->get('slug') . '_protected_tags', $protected_tags);

	    /*
         * By default all the Gutenberg Blocks except the following are protected:
         *
         * - Paragraph
         * - List
         * - Text Columns
         */
	    $default_protected_gutenberg_blocks = array(
		    //'paragraph',
		    'image',
		    'heading',
		    'gallery',
		    //'list',
		    'quote',
		    'audio',
		    'cover-image',
		    'subhead',
		    'video',
		    'code',
		    'html',
		    'preformatted',
		    'pullquote',
		    'table',
		    'verse',
		    'button',
		    'columns',
		    'more',
		    'nextpage',
		    'separator',
		    'spacer',
		    //'text-columns',
		    'shortcode',
		    'categories',
		    'latest-posts',
		    'embed',
		    'core-embed/twitter',
		    'core-embed/youtube',
		    'core-embed/facebook',
		    'core-embed/instagram',
		    'core-embed/wordpress',
		    'core-embed/soundcloud',
		    'core-embed/spotify',
		    'core-embed/flickr',
		    'core-embed/vimeo',
		    'core-embed/animoto',
		    'core-embed/cloudup',
		    'core-embed/collegehumor',
		    'core-embed/dailymotion',
		    'core-embed/funnyordie',
		    'core-embed/hulu',
		    'core-embed/imgur',
		    'core-embed/issuu',
		    'core-embed/kickstarter',
		    'core-embed/meetup-com',
		    'core-embed/mixcloud',
		    'core-embed/photobucket',
		    'core-embed/polldaddy',
		    'core-embed/reddit',
		    'core-embed/reverbnation',
		    'core-embed/screencast',
		    'core-embed/scribd',
		    'core-embed/slideshare',
		    'core-embed/smugmug',
		    'core-embed/speaker',
		    'core-embed/ted',
		    'core-embed/tumblr',
		    'core-embed/videopress',
		    'core-embed/wordpress-tv'
	    );
	    add_option($this->shared->get('slug') . '_protected_gutenberg_blocks',
		    $default_protected_gutenberg_blocks);
	    add_option($this->shared->get('slug') . '_protected_gutenberg_custom_blocks', '');
	    add_option($this->shared->get('slug') . '_protected_gutenberg_custom_void_blocks', '');
	    add_option( $this->shared->get('slug') . '_pagination_dashboard_menu', "10");
	    add_option( $this->shared->get('slug') . '_pagination_juice_menu', "10");
	    add_option( $this->shared->get('slug') . '_pagination_hits_menu', "10");
	    add_option( $this->shared->get('slug') . '_pagination_ail_menu', "10");

    }

    /*
     * create the plugin database tables
     */
    private function ac_create_database_tables(){

        //check database version and create the database
        if( intval(get_option( $this->shared->get('slug') . '_database_version'), 10) < 2 ){

            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

            //create *prefix*_archive
            global $wpdb;
            $table_name = $wpdb->prefix . $this->shared->get('slug') . "_archive";
            $sql = "CREATE TABLE $table_name (
                id bigint(20) NOT NULL AUTO_INCREMENT PRIMARY KEY,
                post_id bigint(20) NOT NULL DEFAULT '0',
                post_title text NOT NULL DEFAULT '',
                post_type varchar(20) NOT NULL DEFAULT '',
                post_date datetime DEFAULT NULL,
                manual_interlinks bigint(20) NOT NULL DEFAULT '0',
                auto_interlinks bigint(20) NOT NULL DEFAULT '0',
                content_length bigint(20) NOT NULL DEFAULT '0',
                recommended_interlinks bigint(20) NOT NULL DEFAULT '0',
                optimization tinyint(1) NOT NULL DEFAULT '0'
            )
            COLLATE = utf8_general_ci
            ";

            dbDelta($sql);
            
            //create *prefix*_juice
            global $wpdb;
            $table_name = $wpdb->prefix . $this->shared->get('slug') . "_juice";
            $sql = "CREATE TABLE $table_name (
                id bigint(20) NOT NULL AUTO_INCREMENT PRIMARY KEY,
                url varchar(2083) NOT NULL DEFAULT '',
                iil bigint(20) NOT NULL DEFAULT '0',
                juice bigint(20) NOT NULL DEFAULT '0',
                juice_relative bigint(20) NOT NULL DEFAULT '0'
            )
            COLLATE = utf8_general_ci
            ";

            dbDelta($sql);
            
            //create *prefix*_anchors
            global $wpdb;
            $table_name = $wpdb->prefix . $this->shared->get('slug') . "_anchors";
            $sql = "CREATE TABLE $table_name (
                id bigint(20) NOT NULL AUTO_INCREMENT PRIMARY KEY,
                url varchar(2083) NOT NULL DEFAULT '',
                anchor longtext NOT NULL DEFAULT '',
                post_id bigint(20) NOT NULL DEFAULT '0',
                post_title text NOT NULL DEFAULT '',
                juice bigint(20) NOT NULL DEFAULT '0'
            )
            COLLATE = utf8_general_ci
            ";

            dbDelta($sql);
            
            //create *prefix*_hits
            global $wpdb;
            $table_name = $wpdb->prefix . $this->shared->get('slug') . "_hits";
            $sql = "CREATE TABLE $table_name (
                id bigint(20) NOT NULL AUTO_INCREMENT PRIMARY KEY,
                source_post_id bigint(20) NOT NULL DEFAULT '0',
                post_title text NOT NULL DEFAULT '',
                target_url varchar(2083) NOT NULL DEFAULT '',
                date datetime DEFAULT NULL,
                date_gmt datetime DEFAULT NULL,
                link_type tinyint(1) NOT NULL DEFAULT '0'
            )
            COLLATE = utf8_general_ci
            ";

            dbDelta($sql);
            
            //create *prefix*_autolinks
            global $wpdb;
            $table_name = $wpdb->prefix . $this->shared->get('slug') . "_autolinks";
            $sql = "CREATE TABLE $table_name (
                id bigint(20) NOT NULL AUTO_INCREMENT PRIMARY KEY,
                keyword varchar(255) NOT NULL DEFAULT '',
                url varchar(2083) NOT NULL DEFAULT '',
                title varchar(1024) NOT NULL DEFAULT '',
                string_before int(11) NOT NULL DEFAULT '1',
                string_after int(11) NOT NULL DEFAULT '1',
                activate_post_types varchar(1000) NOT NULL DEFAULT '',
                max_number_autolinks int(11) NOT NULL DEFAULT '0',
                case_insensitive_search tinyint(1) NOT NULL DEFAULT '0',
                open_new_tab tinyint(1) NOT NULL DEFAULT '0',
                use_nofollow tinyint(1) NOT NULL DEFAULT '0',
                priority int(11) NOT NULL DEFAULT '0'
            )
            COLLATE = utf8_general_ci
            ";

            dbDelta($sql);

            //Update database version
            update_option( $this->shared->get('slug') . '_database_version',"2");

        }

    }

    /*
     * plugin delete
     */
    static public function un_delete(){
        
        /*
         * delete options and tables for all the sites in the network
         */
        if(function_exists('is_multisite') and is_multisite()) {

            //get the current blog id
            global $wpdb;
            $current_blog = $wpdb->blogid;

            //create an array with all the blog ids
            $blogids = $wpdb->get_col("SELECT blog_id FROM $wpdb->blogs");

            //iterate through all the blogs
            foreach ($blogids as $blog_id){

                //swith to the iterated blog
                switch_to_blog($blog_id);

                //create options and tables for the iterated blog
                Daim_Admin::un_delete_options();
                Daim_Admin::un_delete_database_tables();

            }

            //switch to the current blog
            switch_to_blog($current_blog);

        }else{

            /*
             * if this is not a multisite installation delete options and
             * tables only for the current blog
             */
            Daim_Admin::un_delete_options();
            Daim_Admin::un_delete_database_tables();

        }
        
    }

    /*
     * delete plugin options
     */
    static public function un_delete_options(){
        
        //assign an instance of Daim_Shared
        $shared = Daim_Shared::get_instance();
        
        //database version -----------------------------------------------------
        delete_option( $shared->get('slug') . "_database_version","0");
        
        //AIL ------------------------------------------------------------------
	    delete_option( $shared->get('slug') . '_default_open_new_tab');
	    delete_option( $shared->get('slug') . '_default_use_nofollow');
	    delete_option( $shared->get('slug') . '_default_activate_post_types');
	    delete_option( $shared->get('slug') . '_default_case_insensitive_search');
        delete_option( $shared->get('slug') . '_default_string_before');
	    delete_option( $shared->get('slug') . '_default_string_after');
        delete_option( $shared->get('slug') . '_default_max_number_autolinks_per_keyword');
        delete_option( $shared->get('slug') . '_default_priority');
        
        //suggestions
        delete_option( $shared->get('slug') . '_suggestions_pool_post_types');
        delete_option( $shared->get('slug') . '_suggestions_pool_size');
        delete_option( $shared->get('slug') . '_suggestions_titles');
        delete_option( $shared->get('slug') . '_suggestions_categories');
        delete_option( $shared->get('slug') . '_suggestions_tags');
        delete_option( $shared->get('slug') . '_suggestions_post_type');
        
        //optimization ---------------------------------------------------------
        delete_option( $shared->get('slug') . '_optimization_num_of_characters');
        delete_option( $shared->get('slug') . '_optimization_delta');
        
        //juice ----------------------------------------------------------------
        delete_option( $shared->get('slug') . '_default_seo_power');
        delete_option( $shared->get('slug') . '_penality_per_position_percentage');
        delete_option( $shared->get('slug') . '_remove_link_to_anchor');
        delete_option( $shared->get('slug') . '_remove_url_parameters');
        
        //tracking -------------------------------------------------------------
        delete_option( $shared->get('slug') . '_track_internal_links');
        
        //analysis -------------------------------------------------------------
        delete_option( $shared->get('slug') . '_set_max_execution_time');
        delete_option( $shared->get('slug') . '_max_execution_time_value');
        delete_option( $shared->get('slug') . '_set_memory_limit');
        delete_option( $shared->get('slug') . '_memory_limit_value');
        delete_option( $shared->get('slug') . '_limit_posts_analysis');
        delete_option( $shared->get('slug') . '_dashboard_post_types');
        delete_option( $shared->get('slug') . '_juice_post_types');
        
        //meta boxes -----------------------------------------------------------
        delete_option( $shared->get('slug') . '_interlinks_options_post_types');
        delete_option( $shared->get('slug') . '_interlinks_optimization_post_types');
        delete_option( $shared->get('slug') . '_interlinks_suggestions_post_types');

        //capabilities ----------------------------------------------------------
        delete_option( $shared->get('slug') . '_dashboard_menu_required_capability');
        delete_option( $shared->get('slug') . '_juice_menu_required_capability');
        delete_option( $shared->get('slug') . '_hits_menu_required_capability');
        delete_option( $shared->get('slug') . '_ail_menu_required_capability');
        delete_option( $shared->get('slug') . '_interlinks_options_mb_required_capability');
        delete_option( $shared->get('slug') . '_interlinks_optimization_mb_required_capability');
        delete_option( $shared->get('slug') . '_interlinks_suggestions_mb_required_capability');

        //advanced -----------------------------------------------------------------------------------------------------
	    delete_option( $shared->get('slug') . '_default_enable_ail_on_post');
        delete_option( $shared->get('slug') . '_filter_priority');
	    delete_option( $shared->get('slug') . '_ail_test_mode');
	    delete_option( $shared->get('slug') . '_random_prioritization');
	    delete_option( $shared->get('slug') . '_ignore_self_ail');
	    delete_option( $shared->get('slug') . '_general_limit_mode');
	    delete_option( $shared->get('slug') . '_characters_per_autolink');
	    delete_option( $shared->get('slug') . '_max_number_autolinks_per_post');
	    delete_option( $shared->get('slug') . '_same_url_limit');
	    delete_option( $shared->get('slug') . '_protected_tags');
	    delete_option( $shared->get('slug') . '_protected_gutenberg_blocks');
	    delete_option( $shared->get('slug') . '_protected_gutenberg_custom_blocks');
	    delete_option( $shared->get('slug') . '_protected_gutenberg_custom_void_blocks');
	    delete_option( $shared->get('slug') . '_pagination_dashboard_menu');
	    delete_option( $shared->get('slug') . '_pagination_juice_menu');
	    delete_option( $shared->get('slug') . '_pagination_hits_menu');
	    delete_option( $shared->get('slug') . '_pagination_ail_menu');

    }

    /*
     * delete plugin database tables
     */
    static public function un_delete_database_tables(){

        //assign an instance of Daim_Shared
        $shared = Daim_Shared::get_instance();

        global $wpdb;
        
        $table_name = $wpdb->prefix . $shared->get('slug') . "_archive";
        $sql = "DROP TABLE $table_name";  
        $wpdb->query($sql);

        $table_name = $wpdb->prefix . $shared->get('slug') . "_juice";
        $sql = "DROP TABLE $table_name";  
        $wpdb->query($sql);
        
        $table_name = $wpdb->prefix . $shared->get('slug') . "_anchors";
        $sql = "DROP TABLE $table_name";  
        $wpdb->query($sql);
        
        $table_name = $wpdb->prefix . $shared->get('slug') . "_hits";
        $sql = "DROP TABLE $table_name";  
        $wpdb->query($sql);
        
        $table_name = $wpdb->prefix . $shared->get('slug') . "_autolinks";
        $sql = "DROP TABLE $table_name";  
        $wpdb->query($sql);

    }

    /*
     * register the admin menu
     */
    public function me_add_admin_menu() {
        
        add_menu_page(
	        esc_attr__('IM', 'daim'),
            esc_attr__('Interlinks', 'daim'),
            get_option( $this->shared->get('slug') . "_dashboard_menu_required_capability"),
            $this->shared->get('slug') . '-dashboard',
            array( $this, 'me_display_menu_dashboard'),
            'dashicons-admin-links'
        );

        $this->screen_id_dashboard = add_submenu_page(
            $this->shared->get('slug') . '-dashboard',
	        esc_attr__('IM - Dashboard', 'daim'),
            esc_attr__('Dashboard', 'daim'),
            get_option( $this->shared->get('slug') . '_dashboard_menu_required_capability'),
            $this->shared->get('slug') . '-dashboard',
            array( $this, 'me_display_menu_dashboard')
        );
        
        $this->screen_id_juice = add_submenu_page(
            $this->shared->get('slug') . '-dashboard',
	        esc_attr__('IM - Juice', 'daim'),
            esc_attr__('Juice', 'daim'),
            get_option( $this->shared->get('slug') . "_juice_menu_required_capability"),
            $this->shared->get('slug') . '-juice',
            array( $this, 'me_display_menu_juice')
        );
        
        $this->screen_id_hits = add_submenu_page(
            $this->shared->get('slug') . '-dashboard',
	        esc_attr__('IM - Hits', 'daim'),
            esc_attr__('Hits', 'daim'),
            get_option( $this->shared->get('slug') . "_hits_menu_required_capability"),
            $this->shared->get('slug') . '-hits',
            array( $this, 'me_display_menu_hits')
        );
        
        $this->screen_id_autolinks = add_submenu_page(
            $this->shared->get('slug') . '-dashboard',
	        esc_attr__('IM - AIL', 'daim'),
            esc_attr__('AIL', 'daim'),
            get_option( $this->shared->get('slug') . "_ail_menu_required_capability"),
            $this->shared->get('slug') . '-autolinks',
            array( $this, 'me_display_menu_autolinks')
        );
        
        $this->screen_id_options = add_submenu_page(
            $this->shared->get('slug') . '-dashboard',
	        esc_attr__('IM - Options', 'daim'),
            esc_attr__('Options', 'daim'),
            'manage_options',
            $this->shared->get('slug') . '-options',
            array( $this, 'me_display_menu_options')
        );
        
    }
    
    /*
     * includes the dashboard view
     */
    public function me_display_menu_dashboard() {
        include_once( 'view/dashboard.php' );
    }
    
    /*
     * includes the juice view
     */
    public function me_display_menu_juice() {
        include_once( 'view/juice.php' );
    }
    
    /*
     * includes the anchors view
     */
    public function me_display_menu_anchors() {
        include_once( 'view/anchors.php' );
    }
    
    /*
     * includes the hits view
     */
    public function me_display_menu_hits() {
        include_once( 'view/hits.php' );
    }
    
    /*
     * includes the autolinks view
     */
    public function me_display_menu_autolinks() {
        include_once( 'view/autolinks.php' );
    }
    
    /*
     * includes the options view
     */
    public function me_display_menu_options() {
        include_once( 'view/options.php' );
    }

    /*
     * register options
     */
    public function op_register_options() {

        //section ail ----------------------------------------------------------
        add_settings_section(
            'daim_ail_settings_section',
            NULL,
            NULL,
            'daim_ail_options'
        );

	    add_settings_field(
		    'default_open_new_tab',
		    esc_attr__('Open New Tab', 'daim'),
		    array($this,'default_open_new_tab_callback'),
		    'daim_ail_options',
		    'daim_ail_settings_section'
	    );

	    register_setting(
		    'daim_ail_options',
		    'daim_default_open_new_tab',
		    array($this,'default_open_new_tab_validation')
	    );

	    add_settings_field(
		    'default_use_nofollow',
		    esc_attr__('Use Nofollow', 'daim'),
		    array($this,'default_use_nofollow_callback'),
		    'daim_ail_options',
		    'daim_ail_settings_section'
	    );

	    register_setting(
		    'daim_ail_options',
		    'daim_default_use_nofollow',
		    array($this,'default_use_nofollow_validation')
	    );

	    add_settings_field(
		    'default_activate_post_types',
		    esc_attr__('Post Types', 'daim'),
		    array($this,'default_activate_post_types_callback'),
		    'daim_ail_options',
		    'daim_ail_settings_section'
	    );

	    register_setting(
		    'daim_ail_options',
		    'daim_default_activate_post_types',
		    array($this,'default_activate_post_types_validation')
	    );

	    add_settings_field(
		    'default_case_insensitive_search',
		    esc_attr__('Case Insensitive Search', 'daim'),
		    array($this,'default_case_insensitive_search_callback'),
		    'daim_ail_options',
		    'daim_ail_settings_section'
	    );

	    register_setting(
		    'daim_ail_options',
		    'daim_default_case_insensitive_search',
		    array($this,'default_case_insensitive_search_validation')
	    );

	    add_settings_field(
		    'default_string_before',
		    esc_attr__('Left Boundary', 'daim'),
		    array($this,'default_string_before_callback'),
		    'daim_ail_options',
		    'daim_ail_settings_section'
	    );

	    register_setting(
		    'daim_ail_options',
		    'daim_default_string_before',
		    array($this,'default_string_before_validation')
	    );

	    add_settings_field(
		    'default_string_after',
		    esc_attr__('Right Boundary', 'daim'),
		    array($this,'default_string_after_callback'),
		    'daim_ail_options',
		    'daim_ail_settings_section'
	    );

	    register_setting(
		    'daim_ail_options',
		    'daim_default_string_after',
		    array($this,'default_string_after_validation')
	    );

	    add_settings_field(
		    'default_max_number_autolinks_per_keyword',
		    esc_attr__('Limit', 'daim'),
		    array($this,'default_max_number_autolinks_per_keyword_callback'),
		    'daim_ail_options',
		    'daim_ail_settings_section'
	    );

	    register_setting(
		    'daim_ail_options',
		    'daim_default_max_number_autolinks_per_keyword',
		    array($this,'default_max_number_autolinks_per_keyword_validation')
	    );

        add_settings_field(
            'default_priority',
            esc_attr__('Priority', 'daim'),
            array($this,'default_priority_callback'),
            'daim_ail_options',
            'daim_ail_settings_section'
        );

        register_setting(
            'daim_ail_options',
            'daim_default_priority',
            array($this,'default_priority_validation')
        );
        
        //section suggestions --------------------------------------------------
        add_settings_section(
            'daim_suggestions_settings_section',
            NULL,
            NULL,
            'daim_suggestions_options'
        );

        add_settings_field(
            'suggestions_pool_post_types',
            esc_attr__('Source Post Types', 'daim'),
            array($this,'suggestions_pool_post_types_callback'),
            'daim_suggestions_options',
            'daim_suggestions_settings_section'
        );

        register_setting(
            'daim_suggestions_options',
            'daim_suggestions_pool_post_types',
            array($this,'suggestions_pool_post_types_validation')
        );
        
        add_settings_field(
            'suggestions_pool_size',
            esc_attr__('Results Pool Size', 'daim'),
            array($this,'suggestions_pool_size_callback'),
            'daim_suggestions_options',
            'daim_suggestions_settings_section'
        );

        register_setting(
            'daim_suggestions_options',
            'daim_suggestions_pool_size',
            array($this,'suggestions_pool_size_validation')
        );
        
        add_settings_field(
            'suggestions_titles',
            esc_attr__('Titles', 'daim'),
            array($this,'suggestions_titles_callback'),
            'daim_suggestions_options',
            'daim_suggestions_settings_section'
        );

        register_setting(
            'daim_suggestions_options',
            'daim_suggestions_titles',
            array($this,'suggestions_titles_validation')
        );
        
        add_settings_field(
            'suggestions_categories',
            esc_attr__('Categories', 'daim'),
            array($this,'suggestions_categories_callback'),
            'daim_suggestions_options',
            'daim_suggestions_settings_section'
        );

        register_setting(
            'daim_suggestions_options',
            'daim_suggestions_categories',
            array($this,'suggestions_categories_validation')
        );
        
        add_settings_field(
            'suggestions_tags',
            esc_attr__('Tags', 'daim'),
            array($this,'suggestions_tags_callback'),
            'daim_suggestions_options',
            'daim_suggestions_settings_section'
        );

        register_setting(
            'daim_suggestions_options',
            'daim_suggestions_tags',
            array($this,'suggestions_categories_validation')
        );
        
        add_settings_field(
            'suggestions_post_type',
            esc_attr__('Post Type', 'daim'),
            array($this,'suggestions_post_type_callback'),
            'daim_suggestions_options',
            'daim_suggestions_settings_section'
        );

        register_setting(
            'daim_suggestions_options',
            'daim_suggestions_post_type',
            array($this,'suggestions_categories_validation')
        );
        
        //section optimization -------------------------------------------------
        add_settings_section(
            'daim_optimization_settings_section',
            NULL,
            NULL,
            'daim_optimization_options'
        );
        
        add_settings_field(
            'optimization_num_of_characters',
            esc_attr__('Characters per Interlink', 'daim'),
            array($this,'optimization_num_of_characters_callback'),
            'daim_optimization_options',
            'daim_optimization_settings_section'
        );

        register_setting(
            'daim_optimization_options',
            'daim_optimization_num_of_characters',
            array($this,'optimization_num_of_characters_validation')
        );
        
        add_settings_field(
            'optimization_delta',
            esc_attr__('Optimization Delta', 'daim'),
            array($this,'optimization_delta_callback'),
            'daim_optimization_options',
            'daim_optimization_settings_section'
        );

        register_setting(
            'daim_optimization_options',
            'daim_optimization_delta',
            array($this,'optimization_delta_validation')
        );
        
        //section juice --------------------------------------------------------
        add_settings_section(
            'daim_juice_settings_section',
            NULL,
            NULL,
            'daim_juice_options'
        );

        add_settings_field(
            'default_seo_power',
            esc_attr__('SEO Power (Default)', 'daim'),
            array($this,'default_seo_power_callback'),
            'daim_juice_options',
            'daim_juice_settings_section'
        );

        register_setting(
            'daim_juice_options',
            'daim_default_seo_power',
            array($this,'default_seo_power_validation')
        );
        
        add_settings_field(
            'penality_per_position_percentage',
            esc_attr__('Penality per Position (%)', 'daim'),
            array($this,'penality_per_position_percentage_callback'),
            'daim_juice_options',
            'daim_juice_settings_section'
        );

        register_setting(
            'daim_juice_options',
            'daim_penality_per_position_percentage',
            array($this,'penality_per_position_percentage_validation')
        );
        
        add_settings_field(
            'remove_link_to_anchor',
            esc_attr__('Remove Link to Anchor', 'daim'),
            array($this,'remove_link_to_anchor_callback'),
            'daim_juice_options',
            'daim_juice_settings_section'
        );

        register_setting(
            'daim_juice_options',
            'daim_remove_link_to_anchor',
            array($this,'remove_link_to_anchor_validation')
        );
        
        add_settings_field(
            'remove_url_parameters',
            esc_attr__('Remove URL Parameters', 'daim'),
            array($this,'remove_url_parameters_callback'),
            'daim_juice_options',
            'daim_juice_settings_section'
        );

        register_setting(
            'daim_juice_options',
            'daim_remove_url_parameters',
            array($this,'remove_url_parameters_validation')
        );
        
        //section tracking -----------------------------------------------------
        add_settings_section(
            'daim_tracking_settings_section',
            NULL,
            NULL,
            'daim_tracking_options'
        );

        add_settings_field(
            'track_internal_links',
            esc_attr__('Track Internal Links', 'daim'),
            array($this,'track_internal_links_callback'),
            'daim_tracking_options',
            'daim_tracking_settings_section'
        );

        register_setting(
            'daim_tracking_options',
            'daim_track_internal_links',
            array($this,'track_internal_links_validation')
        );
        
        //section analysis --------------------------------------------------
        add_settings_section(
            'daim_analysis_settings_section',
            NULL,
            NULL,
            'daim_analysis_options'
        );
        
        add_settings_field(
            'set_max_execution_time',
            esc_attr__('Set Max Execution Time', 'daim'),
            array($this,'set_max_execution_time_callback'),
            'daim_analysis_options',
            'daim_analysis_settings_section'
        );
        
        register_setting(
            'daim_analysis_options',
            'daim_set_max_execution_time',
            array($this,'set_max_execution_time_validation')
        );
        
        add_settings_field(
            'max_execution_time_value',
            esc_attr__('Max Execution Time Value', 'daim'),
            array($this,'max_execution_time_value_callback'),
            'daim_analysis_options',
            'daim_analysis_settings_section'
        );
        
        register_setting(
            'daim_analysis_options',
            'daim_max_execution_time_value',
            array($this,'max_execution_time_value_validation')
        );
        
        add_settings_field(
            'set_memory_limit',
            esc_attr__('Set Memory Limit', 'daim'),
            array($this,'set_memory_limit_callback'),
            'daim_analysis_options',
            'daim_analysis_settings_section'
        );
        
        register_setting(
            'daim_analysis_options',
            'daim_set_memory_limit',
            array($this,'set_memory_limit_validation')
        );
        
        add_settings_field(
            'memory_limit_value',
            esc_attr__('Memory Limit Value', 'daim'),
            array($this,'memory_limit_value_callback'),
            'daim_analysis_options',
            'daim_analysis_settings_section'
        );
        
        register_setting(
            'daim_analysis_options',
            'daim_memory_limit_value',
            array($this,'memory_limit_value_validation')
        );
        
        add_settings_field(
            'limit_posts_analysis',
            esc_attr__('Limit Posts Analysis', 'daim'),
            array($this,'limit_posts_analysis_callback'),
            'daim_analysis_options',
            'daim_analysis_settings_section'
        );

        register_setting(
            'daim_analysis_options',
            'daim_limit_posts_analysis',
            array($this,'limit_posts_analysis_validation')
        );
        
        add_settings_field(
            'dashboard_post_types',
            esc_attr__('Dashboard Post Types', 'daim'),
            array($this,'dashboard_post_types_callback'),
            'daim_analysis_options',
            'daim_analysis_settings_section'
        );

        register_setting(
            'daim_analysis_options',
            'daim_dashboard_post_types',
            array($this,'dashboard_post_types_validation')
        );
        
        add_settings_field(
            'juice_post_types',
            esc_attr__('Juice Post Types', 'daim'),
            array($this,'juice_post_types_callback'),
            'daim_analysis_options',
            'daim_analysis_settings_section'
        );

        register_setting(
            'daim_analysis_options',
            'daim_juice_post_types',
            array($this,'juice_post_types_validation')
        );
        
        //meta boxes -----------------------------------------------------------
        add_settings_section(
            'daim_metaboxes_settings_section',
            NULL,
            NULL,
            'daim_metaboxes_options'
        );

        add_settings_field(
            'interlinks_options_post_types',
            esc_attr__('Interlinks Options Post Types', 'daim'),
            array($this,'interlinks_options_post_types_callback'),
            'daim_metaboxes_options',
            'daim_metaboxes_settings_section'
        );

        register_setting(
            'daim_metaboxes_options',
            'daim_interlinks_options_post_types',
            array($this,'interlinks_options_post_types_validation')
        );
        
        add_settings_field(
            'interlinks_optimization_post_types',
            esc_attr__('Interlinks Optimization Post Types', 'daim'),
            array($this,'interlinks_optimization_post_types_callback'),
            'daim_metaboxes_options',
            'daim_metaboxes_settings_section'
        );

        register_setting(
            'daim_metaboxes_options',
            'daim_interlinks_optimization_post_types',
            array($this,'interlinks_optimization_post_types_validation')
        );
        
        add_settings_field(
            'interlinks_suggestions_post_types',
            esc_attr__('Interlinks Suggestions Post Types', 'daim'),
            array($this,'interlinks_suggestions_post_types_callback'),
            'daim_metaboxes_options',
            'daim_metaboxes_settings_section'
        );

        register_setting(
            'daim_metaboxes_options',
            'daim_interlinks_suggestions_post_types',
            array($this,'interlinks_suggestions_post_types_validation')
        );
        
        //capabilities ----------------------------------------------------------
        add_settings_section(
            'daim_capabilities_settings_section',
            NULL,
            NULL,
            'daim_capabilities_options'
        );
        
        add_settings_field(
            'dashboard_menu_required_capability',
            esc_attr__('Dashboard Menu', 'daim'),
            array($this,'dashboard_menu_required_capability_callback'),
            'daim_capabilities_options',
            'daim_capabilities_settings_section'
        );

        register_setting(
            'daim_capabilities_options',
            'daim_dashboard_menu_required_capability',
            array($this,'dashboard_menu_required_capability_validation')
        );
        
        add_settings_field(
            'juice_menu_required_capability',
            esc_attr__('Juice Menu', 'daim'),
            array($this,'juice_menu_required_capability_callback'),
            'daim_capabilities_options',
            'daim_capabilities_settings_section'
        );

        register_setting(
            'daim_capabilities_options',
            'daim_juice_menu_required_capability',
            array($this,'juice_menu_required_capability_validation')
        );
        
        add_settings_field(
            'hits_menu_required_capability',
            esc_attr__('Hits Menu', 'daim'),
            array($this,'hits_menu_required_capability_callback'),
            'daim_capabilities_options',
            'daim_capabilities_settings_section'
        );

        register_setting(
            'daim_capabilities_options',
            'daim_hits_menu_required_capability',
            array($this,'hits_menu_required_capability_validation')
        );
        
        add_settings_field(
            'ail_menu_required_capability',
            esc_attr__('AIL Menu', 'daim'),
            array($this,'ail_menu_required_capability_callback'),
            'daim_capabilities_options',
            'daim_capabilities_settings_section'
        );

        register_setting(
            'daim_capabilities_options',
            'daim_ail_menu_required_capability',
            array($this,'ail_menu_required_capability_validation')
        );
        
        add_settings_field(
            'interlinks_options_mb_required_capability',
            esc_attr__('Interlinks Options Meta Box', 'daim'),
            array($this,'interlinks_options_mb_required_capability_callback'),
            'daim_capabilities_options',
            'daim_capabilities_settings_section'
        );

        register_setting(
            'daim_capabilities_options',
            'daim_interlinks_options_mb_required_capability',
            array($this,'interlinks_options_mb_required_capability_validation')
        );
        
        add_settings_field(
            'interlinks_optimization_mb_required_capability',
            esc_attr__('Interlinks Optimization Meta Box', 'daim'),
            array($this,'interlinks_optimization_mb_required_capability_callback'),
            'daim_capabilities_options',
            'daim_capabilities_settings_section'
        );

        register_setting(
            'daim_capabilities_options',
            'daim_interlinks_optimization_mb_required_capability',
            array($this,'interlinks_optimization_mb_required_capability_validation')
        );
        
        add_settings_field(
            'interlinks_suggestions_mb_required_capability',
            esc_attr__('Interlinks Suggestions Meta Box', 'daim'),
            array($this,'interlinks_suggestions_mb_required_capability_callback'),
            'daim_capabilities_options',
            'daim_capabilities_settings_section'
        );

        register_setting(
            'daim_capabilities_options',
            'daim_interlinks_suggestions_mb_required_capability',
            array($this,'interlinks_suggestions_mb_required_capability_validation')
        );

	    //advanced -----------------------------------------------------------------------------------------------------
	    add_settings_section(
		    'daim_advanced_settings_section',
		    NULL,
		    NULL,
		    'daim_advanced_options'
	    );

	    add_settings_field(
		    'default_enable_ail_on_post',
		    esc_attr__('Enable AIL', 'daim'),
		    array($this,'default_enable_ail_on_post_callback'),
		    'daim_advanced_options',
		    'daim_advanced_settings_section'
	    );

	    register_setting(
		    'daim_advanced_options',
		    'daim_default_enable_ail_on_post',
		    array($this,'default_enable_ail_on_post_validation')
	    );

	    add_settings_field(
		    'filter_priority',
		    esc_attr__('Filter Priority', 'daim'),
		    array($this,'filter_priority_callback'),
		    'daim_advanced_options',
		    'daim_advanced_settings_section'
	    );

	    register_setting(
		    'daim_advanced_options',
		    'daim_filter_priority',
		    array($this,'filter_priority_validation')
	    );

	    add_settings_field(
		    'ail_test_mode',
		    esc_attr__('Test Mode', 'daim'),
		    array($this,'ail_test_mode_callback'),
		    'daim_advanced_options',
		    'daim_advanced_settings_section'
	    );

	    register_setting(
		    'daim_advanced_options',
		    'daim_ail_test_mode',
		    array($this,'ail_test_mode_validation')
	    );

	    add_settings_field(
		    'random_prioritization',
		    esc_attr__('Random Prioritization', 'daim'),
		    array($this, 'random_prioritization_callback'),
		    'daim_advanced_options',
		    'daim_advanced_settings_section'
	    );

	    register_setting(
		    'daim_advanced_options',
		    'daim_random_prioritization',
		    array($this, 'random_prioritization_validation')
	    );

	    add_settings_field(
		    'ignore_self_ail',
		    esc_attr__('Ignore Self AIL', 'daim'),
		    array($this,'ignore_self_ail_callback'),
		    'daim_advanced_options',
		    'daim_advanced_settings_section'
	    );

	    register_setting(
		    'daim_advanced_options',
		    'daim_ignore_self_ail',
		    array($this,'ignore_self_ail_validation')
	    );

	    add_settings_field(
		    'general_limit_mode',
		    esc_attr__('General Limit Mode', 'daim'),
		    array($this,'general_limit_mode_callback'),
		    'daim_advanced_options',
		    'daim_advanced_settings_section'
	    );

	    register_setting(
		    'daim_advanced_options',
		    'daim_general_limit_mode',
		    array($this,'general_limit_mode_validation')
	    );

	    add_settings_field(
		    'characters_per_autolink',
		    esc_attr__('General Limit (Characters per AIL)', 'daim'),
		    array($this,'characters_per_autolink_callback'),
		    'daim_advanced_options',
		    'daim_advanced_settings_section'
	    );

	    register_setting(
		    'daim_advanced_options',
		    'daim_characters_per_autolink',
		    array($this,'characters_per_autolink_validation')
	    );

	    add_settings_field(
		    'max_number_autolinks_per_post',
		    esc_attr__('General Limit (Amount)', 'daim'),
		    array($this,'max_number_autolinks_per_post_callback'),
		    'daim_advanced_options',
		    'daim_advanced_settings_section'
	    );

	    register_setting(
		    'daim_advanced_options',
		    'daim_max_number_autolinks_per_post',
		    array($this,'max_number_autolinks_per_post_validation')
	    );

	    add_settings_field(
		    'same_url_limit',
		    esc_attr__('Same URL Limit', 'daim'),
		    array($this,'same_url_limit_callback'),
		    'daim_advanced_options',
		    'daim_advanced_settings_section'
	    );

	    register_setting(
		    'daim_advanced_options',
		    'daim_same_url_limit',
		    array($this,'same_url_limit_validation')
	    );

	    add_settings_field(
		    'protected_tags',
		    esc_attr__('Protected Tags', 'daim'),
		    array($this,'protected_tags_callback'),
		    'daim_advanced_options',
		    'daim_advanced_settings_section'
	    );

	    register_setting(
		    'daim_advanced_options',
		    'daim_protected_tags',
		    array($this,'protected_tags_validation')
	    );

	    add_settings_field(
		    'protected_gutenberg_blocks',
		    esc_attr__('Protected Gutenberg Blocks', 'daim'),
		    array($this,'protected_gutenberg_blocks_callback'),
		    'daim_advanced_options',
		    'daim_advanced_settings_section'
	    );

	    register_setting(
		    'daim_advanced_options',
		    'daim_protected_gutenberg_blocks',
		    array($this,'protected_gutenberg_blocks_validation')
	    );

	    add_settings_field(
		    'protected_gutenberg_custom_blocks',
		    esc_attr__('Protected Gutenberg Custom Blocks', 'daim'),
		    array($this,'protected_gutenberg_custom_blocks_callback'),
		    'daim_advanced_options',
		    'daim_advanced_settings_section'
	    );

	    register_setting(
		    'daim_advanced_options',
		    'daim_protected_gutenberg_custom_blocks',
		    array($this,'protected_gutenberg_custom_blocks_validation')
	    );

	    add_settings_field(
		    'protected_gutenberg_custom_void_blocks',
		    esc_attr__('Protected Gutenberg Custom Void Blocks', 'daim'),
		    array($this,'protected_gutenberg_custom_void_blocks_callback'),
		    'daim_advanced_options',
		    'daim_advanced_settings_section'
	    );

	    register_setting(
		    'daim_advanced_options',
		    'daim_protected_gutenberg_custom_void_blocks',
		    array($this,'protected_gutenberg_custom_void_blocks_validation')
	    );

	    add_settings_field(
		    'pagination_dashboard_menu',
		    esc_attr__('Pagination Dashboard Menu', 'daim'),
		    array($this,'pagination_dashboard_menu_callback'),
		    'daim_advanced_options',
		    'daim_advanced_settings_section'
	    );

	    register_setting(
		    'daim_advanced_options',
		    'daim_pagination_dashboard_menu',
		    array($this,'pagination_dashboard_menu_validation')
	    );

	    add_settings_field(
		    'pagination_juice_menu',
		    esc_attr__('Pagination Juice Menu', 'daim'),
		    array($this,'pagination_juice_menu_callback'),
		    'daim_advanced_options',
		    'daim_advanced_settings_section'
	    );

	    register_setting(
		    'daim_advanced_options',
		    'daim_pagination_juice_menu',
		    array($this,'pagination_juice_menu_validation')
	    );

	    add_settings_field(
		    'pagination_hits_menu',
		    esc_attr__('Pagination Hits Menu', 'daim'),
		    array($this,'pagination_hits_menu_callback'),
		    'daim_advanced_options',
		    'daim_advanced_settings_section'
	    );

	    register_setting(
		    'daim_advanced_options',
		    'daim_pagination_hits_menu',
		    array($this,'pagination_hits_menu_validation')
	    );

	    add_settings_field(
		    'pagination_ail_menu',
		    esc_attr__('Pagination AIL Menu', 'daim'),
		    array($this,'pagination_ail_menu_callback'),
		    'daim_advanced_options',
		    'daim_advanced_settings_section'
	    );

	    register_setting(
		    'daim_advanced_options',
		    'daim_pagination_ail_menu',
		    array($this,'pagination_ail_menu_validation')
	    );
        
    }
    
    //ail options callbacks and validations ------------------------------------
	public function default_open_new_tab_callback($args){

		$html = '<select id="daim_default_open_new_tab" name="daim_default_open_new_tab" class="daext-display-none">';
		$html .= '<option ' . selected(intval(get_option("daim_default_open_new_tab")), 0, false) . ' value="0">' . esc_attr__('No', 'daim') . '</option>';
		$html .= '<option ' . selected(intval(get_option("daim_default_open_new_tab")), 1, false) . ' value="1">' . esc_attr__('Yes', 'daim') . '</option>';
		$html .= '</select>';
		$html .= '<div class="help-icon" title="' . esc_attr(__('If you select "Yes" the link generated on the defined keyword opens the linked document in a new tab. This option determines the default value of the "Open New Tab" field available in the AIL menu.', 'daim')) . '"></div>';

		echo $html;

	}

	public function default_open_new_tab_validation($input){

		return intval($input, 10) == 1 ? '1' : '0';

	}

	public function default_use_nofollow_callback($args){

		$html = '<select id="daim_default_use_nofollow" name="daim_default_use_nofollow" class="daext-display-none">';
		$html .= '<option ' . selected(intval(get_option("daim_default_use_nofollow")), 0, false) . ' value="0">' . esc_attr__('No', 'daim') . '</option>';
		$html .= '<option ' . selected(intval(get_option("daim_default_use_nofollow")), 1, false) . ' value="1">' . esc_attr__('Yes', 'daim') . '</option>';
		$html .= '</select>';
		$html .= '<div class="help-icon" title="' . esc_attr(__('If you select "Yes" the link generated on the defined keyword will include the rel="nofollow" attribute. This option determines the default value of the "Use Nofollow" field available in the AIL menu.', 'daim')) . '"></div>';

		echo $html;

	}

	public function default_use_nofollow_validation($input){

		return intval($input, 10) == 1 ? '1' : '0';

	}

	public function default_activate_post_types_callback($args){

		$html = '<input type="text" id="daim_default_activate_post_types" name="daim_default_activate_post_types" class="regular-text" value="' . esc_attr(get_option("daim_default_activate_post_types")) . '" />';
		$html .= '<div class="help-icon" title="' . esc_attr(__('Enter a list of post types separated by a comma. This option determines the default value of the "Post Types" field available in the AIL menu.', 'daim')) . '"></div>';
		echo $html;

	}

	public function default_activate_post_types_validation($input){

		if(!preg_match($this->shared->regex_list_of_post_types, $input)){
			add_settings_error( 'daim_default_activate_post_types', 'daim_default_activate_post_types', esc_attr__('Please enter a valid list of post types separated by a comma in the "Post Types" option.', 'daim') );
			$output = get_option('daim_default_activate_post_types');
		}else{
			$output = $input;
		}

		return $output;

	}

	public function default_case_insensitive_search_callback($args){

		$html = '<select id="daim_default_case_insensitive_search" name="daim_default_case_insensitive_search" class="daext-display-none">';
		$html .= '<option ' . selected(intval(get_option("daim_default_case_insensitive_search")), 0, false) . ' value="0">' . esc_attr__('No', 'daim') . '</option>';
		$html .= '<option ' . selected(intval(get_option("daim_default_case_insensitive_search")), 1, false) . ' value="1">' . esc_attr__('Yes', 'daim') . '</option>';
		$html .= '</select>';
		$html .= '<div class="help-icon" title="' . esc_attr(__('If you select "Yes" your keyword will match both lowercase and uppercase variations. This option determines the default value of the "Case Insensitive Search" field available in the AIL menu.', 'daim')) . '"></div>';

		echo $html;

	}

	public function default_case_insensitive_search_validation($input){

		return intval($input, 10) == 1 ? '1' : '0';

	}

	public function default_string_before_callback($args)
	{

		$html = '<select id="daim_default_string_before" name="daim_default_string_before" class="daext-display-none">';
		$html .= '<option ' . selected(intval(get_option("daim_default_string_before")), 1,
				false) . ' value="1">' . esc_attr__('Generic', 'daim') . '</option>';
		$html .= '<option ' . selected(intval(get_option("daim_default_string_before")), 2,
				false) . ' value="2">' . esc_attr__('White Space', 'daim') . '</option>';
		$html .= '<option ' . selected(intval(get_option("daim_default_string_before")), 3,
				false) . ' value="3">' . esc_attr__('Comma', 'daim') . '</option>';
		$html .= '<option ' . selected(intval(get_option("daim_default_string_before")), 4,
				false) . ' value="4">' . esc_attr__('Point', 'daim') . '</option>';
		$html .= '<option ' . selected(intval(get_option("daim_default_string_before")), 5,
				false) . ' value="5">' . esc_attr__('None', 'daim') . '</option>';
		$html .= '</select>';
		$html .= '<div class="help-icon" title="' . esc_attr__('Use this option to match keywords preceded by a generic boundary or by a specific character. This option determines the default value of the "Left Boundary" field available in the "AIL" menu.',
				'daim') . '"></div>';

		echo $html;

	}

	public function default_string_before_validation($input)
	{

		if (intval($input, 10) >= 1 and intval($input, 10) <= 5) {
			return intval($input, 10);
		} else {
			return intval(get_option('daim_default_string_before'), 10);
		}

	}

	public function default_string_after_callback($args)
	{

		$html = '<select id="daim_default_string_after" name="daim_default_string_after" class="daext-display-none">';
		$html .= '<option ' . selected(intval(get_option("daim_default_string_after")), 1,
				false) . ' value="1">' . esc_attr__('Generic', 'daim') . '</option>';
		$html .= '<option ' . selected(intval(get_option("daim_default_string_after")), 2,
				false) . ' value="2">' . esc_attr__('White Space', 'daim') . '</option>';
		$html .= '<option ' . selected(intval(get_option("daim_default_string_after")), 3,
				false) . ' value="3">' . esc_attr__('Comma', 'daim') . '</option>';
		$html .= '<option ' . selected(intval(get_option("daim_default_string_after")), 4,
				false) . ' value="4">' . esc_attr__('Point', 'daim') . '</option>';
		$html .= '<option ' . selected(intval(get_option("daim_default_string_after")), 5,
				false) . ' value="5">' . esc_attr__('None', 'daim') . '</option>';
		$html .= '</select>';
		$html .= '<div class="help-icon" title="' . esc_attr__('Use this option to match keywords followed by a generic boundary or by a specific character. This option determines the default value of the "Right Boundary" field available in the "AIL" menu.',
				'daim') . '"></div>';

		echo $html;

	}

	public function default_string_after_validation($input)
	{

		if (intval($input, 10) >= 1 and intval($input, 10) <= 5) {
			return intval($input, 10);
		} else {
			return intval(get_option('daim_default_string_after'), 10);
		}

	}

	public function default_max_number_autolinks_per_keyword_callback($args){

		$html = '<input type="text" id="daim_default_max_number_autolinks_per_keyword" name="daim_default_max_number_autolinks_per_keyword" class="regular-text" value="' . intval(get_option("daim_default_max_number_autolinks_per_keyword"), 10) . '" />';
		$html .= '<div class="help-icon" title="' . esc_attr(__('With this option you can determine the maximum number of matches of the defined keyword automatically converted to a link. This option determines the default value of the "Limit" field available in the AIL menu.', 'daim')) . '"></div>';
		echo $html;

	}

	public function default_max_number_autolinks_per_keyword_validation($input){

		if(!preg_match($this->shared->regex_number_ten_digits, $input) or intval($input, 10) < 1 or intval($input, 10) > 1000000 ){
			add_settings_error( 'daim_default_max_number_autolinks_per_keyword', 'daim_default_max_number_autolinks_per_keyword', esc_attr__('Please enter a number from 1 to 1000000 in the "Max AIL per Keyword (Default)" option.', 'daim') );
			$output = get_option('daim_default_max_number_autolinks_per_keyword');
		}else{
			$output = $input;
		}

		return intval($output,  10);

	}

	public function default_priority_callback($args){
        
        $html = '<input type="text" id="daim_default_priority" name="daim_default_priority" class="regular-text" value="' . intval(get_option("daim_default_priority"), 10) . '" />';
        $html .= '<div class="help-icon" title="' . esc_attr(__('The priority value determines the order used to apply the AIL on the post. This option determines the default value of the "Priority" field available in the AIL menu.', 'daim')) . '"></div>';
        
        echo $html;
        
    }
    
    public function default_priority_validation($input){
        
        if(!preg_match($this->shared->regex_number_ten_digits, $input) or intval($input, 10) > 1000000){
            add_settings_error( 'daim_default_priority', 'daim_default_priority', esc_attr__('Please enter a number from 0 to 1000000 in the "Priority (Default)" option.', 'daim') );
            $output = get_option('daim_default_priority');
        }else{
            $output = $input;
        }

        return intval($output, 10);
        
    }
    
    //suggestions options callbacks and validations ----------------------------
    
    public function suggestions_pool_post_types_callback($args){
        
        $html = '<input type="text" id="daim_suggestions_pool_post_types" name="daim_suggestions_pool_post_types" class="regular-text" value="' . esc_attr(get_option("daim_suggestions_pool_post_types")) . '" />';
        $html .= '<div class="help-icon" title="' . esc_attr(__('A list of post types, separated by comma, where the algorithm available in the "Interlinks Suggestions" meta box should look for suggestions.', 'daim')) . '"></div>';
        
        echo $html;
        
    }
    
    public function suggestions_pool_post_types_validation($input){
        
        if(!preg_match($this->shared->regex_list_of_post_types, $input)){
            add_settings_error( 'daim_suggestions_pool_post_types', 'daim_suggestions_pool_post_types', esc_attr__('Please enter a valid list of post types separated by a comma in the "Pool Post Types" option.', 'daim') );
            $output = get_option('daim_suggestions_pool_post_types');
        }else{
            $output = $input;
        }

        return $output;
        
    }
    
    public function suggestions_pool_size_callback($args){
        
        $html = '<input type="text" id="daim_suggestions_pool_size" name="daim_suggestions_pool_size" class="regular-text" value="' . esc_attr(get_option("daim_suggestions_pool_size")) . '" />';
        $html .= '<div class="help-icon" title="' . esc_attr(__('This option determines the maximum number of results returned by the algorithm available in the "Interlinks Suggestions" meta box. (The five results shown for each iteration are retrieved from a pool of results which has, as a maximum size, the value defined with this option.)', 'daim')) . '"></div>';
        
        echo $html;
        
    }
    
    public function suggestions_pool_size_validation($input){
        
        if(!preg_match($this->shared->regex_number_ten_digits, $input) or intval($input, 10) < 5 or intval($input) > 1000000){
            add_settings_error( 'daim_suggestions_pool_size', 'daim_suggestions_pool_size', esc_attr__('Please enter a number from 5 to 1000000 in the "Pool Size" option.', 'daim') );
            $output = get_option('daim_suggestions_pool_size');
        }else{
            $output = $input;
        }

        return intval($output, 10);
        
    }
    
    public function suggestions_titles_callback($args){
        
        $html = '<select id="daim_suggestions_titles" name="daim_suggestions_titles" class="daext-display-none">';
            $html .= '<option ' . selected(get_option("daim_suggestions_titles"), 'consider', false) . ' value="consider">' . esc_attr__('Consider', 'daim') . '</option>';
            $html .= '<option ' . selected(get_option("daim_suggestions_titles"), 'ignore', false) . ' value="ignore">' . esc_attr__('Ignore', 'daim') . '</option>';
        $html .= '</select>';
        $html .= '<div class="help-icon" title="' . esc_attr(__('Select if the algorithm available in the "Interlinks Suggestions" meta box should consider the posts, pages and custom post types titles.', 'daim')) . '"></div>';
        
        echo $html;
        
    }
    
    public function suggestions_titles_validation($input){
        
        return $input;
        
    }
    
    public function suggestions_categories_callback($args){
        
        $html = '<select id="daim_suggestions_categories" name="daim_suggestions_categories" class="daext-display-none">';
            $html .= '<option ' . selected(get_option("daim_suggestions_categories"), 'require', false) . ' value="require">' . esc_attr__('Require', 'daim') . '</option>';
            $html .= '<option ' . selected(get_option("daim_suggestions_categories"), 'consider', false) . ' value="consider">' . esc_attr__('Consider', 'daim') . '</option>';
            $html .= '<option ' . selected(get_option("daim_suggestions_categories"), 'ignore', false) . ' value="ignore">' . esc_attr__('Ignore', 'daim') . '</option>';
        $html .= '</select>';
        $html .= '<div class="help-icon" title="' . esc_attr(__('Select if the algorithm available in the "Interlinks Suggestions" meta box should consider the post categories. If "Required" is selected the algorithm will return only posts that have at least one category in common with the edited post.', 'daim')) . '"></div>';
        
        echo $html;
        
    }
    
    public function suggestions_categories_validation($input){
        
        return $input;
        
    }
    
    public function suggestions_tags_callback($args){
        
        $html = '<select id="daim_suggestions_tags" name="daim_suggestions_tags" class="daext-display-none">';
            $html .= '<option ' . selected(get_option("daim_suggestions_tags"), 'require', false) . ' value="require">' . esc_attr__('Require', 'daim') . '</option>';
            $html .= '<option ' . selected(get_option("daim_suggestions_tags"), 'consider', false) . ' value="consider">' . esc_attr__('Consider', 'daim') . '</option>';
            $html .= '<option ' . selected(get_option("daim_suggestions_tags"), 'ignore', false) . ' value="ignore">' . esc_attr__('Ignore', 'daim') . '</option>';
        $html .= '</select>';
        $html .= '<div class="help-icon" title="' . esc_attr(__('Select if the algorithm available in the "Interlinks Suggestions" meta box should consider the post tags. If "Required" is selected the algorithm will return only posts that have at least one tag in common with the edited post.', 'daim')) . '"></div>';
        
        echo $html;
        
    }
    
    public function suggestions_tags_validation($input){
        
        return $input;
        
    }
    
    public function suggestions_post_type_callback($args){
        
        $html = '<select id="daim_suggestions_post_type" name="daim_suggestions_post_type" class="daext-display-none">';
            $html .= '<option ' . selected(get_option("daim_suggestions_post_type"), 'require', false) . ' value="require">' . esc_attr__('Require', 'daim') . '</option>';
            $html .= '<option ' . selected(get_option("daim_suggestions_post_type"), 'consider', false) . ' value="consider">' . esc_attr__('Consider', 'daim') . '</option>';
            $html .= '<option ' . selected(get_option("daim_suggestions_post_type"), 'ignore', false) . ' value="ignore">' . esc_attr__('Ignore', 'daim') . '</option>';
        $html .= '</select>';
        $html .= '<div class="help-icon" title="' . esc_attr(__('Select if the algorithm available in the "Interlinks Suggestions" meta box should consider the post type. If "Required" is selected the algorithm will return only posts that belong to the same post type of the edited post.', 'daim')) . '"></div>';
        
        echo $html;
        
    }
    
    public function suggestions_post_type_validation($input){
        
        return $input;
        
    }
    
    //optimization options callbacks and validations ---------------------------
    
    public function optimization_num_of_characters_callback($args){
        
        $html = '<input type="text" id="daim_optimization_num_of_characters" name="daim_optimization_num_of_characters" class="regular-text" value="' . intval(get_option("daim_optimization_num_of_characters"), 10) . '" />';
        $html .= '<div class="help-icon" title="' . esc_attr(__('The "Recommended Interlinks" value available in the "Dashboard" menu and in the "Interlinks Optimization" meta box is based on the defined "Characters per Interlink" and on the content length of the post. For example if you define 500 "Characters per Interlink", in the "Dashboard" menu, with a post that has a content length of 2000 characters you will get 4 as the value for the "Recommended Interlinks".', 'daim')) . '"></div>';
        
        echo $html;
        
    }
    
    public function optimization_num_of_characters_validation($input){
        if(!preg_match($this->shared->regex_number_ten_digits, $input) or ( intval($input, 10) < 1 ) or ( intval($input, 10) > 1000000 ) ){
            add_settings_error( 'daim_optimization_num_of_characters', 'daim_optimization_num_of_characters', esc_attr__('Please enter a number from 1 to 1000000 in the "Characters per Interlink" option.', 'daim') );
            $output = get_option('daim_optimization_num_of_characters');
        }else{
            $output = $input;
        }

        return intval($output, 10);
        
    }
    
    public function optimization_delta_callback($args){
        
        $html = '<input type="text" id="daim_optimization_delta" name="daim_optimization_delta" class="regular-text" value="' . intval(get_option("daim_optimization_delta"), 10) . '" />';
        $html .= '<div class="help-icon" title="' . esc_attr(__('The "Optimization Delta" is used to generate the "Optimization Flag" available in the "Dashboard" menu and the text message diplayed in the "Interlinks Optimization" meta box. This option determines how different can be the actual number of interlinks in a post from the calculated "Recommended Interlinks". This option defines a range, so for example in a post with 10 "Recommended Interlinks" and this option value equal to 4, the post will be considered optimized when it includes from 8 to 12 interlinks.', 'daim')) . '"></div>';
        
        
        echo $html;
        
    }
    
    public function optimization_delta_validation($input){
        
        if(!preg_match($this->shared->regex_number_ten_digits, $input) or ( intval($input, 10) > 1000000 )){
            add_settings_error( 'daim_optimization_delta', 'daim_optimization_delta', esc_attr__('Please enter a number from 0 to 1000000 in the "Optimization Delta" option.', 'daim') );
            $output = get_option('daim_optimization_delta');
        }else{
            $output = $input;
        }

        return intval($output, 10);
        
    }
    
    //juice options callbacks and validations ----------------------------------
    public function default_seo_power_callback($args){
        
        $html = '<input type="text" id="daim_default_seo_power" name="daim_default_seo_power" class="regular-text" value="' . intval(get_option("daim_default_seo_power"), 10) . '" />';
        $html .= '<div class="help-icon" title="' . esc_attr(__('The "SEO Power" is the base value used to calculate the flow of "Link Juice" and this option determines the default "SEO Power" value of a post. You can override this value for specific posts in the "Interlinks Options" meta box.', 'daim')) . '"></div>';
        
        echo $html;
        
    }
    
    public function default_seo_power_validation($input){
        
        if(!preg_match($this->shared->regex_number_ten_digits, $input) or ( intval($input, 10) < 100 ) or ( intval($input, 10) > 1000000 ) ){
            add_settings_error( 'daim_default_seo_power', 'daim_default_seo_power', esc_attr__('Please enter a number from 100 to 1000000 in the "SEO Power (Default)" option.', 'daim') );
            $output = get_option('daim_default_seo_power');
        }else{
            $output = $input;
        }

        return intval($output, 10);
        
    }
    
    public function penality_per_position_percentage_callback($args){
        
        $html = '<input type="text" id="daim_penality_per_position_percentage" name="daim_penality_per_position_percentage" class="regular-text" value="' . intval(get_option("daim_penality_per_position_percentage"), 10) . '" />';
        $html .= '<div class="help-icon" title="' . esc_attr(__('With multiple links in an article, the algorithm that calculates the "Link Juice" passed by each link removes a percentage of the passed "Link Juice" based on the position of a link compared to the other links.', 'daim')) . '"></div>';
        
        echo $html;
        
    }
    
    public function penality_per_position_percentage_validation($input){
        
        if( !preg_match($this->shared->regex_number_ten_digits, $input) or ( intval($input, 10) > 100 ) ){
            add_settings_error( 'daim_penality_per_position_percentage', 'daim_penality_per_position_percentage', esc_attr__('Please enter a number from 0 to 100 in the "Penality per position" option.', 'daim') );
            $output = get_option('daim_penality_per_position_percentage');
        }else{
            $output = $input;
        }

        return intval($output, 10);
        
    }
    
    public function remove_link_to_anchor_callback($args) {

        $html = '<select id="daim_remove_link_to_anchor" name="daim_remove_link_to_anchor" class="daext-display-none">';
            $html .= '<option ' . selected(intval(get_option("daim_remove_link_to_anchor")), 0, false) . ' value="0">' . esc_attr__('No', 'daim') . '</option>';
            $html .= '<option ' . selected(intval(get_option("daim_remove_link_to_anchor")), 1, false) . ' value="1">' . esc_attr__('Yes', 'daim') . '</option>';
        $html .= '</select>';
        $html .= '<div class="help-icon" title="' . esc_attr(__('Select "Yes" to automatically remove links to anchors from every URL used to calculate the link juice. With this option enabled "http://example.com" and "http://example.com#myanchor" will both contribute to generate link juice only for a single URL, that is "http://example.com".', 'daim')) . '"></div>';               
        
        echo $html;

    }
    
    public function remove_link_to_anchor_validation($input){

        return intval($input, 10) == 1 ? '1' : '0';

    }
    
    public function remove_url_parameters_callback($args) {

        $html = '<select id="daim_remove_url_parameters" name="daim_remove_url_parameters" class="daext-display-none">';
            $html .= '<option ' . selected(intval(get_option("daim_remove_url_parameters")), 0, false) . ' value="0">' . esc_attr__('No', 'daim') . '</option>';
            $html .= '<option ' . selected(intval(get_option("daim_remove_url_parameters")), 1, false) . ' value="1">' . esc_attr__('Yes', 'daim') . '</option>';
        $html .= '</select>';
        $html .= '<div class="help-icon" title="' . esc_attr(__('Select "Yes" to automatically remove the URL parameters from every URL used to calculate the link juice. With this option enabled "http://example.com" and "http://example.com?param=1" will both contribute to generate link juice only for a single URL, that is "http://example.com". Please note that this option should not be enabled if your website is using URL parameters to actually identify specific pages. (for example with pretty permalinks not enabled)', 'daim')) . '"></div>';
        
        echo $html;

    }
    
    public function remove_url_parameters_validation($input){

        return intval($input, 10) == 1 ? '1' : '0';

    }
    
    //tracking options callbacks and validations -------------------------------
    public function track_internal_links_callback($args) {

        $html = '<select id="daim_track_internal_links" name="daim_track_internal_links" class="daext-display-none">';
            $html .= '<option ' . selected(intval(get_option("daim_track_internal_links")), 0, false) . ' value="0">' . esc_attr__('No', 'daim') . '</option>';
            $html .= '<option ' . selected(intval(get_option("daim_track_internal_links")), 1, false) . ' value="1">' . esc_attr__('Yes', 'daim') . '</option>';
        $html .= '</select>';
        $html .= '<div class="help-icon" title="' . esc_attr(__('With this option enabled every click on the manual and auto internal links will be tracked. The collected data will be available in the "Hits" menu.', 'daim')) . '"></div>';               
        
        echo $html;

    }
    
    public function track_internal_links_validation($input){

        return intval($input, 10) == 1 ? '1' : '0';

    }
    
    //analysis options callbacks and validations ----------------------------
    public function set_max_execution_time_callback($args){
        
        $html = '<select id="daim_set_max_execution_time" name="daim_set_max_execution_time" class="daext-display-none">';
            $html .= '<option ' . selected(intval(get_option("daim_set_max_execution_time")), 0, false) . ' value="0">' . esc_attr__('No', 'daim') . '</option>';
            $html .= '<option ' . selected(intval(get_option("daim_set_max_execution_time")), 1, false) . ' value="1">' . esc_attr__('Yes', 'daim') . '</option>';
        $html .= '</select>';
        $html .= '<div class="help-icon" title="' . esc_attr(__('Select "Yes" to enable your custom "Max Execution Time Value" on the scripts used to analyze your posts.', 'daim')) . '"></div>';
            
        echo $html;
        
    }
    
    public function set_max_execution_time_validation($input){
        
        return intval($input, 10) == 1 ? '1' : '0';
        
    }
    
    public function max_execution_time_value_callback($args){
        
        $html = '<input type="text" id="daim_max_execution_time_value" name="daim_max_execution_time_value" class="regular-text" value="' . intval(get_option("daim_max_execution_time_value"), 10) . '" />';
        $html .= '<div class="help-icon" title="' . esc_attr(__('This value determines the maximum number of seconds allowed to execute the scripts used to analyze your posts.', 'daim')) . '"></div>';
        
        echo $html;
        
    }
    
    public function max_execution_time_value_validation($input){
        
        if(!preg_match($this->shared->regex_number_ten_digits, $input) or intval($input, 10) < 1 or intval($input, 10) > 1000000 ){
            add_settings_error( 'daim_max_execution_time_value', 'daim_max_execution_time_value', esc_attr__('Please enter a number from 1 to 1000000 in the "Max Execution Time Value" option.', 'daim') );
            $output = get_option('daim_max_execution_time_value');
        }else{
            $output = $input;
        }

        return intval($output, 10);
        
    } 
    
    public function set_memory_limit_callback($args){
        
        $html = '<select id="daim_set_memory_limit" name="daim_set_memory_limit" class="daext-display-none">';
            $html .= '<option ' . selected(intval(get_option("daim_set_memory_limit")), 0, false) . ' value="0">' . esc_attr__('No', 'daim') . '</option>';
            $html .= '<option ' . selected(intval(get_option("daim_set_memory_limit")), 1, false) . ' value="1">' . esc_attr__('Yes', 'daim') . '</option>';
        $html .= '</select>';
        $html .= '<div class="help-icon" title="' . esc_attr(__('Select "Yes" to enable your custom "Memory Limit Value" on the scripts used to analyze your posts.', 'daim')) . '"></div>';
            
        echo $html;
        
    }
    
    public function set_memory_limit_validation($input){
        
        return intval($input, 10) == 1 ? '1' : '0';
        
    } 
    
    public function memory_limit_value_callback($args){
        
        $html = '<input type="text" id="daim_memory_limit_value" name="daim_memory_limit_value" class="regular-text" value="' . intval(get_option("daim_memory_limit_value"), 10) . '" />';
        $html .= '<div class="help-icon" title="' . esc_attr(__('This value determines the PHP memory limit in megabytes allowed to execute the scripts used to analyze your posts.', 'daim')) . '"></div>';
        
        echo $html;
        
    }
    
    public function memory_limit_value_validation($input){
        
        if(!preg_match($this->shared->regex_number_ten_digits, $input) or intval($input, 10) < 1 or intval($input, 10) > 1000000 ){
            add_settings_error( 'daim_memory_limit_value', 'daim_memory_limit_value', esc_attr__('Please enter a number from 1 to 1000000 in the "Memory Limit Value" option.', 'daim') );
            $output = get_option('daim_memory_limit_value');
        }else{
            $output = $input;
        }

        return intval($output, 10);
        
    }
    
    public function limit_posts_analysis_callback($args){
        
        $html = '<input type="text" id="daim_limit_posts_analysis" name="daim_limit_posts_analysis" class="regular-text" value="' . intval(get_option("daim_limit_posts_analysis"), 10) . '" />';
        $html .= '<div class="help-icon" title="' . esc_attr(__('With this options you can determine the maximum number of posts analyzed to get information about your internal links, to get information about the internal links juice and to get suggestions in the "Interlinks Suggestions" meta box. If you select for example "1000", the analysis performed by the plugin will use your latest "1000" posts.', 'daim')) . '"></div>';
        
        echo $html;
        
    }
    
    public function limit_posts_analysis_validation($input){
        
        if(!preg_match($this->shared->regex_number_ten_digits, $input) or intval($input, 10) < 1 or intval($input, 10) > 100000){
            add_settings_error( 'daim_limit_posts_analysis', 'daim_limit_posts_analysis', esc_attr__('Please enter a number from 1 to 100000 in the "Limit Posts Analysis" option.', 'daim') );
            $output = get_option('daim_limit_posts_analysis');
        }else{
            $output = $input;
        }

        return intval($output, 10);
        
    }    
    
    public function dashboard_post_types_callback($args){
        
        $html = '<input type="text" id="daim_dashboard_post_types" name="daim_dashboard_post_types" class="regular-text" value="' . esc_attr(get_option("daim_dashboard_post_types")) . '" />';
        $html .= '<div class="help-icon" title="' . esc_attr(__('A list of post types, separated by comma, used to determine the post types analyzed in the Dashboard menu.', 'daim')) . '"></div>';
        
        echo $html;
        
    }
    
    public function dashboard_post_types_validation($input){
        
        if(!preg_match($this->shared->regex_list_of_post_types, $input)){
            add_settings_error( 'daim_dashboard_post_types', 'daim_dashboard_post_types', esc_attr__('Please enter a valid list of post types separated by a comma in the "Dashboard Post Types" option.', 'daim') );
            $output = get_option('daim_dashboard_post_types');
        }else{
            $output = $input;
        }

        return $output;
        
    }
    
    public function juice_post_types_callback($args){
        
        $html = '<input type="text" id="daim_juice_post_types" name="daim_juice_post_types" class="regular-text" value="' . esc_attr(get_option("daim_juice_post_types")) . '" />';
        $html .= '<div class="help-icon" title="' . esc_attr(__('A list of post types, separated by comma, used to determine the post types analyzed in the Juice menu.', 'daim')) . '"></div>';
        
        echo $html;
        
    }
    
    public function juice_post_types_validation($input){
        
        if(!preg_match($this->shared->regex_list_of_post_types, $input)){
            add_settings_error( 'daim_juice_post_types', 'daim_juice_post_types', esc_attr__('Please enter a valid list of post types separated by a comma in the "Juice Post Types" option.', 'daim') );
            $output = get_option('daim_juice_post_types');
        }else{
            $output = $input;
        }

        return $output;
        
    }
    
    //metaboxes options callbacks and validation -------------------------------
    public function interlinks_options_post_types_callback($args){
        
        $html = '<input type="text" id="daim_interlinks_options_post_types" name="daim_interlinks_options_post_types" class="regular-text" value="' . esc_attr(get_option("daim_interlinks_options_post_types")) . '" />';
        $html .= '<div class="help-icon" title="' . esc_attr(__('A list of post types, separated by comma, where the "Interlinks Options" meta box should be loaded.', 'daim')) . '"></div>';
        
        echo $html;
        
    }
    
    public function interlinks_options_post_types_validation($input){
        
        if(!preg_match($this->shared->regex_list_of_post_types, $input)){
            add_settings_error( 'daim_interlinks_options_post_types', 'daim_interlinks_options_post_types', esc_attr__('Please enter a valid list of post types separated by a comma in the "Interlinks Options Post Types" option.', 'daim') );
            $output = get_option('daim_interlinks_options_post_types');
        }else{
            $output = $input;
        }

        return $output;
        
    }
    
    public function interlinks_optimization_post_types_callback($args){
        
        $html = '<input type="text" id="daim_interlinks_optimization_post_types" name="daim_interlinks_optimization_post_types" class="regular-text" value="' . esc_attr(get_option("daim_interlinks_optimization_post_types")) . '" />';
        $html .= '<div class="help-icon" title="' . esc_attr(__('A list of post types, separated by comma, where the "Interlinks Optimization" meta box should be loaded.', 'daim')) . '"></div>';
        
        echo $html;
        
    }
    
    public function interlinks_optimization_post_types_validation($input){
        
        if(!preg_match($this->shared->regex_list_of_post_types, $input)){
            add_settings_error( 'daim_interlinks_optimization_post_types', 'daim_interlinks_optimization_post_types', esc_attr__('Please enter a valid list of post types separated by a comma in the "Interlinks Optimization Post Types" option.', 'daim') );
            $output = get_option('daim_interlinks_optimization_post_types');
        }else{
            $output = $input;
        }

        return $output;
        
    }
    
    public function interlinks_suggestions_post_types_callback($args){
        
        $html = '<input type="text" id="daim_interlinks_suggestions_post_types" name="daim_interlinks_suggestions_post_types" class="regular-text" value="' . esc_attr(get_option("daim_interlinks_suggestions_post_types")) . '" />';
        $html .= '<div class="help-icon" title="' . esc_attr(__('A list of post types, separated by comma, where the "Interlinks Suggestions" meta box should be loaded.', 'daim')) . '"></div>';
        
        echo $html;
        
    }
    
    public function interlinks_suggestions_post_types_validation($input){
        
        if(!preg_match($this->shared->regex_list_of_post_types, $input)){
            add_settings_error( 'daim_interlinks_suggestions_post_types', 'daim_interlinks_suggestions_post_types', esc_attr__('Please enter a valid list of post types separated by a comma in the "Interlinks Suggestions Post Types" option.', 'daim') );
            $output = get_option('daim_interlinks_suggestions_post_types');
        }else{
            $output = $input;
        }

        return $output;
        
    }
    
    public function dashboard_menu_required_capability_callback($args){
        
        $html = '<input type="text" id="daim_dashboard_menu_required_capability" name="daim_dashboard_menu_required_capability" class="regular-text" value="' . esc_attr(get_option("daim_dashboard_menu_required_capability")) . '" />';
        $html .= '<div class="help-icon" title="' . esc_attr(__('The capability required to get access on the "Dashboard" Menu.', 'daim')) . '"></div>';
        
        echo $html;
        
    }
    
    public function dashboard_menu_required_capability_validation($input){
        
        if(!preg_match($this->shared->regex_capability, $input)){
            add_settings_error( 'daim_dashboard_menu_required_capability', 'daim_dashboard_menu_required_capability', esc_attr__('Please enter a valid capability in the "Dashboard Menu" option.', 'daim') );
            $output = get_option('daim_dashboard_menu_required_capability');
        }else{
            $output = $input;
        }

        return trim($output);
        
    }
    
    public function juice_menu_required_capability_callback($args){
        
        $html = '<input type="text" id="daim_juice_menu_required_capability" name="daim_juice_menu_required_capability" class="regular-text" value="' . esc_attr(get_option("daim_juice_menu_required_capability")) . '" />';
        $html .= '<div class="help-icon" title="' . esc_attr(__('The capability required to get access on the "Juice" Menu.', 'daim')) . '"></div>';
        
        echo $html;
        
    }
    
    public function juice_menu_required_capability_validation($input){
        
        if(!preg_match($this->shared->regex_capability, $input)){
            add_settings_error( 'daim_juice_menu_required_capability', 'daim_juice_menu_required_capability', esc_attr__('Please enter a valid capability in the "Juice Menu" option.', 'daim') );
            $output = get_option('daim_juice_menu_required_capability');
        }else{
            $output = $input;
        }

        return trim($output);
        
    }
    
    public function hits_menu_required_capability_callback($args){
        
        $html = '<input type="text" id="daim_hits_menu_required_capability" name="daim_hits_menu_required_capability" class="regular-text" value="' . esc_attr(get_option("daim_hits_menu_required_capability")) . '" />';
        $html .= '<div class="help-icon" title="' . esc_attr(__('The capability required to get access on the "Hits" Menu.', 'daim')) . '"></div>';
        
        echo $html;
        
    }
    
    public function hits_menu_required_capability_validation($input){
        
        if(!preg_match($this->shared->regex_capability, $input)){
            add_settings_error( 'daim_hits_menu_required_capability', 'daim_hits_menu_required_capability', esc_attr__('Please enter a valid capability in the "Hits Menu" option.', 'daim') );
            $output = get_option('daim_hits_menu_required_capability');
        }else{
            $output = $input;
        }

        return trim($output);
        
    }
    
    public function ail_menu_required_capability_callback($args){
        
        $html = '<input type="text" id="daim_ail_menu_required_capability" name="daim_ail_menu_required_capability" class="regular-text" value="' . esc_attr(get_option("daim_ail_menu_required_capability")) . '" />';
        $html .= '<div class="help-icon" title="' . esc_attr(__('The capability required to get access on the "AIL" Menu.', 'daim')) . '"></div>';
        
        echo $html;
        
    }
    
    public function ail_menu_required_capability_validation($input){
        
        if(!preg_match($this->shared->regex_capability, $input)){
            add_settings_error( 'daim_ail_menu_required_capability', 'daim_ail_menu_required_capability', esc_attr__('Please enter a valid capability in the "AIL Menu" option.', 'daim') );
            $output = get_option('daim_ail_menu_required_capability');
        }else{
            $output = $input;
        }

        return trim($output);
        
    }
    
    public function interlinks_options_mb_required_capability_callback($args){
        
        $html = '<input type="text" id="daim_interlinks_options_mb_required_capability" name="daim_interlinks_options_mb_required_capability" class="regular-text" value="' . esc_attr(get_option("daim_interlinks_options_mb_required_capability")) . '" />';
        $html .= '<div class="help-icon" title="' . esc_attr(__('The capability required to get access on the "Interlinks Options" Meta Box.', 'daim')) . '"></div>';
        
        echo $html;
        
    }
    
    public function interlinks_options_mb_required_capability_validation($input){
        
        if(!preg_match($this->shared->regex_capability, $input)){
            add_settings_error( 'daim_interlinks_options_mb_required_capability', 'daim_interlinks_options_mb_required_capability', esc_attr__('Please enter a valid capability in the "Interlinks Options Meta Box" option.', 'daim') );
            $output = get_option('daim_interlinks_options_mb_required_capability');
        }else{
            $output = $input;
        }

        return trim($output);
        
    }
    
    public function interlinks_optimization_mb_required_capability_callback($args){
        
        $html = '<input type="text" id="daim_interlinks_optimization_mb_required_capability" name="daim_interlinks_optimization_mb_required_capability" class="regular-text" value="' . esc_attr(get_option("daim_interlinks_optimization_mb_required_capability")) . '" />';
        $html .= '<div class="help-icon" title="' . esc_attr(__('The capability required to get access on the "Interlinks Optimization" Meta Box.', 'daim')) . '"></div>';
        
        echo $html;
        
    }
    
    public function interlinks_optimization_mb_required_capability_validation($input){
        
        if(!preg_match($this->shared->regex_capability, $input)){
            add_settings_error( 'daim_interlinks_optimization_mb_required_capability', 'daim_interlinks_optimization_mb_required_capability', esc_attr__('Please enter a valid capability in the "Interlinks Optimization Meta Box" option.', 'daim') );
            $output = get_option('daim_interlinks_optimization_mb_required_capability');
        }else{
            $output = $input;
        }

        return trim($output);
        
    }
    
    public function interlinks_suggestions_mb_required_capability_callback($args){
        
        $html = '<input type="text" id="daim_interlinks_suggestions_mb_required_capability" name="daim_interlinks_suggestions_mb_required_capability" class="regular-text" value="' . esc_attr(get_option("daim_interlinks_suggestions_mb_required_capability")) . '" />';
        $html .= '<div class="help-icon" title="' . esc_attr(__('The capability required to get access on the "Interlinks Suggestions" Meta Box.', 'daim')) . '"></div>';
        
        echo $html;
        
    }
    
    public function interlinks_suggestions_mb_required_capability_validation($input){
        
        if(!preg_match($this->shared->regex_capability, $input)){
            add_settings_error( 'daim_interlinks_suggestions_mb_required_capability', 'daim_interlinks_suggestions_mb_required_capability', esc_attr__('Please enter a valid capability in the "Interlinks Suggestions Meta Box" option.', 'daim') );
            $output = get_option('daim_interlinks_suggestions_mb_required_capability');
        }else{
            $output = $input;
        }

        return trim($output);
        
    }

    //advanced ---------------------------------------------------------------------------------------------------------
	public function default_enable_ail_on_post_callback($args){

		$html = '<select id="daim_default_enable_ail_on_post" name="daim_default_enable_ail_on_post" class="daext-display-none">';
		$html .= '<option ' . selected(intval(get_option("daim_default_enable_ail_on_post")), 0, false) . ' value="0">' . esc_attr__('No', 'daim') . '</option>';
		$html .= '<option ' . selected(intval(get_option("daim_default_enable_ail_on_post")), 1, false) . ' value="1">' . esc_attr__('Yes', 'daim') . '</option>';
		$html .= '</select>';
		$html .= '<div class="help-icon" title="' . esc_attr(__('This option determines the default status of the "Enable AIL" option available in the "Interlinks Options" meta box.', 'daim')) . '"></div>';

		echo $html;

	}

	public function default_enable_ail_on_post_validation($input){

		return intval($input, 10) == 1 ? '1' : '0';

	}

    public function filter_priority_callback($args)
	{

		$html = '<input maxlength="11" type="text" id="daim_filter_priority" name="daim_filter_priority" class="regular-text" value="' . intval(get_option("daim_filter_priority"),
				10) . '" />';
		$html .= '<div class="help-icon" title="' . esc_attr__('This option determines the priority of the filter used to apply the AIL. A lower number corresponds with an earlier execution.',
				'daim') . '"></div>';
		echo $html;

	}

	public function filter_priority_validation($input)
	{

		if (intval($input, 10) < -2147483648 or intval($input, 10) > 2147483646) {
			add_settings_error('daim_filter_priority', 'daim_filter_priority',
				esc_attr__('Please enter a number from -2147483648 to 2147483646 in the "Filter Priority" option.',
					'daim'));
			$output = get_option('daim_filter_priority');
		} else {
			$output = $input;
		}

		return intval($output, 10);

	}

	public function ail_test_mode_callback($args){

		$html = '<select id="daim_ail_test_mode" name="daim_ail_test_mode" class="daext-display-none">';
		$html .= '<option ' . selected(intval(get_option("daim_ail_test_mode")), 0, false) . ' value="0">' . esc_attr__('No', 'daim') . '</option>';
		$html .= '<option ' . selected(intval(get_option("daim_ail_test_mode")), 1, false) . ' value="1">' . esc_attr__('Yes', 'daim') . '</option>';
		$html .= '</select>';
		$html .= '<div class="help-icon" title="' . esc_attr(__('With the test mode enabled the AIL will be applied to your posts, pages or custom post types only if the user that is requesting the posts, pages or custom post types has the capability defined with the "AIL Menu" option.', 'daim')) . '"></div>';

		echo $html;

	}

	public function ail_test_mode_validation($input){

		return intval($input, 10) == 1 ? '1' : '0';

	}

	public function random_prioritization_callback($args)
	{

		$html = '<select id="daim_random_prioritization" name="daim_random_prioritization" class="daext-display-none">';
		$html .= '<option ' . selected(intval(get_option("daim_random_prioritization")), 0,
				false) . ' value="0">' . esc_attr__('No', 'daim') . '</option>';
		$html .= '<option ' . selected(intval(get_option("daim_random_prioritization")), 1,
				false) . ' value="1">' . esc_attr__('Yes', 'daim') . '</option>';
		$html .= '</select>';
		$html .= '<div class="help-icon" title="' . esc_attr__("With this option enabled the order used to apply the AIL with the same priority is randomized on a per-post basis. With this option disabled the order used to apply the AIL with the same priority is the order used to add them in the back-end. It's recommended to enable this option for a better distribution of the AIL.", 'daim') . '"></div>';

		echo $html;

	}

	public function random_prioritization_validation($input)
	{

		return intval($input, 10) == 1 ? '1' : '0';

	}

	public function ignore_self_ail_callback($args){

		$html = '<select id="daim_ignore_self_ail" name="daim_ignore_self_ail" class="daext-display-none">';
		$html .= '<option ' . selected(intval(get_option("daim_ignore_self_ail")), 0, false) . ' value="0">' . esc_attr__('No', 'daim') . '</option>';
		$html .= '<option ' . selected(intval(get_option("daim_ignore_self_ail")), 1, false) . ' value="1">' . esc_attr__('Yes', 'daim') . '</option>';
		$html .= '</select>';
		$html .= '<div class="help-icon" title="' . esc_attr(__('With this option enabled, the AIL, which have as a target the post where they should be applied, will be ignored.', 'daim')) . '"></div>';

		echo $html;

	}

	public function ignore_self_ail_validation($input){

		return intval($input, 10) == 1 ? '1' : '0';

	}

	public function general_limit_mode_callback($args)
	{

		$html = '<select id="daim_general_limit_mode" name="daim_general_limit_mode" class="daext-display-none">';
		$html .= '<option ' . selected(intval(get_option("daim_general_limit_mode")), 0,
				false) . ' value="0">' . esc_attr__('Auto', 'daim') . '</option>';
		$html .= '<option ' . selected(intval(get_option("daim_general_limit_mode")), 1,
				false) . ' value="1">' . esc_attr__('Manual', 'daim') . '</option>';
		$html .= '</select>';
		$html .= '<div class="help-icon" title="' . esc_attr__('If "Auto" is selected the maximum number of AIL per post is automatically generated based on the length of the post, in this case the "General Limit (Characters per AIL)" option is used. If "Manual" is selected the maximum number of AIL per post is equal to the value of the "General Limit (Amount)" option.',
				'daim') . '"></div>';

		echo $html;

	}

	public function general_limit_mode_validation($input)
	{

		return intval($input, 10) == 1 ? '1' : '0';

	}

	public function characters_per_autolink_callback($args)
	{

		$html = '<input maxlength="7" type="text" id="daim_characters_per_autolink" name="daim_characters_per_autolink" class="regular-text" value="' . intval(get_option("daim_characters_per_autolink"),
				10) . '" />';
		$html .= '<div class="help-icon" title="' . esc_attr__('This value is used to automatically determine the maximum number of AIL per post when the "General Limit Mode" option is set to "Auto".',
				'daim') . '"></div>';
		echo $html;

	}

	public function characters_per_autolink_validation($input)
	{

		if ( ! preg_match($this->shared->regex_number_ten_digits, $input) or intval($input, 10) < 1 or intval($input,
				10) > 1000000) {
			add_settings_error('daim_characters_per_autolink',
				'daim_characters_per_autolink',
				esc_attr__('Please enter a number from 1 to 1000000 in the "General Limit (Characters per AIL)" option.',
					'daim'));
			$output = get_option('daim_characters_per_autolink');
		} else {
			$output = $input;
		}

		return intval($output, 10);

	}

	public function max_number_autolinks_per_post_callback($args){

		$html = '<input maxlength="7" type="text" id="daim_max_number_autolinks_per_post" name="daim_max_number_autolinks_per_post" class="regular-text" value="' . intval(get_option("daim_max_number_autolinks_per_post"), 10) . '" />';
		$html .= '<div class="help-icon" title="' . esc_attr(__('This value determines the maximum number of AIL per post when the "General Limit Mode" option is set to "Manual".', 'daim')) . '"></div>';

		echo $html;

	}

	public function max_number_autolinks_per_post_validation($input){

		if(!preg_match($this->shared->regex_number_ten_digits, $input) or intval($input, 10) < 1 or intval($input, 10) > 1000000 ){
			add_settings_error( 'daim_max_number_autolinks_per_post', 'daim_max_number_autolinks_per_post', esc_attr__('Please enter a number from 1 to 1000000 in the "General Limit (Amount)" option.', 'daim') );
			$output = get_option('daim_max_number_autolinks_per_post');
		}else{
			$output = $input;
		}

		return intval($output, 10);

	}

    public function same_url_limit_callback($args)
	{

		$html = '<input maxlength="7" type="text" id="daim_same_url_limit" name="daim_same_url_limit" class="regular-text" value="' . intval(get_option("daim_same_url_limit"),
				10) . '" />';
		$html .= '<div class="help-icon" title="' . esc_attr__('This option limits the number of AIL with the same URL to a specific value.',
				'daim') . '"></div>';
		echo $html;

	}

	public function same_url_limit_validation($input)
	{

		if ( ! preg_match($this->shared->regex_number_ten_digits, $input) or intval($input, 10) < 1 or intval($input,
				10) > 1000000) {
			add_settings_error('daim_same_url_limit', 'daim_same_url_limit',
				esc_attr__('Please enter a number from 1 to 1000000 in the "Same URL Limit" option.', 'daim'));
			$output = get_option('daim_same_url_limit');
		} else {
			$output = $input;
		}

		return intval($output, 10);

	}

	public function protected_tags_callback($args)
	{

		$protected_tags_a = $this->shared->get_protected_tags_option();

		$html = '<select id="daim-protected-tags" name="daim_protected_tags[]" class="daext-display-none" multiple>';

		$list_of_html_tags = array(
			'a',
			'abbr',
			'acronym',
			'address',
			'applet',
			'area',
			'article',
			'aside',
			'audio',
			'b',
			'base',
			'basefont',
			'bdi',
			'bdo',
			'big',
			'blockquote',
			'body',
			'br',
			'button',
			'canvas',
			'caption',
			'center',
			'cite',
			'code',
			'col',
			'colgroup',
			'datalist',
			'dd',
			'del',
			'details',
			'dfn',
			'dir',
			'div',
			'dl',
			'dt',
			'em',
			'embed',
			'fieldset',
			'figcaption',
			'figure',
			'font',
			'footer',
			'form',
			'frame',
			'frameset',
			'h1',
			'h2',
			'h3',
			'h4',
			'h5',
			'h6',
			'head',
			'header',
			'hgroup',
			'hr',
			'html',
			'i',
			'iframe',
			'img',
			'input',
			'ins',
			'kbd',
			'keygen',
			'label',
			'legend',
			'li',
			'link',
			'map',
			'mark',
			'menu',
			'meta',
			'meter',
			'nav',
			'noframes',
			'noscript',
			'object',
			'ol',
			'optgroup',
			'option',
			'output',
			'p',
			'param',
			'pre',
			'progress',
			'q',
			'rp',
			'rt',
			'ruby',
			's',
			'samp',
			'script',
			'section',
			'select',
			'small',
			'source',
			'span',
			'strike',
			'strong',
			'style',
			'sub',
			'summary',
			'sup',
			'table',
			'tbody',
			'td',
			'textarea',
			'tfoot',
			'th',
			'thead',
			'time',
			'title',
			'tr',
			'tt',
			'u',
			'ul',
			'var',
			'video',
			'wbr'
		);

		foreach ($list_of_html_tags as $key => $tag) {
			$html .= '<option value="' . $tag . '" ' . $this->shared->selected_array($protected_tags_a,
					$tag) . '>' . $tag . '</option>';
		}

		$html .= '</select>';
		$html .= '<div class="help-icon" title="' . esc_attr__('With this option you are able to determine in which HTML tags the AIL should not be applied.',
				'daim') . '"></div>';

		echo $html;

	}

	public function protected_tags_validation($input)
	{

		if (is_array($input)) {
			return $input;
		} else {
			return '';
		}

	}

	public function protected_gutenberg_blocks_callback($args)
	{

		$protected_gutenberg_blocks_a = get_option("daim_protected_gutenberg_blocks");

		$html = '<select id="daim-protected-gutenberg-blocks" name="daim_protected_gutenberg_blocks[]" class="daext-display-none" multiple>';

		$html .= '<option value="paragraph" ' . $this->shared->selected_array($protected_gutenberg_blocks_a,
				'paragraph') . '>' . esc_attr__('Paragraph', 'daim') . '</option>';
		$html .= '<option value="image" ' . $this->shared->selected_array($protected_gutenberg_blocks_a,
				'image') . '>' . esc_attr__('Image', 'daim') . '</option>';
		$html .= '<option value="heading" ' . $this->shared->selected_array($protected_gutenberg_blocks_a,
				'heading') . '>' . esc_attr__('Heading', 'daim') . '</option>';
		$html .= '<option value="gallery" ' . $this->shared->selected_array($protected_gutenberg_blocks_a,
				'gallery') . '>' . esc_attr__('Gallery', 'daim') . '</option>';
		$html .= '<option value="list" ' . $this->shared->selected_array($protected_gutenberg_blocks_a,
				'list') . '>' . esc_attr__('List', 'daim') . '</option>';
		$html .= '<option value="quote" ' . $this->shared->selected_array($protected_gutenberg_blocks_a,
				'quote') . '>' . esc_attr__('Quote', 'daim') . '</option>';
		$html .= '<option value="audio" ' . $this->shared->selected_array($protected_gutenberg_blocks_a,
				'audio') . '>' . esc_attr__('Audio', 'daim') . '</option>';
		$html .= '<option value="cover-image" ' . $this->shared->selected_array($protected_gutenberg_blocks_a,
				'cover-image') . '>' . esc_attr__('Cover Image', 'daim') . '</option>';
		$html .= '<option value="subhead" ' . $this->shared->selected_array($protected_gutenberg_blocks_a,
				'subhead') . '>' . esc_attr__('Subhead', 'daim') . '</option>';
		$html .= '<option value="video" ' . $this->shared->selected_array($protected_gutenberg_blocks_a,
				'video') . '>' . esc_attr__('Video', 'daim') . '</option>';
		$html .= '<option value="code" ' . $this->shared->selected_array($protected_gutenberg_blocks_a,
				'code') . '>' . esc_attr__('Code', 'daim') . '</option>';
		$html .= '<option value="html" ' . $this->shared->selected_array($protected_gutenberg_blocks_a,
				'html') . '>' . esc_attr__('Custom HTML', 'daim') . '</option>';
		$html .= '<option value="preformatted" ' . $this->shared->selected_array($protected_gutenberg_blocks_a,
				'preformatted') . '>' . esc_attr__('Preformatted', 'daim') . '</option>';
		$html .= '<option value="pullquote" ' . $this->shared->selected_array($protected_gutenberg_blocks_a,
				'pullquote') . '>' . esc_attr__('Pullquote', 'daim') . '</option>';
		$html .= '<option value="table" ' . $this->shared->selected_array($protected_gutenberg_blocks_a,
				'table') . '>' . esc_attr__('Table', 'daim') . '</option>';
		$html .= '<option value="verse" ' . $this->shared->selected_array($protected_gutenberg_blocks_a,
				'verse') . '>' . esc_attr__('Verse', 'daim') . '</option>';
		$html .= '<option value="button" ' . $this->shared->selected_array($protected_gutenberg_blocks_a,
				'button') . '>' . esc_attr__('Button', 'daim') . '</option>';
		$html .= '<option value="columns" ' . $this->shared->selected_array($protected_gutenberg_blocks_a,
				'columns') . '>' . esc_attr__('Columns (Experimentals)', 'daim') . '</option>';
		$html .= '<option value="more" ' . $this->shared->selected_array($protected_gutenberg_blocks_a,
				'more') . '>' . esc_attr__('More', 'daim') . '</option>';
		$html .= '<option value="nextpage" ' . $this->shared->selected_array($protected_gutenberg_blocks_a,
				'nextpage') . '>' . esc_attr__('Page Break', 'daim') . '</option>';
		$html .= '<option value="separator" ' . $this->shared->selected_array($protected_gutenberg_blocks_a,
				'separator') . '>' . esc_attr__('Separator', 'daim') . '</option>';
		$html .= '<option value="spacer" ' . $this->shared->selected_array($protected_gutenberg_blocks_a,
				'spacer') . '>' . esc_attr__('Spacer', 'daim') . '</option>';
		$html .= '<option value="text-columns" ' . $this->shared->selected_array($protected_gutenberg_blocks_a,
				'text-columns') . '>' . esc_attr__('Text Columnns', 'daim') . '</option>';
		$html .= '<option value="shortcode" ' . $this->shared->selected_array($protected_gutenberg_blocks_a,
				'shortcode') . '>' . esc_attr__('Shortcode', 'daim') . '</option>';
		$html .= '<option value="categories" ' . $this->shared->selected_array($protected_gutenberg_blocks_a,
				'categories') . '>' . esc_attr__('Categories', 'daim') . '</option>';
		$html .= '<option value="latest-posts" ' . $this->shared->selected_array($protected_gutenberg_blocks_a,
				'latest-posts') . '>' . esc_attr__('Latest Posts', 'daim') . '</option>';
		$html .= '<option value="embed" ' . $this->shared->selected_array($protected_gutenberg_blocks_a,
				'embed') . '>' . esc_attr__('Embed', 'daim') . '</option>';
		$html .= '<option value="core-embed/twitter" ' . $this->shared->selected_array($protected_gutenberg_blocks_a,
				'core-embed/twitter') . '>' . esc_attr__('Twitter', 'daim') . '</option>';
		$html .= '<option value="core-embed/youtube" ' . $this->shared->selected_array($protected_gutenberg_blocks_a,
				'core-embed/youtube') . '>' . esc_attr__('YouTube', 'daim') . '</option>';
		$html .= '<option value="core-embed/facebook" ' . $this->shared->selected_array($protected_gutenberg_blocks_a,
				'core-embed/facebook') . '>' . esc_attr__('Facebook', 'daim') . '</option>';
		$html .= '<option value="core-embed/instagram" ' . $this->shared->selected_array($protected_gutenberg_blocks_a,
				'core-embed/instagram') . '>' . esc_attr__('Instagram', 'daim') . '</option>';
		$html .= '<option value="core-embed/wordpress" ' . $this->shared->selected_array($protected_gutenberg_blocks_a,
				'core-embed/wordpress') . '>' . esc_attr__('WordPress', 'daim') . '</option>';
		$html .= '<option value="core-embed/soundcloud" ' . $this->shared->selected_array($protected_gutenberg_blocks_a,
				'core-embed/soundcloud') . '>' . esc_attr__('SoundCloud', 'daim') . '</option>';
		$html .= '<option value="core-embed/spotify" ' . $this->shared->selected_array($protected_gutenberg_blocks_a,
				'core-embed/spotify') . '>' . esc_attr__('Spotify', 'daim') . '</option>';
		$html .= '<option value="core-embed/flickr" ' . $this->shared->selected_array($protected_gutenberg_blocks_a,
				'core-embed/flickr') . '>' . esc_attr__('Flickr', 'daim') . '</option>';
		$html .= '<option value="core-embed/vimeo" ' . $this->shared->selected_array($protected_gutenberg_blocks_a,
				'core-embed/vimeo') . '>' . esc_attr__('Vimeo', 'daim') . '</option>';
		$html .= '<option value="core-embed/animoto" ' . $this->shared->selected_array($protected_gutenberg_blocks_a,
				'core-embed/animoto') . '>' . esc_attr__('Animoto', 'daim') . '</option>';
		$html .= '<option value="core-embed/cloudup" ' . $this->shared->selected_array($protected_gutenberg_blocks_a,
				'core-embed/cloudup') . '>' . esc_attr__('Cloudup', 'daim') . '</option>';
		$html .= '<option value="core-embed/collegehumor" ' . $this->shared->selected_array($protected_gutenberg_blocks_a,
				'core-embed/collegehumor') . '>' . esc_attr__('CollegeHumor', 'daim') . '</option>';
		$html .= '<option value="core-embed/dailymotion" ' . $this->shared->selected_array($protected_gutenberg_blocks_a,
				'core-embed/dailymotion') . '>' . esc_attr__('DailyMotion', 'daim') . '</option>';
		$html .= '<option value="core-embed/funnyordie" ' . $this->shared->selected_array($protected_gutenberg_blocks_a,
				'core-embed/funnyordie') . '>' . esc_attr__('Funny or Die', 'daim') . '</option>';
		$html .= '<option value="core-embed/hulu" ' . $this->shared->selected_array($protected_gutenberg_blocks_a,
				'core-embed/hulu') . '>' . esc_attr__('Hulu', 'daim') . '</option>';
		$html .= '<option value="core-embed/imgur" ' . $this->shared->selected_array($protected_gutenberg_blocks_a,
				'core-embed/imgur') . '>' . esc_attr__('Imgur', 'daim') . '</option>';
		$html .= '<option value="core-embed/issuu" ' . $this->shared->selected_array($protected_gutenberg_blocks_a,
				'core-embed/issuu') . '>' . esc_attr__('Issuu', 'daim') . '</option>';
		$html .= '<option value="core-embed/kickstarter" ' . $this->shared->selected_array($protected_gutenberg_blocks_a,
				'core-embed/kickstarter') . '>' . esc_attr__('Kickstarter', 'daim') . '</option>';
		$html .= '<option value="core-embed/meetup-com" ' . $this->shared->selected_array($protected_gutenberg_blocks_a,
				'core-embed/meetup-com') . '>' . esc_attr__('Meetup.com', 'daim') . '</option>';
		$html .= '<option value="core-embed/mixcloud" ' . $this->shared->selected_array($protected_gutenberg_blocks_a,
				'core-embed/mixcloud') . '>' . esc_attr__('Mixcloud', 'daim') . '</option>';
		$html .= '<option value="core-embed/photobucket" ' . $this->shared->selected_array($protected_gutenberg_blocks_a,
				'core-embed/photobucket') . '>' . esc_attr__('Photobucket', 'daim') . '</option>';
		$html .= '<option value="core-embed/polldaddy" ' . $this->shared->selected_array($protected_gutenberg_blocks_a,
				'core-embed/polldaddy') . '>' . esc_attr__('Polldaddy', 'daim') . '</option>';
		$html .= '<option value="core-embed/reddit" ' . $this->shared->selected_array($protected_gutenberg_blocks_a,
				'core-embed/reddit') . '>' . esc_attr__('Reddit', 'daim') . '</option>';
		$html .= '<option value="core-embed/reverbnation" ' . $this->shared->selected_array($protected_gutenberg_blocks_a,
				'core-embed/reverbnation') . '>' . esc_attr__('ReverbNation', 'daim') . '</option>';
		$html .= '<option value="core-embed/screencast" ' . $this->shared->selected_array($protected_gutenberg_blocks_a,
				'core-embed/screencast') . '>' . esc_attr__('Screencast', 'daim') . '</option>';
		$html .= '<option value="core-embed/scribd" ' . $this->shared->selected_array($protected_gutenberg_blocks_a,
				'core-embed/scribd') . '>' . esc_attr__('Scribd', 'daim') . '</option>';
		$html .= '<option value="core-embed/slideshare" ' . $this->shared->selected_array($protected_gutenberg_blocks_a,
				'core-embed/slideshare') . '>' . esc_attr__('Slideshare', 'daim') . '</option>';
		$html .= '<option value="core-embed/smugmug" ' . $this->shared->selected_array($protected_gutenberg_blocks_a,
				'core-embed/smugmug') . '>' . esc_attr__('SmugMug', 'daim') . '</option>';
		$html .= '<option value="core-embed/speaker" ' . $this->shared->selected_array($protected_gutenberg_blocks_a,
				'core-embed/speaker') . '>' . esc_attr__('Speaker', 'daim') . '</option>';
		$html .= '<option value="core-embed/ted" ' . $this->shared->selected_array($protected_gutenberg_blocks_a,
				'core-embed/ted') . '>' . esc_attr__('Ted', 'daim') . '</option>';
		$html .= '<option value="core-embed/tumblr" ' . $this->shared->selected_array($protected_gutenberg_blocks_a,
				'core-embed/tumblr') . '>' . esc_attr__('Tumblr', 'daim') . '</option>';
		$html .= '<option value="core-embed/videopress" ' . $this->shared->selected_array($protected_gutenberg_blocks_a,
				'core-embed/videopress') . '>' . esc_attr__('VideoPress', 'daim') . '</option>';
		$html .= '<option value="core-embed/wordpress-tv" ' . $this->shared->selected_array($protected_gutenberg_blocks_a,
				'core-embed/wordpress-tv') . '>' . esc_attr__('WordPress.tv', 'daim') . '</option>';

		$html .= '</select>';
		$html .= '<div class="help-icon" title="' . esc_attr__('With this option you are able to determine in which Gutenberg blocks the AIL should not be applied.',
				'daim') . '"></div>';

		echo $html;

	}

	public function protected_gutenberg_blocks_validation($input)
	{

		if (is_array($input)) {
			return $input;
		} else {
			return '';
		}

	}

	public function protected_gutenberg_custom_blocks_callback($args)
	{

		$html = '<input type="text" id="daim_protected_gutenberg_custom_blocks" name="daim_protected_gutenberg_custom_blocks" class="regular-text" value="' . esc_attr(get_option("daim_protected_gutenberg_custom_blocks")) . '" />';
		$html .= '<div class="help-icon" title="' . esc_attr(__('Enter a list of Gutenberg custom blocks, separated by a comma.',
				'daim')) . '"></div>';

		echo $html;

	}

	public function protected_gutenberg_custom_blocks_validation($input)
	{

		if (strlen(trim($input)) > 0 and ! preg_match($this->shared->regex_list_of_gutenberg_blocks, $input)) {
			add_settings_error('daim_protected_gutenberg_custom_blocks',
				'daim_protected_gutenberg_custom_blocks',
				__('Please enter a valid list of Gutenberg custom blocks separated by a comma in the "Protected Gutenberg Custom Blocks" option.',
					'daim'));
			$output = get_option('daim_protected_gutenberg_custom_blocks');
		} else {
			$output = $input;
		}

		return $output;

	}

	public function protected_gutenberg_custom_void_blocks_callback($args)
	{

		$html = '<input type="text" id="daim_protected_gutenberg_custom_void_blocks" name="daim_protected_gutenberg_custom_void_blocks" class="regular-text" value="' . esc_attr(get_option("daim_protected_gutenberg_custom_void_blocks")) . '" />';
		$html .= '<div class="help-icon" title="' . esc_attr(__('Enter a list of Gutenberg custom void blocks, separated by a comma.',
				'daim')) . '"></div>';

		echo $html;

	}

	public function protected_gutenberg_custom_void_blocks_validation($input)
	{

		if (strlen(trim($input)) > 0 and ! preg_match($this->shared->regex_list_of_gutenberg_blocks, $input)) {
			add_settings_error('daim_protected_gutenberg_custom_void_blocks',
				'daim_protected_gutenberg_custom_void_blocks',
				__('Please enter a valid list of Gutenberg custom void blocks separated by a comma in the "Protected Gutenberg Custom Void Blocks" option.',
					'daim'));
			$output = get_option('daim_protected_gutenberg_custom_void_blocks');
		} else {
			$output = $input;
		}

		return $output;

	}

	public function pagination_dashboard_menu_callback($args){

		$html = '<select id="daim_pagination_dashboard_menu" name="daim_pagination_dashboard_menu" class="daext-display-none">';
		$html .= '<option ' . selected(intval(get_option("daim_pagination_dashboard_menu")), 10, false) . ' value="10">' . esc_attr__('10', 'daim') . '</option>';
		$html .= '<option ' . selected(intval(get_option("daim_pagination_dashboard_menu")), 20, false) . ' value="20">' . esc_attr__('20', 'daim') . '</option>';
		$html .= '<option ' . selected(intval(get_option("daim_pagination_dashboard_menu")), 30, false) . ' value="30">' . esc_attr__('30', 'daim') . '</option>';
		$html .= '<option ' . selected(intval(get_option("daim_pagination_dashboard_menu")), 40, false) . ' value="40">' . esc_attr__('40', 'daim') . '</option>';
		$html .= '<option ' . selected(intval(get_option("daim_pagination_dashboard_menu")), 50, false) . ' value="50">' . esc_attr__('50', 'daim') . '</option>';
		$html .= '<option ' . selected(intval(get_option("daim_pagination_dashboard_menu")), 60, false) . ' value="60">' . esc_attr__('60', 'daim') . '</option>';
		$html .= '<option ' . selected(intval(get_option("daim_pagination_dashboard_menu")), 70, false) . ' value="70">' . esc_attr__('70', 'daim') . '</option>';
		$html .= '<option ' . selected(intval(get_option("daim_pagination_dashboard_menu")), 80, false) . ' value="80">' . esc_attr__('80', 'daim') . '</option>';
		$html .= '<option ' . selected(intval(get_option("daim_pagination_dashboard_menu")), 90, false) . ' value="90">' . esc_attr__('90', 'daim') . '</option>';
		$html .= '<option ' . selected(intval(get_option("daim_pagination_dashboard_menu")), 100, false) . ' value="100">' . esc_attr__('100', 'daim') . '</option>';
		$html .= '</select>';
		$html .= '<div class="help-icon" title="' . esc_attr(__('This options determines the number of elements per page displayed in the "Dashboard" menu.', 'daim')) . '"></div>';

		echo $html;

	}

	public function pagination_dashboard_menu_validation($input){

		return intval($input, 10);

	}

	public function pagination_juice_menu_callback($args){

		$html = '<select id="daim_pagination_juice_menu" name="daim_pagination_juice_menu" class="daext-display-none">';
		$html .= '<option ' . selected(intval(get_option("daim_pagination_juice_menu")), 10, false) . ' value="10">' . esc_attr__('10', 'daim') . '</option>';
		$html .= '<option ' . selected(intval(get_option("daim_pagination_juice_menu")), 20, false) . ' value="20">' . esc_attr__('20', 'daim') . '</option>';
		$html .= '<option ' . selected(intval(get_option("daim_pagination_juice_menu")), 30, false) . ' value="30">' . esc_attr__('30', 'daim') . '</option>';
		$html .= '<option ' . selected(intval(get_option("daim_pagination_juice_menu")), 40, false) . ' value="40">' . esc_attr__('40', 'daim') . '</option>';
		$html .= '<option ' . selected(intval(get_option("daim_pagination_juice_menu")), 50, false) . ' value="50">' . esc_attr__('50', 'daim') . '</option>';
		$html .= '<option ' . selected(intval(get_option("daim_pagination_juice_menu")), 60, false) . ' value="60">' . esc_attr__('60', 'daim') . '</option>';
		$html .= '<option ' . selected(intval(get_option("daim_pagination_juice_menu")), 70, false) . ' value="70">' . esc_attr__('70', 'daim') . '</option>';
		$html .= '<option ' . selected(intval(get_option("daim_pagination_juice_menu")), 80, false) . ' value="80">' . esc_attr__('80', 'daim') . '</option>';
		$html .= '<option ' . selected(intval(get_option("daim_pagination_juice_menu")), 90, false) . ' value="90">' . esc_attr__('90', 'daim') . '</option>';
		$html .= '<option ' . selected(intval(get_option("daim_pagination_juice_menu")), 100, false) . ' value="100">' . esc_attr__('100', 'daim') . '</option>';
		$html .= '</select>';
		$html .= '<div class="help-icon" title="' . esc_attr(__('This options determines the number of elements per page displayed in the "Juice" menu.', 'daim')) . '"></div>';

		echo $html;

	}

	public function pagination_juice_menu_validation($input){

		return intval($input, 10);

	}

	public function pagination_hits_menu_callback($args){

		$html = '<select id="daim_pagination_hits_menu" name="daim_pagination_hits_menu" class="daext-display-none">';
		$html .= '<option ' . selected(intval(get_option("daim_pagination_hits_menu")), 10, false) . ' value="10">' . esc_attr__('10', 'daim') . '</option>';
		$html .= '<option ' . selected(intval(get_option("daim_pagination_hits_menu")), 20, false) . ' value="20">' . esc_attr__('20', 'daim') . '</option>';
		$html .= '<option ' . selected(intval(get_option("daim_pagination_hits_menu")), 30, false) . ' value="30">' . esc_attr__('30', 'daim') . '</option>';
		$html .= '<option ' . selected(intval(get_option("daim_pagination_hits_menu")), 40, false) . ' value="40">' . esc_attr__('40', 'daim') . '</option>';
		$html .= '<option ' . selected(intval(get_option("daim_pagination_hits_menu")), 50, false) . ' value="50">' . esc_attr__('50', 'daim') . '</option>';
		$html .= '<option ' . selected(intval(get_option("daim_pagination_hits_menu")), 60, false) . ' value="60">' . esc_attr__('60', 'daim') . '</option>';
		$html .= '<option ' . selected(intval(get_option("daim_pagination_hits_menu")), 70, false) . ' value="70">' . esc_attr__('70', 'daim') . '</option>';
		$html .= '<option ' . selected(intval(get_option("daim_pagination_hits_menu")), 80, false) . ' value="80">' . esc_attr__('80', 'daim') . '</option>';
		$html .= '<option ' . selected(intval(get_option("daim_pagination_hits_menu")), 90, false) . ' value="90">' . esc_attr__('90', 'daim') . '</option>';
		$html .= '<option ' . selected(intval(get_option("daim_pagination_hits_menu")), 100, false) . ' value="100">' . esc_attr__('100', 'daim') . '</option>';
		$html .= '</select>';
		$html .= '<div class="help-icon" title="' . esc_attr(__('This options determines the number of elements per page displayed in the "Hits" menu.', 'daim')) . '"></div>';

		echo $html;

	}

	public function pagination_hits_menu_validation($input){

		return intval($input, 10);

	}

	public function pagination_ail_menu_callback($args){

		$html = '<select id="daim_pagination_ail_menu" name="daim_pagination_ail_menu" class="daext-display-none">';
		$html .= '<option ' . selected(intval(get_option("daim_pagination_ail_menu")), 10, false) . ' value="10">' . esc_attr__('10', 'daim') . '</option>';
		$html .= '<option ' . selected(intval(get_option("daim_pagination_ail_menu")), 20, false) . ' value="20">' . esc_attr__('20', 'daim') . '</option>';
		$html .= '<option ' . selected(intval(get_option("daim_pagination_ail_menu")), 30, false) . ' value="30">' . esc_attr__('30', 'daim') . '</option>';
		$html .= '<option ' . selected(intval(get_option("daim_pagination_ail_menu")), 40, false) . ' value="40">' . esc_attr__('40', 'daim') . '</option>';
		$html .= '<option ' . selected(intval(get_option("daim_pagination_ail_menu")), 50, false) . ' value="50">' . esc_attr__('50', 'daim') . '</option>';
		$html .= '<option ' . selected(intval(get_option("daim_pagination_ail_menu")), 60, false) . ' value="60">' . esc_attr__('60', 'daim') . '</option>';
		$html .= '<option ' . selected(intval(get_option("daim_pagination_ail_menu")), 70, false) . ' value="70">' . esc_attr__('70', 'daim') . '</option>';
		$html .= '<option ' . selected(intval(get_option("daim_pagination_ail_menu")), 80, false) . ' value="80">' . esc_attr__('80', 'daim') . '</option>';
		$html .= '<option ' . selected(intval(get_option("daim_pagination_ail_menu")), 90, false) . ' value="90">' . esc_attr__('90', 'daim') . '</option>';
		$html .= '<option ' . selected(intval(get_option("daim_pagination_ail_menu")), 100, false) . ' value="100">' . esc_attr__('100', 'daim') . '</option>';
		$html .= '</select>';
		$html .= '<div class="help-icon" title="' . esc_attr(__('This options determines the number of elements per page displayed in the "AIL" menu.', 'daim')) . '"></div>';

		echo $html;

	}

	public function pagination_ail_menu_validation($input){

		return intval($input, 10);

	}

	//meta box -----------------------------------------------------------------
    public function create_meta_box(){
        
        if(current_user_can(get_option( $this->shared->get('slug') . "_interlinks_options_mb_required_capability"))){

            /*
             * Load the "Interlinks Options" meta box only in the post types defined
             * with the "Interlinks Options Post Types" option
             */
            $interlinks_options_post_types = preg_replace('/\s+/', '', get_option( $this->shared->get('slug') . '_interlinks_options_post_types' ));
            $interlinks_options_post_types_a = explode(',', $interlinks_options_post_types);
            foreach ($interlinks_options_post_types_a as $key => $post_type) {
                add_meta_box( 'daim-meta-options', esc_attr__('Interlinks Options', 'daim'), array($this, 'create_options_meta_box_callback'), $post_type, 'normal', 'high' );
            }
            
        }

        if(current_user_can(get_option( $this->shared->get('slug') . "_interlinks_optimization_mb_required_capability"))){
        
            /*
             * Load the "Interlinks Optimization" meta box only in the post types
             * defined with the "Interlinks Optimization Post Types" option
             */
            $interlinks_optimization_post_types = preg_replace('/\s+/', '', get_option( $this->shared->get('slug') . '_interlinks_optimization_post_types' ));
            $interlinks_optimization_post_types_a = explode(',', $interlinks_optimization_post_types);
            foreach ($interlinks_optimization_post_types_a as $key => $post_type) {
                add_meta_box( 'daim-meta-optimization', esc_attr__('Interlinks Optimization', 'daim'), array($this, 'create_optimization_meta_box_callback'), $post_type, 'side', 'default' );
            }
            
        }
        
        if(current_user_can(get_option( $this->shared->get('slug') . "_interlinks_suggestions_mb_required_capability"))){
        
            /*
             * Load the "Interlinks Suggestions" meta box only in the post types
             * defined with the "Interlinks Suggestions Post Types" option
             */
            $interlinks_suggestions_post_types = preg_replace('/\s+/', '', get_option( $this->shared->get('slug') . '_interlinks_suggestions_post_types' ));
            $interlinks_suggestions_post_types_a = explode(',', $interlinks_suggestions_post_types);
            foreach ($interlinks_suggestions_post_types_a as $key => $post_type) {
                add_meta_box( 'daim-meta-suggestions', esc_attr__('Interlinks Suggestions', 'daim'), array($this, 'create_suggestions_meta_box_callback'), $post_type, 'side', 'default' );
            }
            
        }
        
    }
    
    //display the Interlinks Options meta box content
    public function create_options_meta_box_callback( $post ) {

	//retrieve the Interlinks Manager data values
	$seo_power = get_post_meta( $post->ID, '_daim_seo_power', true );
        if(strlen(trim($seo_power)) == 0){$seo_power = (int) get_option( $this->shared->get('slug') . '_default_seo_power');}
        $enable_ail = get_post_meta( $post->ID, '_daim_enable_ail', true );
	
        //if the $enable_ail is empty use the Enable AIL option as a default
        if(strlen(trim($enable_ail)) == 0){
            $enable_ail = get_option( $this->shared->get('slug') . '_default_enable_ail_on_post');
        }
        
	?>

        <table class="form-table table-interlinks-options">
            <tbody>
                
                <tr>
                    <th scope="row"><label><?php esc_attr_e('SEO Power', 'daim'); ?></label></th>
                    <td>
                        <input type="text" name="daim_seo_power" value="<?php echo intval(( $seo_power ), 10); ?>" class="regular-text" maxlength="7">
                        <div class="help-icon" title="<?php esc_attr_e('The SEO Power of this post.', 'daim'); ?>"></div>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row"><label><?php esc_attr_e('Enable AIL', 'daim'); ?></label></th>
                    <td>
                        <select id="daim-enable-ail" class="daext-display-none" name="daim_enable_ail">
                            <option <?php selected(intval($enable_ail, 10), 0); ?> value="0"><?php esc_attr_e('No', 'daim'); ?></option>
                            <option <?php selected(intval($enable_ail, 10), 1); ?>value="1"><?php esc_attr_e('Yes', 'daim'); ?></option>
                        </select>
                        <div class="help-icon" title="<?php esc_attr_e('Select "Yes" to enable the AIL in this post.', 'daim'); ?>"></div>

                    </td>
                </tr>
                
            </tbody>
        </table>     
        
	<?php
	
	// Use nonce for verification
	wp_nonce_field( plugin_basename( __FILE__ ), 'daim_nonce' );	
	
    }
    
    //display the Interlinks Optimization meta box content
    public function create_optimization_meta_box_callback( $post ) {
	
	?>

        <div class="meta-box-body">
            <table class="form-table">
                <tbody>

                    <tr>
                        <td>
                            <?php echo $this->shared->generate_interlinks_optimization_metabox_html($post); ?>
                        </td>
                    </tr>

                </tbody>
            </table>
        </div>
        
	<?php	
	
    }
    
    //display the Interlinks Suggestions meta box content
    public function create_suggestions_meta_box_callback( $post ) {
	
	?>

        <div class="meta-box-body">
            <table class="form-table">
                <tbody>

                    <tr>
                        <td>
                            <p id="daim-interlinks-suggestions-introduction"><?php esc_attr_e('Click the "Generate" button multiple times until you can find posts suitable to be used as interlinks of this post.', 'daim'); ?></p>
                            <div id="daim-interlinks-suggestions-list"></div>
                        </td>
                    </tr>

                </tbody>
            </table>  
        </div>

        <div id="major-publishing-actions">

            <div id="publishing-action">
                <input id="ajax-request-status" type="hidden" value="inactive">
                <span class="spinner"></span>
                <input data-post-id="<?php echo $post->ID; ?>" type="button" class="button button-primary button-large" id="generate-ideas" value="<?php esc_attr_e('Generate', 'daim'); ?>">
            </div>
            <div class="clear"></div>

        </div>
        
	<?php	
	
    }
    
    //Save the Interlinks Options meta data
    public function daim_save_meta_interlinks_options( $post_id ) {

        //security verifications -----------------------------------------------

        // verify if this is an auto save routine. 
        // If it is our form has not been submitted, so we dont want to do anything        
        if(defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {  
          return;
        }	

        /*
         * verify this came from the our screen and with proper authorization,
         * because save_post can be triggered at other times
         */
        if ( !isset( $_POST['daim_nonce'] ) || !wp_verify_nonce( $_POST['daim_nonce'], plugin_basename( __FILE__ ) ) ){
            return;
        }

        //verify the capability
        if(!current_user_can(get_option( $this->shared->get('slug') . "_interlinks_options_mb_required_capability"))){return;}

        //end security verifications -------------------------------------------

        //save the "SEO Power" only if it's included in the allowed values
        if(intval($_POST['daim_seo_power'], 10) != 0 and intval($_POST['daim_seo_power'], 10) <= 1000000){
            update_post_meta( $post_id, '_daim_seo_power', intval($_POST['daim_seo_power']) );
        }
        
        //save the "Enable AIL"
        update_post_meta( $post_id, '_daim_enable_ail', intval($_POST['daim_enable_ail'], 10) );

    }
    
    /*
     * The "Export CSV" buttons and/or icons available in the Dashboard, Juice
     * and Hits menus are intercepted and the proper method that generates on
     * the fly the specific downloadable CSV file is called
     */
    public function export_csv_controller(){
        
        /*
         * Intercept requests that come from the "Export CSV" button from the
         * "Dashboard" menu and generate the downloadable CSV file with the
         * dashboard_menu_export_csv() method
         */
        if( isset($_GET['page']) and
            $_GET['page'] == 'daim-dashboard' and
            isset($_POST['export_csv']))
        {
            $this->dashboard_menu_export_csv();
        }
        
        /*
         * Intercept requests that come from the "Export CSV" button from the
         * "Juice" menu and generate the downloadable CSV file with the
         * juice_menu_export_csv() method
         */
        if( isset($_GET['page']) and
            $_GET['page'] == 'daim-juice' and
            isset($_POST['export_csv']))
        {
            $this->juice_menu_export_csv();
        }
        
        /*
         * Intercept requests that come from the "Export CSV" button from the
         * "Juice" menu and generate the downloadable CSV file with the
         * anchors_menu_export_csv() method
         */
        if( isset($_GET['page']) and
            $_GET['page'] == 'daim-juice' and
            isset($_POST['export_anchors_csv']) and
            isset($_POST['anchors_url']))
        {
            $this->anchors_menu_export_csv();
        }
        
        /*
         * Intercept requests that come from the "Export CSV" button from the
         * "Hits" menu and generate the downloadable CSV file with the
         * hits_menu_export_csv() method
         */
        if( isset($_GET['page']) and
            $_GET['page'] == 'daim-hits' and
            isset($_POST['export_csv']))
        {
            $this->hits_menu_export_csv();
        }
        
    }
    
    /*
     * Generates the downloadable CSV file with all the items available in the
     * Dashboard menu
     */
    private function dashboard_menu_export_csv(){

        //verify capability
        if(!current_user_can(get_option( $this->shared->get('slug') . '_dashboard_menu_required_capability'))){die();}
        
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
        
        //get the data from the db table
        global $wpdb;
        $table_name = $wpdb->prefix . $this->shared->get('slug') . "_archive";
        $results = $wpdb->get_results("SELECT * FROM $table_name ORDER BY post_date DESC", ARRAY_A);
        
        //if there are data generate the csv header and content
        if( count( $results ) > 0 ){
            
            $csv_content = '';
            $new_line = "\n";

            //set the csv header
            header('Content-Encoding: UTF-8');
            header('Content-type: text/csv; charset=UTF-8');
            header("Content-Disposition: attachment; filename=dashboard-" . time() . ".csv");
            header("Pragma: no-cache");
            header("Expires: 0");

            //set headings
            $csv_content .= '"' . $this->esc_csv(__('Post', 'daim')) . '",';
            $csv_content .= '"' . $this->esc_csv(__('Date', 'daim')) . '",';
            $csv_content .= '"' . $this->esc_csv(__('PT', 'daim')) . '",';
            $csv_content .= '"' . $this->esc_csv(__('CL', 'daim')) . '",';
            $csv_content .= '"' . $this->esc_csv(__('MIL', 'daim')) . '",';
            $csv_content .= '"' . $this->esc_csv(__('AIL', 'daim')) . '",';
            $csv_content .= '"' . $this->esc_csv(__('RI', 'daim')) . '",';
            $csv_content .= '"' . $this->esc_csv(__('VS', 'daim')) . '",';
            $csv_content .= '"' . $this->esc_csv(__('OF', 'daim')) . '"';
            $csv_content .= $new_line;			
            
            //set column content
            foreach ( $results as $result ) {
                
                $csv_content .= '"' . $this->esc_csv($result['post_title']) . '",';
                $csv_content .= '"' . $this->esc_csv( mysql2date( get_option('date_format') , $result['post_date'] ) ) . '",';
                $csv_content .= '"' . $this->esc_csv($result['post_type']) . '",';
                $csv_content .= '"' . $this->esc_csv($result['content_length']) . '",';
                $csv_content .= '"' . $this->esc_csv($result['manual_interlinks']) . '",';
                $csv_content .= '"' . $this->esc_csv($result['auto_interlinks']) . '",';
                $csv_content .= '"' . $this->esc_csv($result['recommended_interlinks']) . '",';
                $csv_content .= '"' . $this->esc_csv($this->shared->get_number_of_hits($result['post_id'])) . '",';
                $csv_content .= '"' . $this->esc_csv($result['optimization']) . '"';
                $csv_content .= $new_line;		

            }
                        
        }else{
            return false;
        }

        echo $csv_content;
        die();
        
    }
    
    /*
     * Generates the downloadable CSV file with all the items available in the
     * Juice menu
     */
    private function juice_menu_export_csv(){

        //verify capability
        if(!current_user_can(get_option( $this->shared->get('slug') . "_juice_menu_required_capability"))){die();}
        
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
        
        //get the data from the db table
        global $wpdb;
        $table_name = $wpdb->prefix . $this->shared->get('slug') . "_juice";
        $results = $wpdb->get_results("SELECT * FROM $table_name ORDER BY juice DESC", ARRAY_A);
        
        //if there are data generate the csv header and content
        if( count( $results ) > 0 ){
            
            $csv_content = '';
            $new_line = "\n";

            //set the csv header
            header('Content-Encoding: UTF-8');
            header('Content-type: text/csv; charset=UTF-8');
            header("Content-Disposition: attachment; filename=juice-" . time() . ".csv");
            header("Pragma: no-cache");
            header("Expires: 0");

            //set headings
            $csv_content .= '"' . $this->esc_csv(__('URL', 'daim')) . '",';
            $csv_content .= '"' . $this->esc_csv(__('IIL', 'daim')) . '",';
            $csv_content .= '"' . $this->esc_csv(__('Juice (Value)', 'daim')) . '"';
            $csv_content .= $new_line;			

            //set column content
            foreach ( $results as $result ) {

                $csv_content .= '"' . $this->esc_csv($result['url']) . '",';
                $csv_content .= '"' . $this->esc_csv($result['iil']) . '",';
                $csv_content .= '"' . $this->esc_csv($result['juice']) . '"';
                $csv_content .= $new_line;		

            }
                        
        }else{
            return false;
        }

        echo $csv_content;
        die();
        
    }
    
    /*
     * Generates the downloadable CSV file with all the items associated with a
     * specificd link available in the Juice menu
     */
    private function anchors_menu_export_csv(){

        //verify capability
        if(!current_user_can(get_option( $this->shared->get('slug') . "_juice_menu_required_capability"))){die();}
        
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
        
        //get the URL
        $url = urldecode($_POST['anchors_url']);
        
        //get the data from the db table
        global $wpdb;
        $table_name = $wpdb->prefix . $this->shared->get('slug') . "_anchors";
        $safe_sql = $wpdb->prepare("SELECT * FROM $table_name WHERE url = %s ORDER BY id DESC", $url);
        $results = $wpdb->get_results($safe_sql, ARRAY_A);
        
        //if there are data generate the csv header and content
        if( count( $results ) > 0 ){
            
            $csv_content = '';
            $new_line = "\n";

            //set the csv header
            header('Content-Encoding: UTF-8');
            header('Content-type: text/csv; charset=UTF-8');
            header("Content-Disposition: attachment; filename=juice-details-" . time() . ".csv");
            header("Pragma: no-cache");
            header("Expires: 0");

            //set headings
            $csv_content .= '"Post ",';
            $csv_content .= '"' . $this->esc_csv(__('Link URL', 'daim')) . '",';
            $csv_content .= '"' . $this->esc_csv(__('Link Juice', 'daim')) . '",';
            $csv_content .= '"' . $this->esc_csv(__('Link Content', 'daim')) . '"';
            $csv_content .= $new_line;			
            
            //set column content
            foreach ( $results as $result ) {

                $csv_content .= '"' . $this->esc_csv($result['post_title']) . '",';
                $csv_content .= '"' . $this->esc_csv($result['url']) . '",';
                $csv_content .= '"' . $this->esc_csv($result['juice']) . '",';
                $csv_content .= '"' . $this->esc_csv($result['anchor']) . '"';
                $csv_content .= $new_line;		

            }
                        
        }else{
            return false;
        }

        echo $csv_content;
        die();
        
    }
    
    /*
     * Generates the downloadable CSV file with all the items available in the
     * Hits menu
     */
    private function hits_menu_export_csv(){

        //verify capability
        if(!current_user_can(get_option( $this->shared->get('slug') . "_hits_menu_required_capability"))){die();}
        
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
        
        //get the data from the db table
        global $wpdb;
        $table_name = $wpdb->prefix . $this->shared->get('slug') . "_hits";
        $results = $wpdb->get_results("SELECT * FROM $table_name ORDER BY date DESC", ARRAY_A);
        
        //if there are data generate the csv header and content
        if( count( $results ) >0 ){
            
            $csv_content = '';
            $new_line = "\n";

            //set the csv header
            header('Content-Encoding: UTF-8');
            header('Content-type: text/csv; charset=UTF-8');
            header("Content-Disposition: attachment; filename=hits-" . time() . ".csv");
            header("Pragma: no-cache");
            header("Expires: 0");

            //set headings
            $csv_content .= '"' . $this->esc_csv(__('Post', 'daim')) . '",';
            $csv_content .= '"' . $this->esc_csv(__('Date', 'daim')) . '",';
            $csv_content .= '"' . $this->esc_csv(__('Target', 'daim')) . '",';
            $csv_content .= '"' . $this->esc_csv(__('Type', 'daim')) . '"';
            $csv_content .= $new_line;			
            
            //set column content
            foreach ( $results as $result ) {

                $csv_content .= '"' . $this->esc_csv($result['post_title']) . '",';
                $csv_content .= '"' . $this->esc_csv(mysql2date( get_option('date_format') , $result['date'] )) . '",';
                $csv_content .= '"' . $this->esc_csv(stripslashes($result['target_url'])) . '",';
                $csv_content .= '"' . $this->esc_csv( $result['link_type'] == 0 ? 'AIL' : 'MIL' ) . '"';
                $csv_content .= $new_line;		

            }
                        
        }else{
            return false;
        }

        echo $csv_content;
        die();
        
    }
    
    /*
     * Escape the double quotes of the $content string, so the returned string
     * can be used in CSV fields enclosed by double quotes
     * 
     * @param $content The unescape content ( Ex: She said "No!" )
     * @return string The escaped content ( Ex: She said ""No!"" )
     */
    private function esc_csv($content){
        return str_replace('"', '""', $content);
    }
    
}