<?php


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
// Function to check if team has already reviewed
function hasTeamReviewed($sheetId, $apiKey, $teamName) {
    $range = 'reviews!A:E'; // Adjust this range to cover all columns in your 'reviews' sheet
    $sheetData = getSheetData($sheetId, $apiKey, $range);
    
    if (isset($sheetData['values'])) {
        foreach ($sheetData['values'] as $row) {
            if (isset($row[3]) && $row[3] === $teamName) { // Assuming 'reviewed_by' is the 5th column (index 4)
                return true;
            }
        }
    }
    return false;
}

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate and sanitize input
    $registrationNumber = filter_input(INPUT_POST, 'registration_number');
    $teamName = filter_input(INPUT_POST, 'team_name');
    $password = filter_input(INPUT_POST, 'password');
    $termsAccepted = filter_input(INPUT_POST, 'termsAccepted', FILTER_VALIDATE_BOOLEAN);

    // Prepare data for logging
    $logData = [$registrationNumber, $teamName, $password, date('Y-m-d H:i:s')];

    $sheetId = ''; // Replace with your actual Google Sheet ID
    $apiKey = ''; // Replace this with a new, restricted API key
    $range = 'login!A2:C'; // Reading the relevant range from the 'login' sheet

    try {
        // Append all attempts to the sheet
        appendToSheet($sheetId, $apiKey, $logData);

        // Retrieve data from the sheet
        $sheetData = getSheetData($sheetId, $apiKey, $range);

        // Log the retrieved data for debugging
        error_log("Retrieved data: " . print_r($sheetData, true));

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
            // Check if the team has already reviewed
            if (hasTeamReviewed($sheetId, $apiKey, $teamName)) {
                echo json_encode(['success' => false, 'message' => 'Already reviewed']);
            } else {
                echo json_encode(['success' => true, 'message' => 'Login information is valid']);
                header('Location: form.html?teamName=' . urlencode($teamName));
            }
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