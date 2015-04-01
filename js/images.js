function getOrientation(width, height) {
	if (width < height)
		return "portrait";
	return "landscape";
}


$(function() {
	$(".elgg-list .elgg-body img").load(function() {
		var orientation = getOrientation($(this).width(), $(this).height());
		$(this).addClass(orientation);
	});

	$(".elgg-list .elgg-body video").each(function() {
		$(this).bind("loadedmetadata", function() { //  loadedmetadata loadstart
			var proportion = this.videoHeight / this.videoWidth
			var orientation = getOrientation(this.videoWidth, this.videoHeight);
			/*console.log("Orientation: " + orientation);*/
			$(this).addClass(orientation);
		});
	});
});
