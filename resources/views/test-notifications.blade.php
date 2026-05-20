@extends('layouts.admin')

@section('page-title')
    Test Notifications
@endsection

@push('css-page')
    <style>
        .test-container {
            max-width: 800px;
            margin: 20px auto;
            padding: 20px;
        }
        .test-button {
            margin: 10px;
            padding: 15px 25px;
            font-size: 16px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .test-substitute { background: #3b82f6; color: white; }
        .test-leave { background: #f59e0b; color: white; }
        .test-exit { background: #ef4444; color: white; }
        .test-recruitment { background: #10b981; color: white; }
        .test-button:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.15); }
    </style>
@endpush

@section('content')
    <div class="test-container">
        <h2>Notification System Test</h2>
        <p>Click the buttons below to test notification highlighting and voice announcements:</p>
        
        <button class="test-button test-substitute" onclick="testNotification('substitute')">
            <i class="ti ti-bell"></i> Test Substitute Notification
        </button>
        
        <button class="test-button test-leave" onclick="testNotification('leave')">
            <i class="ti ti-calendar-event"></i> Test Leave Notification
        </button>
        
        <button class="test-button test-exit" onclick="testNotification('exit')">
            <i class="ti ti-logout"></i> Test Exit Notification
        </button>
        
        <button class="test-button test-recruitment" onclick="testNotification('recruitment')">
            <i class="ti ti-briefcase"></i> Test Recruitment Notification
        </button>
        
        <div style="margin-top: 30px; padding: 20px; background: #f8fafc; border-radius: 8px;">
            <h3>Instructions:</h3>
            <ul>
                <li>Click any button above to simulate receiving a new notification</li>
                <li>You should see the notification icon highlight with animation</li>
                <li>You should hear a voice announcement in Hindi</li>
                <li>The highlighting will stop after 5 seconds</li>
                <li>Check your browser's audio permissions if voice doesn't work</li>
            </ul>
        </div>
        
        <div style="margin-top: 20px; padding: 15px; background: #fef3c7; border-radius: 8px;">
            <strong>Note:</strong> This test page simulates notifications. In the actual dashboard, notifications will be detected automatically every 30 seconds.
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        // Test function for notifications
        function testNotification(type) {
            if (typeof window.testNotification === 'function') {
                window.testNotification(type, 1);
                console.log('Test notification triggered for:', type);
            } else {
                console.error('Test notification function not available');
                alert('Notification system not loaded. Please ensure you are on the dashboard page.');
            }
        }
    </script>
@endpush
