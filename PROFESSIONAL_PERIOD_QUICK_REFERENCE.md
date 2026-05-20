# Professional Period - Quick Reference Guide

## فوری حوالہ (Quick Reference)

### **اہم فائلیں (Key Files):**

| فائل | مقصد |
|------|------|
| [database/migrations/2026_02_16_120001_add_professional_period_to_leaves_table.php](database/migrations/2026_02_16_120001_add_professional_period_to_leaves_table.php) | Leaves میں professional period کالمز |
| [database/migrations/2026_02_16_120002_add_professional_period_to_attendance_employees_table.php](database/migrations/2026_02_16_120002_add_professional_period_to_attendance_employees_table.php) | Attendance میں professional period کالمز |
| [app/Models/Leave.php](app/Models/Leave.php) | Leave Model - نئے methods |
| [app/Models/AttendanceEmployee.php](app/Models/AttendanceEmployee.php) | Attendance Model - نئے methods |
| [app/Http/Controllers/LeaveController.php](app/Http/Controllers/LeaveController.php) | Leave میں calculation |
| [app/Http/Controllers/AttendanceEmployeeController.php](app/Http/Controllers/AttendanceEmployeeController.php) | Attendance میں calculation |

---

## نئے Database Columns

### Leaves Table میں:
```sql
professional_years      INT         -- سال میں مدت
professional_months     INT         -- مہینے میں مدت
professional_days       INT         -- کل دن میں مدت
calculated_at          TIMESTAMP    -- کب calculate کیا گیا
```

### Attendance_employees Table میں:
```sql
professional_years_at_attendance       INT         -- حاضری کے دن سال
professional_months_at_attendance      INT         -- حاضری کے دن مہینے
professional_days_at_attendance        INT         -- حاضری کے دن کل دن
in_probation_at_attendance            BOOLEAN      -- probation status
```

---

## نئے Methods

### Leave Model:
```php
// منظم صورت میں professional period دکھانا
$leave->getProfessionalPeriodDisplay();
// Output: "1 year 3 months" یا "5 months" یا "15 days"

// فیلڈز براہ راست:
$leave->professional_years      // سال
$leave->professional_months     // مہینے
$leave->professional_days       // کل دن
```

### AttendanceEmployee Model:
```php
// منظم صورت میں مدت دکھانا
$attendance->getProfessionalPeriodDisplay();

// Status دیکھنا (Probation یا Active)
$attendance->getProfessionalPeriodStatus();

// فیلڈز براہ راست:
$attendance->professional_years_at_attendance
$attendance->professional_months_at_attendance
$attendance->professional_days_at_attendance
$attendance->in_probation_at_attendance
```

### LeaveController:
```php
// ملازم کی موجودہ مدت نکالنا
protected function calculateProfessionalPeriod(?Employee $employee): array
{
    // Returns:
    // ['professional_years', 'professional_months', 'professional_days']
}
```

### AttendanceEmployeeController:
```php
// کسی خاص تاریخ پر مدت نکالنا
protected function calculateProfessionalPeriodAtDate(?Employee $employee, $date = null): array
{
    // Returns:
    // ['professional_years', 'professional_months', 'professional_days', 'in_probation']
}
```

---

## استعمال کی مثالیں (Examples)

### Leave میں مدت دکھانا:
```blade
@foreach($leaves as $leave)
    <tr>
        <td>{{ $leave->employees->name }}</td>
        <td>{{ $leave->start_date }} - {{ $leave->end_date }}</td>
        <td>{{ $leave->getProfessionalPeriodDisplay() }}</td>
        <td>
            @if($leave->professional_years > 0 || $leave->professional_months >= 6)
                <span class="badge badge-success">سینیر ملازم</span>
            @else
                <span class="badge badge-warning">نیا ملازم</span>
            @endif
        </td>
    </tr>
@endforeach
```

### Attendance میں مدت دکھانا:
```blade
@foreach($attendances as $attendance)
    <tr>
        <td>{{ $attendance->employee->name }}</td>
        <td>{{ $attendance->date }}</td>
        <td>{{ $attendance->clock_in }} - {{ $attendance->clock_out }}</td>
        <td>{{ $attendance->getProfessionalPeriodDisplay() }}</td>
        <td>
            <span class="badge @if($attendance->in_probation_at_attendance) badge-danger @else badge-success @endif">
                {{ $attendance->getProfessionalPeriodStatus() }}
            </span>
        </td>
    </tr>
@endforeach
```

### Leave Approval میں:
```php
// Manager leave approve کرتے وقت مدت دیکھ سکتا ہے
$leave = Leave::find($id);

if ($leave->professional_years < 1) {
    // Probation میں ملازم
    // خاص rules apply کریں
}

if ($leave->professional_years >= 5) {
    // سینیر ملازم - زیادہ رعایت
}
```

### Report میں:
```php
$leaves = Leave::whereYear('created_at', now()->year)
    ->get()
    ->groupBy(function ($leave) {
        if ($leave->professional_years >= 5) return 'سینیر';
        if ($leave->professional_years >= 3) return 'میڈیم';
        return 'جونیئر';
    });
```

---

## Migration چلانا

```bash
# تمام migrations چلانا
php artisan migrate

# صرف یہ migrations چلانا:
php artisan migrate --path=database/migrations/2026_02_16_120001_add_professional_period_to_leaves_table.php
php artisan migrate --path=database/migrations/2026_02_16_120002_add_professional_period_to_attendance_employees_table.php

# Rollback کرنا:
php artisan migrate:rollback
```

---

## Configuration

### Settings میں:
```php
// config/database میں:
'probation_months' => 6,  // آزمائشی مدت مہینوں میں

// Settings میں:
$settings['probation_leave_policy'] => 'absence' | 'lop'
$settings['probation_leave_accumulation'] => 'during' | 'after'
```

---

## فوائل (Benefits)

✅ **Automatic Tracking** - ہر استعمال میں خودکار طور پر مدت محفوظ

✅ **Probation Management** - نئے ملازمین کے لیے special rules

✅ **Report Generation** - مدت کی بنیاد پر رپورٹس

✅ **Compliance** - تمام records میں مدت کا ریکارڈ

✅ **Performance** - ایک دفعہ calculate، ہمیشہ storage میں

---

## Troubleshooting

### اگر professional period 0 آ رہی ہو:
```php
// Employee کو check کریں
$employee = Employee::find($id);
if (empty($employee->company_doj)) {
    // DOJ field خالی ہے - fill کریں
    $employee->company_doj = '2020-01-15';
    $employee->save();
}
```

### Migration Error آ رہی ہو:
```bash
# پہلے check کریں کہ کالمز موجود ہیں یا نہیں:
php artisan tinker
>>> Schema::hasColumn('leaves', 'professional_years')

# Existing columns دیکھیں:
>>> DB::select("DESC leaves")
```

---

## مستقبل کی بہتریوں (Future Enhancements)

- [ ] Bulk update professional period via artisan command
- [ ] Professional period based leave policies
- [ ] Senior employee benefits automation
- [ ] Professional period reports dashboard
- [ ] Export reports with professional period

---

**Version:** 1.0  
**Last Updated:** 16 فروری 2026

