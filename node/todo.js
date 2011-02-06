var express = require('express');
var http = require('http');

var app = express.createServer();
app.use(express.bodyDecoder());

app.set('views', __dirname + '/views');
app.set('view engine', 'jade');

var rb = require(__dirname + '/rb.js');
rb.init(http, 'www', 80, '/nxt/db/');

app.post('/todo/create', function(req, res) {
	// add a todo
	var params = [
		{"description" : req.body.dscr}
	];
	
	rb.send("todo:store", params, function(result) {
		res.redirect('/');
	});
});

app.post('/todo/delete', function(req, res) {
	// remove a todo
	rb.send("todo:trash", [req.body.id], function(result) {
		res.redirect('/');
	});
});

app.get('/', function(req, res) {
	rb.send("todo:getList", null, function(todoList) {
		res.render('todos', { todos : todoList });
	});
});

app.listen(3000);
console.log('Express app started on port 3000');