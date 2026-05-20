# Miraix HR Activity Tracker - Testing Guide

## 🧪 Testing Overview

This guide provides comprehensive testing procedures for the Miraix HR Activity Tracker to ensure all features work correctly before deployment.

## 📋 Testing Prerequisites

### Required Tools:
- **Node.js 18+** - For development testing
- **Windows 10/11** - Target platform
- **Test Miraix HR Account** - For API testing
- **Internet Connection** - For server communication

### Test Environment Setup:
1. Clean Windows test machine (or VM)
2. Administrator privileges
3. Stable internet connection
4. Test Miraix HR credentials

## 🚀 Phase 1: Development Testing

### 1.1 Build Verification
```bash
# Navigate to project directory
cd C:\xampp\htdocs\hrms\Modules\activity-tracker\node-agent

# Install dependencies
npm install

# Run in development mode
npm start
```

**Expected Results:**
- Electron app launches without errors
- Main window opens with Miraix branding
- No console errors or crashes

### 1.2 UI/UX Testing
**Test Checklist:**
- [ ] Miraix branding visible throughout UI
- [ ] All buttons and forms functional
- [ ] Responsive layout works on different screen sizes
- [ ] System tray icon appears
- [ ] Window minimizes/restores correctly

### 1.3 Configuration Testing
**Test API Configuration:**
1. Open app settings
2. Verify API URL: `https://miraix.in/api`
3. Test connection with valid credentials
4. Test with invalid credentials (should show error)

## 🔧 Phase 2: Functional Testing

### 2.1 Authentication Testing
**Test Scenarios:**
```
Test Case 1: Valid Login
- Input: Valid email/password
- Expected: Successful authentication
- Result: Device registration successful

Test Case 2: Invalid Login
- Input: Invalid email/password  
- Expected: Error message displayed
- Result: Login rejected with clear error

Test Case 3: Empty Credentials
- Input: Empty email/password
- Expected: Validation errors
- Result: Form validation prevents submission
```

### 2.2 Device Registration Testing
**Test Steps:**
1. Login with valid credentials
2. Click "Register Device"
3. Verify device appears in Miraix HR dashboard
4. Test device heartbeat functionality

**Expected Results:**
- Device UUID generated correctly
- Device name displays properly
- Heartbeat sent every 60 seconds
- Device appears in HR dashboard

### 2.3 Activity Tracking Testing
**Test Scenarios:**
```
Test Case 1: Normal Activity
- Action: Type and use mouse normally
- Expected: Activity counters increase
- Result: Keystrokes and mouse events tracked

Test Case 2: Idle Detection
- Action: Stop all input for 60+ seconds
- Expected: Idle time increases
- Result: System correctly detects idle state

Test Case 3: App Switching
- Action: Switch between different applications
- Expected: Active app changes tracked
- Result: Current application logged correctly
```

### 2.4 Screenshot Testing
**Test Steps:**
1. Start activity tracking
2. Wait 5 minutes for automatic screenshot
3. Check screenshot upload to server
4. Verify screenshot quality and format

**Expected Results:**
- Screenshots captured every 5 minutes
- Images uploaded to Miraix servers
- Screenshots appear in HR dashboard
- File size within limits (<5MB)

### 2.5 Data Upload Testing
**Test Scenarios:**
```
Test Case 1: Online Mode
- Action: Normal tracking with internet
- Expected: Real-time data upload
- Result: Data appears immediately in dashboard

Test Case 2: Offline Mode
- Action: Disconnect internet, continue tracking
- Expected: Data queued locally
- Result: Queue count increases, no errors

Test Case 3: Reconnection
- Action: Reconnect internet
- Expected: Queued data uploads
- Result: Queue drains, dashboard updates
```

## 📊 Phase 3: Integration Testing

### 3.1 Miraix HR Dashboard Integration
**Test Steps:**
1. Login to Miraix HR admin panel
2. Navigate to Activity Tracker section
3. Verify device appears in device list
4. Check real-time activity data
5. Review screenshot timeline

**Expected Results:**
- Device registered and visible
- Live activity data updates
- Screenshots display correctly
- Reports generate properly

### 3.2 API Endpoint Testing
**Test API Calls:**
```bash
# Device Registration
POST https://miraix.in/api/activity-tracker/device/register
Headers: Authorization: Bearer {token}
Body: { device_uuid, device_name, os }

# Activity Upload
POST https://miraix.in/api/activity-tracker/activity/store
Headers: Authorization: Bearer {token}
Body: { samples: [...] }

# Screenshot Upload
POST https://miraix.in/api/activity-tracker/screenshot/upload
Headers: Authorization: Bearer {token}
Body: multipart/form-data with image
```

## 🔒 Phase 4: Security Testing

### 4.1 Authentication Security
**Test Cases:**
- [ ] Invalid tokens rejected
- [ ] Expired tokens handled properly
- [ ] Token revocation works
- [ ] HTTPS encryption enforced

### 4.2 Data Privacy Testing
**Test Scenarios:**
```
Test Case 1: Consent Verification
- Action: Start tracking without consent
- Expected: Tracking blocked
- Result: Clear consent required message

Test Case 2: Data Encryption
- Action: Monitor network traffic
- Expected: Encrypted data transmission
- Result: HTTPS/TLS encryption active
```

## 📱 Phase 5: Performance Testing

### 5.1 Resource Usage Testing
**Monitor System Resources:**
- CPU usage during tracking
- Memory consumption
- Disk space for screenshots
- Network bandwidth usage

**Acceptable Limits:**
- CPU: <5% during normal operation
- Memory: <100MB
- Disk: <1GB per month for screenshots
- Network: <10MB per hour

### 5.2 Stress Testing
**Test Scenarios:**
```
Test Case 1: High Activity
- Action: Rapid typing and mouse movement
- Expected: No performance degradation
- Result: Smooth tracking maintained

Test Case 2: Long Running
- Action: Run for 24+ hours continuously
- Expected: No memory leaks
- Result: Stable performance over time
```

## 🐛 Phase 6: Error Handling Testing

### 6.1 Network Error Testing
**Test Scenarios:**
- Internet disconnection during tracking
- Server timeout errors
- Invalid API responses
- Network latency issues

**Expected Behavior:**
- Graceful error handling
- Clear user notifications
- Automatic retry mechanisms
- Data preservation during errors

### 6.2 Application Error Testing
**Test Scenarios:**
- Application crashes
- Insufficient permissions
- Disk space full
- Screenshot capture failures

## 📋 Phase 7: User Acceptance Testing

### 7.1 End-User Testing
**Test User Scenarios:**
```
Scenario 1: First-Time User
- Install application
- Configure settings
- Start tracking
- Verify data appears in dashboard

Scenario 2: Daily User
- Start tracking at work beginning
- Monitor throughout day
- Stop tracking at work end
- Review daily reports

Scenario 3: Mobile User
- Use on laptop with intermittent internet
- Test offline queuing
- Verify data sync when reconnected
```

### 7.2 Administrator Testing
**Admin Test Cases:**
- [ ] User management
- [ ] Device registration approval
- [ ] Report generation
- [ ] Policy configuration
- [ ] Data export functionality

## 🔄 Phase 8: Regression Testing

### 8.1 Version Compatibility
**Test Areas:**
- Backward compatibility with older data
- Database migration testing
- Configuration file compatibility
- API version compatibility

### 8.2 Cross-Platform Testing
**Test Platforms:**
- Windows 10 (different versions)
- Windows 11 (different builds)
- Different hardware configurations
- Various screen resolutions

## 📊 Test Results Documentation

### Test Report Template
```
Test Case: [Test Name]
Date: [Test Date]
Tester: [Tester Name]
Environment: [OS Version, Hardware]
Preconditions: [Setup Required]
Test Steps: [Step-by-step actions]
Expected Results: [What should happen]
Actual Results: [What actually happened]
Status: Pass/Fail/Pending
Issues: [Any problems found]
Screenshots: [Relevant screenshots]
```

### Bug Reporting
**Bug Report Format:**
```
Bug ID: [Unique identifier]
Severity: [Critical/High/Medium/Low]
Priority: [1-5]
Component: [Affected module]
Description: [Detailed bug description]
Steps to Reproduce: [Reproduction steps]
Expected Behavior: [What should happen]
Actual Behavior: [What happens instead]
Environment: [Test environment]
Attachments: [Screenshots, logs]
```

## ✅ Test Completion Checklist

### Before Deployment:
- [ ] All test cases executed
- [ ] Critical bugs resolved
- [ ] Performance benchmarks met
- [ ] Security tests passed
- [ ] User acceptance confirmed
- [ ] Documentation updated
- [ ] Backup procedures tested
- [ ] Rollback plan prepared

### Deployment Readiness:
- [ ] Build signed and verified
- [ ] Installation package tested
- [ ] Upgrade path tested
- [ ] Support documentation ready
- [ ] Training materials prepared
- [ ] Monitoring systems configured

## 🚀 Quick Test Script

For rapid testing, use this checklist:

```bash
# 1. Build Test
npm run build
# Expected: No build errors, installer created

# 2. Install Test
# Run installer on clean machine
# Expected: Clean installation, no errors

# 3. Launch Test
# Launch installed application
# Expected: App starts, Miraix branding visible

# 4. Login Test
# Enter test credentials
# Expected: Successful authentication

# 5. Tracking Test
# Start tracking for 10 minutes
# Expected: Activity data collected

# 6. Dashboard Test
# Check Miraix HR dashboard
# Expected: Real-time data visible

# 7. Screenshot Test
# Wait 5+ minutes
# Expected: Screenshot captured and uploaded
```

## 📞 Support During Testing

### Test Support Contacts:
- **Technical Issues**: development@miraix.in
- **API Support**: api-support@miraix.in
- **User Testing**: qa@miraix.in

### Test Environment Access:
- **Staging Server**: staging.miraix.in
- **Test Accounts**: Available from HR admin
- **API Documentation**: docs.miraix.in/api

---

**Testing Duration**: 2-3 days for comprehensive testing  
**Test Environment**: Isolated Windows test machines  
**Success Criteria**: All critical test cases pass, no security issues  

Complete this testing guide before deploying the Miraix HR Activity Tracker to production users.
