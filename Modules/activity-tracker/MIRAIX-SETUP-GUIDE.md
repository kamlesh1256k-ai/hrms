# Miraix HR Activity Tracker - Setup Guide

## 🚀 Overview

The Miraix HR Activity Tracker has been updated and configured to work with the Miraix.in HR platform. This modern employee monitoring solution provides real-time activity tracking, screenshot capture, and productivity analytics.

## 📦 What's Been Updated

### ✅ Branding & Configuration
- **Product Name**: Changed from "HRMS" to "Miraix Activity Tracker"
- **API URL**: Pre-configured to `https://miraix.in/api`
- **Version**: Updated to v2.0.0
- **UI**: Modern Miraix branding throughout the application

### ✅ Enhanced Features
- Real-time activity monitoring
- Screenshot capture every 5 minutes
- Application usage tracking
- Idle time detection
- GDPR-compliant privacy controls
- Offline data queuing
- System tray integration

## 🛠️ Installation & Build Process

### Prerequisites
1. **Node.js 18+** - Download from [nodejs.org](https://nodejs.org/)
2. **Windows 10/11** - Required for Electron app
3. **Administrator permissions** - For installation

### Step 1: Build the Application

1. **Navigate to the activity tracker folder:**
   ```
   C:\xampp\htdocs\hrms\Modules\activity-tracker\
   ```

2. **Run the build script:**
   - Double-click `build_miraix_tracker.bat`
   - OR run from Command Prompt as Administrator

3. **The script will:**
   - Check Node.js installation
   - Clean previous builds
   - Install dependencies
   - Build the executable
   - Create installer package

### Step 2: Install the Application

1. **Navigate to the output folder:**
   ```
   C:\xampp\htdocs\hrms\Modules\activity-tracker\node-agent\dist-miraix-2.0.0\
   ```

2. **Run the installer:**
   - Find: `Miraix Activity Tracker Setup 2.0.0.exe`
   - Right-click → "Run as administrator"
   - Follow the installation wizard

3. **Installation options:**
   - Choose installation directory (default: Program Files)
   - Create desktop shortcut (recommended)
   - Create Start Menu shortcut (recommended)
   - Auto-start with Windows (optional)

## ⚙️ Configuration

### First-Time Setup

1. **Launch Miraix Activity Tracker** from desktop shortcut

2. **Configure connection settings:**
   - **API URL**: `https://miraix.in/api` (pre-filled)
   - **Email**: Your Miraix HR employee email
   - **Password**: Your Miraix HR password
   - **Device Name**: Your computer name (auto-filled)

3. **Register device:**
   - Click "Register Device"
   - Wait for confirmation

4. **Accept consent:**
   - Read the monitoring consent information
   - Click "I understand and consent"

5. **Start tracking:**
   - Click "▶ Start Tracking"
   - Activity will begin monitoring

### Advanced Settings

Access via the tray icon right-click menu:

- **Screenshot Interval**: 5 minutes (default)
- **Activity Interval**: 30 seconds (default)
- **Heartbeat Interval**: 1 minute (default)
- **Auto-start with Windows**: Enable/disable

## 📊 Features & Functionality

### 🖥️ Activity Monitoring
- **Active Application Tracking**: Monitors current app and window title
- **Keyboard/Mouse Events**: Counts keystrokes and mouse movements
- **Idle Time Detection**: Identifies periods of inactivity
- **Application Usage**: Tracks time spent in each application

### 📸 Screenshot Capture
- **Automatic Screenshots**: Every 5 minutes during active periods
- **Privacy Protection**: No screenshots during idle time
- **Secure Upload**: Encrypted transmission to Miraix servers
- **Local Storage**: Temporary offline queuing available

### 📈 Analytics & Reporting
- **Real-time Dashboard**: Live activity monitoring
- **Productivity Reports**: Daily/weekly/monthly analytics
- **App Usage Statistics**: Most used applications
- **Time Tracking**: Active vs. idle time analysis

### 🔒 Security & Privacy
- **Consent-Based**: Explicit employee consent required
- **Data Encryption**: Secure data transmission
- **Access Control**: Role-based permissions
- **GDPR Compliant**: Privacy regulations followed

## 🔧 Troubleshooting

### Common Issues

#### ❌ "Registration failed: 401"
**Solution:** 
- Check email/password credentials
- Verify Miraix HR account is active
- Contact HR administrator for account access

#### ❌ "Cannot start: consent not accepted"
**Solution:**
- Click the consent banner in the app
- Read and accept the monitoring terms

#### ❌ "Screenshots not uploading"
**Solution:**
- Check internet connection
- Verify API URL is correct
- Restart the application

#### ❌ "Build failed during installation"
**Solution:**
- Ensure Node.js is properly installed
- Run Command Prompt as Administrator
- Check available disk space (>500MB)

### Debug Mode

Enable debug logging:
1. Right-click tray icon
2. Select "Debug Mode"
3. Check log files in: `%APPDATA%\hrms-activity-tracker-agent\logs\`

## 📱 Mobile & Web Access

### Miraix HR Dashboard
Access your activity data online:
1. Login to [Miraix HR](https://miraix.in)
2. Navigate to "Activity Tracker"
3. View real-time monitoring and reports

### Features Available:
- Live activity monitoring
- Screenshot timeline
- Productivity analytics
- Team activity overview
- Export reports (CSV/PDF)

## 🔄 Updates & Maintenance

### Automatic Updates
- The app checks for updates weekly
- Updates are downloaded automatically
- Manual restart required for installation

### Manual Updates
1. Download latest version from HR administrator
2. Run installer over existing installation
3. Settings and configuration are preserved

## 📞 Support

### Technical Support
- **Email**: support@miraix.in
- **Phone**: [Contact HR department]
- **Documentation**: Available in Miraix HR portal

### HR Administrator Support
- User account management
- Device registration assistance
- Policy configuration
- Report customization

## 📋 Checklist

### Pre-Installation Checklist
- [ ] Node.js 18+ installed
- [ ] Windows 10/11 system
- [ ] Administrator permissions
- [ ] Miraix HR account credentials
- [ ] Stable internet connection

### Post-Installation Checklist
- [ ] Application launches successfully
- [ ] Device registered with Miraix
- [ ] Consent accepted
- [ ] Tracking started without errors
- [ ] Screenshots uploading correctly
- [ ] Activity data visible in dashboard

## 🎯 Best Practices

### For Employees
- Start tracking at the beginning of workday
- Stop tracking when finished with work
- Keep the app running during breaks (idle detection handles this)
- Report any technical issues immediately

### For HR Administrators
- Regularly review activity data
- Set clear expectations with employees
- Use data for productivity improvement, not punishment
- Ensure privacy policies are followed

---

**Version**: 2.0.0  
**Platform**: Windows 10/11  
**API**: Miraix.in HR Platform  
**Last Updated**: May 2026

For additional support, please contact your HR department or Miraix support team.
