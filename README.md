# Just Add Power AV Control System

![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)

> **A centralized, web-based control system for managing Just Add Power AV over IP devices in commercial environments.**

This system provides an intuitive web interface for controlling multiple Just Add Power devices, managing displays, and routing AV signals across venues such as bars, restaurants, and entertainment facilities. Built with PHP, JavaScript, and modern web technologies, it offers comprehensive device management, remote control capabilities, and a robust administrative interface.

## 📜 Table of Contents
- [Features](#-features)
- [System Requirements](#-system-requirements)
- [Installation](#-installation)
- [Project Structure](#-project-structure)
- [Configuration](#%EF%B8%8F-configuration)
- [Usage Guide](#-usage-guide)
- [API Integration](#-api-integration)
- [Security](#-security)
- [Troubleshooting](#%EF%B8%8F-troubleshooting)
- [Development](#-development)
- [Support & Maintenance](#-support--maintenance)
- [License](#-license)

## 🚀 Features

### Matrix Control Interface
- **Multi-Device Management**: Control multiple receivers simultaneously
- **Individual Receiver Cards**: Dedicated control panels for each device showing:
  - Current input source
  - Volume level (for supported devices)
  - Power status
  - Connection state
- **Global Controls**: Power all displays on/off simultaneously
- **Status Monitoring**: Real-time connection and state monitoring
- **Retry Functionality**: One-click reconnection for disconnected devices

### Remote Control Interface
- **Virtual Remote**: On-screen remote control for source devices
- **Channel Controls**: 
  - Direct numeric input
  - Channel up/down
  - Last channel recall
- **Navigation Controls**: 
  - Directional pad
  - Select button
  - Guide and exit functions
- **IR Command System**: Customizable IR commands via `payloads.txt`

### Administrative Features
- **Web-Based Configuration**: Access via Control+Click on logo
- **Device Management**:
  - Add/Remove receivers and transmitters
  - Configure IP addresses
  - Set power control options
- **System Settings**:
  - Volume limits and steps
  - API timeout values
  - Logging preferences
- **Backup Management**:
  - Automatic configuration backups
  - Restore from previous configs
  - Backup rotation (keeps last 3)

### User Interface
- **Responsive Design**: Mobile-friendly layout
- **Dark Mode**: Built-in dark theme
- **Accessibility Features**:
  - High contrast support
  - Reduced motion options
  - Screen reader compatibility
- **Real-Time Updates**: AJAX-based instant feedback

## 📋 System Requirements

### Server Requirements
- **Web Server**: Apache 2.4+ or Nginx
- **PHP Version**: 7.4 or higher
- **PHP Extensions**:
  - curl
  - json
  - fileinfo
  - posix (recommended)
- **File Permissions**:
  - Write access for logs
  - Config file access
  - Backup directory access

### Network Requirements
- **Connectivity**: All devices on same subnet
- **Protocols**:
  - HTTP (Port 80)
  - Multicast (for device discovery)
- **Static IPs**: Required for all JAP devices
- **CEC Support**: For display power control
- **Network Configuration**:
  - Proper VLAN setup (recommended)
  - Multicast configuration
  - QoS settings (recommended)

### Client Requirements
- **Browsers**:
  - Chrome 80+
  - Firefox 75+
  - Safari 13+
  - Edge 80+
- **JavaScript**: Enabled
- **Features**:
  - ES6+ Support
  - CSS Grid
  - Flexbox
  - Fetch API

## 🔧 Installation

### 1. Server Setup
```bash
# Create web directory
mkdir -p /var/www/av-control
cd /var/www/av-control

# Clone repository or copy files
git clone https://github.com/yourusername/av-control-system.git .

# Set permissions
chown -R www-data:www-data .
find . -type f -exec chmod 644 {} \;
find . -type d -exec chmod 755 {} \;
chmod 666 av_controls.log
chmod 666 config.php
```

### 2. Web Server Configuration

#### Apache Configuration
```apache
<VirtualHost *:80>
    ServerName av.local
    DocumentRoot /var/www/av-control
    
    <Directory /var/www/av-control>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
        
        # Optional: IP restriction
        #Require ip 192.168.1.0/24
    </Directory>
    
    ErrorLog ${APACHE_LOG_DIR}/av-error.log
    CustomLog ${APACHE_LOG_DIR}/av-access.log combined
</VirtualHost>
```

#### Nginx Configuration
```nginx
server {
    listen 80;
    server_name av.local;
    root /var/www/av-control;
    
    location / {
        try_files $uri $uri/ /index.php?$query_string;
        
        # Optional: IP restriction
        #allow 192.168.1.0/24;
        #deny all;
    }
    
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php7.4-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

### 3. Initial Configuration
1. Access the web interface
2. Control+Click the logo to access settings
3. Configure your devices and system settings
4. Test connectivity to all devices

## 📁 Project Structure

```
├── api.php           # API endpoint handler
├── config.php        # Main configuration
├── index.php         # Main interface
├── settings.php      # Admin interface
├── template.php      # Interface template
├── utils.php         # Utility functions
├── script.js         # Frontend JavaScript
├── styles.css        # CSS styles
├── payloads.txt     # IR commands
├── transmitters.txt  # Device list
└── av_controls.log   # System log
```

### Key File Descriptions

#### api.php
Handles all API requests for:
- Remote control commands
- Power management
- Volume control
- Channel switching

#### config.php
```php
const RECEIVERS = [
    'Receiver Name' => [
        'ip' => '192.168.x.x',
        'show_power' => true/false
    ]
];

const TRANSMITTERS = [
    'Source Name' => channel_number
];

// System settings
const MAX_VOLUME = 11;
const MIN_VOLUME = 0;
const VOLUME_STEP = 1;
const API_TIMEOUT = 1;
const LOG_LEVEL = 'error';
```

#### payloads.txt
```text
power=sendir,1:1,1,58000,...
channel_up=sendir,1:1,1,58000,...
guide=sendir,1:1,1,58000,...
```

## ⚙️ Configuration

### Web Interface Configuration
Access the settings page via Control+Click on the logo to configure:

1. **Receivers**
   - Name and IP address
   - Power control options
   - Display settings

2. **Transmitters**
   - Source names
   - Channel assignments
   - Input configurations

3. **System Settings**
   - Volume controls
   - API timeouts
   - Logging preferences
   - Network settings

### Backup Management
- Automatic backups before changes
- Maintains last 3 backups
- Restore capability
- Pre-restore backup creation

## 💻 Usage Guide

### Basic Operations

1. **Display Control**
   - Select input source from dropdown
   - Adjust volume if supported
   - Control power via CEC
   - Monitor connection status

2. **Remote Control**
   - Select target transmitter
   - Use on-screen remote
   - Direct channel entry
   - Navigation controls

3. **Global Controls**
   - All displays on/off
   - System status overview
   - Error monitoring

### Advanced Features

1. **Volume Management**
   - Individual control per device
   - Global limits
   - Custom step sizes
   - Support detection

2. **Power Control**
   - CEC over HDMI
   - Individual or group control
   - Status monitoring
   - Failure detection

## 🔄 API Integration

### Just Add Power API Endpoints

```php
// Command endpoints
command/cli          # General commands
command/channel      # Input switching
command/audio/stereo/volume  # Volume control

// Status endpoints
details/channel      # Current input
details/audio/stereo/volume  # Current volume
details/device/model # Device information
```

### Error Handling
- Connection timeouts
- Retry mechanisms
- User feedback
- Detailed logging

## 🔐 Security

### Network Security
- Isolate AV network
- Use VLANs
- Implement firewalls
- Restrict management access

### Access Control
- Optional authentication
- IP restrictions
- Settings page protection
- Log monitoring

### File Security
- Proper permissions
- Regular backups
- Secure configuration
- Log rotation

## 🛠️ Troubleshooting

### Common Issues

1. **Connection Problems**
   - Check network connectivity
   - Verify IP addresses
   - Test device power
   - Review logs

2. **Power Control Issues**
   - Verify CEC support
   - Check HDMI connections
   - Test TV compatibility
   - Power cycle if needed

3. **Volume Control**
   - Check device support
   - Verify network access
   - Test audio routing

### Logging
```log
[2024-xx-xx xx:xx:xx] [error] Error message
[2024-xx-xx xx:xx:xx] [debug] Debug info
```

## 💻 Development

### Environment Setup
- PHP 7.4+
- Modern web browser
- Network access
- Development tools

### Standards
- PSR-12 compliance
- Consistent documentation
- Error handling
- Security practices

## 🔧 Support & Maintenance

### Regular Tasks
1. Log review
2. Backup cleanup
3. Connection testing
4. Updates

### Updates
1. Check for updates
2. Backup configuration
3. Test changes
4. Monitor logs

## 📄 License

This project is licensed under the MIT License. See LICENSE for details.

## Disclaimer

This is not an official Just Add Power product. It's a custom control interface designed to work with Just Add Power devices. All trademarks are property of their respective owners.

---

Built with ❤️ to simplify AV control in complex environments.
