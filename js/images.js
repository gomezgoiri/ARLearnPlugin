function setOrientation(el) {
	if (el.width() < el.height()) { // Portrait images!
		el.addClass("portrait");
	} else {
		el.addClass("landscape");
	}
}

$(function() {
	$(".elgg-list .elgg-body img").each(function() {
		setOrientation($(this));
	});

	$(".elgg-list .elgg-body video").each(function() {
		setOrientation($(this));
	});
});
