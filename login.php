<?php

// Function to retrieve data from Google Sheet
function getSheetData($sheetId, $apiKey, $range) {
    $url = "https://sheets.googleapis.com/v4/spreadsheets/{$sheetId}/values/{$range}?key={$apiKey}";
    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
    ]);

    $response = curl_exec($curl);
    $err = curl_error($curl);
    curl_close($curl);

    if ($err) {
        throw new Exception("cURL Error: " . $err);
    }

    return json_decode($response, true);
}

// Function to append data to Google Sheet
function appendToSheet($sheetId, $apiKey, $data) {
    $range = 'login!A1:D1';
    $params = [
        'key' => $apiKey,
        'valueInputOption' => 'RAW'
    ];

    $url = "https://sheets.googleapis.com/v4/spreadsheets/{$sheetId}/values/{$range}:append?" . http_build_query($params);

    $body = json_encode([
        'values' => [$data]
    ]);

    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $body,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json'
        ]
    ]);

    $response = curl_exec($curl);
    $err = curl_error($curl);
    curl_close($curl);

    if ($err) {
        throw new Exception("cURL Error: " . $err);
    }

    return json_decode($response, true);
}

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate and sanitize input
    $registrationNumber = filter_input(INPUT_POST, 'registration_number', FILTER_SANITIZE_STRING);
    $teamName = filter_input(INPUT_POST, 'team_name', FILTER_SANITIZE_STRING);
    $password = filter_input(INPUT_POST, 'password', FILTER_SANITIZE_STRING);
    $termsAccepted = filter_input(INPUT_POST, 'termsAccepted', FILTER_VALIDATE_BOOLEAN);

    // Prepare data for logging
    $logData = [$registrationNumber, $teamName, $password, date('Y-m-d H:i:s')];

    $sheetId = '1ZEBj7JAETOXOTTroH_bKqmUMJrveypT3JVIIifMDd5A'; // Replace with your actual Google Sheet ID
    $apiKey = 'AIzaSyDb806fUu5h4V2tUP09dx8AJP53zQKXgjk'; // Replace this with a new, restricted API key
    $range = 'login!A2:C'; // Reading the relevant range from the 'login' sheet

    try {
        // Append all attempts to the sheet
        appendToSheet($sheetId, $apiKey, $logData);

        // Retrieve data from the sheet
        $sheetData = getSheetData($sheetId, $apiKey, $range);

        // Log the retrieved data for debugging
        error_log("Retrieved data: " . print_r($sheetData, true));
        echo json_encode(['success' => true, 'message' => 'Data retrieved successfully']);

        // Check if the provided details exist in the sheet
        $exists = false;
        if (isset($sheetData['values'])) {
            foreach ($sheetData['values'] as $row) {
                if (isset($row[0]) && $row[0] === $registrationNumber &&
                    isset($row[1]) && $row[1] === $teamName &&
                    isset($row[2]) && $row[2] === $password) {
                    $exists = true;
                    break;
                }
            }
        }

        if ($exists) {
            echo json_encode(['success' => true, 'message' => 'Login information is valid']);
            header('Location: form.html?teamName=' . urlencode($teamName));
            // Proceed with any further actions, e.g., redirecting the user
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid inputs']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error validating inputs: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>
