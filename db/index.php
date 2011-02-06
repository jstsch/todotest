<?php
require("lib/rb.php"); 
R::setup();
class Model_Todo extends RedBean_SimpleModel {
	public function getList() {
		return R::findAndExport("todo");
	}
}
$beancan = new RedBean_BeanCan;
if (isset($_POST["json"])) die($beancan->handleJSONRequest( $_POST["json"] ));
?>