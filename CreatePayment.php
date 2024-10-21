<?php
require_once __DIR__ . '/CurlHttpClient.php';
require_once __DIR__ . '/SportsBookBuilderCreateAccessApi.php';

$config = require 'config/api_config.php';


// Set the content type to JSON for the response
header('Content-Type: application/json');

// Validate configuration
if (!isset($config['base_url']) || !isset($config['merchant_id']) || !isset($config['merchant_secret'])) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Missing required configuration values'
    ]);
    exit; // Stop further execution
}

$httpClient = new CurlHttpClient();
$api = new SportsBookBuilderApiCreateAccessApi($config['base_url'], $httpClient);

try {
    // Get the access token
    $tokenResponse = $api->createAccessToken(
        $config['merchant_id'],
        $config['merchant_secret'],
        $config['client_id'] ?? null // Use config value if available, otherwise null
    );
    
    // Extract the token from the response
    $accessToken = $tokenResponse['body']['token'] ?? null;
    if (!$accessToken) {
        throw new \Exception('Failed to obtain access token');
    }

    $json = file_get_contents('php://input');
    $data = json_decode($json, true);
    
    
    
    // Check if JSON decoding was successful
    if ($data === null && json_last_error() !== JSON_ERROR_NONE) {
        http_response_code(400); // Bad Request
        echo json_encode([
            'status' => 'error',
            'message' => 'Invalid JSON input'
        ]);
        exit;
    }
    // Assign dynamic values from JSON or use defaults
$userId = $data['user_id'] ?? 'defaultUserId';  // Or some default
$amount = $data['amount'] ?? 4210.00;  // Default amount if not provided
$currency = $data['currency'] ?? 'EUR';  // Default currency if not provided
$merchantTransactionReference = $data['merchant_transaction_reference'] ?? uniqid('txn_', true);


    // Validate input data
    if (!is_numeric($amount) || $amount <= 0) {
        http_response_code(400); // Bad Request
        echo json_encode([
            'status' => 'error',
            'message' => 'Invalid amount'
        ]);
        exit;
    }
    if (!in_array($currency, ['EUR', 'USD', 'GBP'])) { // Add all supported currencies
        http_response_code(400); // Bad Request
        echo json_encode([
            'status' => 'error',
            'message' => 'Invalid currency'
        ]);
        exit;
    }
    if (!preg_match('/^[a-zA-Z0-9-]+$/', $merchantTransactionReference)) {
        http_response_code(400); // Bad Request
        echo json_encode([
            'status' => 'error',
            'message' => 'Invalid merchant transaction reference'
        ]);
        exit;
    }

    // Create the payment
    $paymentResponse = $api->createPayment(
        $userId,
        $amount,
        $currency,
        $merchantTransactionReference,
        $config['merchant_secret'],
        $accessToken
    );

    // Handle the payment response
    handlePaymentResponse($paymentResponse);
} catch (\Exception $e) {
    handleError($e);
}

function handlePaymentResponse(array $response) {
    $statusCode = $response['statusCode'] ?? null;
    $body = $response['body'] ?? [];

    if ($statusCode === 200 && isset($body['transaction_id'])) {
        echo json_encode([
            'status' => 'success',
            'transaction_id' => $body['transaction_id'],
            'message' => 'Payment created successfully.'
        ]);
        // Process successful payment...
    } else {
        http_response_code($statusCode ?? 500); // Set HTTP response code to the API response status or 500
        echo json_encode([
            'status' => 'error',
            'message' => $body['error'] ?? 'Unknown error',
            'code' => $statusCode
        ]);
        // Handle failed payment...
    }
}

function handleError(\Exception $e) {
    error_log('Payment Error: ' . $e->getMessage());
    http_response_code(500); // Set HTTP response code to 500 for server errors
    echo json_encode([
        'status' => 'error',
        'message' => 'An error occurred: ' . $e->getMessage()
    ]);
    // Implement appropriate error handling logic
}