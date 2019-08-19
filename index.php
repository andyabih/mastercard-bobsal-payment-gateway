<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$price = (float) $_GET['price'];
$id = $_GET['id'];
$returnUrl = $_GET['return'];

function createSession($oid, $price, $returnUrl) {
    $post = array(
        'apiOperation' => 'CREATE_CHECKOUT_SESSION',
        'order' => array(
            'id' => $oid,
            'amount' => $price,
            'currency' => 'USD'
        ),
        'interaction' => array(
            'returnUrl' => $returnUrl
        )
    );
  

    require __DIR__ . '/session.php';
}

createSession($id, $price, $returnUrl);