<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class FacialRecognitionService
{
    private $apiKey;

    private $apiEndpoint = 'https://api.openai.com/v1/vision/comparisons';

    public function __construct()
    {
        $this->apiKey = config('services.openai.key') ?? env('OPENAI_API_KEY');
    }

    /**
     * Resolve a readable absolute filesystem path for DeepFace (local files only).
     */
    private function resolveLocalPathForDeepFace(string $imagePath): ?string
    {
        if (is_readable($imagePath)) {
            $real = realpath($imagePath);

            return $real !== false ? $real : $imagePath;
        }
        try {
            if (Storage::disk('public')->exists($imagePath)) {
                $p = Storage::disk('public')->path($imagePath);

                return is_readable($p) ? $p : null;
            }
        } catch (\Throwable $e) {
            // ignore
        }
        try {
            if (Storage::exists($imagePath)) {
                $p = Storage::path($imagePath);

                return is_readable($p) ? $p : null;
            }
        } catch (\Throwable $e) {
            // ignore
        }

        return null;
    }

    /**
     * Compare two images to verify if they are the same person
     *
     * @param  string  $clockInPhotoPath  - Path to the photo taken during clock-in
     * @param  string  $employeePhotoPath  - Path to the employee's document/profile photo
     * @return array - ['match' => bool, 'confidence' => float, 'message' => string]
     */
    public function verifyFace($clockInPhotoPath, $employeePhotoPath)
    {
        try {
            if (config('services.deepface.enabled')) {
                $p1 = $this->resolveLocalPathForDeepFace((string) $clockInPhotoPath);
                $p2 = $this->resolveLocalPathForDeepFace((string) $employeePhotoPath);
                if ($p1 && $p2) {
                    $deep = app(DeepFaceVerificationService::class);
                    $r = $deep->verify($p1, $p2);
                    $providerError = ! empty($r['provider_error']);
                    if (! $providerError) {
                        return [
                            'match' => (bool) ($r['match'] ?? false),
                            'confidence' => (float) ($r['confidence'] ?? 0),
                            'message' => (string) ($r['message'] ?? 'DeepFace'),
                            'error' => (bool) ($r['error'] ?? false),
                        ];
                    }
                    if (! config('services.deepface.fallback_openai', true)) {
                        return [
                            'match' => false,
                            'confidence' => 0,
                            'message' => (string) ($r['message'] ?? 'DeepFace failed'),
                            'error' => true,
                        ];
                    }
                    Log::warning('DeepFace failed; falling back to OpenAI', [
                        'message' => $r['message'] ?? '',
                    ]);
                }
            }

            // Convert file paths to base64
            $clockInPhotoBase64 = $this->getBase64Image($clockInPhotoPath);
            $employeePhotoBase64 = $this->getBase64Image($employeePhotoPath);

            if (!$clockInPhotoBase64 || !$employeePhotoBase64) {
                return [
                    'match' => false,
                    'confidence' => 0,
                    'message' => 'Could not read one or both images',
                    'error' => true
                ];
            }

            // Call OpenAI Vision API to compare faces
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->post('https://api.openai.com/v1/chat/completions', [
                'model' => 'gpt-4o',
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => [
                            [
                                'type' => 'text',
                                'text' => 'Compare these two images and determine if they show the same person. Respond with a JSON object containing: {"same_person": boolean, "confidence": number (0-100), "reason": string}'
                            ],
                            [
                                'type' => 'image_url',
                                'image_url' => [
                                    'url' => 'data:image/jpeg;base64,' . $clockInPhotoBase64,
                                    'detail' => 'high'
                                ]
                            ],
                            [
                                'type' => 'image_url',
                                'image_url' => [
                                    'url' => 'data:image/jpeg;base64,' . $employeePhotoBase64,
                                    'detail' => 'high'
                                ]
                            ]
                        ]
                    ]
                ],
                'max_tokens' => 500
            ]);

            if (!$response->successful()) {
                Log::error('OpenAI API Error: ' . $response->body());
                return [
                    'match' => false,
                    'confidence' => 0,
                    'message' => 'API request failed',
                    'error' => true
                ];
            }

            $result = $response->json();
            $content = $result['choices'][0]['message']['content'] ?? '';

            // Parse JSON response from OpenAI
            preg_match('/\{.*\}/s', $content, $matches);
            if (!empty($matches[0])) {
                $jsonResponse = json_decode($matches[0], true);
                return [
                    'match' => $jsonResponse['same_person'] ?? false,
                    'confidence' => $jsonResponse['confidence'] ?? 0,
                    'message' => $jsonResponse['reason'] ?? 'Unable to verify',
                    'error' => false
                ];
            }

            return [
                'match' => false,
                'confidence' => 0,
                'message' => 'Could not parse API response',
                'error' => true
            ];

        } catch (\Exception $e) {
            Log::error('Facial Recognition Error: ' . $e->getMessage());
            return [
                'match' => false,
                'confidence' => 0,
                'message' => 'Verification failed: ' . $e->getMessage(),
                'error' => true
            ];
        }
    }

    /**
     * Convert image file to base64 string
     */
    private function getBase64Image($imagePath)
    {
        try {
            // Handle HTTP/HTTPS URLs
            if (str_starts_with($imagePath, 'http://') || str_starts_with($imagePath, 'https://')) {
                $imageData = file_get_contents($imagePath);
                return $imageData ? base64_encode($imageData) : null;
            }
            // Check if it's a storage path or full path
            if (file_exists($imagePath)) {
                $imageData = file_get_contents($imagePath);
            } else {
                // Try to get from storage
                if (Storage::exists($imagePath)) {
                    $imageData = Storage::get($imagePath);
                } else {
                    return null;
                }
            }

            return base64_encode($imageData);
        } catch (\Exception $e) {
            Log::error('Error converting image to base64: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Get MIME type of image
     */
    private function getImageMimeType($imagePath)
    {
        $mimeType = mime_content_type($imagePath);
        return $mimeType ?: 'image/jpeg';
    }

    /**
     * Verify by Employee ID - compares clock-in photo with employee's document photos
     * 
     * @param int $employeeId - Employee ID
     * @param string $clockInPhotoPath - Path to clock-in photo
     * @return array - ['match' => bool, 'confidence' => float, 'message' => string, 'employee_id' => int]
     */
    public function verifyByEmployeeId($employeeId, $clockInPhotoPath)
    {
        try {
            $employee = \App\Models\Employee::find($employeeId);
            if (!$employee) {
                return [
                    'match' => false,
                    'confidence' => 0,
                    'message' => 'Employee not found',
                    'employee_id' => null,
                    'error' => true
                ];
            }

            // Get employee document photos
            $documentPhotoPaths = $this->getEmployeeDocumentPhotos($employee);

            if (empty($documentPhotoPaths)) {
                return [
                    'match' => false,
                    'confidence' => 0,
                    'message' => 'No document photos found for this employee. Please upload ID or passport photo first.',
                    'employee_id' => $employeeId,
                    'error' => true
                ];
            }

            if (config('services.deepface.enabled')) {
                $clockPath = $this->resolveLocalPathForDeepFace((string) $clockInPhotoPath);
                if ($clockPath) {
                    $deep = app(DeepFaceVerificationService::class);
                    $bestMatch = [
                        'match' => false,
                        'confidence' => 0,
                        'message' => 'No match found',
                    ];
                    $anyProviderError = false;
                    foreach ($documentPhotoPaths as $docPhoto) {
                        $docPath = $this->resolveLocalPathForDeepFace((string) $docPhoto);
                        if (! $docPath) {
                            continue;
                        }
                        $r = $deep->verify($clockPath, $docPath);
                        if (! empty($r['provider_error'])) {
                            $anyProviderError = true;
                            break;
                        }
                        $currentConfidence = (float) ($r['confidence'] ?? 0);
                        if ($currentConfidence > $bestMatch['confidence']) {
                            $bestMatch = [
                                'match' => (bool) ($r['match'] ?? false),
                                'confidence' => $currentConfidence,
                                'message' => (string) ($r['message'] ?? 'Verification complete'),
                            ];
                        }
                        if ($bestMatch['match'] && $bestMatch['confidence'] >= 80) {
                            break;
                        }
                    }
                    if (! $anyProviderError) {
                        return array_merge($bestMatch, [
                            'employee_id' => $employeeId,
                            'error' => false,
                        ]);
                    }
                    if (! config('services.deepface.fallback_openai', true)) {
                        return [
                            'match' => false,
                            'confidence' => 0,
                            'message' => 'DeepFace failed for document verification',
                            'employee_id' => $employeeId,
                            'error' => true,
                        ];
                    }
                    Log::warning('DeepFace document verify failed; falling back to OpenAI');
                }
            }

            // Convert clock-in photo to base64
            $clockInPhotoBase64 = $this->getBase64Image($clockInPhotoPath);
            if (!$clockInPhotoBase64) {
                return [
                    'match' => false,
                    'confidence' => 0,
                    'message' => 'Could not read clock-in photo',
                    'employee_id' => $employeeId,
                    'error' => true
                ];
            }

            // Check against all employee document photos
            $bestMatch = [
                'match' => false,
                'confidence' => 0,
                'message' => 'No match found'
            ];

            foreach ($documentPhotoPaths as $docPhoto) {
                $docPhotoBase64 = $this->getBase64Image($docPhoto);
                if (!$docPhotoBase64) continue;

                // Call OpenAI Vision API
                $result = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type' => 'application/json',
                ])->post('https://api.openai.com/v1/chat/completions', [
                    'model' => 'gpt-4o',
                    'messages' => [
                        [
                            'role' => 'user',
                            'content' => [
                                [
                                    'type' => 'text',
                                    'text' => 'Compare these two images and determine if they show the same person. Respond ONLY with a valid JSON object: {"same_person": boolean, "confidence": number from 0 to 100, "reason": "brief reason"}'
                                ],
                                [
                                    'type' => 'image_url',
                                    'image_url' => [
                                        'url' => 'data:image/jpeg;base64,' . $clockInPhotoBase64,
                                        'detail' => 'high'
                                    ]
                                ],
                                [
                                    'type' => 'image_url',
                                    'image_url' => [
                                        'url' => 'data:image/jpeg;base64,' . $docPhotoBase64,
                                        'detail' => 'high'
                                    ]
                                ]
                            ]
                        ]
                    ],
                    'max_tokens' => 200,
                    'temperature' => 0.3
                ]);

                if ($result->successful()) {
                    $content = $result->json()['choices'][0]['message']['content'] ?? '';
                    
                    // Extract JSON from response
                    preg_match('/\{[^}]+\}/', $content, $matches);
                    if (!empty($matches[0])) {
                        $jsonResponse = json_decode($matches[0], true);
                        if ($jsonResponse) {
                            $currentConfidence = $jsonResponse['confidence'] ?? 0;
                            
                            // Keep best match
                            if ($currentConfidence > $bestMatch['confidence']) {
                                $bestMatch = [
                                    'match' => $jsonResponse['same_person'] ?? false,
                                    'confidence' => $currentConfidence,
                                    'message' => $jsonResponse['reason'] ?? 'Verification complete'
                                ];

                                // If high confidence match found, stop searching
                                if ($bestMatch['match'] && $bestMatch['confidence'] >= 80) {
                                    break;
                                }
                            }
                        }
                    }
                }
            }

            return array_merge($bestMatch, [
                'employee_id' => $employeeId,
                'error' => false
            ]);

        } catch (\Exception $e) {
            Log::error('Employee Facial Verification Error: ' . $e->getMessage());
            return [
                'match' => false,
                'confidence' => 0,
                'message' => 'Verification error: ' . $e->getMessage(),
                'employee_id' => $employeeId,
                'error' => true
            ];
        }
    }

    /**
     * Get employee document photo paths
     */
    private function getEmployeeDocumentPhotos($employee)
    {
        $photoPaths = [];

        try {
            // Search by both numeric id and employee_id string to cover all cases
            $documents = \App\Models\EmployeeDocument::where(function ($q) use ($employee) {
                $q->where('employee_id', $employee->id)
                  ->orWhere('employee_id', $employee->employee_id);
            })->get();

            foreach ($documents as $doc) {
                if (!empty($doc->document_value)) {
                    // Try multiple possible paths
                    $candidates = [
                        storage_path('uploads/document/' . $doc->document_value),
                        storage_path('app/public/' . $doc->document_value),
                        public_path('storage/' . $doc->document_value),
                        storage_path('uploads/' . $doc->document_value),
                        public_path('uploads/' . $doc->document_value),
                        public_path('uploads/document/' . $doc->document_value),
                    ];
                    foreach ($candidates as $path) {
                        if (file_exists($path)) {
                            $photoPaths[] = $path;
                            break;
                        }
                    }
                }
            }

            // Also check employee's user avatar if no documents found
            if (empty($photoPaths)) {
                $user = \App\Models\User::find($employee->user_id);
                $avatar = $user->avatar ?? null;
                if ($avatar && $avatar !== 'avatar.png' && $avatar !== 'default.png') {
                    // If avatar is a full URL, use it directly
                    if (str_starts_with($avatar, 'http://') || str_starts_with($avatar, 'https://')) {
                        $photoPaths[] = $avatar;
                    } else {
                        $profileCandidates = [
                            storage_path('uploads/avatar/' . $avatar),
                            storage_path('app/public/users-avatar/' . $avatar),
                            public_path('storage/uploads/avatar/' . $avatar),
                            public_path('uploads/avatar/' . $avatar),
                        ];
                        foreach ($profileCandidates as $path) {
                            if (file_exists($path)) {
                                $photoPaths[] = $path;
                                break;
                            }
                        }
                        // Fallback: try as public URL
                        if (empty($photoPaths)) {
                            $photoPaths[] = url('storage/uploads/avatar/' . $avatar);
                        }
                    }
                }
            }

            // Also always add document photos from Document section
            $documents = \App\Models\EmployeeDocument::where(function ($q) use ($employee) {
                $q->where('employee_id', $employee->id)
                  ->orWhere('employee_id', $employee->employee_id);
            })->whereNotNull('document_value')->get();
            foreach ($documents as $doc) {
                if (!empty($doc->document_value)) {
                    $val = $doc->document_value;
                    if (str_starts_with($val, 'http://') || str_starts_with($val, 'https://')) {
                        if (!in_array($val, $photoPaths)) $photoPaths[] = $val;
                    } else {
                        $candidates = [
                            storage_path('uploads/document/' . $val),
                            public_path('storage/uploads/document/' . $val),
                            storage_path('app/public/uploads/document/' . $val),
                        ];
                        foreach ($candidates as $path) {
                            if (file_exists($path) && !in_array($path, $photoPaths)) {
                                $photoPaths[] = $path;
                                break;
                            }
                        }
                        // URL fallback
                        $urlPath = url('storage/uploads/document/' . $val);
                        if (!in_array($urlPath, $photoPaths)) $photoPaths[] = $urlPath;
                    }
                }
            }
        } catch (\Exception $e) {
            Log::warning('Error fetching employee documents: ' . $e->getMessage());
        }

        return $photoPaths;
    }
}


