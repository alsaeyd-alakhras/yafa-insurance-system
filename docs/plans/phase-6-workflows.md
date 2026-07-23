# المرحلة 6 — تدفقات العمل الخاصة

## الهدف

بناء سيرين معقدين لا يتبعان نمط CRUD القياسي: الاستبيان العام (نموذج غير مصادَق عليه + مراجعة أدمن)، وتدفق تسجيل الزيارة الكامل من البحث لحين الحفظ النهائي، بالإضافة لحساب الرصيد الشهري القابل لإعادة الاستخدام.

## 6.1 إعداد نافذة الاستبيان الزمنية

بدل جدول إعدادات جديد، **إعادة استخدام جدول `constants` الموجود** — إضافة مدخلين جديدين لملف السجل اللي يقرأه `ConstantController` (مثلاً `survey_window_start` و `survey_window_end`)، قابلين للتعديل من صفحة `dashboard.constants.index` الموجودة أصلاً (بدون controller جديد).

Helper بسيط (`app/Services/SurveyWindowService.php` أو method على `Constant`): `isOpen(): bool` يفحص `now()->between($start, $end)` — يُستخدم من controller النموذج العام وبشكل اختياري لشارة عدد بالـ nav.

هذا يحقق متطلب "يُتحكم به عبر إعداد (تاريخ بداية/نهاية) وليس تعطيلاً يدوياً" بدون بنية تحتية جديدة.

## 6.2 نموذج الاستبيان العام (بدون مصادقة)

- مجموعة routes جديدة بـ `routes/web.php` (**ليس** `routes/dashboard.php`، لأنه عام وخارج middleware المصادقة بالكامل): `Route::prefix('survey')->name('survey.')->group(...)`:
  - `GET /survey` — عرض النموذج، أو رسالة "انتهت فترة استقبال البيانات" إذا النافذة مغلقة.
  - `POST /survey` — تحقق + فحص تفرّد الهوية عبر 3 مصادر + إنشاء صف `survey_submissions` بحالة `pending`.
  - `GET /survey/check-national-id/{id}` — endpoint AJAX خفيف لفحص التكرار لحظياً أثناء الكتابة (مطابق لـ UC-E1).
- Controller جديد: `app/Http/Controllers/SurveySubmissionPublicController.php` (خارج namespace `Dashboard` — واجهة عامة، ليس لوحة تحكم).
- View جديد: `resources/views/survey/form.blade.php` — **لا يستخدم** `<x-front-layout>` (يفترض مستخدم مصادَق عليه بقائمة جانبية) — يحتاج layout عام مخصص (`resources/views/layouts/public-layout.blade.php`، يعيد استخدام نفس أصول head/RTL/Almarai/Bootstrap بدون sidebar/navbar). حقول النموذج: قسم الموظف (اسم، هوية، جنس، حالة زوجية، اختيار وحدة تنظيمية) + قسم تابعين متكرر (إضافة/حذف صفوف عبر JS، كل صف نوع/اسم/هوية/جنس + `parent_type` شرطي) — نفس روح واجهة التابعين المتداخلة بصفحة تعديل الموظف (5.3)، لكن مستقلة تماماً (بدون سياق مصادقة أدمن).
- `raw_data` يُخزَّن كامل الـ payload بالضبط كما قُدِّم (موظف + مصفوفة تابعين) — هذا ما يُعاد تشغيله لإنشاء صفوف `employees`+`dependents` الحقيقية عند الموافقة.

## 6.3 لوحة مراجعة الأدمن للاستبيانات

- `app/Http/Controllers/Dashboard/SurveySubmissionController.php` (بـ namespace/routes الداشبورد العادية):
  - `index()` — قائمة (DataTable أو قائمة مفلترة بسيطة)، الافتراضي `status=pending`، مع فلتر لعرض السجل التاريخي (approved/rejected).
  - `show(SurveySubmission $submission)` — يعرض `raw_data` بشكل مقروء (بيانات الموظف + جدول تابعين) للمراجعة.
  - `approve(SurveySubmission $submission)` — داخل `DB::transaction()`: إنشاء صف `Employee` (`source='survey'`, `status='active'`, `approved_by=auth()->id()`, `approved_at=now()`)، إنشاء كل صف `Dependent` من `raw_data`، تحديث `survey_submissions.created_employee_id`/`status='approved'`/`reviewed_by`/`reviewed_at`، تسجيل `survey_submission_approved`.
  - `reject(SurveySubmission $submission)` — **[محسوم]** `status='rejected'`, `reviewed_by`, `reviewed_at` فقط — بدون أي حقل/إدخال لسبب الرفض (قرار صريح: لا داعي لحقل سبب، تبسيطاً). تسجيل `survey_submission_rejected` بـ `activity_logs` (بدون تفصيل سبب إضافي).
  - **إعادة فحص تفرّد الهوية عند الموافقة أيضاً** (وليس فقط وقت التقديم) — كحماية إضافية لو انقضى وقت وحصل تعارض بالأثناء (بدل الفشل الفجائي بقيد DB unique، رفض الموافقة برسالة واضحة).
- Views: `resources/views/dashboard/survey_submissions/{index,show}.blade.php` — أزرار موافقة/رفض عبر `components/confirm-modal.blade.php`.

## 6.4 حساب الرصيد الشهري (قابل لإعادة الاستخدام)

يُبنى كـ method على موديل `Employee` (وليس فقط query scope، لأنه يحتاج تجميع زيارات الموظف نفسه + كل تابعيه معاً):

```php
public function visitsThisMonth(): int
{
    return Visit::where('employee_id', $this->id)
        ->whereYear('visit_date', now()->year)
        ->whereMonth('visit_date', now()->month)
        ->count();
}

public function remainingQuota(): int
{
    return max(0, 2 - $this->visitsThisMonth());
}
```

**ملاحظة مهمة للتنفيذ:** عمود `visits.employee_id` هو دائماً "الموظف صاحب الرصيد" (سواء كان المريض هو الموظف نفسه أو أحد تابعيه) — لذلك استعلام COUNT بعمود واحد فقط (`employee_id`) كافٍ تماماً ويعكس صح "الموظف + كل تابعيه معاً"، **بدون** حاجة لـ UNION بين `patient_employee_id`/`patient_dependent_id`. هذه نقطة سهل الوقوع بخطأ تنفيذها لو اعتمدنا على العمودين الخطأ — توثيقها هنا صريح لتفادي ذلك.

يُستخدم من: `VisitController::search()` (فحص الرصيد قبل السماح بالإنشاء)، وعرض رصيد الموظف (UC-R1/UC-R4 "عرض رصيد موظف").

## 6.5 تدفق تسجيل الزيارة — الربط الكامل من البداية للنهاية

**محدّثة بعد اعتماد جدول الخصومات الجديد** — الشمول أصبح (مريض + يوم + عيادة) بدل (مريض + يوم)، راجع `03-business-rules.md` §2.

يربط 5.5 + 6.4 معاً:

```
بحث برقم الهوية (+ اختيار عيادة، اختياري، لو الزيارة فيها كشف طبي)
    → تحديد المريض (موظف أم تابع؟)
    → تحديد الموظف صاحب الرصيد
    → فحص الازدواجية (اليوم، نفس عمود المريض بالضبط، ونفس clinic_id
      — أو عدم وجود عيادة على الإطلاق لو لم تُختر عيادة)
        → إذا موجودة: إعادة توجيه لصفحة تعديل الزيارة الموجودة
        → إذا غير موجودة: فحص الرصيد عبر remainingQuota()
            → إذا 0: رفض برسالة عربية واضحة
            → إذا متبقي: إنشاء الزيارة (employee_id=صاحب الرصيد،
              patient_employee_id أو patient_dependent_id حسب نوع المريض،
              clinic_id=العيادة المختارة أو null، visit_date=اليوم،
              recorded_by=المستخدم الحالي)
              → إعادة توجيه لصفحة تعديل الزيارة
              → إضافة قسم/أقسام طبية: الاستقبال يُدخل المبلغ الأساسي فقط
                (amount_before_discount)، النظام يحسب النسبة/الحد الأقصى/
                المبلغ بعد الخصم تلقائياً (نسخ applied_discount_percentage +
                applied_max_discount_amount كـ snapshot)
              → إعادة حساب المجاميع
              → تسجيل activity_log بكل خطوة إنشاء/تعديل
```

**تنبيه:** نفس المريض بنفس اليوم بعيادتين مختلفتين (مثال: كشف باطنة ثم لاحقاً بنفس اليوم كشف عظام) = زيارتان منفصلتان بالكامل تمران بنفس التدفق أعلاه مرتين، كل واحدة تُستهلك من `remainingQuota()` بشكل مستقل.

## ملاحظات عامة لكل هذه المرحلة

- كل عملية كتابة بقاعدة البيانات داخل `DB::transaction()`.
- كل عملية تعديل تستدعي `ActivityLogService::log($eventType, $modelName, $رسالة_عربية, $oldData, $newData)`. قيم `event_type` موحّدة: `employee_created`, `employee_updated`, `dependent_created`, `dependent_updated`, `dependent_deleted`, `visit_created`, `visit_updated`, `organization_unit_created/updated/deleted`, `medical_department_updated`, `survey_submission_approved/rejected`, `user_created/updated/deleted` (موجود مسبقاً).
- نصوص الـ Arabic labels النهائية بالـ nav/الأزرار/العناوين تُحسم أثناء التنفيذ الفعلي بما يتماشى مع الأسلوب الحالي (عرض/اضافة/تعديل/حذف) — **[انظر open-questions.md]** بخصوص مصطلحات تنظيمية محددة.
