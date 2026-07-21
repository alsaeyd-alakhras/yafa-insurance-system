# المرحلة 1 — تنظيف المشروع القديم

## الهدف

حذف كل بقايا المشروع القديم غير المستخدمة (NGO aid-distribution/monitoring/projects system) من الكود، وإصلاح عطل حرج يمنع تشغيل أي صفحة بلوحة التحكم حالياً.

## اكتشاف حرج — يُصلَح أولاً قبل أي شي آخر

`bootstrap/app.php` يسجّل:
```php
$middleware->alias([
    'check.cookie' => CheckUserCookie::class,
    'ensure.phone' => \App\Http\Middleware\EnsurePhoneIsSet::class,
]);
```
لكن `app/Http/Middleware/EnsurePhoneIsSet.php` **غير موجود بالكود إطلاقاً**، وهو مطبّق كـ middleware على مجموعة الـ routes بأكملها بـ `routes/dashboard.php` (`['check.cookie', 'ensure.phone']`). أي طلب لأي صفحة بلوحة التحكم حالياً يفشل بخطأ `Target class [ensure.phone] does not exist`.

## خطوة 1.1 — إصلاح الـ middleware (أولوية قصوى)

- تعديل `bootstrap/app.php`: حذف سطر `'ensure.phone' => ...` من الـ alias.
- تعديل `routes/dashboard.php`: استبدال `['check.cookie', 'ensure.phone']` بـ middleware `auth` القياسي من Laravel (الـ guard `web` مسجَّل أصلاً عبر Fortify، وجهة إعادة التوجيه بعد تسجيل الدخول هي `AppServiceProvider::HOME` = `/`).
- حذف `app/Http/Middleware/CheckUserCookie.php` — يقرأ cookie باسم `user_id` ويعمل `Auth::login()` تلقائياً **بدون فحص كلمة مرور** (ثغرة أمنية موروثة من نمط "تذكرني بدون كلمة مرور" بالمشروع القديم). غير مطلوب لأن مصادقة Fortify بالـ session تكفي. **[محسوم]** تأكيد المستخدم: لا يوجد سبب تشغيلي خفي (لا SSO ولا تكامل خاص)، الحذف آمن بالكامل.
- الإبقاء على `app/Http/Middleware/LogLastUserActivity.php` (يحدّث `users.last_activity`، عام ومفيد) — لكن يجب التأكد أولاً أنه لا يشير لحقول `person`/`user_type`/`phone` قبل إبقائه كما هو (فحص سريع بوقت التنفيذ).

## خطوة 1.2 — حذف الـ routes الميتة من `routes/dashboard.php`

بالترتيب (مع حذف سطر `use` المقابل من الأعلى):
- `reports.*` (index, export, tradersReve, brokersReve, broker-details) + `use ReportController`
- `profile/complete-phone` (GET/PUT) — يعتمد على `Person` model غير موجود بهذا المشروع
- `aid-distributions-filters` + `use AidDistributionController` — **[محسوم]** يُحذف هذا الـ route تحديداً رغم قرار الإبقاء على ملفات `aid_distributions` (انظر خطوة 1.5) لأن `AidDistributionController` **غير موجود أصلاً كملف بالكود** (تم التأكد: لا يوجد `app/Http/Controllers/Dashboard/AidDistributionController.php`) — الـ route معطّل حتماً (`Target class does not exist`) بغض النظر عن قرار الإبقاء على الـ views كمرجع دراسة. الإبقاء على المرجع يقتصر على ملفات Blade + `datatable.js` فقط، وليس route/controller فعلي يعمل.
- `currencies` resource + `use CurrencyController`
- الكتلة كاملة من `// Foundation — organizational hierarchy...` حتى `// Checklist admin` ضمناً:
  - `org-structure.*`, `directory.*`, `departments.by-center`, `sections.by-department`, `sections.for-project`
  - resource group: `centers`, `departments`, `sections`, `people`, `funders`
  - `monitoring-activities.*` (+ 5 routes الـ workflow التابعة لها)
  - `projects.*` (+ 17 route الـ workflow التابعة لها + export-pdf)
  - `checklist-admin.*`
- حذف كل الـ `use` imports المقابلة: `CenterController`, `ChecklistAdminController`, `DepartmentController`, `DirectoryController`, `FunderController`, `MonitoringActivityController`, `OrganizationalStructureController`, `PersonController`, `ProjectController`, `SectionController`, `AidDistributionController`, `CurrencyController`, `ReportController`

**نُبقي على:** `dashboard.home`, `dashboard.home.refresh-cache`, `logs.*`, resource `users` (يُعاد بناؤه بالمرحلة 5)، resource `constants`، `profile/settings` GET/PUT (يُعدَّل بالمرحلة 2/5 لحذف مرجعية `phone`/`person`).

## خطوة 1.3 — حذف الـ Controllers الميتة

- `app/Http/Controllers/Dashboard/ReportController.php`
- `app/Http/Controllers/Dashboard/CurrencyController.php`

**لا تُحذف:** `HomeController.php` و `UserController.php` — يُعاد كتابتهم بالكامل بمراحل لاحقة (أسماء الـ routes الخاصة فيهم تبقى)، مش يُحذفوا الآن.

## خطوة 1.4 — حذف الـ Models/Migrations/Policies/Observers/Exports الميتة

- `app/Models/Currency.php`
- `database/migrations/2024_06_28_233848_create_currencies_table.php` — **[محسوم]** يُحذف الملف مباشرة (تأكيد المستخدم: لا مشكلة، الملفات غير المستخدمة تُحذف بدون قلق بخصوص بيئات سابقة).
- `app/Policies/CurrencyPolicy.php`
- `app/Observers/CurrencyObserver.php`
- إزالة `Currency::observe(CurrencyObserver::class);` من `AppServiceProvider::boot()` + إزالة الـ imports المرتبطة (`Currency`, `CurrencyObserver`)
- `app/Exports/AidDistributionsExport.php`
- **الإبقاء على** `app/Exports/ModelExport.php` — كلاس عام (`FromCollection`/`WithHeadings`) بدون أي ارتباط بالمجال القديم، مفيد لاحقاً لو احتجنا Export (خارج نطاق هذه المرحلة لكن غير مضر إبقاؤه).

## خطوة 1.5 — حذف الـ Views الميتة

- `resources/views/dashboard/pages/report.blade.php`
- `resources/views/dashboard/pages/currencies.blade.php`
- `resources/views/layouts/front-layout.blade.php`
- `resources/views/layouts/partials/aside.blade.php`
- `resources/views/layouts/partials/nav.blade.php`

  (مؤكَّد إنهم ميتين: `app/View/Components/FrontLayout.php` يحوّل دايماً لـ `front-layout-horizantal.blade.php`، مش لهاي النسخة العمودية. القائمة بـ `aside.blade.php` مبنية بالكامل على موديلات/routes من المشروع القديم غير موجودة: `App\Models\Allocation`, `Executive`, `Broker`, `AccreditationProject`.)

**لا تُحذف بعد:** `resources/views/dashboard/aid_distributions/{index,create,edit,_form}.blade.php`

  هاي الملفات هي المرجع الحي الوحيد لنمط الـ DataTable (`datatable.js` + الفلاتر + التصدير) ونمط "صفحة منفصلة للفورم المعقد المتداخل". **[محسوم]** تأكيد المستخدم: هاي الطريقة (الفلاتر + نمط DataTable) مهمة ولازم تُفهَم كويس قبل أي حذف — تبقى **حتى نهاية المرحلة 5 بالكامل** (وليس أول ما توجد صفحة بديلة)، وتُحذف فقط كآخر خطوة صريحة بتلك المرحلة بعد التأكد الكامل من أن كل كيان جديد نسخ النمط المطلوب منها فعلياً.

  **لا تُحذف أيضاً:** `resources/views/layouts/front-layout.blade.php`، `resources/views/layouts/partials/aside.blade.php`، `resources/views/layouts/partials/nav.blade.php` — **[محسوم، تراجع عن القرار الأصلي بالحذف]** رغم إنها فعلاً غير مُستخدَمة حالياً (`FrontLayout::render()` يوجّه دايماً لـ `front-layout-horizantal.blade.php` بغض النظر عن قيمة `dirNav`)، هذول جزء من آلية تبديل اتجاه القائمة (أفقي/عمودي) الموجودة فعلياً عبر كوكي `dirNav` وفرع معلَّق (commented-out) بـ `FrontLayout::render()` — المستخدم يريد الإبقاء على إمكانية التحويل لاحقاً للوضع الجانبي (Vertical Aside)، فالحذف الآن يفقد هذا الخيار بلا داعٍ. **بدل الحذف:** تُنظَّف الروابط الميتة جواتها فقط (نفس منطق `asideH.blade.php`/`navH.blade.php` بالضبط — تُزال أي روابط لـ routes/models قديمة: `org-structure`, `directory`, `centers`, `departments`, `sections`, `people`, `funders`, `monitoring-activities`, `projects`, `checklist-admin`, وأي مرجعية لـ `Allocation`/`Executive`/`Broker`/`AccreditationProject`)، وتُبقى بنية الملف ونقاط الدخول (title, wrapper markup) كما هي دون حذف الملفات نفسها. **ملاحظة تنفيذية:** هذا التنظيف يعتمد على تحديد الروابط النهائية الصحيحة بالمرحلة 4 (نفس محتوى `asideH.blade.php`/`navH.blade.php` المُحدَّثين) — لذلك يُنفَّذ فعلياً بالتوازي مع أو بعد المرحلة 4 مباشرة، وليس بمعزل تام ضمن المرحلة 1؛ يكفي بهذه المرحلة (1) تأكيد عدم الحذف و(2) إزالة أي رابط يشير لـ Controller/Model محذوف فعلاً بهذه المرحلة (fail-fast لتفادي أي احتمال استخدامها بالخطأ قبل التحديث الكامل بالمرحلة 4).

**تدقيق إضافي:** راجع باقي ملفات `resources/views/dashboard/pages/*` (تم تأكيد: `logs.blade.php` و `constants.blade.php` + مجلد `constants/` الفرعي هم عامّون ويُبقوا؛ فقط `report.blade.php` و `currencies.blade.php` من المجال القديم).

## خطوة 1.6 — تنظيف `data/abilities.php`

- حذف مجموعات المجال القديم: `centers`, `departments`, `sections`, `people`, `funders`, `monitoringactivities`, `projects`, أي مفتاح متعلق بـ `checklist_admin`.
- الإبقاء على: `users`, `constants`, `activitylogs`.
- **[محسوم — تصحيح فهم]** آلية `data/abilities.php` + `ModelPolicy::__call` **كانت صحيحة ومتّسقة من البداية** كنمط فحص صلاحيات — الالتباس بالصياغة السابقة (اللي وصفتها كـ"شبه ميتة" أو فيها "مشاكل") كان قصور بالشرح مني وليس عيب فعلي بالتصميم الأصلي. هذا الملف **مستخدَم فعلياً** لعرض قائمة الصلاحيات الجزئية بواجهة إنشاء/تعديل المستخدم، ويبقى هو مصدر الحقيقة لأسماء المجموعات/القدرات المعروضة بواجهة إدارة صلاحيات `RoleUser`. يُضاف له مجموعات الكيانات الجديدة الستة كخطوة بالمرحلة 2 (وليس هذه المرحلة — الحذف هنا يقتصر على إزالة مجموعات المجال القديم فقط)، مع تعديلات بسيطة فقط على بعض المفاتيح (انظر الملاحظة التالية) — لا إعادة تصميم للآلية نفسها.
- **ملاحظة للتحقق (تعديل بسيط، وليس مشكلة بنيوية):** مفتاح `activitylogs` قد لا يطابق حرفياً الـ ability string اللي يشتقه `ModelPolicy::__call` فعلياً لموديل `ActivityLog` (على الأغلب `activity-logs` عبر `Str::kebab(Str::plural(...))`، مش `activitylogs` المُلصَق). يُتحقق منه ويُصحَّح بالمرحلة 2 كتعديل تسمية بسيط فقط — هذه المرة **يؤثر فعلياً** على الصلاحيات (لا يوجد bypass عام للأدمن بعد الآن، فقط `super_admin`)، فالتصحيح مهم وليس تجميلياً فقط، لكنه لا يغيّر آلية الفحص نفسها.

## ترتيب التنفيذ المقترح

1.1 (إصلاح الفشل) → 1.2 (routes) → 1.3 (controllers) → 1.4 (models/migrations/policies/observers/exports) → 1.5 (views) → 1.6 (abilities.php)

هذا الترتيب يمنع أي 500 إضافي أثناء التنظيف نفسه، ويجعل كل خطوة قابلة للاختبار فوراً (تحميل أي صفحة بلوحة التحكم بعد 1.1 لأول مرة).
