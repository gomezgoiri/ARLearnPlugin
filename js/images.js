function setOrientation(el) {
	if (el.width()<el.height()) { // Portrait images!
		el.addClass("portrait");
	} else {
		el.addClass("landscape");
	}
}

$(function() {
	$(".elgg-list .elgg-body img").load(function() {
		setOrientation($(this));
	});

	$(".elgg-list .elgg-body video").load(function() {
		setOrientation($(this));
	});
});
