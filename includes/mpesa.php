<?php
function getMpesaAccessToken() {
    $consumerKey = getSetting('mpesa_consumer_key');
    $consumerSecret = getSetting('mpesa_consumer_secret');
    $environment = getSetting('mpesa_environment', 'sandbox');
    
    if (empty($consumerKey) || empty($consumerSecret)) {
        return null;
    }
    
    $url = $environment === 'production' 
        ? 'https://api.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials'
        : 'https://sandbox.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials';
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Basic ' . base64_encode($consumerKey . ':' . $consumerSecret)]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);
    
    $result = json_decode($response, true);
    
    if ($httpCode === 200 && isset($result['access_token'])) {
        return $result['access_token'];
    }
    
    return null;
}

function initiateMpesaSTK($phoneNumber, $amount, $bookingReference, $accountReference = null) {
    $accessToken = getMpesaAccessToken();
    if (!$accessToken) {
        return ['success' => false, 'message' => 'M-Pesa credentials not configured'];
    }
    
    $consumerKey = getSetting('mpesa_consumer_key');
    $consumerSecret = getSetting('mpesa_consumer_secret');
    $passkey = getSetting('mpesa_passkey');
    $shortcode = getSetting('mpesa_shortcode');
    $environment = getSetting('mpesa_environment', 'sandbox');
    $callbackUrl = getSetting('mpesa_callback_url');
    
    if (empty($passkey) || empty($shortcode) || empty($callbackUrl)) {
        return ['success' => false, 'message' => 'M-Pesa configuration incomplete'];
    }
    
    $timestamp = date('YmdHis');
    $password = base64_encode($shortcode . $passkey . $timestamp);
    
    $payload = [
        "BusinessShortCode" => $shortcode,
        "Password" => $password,
        "Timestamp" => $timestamp,
        "TransactionType" => "CustomerPayBillOnline",
        "Amount" => (int)$amount,
        "PartyA" => $phoneNumber,
        "PartyB" => $shortcode,
        "PhoneNumber" => $phoneNumber,
        "CallBackURL" => $callbackUrl,
        "AccountReference" => "Hayven CarHire",
        "TransactionDesc" => "Hayven CarHire - " . $bookingReference
    ];
    
    $url = $environment === 'production'
        ? 'https://api.safaricom.co.ke/mpesa/stkpush/v1/processrequest'
        : 'https://sandbox.safaricom.co.ke/mpesa/stkpush/v1/processrequest';
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Content-Type: application/json",
        "Authorization: Bearer " . $accessToken
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);
    
    $result = json_decode($response, true);
    
    if ($httpCode === 200 && is_array($result) && isset($result['ResponseCode']) && $result['ResponseCode'] === '0') {
        return [
            'success' => true,
            'checkout_request_id' => $result['CheckoutRequestID'] ?? null,
            'merchant_request_id' => $result['MerchantRequestID'] ?? null,
            'customer_message' => $result['CustomerMessage'] ?? 'Success'
        ];
    }
    
    $errorMessage = is_array($result) ? ($result['errorMessage'] ?? $result['ResponseDescription'] ?? 'STK Push failed') : 'STK Push failed';
    
    return [
        'success' => false,
        'message' => $curlError ? $curlError : $errorMessage,
        'http_code' => $httpCode,
        'response' => $result
    ];
}
