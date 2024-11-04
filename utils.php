<?php
/**
 * Utility functions for AV Control System
 * 
 * This file provides a set of utility functions to manage and interact with AV devices,
 * including configuration default settings, API calls, form generation, and logging.
 */

// Set default values for configuration constants if they're not defined
if (!defined('API_TIMEOUT')) define('API_TIMEOUT', 5);  // Timeout for API requests in seconds
if (!defined('LOG_LEVEL')) define('LOG_LEVEL', 'error');  // Default logging level
if (!defined('MAX_VOLUME')) define('MAX_VOLUME', 11);  // Maximum volume level
if (!defined('MIN_VOLUME')) define('MIN_VOLUME', 0);  // Minimum volume level
if (!defined('VOLUME_STEP')) define('VOLUME_STEP', 1);  // Volume adjustment increment
if (!defined('HOME_URL')) define('HOME_URL', 'http://localhost');  // Default home URL
if (!defined('ERROR_MESSAGES')) {
    define('ERROR_MESSAGES', [
        'connection' => 'Unable to connect to %s (%s). Please check the connection and try again.',
        'global' => 'Unable to connect to any receivers. Please check your network connection and try again.',
        'remote' => 'Unable to send remote command. Please try again.'
    ]);
}

/**
 * Generates the HTML for all receiver forms, iterating over configured receivers.
 * Each receiver is represented by a form allowing control actions.
 * 
 * @return string HTML content for all receiver forms, or error message if no receivers are configured
 */
function generateReceiverForms() {
    if (!defined('RECEIVERS')) {
        return '<div class="error">No receivers configured</div>';
    }

    $html = '';
    foreach (RECEIVERS as $receiverName => $settings) {
        try {
            // Generate form for each receiver
            $html .= generateReceiverForm($receiverName, $settings['ip'], MIN_VOLUME, MAX_VOLUME, VOLUME_STEP, $settings['show_power']);
        } catch (Exception $e) {
            $html .= "<div class='receiver'><p class='warning'>Error generating form for " . htmlspecialchars($receiverName) . ": " . htmlspecialchars($e->getMessage()) . "</p></div>";
            logMessage("Error generating form for {$receiverName}: " . $e->getMessage(), 'error');
        }
    }
    return $html;
}

/**
 * Generates HTML for a single receiver control form, including channel and volume controls.
 *
 * @param string $receiverName Name of the receiver
 * @param string $deviceIp IP address of the receiver
 * @param int $minVolume Minimum volume level
 * @param int $maxVolume Maximum volume level
 * @param int $volumeStep Volume adjustment increment
 * @param bool $showPower Indicates if power controls should be displayed
 * @return string HTML content for the receiver's form
 */
function generateReceiverForm($receiverName, $deviceIp, $minVolume, $maxVolume, $volumeStep, $showPower = true) {
    try {
        $currentChannel = getCurrentChannel($deviceIp);  // Fetch the current channel
        if ($currentChannel === null) {
            throw new Exception("Unable to get current channel");
        }
        $supportsVolume = supportsVolumeControl($deviceIp);  // Check if device supports volume control
        
        // Start building form HTML
        $html = "<div class='receiver'>";
        $html .= "<form method='POST'>";
        $html .= "<button type='button' class='receiver-title'>" . htmlspecialchars($receiverName) . "</button>";
        
        // Channel selection dropdown
        $html .= "<label for='channel_" . htmlspecialchars($receiverName) . "'>Channel:</label>";
        $html .= "<select id='channel_" . htmlspecialchars($receiverName) . "' name='channel'>";
        if (defined('TRANSMITTERS')) {
            foreach (TRANSMITTERS as $transmitterName => $channelNumber) {
                $selected = ($channelNumber == $currentChannel) ? ' selected' : '';
                $html .= "<option value='$channelNumber'$selected>" . htmlspecialchars($transmitterName) . "</option>";
            }
        }
        $html .= "</select>";
        
        // Volume control if supported by the device
        if ($supportsVolume) {
            $currentVolume = getCurrentVolume($deviceIp);
            if ($currentVolume === null) {
                $currentVolume = $minVolume;
            }
            $html .= "<label for='volume_" . htmlspecialchars($receiverName) . "'>Volume:</label>";
            $html .= "<input type='range' id='volume_" . htmlspecialchars($receiverName) . "' name='volume' min='$minVolume' max='$maxVolume' step='$volumeStep' value='$currentVolume' oninput='updateVolumeLabel(this)'>";
            $html .= "<span class='volume-label'>$currentVolume</span>";
        }
        
        // Hidden field for device IP
        $html .= "<input type='hidden' name='receiver_ip' value='" . htmlspecialchars($deviceIp) . "'>";
        $html .= "<button type='submit' class='update-button'>Update</button>";
        
        // Power control buttons if enabled
        if ($showPower) {
            $html .= "<div class='power-buttons'>";
            $html .= "<button type='button' class='power-on' onclick='sendPowerCommand(\"" . htmlspecialchars($deviceIp) . "\", \"cec_tv_on.sh\")'>Power On</button>";
            $html .= "<button type='button' class='power-off' onclick='sendPowerCommand(\"" . htmlspecialchars($deviceIp) . "\", \"cec_tv_off.sh\")'>Power Off</button>";
            $html .= "</div>";
        }
        
        $html .= "</form>";
        $html .= "</div>";
        
        return $html;
    } catch (Exception $e) {
        logMessage("Error generating form for receiver {$receiverName}: " . $e->getMessage(), 'error');
        return "<div class='receiver error'>"
             . "<h2>" . htmlspecialchars($receiverName) . "</h2>"
             . "<p class='error-message'>Unable to reach " . htmlspecialchars($receiverName) 
             . " (" . htmlspecialchars($deviceIp) . "). Please check that it is powered on and connected to the network.</p>"
             . "</div>";
    }
}

/**
 * Makes an API call to a device endpoint.
 *
 * @param string $method HTTP method for the API call (GET, POST, etc.)
 * @param string $deviceIp IP address of the device
 * @param string $endpoint API endpoint path
 * @param mixed $data Optional data payload to send with the request
 * @param string $contentType Content-Type header for the request
 * @return string API response content
 * @throws Exception if there is a cURL or HTTP error
 */
function makeApiCall($method, $deviceIp, $endpoint, $data = null, $contentType = 'application/x-www-form-urlencoded') {
    $timeout = defined('API_TIMEOUT') ? API_TIMEOUT : 5;  // Use configured timeout or default

    $apiUrl = 'http://' . $deviceIp . '/cgi-bin/api/' . $endpoint;  // Construct API URL
    $ch = curl_init($apiUrl);
    
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);

    if ($data !== null) {
        if ($contentType === 'application/json' && !is_string($data)) {
            $data = json_encode($data);
        }
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: ' . $contentType));
    }

    $result = curl_exec($ch);
    
    if ($result === false) {
        throw new Exception('cURL error: ' . curl_error($ch));
    }

    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode >= 400) {
        throw new Exception('HTTP error: ' . $httpCode . ' - Response: ' . $result);
    }

    return $result;
}

/**
 * Fetches the current volume of a device.
 *
 * @param string $deviceIp IP address of the device
 * @return int|null Current volume level or null on failure
 */
function getCurrentVolume($deviceIp) {
    try {
        $response = makeApiCall('GET', $deviceIp, 'details/audio/stereo/volume');
        $data = json_decode($response, true);
        return isset($data['data']) ? intval($data['data']) : null;
    } catch (Exception $e) {
        logMessage('Error getting current volume: ' . $e->getMessage(), 'error');
        return null;
    }
}

/**
 * Retrieves the current channel from a device.
 *
 * @param string $deviceIp IP address of the device
 * @return int|null Current channel number or null on failure
 */
function getCurrentChannel($deviceIp) {
    try {
        $response = makeApiCall('GET', $deviceIp, 'details/channel');
        $data = json_decode($response, true);
        return isset($data['data']) ? intval($data['data']) : null;
    } catch (Exception $e) {
        logMessage('Error getting current channel: ' . $e->getMessage(), 'error');
        return null;
    }
}

/**
 * Sets the volume level on a device.
 *
 * @param string $deviceIp IP address of the device
 * @param int $volume Desired volume level
 * @return bool True if volume is set successfully, false otherwise
 */
function setVolume($deviceIp, $volume) {
    try {
        $response = makeApiCall('POST', $deviceIp, 'command/audio/stereo/volume', $volume, 'text/plain');
        $data = json_decode($response, true);
        return isset($data['data']) && $data['data'] === 'OK';
    } catch (Exception $e) {
        logMessage('Error setting volume: ' . $e->getMessage(), 'error');
        return false;
    }
}

/**
 * Changes the channel on a device.
 *
 * @param string $deviceIp IP address of the device
 * @param int $channel Desired channel number
 * @return bool True if channel is set successfully, false otherwise
 */
function setChannel($deviceIp, $channel) {
    try {
        $response = makeApiCall('POST', $deviceIp, 'command/channel', $channel, 'text/plain');
        $data = json_decode($response, true);
        return isset($data['data']) && $data['data'] === 'OK';
    } catch (Exception $e) {
        logMessage('Error setting channel: ' . $e->getMessage(), 'error');
        return false;
    }
}

/**
 * Checks if a device supports volume control based on its model.
 *
 * @param string $deviceIp IP address of the device
 * @return bool True if volume control is supported, false otherwise
 */
function supportsVolumeControl($deviceIp) {
    try {
        $response = makeApiCall('GET', $deviceIp, 'details/device/model');
        $data = json_decode($response, true);
        $model = $data['data'] ?? '';
        if (!defined('VOLUME_CONTROL_MODELS')) {
            define('VOLUME_CONTROL_MODELS', ['3G+4+ TX', '3G+AVP RX', '3G+AVP TX', '3G+WP4 TX', '2G/3G SX']);
        }
        return in_array($model, VOLUME_CONTROL_MODELS);
    } catch (Exception $e) {
        logMessage('Error checking volume control support: ' . $e->getMessage(), 'error');
        return false;
    }
}

/**
 * Sanitizes input data based on the specified type.
 *
 * @param mixed $data Input data to sanitize
 * @param string $type Type of data (int, ip, string)
 * @param array $options Additional options such as min and max ranges
 * @return mixed Sanitized data or null if invalid
 */
function sanitizeInput($data, $type, $options = []) {
    switch ($type) {
        case 'int':
            $sanitized = filter_var($data, FILTER_VALIDATE_INT, [
                'options' => [
                    'min_range' => $options['min'] ?? PHP_INT_MIN,
                    'max_range' => $options['max'] ?? PHP_INT_MAX
                ]
            ]);
            break;
        case 'ip':
            $sanitized = filter_var($data, FILTER_VALIDATE_IP);
            break;
        case 'string':
            $sanitized = filter_var($data, FILTER_SANITIZE_STRING);
            break;
        default:
            $sanitized = null;
    }
    return $sanitized !== false ? $sanitized : null;
}

/**
 * Logs a message to a file based on the specified logging level.
 *
 * @param string $message The message to log
 * @param string $level Log level ('info', 'error')
 */
function logMessage($message, $level = 'info') {
    if ($level === 'error' || strtolower(LOG_LEVEL) === $level) {
        $timestamp = date('Y-m-d H:i:s');
        $formattedMessage = "[$timestamp] [$level] $message" . PHP_EOL;
        error_log($formattedMessage, 3, __DIR__ . '/av_controls.log');
    }
}

/**
 * Loads IR command payloads from a specified file.
 *
 * @param string $filename The file containing IR command payloads
 * @return array Associative array of actions to IR command codes
 */
function loadPayloads($filename) {
    $payloads = [];
    if (file_exists($filename)) {
        $lines = file($filename, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            list($action, $irCode) = explode('=', $line, 2);
            $payloads[trim($action)] = trim($irCode);
        }
    }
    return $payloads;
}
