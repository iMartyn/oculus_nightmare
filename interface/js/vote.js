
function command_url(command) {
    var this_location = document.location.toString();
    var base_url = this_location.replace(/\/interface.*/,'');
    return base_url + '/server/?command=' + command;
}

var game_id = 0;

jQuery(document).ready(function () {
    jQuery(".action-butt").click(function (sender) {
	var voteid=jQuery(this).attr("id")[5];
	var userid = myuserdata.id.toString()
	jQuery.getJSON(command_url("client_vote") + "&user_id=" + userid + 
	  "&game_id=" + game_id + "&vote=" + voteid, function(data) {
	      // Don't care.
	});
    });
});
