jQuery(document).ready(function () {
    jQuery("i").click(function(sender) {
        var this_location = document.location.toString();
        var base_url = this_location.replace(/\/interface.*/,'');
        var command = jQuery(sender.target).attr('id');
        var action_url = base_url + '/server/?command=' + command;
        jQuery.get(action_url);
    });
});