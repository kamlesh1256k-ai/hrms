# Facial Recognition System - Quick Reference

## 🎯 What It Does

Employees can clock in/out by having their face verified against their uploaded ID/document photo using OpenAI Vision API. The system:
- ✅ Compares real-time clock-in photo with employee's document photo
- ✅ Returns confidence score (0-100%)
- ✅ Creates attendance record only if confidence >= 80%
- ✅ Stores verification photo reference for audit trail

## 📋 Setup Checklist (5 minutes)

```bash
# 1. Add OPENAI_API_KEY to .env (already done ✓)
# Check: grep OPENAI_API_KEY .env

# 2. Run migration
php artisan migrate

# 3. Cache config
php artisan config:cache

# 4. Ensure employee documents uploaded
# (Verify: SELECT * FROM employee_documents;)

# 5. Test with API call (see below)
```

## 🚀 Quick API Test

### Get Sanctum Token
```bash
curl -X POST http://localhost/hrm-software/api/login \
  -H "Content-Type: application/json" \
  -d '{"email":"user@example.com","password":"password"}'
# Copy the "token" value
```

### Test Clock-In with Facial Recognition
```bash
TOKEN="your_token_here"
EMPLOYEE_ID="1"
PHOTO="/path/to/your/photo.jpg"
TIME="2026-02-17 09:30:00"

curl -X POST http://localhost/hrm-software/api/attendance/clock-in-facial \
  -H "Authorization: Bearer $TOKEN" \
  -F "employee_id=$EMPLOYEE_ID" \
  -F "clock_in_photo=@$PHOTO" \
  -F "punch_time=$TIME" \
  -F "type=clock_in"
```

**Expected Response:**
```json
{
  "success": true,
  "message": "Clock-in verified and recorded successfully",
  "confidence": 85.5,
  "attendance_record": {
    "id": 123,
    "facial_verification_status": "passed",
    "facial_verification_confidence": 85.5
  }
}
```

## 📁 Key Files

| File | Purpose | Status |
|------|---------|--------|
| `app/Services/FacialRecognitionService.php` | Core facial recognition logic | ✅ Ready |
| `app/Http/Controllers/BiometricAttendanceController.php` | Clock-in/out endpoints | ✅ Ready |
| `resources/views/biometricattendance/clock-in-facial.blade.php` | UI for camera capture | ✅ Ready |
| `routes/api.php` | API endpoints | ✅ Configured |
| `database/migrations/2026_02_17_000001_*` | Database columns | ✅ Ready |
| `.env` | OpenAI API key | ✅ Configured |

## 🔧 Core Methods

### FacialRecognitionService

```php
// Verify face between two photos
$result = $service->verifyFace($photo1Path, $photo2Path);
// Returns: ['success' => bool, 'confidence' => 0-100, 'is_verified' => bool]

// Auto-fetch employee's document and verify
$result = $service->verifyByEmployeeId($employeeId, $clockInPhotoPath);
// Returns: ['success' => bool, 'confidence' => 0-100, 'is_verified' => bool]

// Get employee's document photos
$photos = $service->getEmployeeDocumentPhotos($employeeId);
// Returns: array of photo paths
```

### BiometricAttendanceController

```php
// Endpoint: POST /api/facial-recognition/verify
public function verifyFacialRecognition(Request $request)
// Input: employee_id, photo1_path, photo2_path
// Output: confidence score + verification result

// Endpoint: POST /api/attendance/clock-in-facial
public function clockInWithFacialRecognition(Request $request)
// Input: employee_id, clock_in_photo (file), punch_time, type (clock_in/clock_out)
// Output: attendance record created OR verification failed
```

## 📊 Database Schema

### attendance_employees table (additions)
```sql
- facial_verification_photo (string) - Path to verified photo
- facial_verification_status (enum: pending/passed/failed) - Verification result
- facial_verification_confidence (decimal 5,2) - Match confidence %
```

### employee_documents table (used for lookup)
```sql
- employee_id (FK to employees)
- document_value (string) - Path to document photo
- Used by FacialRecognitionService to get photos
```

## ⚙️ Configuration

### .env Settings
```env
OPENAI_API_KEY=sk-proj-... # Already set ✓
```

### Confidence Threshold (adjustable)
```php
// In BiometricAttendanceController.php, line ~175
$confidenceThreshold = 80; // Change this to adjust sensitivity
```

- Raise threshold (85+): Stricter verification, fewer false positives
- Lower threshold (75): More lenient, may allow impersonation

## 🧪 Testing Scenarios

### Scenario 1: Successful Verification
```
Employee photo in document: Face.jpg
Clock-in photo: Face.jpg (same person)
Result: confidence ~90% ✅ PASS (created attendance)
```

### Scenario 2: Failed Verification
```
Employee photo in document: PersonA.jpg
Clock-in photo: PersonB.jpg (different person)
Result: confidence ~30% ❌ FAIL (no attendance created)
```

### Scenario 3: Low Confidence
```
Employee photo in document: Face.jpg
Clock-in photo: Face.jpg (same person, poor lighting)
Result: confidence ~65% ⚠️ FAIL (below 80% threshold)
```

## 🐛 Troubleshooting

| Issue | Solution |
|-------|----------|
| "Employee not found" | Ensure employee_documents table has entries for this employee |
| "OPENAI_API_KEY is missing" | Add to .env and run `php artisan config:cache` |
| Low confidence scores | Check lighting, face angle, photo quality |
| "Column not found" | Run `php artisan migrate` and verify migration ran |
| 401 Unauthorized | Verify Sanctum token is valid and not expired |

## 📝 API Response Status Codes

| Code | Meaning |
|------|---------|
| 200 | Verification successful, attendance created |
| 422 | Verification failed - confidence too low |
| 404 | Employee or document photos not found |
| 401 | Authentication failed - invalid/missing token |
| 500 | Server error - check logs/OpenAI API connection |

## 🔐 Security

- **Authentication:** Sanctum token required (middleware: `auth:sanctum`)
- **Authorization:** Employees can only verify their own ID
- **Data:** Photos stored in `storage/app/verifications/` (non-public)
- **Audit:** All attempts logged with timestamp, confidence, result

## 📚 Full Documentation

See [FACIAL_RECOGNITION_TESTING_GUIDE.md](FACIAL_RECOGNITION_TESTING_GUIDE.md) for:
- Complete API reference
- Step-by-step testing workflow
- Frontend integration examples
- Deployment checklist
- Performance optimization
- Troubleshooting guide

## 🎯 Next Steps

1. ✅ Run migration: `php artisan migrate`
2. ✅ Verify API key: `.env` has OPENAI_API_KEY
3. ✅ Test verification: Use quick API test above
4. 📋 Integrate to existing clock-in UI
5. 🚀 Train employees on face capture process
6. 📊 Monitor accuracy and adjust threshold if needed

## 📞 Support

Check logs:
```bash
tail -f storage/logs/laravel.log
```

Test image encoding:
```bash
php artisan tinker
$service = app(App\Services\FacialRecognitionService::class);
// Test methods and diagnose issues
```

---

**Status:** ✅ Fully Implemented & Ready for Testing
**Last Updated:** 2026-02-17
**Version:** 1.0
