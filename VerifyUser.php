<?php
require_once 'Http/CurlHttpClient.php';
require_once 'Api/SportsBookBuilderCreateAccessApi.php';

$config = require 'config/api_config.php';


header('Content-Type: application/json');

$httpClient = new CurlHttpClient();
$api = new SportsBookBuilderApiCreateAccessApi($config['base_url'], $httpClient);

//$response = $api->createAccessToken($config['merchant_id'], $config['merchant_secret']);




try {
   //  Get the access token
    $response = $api->createAccessToken(
        $config['merchant_id'],
        $config['merchant_secret'],
        100 // Optional client_id
    );
    
    
    // Extract the token from the response
    $accessToken = $tokenResponse['body']['token'] ?? null;

    if (!$accessToken) {
        throw new \Exception('Failed to obtain access token');
    }

    $userId = $_GET['user_id'] ?? null; // Get user_id from query parameter
    if (!$userId) {
        throw new \InvalidArgumentException('User ID is required');
    }

       //  Verify the user account using the access token
       $userId = '12345'; // The user ID you want to verify
       $verifyResponse = $api->verifyUserAccount($userId, $accessToken);

       
     // Handle the verification response
     handleVerificationResponse($verifyResponse);
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage();
}


function handleVerificationResponse(array $response) {
    $statusCode = $response['statusCode'] ?? null;
    $body = $response['body'] ?? [];

    if ($statusCode === 200) {
        echo json_encode([
            'status' => 'success',
            'message' => 'User account verified successfully.',
            'data' => $body // Include the verification data in the response
        ]);
    } else {
        http_response_code($statusCode ?? 500); // Set HTTP response code to the API response status or 500
        echo json_encode([
            'status' => 'error',
            'message' => $body['error'] ?? 'Unknown error',
            'code' => $statusCode
        ]);
    }
}

function handleError(\Exception $e) {
    error_log('Verification Error: ' . $e->getMessage());
    http_response_code(500); // Set HTTP response code to 500 for server errors
    echo json_encode([
        'status' => 'error',
        'message' => 'An error occurred: ' . $e->getMessage()
    ]);
}

