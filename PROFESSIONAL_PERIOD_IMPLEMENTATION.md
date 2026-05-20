# Professional Period Calculation - Leave & Attendance System

## عام تجزیہ (Overall Summary)

یہ دستاویز HRM سافٹویئر میں **پروفیشنل پیریڈ کیلکولیشن** (ملازم کی خدمت کی مدت) کو **استعمال کی سسٹم** میں شامل کرنے کی تمام تبدیلیاں بیان کرتا ہے۔

---

## نئے فیچرز (New Features)

### 1. **Leave میں Professional Period Tracking**
استعمال درخواست کے وقت ملازم کی خدمت کی مدت محفوظ ہوتی ہے:
- سال (Years)
- مہینے (Months)  
- کل دن (Total Days)
- نرخ کا وقت (Calculated Timestamp)

### 2. **Attendance میں Professional Period Tracking**
روزانہ کی حاضری ریکارڈ میں ملازم کی خدمت کی معلومات:
- سال، مہینے، کل اور کل دن (Years, Months, Total Days at attendance time)
- کیا ملازم آزمائشی مدت میں تھا (In probation status)

### 3. **خودکار Probation ٹریکنگ**
- جب ملازم آزمائشی مدت میں ہو تو یہ خودکار طور پر ریکارڈ ہوتا ہے
- استعمال اور حاضری دونوں میں probation status محفوظ رہتی ہے

---

## ڈیٹا بیس تبدیلیاں (Database Changes)

### Migration 1: `leaves` ٹیبل میں نئے کالمز
**فائل:** `database/migrations/2026_02_16_120001_add_professional_period_to_leaves_table.php`

```
ٹیبل: leaves
نئے کالمز:
- professional_years (INT): سال میں خدمت کی مدت
- professional_months (INT): مہینوں میں اضافی مدت
- professional_days (INT): کل دن میں بدلی ہوئی مدت
- calculated_at (TIMESTAMP): کب calculate کیا گیا
```

### Migration 2: `attendance_employees` ٹیبل میں نئے کالمز
**فائل:** `database/migrations/2026_02_16_120002_add_professional_period_to_attendance_employees_table.php`

```
ٹیبل: attendance_employees
نئے کالمز:
- professional_years_at_attendance (INT): حاضری کے وقت سال
- professional_months_at_attendance (INT): حاضری کے وقت مہینے
- professional_days_at_attendance (INT): حاضری کے وقت کل دن
- in_probation_at_attendance (BOOLEAN): کیا probation میں تھا
```

---

## Model اور Controller میں تبدیلیاں

### 1. Leave Model (`app/Models/Leave.php`)
**تبدیلیاں:**
- `fillable` array میں 4 نئے فیلڈز شامل
- `getProfessionalPeriodDisplay()` method شامل - منظم صورت میں professional period دکھاتا ہے

**نیا Method:**
```php
public function getProfessionalPeriodDisplay()
{
    // صورت: "2 years 3 months" یا "5 months" یا "15 days"
}
```

### 2. AttendanceEmployee Model (`app/Models/AttendanceEmployee.php`)
**تبدیلیاں:**
- `fillable` array میں 4 نئے فیلڈز شامل
- `getProfessionalPeriodDisplay()` method شامل
- `getProfessionalPeriodStatus()` method شامل - "In Probation" یا "Active"

### 3. LeaveController (`app/Http/Controllers/LeaveController.php`)
**نیا Method:**
```php
protected function calculateProfessionalPeriod(?Employee $employee): array
{
    // ملازم کی تاریخ شامل سے موجودہ تاریخ تک کی مدت calculate کتا ہے
    // واپسی: ['professional_years', 'professional_months', 'professional_days']
}
```

**Store Method میں تبدیلیاں:**
- استعمال بناتے وقت professional period calculate ہوتی ہے
- 4 نئے فیلڈز میں ڈیٹا محفوظ ہوتا ہے

**متعلقہ فیچرز:**
- پہلے سے موجود `isEmployeeInProbation()` method استعمال ہو رہی ہے
- `getLeaveAllowance()` probation status کو measure کرتی ہے

### 4. AttendanceEmployeeController (`app/Http/Controllers/AttendanceEmployeeController.php`)
**نیا Method:**
```php
protected function calculateProfessionalPeriodAtDate(?Employee $employee, $date = null): array
{
    // کسی خاص تاریخ پر ملازم کی مدت کا حساب لگاتا ہے
    // واپسی: ['professional_years', 'professional_months', 'professional_days', 'in_probation']
}
```

**Update کیے گئے Methods:**
1. **store()** - نئے attendance record میں professional period شامل
2. **update()** - attendance update کرتے وقت professional period update
3. **attendance()** - Employee self clock-in میں professional period شامل
4. **bulkAttendance()** - Bulk clock-in میں professional period

---

## کام کا بہاؤ (Workflow)

### استعمال Apply کرتے وقت:
1. Employee استعمال فارم میں تفصیلات بھرتا ہے
2. LeaveController `store()` method کال ہوتی ہے
3. `calculateProfessionalPeriod()` ملازم کی شامل ہونے کی تاریخ سے موجودہ مدت نکالتا ہے
4. یہ معلومات Leave record میں محفوظ ہوتی ہے
5. Manager کو professional period دیکھ سکتا ہے جب استعمال منظور کر رہا ہو

### حاضری ریکارڈ کرتے وقت:
1. Employee یا HR حاضری ریکارڈ کریں
2. AttendanceEmployeeController `store()` یا `attendance()` method کال ہوتی ہے
3. `calculateProfessionalPeriodAtDate()` اس تاریخ پر مدت calculate کرتا ہے
4. معلومات attendance record میں save ہوتی ہے
5. اگر employee probation میں ہو تو یہ بھی mark ہوتا ہے

---

## نتیجہ اور فوائل (Results & Benefits)

### ✓ Professional Period Tracking
- ہر استعمال اور حاضری میں خدمت کی مدت محفوظ رہتی ہے
- تاریخ کے ساتھ ملازم کی حالت معلوم ہو سکتی ہے

### ✓ Probation Management
- خودکار probation detection
- استعمال میں probation rules apply ہو سکتے ہیں
- حاضری میں probation status track ہوتی ہے

### ✓ Reporting & Analysis
- Professional period based reports بنائے جا سکتے ہیں
- ملازمین کی مدت کی بنیاد پر تجزیہ

### ✓ Compliance
- تمام استعمال اور حاضری میں متعلقہ professional period ہے
- audit trail محفوظ رہتا ہے

---

## استعمال کی ہدایتیں (Usage Instructions)

### Migrations چلانا:
```bash
php artisan migrate
```

### Data Display میں:
```php
// Leave میں professional period دکھانا
$leave->getProfessionalPeriodDisplay(); // Output: "1 year 3 months"

// Attendance میں professional period دکھانا
$attendance->getProfessionalPeriodDisplay();
$attendance->getProfessionalPeriodStatus(); // Output: "In Probation" یا "Active"
```

---

## تکنیکی تفصیلات (Technical Details)

### Calculation Logic:
- **DOJ (Date of Joining):** Employee model میں `company_doj` field
- **Probation Months:** Settings میں `probation_months` سے آتے ہیں
- **Current Status:** `Carbon::now()` سے موجودہ وقت
- **Formula:** 
  ```
  Years = تاریخ فرق / 365 دن
  Months = باقی دن / 30 دن  
  Days = کل دن
  ```

### Performance:
- ہر leave/attendance میں ایک دفعہ calculate ہوتا ہے
- Database میں محفوظ رہتا ہے (دوبارہ calculate نہیں)
- کوئی additional query نہیں

### Error Handling:
- اگر `company_doj` نہ ہو تو صفر (0) values set ہوتی ہیں
- Null/empty employee کو handle کیا جاتا ہے

---

## UI/Views میں Integration (اختیاری)

Views میں یہ fields دکھانے کے لیے:

```php
// Leave Details میں:
<div class="professional-period">
    <strong>Professional Period:</strong> 
    {{ $leave->getProfessionalPeriodDisplay() }}
</div>

// Attendance Details میں:
<div class="professional-period">
    <strong>Service Period:</strong> 
    {{ $attendance->getProfessionalPeriodDisplay() }}
    <span class="status">{{ $attendance->getProfessionalPeriodStatus() }}</span>
</div>
```

---

## خلاصہ (Summary)

✅ **Professional Period** اب مکمل طور پر Leave اور Attendance systems میں integrate ہو چکا ہے۔

✅ ہر leave application میں **employment duration** محفوظ رہتی ہے۔

✅ ہر attendance record میں **service status at time** محفوظ رہتی ہے۔

✅ **Probation management** خودکار اور accurate ہے۔

✅ تمام calculations **secure اور efficient** ہیں۔

---

**آخری تبدیلی:** 16 فروری 2026  
**Version:** 1.0
