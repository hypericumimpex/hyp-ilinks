(function($) {

  'use strict';

  $(document).ready(function() {

    'use strict';

    //initialize chosen on all the select elements
    var chosenElements = [];

    //Interlinks Options Meta Box --------------------------------------------------------------------------------------
    addToChosen('daim-enable-ail');

    //Dashboard Menu ---------------------------------------------------------------------------------------------------
    addToChosen('op');
    addToChosen('sb');
    addToChosen('or');

    //AIL Menu ---------------------------------------------------------------------------------------------------------
    addToChosen('left-boundary');
    addToChosen('right-boundary');
    addToChosen('case-insensitive-search');
    addToChosen('open-new-tab');
    addToChosen('use-nofollow');

    //Options Menu -----------------------------------------------------------------------------------------------------

    //AIL
    addToChosen('daim_default_string_before');
    addToChosen('daim_default_string_after');
    addToChosen('daim_default_case_insensitive_search');
    addToChosen('daim_default_open_new_tab');
    addToChosen('daim_default_use_nofollow');

    //Suggestions
    addToChosen('daim_suggestions_titles');
    addToChosen('daim_suggestions_categories');
    addToChosen('daim_suggestions_tags');
    addToChosen('daim_suggestions_post_type');

    //Juice
    addToChosen('daim_remove_link_to_anchor');
    addToChosen('daim_remove_url_parameters');

    //Tracking
    addToChosen('daim_track_internal_links');

    //Analysis
    addToChosen('daim_set_max_execution_time');
    addToChosen('daim_set_memory_limit');

    //Advanced
    addToChosen('daim_default_enable_ail_on_post');
    addToChosen('daim_ail_test_mode');
    addToChosen('daim_random_prioritization');
    addToChosen('daim_ignore_self_ail');
    addToChosen('daim_general_limit_mode');
    addToChosen('daim-protected-tags');
    addToChosen('daim-protected-gutenberg-blocks');
    addToChosen('daim_pagination_dashboard_menu');
    addToChosen('daim_pagination_juice_menu');
    addToChosen('daim_pagination_hits_menu');
    addToChosen('daim_pagination_ail_menu');

    $(chosenElements.join(',')).chosen({
      placeholder_text_multiple: window.objectL10n.chooseAnOptionText,
    });

    function addToChosen(elementId) {

      if ($('#' + elementId).length && chosenElements.indexOf($('#' + elementId)) === -1) {
        chosenElements.push('#' + elementId);
      }

    }

  });

})(window.jQuery);