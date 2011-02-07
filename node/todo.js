var express = require('express');
var http = require('http');

var app = express.createServer();
app.use(express.bodyDecoder());

app.set('views', __dirname + '/views');
app.set('view engine', 'jade');

var rb = require(__dirname + '/rb.js');
// rb.init(http, 'www', 80, '/nxt/db/');
rb.init(http, '127.0.0.1', 6656, '/');

app.post('/todo/create', function(req, res) {
	// add a todo
	var params = [
		{"description" : req.body.dscr}
	];
	var ms = Date.now();	
	rb.send("todo:store", params, function(result) {
		console.log("Request /todo/create took " + (Date.now() - ms) + " ms");
		res.redirect('/');
	});
});

app.post('/todo/delete', function(req, res) {
	// remove a todo
	var ms = Date.now();
	rb.send("todo:trash", [req.body.id], function(result) {
		console.log("Request /todo/delete took " + (Date.now() - ms) + " ms");
		res.redirect('/');
	});
});

app.get('/', function(req, res) {
	var ms = Date.now();
	rb.send("todo:getList", null, function(todoList) {
		console.log("Request / took " + (Date.now() - ms) + " ms");
		res.render('todos', { todos : todoList });
	});
});

app.listen(3000);
console.log('Express app started on port 3000');