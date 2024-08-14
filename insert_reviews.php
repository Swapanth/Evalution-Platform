<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

function base64url_encode($data) {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

function makeRequest($url, $method = 'GET', $data = null, $headers = []) {
    $options = [
        'http' => [
            'method'  => $method,
            'header'  => implode("\r\n", $headers),
            'content' => $data,
            'ignore_errors' => true
        ]
    ];
    $context  = stream_context_create($options);
    $result = file_get_contents($url, false, $context);

    if ($result === FALSE) {
        echo "Error making request to $url: " . error_get_last()['message'] . "\n";
        return false;
    }

    echo "Response headers for $url:\n";
    print_r($http_response_header);

    return $result;
}

$credentials = json_decode(file_get_contents('hip-fuze-432111-m3-fd461a067095.json'), true);
$client_email = $credentials['client_email'];
$private_key = str_replace('\n', "\n", $credentials['private_key']);

echo "Client Email: $client_email\n";
echo "Private Key (first 50 characters): " . substr($private_key, 0, 50) . "...\n";

$private_key = openssl_pkey_get_private($private_key);
if ($private_key === false) {
    echo "Failed to load private key. OpenSSL error: " . openssl_error_string() . "\n";
    exit;
}

$header = base64url_encode(json_encode(['typ' => 'JWT', 'alg' => 'RS256']));
$payload = base64url_encode(json_encode([
    'iss' => $client_email,
    'scope' => 'https://www.googleapis.com/auth/spreadsheets',
    'aud' => 'https://oauth2.googleapis.com/token',
    'exp' => time() + 3600,
    'iat' => time()
]));

echo "Header: $header\n";
echo "Payload: $payload\n";

$signature = '';
$success = openssl_sign("$header.$payload", $signature, $private_key, OPENSSL_ALGO_SHA256);
if (!$success) {
    echo "Failed to sign JWT. OpenSSL error: " . openssl_error_string() . "\n";
    exit;
}
$signature = base64url_encode($signature);

echo "Signature (first 50 characters): " . substr($signature, 0, 50) . "...\n";

$jwt = "$header.$payload.$signature";

echo "Full JWT (first 100 characters): " . substr($jwt, 0, 100) . "...\n";

$token_url = 'https://oauth2.googleapis.com/token';
$token_data = http_build_query([
    'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
    'assertion' => $jwt
]);

echo "Token request data:\n";
print_r($token_data);

$token_response = makeRequest($token_url, 'POST', $token_data, [
    'Content-Type: application/x-www-form-urlencoded'
]);

echo "Token response:\n";
print_r(json_decode($token_response, true));

if (!$token_response || !isset(json_decode($token_response, true)['access_token'])) {
    echo "Failed to get access token. Response: " . $token_response . "\n";
    exit;
}
$token = json_decode($token_response, true)['access_token'];

echo "Using access token: " . $token . "\n";

// The rest of your code for accessing the spreadsheet goes here...

$spreadsheetId = '';

$sheets_url = "https://sheets.googleapis.com/v4/spreadsheets/{$spreadsheetId}";
$sheets_response = makeRequest($sheets_url, 'GET', null, ["Authorization: Bearer {$token}"]);

if ($sheets_response === false) {
    echo "Failed to get spreadsheet info\n";
    exit;
}

$sheets_data = json_decode($sheets_response, true);

foreach ($sheets_data['sheets'] as $sheet) {
    echo $sheet['properties']['title'] . "\n";
}

echo "\n";
echo "Sheet ID: " . $spreadsheetId . "\n";

$values_url = "https://sheets.googleapis.com/v4/spreadsheets/{$spreadsheetId}/values/reviews!A1:D10";
$values_response = makeRequest($values_url, 'GET', null, ["Authorization: Bearer {$token}"]);

if ($values_response === false) {
    echo "Failed to get values\n";
    exit;
}

$values = json_decode($values_response, true)['values'];
print_r($values);

$data = json_decode(file_get_contents('php://input'), true);
if (!$data) {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'No data received']);
    exit;
}

$values = [];
foreach ($data as $row) {
    $values[] = [
        $row['team'],
        $row['rating'],
        $row['comment'],
        $row['reviewed_by']
    ];
}

$append_url = "https://sheets.googleapis.com/v4/spreadsheets/{$spreadsheetId}/values/reviews!A1:append?valueInputOption=RAW";
$append_data = json_encode(['values' => $values]);
$append_headers = [
    "Authorization: Bearer {$token}",
    'Content-Type: application/json'
];

try {
    $append_response = makeRequest($append_url, 'POST', $append_data, $append_headers);
    if ($append_response === false) {
        throw new Exception("Failed to append data");
    }
    header('Content-Type: application/json');
    echo json_encode(['status' => 'success', 'message' => 'Data successfully added']);
} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
