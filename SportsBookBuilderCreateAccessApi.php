<?php
require_once __DIR__ . '/HttpClientInterface.php';



class SportsBookBuilderApiCreateAccessApi{
    private string $baseUrl;
    private HttpClientInterface $httpClient;


     //  We depend on abstractions (HttpClientInterface) not concretions
     public function __construct(string $baseUrl, HttpClientInterface $httpClient)
     {
         $this->baseUrl = $baseUrl;
         $this->httpClient = $httpClient;

         
     }
    
        // This method handles the token creation endpoint
        public function createAccessToken(string $merchantId, string $merchantSecret, ?int $clientId = null): array
    {
$url = "{$this->baseUrl}/token/create";
        $data = [
            'merchant_id' => $merchantId,
            'merchant_secret' => $merchantSecret
        ];

        // Only include client_id if it's provided
        if ($clientId !== null) {
            $data['client_id'] = $clientId;
        }

        // Use the injected HTTP client to make the request
        return $this->httpClient->post($url, $data);
    }



       // This method handles the user account verification

       public function verifyUserAccount(string $userId, string $accessToken): array {
$url = "{$this->baseUrl}/account/verify/{$userId}";

        // Set the Authorization header with the access token
        $headers = [
            'Authorization: ' . $accessToken,
            'Content-Type: application/json'
        ];

        return $this->httpClient->get($url, [], $headers);  // Make sure this calls the `get()` method
    }


    // Create payment method
    public function createPayment(
        string $userId, 
        float $amount, 
        string $currency, 
        string $merchantTransactionReference, 
        string $merchantSecret, 
        string $accessToken
    ): array {

$url = "{$this->baseUrl}/payment/create";

// Input validation
if (empty($userId)) {
    throw new InvalidArgumentException('User ID cannot be empty.');
}

if ($amount <= 0) {
    throw new InvalidArgumentException('Amount must be greater than zero.');
}

if (!in_array($currency, ['USD', 'EUR', 'GBP', 'NGN'], true)) {  // Adjust currency list based on your needs
    throw new InvalidArgumentException('Invalid currency code.');
}

if (empty($merchantTransactionReference)) {
    throw new InvalidArgumentException('Merchant transaction reference cannot be empty.');
}

if (empty($merchantSecret)) {
    throw new InvalidArgumentException('Merchant secret cannot be empty.');
}


$formattedAmount = number_format($amount, 2, '.', '');

          // Concatenate the values to form the control sum
$message = "{$userId}{$formattedAmount}{$currency}{$merchantTransactionReference}";
        
          // Calculate the control sum using sha512 with merchant_secret as key
          $controlSum = hash_hmac('sha512', $message, $merchantSecret);

           // Prepare the payload
    $data = [
        'user_id' => $userId,
        'amount' => $amount,
        'currency' => $currency,
        'merchant_transaction_reference' => $merchantTransactionReference,
        'control_sum' => $controlSum
    ];

     // Prepare the headers
     $headers = [
        'Authorization: ' . $accessToken,
        'Content-Type: application/json'
    ] ;

    // Send the POST request
    return $this->httpClient->post($url, $data, $headers);
}


public function checkPaymentStatus(string $transactionReference, string $merchantSecret, string $accessToken): array
    {
$url = "{$this->baseUrl}/payment/verify/{$transactionReference}";

        // Prepare the headers
        $headers = [
            'Authorization: ' . $accessToken,
            'Content-Type: application/json'
        ];

        // Make  GET request
        $response = $this->httpClient->get($url, $headers);
        $responseBody = $response['body'];

        if (isset($responseBody['control_sum'])) {
            // Concatenate the values in the order for the control_sum
            $message = $responseBody['status_code'] .
                       $responseBody['status'] .
                       $responseBody['transaction_reference'] .
                       $responseBody['amount'] .
                       $responseBody['currency'] .
                       $responseBody['merchant_transaction_reference'];

            // Generate the control_sum with sha512
            $calculatedControlSum = hash_hmac('sha512', $message, $merchantSecret);

            // Verify control_sum
            if ($calculatedControlSum !== $responseBody['control_sum']) {
                throw new Exception("Invalid control_sum. Possible tampering detected.");
            }
        } else {
            throw new Exception("control_sum missing from response.");
        }

        return $responseBody;
    }

    }

   

    




