# Facial Recognition Clock-In System

## Overview
This system uses **OpenAI's Vision API** to perform facial recognition and verify employee identity during clock-in. The system compares the photo taken during clock-in with the employee's stored document/profile photo.

## Setup Instructions

### 1. Install Dependencies
```bash
composer require guzzlehttp/guzzle
```

### 2. Add OpenAI API Key to Environment
Edit your `.env` file:
```
OPENAI_API_KEY=sk-your-openai-api-key-here
```

### 3. Create Storage Directories
```bash
mkdir -p storage/app/public/temp/clock-in
chmod -R 755 storage/app/public/
php artisan storage:link
```

### 4. Database Configuration
Ensure your employees have:
- **Avatar/profile photos** in `employees.avatar` field
- **Document photos** in the `employee_documents` table

### 5. Add Route to Web Routes (Optional)
If you want to add a web route for the facial recognition page:

```php
// In routes/web.php
Route::get('/clock-in-facial', function() {
    return view('biometricattendance.clock-in-facial');
})->middleware('auth')->name('clock-in-facial');
```

## API Endpoint

### Facial Recognition Verification
**POST** `/api/facial-recognition/verify`

**Headers:**
```
Authorization: Bearer {sanctum_token}
Content-Type: multipart/form-data
```

**Parameters:**
- `employee_id` (required, integer): The employee ID
- `clock_in_photo` (required, file): Image file (jpeg, png, jpg, gif)

**Response Success (200):**
```json
{
    "success": true,
    "message": "Facial recognition successful. Employee verified.",
    "confidence": 92,
    "employee_id": 123
}
```

**Response Failure (422):**
```json
{
    "success": false,
    "message": "Facial recognition failed. Identity does not match employee records.",
    "confidence": 45,
    "reason": "Facial features do not match"
}
```

## How It Works

### Frontend (Vue/JavaScript)
1. User enters Employee ID
2. Opens camera or uploads photo
3. Captures clear photo of face
4. Sends photo to API for verification

### Backend Processing
1. **Receives** clock-in photo from employee
2. **Retrieves** employee's stored photos (avatar + documents)
3. **Converts** both images to base64
4. **Sends** to OpenAI Vision API for comparison
5. **Compares** facial features in both images
6. **Returns** result with confidence score (0-100)
7. **Verifies** if confidence >= 80% for login/clock-in

### OpenAI Vision API
- Model: `gpt-4-vision-preview`
- Compares two images
- Returns: `same_person` (boolean), `confidence` (0-100), `reason` (string)

## Security Features

✅ **Facial Verification Required** - Only matching employees can clock in  
✅ **High Confidence Threshold** - Requires 80%+ match  
✅ **API Token Authentication** - Sanctum middleware  
✅ **Temporary Photo Cleanup** - Clock-in photos deleted after verification  
✅ **Error Logging** - All failures logged for audit trail  

## Usage Example

```bash
curl -X POST http://localhost/hrm-software/api/facial-recognition/verify \
  -H "Authorization: Bearer YOUR_SANCTUM_TOKEN" \
  -F "employee_id=123" \
  -F "clock_in_photo=@/path/to/photo.jpg"
```

## Troubleshooting

### "No employee photo found in system"
- Upload employee avatar in employee profile
- Or upload identity documents to employee records

### "Facial recognition failed"
- Ensure good lighting in photo
- Take clear, frontal photo
- Verify employee photo is clear in system

### OPENAI_API_KEY not found
- Set in .env file
- Run `php artisan config:cache`

### Image conversion fails
- Check file permissions on storage/app/public
- Verify image format is supported (jpeg, png, jpg, gif)

## Confidence Scoring

The system returns a confidence score (0-100):
- **90-100**: Very high match - Accept
- **80-89**: High match - Accept  
- **70-79**: Medium match - May require additional verification
- **Below 70**: Low match - Reject

Current threshold: **80%** for automatic acceptance

## Cost Considerations

OpenAI API charges per image:
- Vision tasks (image comparison): ~$0.01 per comparison
- Estimate: 1-2 cents per successful clock-in

## Performance Notes

- Average verification time: 2-5 seconds
- Depends on image quality and internet speed
- First request may take slightly longer

## Next Steps

1. ✅ Service created
2. ✅ Controller updated
3. ✅ API routes configured  
4. ✅ Frontend UI created
5. Next: Test with real employee photos
