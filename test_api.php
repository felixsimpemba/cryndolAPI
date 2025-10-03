<?php

/**
 * Simple API Test Script for Cryndol API
 * 
 * This script tests the main authentication endpoints
 * Run: php test_api.php
 */

$baseUrl = 'http://localhost:8000/api';

function makeRequest($url, $method = 'GET', $data = null, $token = null) {
    $ch = curl_init();
    
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    
    $headers = ['Content-Type: application/json'];
    if ($token) {
        $headers[] = 'Authorization: Bearer ' . $token;
    }
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    
    if ($data) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return [
        'code' => $httpCode,
        'body' => json_decode($response, true)
    ];
}

echo "Testing Cryndol API Endpoints\n";
echo "=============================\n\n";

// Test 1: Register Personal Profile
echo "1. Testing Personal Registration...\n";
$registerData = [
    'fullName' => 'John Doe',
    'email' => 'john.doe@example.com',
    'phoneNumber' => '+1234567890',
    'password' => 'SecurePassword123!',
    'acceptTerms' => true
];

$registerResponse = makeRequest($baseUrl . '/auth/register/personal', 'POST', $registerData);
echo "Status Code: " . $registerResponse['code'] . "\n";
echo "Response: " . json_encode($registerResponse['body'], JSON_PRETTY_PRINT) . "\n\n";

$accessToken = $registerResponse['body']['data']['tokens']['accessToken'] ?? null;

if ($accessToken) {
    // Test 2: Get User Profile
    echo "2. Testing Get Profile (with token)...\n";
    $profileResponse = makeRequest($baseUrl . '/auth/profile', 'GET', null, $accessToken);
    echo "Status Code: " . $profileResponse['code'] . "\n";
    echo "Response: " . json_encode($profileResponse['body'], JSON_PRETTY_PRINT) . "\n\n";

    // Test 3: Create Business Profile
    echo "3. Testing Create Business Profile...\n";
    $businessData = [
        'businessName' => 'John\'s Consulting LLC'
    ];
    
    $businessResponse = makeRequest($baseUrl . '/auth/business-profile', 'POST', $businessData, $accessToken);
    echo "Status Code: " . $businessResponse['code'] . "\n";
    echo "Response: " . json_encode($businessResponse['body'], JSON_PRETTY_PRINT) . "\n\n";

    // Test 4: Get Profile Again (should include business profile)
    echo "4. Testing Get Profile (with business profile)...\n";
    $profileResponse2 = makeRequest($baseUrl . '/auth/profile', 'GET', null, $accessToken);
    echo "Status Code: " . $profileResponse2['code'] . "\n";
    echo "Response: " . json_encode($profileResponse2['body'], JSON_PRETTY_PRINT) . "\n\n";

    // Test 5: Logout
    echo "5. Testing Logout...\n";
    $logoutResponse = makeRequest($baseUrl . '/auth/logout', 'POST', null, $accessToken);
    echo "Status Code: " . $logoutResponse['code'] . "\n";
    echo "Response: " . json_encode($logoutResponse['body'], JSON_PRETTY_PRINT) . "\n\n";
}

// Test 6: Login
echo "6. Testing Login...\n";
$loginData = [
    'email' => 'john.doe@example.com',
    'password' => 'SecurePassword123!'
];

$loginResponse = makeRequest($baseUrl . '/auth/login', 'POST', $loginData);
echo "Status Code: " . $loginResponse['code'] . "\n";
echo "Response: " . json_encode($loginResponse['body'], JSON_PRETTY_PRINT) . "\n\n";

echo "API Testing Complete!\n";
echo "====================\n";
echo "Note: Make sure your Laravel server is running with: php artisan serve\n";
echo "Swagger documentation available at: http://localhost:8000/api/documentation\n";
