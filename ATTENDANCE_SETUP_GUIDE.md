# 🚀 Attendance Tracking System - Complete Setup Guide

## 📋 Index
1. [Installation](#installation)
2. [Configuration](#configuration)
3. [Features Overview](#features-overview)
4. [Usage Guide](#usage-guide)
5. [Testing](#testing)
6. [Troubleshooting](#troubleshooting)
7. [File Structure](#file-structure)

---

## Installation

### Prerequisites
```
✓ PHP 8.0 or higher
✓ Laravel 11
✓ MySQL 5.7+
✓ XAMPP with PHP & MySQL
✓ Composer
✓ Modern Web Browser (Chrome, Firefox, Safari, Edge)
```

### Step 1: Apply Migration

The migration has already been applied. Verify:

```bash
cd c:\xampp5\htdocs\hrm-software

# Check migration status
php artisan migrate:status
```

Expected output shows: ✓ All migrations completed

### Step 2: Verify Directory Permissions

```bash
# Ensure uploads directory exists and is writable
mkdir -p public/uploads/attendance
chmod -R 777 public/uploads/attendance

# Windows (if needed)
icacls "public/uploads/attendance" /grant:r "%username%":F /T
```

### Step 3: Verify Model & Controller Updates

Files automatically updated:
- ✅ `app/Models/AttendanceEmployee.php`
- ✅ `app/Http/Controllers/AttendanceEmployeeController.php`
- ✅ `resources/views/dashboard/dashboard.blade.php`
- ✅ `resources/views/attendance/index.blade.php`

---

## Configuration

### Browser Permissions

#### Chrome/Edge:
1. Settings → Privacy and security → Site settings
2. Camera → Manage permissions → Allow http://localhost
3. Location → Manage permissions → Allow http://localhost

#### Firefox:
1. Preferences → Privacy → Permissions
2. Camera → Allow localhost
3. Location → Allow localhost

#### Safari:
System Preferences → Security & Privacy → Camera/Location

### Server Configuration

#### HTTPS (Recommended for Production)

```bash
# Generate self-signed certificate
openssl req -x509 -newkey rsa:4096 -keyout private.key -out certificate.crt -days 365 -nodes

# Update Apache configuration
# Uncomment SSL in httpd.conf and update paths
```

---

## Features Overview

### 1. Device Type Detection ✅

Automatically detects:
```
├── Desktop - Windows/Mac/Linux browser
├── Mobile - iPhone/Android phone
└── Tablet - iPad/Android tablet
```

### 2. Geolocation Tracking ✅

Captures:
```
├── GPS Latitude
├── GPS Longitude  
├── Address (via Reverse Geocoding)
└── Timestamp
```

### 3. Photo Capture ✅

Features:
```
├── Real-time camera preview
├── Photo capture via HTML5 Canvas
├── Retake functionality
├── Base64 encoding
└── Server-side storage
```

### 4. Attendance Record ✅

Stores:
```
├── Employee ID
├── Date & Time (Clock In/Out)
├── Late/Overtime calculation
├── Device Type
├── Location (with map link)
├── Photo
└── Additional metadata
```

---

## Usage Guide

### For Employees

#### Clock In:

```
1. Login: http://localhost/hrm-software/login
2. Go to: Dashboard
3. Click: "CLOCK IN" button
4. Allow: Camera permission
5. Allow: Location permission
6. Capture: Photo from camera
7. Review: Confirm photo is correct
8. Click: "Clock In" button
9. Success: Message displayed
```

#### Clock Out:

```
1. Dashboard
2. Click: "CLOCK OUT" button
3. Success: Clock out recorded
```

#### View Records:

```
1. Dashboard
2. Scroll down: "Today's Attendance"
3. Or: Go to Attendance → Employee attendance
```

---

### For Admins/HR

#### View All Attendance:

```
1. Login: http://localhost/hrm-software/login (as Admin/HR)
2. Sidebar: Attendance → Attendance
3. Filter: By Date, Branch, Department
4. View: All employee attendance records
```

#### Verify Devices:

```
1. Attendance List
2. Column: "Device Type"
3. Filter: Desktop/Mobile/Tablet
4. Analysis: Identify login patterns
```

#### Check Locations:

```
1. Attendance List
2. Column: "Location"
3. Click: "View Location" button
4. Opens: Google Maps with coordinates
5. Check: Address and coordinates
```

#### View Photos:

```
1. Attendance List
2. Column: "Photo"
3. Click: Photo thumbnail
4. View: Full-size image in browser
5. Download: If needed for records
```

---

## Testing

### Test Checklist

#### ✅ Database Tests

```bash
# Connect to database
mysql -u root -p hrm_software

# Check table structure
DESC attendance_employees;

# Check new columns exist
SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_NAME = 'attendance_employees' 
AND COLUMN_NAME IN ('device_type', 'latitude', 'longitude', 'address', 'photo');

# Check sample records
SELECT * FROM attendance_employees LIMIT 5;
```

#### ✅ Functional Tests

**Test 1: Desktop Clock In**
```
1. Open: Chrome on Windows
2. Clock in with camera
3. Verify: Device Type = "Desktop"
4. Verify: Photo saved
5. Verify: Location captured
```

**Test 2: Mobile Clock In**
```
1. Open: Mobile browser (Android/iPhone)
2. Navigate to: http://localhost:8000
3. Clock in with camera
4. Verify: Device Type = "Mobile"
5. Verify: GPS location captured
```

**Test 3: Photo Upload**
```
1. Clock in and capture photo
2. Check: public/uploads/attendance/
3. Verify: File exists with correct naming
4. Open: Photo in browser, should display correctly
```

**Test 4: Location Data**
```
1. Clock in with location enabled
2. Check: Attendance list
3. Click: "View Location" button
4. Verify: Opens Google Maps
5. Verify: Address shown correctly
```

**Test 5: Attendance List**
```
1. Go to: Attendance list
2. Verify: Device Type column shows badges
3. Verify: Location column shows Google Maps links
4. Verify: Photo column shows thumbnails
5. Test: Hover effects and tooltips
```

---

## Troubleshooting

### Issue 1: Camera Not Working

**Symptoms:** 
```
"Unable to access camera" error
Camera modal opens but video not showing
```

**Solutions:**
```
1. Check browser permissions:
   - Chrome: Settings → Privacy → Permissions → Camera
   
2. Check HTTPS (if not localhost):
   - Camera requires HTTPS in production
   
3. Try different browser:
   - Chrome, Firefox, Safari all support HTML5 Camera API
   
4. Check browser console (F12):
   - Look for MediaDevices errors
   - Check if getUserMedia is supported
   
5. Verify camera hardware:
   - Webcam connected and working
   - Test in other applications
```

### Issue 2: Location Not Detected

**Symptoms:**
```
"Location not available" message
Latitude/Longitude are null
Address not filled
```

**Solutions:**
```
1. Check browser permissions:
   - Allow location access for the site
   
2. Check internet connection:
   - Reverse geocoding requires internet
   
3. Check browser console:
   - Look for Geolocation errors
   
4. Try GPS on mobile:
   - Enable Location Services on phone
   - Allow browser to access location
   
5. Wait for GPS signal:
   - GPS takes 2-5 seconds to acquire
   - Show progress message while waiting
   
6. Fallback to network location:
   - If GPS not available
   - Uses IP-based location (less accurate)
```

### Issue 3: Photo Not Saving

**Symptoms:**
```
"Error uploading photo" message
Photo stored as null in database
```

**Solutions:**
```
1. Check folder permissions:
   cd public/uploads/attendance
   chmod -R 777 .
   
2. Check disk space:
   - Ensure sufficient space for file storage
   
3. Check Base64 encoding:
   - Verify photo_base64 value in form
   - Check browser console for errors
   
4. Check Laravel logs:
   - tail -f storage/logs/laravel.log
   - Look for file write errors
   
5. Verify .env settings:
   - APP_DEBUG=true for development
   - Check upload driver settings
```

### Issue 4: CSRF Token Issues

**Symptoms:**
```
"TokenMismatchException" error
Form not submitting
```

**Solutions:**
```
1. Verify CSRF token in HTML:
   <meta name="csrf-token" content="...">
   
2. Include token in form:
   {{ csrf_field() }}
   
3. Include in AJAX headers:
   headers: {
       'X-CSRF-TOKEN': token
   }
   
4. Clear cookies:
   - Delete site cookies and retry
```

### Issue 5: Database Column Errors

**Symptoms:**
```
"Unknown column 'device_type'" error
```

**Solutions:**
```
1. Verify migration ran:
   php artisan migrate:status
   
2. Re-run migration if needed:
   php artisan migrate
   
3. Refresh migration:
   php artisan migrate:refresh
   (Warning: clears all data)
   
4. Check table structure:
   DESC attendance_employees;
   
5. Check Laravel cache:
   php artisan cache:clear
   php artisan config:clear
```

---

## File Structure

### Created/Modified Files

```
hrm-software/
├── database/
│   └── migrations/
│       └── 2026_02_12_065702_add_tracking_columns_to_attendance_employees_table.php ← NEW
│
├── app/
│   ├── Models/
│   │   └── AttendanceEmployee.php (MODIFIED)
│   │
│   └── Http/Controllers/
│       └── AttendanceEmployeeController.php (MODIFIED)
│
├── resources/
│   └── views/
│       ├── dashboard/
│       │   └── dashboard.blade.php (MODIFIED)
│       │       ├── Added camera modal
│       │       ├── Added JavaScript functions
│       │       └── Added form hidden fields
│       │
│       └── attendance/
│           └── index.blade.php (MODIFIED)
│               ├── Added device_type column
│               ├── Added location column
│               └── Added photo column
│
├── public/
│   └── uploads/
│       └── attendance/ ← NEW (photo storage)
│
└── Documentation/
    ├── ATTENDANCE_TRACKING_IMPLEMENTATION.md ← NEW
    ├── ATTENDANCE_GUIDE.md ← NEW
    └── ATTENDANCE_API_DOCS.md ← NEW
```

### Modified Code Sections

**AttendanceEmployeeController.php (attendance method):**
- Added device_type capture
- Added latitude/longitude capture
- Added address capture
- Added photo handling (file and base64)
- Added directory creation

**dashboard.blade.php:**
- Changed Clock In button to open modal
- Added camera modal HTML
- Added device detection JavaScript
- Added geolocation JavaScript
- Added camera streaming JavaScript
- Added photo capture JavaScript

**attendance/index.blade.php:**
- Added Device Type column
- Added Location column  
- Added Photo column
- Added badge styling
- Added Google Maps links
- Added tooltips

---

## Database Schema

### attendance_employees Table

```sql
-- Original columns
├── id BIGINT PRIMARY KEY AUTO_INCREMENT
├── employee_id INT
├── date DATE
├── status VARCHAR(255)
├── clock_in TIME
├── clock_out TIME
├── late TIME
├── early_leaving TIME
├── overtime TIME
├── total_rest TIME
├── created_by INT

-- NEW columns (added by migration)
├── device_type VARCHAR(255) NULLABLE
├── latitude VARCHAR(255) NULLABLE
├── longitude VARCHAR(255) NULLABLE
├── address TEXT NULLABLE
├── photo VARCHAR(255) NULLABLE

└── Timestamps
    ├── created_at TIMESTAMP
    └── updated_at TIMESTAMP
```

---

## API Response Examples

### Successful Clock In

```javascript
// Form submitted successfully
// Response: 302 Redirect to /dashboard

// Session flash message:
{
    'success': 'Employee Successfully Clock In.'
}

// Database record created:
{
    id: 123,
    employee_id: 45,
    date: "2026-02-12",
    clock_in: "09:30:45",
    clock_out: "00:00:00",
    device_type: "Desktop",
    latitude: "28.6139",
    longitude: "77.2090",
    address: "New Delhi, Delhi, India",
    photo: "uploads/attendance/1707612345_45.jpg",
    created_at: "2026-02-12T09:30:45.000Z"
}
```

---

## Performance Metrics

### Expected Timings

```
Device Detection:     < 1ms
Geolocation API:      2-5 seconds
Photo Capture:        < 1 second
Photo Upload:         < 2 seconds
Database Save:        < 100ms
Page Redirect:        < 500ms

TOTAL:               2-7 seconds (typical)
```

---

## Best Practices

### 1. Regular Backups
```bash
# Backup attendance photos
tar -czf attendance_backup.tar.gz public/uploads/attendance/

# Backup database
mysqldump -u root -p hrm_software > hrm_backup.sql
```

### 2. Monitor Disk Space
```bash
# Check uploads folder size
du -sh public/uploads/attendance/

# Archive old photos (recommended monthly)
```

### 3. Security Checklist
- [ ] Enable HTTPS in production
- [ ] Restrict file access to authenticated users
- [ ] Regular photo cleanup (old files)
- [ ] Monitor suspicious activities
- [ ] Backup data regularly
- [ ] Use strong passwords

---

## Support & Documentation

### Internal Docs
- `ATTENDANCE_TRACKING_IMPLEMENTATION.md` - Technical details
- `ATTENDANCE_GUIDE.md` - User guide
- `ATTENDANCE_API_DOCS.md` - API reference

### External Resources
- Laravel Documentation: https://laravel.com/docs
- Mozilla MDN Web APIs: https://developer.mozilla.org/
- OpenStreetMap API: https://wiki.openstreetmap.org/wiki/Nominatim

---

## Maintenance Tasks

### Daily
- [ ] Monitor application logs
- [ ] Check server disk space
- [ ] Verify attendance data accuracy

### Weekly
- [ ] Backup database and photos
- [ ] Test camera functionality
- [ ] Check location tracking accuracy

### Monthly
- [ ] Archive old photos
- [ ] Analyze attendance patterns
- [ ] Review security logs
- [ ] Update browser compatibility

---

## version History

### v1.0 (February 12, 2026)
- ✅ Initial release
- ✅ Device detection
- ✅ Geolocation tracking
- ✅ Photo capture
- ✅ Attendance list display

### Future Versions
- Face recognition verification
- Geofencing support
- Offline mode
- Mobile app integration
- Advanced analytics

---

**Setup Complete!** ✅

Your attendance tracking system is ready to use.
All employees can now clock in with photo verification, device detection, and location tracking.

For any issues, refer to the Troubleshooting section above.

**Questions?** Check the documentation files in the project root directory.
