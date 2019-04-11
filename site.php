<?php

use \Hcode\Page;
use \Hcode\Model\Product;
use \Hcode\Model\Category;

$app->get('/', function() {

	$products = Product::listAll();

	$page = new Page();

	$page->setTpl('index', [
		'products'=>Product::checkList($products)
	]);

});

$app->get('/categories/:idcategory', function($idcategory) {

	$nrPage = (isset($_GET['nrPage'])) ? (int)$_GET['nrPage'] : 1;

	$category = new Category();

	$category->get((int)$idcategory);

	$pagination = $category->getProductsPage($nrPage);

	$pages = [];

	for ($i=1; $i <= $pagination['nrPages']; $i++) { 
		array_push($pages, [
			'link'=>"/categories/".$category->getidcategory()."?page=".$i,
			'page'=>$i
		]);
	}

	$page = new Page();

	$page->setTpl("category", [
		'category'=>$category->getValues(),
		'products'=>$pagination['data'],
		'pages'=>$pages
	]);

});

$app->get("/products/:desurl", function($desurl) {

	$product = new Product();

	$product->getFromURL($desurl);

	$page = new Page();

	$page->setTpl("product-detail", [
		'product'=>$product->getValues(),
		'categories'=>$product->getCategories()
	]);
});

?>