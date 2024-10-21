<?php
require_once 'Http/CurlHttpClient.php';
require_once 'Api/SportsBookBuilderCreateAccessApi.php';

$config = require 'config/api_config.php';

$httpClient = new CurlHttpClient();
$api = new SportsBookBuilderApiCreateAccessApi($config['base_url'], $httpClient);

//$response = $api->createAccessToken($config['merchant_id'], $config['merchant_secret']);

header('Content-Type: application/json'); // Set the content type to JSON




try {
    // Call the createAccessToken method with merchant credentials
    $response = $api->createAccessToken(
        $config['merchant_id'],
        $config['merchant_secret'],
        100 // Optional client_id
    );

    $accessToken = $response['body']['token'];

        // Verify the payment status
        $transactionReference = 'a4a66e1d-de64-4758-a580-caae1665d139';
        $paymentStatusResponse = $api->checkPaymentStatus(
            $transactionReference,
            $config['merchant_secret'],
            $accessToken
        );
    
   // Output the response
    // Return the payment status as a JSON response
    echo json_encode([
        'status' => 'success',
        'data' => $paymentStatusResponse
    ], JSON_PRETTY_PRINT); 
} catch (Exception $e) {
    // Return an error response in JSON format
    http_response_code(500); // Set the HTTP status code to 500 (Internal Server Error)
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}

