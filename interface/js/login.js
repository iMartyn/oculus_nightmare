jQuery(document).ready(function () {
    jQuery("#login").on("submit",function(sender) {
        var this_location = document.location.toString();
        var base_url = this_location.replace(/\/interface.*/,'');
        var name=jQuery("#loginame").val();
        var action_url = base_url + '/server/?command=' + "login";
        jQuery.get(action_url+"&name="+name);
    });
});
