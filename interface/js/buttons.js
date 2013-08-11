function command_url(command) {
    var this_location = document.location.toString();
    var base_url = this_location.replace(/\/interface.*/,'');
    return base_url + '/server/?command=' + command;
}

jQuery(document).ready(function () {
    jQuery("i").click(function(sender) {
	var command = jQuery(sender.delegateTarget).attr('id');
        var action_url = command_url(command);
        jQuery.get(action_url);
    });
});
