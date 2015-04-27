function getOrientation(width, height) {
	if (width < height)
		return "portrait";
	return "landscape";
}


$(function() {
	$(".elgg-list .elgg-body img").one("load", function() {
		var orientation = getOrientation($(this).width(), $(this).height());
		$(this).addClass(orientation);
	}).each(function() {
		// To force setting the orientation even when the image is cached.
		// (see http://stackoverflow.com/questions/3877027/jquery-callback-on-image-load-even-when-the-image-is-cached)
		if(this.complete) $(this).load();
	});

	$(".elgg-list .elgg-body video").each(function() {
		$(this).addClass("temporary");  // Added because Firefox sometimes does not seem to be ALWAYS calling this function :-S
		$(this).bind("loadedmetadata", function() { //  loadedmetadata loadstart
			var orientation = getOrientation(this.videoWidth, this.videoHeight);
			/*console.log("Orientation: " + orientation);*/
			$(this).removeClass("temporary");
			$(this).addClass(orientation);
		});
	});

	$(".fancybox").fancybox({
		/* None of these options seem to work in Safari :-S
		height: $(window).height()*0.8,*/
		afterShow: function() {
			var innerVideo = $(".fancybox-inner video");
			if (innerVideo.hasClass("portrait")) {
				// Adjusting portrait images' height
				innerVideo.css("height", $(window).height()*0.8); //$(".fancybox-inner").height());
				$.fancybox.update(); // Auto-resize to match content size. 
			}
		}
	});
});
