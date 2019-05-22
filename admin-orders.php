<?php

use \Hcode\PageAdmin;
use \Hcode\Model\User;
use \Hcode\Model\Order;
use \Hcode\Model\OrderStatus;
use \Hcode\Model\Cart;


$app->get('/admin/orders/:idorder/delete', function ($idorder){

	User::verifyLogin();

	$order = new Order();

	$order->get((int)$idorder);

	$order->delete();

	$page = new PageAdmin();

	$page->setTpl("order-status", [
		'order'=>$order->getValues(),
		'status'=>OrderStatus::listAll()
	]);

});

$app->get('/admin/orders/:idorder/status', function ($idorder){

	User::verifyLogin();

	$order = new Order();

	$order->get((int)$idorder);

	$page = new PageAdmin();

	$page->setTpl("order-status",[
		'order'=>$order->getValues(),
		'status'=>OrderStatus::listAll(),
		'msgError'=>Order::getError(),
		'msgSuccess'=>Order::getSuccess()
	]);

});

$app->post('/admin/orders/:idorder/status', function ($idorder){

	User::verifyLogin();

	if (!isset($_POST['idstatus']) || !(int)$_POST['idstatus'] > 0) {
		Order::setError("Informe o status atual.");
		header("Location: /admin/orders/$idorder/status");
		exit;
	}

	$order = new Order();

	$order->get((int)$idorder);

	$order->setidstatus($_POST['idstatus']);

	$order->save();
	
	Order::setSuccess("Status da ordem alterado com sucesso.");
	
	header("Location: /admin/orders/$idorder/status");
	exit;


});

$app->get('/admin/orders/:idorder', function ($idorder){

	User::verifyLogin();

	$order = new Order();

	$order->get((int)$idorder);

	$cart = new Cart();

	$cart->get((int)$order->getidcart());

	$page = new PageAdmin();

	$page->setTpl("order", [
		'order'=>$order->getValues(),
		'cart'=>$cart->getValues(),
		'products'=>$cart->getProducts()
	]);

});

$app->get('/admin/orders', function (){

	User::verifyLogin();

	$page = new PageAdmin();

	$page->setTpl("orders", [
		'orders'=>Order::listAll()
	]);

});




?>