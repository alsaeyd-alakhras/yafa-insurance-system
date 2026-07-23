# المرحلة 5 — بناء الكيانات (كيان كيان، بنمط DataTable)

## الهدف

بناء كل كيان جديد بالترتيب المنطقي حسب الاعتماديات، مع قرار صريح ومُبرَّر لكل كيان: **Popup/Modal** مقابل **صفحة منفصلة**.

## ترتيب البناء

```
Organization Unit → Medical Department → Clinic → Employee (+Dependent متداخل) → User → Visit
```

**تحديث سياسة (بعد اعتماد جدول خصومات رسمي جديد، راجع `docs/reference/01-entities.md` §4/§4ب و`03-business-rules.md` §2/§3/§7):** الأقسام الطبية أصبحت خمسة (كشف طبي 100%، صيدلية 25%، مختبر 25%، بصريات 25%، أسنان 25%) بدل أربعة، بعضها له حد أقصى لمبلغ الخصم (`max_discount_amount`)، وأُضيف كيان **Clinic** جديد كلياً يُختار عند تسجيل زيارة كشف طبي.
(Survey Submission بالمرحلة 6 — سير عمل خاص يمتد بين صفحة عامة ولوحة الأدمن، مش CRUD عادي.)

## مرجع نمط DataTable الحالي

`resources/views/dashboard/aid_distributions/{index,create,edit,_form}.blade.php` + `public/js/datatable.js` — **يبقوا موجودين لحد نهاية هذه المرحلة** فقط كمرجع نسخ (لا تُحذف قبل ما توجد صفحة `employees` أو `visits` تحل محلهم كمرجع فعلي، كما هو محدد بالمرحلة 1).

نمط `datatable.js`: سكربت عام يتوقع متغيرات JS معرَّفة بكل صفحة (`tableId, urlIndex, urlFilters, urlDelete, _token, fields[], columnsTable[], arabicFileJson, sortConfig`) ويتواصل مع endpoint خلفي متوافق مع Yajra DataTables (`draw, recordsTotal, recordsFiltered, data` + `column_filters` JSON param + `sort_column`/`sort_direction`)، بالإضافة لـ endpoint منفصل `GET {resource}-filters/{column}` يرجّع قيم مميزة لتعبئة فلتر checkbox لكل عمود. حزمة `yajra/laravel-datatables-oracle` موجودة أصلاً بـ composer.json — يُستخدم `DataTables::of($query)->make(true)` بدل بناء الـ JSON envelope يدوياً.

## 5.1 Organization Unit

**القرار: صفحة إدارة شجرية مخصصة (tree-manager)، ليس DataTable، والإضافة/التعديل عبر Modal.**

**السبب:** هيكل شجري بـ3 مستويات (مركز/دائرة/قسم) — نمط الجدول المسطّح مع فلاتر DataTable غير مناسب لعرض/إدارة شجرة. عرض شجري متداخل قابل للطي (Blade partial تكراري `_node.blade.php`) أنسب لإدارة الأدمن للهيكل. الإضافة/التعديل عبر Modal (نموذج بسيط: اسم + اختيار الأب) لأن الفورم بسيط جداً (حقلين) — على عكس Employee اللي له تعقيد كافي لتبرير صفحة منفصلة.

**الملفات:**
- `app/Http/Controllers/Dashboard/OrganizationUnitController.php` — `index` يعرض الشجرة (eager load `children.children` أو استعلام متكرر)، `store`/`update`/`destroy` ترجع JSON للـ Modal، مع `ActivityLogService::log('organization_unit_created'|'_updated'|'_deleted', 'OrganizationUnit', ...)`.
- `resources/views/dashboard/organization_units/index.blade.php` + `_node.blade.php` + `_modal.blade.php`.
- مرجع النمط: `ConstantController` (CRUD بسيط بدون DataTable)، `components/confirm-modal.blade.php` لتأكيد الحذف.
- **حماية الحذف:** منع حذف وحدة لها أبناء أو موظفين مرتبطين — الـ FK (`restrictOnDelete`) يحمي على مستوى DB، لكن الـ Controller يفحص مسبقاً ويعرض رسالة عربية واضحة قبل المحاولة.

## 5.2 Medical Department

**القرار: صفحة واحدة بجدول ثابت من 5 صفوف فعّالة (+ صف `radiology` معطّل تاريخياً غير معروض)، تعديل `discount_percentage`/`max_discount_amount`/`is_active` فقط عبر Modal — بدون إنشاء أو حذف.**

**السبب:** الأقسام ثابتة حسب الجدول الرسمي المعتمد (كشف طبي/صيدلية/مختبر/بصريات/أسنان) — لا حالة استخدام لإنشاء أو حذف قسم. بناء DataTable+CRUD كامل لجدول دايماً بعدد صفوف ثابت تعقيد بدون داعٍ. **تحديث:** حقل `max_discount_amount` الجديد (nullable) يُعدَّل من نفس الـ Modal جنباً إلى جنب مع `discount_percentage` — لا قيد شرطي بينهما بالفورم (الحقل مستقل، ممكن معبّى بغض النظر عن قيمة النسبة).

**الملفات:**
- `app/Http/Controllers/Dashboard/MedicalDepartmentController.php` — `index` يعرض الصفوف الفعّالة فقط (`is_active = true`)، `update` فقط (عبر Modal بسيط أو نموذج inline، يشمل `max_discount_amount`).
- `resources/views/dashboard/medical_departments/index.blade.php` — جدول Bootstrap عادي، بدون DataTable JS، عمود إضافي لعرض الحد الأقصى (أو "بدون حد" لو null).
- مرجع النمط: `ConstantController` (أقرب مثال موجود لمجموعة إعدادات ثابتة صغيرة).
- **لا routes لـ store/destroy إطلاقاً** — `Route::resource(..., ['only' => ['index', 'update']])`.

## 5.2ب Clinic (كيان جديد)

**القرار: صفحة DataTable بسيطة + Modal للإنشاء/التعديل/التعطيل — نفس نمط User (5.4) وليس نمط Medical Department (قائمة مفتوحة، ليست صفوف ثابتة).**

**السبب:** العيادات قائمة مفتوحة تُدار من الإدارة (إضافة عيادة جديدة، تعطيل عيادة قديمة) — على عكس الأقسام الطبية الخمسة الثابتة. حقول قليلة (اسم فقط + `is_active`) تبرر Modal بدل صفحة منفصلة.

**الملفات:**
- `app/Http/Controllers/Dashboard/ClinicController.php` — `index` (DataTable أو قائمة بسيطة إن كان العدد صغيراً)، `store`/`update`/`destroy` (`destroy` عملياً تعطيل `is_active=false` وليس حذف فعلي، بما إن `visits.clinic_id` محمي بـ `restrictOnDelete` — لو فيه زيارات مرتبطة، الحذف الفعلي يفشل على مستوى DB، فمن الأفضل تعطيل مباشرة بدل محاولة حذف).
- `resources/views/dashboard/clinics/index.blade.php` + `_modal.blade.php`.
- `ActivityLogService::log('clinic_created'|'clinic_updated', 'Clinic', ...)`.

## 5.3 Employee (+ Dependent متداخل)

**القرار: Employee = صفحة DataTable كاملة + صفحة منفصلة كاملة للإنشاء/التعديل (ليس Modal)، مع إدارة Dependent متداخلة داخل نفس صفحة تعديل الموظف. Dependent ليس كيان DataTable مستقل.**

**السبب:** Employee له حقول كافية (اسم، هوية، جنس، حالة زوجية، وحدة تنظيمية، حالة، مصدر، معتمِد) تبرر فلترة DataTable (الأدمن يفلتر حسب الوحدة التنظيمية/الحالة، الاستقبال يبحث برقم الهوية — سير عمل منفصل بالمرحلة 6). التابعون ما إلهم معنى مستقل خارج موظفهم (لا توجد حالة استخدام "تصفح كل التابعين بالنظام") — UC-A3 يؤطر إضافة/تعديل تابع صراحة كإجراء فرعي ضمن "صفحة موظف قائم"، فصفحة DataTable مستقلة للتابعين بدون نقطة دخول مطلوبة من أي حالة استخدام.

**الملفات:**
- `app/Http/Controllers/Dashboard/EmployeeController.php` — `index` (Yajra، مطابق لنمط aid_distributions)، `getFilterOptions($column)`، `create`، `store`، `edit` (مع eager load لـ `dependents`)، `update`، `destroy`. **[محسوم]** الأدمن (بصلاحية `employees.delete` عبر `RoleUser`) يحذف بحرية كاملة بدون قيد أعمال إضافي (لا فحص "عدد الزيارات" مسبق) — الاستقبال بدون هذه الصلاحية افتراضياً ولا يقدر يحذف إطلاقاً. حماية الـ FK (`restrictOnDelete` على `visits.employee_id`) تبقى كخط دفاع تقني أخير فقط (تمنع فعلياً حذف موظف له زيارات، لكن هذا ناتج تقني من قيد الـ FK وليس قاعدة عمل مقصودة يفحصها الكونترولر مسبقاً) — إذا حصل ورفض الـ FK الحذف، تُعرض رسالة خطأ عربية واضحة بدل ترك استثناء SQL خام يظهر للمستخدم.
- `app/Http/Controllers/Dashboard/DependentController.php` — متداخل تحت الموظف: `store(Employee $employee)`, `update(Employee $employee, Dependent $dependent)`, `destroy(...)` — كلها AJAX، بدون index/create/edit مستقلين. مسارات مثل `Route::post('employees/{employee}/dependents', ...)`. **[محسوم]** نفس منطق `Employee`: الأدمن (بصلاحية `dependents.update`/`dependents.delete`) يعدّل/يحذف تابعين بحرية كاملة، الاستقبال بدون هذه الصلاحيات افتراضياً (عرض فقط).
- Views: `resources/views/dashboard/employees/{index,create,edit,_form}.blade.php` (نسخ نمط aid_distributions حرفياً)، وداخل `edit.blade.php` (أو partial `_dependents.blade.php`) جدول تابعين قابل للتوسيع مع نموذج إضافة inline (AJAX، يحدّث القسم فقط) — اختيار النوع (spouse/child/parent) يتحكم بإظهار حقل `parent_type` عبر JS بسيط (نفس `components/form/select.blade.php`).
- صفحة الإنشاء **بدون** قسم تابعين (التابع يتطلب `employee_id` موجود مسبقاً) — هذا هو التبرير الطبيعي لصفحة منفصلة بدل Modal: لا يمكن إضافة تابعين بمنطق نظيف قبل وجود سجل الموظف نفسه.
- مرجع النمط: `aid_distributions/{index,create,edit,_form}.blade.php`، `components/form/{input,select}.blade.php`.
- **قاعدة عرضية مشتركة:** فحص تفرّد `national_id` (عبر employees+dependents معاً) كـ custom validation rule `app/Rules/UniqueNationalId.php` يُستخدم بكل من `EmployeeController` و `DependentController` (مع استثناء السجل الحالي عند التعديل).

## 5.4 User

**القرار: صفحة DataTable + Modal للإنشاء/التعديل (ليس صفحة منفصلة).**

**السبب:** حقول قليلة (اسم، username، إيميل، كلمة مرور، role، صورة) — مناسب لـ Modal، وبدون تعقيد أبناء متداخلين مثل Employee. هذا أيضاً يصحح `UserController` الحالي (يعيد التوجيه حالياً لنظام `directory` وهمي غير موجود).

**الملفات:**
- إعادة كتابة `app/Http/Controllers/Dashboard/UserController.php` بالكامل: `index()` (DataTable: name, username, email, شارة role, last_activity, إجراءات)، `store`/`update` (تحقق + `role` enum + رفع صورة اختياري). **[مُعدَّل بعد قرار المرحلة 2]** منطق `EMPLOYEE_DEFAULT_ABILITIES`/`normalizeAbilitiesForUserType`/`syncUserAbilities` **لا يُحذف بل يُحدَّث** — نفس الفكرة (تعبئة صلاحيات `RoleUser` افتراضية حسب `role` المختار وقت الإنشاء، مع إمكانية تخصيصها يدوياً بعدها عبر checkboxes) لكن بقائمة صلاحيات الكيانات الجديدة الستة بدل `aiddistributions.*` القديمة (انظر `phase-2-auth-authorization.md` §2.3 للتفصيل الكامل). **حذف** كل إشارة لـ `$user->person`، **حذف** `completePhone`/`storeCompletePhone` (مفاهيم phone/person غير موجودة بهذا المشروع)، تبسيط `settings()`/`updateProfile()` بحذف حقول `phone`/`person`. الإبقاء على `show()` (سجل نشاط لكل مستخدم).
  - `destroy()`: **[محسوم]** فحص صلاحية `users.delete` عبر `RoleUser` القياسي، بالإضافة لقيود صريحة إضافية غير مرتبطة بالصلاحيات (تُفرض داخل الـ method مباشرة، تفصيلها الكامل بـ `phase-2-auth-authorization.md` §2.7): منع حذف المستخدم لنفسه دائماً؛ `super_admin` يحذف أي حساب؛ مستخدم `role=admin` عادي (بدون `super_admin`) لا يقدر يحذف حساب `role=admin` آخر حتى لو يملك صلاحية `users.delete`؛ استقبال بصلاحية `users.delete` الممنوحة له صراحة يقدر يحذف حسابات أخرى (ضمن نفس قيد الأدمن أعلاه).
- Views: `resources/views/dashboard/users/index.blade.php` (DataTable)، `_modal.blade.php` للإنشاء/التعديل، تحديث `show.blade.php` و `settings.blade.php` الموجودين لحذف حقول `person`/`phone` (يجب قراءتهم بالكامل أولاً بوقت التنفيذ — لم يُفحصوا بالتفصيل أثناء الاستكشاف).
- `UserObserver`: الإبقاء على hooks `created`/`deleted`، وتفعيل `updated()` (حالياً no-op فارغ) بنفس نمط `ActivityLogService::log(...)` — **قرار:** الاعتماد على الـ Observer فقط لتسجيل النشاط (وحذف أي استدعاء يدوي مكرر بالـ Controller)، بما يتماشى مع نمط `Constant` الموجود.

## 5.5 Visit (سير العمل المركزي)

**القرار: صفحة DataTable للفهرسة العامة + صفحة مخصصة واحدة تجمع البحث بالهوية + الإنشاء/التعديل + إضافة الأقسام (وليس Modal، وليس صفحات منفصلة للإنشاء/التعديل).**

**السبب:** هذا هو سير العمل الوحيد المعقّد بما يكفي لتبرير رفض الـ Modal بشكل قاطع — يتضمن خطوة بحث، إعادة توجيه عند وجود ازدواجية بنفس اليوم، فحص رصيد مع رسالة رفض، ثم سير فرعي لإضافة أقسام مع حساب خصم لحظي. مطابق لتعقيد `aid_distributions/_form.blade.php` (فورم متعدد الأقسام، غير مناسب لـ Modal).

**الملفات:**
- `app/Http/Controllers/Dashboard/VisitController.php`:
  - `index()` — DataTable لكل الزيارات، فلترة بالتاريخ/اسم المريض/القسم — ظاهر لكلا الدورين.
  - `search(Request $request)` — method جديد (ليس CRUD قياسي): يستقبل `national_id` + `clinic_id` اختياري (يُختار لو الاستقبال ناوي يسجّل كشف طبي)، يحدد إذا المريض موظف أو تابع، يحدد الموظف صاحب الرصيد، يفحص وجود زيارة اليوم لنفس المريض **+ نفس `clinic_id`** (أو بدون عيادة إن لم يُختر `clinic_id`) — يرجّع إما توجيه لصفحة تعديل الزيارة الموجودة، أو حالة الرصيد (X/2) لبدء إنشاء زيارة جديدة. أهم method بالكامل — ينفّذ workflow §2 المحدَّث بالوثيقة حرفياً (مريض+يوم+عيادة، انظر `03-business-rules.md` §2).
  - `store(Request $request)` — ينشئ الزيارة (فقط لو فحص الرصيد نجح)، `clinic_id` من نتيجة البحث (nullable)، `recorded_by = auth()->id()`، تسجيل `visit_created`.
  - `edit(Visit $visit)` — يعرض الزيارة + أقسامها الحالية + نموذج إضافة قسم جديد/تعديل مبلغ قسم موجود.
  - `addDepartment(...)` / `updateDepartmentAmount(...)` — نسخ `applied_discount_percentage` **و `applied_max_discount_amount`** وقت الإضافة فقط (لا يُعاد نسخهم عند تعديل المبلغ لاحقاً)، الاستقبال يُدخل `amount_before_discount` فقط، `amount_after_discount` يُحسب دائماً بالسيرفر عبر `MIN(amount_before_discount × applied_discount_percentage / 100, applied_max_discount_amount ?? INF)` (منطق مقترح كـ method على `VisitDepartment`، مثلاً `calculateAmountAfterDiscount()`)، ثم إعادة حساب وحفظ `visits.total_before_discount`/`total_after_discount` (مجموع كل صفوف `visit_departments`) عبر method قابل لإعادة الاستخدام (`Visit::recalculateTotals()` أو service مخصص) — لا تكرار لهذا المنطق. تحديث `last_updated_by`، تسجيل `visit_updated`.
  - `destroy(Visit $visit)` — **[محسوم، مُعدَّل عن التوصية المبدئية]** حذف مباشر (hard delete)، **ليس** ممنوعاً بالكامل كما كانت التوصية الأولى. الصلاحية تُفحص عبر `VisitPolicy::delete()` (تفصيلها الكامل بـ `phase-2-auth-authorization.md` §2.4): مستخدم بصلاحية `visits.delete` العامة (افتراضياً الأدمن) يحذف أي زيارة؛ مستخدم بصلاحية `visits.delete-own` فقط (افتراضياً الاستقبال) يحذف فقط الزيارات التي `recorded_by = هو`. لا حالة "ملغية" وسيطة — حذف نهائي مباشر. تسجيل `visit_deleted` بـ `activity_logs` (يشمل مين حذف ومتى).
- Views: `resources/views/dashboard/visits/index.blade.php` (DataTable + بطاقة بحث بالهوية مدمجة أعلى الصفحة أو ضمن `extra_nav`، بدل صفحة بحث منفصلة — تقليل عدد الصفحات؛ زر حذف بعمود الإجراءات يظهر فقط عبر `@can('delete', $visit)` — يتقلص تلقائياً للاستقبال ليشمل فقط صفوفه هو بحكم منطق `VisitPolicy::delete()`)، `resources/views/dashboard/visits/edit.blade.php` (معلومات المريض، حالة الرصيد الشهري، قائمة أقسام الزيارة الحالية بحقول مبلغ قابلة للتعديل، نموذج مصغّر لإضافة قسم من `medical_departments` النشطة غير المضافة مسبقاً لهذه الزيارة، وزر حذف الزيارة كاملة بنفس شرط `@can`) — تأكيد الحذف عبر `components/confirm-modal.blade.php` الموجود.
- **لا `create.blade.php` منفصلة** — الإنشاء يحصل عبر AJAX من نتيجة البحث مباشرة لـ `store()`، ثم إعادة توجيه لـ `edit.blade.php` لنفس الزيارة الجديدة — يطابق حرفياً نهاية workflow §2 بالوثيقة ("إعادة التوجيه لصفحة الزيارة القائمة لإضافة/تعديل الأقسام").
- مرجع النمط: `aid_distributions/_form.blade.php` لفورم معقّد متعدد الأقسام لكيان واحد؛ `datatable.js` للفهرسة؛ `components/form/select.blade.php` (بخاصية `searchable`) لاختيار القسم الطبي.

## 5.6 Survey Submission

يُغطى بالمرحلة 6 (سير عمل خاص يمتد بين نموذج عام غير مصادَق عليه ولوحة مراجعة الأدمن — ليس CRUD نمطي).
