
function onOpened() {
	console.log("Connection opened.");
}

function onMessage(m) {
	var newState = JSON.parse(m.data);
	console.log(newState);
	var requestUrl = location.origin + '/wespot_arlearn/update?runid=' + newState.runId;
	console.log('Requesting for an update: ' + requestUrl + '.');
	$.get(requestUrl, function( data ) {
		console.log("Update successfully triggered.");
		console.log(data);
		// TODO better just to load the new element.
		// TODO reload when it is only in the same game!
		// window.location.reload();
	}).fail(function() {
		console.error('The runId ' + m.runId + ' could not be updated.');
  	});
}

function onError(err) {
    console.error("Error received in the channel.");
    console.error(err)
}

function onClose() {
    // alert("close");
    //   connected = false;
    console.log("Closing connection.");
}


$(function() {
	console.log("Creating channel...");
	channel = new goog.appengine.Channel(clientToken);
	socket = channel.open();
	socket.onopen = onOpened;
	socket.onmessage = onMessage;
	socket.onerror = onError;
	socket.onclose = onClose;
    console.log( "ready!" );
});
