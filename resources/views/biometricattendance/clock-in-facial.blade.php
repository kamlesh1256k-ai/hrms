@extends('layouts.admin')

@section('page-title')
    {{ __('Clock In with Facial Recognition') }}
@endsection

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Home') }}</a></li>
    <li class="breadcrumb-item">{{ __('Clock In') }}</li>
@endsection

@section('content')
<div class="row">
    <div class="col-md-8 offset-md-2">
        <div class="card">
            <div class="card-header">
                <h5>{{ __('Facial Recognition Clock In') }}</h5>
            </div>
            <div class="card-body">
                <div class="alert alert-info">
                    <strong>Instructions:</strong> Please allow camera access and take a clear photo of your face for verification.
                </div>

                <form id="clockInForm" enctype="multipart/form-data">
                    @csrf
                    
                    <div class="form-group mb-3">
                        <label class="form-label">{{ __('Employee ID') }}</label>
                        <input type="number" class="form-control" id="employee_id" name="employee_id" required>
                    </div>

                    <!-- Camera preview and capture -->
                    <div class="form-group mb-3">
                        <label class="form-label">{{ __('Take Photo') }}</label>
                        <div id="cameraContainer" class="border p-3 mb-3" style="text-align: center;">
                            <video id="video" width="100%" height="400" style="border-radius: 8px; display: none;"></video>
                            <canvas id="canvas" width="640" height="480" style="display: none;"></canvas>
                            <img id="photoPreview" style="max-width: 100%; border-radius: 8px; display: none;"/>
                            <div id="noWebcam" class="alert alert-warning" style="display: none;">
                                Webcam not available. Please use the file input below.
                            </div>
                        </div>

                        <div id="cameraControls" class="mb-3">
                            <button type="button" class="btn btn-primary me-2" id="startCameraBtn">
                                <i class="ti ti-camera"></i> {{ __('Start Camera') }}
                            </button>
                            <button type="button" class="btn btn-success me-2" id="capturePhotoBtn" style="display: none;">
                                <i class="ti ti-camera-check"></i> {{ __('Capture Photo') }}
                            </button>
                            <button type="button" class="btn btn-danger" id="stopCameraBtn" style="display: none;">
                                <i class="ti ti-x"></i> {{ __('Stop Camera') }}
                            </button>
                        </div>

                        <!-- File input as fallback -->
                        <label class="form-label">{{ __('Or Upload Photo') }}</label>
                        <input type="file" id="clock_in_photo" name="clock_in_photo" class="form-control" accept="image/*" required>
                        <input type="hidden" id="photo_data" name="photo_data">
                    </div>

                    <div class="form-group">
                        <button type="submit" class="btn btn-success">
                            <i class="ti ti-check"></i> {{ __('Verify & Clock In') }}
                        </button>
                    </div>
                </form>

                <!-- Verification Result -->
                <div id="verificationResult" style="display: none;">
                    <div id="resultContent"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    let video = document.getElementById('video');
    let canvas = document.getElementById('canvas');
    let photoPreview = document.getElementById('photoPreview');
    let cameraActive = false;

    document.getElementById('startCameraBtn').addEventListener('click', startCamera);
    document.getElementById('capturePhotoBtn').addEventListener('click', capturePhoto);
    document.getElementById('stopCameraBtn').addEventListener('click', stopCamera);
    document.getElementById('clockInForm').addEventListener('submit', submitForm);

    function startCamera() {
        navigator.mediaDevices.getUserMedia({ video: { facingMode: 'user' } })
            .then(stream => {
                video.srcObject = stream;
                video.style.display = 'block';
                photoPreview.style.display = 'none';
                cameraActive = true;

                document.getElementById('startCameraBtn').style.display = 'none';
                document.getElementById('capturePhotoBtn').style.display = 'inline-block';
                document.getElementById('stopCameraBtn').style.display = 'inline-block';
            })
            .catch(error => {
                console.error('Error accessing camera:', error);
                document.getElementById('noWebcam').style.display = 'block';
            });
    }

    function capturePhoto() {
        const context = canvas.getContext('2d');
        context.drawImage(video, 0, 0, canvas.width, canvas.height);

        // Convert canvas to blob and add to form
        canvas.toBlob(blob => {
            const file = new File([blob], 'clock-in-photo.jpg', { type: 'image/jpeg' });
            const dataTransfer = new DataTransfer();
            dataTransfer.items.add(file);
            document.getElementById('clock_in_photo').files = dataTransfer.files;

            // Show preview
            photoPreview.src = canvas.toDataURL('image/jpeg');
            photoPreview.style.display = 'block';
            video.style.display = 'none';

            stopCamera();
        }, 'image/jpeg', 0.95);
    }

    function stopCamera() {
        if (video.srcObject) {
            video.srcObject.getTracks().forEach(track => track.stop());
            cameraActive = false;
        }
        video.style.display = 'none';
        document.getElementById('startCameraBtn').style.display = 'inline-block';
        document.getElementById('capturePhotoBtn').style.display = 'none';
        document.getElementById('stopCameraBtn').style.display = 'none';
    }

    function submitForm(e) {
        e.preventDefault();

        const employeeId = document.getElementById('employee_id').value;
        const photoFile = document.getElementById('clock_in_photo').files[0];

        if (!employeeId || !photoFile) {
            alert('{{ __("Please fill all fields and capture/upload a photo") }}');
            return;
        }

        const formData = new FormData();
        formData.append('employee_id', employeeId);
        formData.append('clock_in_photo', photoFile);

        // Show loading
        const btn = document.querySelector('button[type="submit"]');
        const originalText = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<i class="ti ti-loader"></i> {{ __("Verifying...") }}';

        fetch('/api/facial-recognition/verify', {
            method: 'POST',
            headers: {
                'Authorization': 'Bearer ' + document.querySelector('meta[name="csrf-token"]').content,
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            const resultDiv = document.getElementById('verificationResult');
            const resultContent = document.getElementById('resultContent');

            if (data.success) {
                resultContent.innerHTML = `
                    <div class="alert alert-success">
                        <h5>{{ __("Verification Successful!") }}</h5>
                        <p>${data.message}</p>
                        <p><strong>{{ __("Confidence:") }}</strong> ${data.confidence}%</p>
                        <p class="mt-3">
                            <a href="{{ route('biometric-attendance.index') }}" class="btn btn-primary">
                                {{ __("View Attendance") }}
                            </a>
                        </p>
                    </div>
                `;
            } else {
                resultContent.innerHTML = `
                    <div class="alert alert-danger">
                        <h5>{{ __("Verification Failed!") }}</h5>
                        <p>${data.message}</p>
                        <p><strong>{{ __("Confidence:") }}</strong> ${data.confidence}%</p>
                        ${data.reason ? `<p><strong>{{ __("Reason:") }}</strong> ${data.reason}</p>` : ''}
                    </div>
                `;
            }
            resultDiv.style.display = 'block';

            btn.disabled = false;
            btn.innerHTML = originalText;
        })
        .catch(error => {
            console.error('Error:', error);
            const resultDiv = document.getElementById('verificationResult');
            const resultContent = document.getElementById('resultContent');
            resultContent.innerHTML = `
                <div class="alert alert-danger">
                    <h5>{{ __("Error") }}</h5>
                    <p>{{ __("An error occurred during verification. Please try again.") }}</p>
                </div>
            `;
            resultDiv.style.display = 'block';

            btn.disabled = false;
            btn.innerHTML = originalText;
        });
    }
</script>

<style>
    #video {
        transform: scaleX(-1);
        -webkit-transform: scaleX(-1);
    }
</style>
@endsection
