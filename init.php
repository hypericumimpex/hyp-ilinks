<?php
/*
Plugin Name: HYP Interlinks
Description: Manages the internal links...
Version: 1.21
Author: Romeo C.
Author URI: https://github.com/hypericumimpex/hyp-ilinks
*/

//Prevent direct access to this file
if ( ! defined( 'WPINC' ) ) { die(); }

//Class shared across public and admin
require_once( plugin_dir_path( __FILE__ ) . 'shared/class-daim-shared.php' );

//Public
require_once( plugin_dir_path( __FILE__ ) . 'public/class-daim-public.php' );
add_action( 'plugins_loaded', array( 'Daim_Public', 'get_instance' ) );

//Admin
if ( is_admin() && ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX ) ) {
    
    //Admin
    require_once( plugin_dir_path( __FILE__ ) . 'admin/class-daim-admin.php' );
    add_action( 'plugins_loaded', array( 'Daim_Admin', 'get_instance' ) );
    
    //Activate
    register_activation_hook( __FILE__, array( Daim_Admin::get_instance(), 'ac_activate' ) );
    
}

//Ajax
if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
    
    //Admin
    require_once( plugin_dir_path( __FILE__ ) . 'class-daim-ajax.php' );
    add_action( 'plugins_loaded', array( 'Daim_Ajax', 'get_instance' ) );
    
}