<?php

// Function to append data to Google Sheet
function appendToSheet($sheetId, $apiKey, $data) {
    $range = 'Sheet1!A1:D1';
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
    $team = filter_input(INPUT_POST, 'team', FILTER_SANITIZE_STRING);
    $rating = filter_input(INPUT_POST, 'rating', FILTER_VALIDATE_INT);
    $comment = filter_input(INPUT_POST, 'comment', FILTER_SANITIZE_STRING);

    if ($team && $rating !== false && $rating >= 1 && $rating <= 10 && $comment) {
        $sheetId = '1ZEBj7JAETOXOTTroH_bKqmUMJrveypT3JVIIifMDd5A';
        $apiKey = 'AIzaSyDb806fUu5h4V2tUP09dx8AJP53zQKXgjk'; // Remember to replace this with a new, restricted API key

        $data = [$team, $rating, $comment, date('Y-m-d H:i:s')];

        try {
            $result = appendToSheet($sheetId, $apiKey, $data);
            echo json_encode(['success' => true, 'message' => 'Review submitted successfully']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error submitting review: ' . $e->getMessage()]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid input']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}