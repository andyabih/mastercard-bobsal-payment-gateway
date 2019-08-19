<?php

/* Main controller page

1. Create 1 MerchantConfiguration object for each merchant ID
2. Create 1 Parser object
3. Call Parser object FormRequest method to form the request that will be sent to the payment server
4. Parse the formed reqest to SendTransaction method to attempt to send the transaction to the payment server
5. Store the received transaction response in a variable
6. Include receipt page which will output the response HTML and parse the server response

*/
session_start();

include __DIR__ . "/configuration.php";
include __DIR__ . "/connection.php";

	
function Write_Session_logFile($LogText)
  {
	$myfile = fopen(__DIR__ . "/Session_log.txt", "a") or die("Unable to open file!");
	$txt = $LogText . "\r\n";
	fwrite($myfile, $txt);
	fclose($myfile);
}
  
// This is used to set the HTTP operation for sending the transaction
// In your integration, you should never pass this in, but set the value here based on your requirements
$method = 'POST'; 

// The following section allows the example code to setup the custom/changing components to the URI
// In your integration, you should never pass these in, but set the values here based on your requirements
$customUri = "/session";

// Add any HTML/$post field names that you want to unset to this array
// If you have any other fields in the HTTP POST, you need to process them here and remove from $post
// After this, $post should only contain fields that are being sent as part of the transaction
$unsetNames = array("submit", "method");

// loop through each field in the unsetNames array
// unset the field if the key exists
foreach ($unsetNames as $fieldName) {
  if (array_key_exists($fieldName, $post))
    unset($post[$fieldName]);
}

// Creates the Merchant Object from config. If you are using multiple merchant ID's,
// you can pass in another configArray each time, instead of using the one from configuration.php
$merchantObj = new Merchant($configArray);

// The Parser object is used to process the response from the gateway and handle the connections
$parserObj = new Parser($merchantObj);

// Get Merchant ID from configuration File
$merchantId = $merchantObj->GetMerchantId();

// In your integration, you should never pass this in, but store the value in configuration
// If you wish to use multiple versions, you can set the version as is being done below
if (array_key_exists("version", $post)) {
  $merchantObj->SetVersion($post["version"]);
  unset($post["version"]);
}

// form transaction request
$request = $parserObj->ParseRequest($post);
Write_Session_logFile("request: " .$request);

// if no post received from HTML page (parseRequest returns "" upon receiving an empty $post)
if ($request == "")
  die();

// print the request pre-send to server if in debug mode
// this is used for debugging only. This would not be used in your integration, as DEBUG should be set to FALSE
if ($merchantObj->GetDebug())
  echo $request . "<br/><br/>";

// forms the requestUrl and assigns it to the merchantObj gatewayUrl member
// returns what was assigned to the gatewayUrl member for echoing if in debug mode
$requestUrl = $parserObj->FormRequestUrl($merchantObj, $customUri);
Write_Session_logFile("requestUrl: " .$requestUrl);

// this is used for debugging only. This would not be used in your integration, as DEBUG should be set to FALSE
if ($merchantObj->GetDebug())
  echo $requestUrl . "<br/><br/>";


// attempt sending of transaction
// $response is used in receipt page, do not change variable name
$response = $parserObj->SendTransaction($merchantObj, $request, $method);

// print response received from server if in debug mode
// this is used for debugging only. This would not be used in your integration, as DEBUG should be set to FALSE
if ($merchantObj->GetDebug()) {
  // replace the newline chars with html newlines
  $response = str_replace("\n", "<br/>", $response);
  echo $response . "<br/><br/>";
  //die();
}

////////////////////////////////////// Parsing Session Response ////////////////////////////////
$errorMessage = "";
$errorCode = "";
$sessionID = "";
$sessionVr = "";
$result = "";
$successIndicator = "";

$tmpArray = array();

// [Snippet] howToDecodeResponse - start
// $response is defined in process.php as the server response
$responseArray = json_decode($response, TRUE);
// [Snippet] howToDecodeResponse - end

// either a HTML error was received
// or response is a curl error
if ($responseArray == NULL) {
  print("JSON decode failed. Please review server response (enable debug in config.php).");
  die();
}

// [Snippet] howToParseResponse - start
if (array_key_exists("result", $responseArray))
  $result = $responseArray["result"];
// [Snippet] howToParseResponse - end


// Form error string if error is triggered
if ($result == "FAIL") {
  if (array_key_exists("reason", $responseArray)) {
    $tmpArray = $responseArray["reason"];

    if (array_key_exists("explanation", $tmpArray)) {
      $errorMessage = rawurldecode($tmpArray["explanation"]);
    }
    else if (array_key_exists("supportCode", $tmpArray)) {
      $errorMessage = rawurldecode($tmpArray["supportCode"]);
    }
    else {
      $errorMessage = "Reason unspecified.";
    }

    if (array_key_exists("code", $tmpArray)) {
      $errorCode = "Error (" . $tmpArray["code"] . ")";
    }
    else {
      $errorCode = "Error (UNSPECIFIED)";
    }
  }
}
else {
  if (array_key_exists("successIndicator", $responseArray)) {
	$successIndicator = $responseArray["successIndicator"];
	$_SESSION["successIndicator"] = $successIndicator;
    $tmpArray = $responseArray["session"];
    if (array_key_exists("id", $tmpArray))
	{
      $sessionID = rawurldecode($tmpArray["id"]);
	  $_SESSION['sessionID'] = $sessionID;
	}
    if (array_key_exists("version", $tmpArray))
      $sessionVr = rawurldecode($tmpArray["version"]);
  }
}

// Get applied API Version from configuration File
$apiVersion = $merchantObj->GetVersion();

if($sessionID != "" & $successIndicator != "")
{
	
	
echo    '

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>XXX - Payment</title>
</head>
 <body>';
  echo "<script src=\"https://bobsal.gateway.mastercard.com/checkout/version/" . $apiVersion . "/checkout.js\"".
        "            data-error=\"errorCallback\"".
        "            data-cancel=\"cancelCallback\">".
        "</script>";
		
echo    "<script type=\"text/javascript\">".

        "    function errorCallback(error) {".
        "        console.log(JSON.stringify(error));".
        "    }".
        "    function cancelCallback() {".
        "        console.log('Payment cancelled');".
        "    }".

        "    Checkout.configure({".
        "        order: {".
        "            currency: 'USD',".  
        "            description: 'Ordered goods'".			
        "       },".
        "        session: {".
        "            id: \"$sessionID\"".
        "        },".
        "        interaction: {".
        "            merchant: {".
        "                name: 'XXX PAYMENT',".
        "                address: {".
        "                    line1: '200 Sample St',".
        "                    line2: '1234 Example Town'".
        "                }".
        "           },".
        "           displayControl: {".
        "             paymentConfirmation: 'HIDE',".
        "             billingAddress: 'HIDE',".
        "             customerEmail: 'HIDE',".
        "             orderSummary: 'HIDE',".
        "             shipping: 'HIDE'".
        "           }".
        "        }".
        "    });
        
            Checkout.showLightbox()
".
        "</script>".
        "<br><br></body></html>";

}

/////////////////////////////// Session Response - End Parsing ////////////////////////////////////////

?>