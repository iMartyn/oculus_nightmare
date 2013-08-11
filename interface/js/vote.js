
jQuery(document).ready(function () {
    jQuery(".action-butt").click(function (sender) {
	console.log(jQuery(this).attr("id")[5]);
    });
});
