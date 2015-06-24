function onInputChange(elId, maxSizeLimit) {
	$(elId).bind("change", function() {
		var fileSize = this.files[0].size;
		if (fileSize>maxSizeLimit) {
			elgg.register_error(elgg.echo("actiongatekeeper:uploadexceeded"));
		}
	});
}
