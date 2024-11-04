<?php
/**
 * Configuration Management Interface
 */

// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

@require_once __DIR__ . '/config.php';

// Default values in case constants aren't defined
$defaultConfig = [
    'receivers' => defined('RECEIVERS') ? RECEIVERS : [],
    'transmitters' => defined('TRANSMITTERS') ? TRANSMITTERS : [],
    'max_volume' => defined('MAX_VOLUME') ? MAX_VOLUME : 11,
    'min_volume' => defined('MIN_VOLUME') ? MIN_VOLUME : 0,
    'volume_step' => defined('VOLUME_STEP') ? VOLUME_STEP : 1,
    'home_url' => defined('HOME_URL') ? HOME_URL : 'http://localhost',
    'log_level' => defined('LOG_LEVEL') ? LOG_LEVEL : 'error',
    'api_timeout' => defined('API_TIMEOUT') ? API_TIMEOUT : 5
];

// Check file permissions
$configFile = __DIR__ . '/config.php';
$isWritable = is_writable($configFile);
$filePerms = substr(sprintf('%o', fileperms($configFile)), -4);
try {
    $fileOwner = posix_getpwuid(fileowner($configFile));
    $webUser = posix_getpwuid(posix_geteuid());
} catch (Exception $e) {
    $fileOwner = ['name' => 'unknown'];
    $webUser = ['name' => 'unknown'];
}

// Initialize form data with current config or defaults
$formData = $defaultConfig;

// Handle configuration updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (!$isWritable) {
            throw new Exception(
                "Config file is not writable. Current permissions: {$filePerms}. " .
                "File owner: {$fileOwner['name']}. Web user: {$webUser['name']}. " .
                "Please make the file writable by running: chmod 666 {$configFile}"
            );
        }

        // Clean up old backups - keep only 3 most recent
        $backups = glob(__DIR__ . '/config_backup_*.php');
        rsort($backups); // Sort newest first
        foreach (array_slice($backups, 3) as $oldBackup) {
            @unlink($oldBackup);
        }

        // Store form data for persistence
        if (isset($_POST['receiver_name'])) {
            $formData['receivers'] = [];
            foreach ($_POST['receiver_name'] as $index => $name) {
                if (!empty($name)) {
                    $formData['receivers'][$name] = [
                        'ip' => $_POST['receiver_ip'][$index],
                        'show_power' => isset($_POST['receiver_power'][$index]) && $_POST['receiver_power'][$index] == '1'
                    ];
                }
            }
        }

        if (isset($_POST['transmitter_name'])) {
            $formData['transmitters'] = [];
            foreach ($_POST['transmitter_name'] as $index => $name) {
                if (!empty($name)) {
                    $formData['transmitters'][$name] = $_POST['transmitter_channel'][$index];
                }
            }
        }

        if (isset($_POST['max_volume'])) {
            $formData['max_volume'] = $_POST['max_volume'];
            $formData['min_volume'] = $_POST['min_volume'];
            $formData['volume_step'] = $_POST['volume_step'];
            $formData['home_url'] = $_POST['home_url'];
            $formData['log_level'] = $_POST['log_level'];
            $formData['api_timeout'] = $_POST['api_timeout'];
        }

        // Validate input based on which section was submitted
        $section = $_POST['section'] ?? 'all';
        
        if ($section === 'receivers' || $section === 'all') {
            // Process receivers
            $receivers = [];
            foreach ($_POST['receiver_name'] as $index => $name) {
                if (!empty($name) && !empty($_POST['receiver_ip'][$index])) {
                    // Validate IP address
                    $ip = filter_var($_POST['receiver_ip'][$index], FILTER_VALIDATE_IP);
                    if (!$ip) {
                        throw new Exception("Invalid IP address for receiver: " . htmlspecialchars($name));
                    }
                    $receivers[$name] = [
                        'ip' => $ip,
                        'show_power' => isset($_POST['receiver_power'][$index]) && $_POST['receiver_power'][$index] == '1'
                    ];
                }
            }
        } else {
            $receivers = $defaultConfig['receivers'];
        }

        if ($section === 'transmitters' || $section === 'all') {
            // Process transmitters
            $transmitters = [];
            foreach ($_POST['transmitter_name'] as $index => $name) {
                if (!empty($name) && !empty($_POST['transmitter_channel'][$index])) {
                    $channel = filter_var($_POST['transmitter_channel'][$index], FILTER_VALIDATE_INT);
                    if ($channel === false || $channel < 1) {
                        throw new Exception("Invalid channel number for transmitter: " . htmlspecialchars($name));
                    }
                    $transmitters[$name] = $channel;
                }
            }
        } else {
            $transmitters = $defaultConfig['transmitters'];
        }

        if ($section === 'global' || $section === 'all') {
            // Validate global settings
            $maxVolume = filter_var($_POST['max_volume'], FILTER_VALIDATE_INT);
            $minVolume = filter_var($_POST['min_volume'], FILTER_VALIDATE_INT);
            $volumeStep = filter_var($_POST['volume_step'], FILTER_VALIDATE_INT);
            $apiTimeout = filter_var($_POST['api_timeout'], FILTER_VALIDATE_INT);

            if ($maxVolume === false || $minVolume === false || $volumeStep === false || $apiTimeout === false) {
                throw new Exception("Invalid numeric value in global settings");
            }

            if ($minVolume >= $maxVolume) {
                throw new Exception("Minimum volume must be less than maximum volume");
            }

            if ($volumeStep <= 0) {
                throw new Exception("Volume step must be greater than 0");
            }

            if ($apiTimeout <= 0) {
                throw new Exception("API timeout must be greater than 0");
            }

            // Process global settings
            $config['MAX_VOLUME'] = $maxVolume;
            $config['MIN_VOLUME'] = $minVolume;
            $config['VOLUME_STEP'] = $volumeStep;
            $config['HOME_URL'] = filter_var($_POST['home_url'], FILTER_VALIDATE_URL);
            $config['LOG_LEVEL'] = in_array($_POST['log_level'], ['error', 'info', 'debug']) ? $_POST['log_level'] : 'error';
            $config['API_TIMEOUT'] = $apiTimeout;
        }

        // Generate new config file content
        $configContent = "<?php\n/**\n * Generated Configuration File\n * Last Updated: " . date('Y-m-d H:i:s') . "\n */\n\n";
        
        // Add receivers
        $configContent .= "const RECEIVERS = [\n";
        foreach ($receivers as $name => $settings) {
            $configContent .= "    " . var_export($name, true) . " => [\n";
            $configContent .= "        'ip' => " . var_export($settings['ip'], true) . ",\n";
            $configContent .= "        'show_power' => " . var_export($settings['show_power'], true) . "\n";
            $configContent .= "    ],\n";
        }
        $configContent .= "];\n\n";

        // Add transmitters
        $configContent .= "const TRANSMITTERS = [\n";
        foreach ($transmitters as $name => $channel) {
            $configContent .= "    " . var_export($name, true) . " => " . var_export($channel, true) . ",\n";
        }
        $configContent .= "];\n\n";

       // Replace the global settings section (around line 181) with this:

       if ($section === 'global' || $section === 'all') {
        // Initialize config array
        $config = [];
        
        // Validate global settings
        $maxVolume = filter_var($_POST['max_volume'], FILTER_VALIDATE_INT);
        $minVolume = filter_var($_POST['min_volume'], FILTER_VALIDATE_INT);
        $volumeStep = filter_var($_POST['volume_step'], FILTER_VALIDATE_INT);
        $apiTimeout = filter_var($_POST['api_timeout'], FILTER_VALIDATE_INT);

        if ($maxVolume === false || $minVolume === false || $volumeStep === false || $apiTimeout === false) {
            throw new Exception("Invalid numeric value in global settings");
        }

        if ($minVolume >= $maxVolume) {
            throw new Exception("Minimum volume must be less than maximum volume");
        }

        if ($volumeStep <= 0) {
            throw new Exception("Volume step must be greater than 0");
        }

        if ($apiTimeout <= 0) {
            throw new Exception("API timeout must be greater than 0");
        }

        // Process global settings
        $config['MAX_VOLUME'] = $maxVolume;
        $config['MIN_VOLUME'] = $minVolume;
        $config['VOLUME_STEP'] = $volumeStep;
        $homeUrl = filter_var($_POST['home_url'], FILTER_VALIDATE_URL);
        if ($homeUrl === false) {
            throw new Exception("Invalid home URL");
        }
        $config['HOME_URL'] = $homeUrl;
        $config['LOG_LEVEL'] = in_array($_POST['log_level'], ['error', 'info', 'debug']) ? $_POST['log_level'] : 'error';
        $config['API_TIMEOUT'] = $apiTimeout;
    } else {
        // If we're not updating global settings, use current/default values
        $config = [
            'MAX_VOLUME' => $formData['max_volume'],
            'MIN_VOLUME' => $formData['min_volume'],
            'VOLUME_STEP' => $formData['volume_step'],
            'HOME_URL' => $formData['home_url'],
            'LOG_LEVEL' => $formData['log_level'],
            'API_TIMEOUT' => $formData['api_timeout']
        ];
    }

        // Add default constants if they don't exist in $config
        if (!isset($config['REMOTE_CONTROL_COMMANDS'])) {
            $configContent .= "\n// Remote control configuration\n";
            $configContent .= "const REMOTE_CONTROL_COMMANDS = " . var_export([
                'power', 'guide', 'up', 'down', 'left', 'right', 'select',
                'channel_up', 'channel_down', '0', '1', '2', '3', '4',
                '5', '6', '7', '8', '9', 'last', 'exit'
            ], true) . ";\n\n";
        }
        
        if (!isset($config['VOLUME_CONTROL_MODELS'])) {
            $configContent .= "const VOLUME_CONTROL_MODELS = " . var_export([
                '3G+4+ TX', '3G+AVP RX', '3G+AVP TX', '3G+WP4 TX', '2G/3G SX'
            ], true) . ";\n\n";
        }
        
        if (!isset($config['ERROR_MESSAGES'])) {
            $configContent .= "const ERROR_MESSAGES = " . var_export([
                'connection' => 'Unable to connect to %s (%s). Please check the connection and try again.',
                'global' => 'Unable to connect to any receivers. Please check your network connection and try again.',
                'remote' => 'Unable to send remote command. Please try again.'
            ], true) . ";\n\n";
        }
        
        $configContent .= "const LOG_FILE = __DIR__ . '/av_controls.log';\n";

        // Backup existing config
        $backupFile = __DIR__ . '/config_backup_' . date('Y-m-d_H-i-s') . '.php';
        if (!@copy($configFile, $backupFile)) {
            throw new Exception("Failed to create backup file");
        }

        // Write new config
        if (file_put_contents($configFile, $configContent) === false) {
            throw new Exception("Failed to write to config file. Please check file permissions.");
        }
        
        $message = ['type' => 'success', 'text' => 'Configuration updated successfully'];

    } catch (Exception $e) {
        $message = ['type' => 'error', 'text' => 'Error updating configuration: ' . $e->getMessage()];
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AV Control System Settings</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        .config-section {
            background: var(--surface-color);
            padding: 2rem;
            border-radius: 8px;
            margin-bottom: 2rem;
        }
        .config-grid {
            display: grid;
            gap: 1rem;
            margin-bottom: 1rem;
        }
        .config-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            align-items: center;
            padding: 0.5rem;
            background: rgba(0, 0, 0, 0.2);
            border-radius: 4px;
        }
        .config-input {
            padding: 0.5rem;
            border: 1px solid var(--primary-color);
            border-radius: 4px;
            background: var(--bg-color);
            color: var(--text-color);
        }
        .add-button {
            background: var(--success-color);
            color: white;
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .remove-button {
            background: var(--error-color);
            color: white;
            padding: 0.5rem;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .apply-button {
            background: var(--secondary-color);
            color: var(--bg-color);
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            margin-top: 1rem;
        }
        .message {
            padding: 1rem;
            border-radius: 4px;
            margin-bottom: 1rem;
        }
        .message.success {
            background: var(--success-color);
            color: white;
        }
        .message.error {
            background: var(--error-color);
            color: white;
        }
        .backup-info {
            margin-top: 1rem;
            padding: 1rem;
            background: rgba(0, 0, 0, 0.2);
            border-radius: 4px;
            font-size: 0.9em;
        }
        .permissions-warning {
            background: var(--warning-color);
            color: white;
            padding: 1rem;
            border-radius: 4px;
            margin-bottom: 1rem;
        }
        .validation-error {
            color: var(--error-color);
            font-size: 0.9em;
            margin-top: 0.25rem;
        }
    </style>
</head>
<body>
    <div class="content-wrapper">
        <header>
            <div class="logo-title-group">
                <h1>AV Control System Settings</h1>
            </div>
            <div class="header-buttons">
                <a href="index.php" class="button home-button">Back to Control Panel</a>
            </div>
        </header>

        <?php if (!$isWritable): ?>
        <div class="permissions-warning">
            Warning: Configuration file is not writable. Current permissions: <?php echo $filePerms; ?><br>
            File owner: <?php echo $fileOwner['name']; ?>, Web user: <?php echo $webUser['name']; ?><br>
            Please make the file writable by running: chmod 666 <?php echo $configFile; ?>
        </div>
        <?php endif; ?>

        <?php if (isset($message)): ?>
            <div class="message <?php echo $message['type']; ?>">
                <?php echo $message['text']; ?>
            </div>
        <?php endif; ?>

        <div class="main-container">
            <form method="POST" action="settings.php">
                <!-- Receivers Section -->
                <div class="config-section">
                    <h2>Receivers Configuration</h2>
                    <div id="receivers-container" class="config-grid">
                        <?php foreach ($formData['receivers'] as $name => $settings): ?>
                        <div class="config-row">
                            <input type="text" name="receiver_name[]" 
                                   value="<?php echo htmlspecialchars($name); ?>" 
                                   placeholder="Receiver Name" 
                                   class="config-input" 
                                   required>
                                   <input type="text" name="receiver_ip[]" 
                                  value="<?php echo htmlspecialchars($settings['ip']); ?>" 
                                  placeholder="IP Address" 
                                  class="config-input" 
                                  pattern="^(?:[0-9]{1,3}\.){3}[0-9]{1,3}$"
                                  title="Please enter a valid IP address"
                                  required>
                           <label>
                               <input type="checkbox" name="receiver_power[]" value="1" 
                                      <?php echo $settings['show_power'] ? 'checked' : ''; ?>>
                               Show Power Controls
                           </label>
                           <button type="button" class="remove-button" onclick="this.parentElement.remove()">Remove</button>
                       </div>
                       <?php endforeach; ?>
                   </div>
                   <button type="button" class="add-button" onclick="addReceiver()">Add Receiver</button>
                   <button type="submit" name="section" value="receivers" class="apply-button" onclick="return confirmSave('receivers')">Apply Receiver Changes</button>
               </div>

               <!-- Transmitters Section -->
               <div class="config-section">
                   <h2>Transmitters Configuration</h2>
                   <div id="transmitters-container" class="config-grid">
                       <?php foreach ($formData['transmitters'] as $name => $channel): ?>
                       <div class="config-row">
                           <input type="text" name="transmitter_name[]" 
                                  value="<?php echo htmlspecialchars($name); ?>" 
                                  placeholder="Transmitter Name" 
                                  class="config-input"
                                  required>
                           <input type="number" name="transmitter_channel[]" 
                                  value="<?php echo htmlspecialchars($channel); ?>" 
                                  placeholder="Channel" 
                                  class="config-input"
                                  min="1"
                                  required>
                           <button type="button" class="remove-button" onclick="this.parentElement.remove()">Remove</button>
                       </div>
                       <?php endforeach; ?>
                   </div>
                   <button type="button" class="add-button" onclick="addTransmitter()">Add Transmitter</button>
                   <button type="submit" name="section" value="transmitters" class="apply-button" onclick="return confirmSave('transmitters')">Apply Transmitter Changes</button>
               </div>

               <!-- Global Settings Section -->
               <div class="config-section">
                   <h2>Global Settings</h2>
                   <div class="config-grid">
                       <div class="config-row">
                           <label>Max Volume:</label>
                           <input type="number" name="max_volume" 
                                  value="<?php echo $formData['max_volume']; ?>" 
                                  class="config-input" 
                                  min="1"
                                  required>
                       </div>
                       <div class="config-row">
                           <label>Min Volume:</label>
                           <input type="number" name="min_volume" 
                                  value="<?php echo $formData['min_volume']; ?>" 
                                  class="config-input" 
                                  min="0"
                                  required>
                       </div>
                       <div class="config-row">
                           <label>Volume Step:</label>
                           <input type="number" name="volume_step" 
                                  value="<?php echo $formData['volume_step']; ?>" 
                                  class="config-input" 
                                  min="1"
                                  required>
                       </div>
                       <div class="config-row">
                           <label>Home URL:</label>
                           <input type="url" name="home_url" 
                                  value="<?php echo $formData['home_url']; ?>" 
                                  class="config-input" 
                                  required>
                       </div>
                       <div class="config-row">
                           <label>Log Level:</label>
                           <select name="log_level" class="config-input">
                               <option value="error" <?php echo $formData['log_level'] === 'error' ? 'selected' : ''; ?>>Error</option>
                               <option value="info" <?php echo $formData['log_level'] === 'info' ? 'selected' : ''; ?>>Info</option>
                               <option value="debug" <?php echo $formData['log_level'] === 'debug' ? 'selected' : ''; ?>>Debug</option>
                           </select>
                       </div>
                       <div class="config-row">
                           <label>API Timeout (seconds):</label>
                           <input type="number" name="api_timeout" 
                                  value="<?php echo $formData['api_timeout']; ?>" 
                                  class="config-input" 
                                  min="1"
                                  required>
                       </div>
                   </div>
                   <button type="submit" name="section" value="global" class="apply-button" onclick="return confirmSave('global')">Apply Global Changes</button>
               </div>

               <div class="config-section">
                   <button type="submit" name="section" value="all" class="button home-button" onclick="return confirmSave('all')">Save All Changes</button>
                   <div class="backup-info">
                       Note: A backup of the current configuration will be automatically created before saving any changes.
                       Only the 3 most recent backups are kept.
                   </div>
               </div>
           </form>
       </div>
   </div>

   <script>
       function confirmSave(section) {
           const message = section === 'all' 
               ? 'Are you sure you want to save all changes?' 
               : `Are you sure you want to save changes to the ${section} section?`;
           return confirm(message);
       }

       function validateIpAddress(input) {
           const ip = input.value;
           const valid = /^(?:[0-9]{1,3}\.){3}[0-9]{1,3}$/.test(ip);
           const error = input.parentElement.querySelector('.validation-error');
           
           if (!valid) {
               if (!error) {
                   const errorDiv = document.createElement('div');
                   errorDiv.className = 'validation-error';
                   errorDiv.textContent = 'Please enter a valid IP address';
                   input.parentElement.appendChild(errorDiv);
               }
               return false;
           } else if (error) {
               error.remove();
           }
           return true;
       }

       function addReceiver() {
           const container = document.getElementById('receivers-container');
           const row = document.createElement('div');
           row.className = 'config-row';
           row.innerHTML = `
               <input type="text" name="receiver_name[]" 
                      placeholder="Receiver Name" 
                      class="config-input" 
                      required>
               <input type="text" name="receiver_ip[]" 
                      placeholder="IP Address" 
                      class="config-input" 
                      pattern="^(?:[0-9]{1,3}\.){3}[0-9]{1,3}$"
                      title="Please enter a valid IP address"
                      onchange="validateIpAddress(this)"
                      required>
               <label>
                   <input type="checkbox" name="receiver_power[]" value="1">
                   Show Power Controls
               </label>
               <button type="button" class="remove-button" onclick="this.parentElement.remove()">Remove</button>
           `;
           container.appendChild(row);
       }

       function addTransmitter() {
           const container = document.getElementById('transmitters-container');
           const row = document.createElement('div');
           row.className = 'config-row';
           row.innerHTML = `
               <input type="text" name="transmitter_name[]" 
                      placeholder="Transmitter Name" 
                      class="config-input"
                      required>
               <input type="number" name="transmitter_channel[]" 
                      placeholder="Channel" 
                      class="config-input"
                      min="1"
                      required>
               <button type="button" class="remove-button" onclick="this.parentElement.remove()">Remove</button>
           `;
           container.appendChild(row);
       }
   </script>
</body>
</html>
