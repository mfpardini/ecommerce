<?php

use \Hcode\Page;
use \Hcode\Model\Product;
use \Hcode\Model\Category;
use \Hcode\Model\Cart;
use \Hcode\Model\Address;
use \Hcode\Model\User;

$app->get('/', function() {

	$products = Product::listAll();

	$page = new Page();

	$page->setTpl('index', [
		'products'=>Product::checkList($products)
	]);

});



$app->get("/cart", function() {

	$cart = Cart::getFromSession();

	$page = new Page();

	$page->setTpl("cart", [
		'cart'=>$cart->getValues(),
		'products'=>$cart->getProducts(),
		'error'=>Cart::getMsgError()
	]);
});


$app->get("/cart/:idproduct/add", function($idproduct) {

	$product = new Product();
	
	$product->get((int)$idproduct);

	$cart = Cart::getFromSession();

	$qtd = (isset($_GET['quantity'])) ? (int)$_GET['quantity'] : 1;

	for ($i=0; $i < $qtd; $i++) { 
	
		$cart->addProduct($product);
	}

	header("Location: /cart");
	exit;

});

$app->get("/cart/:idproduct/minus", function($idproduct) {

	$product = new Product();
	
	$product->get((int)$idproduct);

	$cart = Cart::getFromSession();

	$cart->removeProduct($product);

	header("Location: /cart");
	exit;

});

$app->get("/cart/:idproduct/remove", function($idproduct) {

	$product = new Product();
	
	$product->get((int)$idproduct);

	$cart = Cart::getFromSession();

	$cart->removeProduct($product, true);

	header("Location: /cart");
	exit;

});

$app->post("/cart/freight", function() {

	$cart = Cart::getFromSession();

	$cart->setFreight($_POST['zipcode']);

	header("Location: /cart");
	exit;

});

$app->get("/checkout", function() {

	User::verifyLogin(false);

	$address = new Address();
	$cart = Cart::getFromSession();

	if (!isset($_GET['zipcode'])) {

		$zipcode = $cart->getdeszipcode();
		header("Location: /checkout?zipcode=$zipcode");
		exit;
	}

	$address->loadFromCEP($_GET['zipcode']);

	$cart->setdeszipcode($_GET['zipcode']);

	$cart->save();

	$cart->getCalculateTotal();

	$page = new Page();

	$page->setTpl("checkout", [
		'cart'=>$cart->getValues(),
		'address'=>$address->getValues(),
		'products'=>$cart->getProducts(),
		'error'=>Address::getMsgError()
	]);
});

$app->post("/checkout", function() {

	User::verifyLogin(false);

	if (!isset($_POST['zipcode']) || $_POST['zipcode'] === '') {
		Address::setMsgError("Informe o CEP.");
		header("Location: /checkout");
		exit;
	}

	$address = new Address();

	$user = User::getFromSession();

	$_POST['deszipcode'] = $_POST['zipcode'];
	$_POST['idperson'] = $user->getidperson();

	$address->setData($_POST);

	$address->save();

	header("Location: /order");
	exit;

});


?>