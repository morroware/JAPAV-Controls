Here's a comprehensive `README.md` tailored to showcase and explain your AV Control System project, designed to highlight its purpose, setup instructions, and functionality based on the full understanding of your project files and utilities.

---

# AV Control System for Just Add Power Devices

![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)
> **A centralized, web-based control system for managing Just Add Power AV devices in commercial environments.**

This system is built with PHP, JavaScript, and modern web technologies to provide an intuitive interface for controlling multiple displays, audio systems, and content sources. The project offers robust device management, a remote control interface, and a customizable administrative toolset for managing AV setups across various venues.

## 📜 Table of Contents
- [Features](#features)
- [System Requirements](#system-requirements)
- [Installation](#installation)
- [Project Structure](#project-structure)
- [Configuration](#configuration)
- [Usage](#usage)
- [API Endpoints](#api-endpoints)
- [Security Recommendations](#security-recommendations)
- [Troubleshooting](#troubleshooting)
- [Development](#development)
- [License](#license)

---

## 🚀 Features

### Device Management
- **Display Control**: Manage individual and grouped displays with power, input source, and volume control options.
- **Volume Control**: Adjustable volume limits and customizable volume steps for fine-tuned control.
- **Input Selection**: Easy-to-use interface for selecting input sources across multiple displays.

### Remote Control Interface
- **IR Command System**: Control compatible cable boxes with an on-screen keypad, navigation controls, and channel management.
- **Customizable IR Commands**: Easily configure and expand supported IR commands via the `payloads.txt` file.

### Administrative Tools
- **Real-Time AJAX Updates**: Instant feedback and control updates without reloading the page.
- **Responsive Design**: Interface is fully mobile-friendly with a responsive layout for various device sizes.
- **Dark Mode & Accessibility**: Includes dark mode and features for users with accessibility needs, such as high-contrast and reduced-motion options.
- **Web-Based Configuration Panel**: Set up and manage device configurations, including receiver and transmitter details, network settings, and backup management.

---

## 📋 System Requirements

- **Web Server**: Apache or Nginx
- **PHP Version**: 7.4 or higher
- **Required PHP Extensions**: `curl`, `json`, `fileinfo`
- **Network Access**: Devices must be accessible over the network for control and monitoring.
- **Modern Web Browser**: Supports Chrome, Firefox, Safari, and Edge.

---

## 🔧 Installation

### 1. Clone the Repository
```bash
git clone https://github.com/yourusername/av-control-system.git
cd av-control-system
```

### 2. Set File Permissions
Set the appropriate permissions for PHP and configuration files.
```bash
# PHP and config files
chmod 755 *.php
chmod 644 payloads.txt transmitters.txt styles.css script.js
```

### 3. Configure Environment
- **Configuration Files**: Copy `config.example.php` to `config.php`.
- **Device List**: Edit `transmitters.txt` with IP addresses for each device.
- **IR Commands**: Update `payloads.txt` to configure available IR commands.

### 4. Access Configuration Panel
Navigate to `http://your-server/settings.php` in a browser to configure devices, volume limits, network timeouts, and error logging.

---

## 📁 Project Structure

```
├── api.php           # API handler for device control and updates
├── config.php        # Main configuration file
├── index.php         # Main control interface
├── settings.php      # Configuration management interface
├── template.php      # HTML template for control panel layout
├── utils.php         # Utility functions for backend operations
├── script.js         # Frontend JavaScript for AJAX and interface controls
├── styles.css        # Stylesheet for control panel design
├── payloads.txt      # IR command definitions
└── transmitters.txt  # List of device IP addresses
```

---

## ⚙️ Configuration

### 1. Device Setup
Edit `transmitters.txt` to define each device's IP and name. Format:
```
Device Name, http://device-ip-address
```

### 2. IR Commands
Define each command in `payloads.txt` with a format of `command_name=ir_code`. Example:
```
power=sendir,1:1,1,38000,1,1,192,192,48,145...
volume_up=sendir,1:1,1,38000,1,1,193...
volume_down=0000 0048 0000 0018 00c1...
```

### 3. System Settings
Settings can be managed through `settings.php`, including:
- Device configurations
- Volume limits and steps
- Network API timeouts
- Logging level and options

---

## 💻 Usage

1. **Access Control Panel**: Open `index.php` to access the main AV control panel.
2. **Device Controls**: Use the intuitive UI to control power, volume, and input sources.
3. **IR Remote Controls**: Manage compatible devices (e.g., cable boxes) with on-screen controls.
4. **Configuration Panel**: Update settings through `settings.php`.

### Receiver Control Features
- **Power Controls**: Toggle power for individual or grouped receivers.
- **Channel Selection**: Select channels from a list of configured transmitters.
- **Volume Adjustment**: Adjust volume with customizable steps and limits.

---

## 🔄 API Endpoints

### Device Control
Send POST requests to control device actions.

#### Power Commands
```php
POST /api.php
{
    "device_url": "http://device-ip",
    "action": "power_on" // or "power_off"
}
```

#### Volume Control
```php
POST /api.php
{
    "receiver_ip": "device-ip",
    "volume": 50 // volume level
}
```

#### IR Command
```php
POST /api.php
{
    "device_url": "http://device-ip",
    "action": "volume_up" // or any other IR command defined in payloads.txt
}
```

---

## 🔐 Security Recommendations

- **Restrict IP Access**: Limit access to trusted networks or specific IPs.
- **Use VLAN Segmentation**: Separate AV device network from general use networks.
- **Secure Config Files**: Ensure permissions for `config.php` are set to 640 or lower.
- **Log Monitoring**: Regularly review logs stored in `av_controls.log` to monitor activity and troubleshoot issues.

---

## 🛠️ Troubleshooting

### Common Issues

1. **Device Not Responding**
   - Check the network connection and IP address configuration in `transmitters.txt`.
   - Verify device power status.

2. **IR Command Issues**
   - Double-check command definitions in `payloads.txt`.
   - Ensure IR transmitter setup is correct and operational.

3. **Permission Errors**
   - Confirm correct permissions for `config.php` and log files.

4. **API Connection Errors**
   - Review network settings and verify API endpoint availability on each device.

### Log File
Logs are saved in `av_controls.log` with timestamps, error levels, and messages.

---

## 💻 Development

### Requirements
- **PHP 7.4+**
- **Modern Browser** for testing
- **Network Access** to AV devices

### Code Standards
- Follows **PSR-12** for PHP code.
- Use **consistent documentation** and error handling practices.
- Prioritize **security** and **performance** in all code.

### Development Setup
Clone the repository, then:
```bash
composer install
cp config.example.php config.php
```

---

## 📝 License

This project is licensed under the MIT License. See the [LICENSE](LICENSE) file for details.

---

Built with ❤️ to simplify AV control in complex environments.
