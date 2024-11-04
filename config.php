<?php
/**
 * System Configuration File
 * This file defines constants for configuring receivers, transmitters, remote commands, volume control models,
 * error messages, and logging settings in the AV Control System.
 * 
 * Last Updated: 2024-11-03
 */

// Define the RECEIVERS configuration, mapping each receiver by name to its IP and visibility settings.
const RECEIVERS = [
    'Bowling Bar TV 1' => [
        'ip' => '192.168.8.60',     // IP address of the receiver
        'show_power' => true        // Boolean indicating if power control should be visible for this device
    ],
    'Bowling Bar TV 2' => [
        'ip' => '192.168.8.61',
        'show_power' => true
    ],
    'Bowling Bar TV 3' => [
        'ip' => '192.168.8.62',
        'show_power' => true
    ],
    'Bowling Bar TV 4' => [
        'ip' => '192.168.8.63',
        'show_power' => true
    ],
    'Bowling Bar Music' => [
        'ip' => '192.168.8.28',
        'show_power' => true
    ],
    'Axe/Billiards Music' => [
        'ip' => '192.168.8.27',
        'show_power' => false       // This receiver's power control is hidden in the UI
    ],
    'Bar TV 1' => [
        'ip' => '192.168.8.12',
        'show_power' => false
    ],
];

// Define the TRANSMITTERS configuration, mapping each transmitter to a numeric ID.
// These IDs may correspond to device identifiers used in the backend or IR system.
const TRANSMITTERS = [
    'Apple TV' => 7,
    'Cable Box 1' => 2,
    'Cable Box 2' => 3,
    'Cable Box 3' => 4,
    'Unifi Signage' => 5,
    'RockBot Audio' => 1,
    'Rink Spare Audio' => 6,
    'Rink Spare Video' => 8,
];

// Define remote control commands with a constant array, where each command is assigned a unique ID.
// These commands are used to send specific actions to the AV receivers, enabling remote control functionality.
const REMOTE_CONTROL_COMMANDS = array (
    0 => 'power',          // Power toggle
    1 => 'guide',          // Guide button
    2 => 'up',             // Up navigation
    3 => 'down',           // Down navigation
    4 => 'left',           // Left navigation
    5 => 'right',          // Right navigation
    6 => 'select',         // Select/Enter button
    7 => 'channel_up',     // Channel up
    8 => 'channel_down',   // Channel down
    9 => '0',              // Numeric button 0
    10 => '1',             // Numeric button 1
    11 => '2',             // Numeric button 2
    12 => '3',             // Numeric button 3
    13 => '4',             // Numeric button 4
    14 => '5',             // Numeric button 5
    15 => '6',             // Numeric button 6
    16 => '7',             // Numeric button 7
    17 => '8',             // Numeric button 8
    18 => '9',             // Numeric button 9
    19 => 'last',          // Last channel button
    20 => 'exit',          // Exit or back button
);

// Define volume control models, mapping model names to their IDs.
// These models specify different device types with distinct volume control capabilities.
const VOLUME_CONTROL_MODELS = array (
    0 => '3G+4+ TX',       // Model 3G+4+ TX
    1 => '3G+AVP RX',      // Model 3G+AVP RX
    2 => '3G+AVP TX',      // Model 3G+AVP TX
    3 => '3G+WP4 TX',      // Model 3G+WP4 TX
    4 => '2G/3G SX',       // Model 2G/3G SX
);

// Define error messages for specific situations to provide clear feedback to the user or administrator.
// %s placeholders allow dynamic information (like IP addresses) to be included in the messages.
const ERROR_MESSAGES = array (
    'connection' => 'Unable to connect to %s (%s). Please check the connection and try again.', // Error message for individual connection failures
    'global' => 'Unable to connect to any receivers. Please check your network connection and try again.', // Error message when no devices are reachable
    'remote' => 'Unable to send remote command. Please try again.', // Error message for remote control failures
);

// Define the path for the log file, where system events or errors are recorded for troubleshooting.
const LOG_FILE = __DIR__ . '/av_controls.log';
