<?php
/**
 * Combined AV Controls and Remote Control Interface
 * This file handles AJAX requests for controlling AV devices, including power, channel, and volume updates,
 * as well as executing remote commands for specific actions.
 * 
 * @version 2.0
 * @date 2024-11-03
 */

error_reporting(E_ALL);             // Enable all error reporting for debugging
ini_set('display_errors', '1');     // Display errors on the page (useful for development, disable in production)

ob_start(); // Start output buffering to control output flow

// Include configuration and utility functions
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/utils.php';

// Handle AJAX requests for device control
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
    strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
    
    $response = array('success' => false, 'message' => ''); // Initialize the response structure

    // Handle receiver control requests (e.g., power, channel, volume)
    if (isset($_POST['receiver_ip'])) {
        // Sanitize and retrieve the receiver IP from POST data
        $deviceIp = sanitizeInput($_POST['receiver_ip'], 'ip');

        // Find the receiver configuration based on the IP address
        $receiverConfig = null;
        foreach (RECEIVERS as $config) {
            if ($config['ip'] === $deviceIp) {
                $receiverConfig = $config;
                break;
            }
        }

        // Process power commands if specified
        if (isset($_POST['power_command'])) {
            // Only allow power commands if the receiver's configuration allows power control
            if ($receiverConfig && $receiverConfig['show_power']) {
                $powerCommand = sanitizeInput($_POST['power_command'], 'string');
                try {
                    // Send the power command via an API call
                    $commandResponse = makeApiCall('POST', $deviceIp, 'command/cli', $powerCommand, 'text/plain');
                    $responseData = json_decode($commandResponse, true); // Decode the JSON response
                    
                    // Check if the response indicates success
                    if (isset($responseData['data']) && $responseData['data'] === 'OK') {
                        $response['success'] = true;
                        $response['message'] = "Power command sent successfully.";
                    } else {
                        $response['message'] = "Error sending power command: Unexpected response.";
                    }
                } catch (Exception $e) {
                    // Log and return any error encountered during the API call
                    $response['message'] = "Error sending power command: " . $e->getMessage();
                    logMessage("Error sending power command: " . $e->getMessage(), 'error');
                }
            } else {
                // Respond if power control is not enabled for this receiver
                $response['message'] = "Power control not enabled for this receiver.";
            }
        } else {
            // Handle channel and volume updates if power command is not specified
            $selectedChannel = sanitizeInput($_POST['channel'], 'int');

            if ($selectedChannel && $deviceIp) {
                try {
                    // Update the channel on the receiver
                    $channelResponse = setChannel($deviceIp, $selectedChannel);
                    $response['message'] .= "Channel: " . ($channelResponse ? "Successfully updated" : "Update failed") . "\n";

                    // If the device supports volume control, update the volume as well
                    if (supportsVolumeControl($deviceIp)) {
                        $selectedVolume = sanitizeInput($_POST['volume'], 'int', ['min' => MIN_VOLUME, 'max' => MAX_VOLUME]);
                        if ($selectedVolume) {
                            $volumeResponse = setVolume($deviceIp, $selectedVolume);
                            $response['message'] .= "Volume: " . ($volumeResponse ? "Successfully updated" : "Update failed") . "\n";
                        }
                    }

                    $response['success'] = true;
                } catch (Exception $e) {
                    // Log and return any error encountered during the update process
                    $response['message'] = "Error updating settings: " . $e->getMessage();
                    logMessage("Error updating settings: " . $e->getMessage(), 'error');
                }
            }
        }
    } else if (isset($_POST['device_url'])) {
        // Handle remote control commands for devices identified by URL
        $deviceUrl = rtrim($_POST['device_url'], '/');
        $action = $_POST['action'];
        
        // Load IR command payloads from the file for specific actions
        $payloads = loadPayloads('payloads.txt');
        
        if (isset($payloads[$action])) {
            try {
                // Construct the API URL and payload to send to the device
                $url = $deviceUrl . "/cgi-bin/api/command/cli";
                $payload = 'echo "' . $payloads[$action] . '" | ./fluxhandlerV2.sh';
                
                // Make the API call to execute the command
                $result = makeApiCall('POST', $deviceUrl, 'command/cli', $payload, 'text/plain');
                $response['success'] = true;
                $response['message'] = "Command sent successfully";
            } catch (Exception $e) {
                // Log and return any error encountered during the remote command
                $response['message'] = "Error sending command: " . $e->getMessage();
                logMessage("Error sending remote command: " . $e->getMessage(), 'error');
            }
        } else {
            // Respond with an error if the action is not recognized
            $response['message'] = "Invalid action: " . htmlspecialchars($action);
        }
    }

    // Return the AJAX response as JSON
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

// Preload check to determine if any receivers are reachable before displaying the UI
$allReceiversUnreachable = true; // Assume initially that all receivers are unreachable
foreach (RECEIVERS as $receiverName => $receiverConfig) {
    try {
        // Attempt to get the current channel to verify connectivity
        getCurrentChannel($receiverConfig['ip']);
        $allReceiversUnreachable = false; // If a receiver responds, set flag to false
        break;
    } catch (Exception $e) {
        continue; // Ignore exceptions and check the next receiver
    }
}

// Include the main template file to render the user interface
include __DIR__ . '/template.php';

ob_end_flush(); // End output buffering and flush output to the browser
