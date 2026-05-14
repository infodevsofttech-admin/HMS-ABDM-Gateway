<?php
// Test login flow
$ch = curl_init();

// Step 1: Get login page and CSRF token
curl_setopt($ch, CURLOPT_URL, 'http://localhost/auth/login');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIEJAR, '/tmp/test_cookies.txt');
curl_setopt($ch, CURLOPT_COOKIEFILE, '/tmp/test_cookies.txt');

$response = curl_exec($ch);
preg_match('/name="([^"]*csrf[^"]*)"[^>]*value="([^"]*)"/', $response, $matches);
$csrf_name = $matches[1] ?? 'csrf_field_name';
$csrf_value = $matches[2] ?? 'csrf_value_missing';

echo "CSRF Token Found: " . ($csrf_value !== 'csrf_value_missing' ? 'YES' : 'NO') . "\n";
echo "CSRF Name: $csrf_name\n";
echo "CSRF Value: " . substr($csrf_value, 0, 20) . "...\n\n";

// Step 2: Attempt login
curl_setopt($ch, CURLOPT_URL, 'http://localhost/auth/login');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
    $csrf_name => $csrf_value,
    'username' => 'admin',
    'password' => 'test',
]));

curl_setopt($ch, CURLOPT_HEADER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

echo "Login Response HTTP Code: $http_code\n";

if ($http_code === 302 || $http_code === 301) {
    echo "Status: REDIRECT (Login Successful!)\n";
    preg_match('/Location: ([^\r\n]+)/', $response, $matches);
    echo "Redirect Location: " . ($matches[1] ?? 'Header missing') . "\n";
} else {
    echo "Status: " . ($http_code === 200 ? "Form Re-displayed (Login Failed)" : "Unknown HTTP $http_code") . "\n";
    if (strpos($response, 'Invalid username or password') !== false) {
        echo "Error: Invalid credentials detected in response\n";
    }
}

curl_close($ch);
