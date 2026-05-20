# Miraix HR Activity Tracker - Live Deployment Guide

## 🚨 ISSUE IDENTIFIED

**Problem**: Activity tracker working locally but NOT showing on live Miraix.in dashboard

**Root Cause**: Activity tracker module not deployed on live Miraix.in server

---

## 🔍 DIAGNOSIS

### **Current Status:**
- ✅ **Local Environment**: Working perfectly
- ❌ **Live Server**: Module not deployed
- ❌ **API Endpoints**: Returning 404 errors
- ❌ **Dashboard**: Activity tracker menu missing

### **What's Missing on Live Server:**
1. **Database Tables**: Activity tracker tables not created
2. **Routes**: API routes not registered
3. **Controllers**: Activity tracker controllers missing
4. **Views**: Dashboard views not deployed
5. **Permissions**: Activity tracker permissions not seeded

---

## 🛠️ DEPLOYMENT STEPS

### **Step 1: Database Migration on Live Server**

```bash
# SSH into live Miraix.in server
ssh user@miraix.in

# Navigate to project directory
cd /var/www/miraix.in

# Run activity tracker migrations
php artisan migrate --path=database/migrations/2026_05_02_000001_create_at_devices_table.php
php artisan migrate --path=database/migrations/2026_05_02_000002_create_at_activity_logs_table.php
php artisan migrate --path=database/migrations/2026_05_02_000003_create_at_screenshots_table.php
php artisan migrate --path=database/migrations/2026_05_02_000004_create_at_app_usage_logs_table.php
php artisan migrate --path=database/migrations/2026_05_02_000005_create_at_daily_summaries_table.php

# Seed permissions
php artisan db:seed --class=ActivityTrackerPermissionsSeeder

# Create storage link for screenshots
php artisan storage:link
```

### **Step 2: Deploy Files to Live Server**

```bash
# Copy activity tracker files to live server
rsync -av Modules/activity-tracker/laravel/ /var/www/miraix.in/

# Specific files to copy:
# - app/Models/At*.php
# - app/Http/Controllers/ActivityTrackerController.php
# - app/Http/Controllers/Api/ActivityTrackerApiController.php
# - resources/views/activity_tracker/
# - database/migrations/2026_05_02_*.php
# - database/seeders/ActivityTrackerPermissionsSeeder.php
```

### **Step 3: Update Routes on Live Server**

```bash
# Add activity tracker routes to web.php
# Edit /var/www/miraix.in/routes/web.php
# Add these lines:

// Activity Tracker Routes
Route::middleware(['auth', 'XSS'])->prefix('activity-tracker')->name('activity-tracker.')->group(function () {
    Route::get('/', [ActivityTrackerController::class, 'index'])->name('index');
    Route::get('/user-activity', [ActivityTrackerController::class, 'userActivity'])->name('user-activity');
    Route::get('/timeline', [ActivityTrackerController::class, 'timeline'])->name('timeline');
    Route::get('/app-usage', [ActivityTrackerController::class, 'appUsage'])->name('app-usage');
    Route::get('/daily-report', [ActivityTrackerController::class, 'dailyReport'])->name('daily-report');
    Route::get('/token', [ActivityTrackerController::class, 'token'])->name('token');
});

// Activity Tracker API Routes
Route::middleware(['auth:sanctum'])->prefix('api/activity-tracker')->name('api.activity-tracker.')->group(function () {
    Route::post('/device/register', [ActivityTrackerApiController::class, 'registerDevice']);
    Route::post('/device/heartbeat', [ActivityTrackerApiController::class, 'heartbeat']);
    Route::post('/activity/store', [ActivityTrackerApiController::class, 'storeActivity']);
    Route::post('/screenshot/upload', [ActivityTrackerApiController::class, 'uploadScreenshot']);
    Route::post('/app-usage/store', [ActivityTrackerApiController::class, 'storeAppUsage']);
    Route::get('/dashboard/summary', [ActivityTrackerApiController::class, 'dashboardSummary']);
});
```

### **Step 4: Update Navigation Menu**

```bash
# Edit navigation file to add activity tracker menu
# Edit /var/www/miraix.in/resources/views/partial/Admin/menu.blade.php

# Add this menu item for HR/Admin:
@can('manage-activity-tracker')
    <li class="dash-item {{ request()->routeIs('activity-tracker.*') ? 'active' : '' }}">
        <a class="dash-link" href="{{ route('activity-tracker.index') }}">
            <span class="dash-micon">
                <i class="ti ti-activity"></i>
            </span>
            <span class="dash-mtext">{{ __('Activity Tracker') }}</span>
        </a>
    </li>
@endcan
```

### **Step 5: Clear Caches on Live Server**

```bash
# Clear all Laravel caches
php artisan cache:clear
php artisan config:clear
php artisan view:clear
php artisan route:clear
php artisan optimize

# Restart queue workers if any
php artisan queue:restart
```

---

## 🔧 TROUBLESHOOTING

### **Issue 1: API Endpoints 404**
**Solution**: Deploy API routes and controllers
```bash
# Check if routes are registered
php artisan route:list | grep activity-tracker

# If not found, add routes to api.php and clear cache
```

### **Issue 2: Dashboard Menu Missing**
**Solution**: Deploy views and update navigation
```bash
# Check if views exist
ls -la /var/www/miraix.in/resources/views/activity_tracker/

# If missing, copy views from local
```

### **Issue 3: Database Tables Missing**
**Solution**: Run migrations on live server
```bash
# Check if tables exist
php artisan tinker
>>> Schema::hasTable('at_devices');
>>> Schema::hasTable('at_activity_logs');

# If false, run migrations
```

### **Issue 4: Permissions Missing**
**Solution**: Seed permissions
```bash
# Check permissions
php artisan tinker
>>> $user = User::find(1);
>>> $user->hasPermission('manage-activity-tracker');

# If false, seed permissions
php artisan db:seed --class=ActivityTrackerPermissionsSeeder
```

---

## 📋 VERIFICATION CHECKLIST

### **After Deployment, Verify:**

#### **1. API Endpoints**
```bash
# Test API endpoints
curl -X GET https://miraix.in/api/activity-tracker/dashboard/summary
# Should return JSON response, not 404
```

#### **2. Dashboard Access**
```bash
# Login to Miraix.in and check:
- [ ] Activity Tracker menu appears in sidebar
- [ ] Dashboard loads without errors
- [ ] All sub-pages accessible
```

#### **3. Database Tables**
```bash
# Verify tables exist and have data:
- at_devices
- at_activity_logs  
- at_screenshots
- at_app_usage_logs
- at_daily_summaries
```

#### **4. Permissions**
```bash
# Check user permissions:
- HR users should see Activity Tracker menu
- Admin users should have full access
- Employee users should have limited access
```

---

## 🚀 DEPLOYMENT SCRIPT

### **Automated Deployment Script:**
```bash
#!/bin/bash
# deploy-activity-tracker.sh

echo "🚀 Deploying Miraix Activity Tracker to Live Server..."

# Variables
LIVE_SERVER="user@miraix.in"
LIVE_PATH="/var/www/miraix.in"
LOCAL_PATH="Modules/activity-tracker/laravel"

# Step 1: Deploy database migrations
echo "📊 Running database migrations..."
ssh $LIVE_SERVER "cd $LIVE_PATH && php artisan migrate --path=database/migrations/2026_05_02_000001_create_at_devices_table.php"
ssh $LIVE_SERVER "cd $LIVE_PATH && php artisan migrate --path=database/migrations/2026_05_02_000002_create_at_activity_logs_table.php"
ssh $LIVE_SERVER "cd $LIVE_PATH && php artisan migrate --path=database/migrations/2026_05_02_000003_create_at_screenshots_table.php"
ssh $LIVE_SERVER "cd $LIVE_PATH && php artisan migrate --path=database/migrations/2026_05_02_000004_create_at_app_usage_logs_table.php"
ssh $LIVE_SERVER "cd $LIVE_PATH && php artisan migrate --path=database/migrations/2026_05_02_000005_create_at_daily_summaries_table.php"

# Step 2: Seed permissions
echo "🔐 Seeding permissions..."
ssh $LIVE_SERVER "cd $LIVE_PATH && php artisan db:seed --class=ActivityTrackerPermissionsSeeder"

# Step 3: Deploy files
echo "📁 Deploying files..."
rsync -avz $LOCAL_PATH/app/Models/At*.php $LIVE_SERVER:$LIVE_PATH/app/Models/
rsync -avz $LOCAL_PATH/app/Http/Controllers/ActivityTracker*.php $LIVE_SERVER:$LIVE_PATH/app/Http/Controllers/
rsync -avz $LOCAL_PATH/resources/views/activity_tracker/ $LIVE_SERVER:$LIVE_PATH/resources/views/activity_tracker/
rsync -avz $LOCAL_PATH/database/seeders/ActivityTrackerPermissionsSeeder.php $LIVE_SERVER:$LIVE_PATH/database/seeders/

# Step 4: Update routes (manual step required)
echo "⚠️  MANUAL STEP: Update routes on live server"
echo "Add activity tracker routes to web.php and api.php"

# Step 5: Clear caches
echo "🧹 Clearing caches..."
ssh $LIVE_SERVER "cd $LIVE_PATH && php artisan cache:clear"
ssh $LIVE_SERVER "cd $LIVE_PATH && php artisan config:clear"
ssh $LIVE_SERVER "cd $LIVE_PATH && php artisan view:clear"
ssh $LIVE_SERVER "cd $LIVE_PATH && php artisan route:clear"

# Step 6: Create storage link
echo "🔗 Creating storage link..."
ssh $LIVE_SERVER "cd $LIVE_PATH && php artisan storage:link"

echo "✅ Deployment completed!"
echo "📋 Next steps:"
echo "1. Manually update routes on live server"
echo "2. Update navigation menu"
echo "3. Test dashboard access"
echo "4. Verify API endpoints"
```

---

## 🎯 EXPECTED RESULT

### **After Successful Deployment:**
- ✅ **API Endpoints**: `https://miraix.in/api/activity-tracker/*` working
- ✅ **Dashboard**: Activity Tracker menu visible in Miraix.in
- ✅ **Data Flow**: Local tracker data appears in live dashboard
- ✅ **Screenshots**: Images display in live timeline
- ✅ **Reports**: Analytics working on live platform

### **URLs to Test:**
- Dashboard: `https://miraix.in/activity-tracker`
- API: `https://miraix.in/api/activity-tracker/dashboard/summary`
- Timeline: `https://miraix.in/activity-tracker/timeline`
- Reports: `https://miraix.in/activity-tracker/daily-report`

---

## 🆘 EMERGENCY SUPPORT

### **If Deployment Fails:**
1. **Check Logs**: `/var/www/miraix.in/storage/logs/laravel.log`
2. **Verify Permissions**: Ensure proper file permissions
3. **Test Database**: Check database connection
4. **Rollback**: Use git to revert changes if needed

### **Contact Support:**
- **Technical**: support@miraix.in
- **Deployment**: dev@miraix.in
- **Emergency**: admin@miraix.in

---

**Status**: 🔴 **NEEDS DEPLOYMENT**  
**Action**: 🚀 **DEPLOY TO LIVE SERVER**  
**Priority**: 🚨 **HIGH**  

The activity tracker is working locally but needs to be deployed to the live Miraix.in server to appear in the dashboard.
