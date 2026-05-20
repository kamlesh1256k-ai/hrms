# Facial Recognition Clock-In/Out - Testing & Deployment Guide

## Overview
This guide provides step-by-step instructions to test and deploy the facial recognition system for employee clock-in/out functionality using OpenAI Vision API.

## Prerequisites

### 1. Server Setup
- PHP 8.0+
- Laravel 9+
- MySQL/MariaDB
- GD Library or Imagick for image processing
- cURL for API calls

### 2. Database
Run migrations to create required columns:

```bash
php artisan migrate
```

This creates:
- `facial_verification_photo` (string) - Path to verification photo
- `facial_verification_status` (enum) - ['pending', 'passed', 'failed']
- `facial_verification_confidence` (decimal) - Match confidence percentage

### 3. Environment Configuration
OpenAI API key should be configured in `.env`:

```env
OPENAI_API_KEY=sk-proj-...your-key-here...
```

Run config cache after updating:
```bash
php artisan config:cache
```

## File Structure

```
app/
├── Services/
│   └── FacialRecognitionService.php
├── Http/
│   └── Controllers/
│       └── BiometricAttendanceController.php
│
resources/
├── views/
│   └── biometricattendance/
│       └── clock-in-facial.blade.php
│
database/
└── migrations/
    └── 2026_02_17_000001_add_facial_verification_to_attendance_employees_table.php

routes/
├── api.php (endpoints)
└── web.php (optional UI route)
```

## API Reference

### 1. Verify Facial Recognition (Generic)

**Endpoint:** `POST /api/facial-recognition/verify`

**Authentication:** Sanctum Bearer Token

**Request Body:**
```json
{
  "employee_id": 1,
  "photo1_path": "/path/to/document/photo.jpg",
  "photo2_path": "/path/to/clockin/photo.jpg"
}
```

**Response:**
```json
{
  "success": true,
  "confidence": 92.5,
  "message": "Facial recognition matched successfully",
  "is_verified": true
}
```

### 2. Clock-In/Out with Facial Recognition

**Endpoint:** `POST /api/attendance/clock-in-facial`

**Authentication:** Sanctum Bearer Token

**Request Parameters:**
- `employee_id` (integer, required) - Employee ID
- `clock_in_photo` (file, required) - Photo to verify
- `punch_time` (datetime, required) - Clock-in/out time
- `type` (string, required) - 'clock_in' or 'clock_out'

**Example Request (cURL):**
```bash
curl -X POST http://localhost/hrm-software/api/attendance/clock-in-facial \
  -H "Authorization: Bearer YOUR_SANCTUM_TOKEN" \
  -F "employee_id=1" \
  -F "clock_in_photo=@/path/to/photo.jpg" \
  -F "punch_time=2026-02-17 09:30:00" \
  -F "type=clock_in"
```

**Success Response:**
```json
{
  "success": true,
  "message": "Clock-in verified and recorded successfully",
  "confidence": 85.5,
  "attendance_record": {
    "id": 123,
    "employee_id": 1,
    "clock_in": "2026-02-17 09:30:00",
    "facial_verification_status": "passed",
    "facial_verification_confidence": 85.5,
    "facial_verification_photo": "/storage/verifications/2026/02/17/{filename}"
  }
}
```

**Error Response (Failed Verification):**
```json
{
  "success": false,
  "message": "Facial recognition failed: Confidence score 65% is below 80% threshold",
  "confidence": 65,
  "reason": "CONFIDENCE_TOO_LOW"
}
```

**Error Response (Employee Not Found):**
```json
{
  "success": false,
  "message": "Employee not found or has no document photos",
  "reason": "EMPLOYEE_NOT_FOUND"
}
```

## Testing Workflow

### Step 1: Upload Employee Documents
Ensure employee documents/ID photos are uploaded to the system:

```sql
INSERT INTO employee_documents (employee_id, document_id, document_value, created_by)
VALUES (1, 1, '/path/to/employee/document/photo.jpg', 1);
```

### Step 2: Get Sanctum Token
```bash
curl -X POST http://localhost/hrm-software/api/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "user@example.com",
    "password": "password"
  }'
```

Response includes `token` field used as Bearer token.

### Step 3: Test Verification Only
Test facial recognition without creating attendance record:

```bash
curl -X POST http://localhost/hrm-software/api/facial-recognition/verify \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "employee_id": 1,
    "photo1_path": "/path/to/stored/document.jpg",
    "photo2_path": "/path/to/test/photo.jpg"
  }'
```

### Step 4: Test Clock-In with Facial Recognition

**Get employee ID first:**
```bash
curl -X GET http://localhost/hrm-software/api/user \
  -H "Authorization: Bearer {token}"
```

**Capture photo and submit:**
```bash
curl -X POST http://localhost/hrm-software/api/attendance/clock-in-facial \
  -H "Authorization: Bearer {token}" \
  -F "employee_id=1" \
  -F "clock_in_photo=@/path/to/your/photo.jpg" \
  -F "punch_time=2026-02-17 09:30:00" \
  -F "type=clock_in"
```

### Step 5: Test Clock-Out
```bash
curl -X POST http://localhost/hrm-software/api/attendance/clock-in-facial \
  -H "Authorization: Bearer {token}" \
  -F "employee_id=1" \
  -F "clock_in_photo=@/path/to/your/photo.jpg" \
  -F "punch_time=2026-02-17 17:30:00" \
  -F "type=clock_out"
```

### Step 6: Verify Database Records

Check successful clock-in:
```sql
SELECT id, employee_id, clock_in, facial_verification_status, 
       facial_verification_confidence, facial_verification_photo
FROM attendance_employees
WHERE employee_id = 1 
ORDER BY clock_in DESC 
LIMIT 1;
```

Expected output:
```
| id  | employee_id | clock_in            | facial_verification_status | facial_verification_confidence | facial_verification_photo          |
|-----|-------------|---------------------|----------------------------|---------------------------------|------------------------------------|
| 123 | 1           | 2026-02-17 09:30:00 | passed                     | 85.50                          | /storage/verifications/2026/02/17/{id}.jpg |
```

## Confidence Threshold

- **Default Threshold:** 80%
- **Pass:** Confidence >= 80%
- **Fail:** Confidence < 80%

To adjust threshold, modify in [BiometricAttendanceController.php](app/Http/Controllers/BiometricAttendanceController.php):

```php
// Line ~175 in clockInWithFacialRecognition()
$confidenceThreshold = 80; // Adjust this value
```

## Troubleshooting

### Issue: "Employee has no document photos"
**Solution:** Ensure `employee_documents` table has entries for the employee.

```sql
SELECT * FROM employee_documents WHERE employee_id = 1;
```

If empty, upload employee document:
```bash
# Through web UI: Settings > Employee > Document Upload
```

### Issue: "Invalid or unsupported image format"
**Solution:** Ensure photos are in supported formats:
- JPEG (.jpg, .jpeg)
- PNG (.png)
- Tested for transparency and color profiles

### Issue: "OpenAI API Error"
**Symptoms:** 500 error, "Failed to contact OpenAI"

**Solutions:**
1. Verify API key: `php artisan tinker` → `config('services.openai.api_key')`
2. Check API key has Vision API access
3. Verify OpenAI account has available credits
4. Check server can access `api.openai.com`

### Issue: Low Confidence Scores
**Possible Causes:**
- Poor lighting during photo capture
- Face angle/position differs significantly from document
- Image quality too low
- Employee wearing glasses/accessories not in document photo

**Solutions:**
- Better lighting conditions
- Multiple angles during capture
- Higher resolution camera
- Remove/add accessories as in document
- Consider lowering threshold (carefully)

### Issue: "Facial_verification_photo column not found"
**Solution:** Run migrations:
```bash
php artisan migrate --force
php artisan config:cache
```

## Frontend Integration

### Option 1: Add to Existing Clock-In Button
Modify the clock-in button in attendance views:

```html
<form id="clock-in-form" action="{{ route('attendance.clock-in-facial') }}" method="POST" enctype="multipart/form-data">
    @csrf
    <input type="hidden" name="employee_id" value="{{ auth()->user()->employee_id }}">
    <input type="hidden" name="punch_time" value="{{ now() }}">
    <input type="hidden" name="type" value="clock_in">
    
    <!-- Camera/Photo input -->
    <input type="file" name="clock_in_photo" id="photo-input" accept="image/*" capture="environment" required>
    
    <!-- Preview -->
    <img id="photo-preview" style="display:none; max-width: 200px;">
    
    <button type="submit" class="btn btn-primary">Clock In with Face Verification</button>
</form>

<script>
document.getElementById('photo-input').addEventListener('change', (e) => {
    const file = e.target.files[0];
    const reader = new FileReader();
    reader.onload = () => {
        document.getElementById('photo-preview').src = reader.result;
        document.getElementById('photo-preview').style.display = 'block';
    };
    reader.readAsDataURL(file);
});
</script>
```

### Option 2: Use Dedicated Facial Recognition Page
Route to [clock-in-facial.blade.php](resources/views/biometricattendance/clock-in-facial.blade.php):

```php
// In web.php
Route::get('/attendance/clock-in-facial', [BiometricAttendanceController::class, 'showClockInFacial'])
    ->middleware('auth')
    ->name('attendance.clock-in-facial');
```

## Performance Optimization

### Image Optimization
- Resize large photos before sending to OpenAI
- Maximum recommended: 1200x1200 pixels
- JPEG quality: 85%

### Caching
- Cache employee document paths (5 minutes)
- Reduce database queries on repeated clock-ins

### API Rate Limiting
- OpenAI has rate limits based on plan
- Monitor usage: https://platform.openai.com/usage
- Implement queue processing for bulk operations

## Security Considerations

1. **API Key Protection:**
   - Never commit .env to version control
   - Use environment-specific keys
   - Rotate keys periodically

2. **Photo Storage:**
   - Store in non-public directory: `storage/app/verifications/`
   - Implement access controls
   - Auto-delete photos after retention period (e.g., 30 days)

3. **Authentication:**
   - Sanctum tokens required for all endpoints
   - Token expiration: 60 minutes
   - Implement refresh token mechanism

4. **Audit Logging:**
   - Log all facial verification attempts
   - Track success/failure reasons
   - Monitor for suspicious patterns

## Example Integration Code

```php
// Example: Complete clock-in workflow
$result = $this->facialRecognitionService->verifyByEmployeeId(
    employeeId: 1,
    clockInPhotoPath: $uploadedPhotoPath
);

if ($result['success'] && $result['confidence'] >= 80) {
    AttendanceEmployee::create([
        'employee_id' => 1,
        'clock_in' => now(),
        'facial_verification_status' => 'passed',
        'facial_verification_confidence' => $result['confidence'],
        'facial_verification_photo' => $uploadedPhotoPath
    ]);
    
    return response()->json([
        'success' => true,
        'message' => 'Verified and clocked in'
    ]);
}

return response()->json([
    'success' => false,
    'message' => 'Verification failed'
], 422);
```

## Deployment Checklist

- [ ] Database migrations run successfully
- [ ] .env file has valid OPENAI_API_KEY
- [ ] `php artisan config:cache` executed
- [ ] Employee documents uploaded in system
- [ ] Test verification API with sample photos
- [ ] Test clock-in endpoint with facial recognition
- [ ] Verify photos stored correctly in storage/
- [ ] Confirm attendance records created in database
- [ ] Test error handling scenarios
- [ ] Document employee training procedure
- [ ] Monitor API usage and costs
- [ ] Set up logging and alerting

## Support & Debugging

**Enable detailed logging:**
```php
// In config/logging.php
'channels' => [
    'facial_recognition' => [
        'driver' => 'single',
        'path' => storage_path('logs/facial-recognition.log'),
        'level' => env('LOG_LEVEL', 'debug'),
    ],
],
```

**Check logs:**
```bash
tail -f storage/logs/facial-recognition.log
```

**Test image format:**
```bash
php artisan tinker
$service = app(\App\Services\FacialRecognitionService::class);
$result = $service->getBase64Image('/path/to/image.jpg');
// Check if encoding successful
```

## Success Indicators

✅ API endpoints respond without errors
✅ Facial verification returns confidence scores
✅ Clock-in creates attendance record with facial data
✅ Photos stored in storage backend
✅ Database columns populated correctly
✅ Error messages clear and helpful
✅ Employee can clock in/out with face recognition

## Next Steps

1. Deploy migrations to production
2. Train employees on facial recognition process
3. Monitor facial verification accuracy
4. Adjust confidence threshold if needed
5. Plan additional facial recognition features (time tracking, geolocation)
