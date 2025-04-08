<?php

/**
 * Simple script to fetch countries data from REST Countries API
 * This can be run directly from PHP CLI
 */

echo "Fetching countries data from REST Countries API...\n";

// Fetch data from API
$apiUrl = 'https://restcountries.com/v3.1/all';
$ch = curl_init($apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);

$response = curl_exec($ch);
$statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

if ($statusCode !== 200 || $error) {
    echo "Error fetching data: " . ($error ?: "HTTP status $statusCode") . "\n";
    exit(1);
}

// Parse the JSON response
$countries = json_decode($response, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    echo "Error parsing JSON response: " . json_last_error_msg() . "\n";
    exit(1);
}

echo "Successfully fetched " . count($countries) . " countries.\n";

// Save to a file
$outputFile = 'all.json';
$dir = dirname($outputFile);
if (!is_dir($dir)) {
    mkdir($dir, 0755, true);
}

if (file_put_contents($outputFile, $response)) {
    echo "Countries data saved to $outputFile\n";
} else {
    echo "Error saving data to $outputFile\n";
    exit(1);
}

echo "Process completed successfully.\n";
exit(0); 