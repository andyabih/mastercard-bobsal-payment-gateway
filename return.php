<?php
session_start();

$resultIndicator = $_GET['resultIndicator'];
$sessionVersion = $_GET['sessionVersion'];
$successIndicator = $_SESSION['successIndicator'];

	function sendMessage($message, $playerIds = array()){

    $content = array(
			"en" => $message
			);

		$fields = array(
			'app_id' => "dd5f73bf-5272-4c0f-a077-5bf7a42fa92b",
			'include_player_ids' => $playerIds,
			'data' => array("foo" => "bar"),
			'contents' => $content
		);

		$fields = json_encode($fields);
    	// print("\nJSON sent:\n");
    	// print($fields);

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, "https://onesignal.com/api/v1/notifications");
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json; charset=utf-8'));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($ch, CURLOPT_HEADER, FALSE);
		curl_setopt($ch, CURLOPT_POST, TRUE);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);

		$response = curl_exec($ch);
		curl_close($ch);

		// echo "<pre>";
		//  print_r($response);
		// echo "</pre>";
		return $response;


	}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>XXXX - Payment</title>
</head>
 <body>
<?php   

if($resultIndicator == $successIndicator) {
	$bid = $_GET['bid'];

	require __DIR__ . '/../libs/db.php';
	$db = new Db();
  $r = $db->getRow(array('id' => $bid), 'booking_requests');
  $l = $db->getRow(array('id' => $r['listing_id']), 'listings');
  $u = $db->getRow(array('id' => $l['account_id']), 'accounts');
  $oid = $u['onesignal_id'];
  
	$db->updateValue(array('status' => 3), array('id' => $bid), 'booking_requests');
	
  sendMessage("Payment for booking #{$bid} has been successully made.", array($oid));
	echo "<div style=\"color: green; font-family: 'Helvetica', 'Arial', sans-serif; text-align: center; padding: 50px 0; font-weight: bold;\">Your Payment is Successfully Confirmed and Validated</div>";
} else
	echo "<div style=\"color: red; font-family: 'Helvetica', 'Arial', sans-serif; text-align: center; padding: 50px 0; font-weight: bold;\">Your Payment info have been Breached! Order Canceled!</div>";


// remove all session variables
session_unset(); 

// destroy the session 
session_destroy();
?>
