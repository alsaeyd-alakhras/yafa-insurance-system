# yafa-insurance-system — TASKS

> **المصدر الرسمي لتتبّع التقدّم على تنفيذ خطة `docs/plans/`.**
>
> - `[x]` = خلصت وتأكّدنا (بما فيها اختبار يدوي إذا كانت الخطوة تحتاج)
> - `[~]` = شغّالة أو جاهزة للمراجعة
> - `[ ]` = لسا
> - `⛔` = مبلوكة (تنتظر مهمة أخرى أو قرار مستخدم)
>
> كل مهمة N.X مطابقة لخطوة موثّقة بملف `phase-N-*.md` المقابل — التفاصيل التقنية الكاملة، الأسباب، والقرارات هناك، مش هون. هذا الملف للتتبّع فقط.
>
> **قاعدة الـ commit:** لا تُرفع أي مرحلة (Phase) لـ git إلا بعد ما تخلص كل مهامها وتنجح خطوة الاختبار اليدوي المذكورة بـ TESTING بأسفل كل مرحلة. انظر `CLAUDE.md` § "سير عمل Git".

---

## الحالة العامة

| Phase | المحتوى | الحالة |
|---|---|---|
| Phase 1 | تنظيف المشروع القديم + إصلاح عطل middleware | `[ ]` |
| Phase 2 | المصادقة والصلاحيات (role + RoleUser + Policies) | `[ ]` |
| Phase 3 | Migrations للكيانات الجديدة | `[ ]` |
| Phase 4 | Layout والـ Navigation | `[ ]` |
| Phase 5 | بناء الكيانات (Organization Unit → Medical Department → Employee/Dependent → User → Visit) | `[ ]` |
| Phase 6 | تدفقات العمل الخاصة (استبيان + تسجيل زيارة + رصيد شهري) | `[ ]` |

---

## Phase 1 — تنظيف المشروع القديم

مرجع: [`phase-1-cleanup.md`](phase-1-cleanup.md)

- [ ] **1.1** إصلاح عطل middleware (أولوية قصوى، يمنع تشغيل أي صفحة حالياً)
  - [ ] 1.1.1 حذف alias `ensure.phone` من `bootstrap/app.php`
  - [ ] 1.1.2 استبدال `['check.cookie', 'ensure.phone']` بـ `auth` القياسي بـ `routes/dashboard.php`
  - [ ] 1.1.3 حذف `app/Http/Middleware/CheckUserCookie.php`
  - [ ] 1.1.4 فحص `LogLastUserActivity.php` (لا إشارة لـ person/user_type/phone)
  - [ ] 1.1.5 **اختبار:** تحميل أي صفحة بلوحة التحكم ينجح لأول مرة (بدون 500)
- [ ] **1.2** حذف الـ routes الميتة من `routes/dashboard.php`
  - [ ] 1.2.1 حذف `reports.*` + import
  - [ ] 1.2.2 حذف `profile/complete-phone` (GET/PUT)
  - [ ] 1.2.3 حذف `aid-distributions-filters` + import
  - [ ] 1.2.4 حذف `currencies` resource + import
  - [ ] 1.2.5 حذف كتلة "Foundation" كاملة (org-structure, directory, centers/departments/sections/people/funders, monitoring-activities.*, projects.*, checklist-admin.*) + كل الـ imports المقابلة
- [ ] **1.3** حذف الـ Controllers الميتة
  - [ ] 1.3.1 حذف `ReportController.php`
  - [ ] 1.3.2 حذف `CurrencyController.php`
- [ ] **1.4** حذف الـ Models/Migrations/Policies/Observers/Exports الميتة
  - [ ] 1.4.1 حذف `Currency.php` model
  - [ ] 1.4.2 حذف migration `create_currencies_table`
  - [ ] 1.4.3 حذف `CurrencyPolicy.php`
  - [ ] 1.4.4 حذف `CurrencyObserver.php` + إزالة تسجيله من `AppServiceProvider`
  - [ ] 1.4.5 حذف `AidDistributionsExport.php` (الإبقاء على `ModelExport.php`)
- [ ] **1.5** حذف الـ Views الميتة
  - [ ] 1.5.1 حذف `pages/report.blade.php`، `pages/currencies.blade.php`
  - [ ] 1.5.2 حذف `layouts/front-layout.blade.php` + `partials/aside.blade.php` + `partials/nav.blade.php`
  - [ ] 1.5.3 **لا تُحذف بعد:** `dashboard/aid_distributions/*` (تُحذف بنهاية Phase 5)
- [ ] **1.6** تنظيف `data/abilities.php` من مجموعات المجال القديم فقط (centers/departments/sections/people/funders/monitoringactivities/projects/checklist_admin) — إضافة الكيانات الجديدة تحصل بـ Phase 2

**TESTING (شرط إغلاق المرحلة):** تسجيل دخول ناجح، تصفح `dashboard.home`/`logs`/`constants`/`users` بدون أي 500 أو خطأ Blade عن route/controller غير موجود.

---

## Phase 2 — المصادقة والصلاحيات

مرجع: [`phase-2-auth-authorization.md`](phase-2-auth-authorization.md)

- [ ] **2.1** Migration + تنظيف موديل `User`
  - [ ] 2.1.1 migration إضافة عمود `role` enum(admin,receptionist) default receptionist
  - [ ] 2.1.2 تنظيف `$fillable`/`casts()` من `phone`/`user_type`/`is_active`، حذف علاقة `person()`
  - [ ] 2.1.3 إضافة `isAdmin()`/`isReceptionist()` helpers
- [ ] **2.2** آلية الصلاحيات (`RoleUser` هو الفحص الفعلي)
  - [ ] 2.2.1 تعديل `Gate::before` بـ `AppServiceProvider` — إبقاء `super_admin` فقط، حذف أي bypass عام لـ `role=admin`
  - [ ] 2.2.2 حذف الـ 3 استدعاءات `Gate::define()` الميتة (admins.super, reports.view, checklist_admin.manage)
  - [ ] 2.2.3 تحديث `data/abilities.php` — إضافة مجموعات الكيانات الست الجديدة (organization_units, medical_departments, employees, dependents, visits [+ delete-own], survey_submissions)
  - [ ] 2.2.4 التحقق من تطابق مفتاح `activitylogs` بالملف مع الـ ability string الفعلي المُشتق (`Str::kebab(Str::plural('ActivityLog'))`) وتصحيحه إذا لزم
- [ ] **2.3** إنشاء الـ Policies الجديدة (فارغة، `extends ModelPolicy`)
  - [ ] 2.3.1 `OrganizationUnitPolicy`
  - [ ] 2.3.2 `MedicalDepartmentPolicy`
  - [ ] 2.3.3 `SurveySubmissionPolicy`
  - [ ] 2.3.4 `EmployeePolicy`
  - [ ] 2.3.5 `DependentPolicy`
- [ ] **2.4** `VisitPolicy` المخصصة (منطق ملكية `delete`/`delete-own`)
- [ ] **2.5** قيود حذف المستخدمين بـ `UserController::destroy()` (منع حذف الذات، منع أدمن عادي من حذف أدمن آخر، `super_admin` يتجاوز الكل)
- [ ] **2.6** فحص `LogLastUserActivity` (مكرر مع 1.1.4، تأكيد نهائي فقط)

**TESTING (شرط إغلاق المرحلة):** إنشاء مستخدم admin ومستخدم receptionist يدوياً (seeder أو tinker)، تسجيل دخول بكل واحد، التأكد من ظهور/اختفاء صحيح لعناصر الصلاحيات (حتى لو النav لسا ما اتحدّث بـ Phase 4 — الفحص عبر `Gate::allows()` بـ tinker كافي بهذه المرحلة).

---

## Phase 3 — Migrations للكيانات الجديدة

مرجع: [`phase-3-migrations.md`](phase-3-migrations.md)

- [ ] **3.1** `create_organization_units_table` + موديل `OrganizationUnit`
- [ ] **3.2** `create_medical_departments_table` + موديل `MedicalDepartment` + seeder (4 صفوف ثابتة)
- [ ] **3.3** `create_employees_table` + موديل `Employee`
- [ ] **3.4** `create_dependents_table` + موديل `Dependent`
- [ ] **3.5** `create_visits_table` (مع unique constraints المزدوجة) + موديل `Visit`
- [ ] **3.6** `create_visit_departments_table` + موديل `VisitDepartment`
- [ ] **3.7** `create_survey_submissions_table` + موديل `SurveySubmission`
- [ ] **3.8** `app/Rules/UniqueNationalId.php` (custom validation rule عبر employees+dependents معاً)

**TESTING (شرط إغلاق المرحلة):** `php artisan migrate:fresh --seed` ينجح بدون أخطاء، فحص العلاقات الأساسية عبر tinker (مثال: `Employee::first()->dependents`, `MedicalDepartment::count() === 4`).

---

## Phase 4 — Layout والـ Navigation

مرجع: [`phase-4-layout-navigation.md`](phase-4-layout-navigation.md)

- [ ] **4.1** تحديث `asideH.blade.php`
  - [ ] 4.1.1 حذف الكتلة المُعلَّقة الضخمة (template demo)
  - [ ] 4.1.2 استبدال الأقسام بأقسام مجال التأمين (الزيارات، الموظفون والتابعون، البيانات الأساسية، طلبات الاستبيان، إدارة المستخدمين، الإعدادات)
- [ ] **4.2** تحديث `navH.blade.php` — حذف الكتل المُعلَّقة الميتة (بحث، مبدّل أنماط، روابط سريعة، إشعارات)
- [ ] **4.3** التحقق من تمرير `title` بكل صفحة جديدة لاحقاً (ليست خطوة كود الآن، ملاحظة للمراحل اللاحقة)

**TESTING (شرط إغلاق المرحلة):** تسجيل دخول admin → القائمة الجانبية تعرض كل الأقسام الجديدة بدون أخطاء Blade (حتى لو الروابط تؤدي لصفحات 404 مؤقتاً لحين Phase 5) — تسجيل دخول receptionist → القائمة تظهر مصغّرة حسب صلاحياته الفعلية.

---

## Phase 5 — بناء الكيانات

مرجع: [`phase-5-entities.md`](phase-5-entities.md)

- [ ] **5.1** Organization Unit (صفحة شجرية + Modal)
  - [ ] 5.1.1 `OrganizationUnitController` (index شجري، store/update/destroy AJAX)
  - [ ] 5.1.2 Views: `index.blade.php` + `_node.blade.php` + `_modal.blade.php`
  - [ ] 5.1.3 حماية حذف وحدة لها أبناء/موظفين (رسالة عربية قبل محاولة FK)
- [ ] **5.2** Medical Department (صفحة ثابتة 4 صفوف)
  - [ ] 5.2.1 `MedicalDepartmentController` (index + update فقط)
  - [ ] 5.2.2 View: `index.blade.php` (جدول عادي + Modal تعديل)
- [ ] **5.3** Employee + Dependent
  - [ ] 5.3.1 `EmployeeController` كامل (index Yajra, getFilterOptions, create, store, edit, update, destroy)
  - [ ] 5.3.2 `DependentController` متداخل (store/update/destroy تحت employee، AJAX)
  - [ ] 5.3.3 Views: `employees/{index,create,edit,_form}.blade.php` + قسم تابعين inline بصفحة التعديل
  - [ ] 5.3.4 ربط `UniqueNationalId` rule بكل من Employee و Dependent
- [ ] **5.4** User (إعادة كتابة كاملة)
  - [ ] 5.4.1 إعادة كتابة `UserController` (index DataTable، store/update بصلاحيات RoleUser افتراضية حسب role، حذف مفاهيم person/phone)
  - [ ] 5.4.2 `destroy()` بقيود حذف المستخدمين (راجع 2.5)
  - [ ] 5.4.3 Views: `users/index.blade.php` + `_modal.blade.php`، تحديث `show.blade.php`/`settings.blade.php`
  - [ ] 5.4.4 تفعيل `UserObserver::updated()`، حذف تسجيل يدوي مكرر بالكونترولر
- [ ] **5.5** Visit (سير العمل المركزي)
  - [ ] 5.5.1 `VisitController::index()` (DataTable + بطاقة بحث بالهوية)
  - [ ] 5.5.2 `VisitController::search()` (تحديد المريض، فحص ازدواجية، فحص رصيد)
  - [ ] 5.5.3 `VisitController::store()` (إنشاء + تسجيل نشاط)
  - [ ] 5.5.4 `VisitController::edit()` + `addDepartment()`/`updateDepartmentAmount()` (snapshot خصم + إعادة حساب مجاميع عبر `Visit::recalculateTotals()`)
  - [ ] 5.5.5 `VisitController::destroy()` (منطق ملكية عبر `VisitPolicy`)
  - [ ] 5.5.6 Views: `visits/index.blade.php` + `visits/edit.blade.php`
- [ ] **5.6** حذف `dashboard/aid_distributions/*` نهائياً (بعد ما `employees`/`visits` صاروا مرجع بديل فعلي)

**TESTING (شرط إغلاق المرحلة):** اختبار يدوي كامل بالمتصفح لكل كيان: إنشاء/تعديل/حذف (حسب الصلاحية)، فلاتر DataTable تشتغل، رسائل الأخطاء العربية تظهر صح.

---

## Phase 6 — تدفقات العمل الخاصة

مرجع: [`phase-6-workflows.md`](phase-6-workflows.md)

- [ ] **6.1** إعداد نافذة الاستبيان الزمنية (مدخلات `constants` جديدة + `SurveyWindowService::isOpen()`)
- [ ] **6.2** نموذج الاستبيان العام
  - [ ] 6.2.1 `SurveySubmissionPublicController` + routes بـ `routes/web.php`
  - [ ] 6.2.2 `layouts/public-layout.blade.php` + `survey/form.blade.php`
  - [ ] 6.2.3 فحص تفرّد الهوية اللحظي (AJAX) + عند التقديم
- [ ] **6.3** لوحة مراجعة الأدمن للاستبيانات
  - [ ] 6.3.1 `SurveySubmissionController` (index, show, approve, reject)
  - [ ] 6.3.2 `approve()` — إنشاء Employee+Dependents من raw_data داخل transaction + إعادة فحص تفرّد الهوية
  - [ ] 6.3.3 Views: `survey_submissions/{index,show}.blade.php`
- [ ] **6.4** حساب الرصيد الشهري — `Employee::visitsThisMonth()`/`remainingQuota()`
- [ ] **6.5** ربط نهائي: تدفق تسجيل الزيارة الكامل من البحث للحفظ (اختبار end-to-end)

**TESTING (شرط إغلاق المرحلة):** تعبئة استبيان كامل (موظف + تابعين) من نافذة متصفح خاصة (بدون تسجيل دخول) → موافقة الأدمن → التأكد من ظهور الموظف الجديد بصفحة employees. تسجيل زيارة كاملة: بحث → إنشاء → إضافة قسمين → محاولة تكرار بنفس اليوم (يجب التوجيه للزيارة الموجودة) → استهلاك الزيارتين واختبار رفض الزيارة الثالثة بنفس الشهر.

---

## بعد كل Phase

راجع قسم "سير عمل Git" بـ `CLAUDE.md` قبل أي `git add`/`commit` — الرفع يكون بعد نجاح TESTING فقط، ليس بعد كل مهمة فرعية.
