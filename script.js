/**
 * Combined JavaScript for AV Controls and Remote Control System
 * This script manages interaction with AV receivers and transmitters, handling commands for power, volume,
 * and channel control, as well as remote command execution through AJAX and Fetch API calls.
 */

document.addEventListener('DOMContentLoaded', function() {
    // Initialize both AV control systems once the document is fully loaded
    initializeReceiverControls();
    loadTransmitters();
});

/**
 * Initializes the receiver control forms and power toggle buttons
 * Attaches event handlers for form submission and "power all" buttons
 */
function initializeReceiverControls() {
    // Attach a submit event handler to each receiver form
    $('.receiver form').on('submit', function(e) {
        e.preventDefault(); // Prevent the form from reloading the page
        const form = $(this);
        
        // Send the form data to the server via AJAX
        $.ajax({
            url: '',  // This will use the current URL; consider specifying if different
            type: 'POST',
            data: form.serialize(),  // Serialize form data for transmission
            dataType: 'json',
            success: function(response) {
                // Show a response message based on the success status
                showResponseMessage(response.message, response.success);
            },
            error: function() {
                // Display an error message if the AJAX request fails
                showResponseMessage('Failed to update settings', false);
            }
        });
    });

    // Attach click event handlers for the "power all on" and "power all off" buttons
    $('#power-all-on').on('click', function() {
        sendPowerCommandToAll('cec_tv_on.sh');  // Send "power on" command to all devices
    });

    $('#power-all-off').on('click', function() {
        sendPowerCommandToAll('cec_tv_off.sh'); // Send "power off" command to all devices
    });
}

/**
 * Updates the volume label display with the slider's current value
 * @param {HTMLElement} slider - The range input element for volume control
 */
function updateVolumeLabel(slider) {
    const label = slider.parentElement.querySelector('.volume-label');
    if (label) {
        // Update the volume label text to reflect the slider's value
        label.textContent = slider.value;
    }
}

/**
 * Sends a power command to a specific device
 * @param {string} deviceIp - IP address of the receiver device
 * @param {string} command - Power command to send (e.g., "cec_tv_on.sh" or "cec_tv_off.sh")
 * @return {jqXHR} - jQuery AJAX promise for handling the request
 */
function sendPowerCommand(deviceIp, command) {
    return $.ajax({
        url: '',  // URL to send the command to; should be configured for specific endpoint
        type: 'POST',
        data: {
            receiver_ip: deviceIp,
            power_command: command
        },
        dataType: 'json'
    });
}

/**
 * Sends a power command to all receiver devices
 * @param {string} command - Power command to send to all devices
 */
function sendPowerCommandToAll(command) {
    const receivers = $('.receiver');
    let promises = [];

    // Loop through each receiver element and send the power command
    receivers.each(function() {
        const deviceIp = $(this).find('input[name="receiver_ip"]').val(); // Get IP from form input
        promises.push(sendPowerCommand(deviceIp, command)); // Store each AJAX request in promises array
    });

    // Execute all power commands simultaneously and return a combined promise
    Promise.all(promises);
}

/**
 * Displays a response message to the user
 * @param {string} message - Message text to display
 * @param {boolean} success - Determines message styling (success or error)
 */
function showResponseMessage(message, success) {
    const responseElement = $('#response-message');
    
    // Update the message style and content, then show it on the page
    responseElement
        .removeClass('success error')
        .addClass(success ? 'success' : 'error')
        .html(message)
        .fadeIn();

    // Automatically hide the message after 5 seconds
    setTimeout(() => responseElement.fadeOut(), 5000);
}

/**
 * Loads transmitter options from 'transmitters.txt' and populates a dropdown menu
 * Each transmitter option includes the device name and URL
 */
function loadTransmitters() {
    fetch('transmitters.txt')
        .then(response => response.text()) // Get the text content of the file
        .then(data => {
            // Split the file content by lines and filter out empty lines
            const transmitters = data.split('\n').filter(line => line.trim() !== '');
            
            // Create a dropdown (select element) to list the transmitters
            const select = document.createElement('select');
            select.id = 'transmitter';
            
            // Populate the dropdown with transmitter options
            transmitters.forEach(transmitter => {
                const [name, url] = transmitter.split(',').map(item => item.trim());
                const option = document.createElement('option');
                option.value = url;         // Set the URL as the option's value
                option.textContent = name;  // Set the transmitter's name as the visible text
                select.appendChild(option); // Add the option to the dropdown
            });
            
            // Insert the dropdown into the designated container in the HTML
            const container = document.getElementById('transmitter-select');
            container.innerHTML = 'Select Transmitter: ';
            container.appendChild(select);
        })
        .catch(error => {
            // Log any errors and show an error message if loading fails
            console.error('Error loading transmitters:', error);
            showError('Failed to load transmitters');
        });
}

/**
 * Sends a remote control command to a selected transmitter
 * @param {string} action - Action command to be executed (e.g., "channel_up", "power")
 */
function sendCommand(action) {
    const transmitter = document.getElementById('transmitter');
    
    // Check if a transmitter is selected before sending a command
    if (!transmitter || !transmitter.value) {
        showError('Please select a transmitter'); // Show error if no transmitter is selected
        return;
    }

    // Send the action command to the selected transmitter via AJAX
    $.ajax({
        url: 'api.php',  // Endpoint for command handling
        type: 'POST',
        data: {
            device_url: transmitter.value,  // URL of the selected transmitter
            action: action                  // Action command to be sent
        },
        dataType: 'json'
    }).fail(function(error) {
        // Display an error message if the AJAX request fails
        showError('Failed to send command');
    });
}

/**
 * Displays an error message on the page
 * @param {string} message - Error message text to display
 */
function showError(message) {
    const errorElement = document.getElementById('error-message');
    const errorTextElement = document.getElementById('error-text');
    
    // Update the error message text and make the error message visible
    errorTextElement.textContent = message;
    errorElement.style.display = 'block';
    
    // Automatically hide the error message after 5 seconds
    setTimeout(() => {
        errorElement.style.display = 'none';
    }, 5000);
}
