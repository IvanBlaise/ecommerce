<?php 

require_once("vendor/autoload.php");

use \Slim\Slim;
use \Hcode\Page;

$app = new Slim();

$app->config('debug', true);

$app->get('/', function() {
    
	$page = new Page();
	
	$page->setTpl("index");

	/*$sql = new Hcode\DB\Sql();
	$param =[
		":ID"=>1
	];
	$results = $sql->select("select * from tb_users where iduser = :ID", $param);

	echo json_encode($results);*/



});

$app->run();

 ?>