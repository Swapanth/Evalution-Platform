<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require 'vendor/autoload.php';

use Google\Client;
use Google\Service\Sheets;

// Create a new Google Client
$client = new Client();
$client->setAuthConfig('hip-fuze-432111-m3-95ab489625bc.json'); // Path to your service account JSON key file
$client->addScope(Sheets::SPREADSHEETS);

$service = new Sheets($client);

$spreadsheetId = '1ZEBj7JAETOXOTTroH_bKqmUMJrveypT3JVIIifMDd5A';
$spreadsheet = $service->spreadsheets->get($spreadsheetId);
$sheets = $spreadsheet->getSheets();
foreach ($sheets as $sheet) {
    echo $sheet->getProperties()->getTitle() . "\n";
}
echo "\n";
echo "Sheet ID: " . $spreadsheetId . "\n";
$response = $service->spreadsheets_values->get($spreadsheetId, 'reviews!A1:D10');
$values = $response->getValues();
print_r($values);
$range = 'reviews!A1';
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

$body = new Sheets\ValueRange([
    'values' => $values
]);

$params = [
    'valueInputOption' => 'RAW'
];

try {
    $result = $service->spreadsheets_values->append($spreadsheetId, $range, $body, $params);
    header('Content-Type: application/json');
    echo json_encode(['status' => 'success', 'message' => 'Data successfully added']);
} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
