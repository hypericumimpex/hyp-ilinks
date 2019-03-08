<?php

        if ( !current_user_can( 'manage_options' ) )  {
            wp_die( esc_attr__( 'You do not have sufficient capabilities to access this page.' ) );
        }

        ?>

        <div class="wrap">
            
            <h2>Interlinks Manager - Options</h2>

            <?php

            //settings errors
            if( isset($_GET['settings-updated']) and $_GET['settings-updated'] == 'true' ){
                settings_errors();
            }

            ?>

            <div id="daext-options-wrapper">

                <?php
                //get current tab value
                $active_tab = isset( $_GET[ 'tab' ] ) ? $_GET[ 'tab' ] : 'ail_options';
                ?>

                <div class="nav-tab-wrapper">
                    <a href="?page=daim-options&tab=ail_options" class="nav-tab <?php echo $active_tab == 'ail_options' ? 'nav-tab-active' : ''; ?>"><?php esc_attr_e('AIL', 'daim'); ?></a>
                    <a href="?page=daim-options&tab=suggestions_options" class="nav-tab <?php echo $active_tab == 'suggestions_options' ? 'nav-tab-active' : ''; ?>"><?php esc_attr_e('Suggestions', 'daim'); ?></a>
                    <a href="?page=daim-options&tab=optimization_options" class="nav-tab <?php echo $active_tab == 'optimization_options' ? 'nav-tab-active' : ''; ?>"><?php esc_attr_e('Optimization', 'daim'); ?></a>
                    <a href="?page=daim-options&tab=juice_options" class="nav-tab <?php echo $active_tab == 'juice_options' ? 'nav-tab-active' : ''; ?>"><?php esc_attr_e('Juice', 'daim'); ?></a>
                    <a href="?page=daim-options&tab=tracking_options" class="nav-tab <?php echo $active_tab == 'tracking_options' ? 'nav-tab-active' : ''; ?>"><?php esc_attr_e('Tracking', 'daim'); ?></a>
                    <a href="?page=daim-options&tab=analysis_options" class="nav-tab <?php echo $active_tab == 'analysis_options' ? 'nav-tab-active' : ''; ?>"><?php esc_attr_e('Analysis', 'daim'); ?></a>
                    <a href="?page=daim-options&tab=metaboxes_options" class="nav-tab <?php echo $active_tab == 'metaboxes_options' ? 'nav-tab-active' : ''; ?>"><?php esc_attr_e('Meta Boxes', 'daim'); ?></a>
                    <a href="?page=daim-options&tab=capabilities_options" class="nav-tab <?php echo $active_tab == 'capabilities_options' ? 'nav-tab-active' : ''; ?>"><?php esc_attr_e('Capabilities', 'daim'); ?></a>
                    <a href="?page=daim-options&tab=advanced_options" class="nav-tab <?php echo $active_tab == 'advanced_options' ? 'nav-tab-active' : ''; ?>"><?php esc_attr_e('Advanced', 'daim'); ?></a>
                </div>

                <form method='post' action='options.php' autocomplete="off">

                    <?php

                    if( $active_tab == 'ail_options' ) {

                            settings_fields( $this->shared->get('slug') . '_ail_options' );
                            do_settings_sections( $this->shared->get('slug') . '_ail_options' );

                    }
                    
                    if( $active_tab == 'suggestions_options' ) {

                            settings_fields( $this->shared->get('slug') . '_suggestions_options' );
                            do_settings_sections( $this->shared->get('slug') . '_suggestions_options' );

                    }
                    
                    if( $active_tab == 'optimization_options' ) {

                        settings_fields( $this->shared->get('slug') . '_optimization_options' );
                        do_settings_sections( $this->shared->get('slug') . '_optimization_options' );

                    }

                    if( $active_tab == 'juice_options' ) {

                        settings_fields( $this->shared->get('slug') . '_juice_options' );
                        do_settings_sections( $this->shared->get('slug') . '_juice_options' );

                    }
                    
                    if( $active_tab == 'tracking_options' ) {

                            settings_fields( $this->shared->get('slug') . '_tracking_options' );
                            do_settings_sections( $this->shared->get('slug') . '_tracking_options' );

                    }
                    
                    if( $active_tab == 'analysis_options' ) {

                            settings_fields( $this->shared->get('slug') . '_analysis_options' );
                            do_settings_sections( $this->shared->get('slug') . '_analysis_options' );

                    }
                    
                    if( $active_tab == 'metaboxes_options' ) {

                            settings_fields( $this->shared->get('slug') . '_metaboxes_options' );
                            do_settings_sections( $this->shared->get('slug') . '_metaboxes_options' );

                    }
                    
                    if( $active_tab == 'capabilities_options' ) {

                            settings_fields( $this->shared->get('slug') . '_capabilities_options' );
                            do_settings_sections( $this->shared->get('slug') . '_capabilities_options' );

                    }

                    if( $active_tab == 'advanced_options' ) {

	                    settings_fields( $this->shared->get('slug') . '_advanced_options' );
	                    do_settings_sections( $this->shared->get('slug') . '_advanced_options' );

                    }

                    ?>

                    <div class="daext-options-action">
                        <input type="submit" name="submit" id="submit" class="button" value="<?php esc_attr_e('Save Changes', 'daim'); ?>">
                    </div>

                </form>

            </div>

        </div>