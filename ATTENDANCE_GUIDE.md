# 🎯 Attendance Tracking System - Quick Reference Guide

## 📋 Overview
आपके HRM system में attendance tracking के लिए ये नए features add किए गए हैं:

1. **Device Detection** - Desktop/Mobile/Tablet से login का पता चलेगा
2. **Geolocation Tracking** - GPS location और address capture होगा  
3. **Photo Capture** - Clock in करते समय camera से photo click होगी
4. **Attendance List** - सभी details दिखेंगी attendance list में

---

## 🚀 कैसे काम करे

### Employee Dashboard पर Clock In:

**Step 1:** Dashboard पर जाएं
```
URL: http://localhost/hrm-software/dashboard
```

**Step 2:** "CLOCK IN" बटन पर क्लिक करें
```
एक modal खुलेगा "Capture Photo for Attendance"
```

**Step 3:** Photo capture करें
```
1. Camera permission allow करें
2. "Capture Photo" बटन पर क्लिक करें
3. अगर photo सही न हो तो "Retake Photo" बटन से दोबारा ले सकते हैं
```

**Step 4:** "Clock In" बटन पर क्लिक करें
```
System automatically यह capture करेगा:
✓ Device Type (Desktop/Mobile/Tablet)
✓ Location (GPS Latitude, Longitude)
✓ Address (Reverse Geocoding से)
✓ Photo (JPEG format में stored होगी)
```

---

## 📊 Attendance List में Data देखें

**Step 1:** Attendance List खोलें
```
URL: http://localhost/hrm-software/attendanceemployee
```

**Step 2:** नई columns check करें:

| Column | Description |
|--------|-------------|
| **Device Type** | Mobile/Desktop/Tablet badge |
| **Location** | Google Maps link + Address |
| **Photo** | Attendance photo thumbnail |

**Step 3:**
- Location button पर क्लिक करके Google Maps पर location देखें
- Photo पर क्लिक करके पूरी image देखें
- Address hover करके देखें

---

## 🗄️ Database Table Structure

```sql
SELECT * FROM attendance_employees;

Columns:
├── id
├── employee_id
├── date
├── status
├── clock_in
├── clock_out
├── late
├── early_leaving
├── overtime
├── total_rest
├── created_by
├── device_type          ← NEW: Desktop/Mobile/Tablet
├── latitude             ← NEW: GPS Latitude
├── longitude            ← NEW: GPS Longitude
├── address              ← NEW: Location Address
├── photo                ← NEW: Photo file path
└── timestamps (created_at, updated_at)
```

---

## 🔐 Browser Permissions Required

जब employee Clock In करे तो ये permissions allow करने होंगे:

1. **📷 Camera Permission**
   - Photo capture के लिए
   - First time पूछा जाएगा

2. **📍 Location Permission**
   - GPS track करने के लिए
   - First time पूछा जाएगा

---

## 📁 Files Stored Location

Photos यहाँ store होंगी:
```
public/uploads/attendance/
├── 1707612345_1.jpg
├── 1707612346_2.jpg
├── 1707612347_3.jpg
└── ...
```

**Filename Format:** `{unix_timestamp}_{employee_id}.jpg`

---

## ⚙️ Technical Details

### Device Detection Logic:
```
User Agent Parser चलता है:
- /android|iphone|mobile/ → Mobile
- /ipad|tablet/ → Tablet  
- अन्य → Desktop
```

### Location Data:
```
1. Browser Geolocation API से GPS coordinates
2. OpenStreetMap Reverse Geocoding से address
3. Fallback: "Lat: 28.123, Long: 77.456" format
```

### Photo Storage:
```
1. Camera से canvas capture
2. Canvas को JPEG में convert
3. Base64 file भेजी जाती है
4. Server पर decode करके save होता है
```

---

## 🧪 Testing Commands

### Database Check:
```bash
cd c:\xampp5\htdocs\hrm-software

# Latest attendance record देखें
php artisan tinker
>>> App\Models\AttendanceEmployee::latest()->first();
```

### Photo Directory Check:
```bash
# Directory exists check करें
dir public\uploads\attendance
```

---

## ❌ Troubleshooting

### Camera काम नहीं कर रहा है:
```bash
✓ Browser permissions check करें
✓ HTTPS enable करें (localhost पर काम करेगा)
✓ Different browser try करें
```

### Location नहीं आ रहा है:
```bash
✓ Location permission check करें
✓ Internet connection check करें  
✓ Browser console में error देखें (F12)
```

### Photo save नहीं हो रहा है:
```bash
✓ public/uploads/attendance folder permissions check करें
✓ Disk space check करें
✓ laravel.log देखें: storage/logs/laravel.log
```

---

## 📝 SQL Queries

### सभी attendance records देखें:
```sql
SELECT 
    e.name AS employee,
    a.date,
    a.clock_in,
    a.device_type,
    a.latitude,
    a.longitude,
    a.address,
    a.photo
FROM attendance_employees a
LEFT JOIN employees e ON a.employee_id = e.id
ORDER BY a.date DESC, a.id DESC;
```

### Device type से filter करें:
```sql
SELECT * FROM attendance_employees
WHERE device_type = 'Mobile'  -- या 'Desktop' या 'Tablet'
ORDER BY date DESC;
```

### Photos के साथ records:
```sql
SELECT * FROM attendance_employees
WHERE photo IS NOT NULL
ORDER BY date DESC;
```

---

## 📈 Reporting Ideas

अब आप ये reports बना सकते हैं:
1. **Device-wise Attendance** - कौन से device से login हो रहे हैं
2. **Location-wise Attendance** - कहाँ से login हो रहे हैं
3. **Photo Verification** - Photo के साथ attendance verify करना
4. **Geofencing Report** - Office location से दूर login की check

---

## 🔒 Security Tips

1. **Location Data Privacy:** 
   - Only authorized admins को data access दें
   - Location data को encrypted रखें

2. **Photo Storage:**
   - Photo directory को public से अलग रखें
   - Regular backup लें
   - Old photos को periodically delete करें

3. **Permissions:**
   - Employees को केवल अपना data देख सकें
   - Admins/HR को सभी data देख सकें

---

## 📞 Support Features

### Admin Access:
- सभी employees का attendance देख सकते हैं
- Device type filter कर सकते हैं
- Location on map देख सकते हैं
- Photos download कर सकते हैं

### Employee Access:
- केवल अपना attendance देख सकते हैं
- अपनी device type देख सकते हैं
- अपना location देख सकते हैं
- अपनी photo देख सकते हैं

---

## 🎓 API Integration (Future)

अगर integrate करना हो तो:
```
POST /attendanceemployee/attendance

Required Fields:
- device_type: string (Desktop/Mobile/Tablet)
- latitude: float
- longitude: float
- address: string
- photo_base64: string (base64 encoded image)
```

---

## ✅ Checklist

सभी features working:
- ✅ Database migration complete
- ✅ Models updated
- ✅ Controller updated
- ✅ Dashboard UI complete
- ✅ Camera functionality working
- ✅ Device detection working
- ✅ Geolocation tracking working
- ✅ Attendance list updated
- ✅ Photo storage working

---

**Last Updated:** February 12, 2026  
**Status:** ✅ Ready for Production  
**Version:** 1.0
