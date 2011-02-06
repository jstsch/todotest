var http;
var host;
var port;
var path;

exports.init = function(_http, _host, _port, _path) {
	http = _http;
	host = _host;
	port = _port;
	path = _path;
}

exports.send = function(method, params, callback) {
	var data = { jsonrpc : "2.0", id : "unused"};
	data.method = method;
	if (params) data.params = params;
	data = "json=" + JSON.stringify(data);
	var options = {
		host : host,
		port: port,
		path: path,
		method: 'POST',
		headers: {
			'Content-Length' : data.length,
			'Content-Type' : 'application/x-www-form-urlencoded',
		}
	}
	
	var dbPost = http.request(options, function(res) {
		res.setEncoding('utf8');
		var buffer = '';
		res.on('data', function(chunk) {
			buffer += chunk;
		});
		res.on('end', function() {
			// TODO check errors
			var result = JSON.parse(buffer).result;
			var items = [];
			for (var i in result) {
				items.push(result[i]);
			}
			callback(items);
		});
	});

	dbPost.write(data);
	dbPost.end();
}