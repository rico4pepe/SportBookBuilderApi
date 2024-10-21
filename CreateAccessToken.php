<?php

require_once __DIR__ . '/CurlHttpClient.php';
require_once __DIR__ . '/SportsBookBuilderCreateAccessApi.php';


$config = require 'config/api_config.php';

// Set the content type header to application/json for the response
header('Content-Type: application/json');


// Validate configuration
if (!isset($config['base_url']) || !isset($config['merchant_id']) || !isset($config['merchant_secret'])) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Missing required configuration values'
    ]);
    exit; // Stop further execution if configuration is invalid
}

$httpClient = new CurlHttpClient();
$api = new SportsBookBuilderApiCreateAccessApi($config['base_url'], $httpClient);

//$response = $api->createAccessToken($config['merchant_id'], $config['merchant_secret']);




try {
    // Call the createAccessToken method with merchant credentials
    $response = $api->createAccessToken(
        $config['merchant_id'],
        $config['merchant_secret'],
        100 // Optional client_id
    );
    
   // Check if the access token is present in the response
   if (isset($response['body']['token'])) {
    // Return success response in JSON format
    echo json_encode([
        'status' => 'success',
        'data' => $response
    ], JSON_PRETTY_PRINT);
}else{
    // Handle missing token in the response
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Failed to retrieve access token'
    ]);
}
} catch (Exception $e) {
    error_log('API Error: ' . $e->getMessage());

    http_response_code(500); // Set HTTP response code to 500
    echo json_encode([
        'status' => 'error',
        'message' => 'An error occurred: ' . $e->getMessage()
    ]);
}

