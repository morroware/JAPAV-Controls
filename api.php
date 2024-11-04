<?php
// Enable full error reporting for debugging purposes
// E_ALL will report all types of errors, including warnings and notices
error_reporting(E_ALL);
ini_set('display_errors', 1);  // Display errors on the page (useful for development, not for production)

// Set the header to return JSON responses for API requests
header('Content-Type: application/json');

/**
 * Function to send an API request using cURL
 * 
 * @param string $url     The full URL of the device API endpoint
 * @param string $payload The payload containing the command to be executed on the device
 * @return array          Associative array with keys: 'response' (API response),
 *                        'httpCode' (HTTP status code), and 'error' (any cURL errors encountered)
 */
function sendApiRequest($url, $payload) {
    // Initialize a new cURL session
    $ch = curl_init();
    
    // Set the target URL for the API request
    curl_setopt($ch, CURLOPT_URL, $url);
    
    // Return the response as a string rather than outputting it
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    // Indicate that this is a POST request
    curl_setopt($ch, CURLOPT_POST, true);
    
    // Attach the payload data to the POST request
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    
    // Set custom HTTP headers for the request
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: text/plain',           // Specifies plain text format for the payload
        'User-Agent: JustOS API Tester'       // Custom user-agent to identify the source of the request
    ]);

    // Execute the cURL request and capture the response
    $response = curl_exec($ch);
    
    // Get the HTTP response code from the executed request
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    // Capture any cURL errors that may have occurred
    $error = curl_error($ch);
    
    // Close the cURL session to free up resources
    curl_close($ch);

    // Return an associative array containing the response, HTTP status code, and any error message
    return [
        'response' => $response,
        'httpCode' => $httpCode,
        'error' => $error
    ];
}

/**
 * Function to load payloads from a specified text file
 * 
 * @param string $filename Path to the payloads file (typically 'payloads.txt')
 * @return array           Associative array where each action is mapped to its respective IR code
 */
function loadPayloads($filename) {
    $payloads = [];
    
    // Check if the specified file exists to avoid errors
    if (file_exists($filename)) {
        // Read each line of the file into an array, ignoring empty lines
        $lines = file($filename, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        
        // Process each line to extract action and corresponding IR code
        foreach ($lines as $line) {
            // Split each line at the '=' sign to separate the action from the IR code
            list($action, $irCode) = explode('=', $line, 2);
            // Trim whitespace and store in the payloads array with action as the key
            $payloads[trim($action)] = trim($irCode);
        }
    }
    
    // Return the constructed payloads array
    return $payloads;
}

// Check if the request method is POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify that required parameters are provided in the POST request
    if (!isset($_POST['device_url']) || !isset($_POST['action'])) {
        // Return an error message as JSON if parameters are missing
        echo json_encode(['error' => 'Missing required parameters']);
        exit;
    }

    // Get the target device's URL from the request, removing any trailing slash
    $deviceUrl = rtrim($_POST['device_url'], '/');
    // Get the action name from the POST request to look up the associated IR command
    $action = $_POST['action'];
    
    // Load the list of available IR command payloads from 'payloads.txt'
    $payloads = loadPayloads('payloads.txt');
    
    // Check if the specified action exists in the loaded payloads
    if (!isset($payloads[$action])) {
        // Return an error message as JSON if the action is not defined
        echo json_encode(['error' => 'Invalid action']);
        exit;
    }

    // Construct the URL for sending the command to the device's CLI endpoint
    $url = $deviceUrl . "/cgi-bin/api/command/cli";
    // Construct the payload command to send, which includes invoking the command handler script
    $payload = 'echo "' . $payloads[$action] . '" | ./fluxhandlerV2.sh';
    
    // Send the API request using the constructed URL and payload
    $result = sendApiRequest($url, $payload);
    
    // Check for errors in the response or an HTTP code indicating failure (4xx or 5xx)
    if ($result['error'] || $result['httpCode'] >= 400) {
        // Return an error message as JSON, including the cURL error or HTTP status code
        echo json_encode([
            'error' => $result['error'] ?: "HTTP Error " . $result['httpCode']
        ]);
    } else {
        // Return a success message as JSON if the command executed successfully
        echo json_encode(['success' => true]);
    }
    exit;
}

// If the request method is not POST, return an error message as JSON
echo json_encode(['error' => 'Invalid request method']);
exit;
