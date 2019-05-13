<?php

use \Hcode\Page;
use \Hcode\Model\User;


// LOGIN AND LOGOUT

$app->get("/login", function() {

	$page = new Page();

	$page->setTpl("login", [
		'loginError'=>User::getLoginError(),
		'registerError'=>User::getRegisterError(),
		'registerValues'=>isset($_SESSION['registerValues']) ? $_SESSION['registerValues'] : 
			['name'=>'', 'email'=>'', 'phone'=>'']
	]);

});

$app->post("/login", function() {

	try {

		User::login($_POST['login'], $_POST['password']);

	} catch (Exception $e) {

		User::setLoginError($e->getMessage());

	}
	
	header("Location: /checkout");
	exit;

});

$app->get("/logout", function() {

	User::logout();

	header("Location: /login");
	exit;

});

$app->post("/register", function() {

	$_SESSION['registerValues'] = $_POST;

	if(!isset($_POST['name']) || $_POST['name'] == '') {

		User::setRegisterError("Preencha seu nome.");
		header("Location: /login");
		exit;
	}

	if(!isset($_POST['email']) || $_POST['email'] == '') {

		User::setRegisterError("Preencha seu email.");
		header("Location: /login");
		exit;
	}

	if(!isset($_POST['password']) || $_POST['password'] == '') {

		User::setRegisterError("Preencha sua senha");
		header("Location: /login");
		exit;
	}

	if(User::checkLoginExists($_POST['email']) === true) {

		User::setRegisterError("Já existe um usuário cadastrado com este e-mail.");
		header("Location: /login");
		exit;
	}

	$user = new User();

	$user->setData([
		'inadmin'=>0,
		'deslogin'=>$_POST['email'],
		'desperson'=>$_POST['name'],
		'desemail'=>$_POST['email'],
		'despassword'=>$_POST['password'],
		'nrphone'=>$_POST['phone']
	]);

	$user->save();

	User::login($_POST['email'], $_POST['password']);

	$_SESSION['registerValues'] = NULL;

	header("Location: /checkout");
	exit;

});


// FORGOT PASSWORD FUNCTIONS

$app->get('/forgot', function() {
    
	$page = new Page();

	$page->setTpl("forgot");

});

$app->post("/forgot", function () {

	$user = User::getForgot($_POST["email"], false);

	header("Location: /forgot/sent");
	exit;
});

$app->get("/forgot/sent", function() {

	$page = new Page();

	$page->setTpl("forgot-sent");

});

$app->get("/forgot/reset", function() {

	$user = User::validForgotDecrypt($_GET["code"]);

	$page = new Page();

	$page->setTpl("forgot-reset", array(
		"name"=>$user["desperson"],
		"code"=>$_GET["code"]
	));

});

$app->post("/forgot/reset", function() {

	$forgot = User::validForgotDecrypt($_POST["code"]);
	
	User::setForgotUsed($forgot["idrecovery"]);

	$user = new User();

	$user->get((int)$forgot["iduser"]);

	$user->setPassword($_POST["password"]);

	$page = new Page();

	$page->setTpl("forgot-reset-success");

});

?>