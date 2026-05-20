# Attendance Tracking System - Architecture & Flow Diagrams

## 1. System Architecture

```
┌─────────────────────────────────────────────────────────────────┐
│                     EMPLOYEE BROWSER                             │
│  ┌────────────────────────────────────────────────────────────┐ │
│  │                    Dashboard Page                          │ │
│  │  ┌──────────────────────────────────────────────────────┐ │ │
│  │  │         CLOCK IN Button                              │ │ │
│  │  │              ↓                                        │ │ │
│  │  │  ┌────────────────────────────────────────────────┐ │ │ │
│  │  │  │      Camera Modal Opens                        │ │ │ │
│  │  │  │         ↓                                       │ │ │ │
│  │  │  │  1. Request Permissions:                       │ │ │ │
│  │  │  │     - Camera Access                            │ │ │ │
│  │  │  │     - Location Access                          │ │ │ │
│  │  │  │         ↓                                       │ │ │ │
│  │  │  │  2. Detect Device Type                         │ │ │ │
│  │  │  │     - Parse User Agent                         │ │ │ │
│  │  │  │     - Determine: Desktop/Mobile/Tablet         │ │ │ │
│  │  │  │         ↓                                       │ │ │ │
│  │  │  │  3. Get Geolocation                            │ │ │ │
│  │  │  │     - GPS Coordinates                          │ │ │ │
│  │  │  │     - Reverse Geocoding (OpenStreetMap)        │ │ │ │
│  │  │  │     - Full Address                             │ │ │ │
│  │  │  │         ↓                                       │ │ │ │
│  │  │  │  4. Capture Photo                              │ │ │ │
│  │  │  │     - Camera Video Stream                      │ │ │ │
│  │  │  │     - Canvas Screenshot                        │ │ │ │
│  │  │  │     - Base64 Encoding                          │ │ │ │
│  │  │  │         ↓                                       │ │ │ │
│  │  │  │  5. Submit Data                                │ │ │ │
│  │  │  │     - POST /attendanceemployee/attendance      │ │ │ │
│  │  │  └────────────────────────────────────────────────┘ │ │ │
│  │  └──────────────────────────────────────────────────────┘ │ │
│  └────────────────────────────────────────────────────────────┘ │
└─────────────────────────────────────────────────────────────────┘
                            ↓↑
                         HTTP(S)
                            ↓↑
┌─────────────────────────────────────────────────────────────────┐
│                   LARAVEL SERVER                                │
│  ┌────────────────────────────────────────────────────────────┐ │
│  │  AttendanceEmployeeController::attendance()               │ │
│  │     ↓                                                      │ │
│  │  1. Process Request Data                                  │ │
│  │     - device_type                                         │ │
│  │     - latitude, longitude                                 │ │
│  │     - address                                             │ │
│  │     - photo_base64                                        │ │
│  │     ↓                                                      │ │
│  │  2. Photo Processing                                      │ │
│  │     - Base64 Decode                                       │ │
│  │     - Create uploads/attendance directory                │ │
│  │     - Save as JPEG file                                  │ │
│  │     ↓                                                      │ │
│  │  3. Calculate Timing Metrics                              │ │
│  │     - Late time                                           │ │
│  │     - Early leaving                                       │ │
│  │     - Overtime                                            │ │
│  │     ↓                                                      │ │
│  │  4. Create Attendance Record                              │ │
│  │     ↓                                                      │ │
│  │  ┌────────────────────────────────────────────────────┐ │ │
│  │  │    AttendanceEmployee Model                         │ │ │
│  │  │    (new AttendanceEmployee())                       │ │ │
│  │  │                                                     │ │ │
│  │  │  Fill Attributes:                                  │ │ │
│  │  │  - employee_id                                    │ │ │
│  │  │  - date                                           │ │ │
│  │  │  - status = 'Present'                             │ │ │
│  │  │  - clock_in                                       │ │ │
│  │  │  - clock_out = '00:00:00'                         │ │ │
│  │  │  - device_type    ← NEW                           │ │ │
│  │  │  - latitude        ← NEW                          │ │ │
│  │  │  - longitude       ← NEW                          │ │ │
│  │  │  - address         ← NEW                          │ │ │
│  │  │  - photo           ← NEW                          │ │ │
│  │  │                                                     │ │ │
│  │  │  $model->save()                                    │ │ │
│  │  └────────────────────────────────────────────────────┘ │ │
│  │     ↓                                                      │ │
│  │  5. Database Insert                                       │ │
│  │     ↓                                                      │ │
│  │  6. Redirect & Flash Message                             │ │
│  │     ↓                                                      │ │
│  └────────────────────────────────────────────────────────────┘ │
└─────────────────────────────────────────────────────────────────┘
                            ↓↑
                         SQL Query
                            ↓↑
┌─────────────────────────────────────────────────────────────────┐
│                      MYSQL DATABASE                             │
│  ┌────────────────────────────────────────────────────────────┐ │
│  │  attendance_employees Table                               │ │
│  │  ┌────────────────────────────────────────────────────┐ │ │
│  │  │ id   | employee_id | date      | status  | clock_in │ │ │
│  │  ├─────┼─────────────┼──────────┼─────────┼──────────┤ │ │
│  │  │ 123 | 45          | 2026-02-12| Present | 09:30:45 │ │ │
│  │  │                                                    │ │ │
│  │  │ clock_out | late | overtime | device_type | ... │ │ │
│  │  ├──────────┼──────┼──────────┼─────────────┤─── │ │ │
│  │  │ 00:00:00 | 0:15 | 00:00:00 | Desktop     | ... │ │ │
│  │  │                                                    │ │ │
│  │  │ latitude  | longitude | address    | photo       │ │ │
│  │  ├──────────┼───────────┼────────────┼────────────┤ │ │
│  │  │ 28.6139  | 77.2090   | New Delhi  | uploads/... │ │ │
│  │  └────────────────────────────────────────────────────┘ │ │
│  └────────────────────────────────────────────────────────────┘ │
│  ┌────────────────────────────────────────────────────────────┐ │
│  │  uploads/attendance/ Directory                            │ │
│  │  ├── 1707612345_45.jpg (Captured Photo)                 │ │
│  │  ├── 1707612378_46.jpg                                 │ │
│  │  └── ...                                                │ │
│  └────────────────────────────────────────────────────────────┘ │
└─────────────────────────────────────────────────────────────────┘
```

---

## 2. Data Flow Diagram

```
┌──────────────┐
│   Employee   │
│   Browser    │
└──────────────┘
       │
       │ 1. Click CLOCK IN
       ↓
┌──────────────────────┐
│ Camera Modal Opens   │
└──────────────────────┘
       │
       ├─ 2. Detect Device Type ─────┐
       │                              │
       ├─ 3. Get Geolocation ─────────┼──────── OpenStreetMap API
       │                              │
       └─ 4. Capture Photo ──┐        │
                             ↓        ↓
                      Browser Memory
                     (Video Stream
                      Canvas Data
                      Base64 Photo)
                             │
                             ↓
       │ 5. Submit Form
       │ (POST with all data)
       ↓
┌──────────────────────────────┐
│ AttendanceEmployeeController │
│      .attendance()           │
└──────────────────────────────┘
       │
       ├─ Parse Request Data
       ├─ Decode Photo (Base64)
       ├─ Save Photo to Disk
       ├─ Calculate Metrics
       └─ Create DB Record
              │
              ↓
┌──────────────────────────────┐
│  MySQL Database              │
│ (attendance_employees table) │
└──────────────────────────────┘
       │
       └─ Redirect to Dashboard
              │
              ↓
         Flash Message
       "Clock In Success"
```

---

## 3. Device Detection Decision Tree

```
                    ┌─── User Agent String
                    │
                    v
        ┌─────────────────────────┐
        │ Check User Agent        │
        │ for device keywords     │
        └─────────────────────────┘
                    │
        ┌───────────┼───────────┐
        │           │           │
        v           v           v
    Android?   iPhone?    iPad?
    WebOS?    iPod?      Tablet?
    BlackBerry?           
        │           │           │
        └─────┬─────┴─────┬─────┘
              │           │
        ┌─────v─────┐ ┌──v──────┐
        │ More      │ │ Not Both │
        │ analysis  │ │ Mobile &Tablet
        └──┬────┬───┘ └──────────
           │    │
    Mobile? │    │ Tablet?
    Check   │    │ Check
           v    v
        Mobile Tablet
           OR
        Desktop

Result: String value
├── "Desktop"
├── "Mobile"  
└── "Tablet"
```

---

## 4. Geolocation Process

```
Employee's Browser
        │
        v
┌──────────────────────────┐
│ navigator.geolocation    │
│ .getCurrentPosition()    │
└──────────────────────────┘
        │
        v
Request Permissions
(Browser asks user)
        │
        ├─ User: ALLOW ────┐
        │                  │
        └─ User: DENY ─────┤
                          │
        ┌─────────────────┘
        │
        v
┌──────────────────────────┐
│ Get Position Object      │
│ -latitude (decimal)      │
│ -longitude (decimal)     │
│ -accuracy (meters)       │
│ -timestamp               │
└──────────────────────────┘
        │
        v
┌──────────────────────────┐
│ Reverse Geocoding        │
│ (OpenStreetMap API)      │
│                          │
│ Call:                    │
│ nominatim.openstreetmap  │
│ /reverse?lat=X&lon=Y     │
└──────────────────────────┘
        │
        v
┌──────────────────────────┐
│ Response Contains:       │
│ -display_name (Address)  │
│ -address (parts)         │
│ -importance             │
│ -class, type            │
└──────────────────────────┘
        │
        v
Store in Form
├── latitude
├── longitude
└── address
```

---

## 5. Photo Capture & Storage

```
┌─────────────────────────────────┐
│  HTML5 Video Stream             │
│  (getUserMedia API)             │
│                                 │
│  <video id="camera-video">      │
│  Stream from: Webcam            │
└─────────────────────────────────┘
        │
        v
┌─────────────────────────────────┐
│  HTML5 Canvas Element           │
│  <canvas id="photo-canvas">     │
│                                 │
│  capturePhoto()                 │
│  ├─ Get 2D context             │
│  ├─ drawImage(video)           │
│  └─ toDataURL('image/jpeg')    │
└─────────────────────────────────┘
        │
        v
┌─────────────────────────────────┐
│  Base64 Data URI                │
│  data:image/jpeg;base64,...     │
│                                 │
│  Stored in:                     │
│  input#photo_base64             │
└─────────────────────────────────┘
        │ POST Form Submit
        v
┌─────────────────────────────────┐
│  Laravel Server                 │
│                                 │
│  $photoBase64 = request         │
│  $photoData = explode(',', ...) │
│  $decoded = base64_decode()     │
│  file_put_contents('/path', ...) │
│                                 │
│  Result: uploads/attendance/    │
│  {timestamp}_{employee_id}.jpg  │
└─────────────────────────────────┘
        │
        v
┌─────────────────────────────────┐
│  File System                    │
│  public/uploads/attendance/     │
│                                 │
│  1707612345_45.jpg              │
│  └─ 2KB-500KB                   │
└─────────────────────────────────┘
```

---

## 6. Attendance List Display

```
┌────────────────────────────────────────────────┐
│  Admin/HR Views Attendance List               │
│  /attendanceemployee                          │
└────────────────────────────────────────────────┘
        │
        v
┌────────────────────────────────────────────────┐
│  Database Query                               │
│  SELECT * FROM attendance_employees           │
│  WHERE date = today                           │
└────────────────────────────────────────────────┘
        │
        v
┌────────────────────────────────────────────────┐
│  Blade Template Rendering                     │
│  (attendance/index.blade.php)                 │
└────────────────────────────────────────────────┘
        │
        ├─ Column 1: Employee Name
        │
        ├─ Column 2: Date
        │
        ├─ Column 3: Clock In/Out
        │
        ├─ Column 4: Device Type
        │            ├─ Desktop (Blue Badge)
        │            ├─ Mobile  (Info Badge)
        │            └─ Tablet  (Warning Badge)
        │
        ├─ Column 5: Location
        │            ├─ Google Maps Button
        │            └─ Address Preview (Tooltip)
        │
        └─ Column 6: Photo
                     ├─ Thumbnail Image
                     └─ Click: Open Full Image

```

---

## 7. Database Schema Relationship

```
┌─────────────────────────────────┐
│    employees                    │
├─────────────────────────────────┤
│ id                              │
│ name                            │
│ user_id                         │
│ branch_id                       │
│ department_id                   │
│ ...                             │
└─────────────────────────────────┘
        │
        │ Foreign Key
        │ (One-to-Many)
        │
        v
┌──────────────────────────────────────────┐
│    attendance_employees                  │
├──────────────────────────────────────────┤
│ id (PK)                                  │
│ employee_id (FK) ─────────────────────┐  │
│ date                                  │  │
│ status                                │  │
│ clock_in                              │  │
│ clock_out                             │  │
│ late                                  │  │
│ early_leaving                         │  │
│ overtime                              │  │
│ total_rest                            │  │
│ created_by                            │  │
├ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─┤ │
│ device_type (VARCHAR) ← NEW ←─────────┤  │
│ latitude (VARCHAR) ← NEW              │  │
│ longitude (VARCHAR) ← NEW             │  │
│ address (TEXT) ← NEW                  │  │
│ photo (VARCHAR) ← NEW                 │  │
├──────────────────────────────────────────┤
│ created_at                              │
│ updated_at                              │
└──────────────────────────────────────────┘
        │
        v
┌──────────────────────────────────────────┐
│    File System                           │
│    public/uploads/attendance/            │
├──────────────────────────────────────────┤
│ 1707612345_45.jpg                        │
│ 1707612346_46.jpg                        │
│ 1707612347_47.jpg                        │
│ ...                                      │
└──────────────────────────────────────────┘
```

---

## 8. Request/Response Flow

```
┌ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ┐
│  BROWSER (Employee)                   │
│                                        │
│  GET /dashboard → Display Page         │
└ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ┘
        │
        │ HTTP 200
        │ HTML + CSS + JS
        │
        ↓
┌ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ┐
│  SERVER (Laravel)                     │
│                                        │
│  HomeController::index()               │
│  ├─ Load employee data                 │
│  ├─ Get office hours                   │
│  ├─ Get today's attendance             │
│  └─ Render dashboard.blade.php         │
└ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ┘
        │
        │ Click Clock In
        │
        ↓
┌ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ┐
│  BROWSER (JS Execution)                │
│                                        │
│  openCameraModal()                     │
│  startCamera()                         │
│  getLocation()                         │
│  detectDeviceType()                    │
│  capturePhoto()                        │
│  submitClockIn()                       │
└ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ┘
        │
        │ POST /attendanceemployee/attendance
        │ Content: multipart/form-data
        │
        │ Data:
        │ ├─ device_type=Desktop
        │ ├─ latitude=28.6139
        │ ├─ longitude=77.2090
        │ ├─ address=New Delhi
        │ ├─ photo_base64=data:image/jpeg;...
        │ └─ _token=csrf_token
        │
        ↓
┌ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ┐
│  SERVER (Laravel)                     │
│                                        │
│  AttendanceEmployeeController          │
│  ::attendance()                        │
│                                        │
│  ├─ Validate CSRF token               │
│  ├─ Process device_type               │
│  ├─ Store coordinates                 │
│  ├─ Decode & save photo               │
│  ├─ Calculate late/overtime           │
│  ├─ Create DB record                  │
│  ├─ Redirect to dashboard             │
│  └─ Flash success message             │
└ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ┘
        │
        │ HTTP 302 Redirect
        │ Location: /dashboard
        │
        ↓
┌ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ┐
│  DATABASE (MySQL)                     │
│                                        │
│  INSERT INTO attendance_employees      │
│  (employee_id, date, status,           │
│   clock_in, device_type,               │
│   latitude, longitude,                 │
│   address, photo)                      │
│  VALUES (...)                          │
│                                        │
│  + Save photo to filesystem            │
└ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ┘
        │
        │ Success
        │
        ↓
┌ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ┐
│  BROWSER                               │
│                                        │
│  ├─ Redirect to /dashboard             │
│  ├─ Stop camera                        │
│  ├─ Show success message               │
│  └─ Close modal                        │
└ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ┘
```

---

## 9. Error Handling Flow

```
┌─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ┐
│  User Action                  │
└─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ┘
        │
        v
    ┌────────┐
    │ Error? │
    └───┬────┘
        │
    ┌───┴──────────────────────────┐
    │                              │
    NO                             YES
    │                              │
    v                              v
Proceed          ┌─────────────────────────┐
                 │ Error Type?             │
                 └─────────────────────────┘
                        │
        ┌───────────────┼───────────────┐
        │               │               │
        v               v               v
   Permission       Network       Validation
    Denied          Error         Error
        │               │               │
        v               v               v
  "Allow X         "No Network    "Missing
   permission      Connection"    Required
   in browser                     Field"
   settings"
        
        All Errors:
        ├─ Alert to user
        ├─ Log to console
        ├─ Show retry option
        └─ Continue/Cancel choice
```

---

## 10. Security Architecture

```
┌──────────────────────────────────┐
│  CSRF Protection                 │
│  ├─ Meta CSRF Token in HTML     │
│  ├─ Token validation on POST    │
│  └─ Token refresh on page load  │
└──────────────────────────────────┘
        │
        v
┌──────────────────────────────────┐
│  Authentication Check            │
│  ├─ Session validation          │
│  ├─ User role verification      │
│  └─ Employee permission check   │
└──────────────────────────────────┘
        │
        v
┌──────────────────────────────────┐
│  IP Restriction (Optional)       │
│  ├─ Load IP whitelist            │
│  ├─ Check request IP             │
│  └─ Allow/Deny                   │
└──────────────────────────────────┘
        │
        v
┌──────────────────────────────────┐
│  Data Validation                 │
│  ├─ Sanitize inputs              │
│  ├─ Validate coordinates         │
│  ├─ Validate photo format        │
│  └─ Validate file size           │
└──────────────────────────────────┘
        │
        v
┌──────────────────────────────────┐
│  File Storage Security           │
│  ├─ Outside web root (ideal)     │
│  ├─ Unique filenames             │
│  ├─ Permission restrictions       │
│  └─ Virus scanning (optional)    │
└──────────────────────────────────┘
        │
        v
┌──────────────────────────────────┐
│  Database Security               │
│  ├─ Prepared statements          │
│  ├─ SQL injection prevention     │
│  ├─ Role-based access control    │
│  └─ Data encryption (optional)   │
└──────────────────────────────────┘
```

---

**Diagrams Created:** 10  
**Coverage:** Architecture, Flow, Logic, Security  
**All Diagrams Complete!** ✅
