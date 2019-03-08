jQuery(document).ready(function($) {

    $('#update-archive').click(function(){
       
        //if another request is processed right now do not proceed with another ajax request
        if($('#ajax-request-status').val() == 'processing'){return;}       
       
        //prepare ajax request
        var data = {
            "action": "update_juice_archive",
            "security": daim_nonce
        };

        //show the ajax loader
        $('#ajax-loader').show();

        //set the ajax request status
        $('#ajax-request-status').val('processing');

        //send ajax request
        $.post(daim_ajax_url, data, function(data) {
        
            //reload the dashboard menu ----------------------------------------
            window.location.replace(daim_admin_url + 'admin.php?page=daim-juice');
        
        });
        
    });

});