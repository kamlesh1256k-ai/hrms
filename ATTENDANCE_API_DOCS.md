# Attendance Tracking API Documentation

## API Endpoint: Clock In/Clock Out

### URL
```
POST /attendanceemployee/attendance
```

### Authentication
```
Required: User must be logged in as Employee
Session based authentication
```

### Request Parameters

#### Form Data (multipart/form-data)

```javascript
{
    "device_type": "string",              // Required: Desktop, Mobile, or Tablet
    "latitude": "string",                 // Required: GPS latitude coordinate
    "longitude": "string",                // Required: GPS longitude coordinate
    "address": "string",                  // Required: Full address or coordinates
    "photo_base64": "string",             // Optional: Base64 encoded photo
    "photo": "file"                       // Optional: Direct file upload
}
```

### Request Headers
```
Content-Type: application/x-www-form-urlencoded
or
Content-Type: multipart/form-data
```

### Response

#### Success (302 Redirect)
```
HTTP 302 Found
Location: /dashboard

Session Message:
{
    "success": "Employee Successfully Clock In."
}
```

#### Error Cases

**Error 1: IP Restriction Enabled (if configured)**
```
HTTP 302
Message: "This IP is not allowed to clock in & clock out."
```

**Error 2: Permission Denied**
```
HTTP 302
Message: "Permission denied."
```

---

## Implementation Examples

### Example 1: JavaScript (Fetch API)

```javascript
// Capture device type
const deviceType = detectDeviceType();

// Get location
const location = await getLocation();

// Capture photo from camera
const photoBase64 = getPhotoFromCamera();

// Prepare form data
const formData = new FormData();
formData.append('device_type', deviceType);
formData.append('latitude', location.latitude);
formData.append('longitude', location.longitude);
formData.append('address', location.address);
formData.append('photo_base64', photoBase64);

// Send request
fetch('/attendanceemployee/attendance', {
    method: 'POST',
    body: formData,
    headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
    }
})
.then(response => {
    if (response.redirected) {
        window.location.href = response.url;
    }
})
.catch(error => console.error('Error:', error));
```

### Example 2: jQuery

```javascript
$.ajax({
    url: '/attendanceemployee/attendance',
    type: 'POST',
    data: {
        device_type: 'Desktop',
        latitude: '28.123456',
        longitude: '77.123456',
        address: 'Mumbai, Maharashtra, India',
        photo_base64: base64PhotoString,
        _token: $('meta[name="csrf-token"]').attr('content')
    },
    success: function(response) {
        // Handle redirect
        window.location.href = '/dashboard';
    },
    error: function(error) {
        console.error('Error:', error);
    }
});
```

### Example 3: cURL

```bash
curl -X POST http://localhost/hrm-software/attendanceemployee/attendance \
  -H "Content-Type: multipart/form-data" \
  -F "device_type=Mobile" \
  -F "latitude=28.123456" \
  -F "longitude=77.123456" \
  -F "address=Mumbai, Maharashtra" \
  -F "photo=@/path/to/photo.jpg" \
  -F "_token=csrf_token_here" \
  --cookie "PHPSESSID=session_id_here"
```

---

## Database Schema

### attendance_employees Table

```sql
CREATE TABLE attendance_employees (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT,
    date DATE,
    status VARCHAR(255),
    clock_in TIME,
    clock_out TIME,
    late TIME,
    early_leaving TIME,
    overtime TIME,
    total_rest TIME,
    created_by INT,
    device_type VARCHAR(255) NULLABLE,
    latitude VARCHAR(255) NULLABLE,
    longitude VARCHAR(255) NULLABLE,
    address TEXT NULLABLE,
    photo VARCHAR(255) NULLABLE,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    
    FOREIGN KEY (employee_id) REFERENCES employees(id),
    INDEX idx_employee_date (employee_id, date),
    INDEX idx_device_type (device_type),
    INDEX idx_created_at (created_at)
);
```

---

## Response Format

### JSON Response (for AJAX requests)

The system redirects by default. To get JSON response, modify controller:

```javascript
// If you want JSON response instead of redirect:
return response()->json([
    'success' => true,
    'message' => 'Employee Successfully Clock In.',
    'attendance' => [
        'id' => $employeeAttendance->id,
        'employee_id' => $employeeId,
        'date' => $date,
        'clock_in' => $time,
        'device_type' => $deviceType,
        'latitude' => $latitude,
        'longitude' => $longitude,
        'address' => $address,
        'photo' => $photo
    ]
]);
```

---

## Device Type Detection Algorithm

```javascript
function detectDeviceType() {
    const userAgent = navigator.userAgent.toLowerCase();
    const isMobile = /android|webos|iphone|ipad|ipod|blackberry|iemobile|opera mini/i.test(userAgent);
    const isTablet = /(ipad|tablet|(android(?!.*mobile))|(windows(?!.*phone)(.*touch))|kindle|playbook|silk|(puffin(?!.*(IP|AP|WP))))/.test(userAgent);
    
    if (isMobile && !isTablet) {
        return 'Mobile';
    } else if (isTablet) {
        return 'Tablet';
    } else {
        return 'Desktop';
    }
}
```

---

## Geolocation Implementation

```javascript
function getLocation() {
    return new Promise((resolve, reject) => {
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(
                async (position) => {
                    const lat = position.coords.latitude;
                    const lon = position.coords.longitude;
                    
                    // Reverse geocoding using OpenStreetMap
                    const response = await fetch(
                        `https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lon}`
                    );
                    const data = await response.json();
                    
                    resolve({
                        latitude: lat,
                        longitude: lon,
                        address: data.display_name || `Lat: ${lat}, Long: ${lon}`
                    });
                },
                (error) => {
                    console.error('Geolocation error:', error);
                    resolve({
                        latitude: null,
                        longitude: null,
                        address: 'Location not available'
                    });
                }
            );
        } else {
            resolve({
                latitude: null,
                longitude: null,
                address: 'Geolocation not supported'
            });
        }
    });
}
```

---

## Photo Handling

### Client-side (JavaScript)

```javascript
// Capture from Camera
function capturePhotoFromCamera() {
    const video = document.getElementById('camera-video');
    const canvas = document.getElementById('photo-canvas');
    const context = canvas.getContext('2d');
    
    canvas.width = video.videoWidth;
    canvas.height = video.videoHeight;
    context.drawImage(video, 0, 0, canvas.width, canvas.height);
    
    return canvas.toDataURL('image/jpeg', 0.8);
}

// Or upload file
function uploadPhotoFile(file) {
    const reader = new FileReader();
    reader.onload = function(e) {
        document.getElementById('photo_base64').value = e.target.result;
    };
    reader.readAsDataURL(file);
}
```

### Server-side (PHP/Laravel)

```php
// Handle base64 photo
if ($request->has('photo_base64')) {
    $photoBase64 = $request->input('photo_base64');
    $photoData = explode(',', $photoBase64);
    
    if (count($photoData) > 1) {
        $photoDecoded = base64_decode($photoData[1]);
        $photoName = time() . '_' . $employeeId . '.jpg';
        $photoPath = public_path('uploads/attendance/' . $photoName);
        
        if (!file_exists(public_path('uploads/attendance'))) {
            mkdir(public_path('uploads/attendance'), 0777, true);
        }
        
        file_put_contents($photoPath, $photoDecoded);
        $photo = 'uploads/attendance/' . $photoName;
    }
}

// Handle file upload
if ($request->hasFile('photo')) {
    $photoFile = $request->file('photo');
    $photoName = time() . '_' . $employeeId . '.' . $photoFile->getClientOriginalExtension();
    $photoFile->move(public_path('uploads/attendance'), $photoName);
    $photo = 'uploads/attendance/' . $photoName;
}
```

---

## Error Handling

### Common Errors

```
1. Browser Permission Denied
   - User denied camera/location access
   - Solution: Advise user to allow permissions

2. Geolocation Timeout
   - GPS signal not found
   - Solution: Show "Getting location..." and retry

3. CSRF Token Missing
   - _token not sent in request
   - Solution: Include Meta CSRF token in headers

4. IP Restriction Failed
   - User IP not whitelisted
   - Solution: Check IP restriction settings
```

---

## Rate Limiting & Security

### Current Implementation
- Only authenticated employees can access
- One clock-in per employee per day (if not clocked out)
- IP restriction (if enabled in settings)
- CSRF protection enabled

### Recommendations
```
1. Add rate limiting (max 10 requests/minute per user)
2. Validate photo quality
3. Add face detection/verification
4. Log all clock-in attempts
5. Monitor suspicious activities
```

---

## Integration Checklist

- ✅ Enable camera in browser
- ✅ Enable location services  
- ✅ Check CSRF token included
- ✅ Handle base64 conversion correctly
- ✅ Ensure public/uploads/attendance is writable
- ✅ Test all device types (Mobile, Desktop, Tablet)
- ✅ Test geolocation (with/without GPS)
- ✅ Test photo upload (file and base64)
- ✅ Verify data stored in database
- ✅ Test attendance list display

---

## Troubleshooting API Calls

### Enable Debug Mode

```bash
# Check Laravel logs
tail -f storage/logs/laravel.log

# Check browser console
Press F12 → Console tab

# Check network requests
Press F12 → Network tab → Follow your request
```

### Test Endpoint

```bash
# Simple curl test
curl http://localhost/hrm-software/attendanceemployee/attendance \
  -X POST \
  -d "device_type=Desktop&latitude=28.1&longitude=77.1&address=Test&_token=YOUR_TOKEN"
```

---

## Performance Metrics

### Expected Response Time
- Device detection: < 1ms
- Geolocation API: 2-5 seconds
- Photo upload (base64): < 2 seconds
- Database save: < 100ms
- **Total**: 2-7 seconds

### Optimization Tips
1. Compress photos before upload
2. Cache geolocation results per session
3. Add loading indicators
4. Implement progressive enhancement

---

**API Version:** 1.0  
**Last Updated:** February 12, 2026  
**Status:** ✅ Production Ready
