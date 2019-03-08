jQuery(document).ready(function($) {

    /*
     * ajax request that generates a list of interlinks suggestion in the
     * "Interlinks Suggestions" meta box
     */
    $('#generate-ideas').click(function(){
       
        //if another request is processed right now do not proceed with another ajax request
        if($('#ajax-request-status').val() == 'processing'){return;}
       
        //get the post id for which the suggestions should be generated
        var post_id = parseInt($(this).attr('data-post-id'), 10);
        
        //prepare ajax request
        var data = {
            "action": "generate_interlinks_suggestions",
            "security": daim_nonce,
            "post_id": post_id
        };

        //show the spinner
        $('#daim-meta-suggestions .spinner').css('visibility', 'visible');
        
        //set the ajax request status
        $('#ajax-request-status').val('processing');

        //send ajax request
        $.post(daim_ajax_url, data, function(list_content) {
        
            //show the new suggestions based on the xml response
            $('#daim-interlinks-suggestions-list').empty().append(list_content).show();
            
            //hide the spinner
            $('#daim-meta-suggestions .spinner').css('visibility', 'hidden');
            
            //set the ajax request status
            $('#ajax-request-status').val('inactive'); 
        
        });
        
    });

  /**
   * Here the wp.data API is used to detect when a post is modified and the Interlinks Optimization meta-box needs to be
   * updated.
   *
   * Note that the update of the Interlinks Optimization meta-box is performed only if:
   *
   * - The Gutenberg editor is available. (wp.blocks is checked against undefined)
   * - The Interlinks Optimization meta-box is present in the DOM (because in specific post types or when the user
   *   doesn't have the proper capability too see it it's not available)
   *
   * References:
   *
   * - https://github.com/WordPress/gutenberg/issues/4674#issuecomment-404587928
   * - https://wordpress.org/gutenberg/handbook/packages/packages-data/
   * - https://www.npmjs.com/package/@wordpress/data
   */
  if(typeof wp.blocks !== 'undefined' && //Verify if the Gutenberg editor is available
    $('#daim-meta-optimization').length > 0){ //Verify if the Interlinks Optimization meta-box is present in the DOM

    var lastModified = '';

    var unssubscribe = wp.data.subscribe(function(){

      var postId = wp.data.select('core/editor').getCurrentPost().id;
      var postModifiedIsChanged = false;

      if(typeof wp.data.select('core/editor').getCurrentPost().modified !== 'undefined' &&
          wp.data.select('core/editor').getCurrentPost().modified !== lastModified){
        lastModified = wp.data.select('core/editor').getCurrentPost().modified;
        postModifiedIsChanged = true;
      }

      /**
       * Update the Interlinks Optimization meta-box if:
       *
       * - The post has been saved
       * - This is not an not an autosave
       * - The "lastModified" flag used to detect if the post "modified" date has changed is set to true
       */
        if(wp.data.select('core/editor').isSavingPost() &&
          !wp.data.select('core/editor').isAutosavingPost() &&
            postModifiedIsChanged === true
          ) {
            updateInterlinksOptimizationMetaBox(postId);
        }

    });

  }

  /**
   * Updates the Interlinks Optimization meta-box content.
   *
   * @param post_id The id of the current post
   */
  function updateInterlinksOptimizationMetaBox(post_id){

    //prepare ajax request
    var data = {
      "action": "generate_interlinks_optimization",
      "security": daim_nonce,
      "post_id": post_id
    };

    //send ajax request
    $.post(daim_ajax_url, data, function(html_content) {

      //update the content of the meta-box
      $('#daim-meta-optimization td').empty().append(html_content);

    });

  }

});