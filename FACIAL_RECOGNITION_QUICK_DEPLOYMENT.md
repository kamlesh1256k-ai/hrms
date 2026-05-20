# Facial Recognition System - Quick Deployment Checklist

**Duration:** ~5 minutes to deploy  
**Difficulty:** Easy  
**Requirements:** Terminal access, PHP 8.0+

---

## âœ… Pre-Deployment Verification

Run these commands to verify everything is ready:

```bash
# 1. Check PHP version (should be 8.0+)
php -v

# 2. List files to deploy
ls -la app/Services/FacialRecognitionService.php
ls -la app/Http/Controllers/BiometricAttendanceController.php
ls -la database/migrations/2026_02_17_000001*.php

# 3. Verify .env has OpenAI key
grep OPENAI_API_KEY .env
# Should show: OPENAI_API_KEY=sk-proj-...

# 4. Check database connectivity
php artisan tinker
# Type: DB::connection()->getPDO();
# Should return: PDOConnection object
# Type: exit
```

---

## ðŸš€ Deployment Steps

### Step 1: Backup Database (Recommended)
```bash
cd /path/to/hrm-software

# Create backup
mysqldump -u root -p hrm-software > backup_2026_02_17_facial.sql
# Enter password when prompted
```

**Expected:** Backup file created (should be 1-10 MB)

---

### Step 2: Run Database Migrations
```bash
php artisan migrate
```

**Expected Output:**
```
Migrating: 2026_02_17_000001_add_facial_verification_to_attendance_employees_table
Migrated:  2026_02_17_000001_add_facial_verification_to_attendance_employees_table (15ms)
```

**If error:** Check backup and restore:
```bash
mysql -u root -p hrm-software < backup_2026_02_17_facial.sql
```

---

### Step 3: Clear Configuration Cache
```bash
php artisan config:cache
php artisan cache:clear
```

**Expected:** No errors, cache cleared message

---

### Step 4: Verify API Key Configuration
```bash
php artisan tinker

# Inside tinker prompt:
config('services.openai.api_key')  # Should show: sk-proj-...

# Test OpenAI connection:
Http::withHeaders(['Authorization' => 'Bearer ' . config('services.openai.api_key')])
  ->get('https://api.openai.com/v1/models')
  ->json();
# Should return model list without error

exit  # Exit tinker
```

---

### Step 5: Verify Database Schema
```bash
php artisan tinker

# Check new columns exist:
Schema::hasColumn('attendance_employees', 'facial_verification_photo')
Schema::hasColumn('attendance_employees', 'facial_verification_status')
Schema::hasColumn('attendance_employees', 'facial_verification_confidence')

# All should return: true

exit
```

---

### Step 6: Test API Endpoint

#### Get Authentication Token
```bash
curl -X POST http://localhost/hrm-software/api/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "admin@example.com",
    "password": "your_password"
  }'
```

**Response should include:**
```json
{
  "token": "YOUR_TOKEN_HERE",
  "message": "Login successful"
}
```

Copy the token value.

---

#### Test Facial Recognition Endpoint
```bash
TOKEN="paste_your_token_here"
EMPLOYEE_ID="1"
PHOTO_PATH="/path/to/test/photo.jpg"
PUNCH_TIME="2026-02-17 09:30:00"

curl -X POST http://localhost/hrm-software/api/attendance/clock-in-facial \
  -H "Authorization: Bearer $TOKEN" \
  -F "employee_id=$EMPLOYEE_ID" \
  -F "clock_in_photo=@$PHOTO_PATH" \
  -F "punch_time=$PUNCH_TIME" \
  -F "type=clock_in"
```

**Expected Response (Success):**
```json
{
  "success": true,
  "message": "Clock-in verified and recorded successfully",
  "confidence": 85.5,
  "attendance_record": {
    "id": 123,
    "employee_id": 1,
    "facial_verification_status": "passed",
    "facial_verification_confidence": 85.5
  }
}
```

**Expected Response (Verification Failed):**
```json
{
  "success": false,
  "message": "Facial recognition failed: confidence 65% is below 80% threshold",
  "confidence": 65,
  "reason": "CONFIDENCE_TOO_LOW"
}
```

---

### Step 7: Verify Database Records
```bash
mysql -u root -p hrm-software
# Enter password

# Check if attendance record created (if Step 6 succeeded):
SELECT id, employee_id, clock_in, facial_verification_status, 
       facial_verification_confidence 
FROM attendance_employees 
WHERE employee_id = 1 
ORDER BY id DESC 
LIMIT 1;

# Exit MySQL
exit
```

---

### Step 8: Check Application Logs
```bash
# Check for any errors
tail -20 storage/logs/laravel.log

# Should show request processed without errors
# Example: [2026-02-17 10:30:00] local.INFO: Facial verification successful (confidence: 85.5%)
```

---

## ðŸ§ª Functionality Verification

After deployment, verify each feature works:

### Feature 1: Leave Balance (Unchanged by facial recognition)
```bash
# Verify in web UI:
1. Navigate to: Attendance > Leave Management
2. Create vacation leave with substitute
3. Check: Original employee loses balance
4. Check: Substitute employee keeps balance intact
```

### Feature 2: Substitute Field (Unchanged by facial recognition)
```bash
# Verify in web UI:
1. Navigate to: Attendance > Leave Management > Create
2. Select: Any non-vacation leave type
3. Check: Substitute field is HIDDEN
4. Select: Vacation leave type
5. Check: Substitute field appears and is REQUIRED
```

### Feature 3: Facial Recognition (New Feature)
```bash
# Already tested in Step 6 via API
# To test via web UI:
1. Navigate to: /attendance/clock-in-facial
2. Allow camera permission
3. Take/upload face photo
4. Click "Clock In"
5. Check: Success response with confidence %
```

---

## âœ… Success Indicators

After deployment, you should see:

- [ ] Migration completed without errors
- [ ] Configuration cached successfully
- [ ] API token obtained successfully
- [ ] Clock-in test endpoint returned success/failure response
- [ ] Database records show facial_verification_* columns populated
- [ ] Logs show no critical errors
- [ ] Leave balance working as expected
- [ ] Substitute field showing only for vacation leaves
- [ ] Facial recognition API responding

If all checks pass: âœ… **DEPLOYMENT SUCCESSFUL**

---

## âš ï¸ Troubleshooting Quick Fixes

### Error: "OPENAI_API_KEY is missing"
```bash
# Fix:
grep OPENAI_API_KEY .env
# If empty, add to .env:
echo "OPENAI_API_KEY=sk-your-openai-api-key-here" >> .env

# Then:
php artisan config:cache
```

### Error: "Column 'facial_verification_photo' not found"
```bash
# Migration didn't run
php artisan migrate --force
```

### 401 Unauthorized Error
```bash
# Token expired or invalid
# Re-run curl login command to get new token
```

### Low Confidence Scores
```
Causes:
- Poor lighting
- Face at odd angle
- Photo quality too low
- Different person than in document

Solutions:
- Retry with better lighting
- Face toward camera directly
- Use high-quality camera
- Verify correct employee ID
```

### "Employee has no document photos"
```bash
# Ensure employee has uploaded documents:
mysql -u root -p hrm-software
SELECT * FROM employee_documents WHERE employee_id = 1;

# If empty, upload document via web UI:
Settings > Employees > Documents section
```

---

## ðŸ“Š Post-Deployment Monitoring

### Check logs daily for first week
```bash
grep -i "facial\|verification\|error" storage/logs/laravel.log | tail -20
```

### Monitor accuracy
- Note false rejection rate
- If > 20%: Lower confidence threshold to 75
- If false acceptance occurs: Raise to 85

### Track usage
```bash
# Count daily facial verifications:
mysql -u root -p hrm-software
SELECT DATE(clock_in), COUNT(*) FROM attendance_employees 
WHERE facial_verification_status = 'passed' 
GROUP BY DATE(clock_in);
```

---

## ðŸŽ¯ Training Employees

Provide employees with:
1. Clear, well-lit photo during clock-in
2. Face centered in camera frame
3. No glasses/hats if not in ID photo
4. 2-3 attempts if needed
5. Manual clock-in fallback option (if available)

### Sample Employee Instructions
```
FACIAL RECOGNITION CLOCK-IN PROCESS:

1. Open the clock-in page
2. Click "Take Photo" or upload image
3. Position your face clearly in frame
4. Ensure good lighting (similar to your ID photo)
5. Click "Clock In"
6. Wait for verification (2-4 seconds)
7. If successful: Attendance recorded âœ“
8. If failed: Try again with better lighting
```

---

## ðŸ“ž Quick Support Reference

| Issue | Command |
|-------|---------|
| Check logs | `tail -f storage/logs/laravel.log` |
| Test migration | `php artisan migrate:status` |
| Clear cache | `php artisan cache:clear` |
| Test API key | `php artisan tinker` â†’ `config('services.openai.api_key')` |
| List migrations | `php artisan migrate:list` |
| Rollback if needed | `php artisan migrate:rollback` |

---

## âœ¨ You're Done!

Your facial recognition system is now live.

**Next Steps:**
1. Train team on new features
2. Monitor for 1 week
3. Gather user feedback
4. Adjust confidence threshold if needed
5. Plan next enhancements

---

**Deployment Checklist:** FACIAL_RECOGNITION_QUICK_DEPLOYMENT.md  
**Version:** 1.0  
**Status:** âœ… Ready to Deploy

For detailed information, see:
- `FACIAL_RECOGNITION_QUICK_REFERENCE.md`
- `FACIAL_RECOGNITION_TESTING_GUIDE.md`
- `FACIAL_RECOGNITION_DEPLOYMENT_STATUS.md`
