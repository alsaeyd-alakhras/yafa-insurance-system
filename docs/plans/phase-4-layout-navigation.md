# المرحلة 4 — تعديل الـ Layout والـ Navigation

## الهدف

تحديث الـ sidebar (`asideH.blade.php`) والـ navbar (`navH.blade.php`) لتعكس كيانات المشروع الجديد بدل المشروع القديم، مع حذف الأكواد الميتة المُعلَّقة (template demo boilerplate).

الـ Layout الأساسي (`front-layout-horizantal.blade.php` + المكوّن `FrontLayout.php`) **يبقى بدون تغيير بنيوي** — تم تأكيده كالنسخة الوحيدة الفعّالة بالمرحلة 1.

## خطوة 4.1 — `resources/views/layouts/partials/asideH.blade.php`

**حذف:** الكتلة المُعلَّقة الضخمة (~السطر 130 حتى 755) — قوائم template demo (eCommerce, Academy, Logistics, Invoice, Kanban, Auth pages...) بالكامل، لا علاقة لها بأي مشروع.

**استبدال** أقسام الـ nav الحالية (الرقابة والمتابعة / البيانات الأساسية / الإعدادات المبنية على المشروع القديم) بأقسام مجال التأمين:

| القسم | الرابط | الشرط | ظاهر لـ |
|---|---|---|---|
| الرئيسية | `dashboard.home` | بدون شرط | الكل |
| الزيارات | `dashboard.visits.index` | `@can('view', 'App\Models\Visit')` | admin + receptionist |
| الموظفون والتابعون | `dashboard.employees.index` | `@can('view', 'App\Models\Employee')` | admin + receptionist (الاستقبال يشوف عرض فقط) |
| البيانات الأساسية | `dashboard.organization-units.index`, `dashboard.medical-departments.index` | `@if(can('view', OrganizationUnit::class) \|\| can('view', MedicalDepartment::class))` كقسم أب، وكل رابط فرعي بـ `@can` خاص فيه | admin فقط |
| طلبات الاستبيان | `dashboard.survey-submissions.index` | `@can('view', 'App\Models\SurveySubmission')` | admin فقط |
| إدارة المستخدمين | `dashboard.users.index` | `@can('view', 'App\Models\User')` | admin فقط |
| الإعدادات | `constants`, `logs` (كما هي) | نفس الشروط الحالية، بعد حذف شرط `checklist_admin.manage` (route محذوف بالمرحلة 1) | admin فقط |

كل قسم/رابط يحافظ على نمط `request()->is(...)` لتحديد الحالة النشطة، مطابقاً للنمط الحالي بالملف.

**ملاحظة تصميم:** لا حاجة لأي منطق `@if($user->isReceptionist())` صريح — التقلص التلقائي للقائمة بالنسبة للاستقبال يحصل تلقائياً كنتيجة لتصميم الـ Policies بالمرحلة 2 (Employee/Dependent يسمحون بـ `view` لكلا الدورين، الباقي يرفض الاستقبال تلقائياً).

## خطوة 4.2 — `resources/views/layouts/partials/navH.blade.php`

**حذف** الكتل المُعلَّقة الميتة: قسم البحث (Search)، مبدّل الأنماط (Style Switcher)، الروابط السريعة (Quick Links/Shortcuts)، قائمة الإشعارات (Notifications dropdown).

**الإبقاء بدون تغيير:** الشعار (brand logo)، زر فتح/طي القائمة، الـ slot `$extra_nav` (تُستخدم لحقن فلاتر/أزرار الصفحة الحالية بالـ navbar)، قائمة المستخدم المنسدلة (البروفايل → `dashboard.profile.settings`، تسجيل الخروج → route الخاص بـ Fortify).

**نقطة تحقق مطلوبة بوقت التنفيذ:** التأكد أن قائمة المستخدم المنسدلة لا تشير لحقول `person`/`user_type`/`phone` (لم تُفحص هذه الأجزاء بالتفصيل أثناء الاستكشاف) — إذا وُجدت إشارات، تُحذف بما يتماشى مع تنظيف موديل `User` بالمرحلة 2.

## خطوة 4.3 — التحقق من `<x-front-layout title="...">`

كل صفحة جديدة تمرر `title` مطابق للاتفاقية الحالية (يظهر بنص الـ brand بالـ navbar، انظر الاستخدام الحالي بـ `navH.blade.php`) — لا حاجة لتعديل بنيوي بـ `FrontLayout.php` نفسه أو `front-layout-horizantal.blade.php`.

## خطوة 4.4 — تنظيف نسخة الـ Vertical غير المفعّلة (`aside.blade.php` / `nav.blade.php` / `front-layout.blade.php`)

**[محسوم]** هاي الملفات **لا تُحذف** (تراجع عن قرار الحذف الأصلي بالمرحلة 1 — انظر `phase-1-cleanup.md` خطوة 1.5) لإبقاء إمكانية التحويل لاحقاً للوضع الجانبي عبر كوكي `dirNav` وفرع `FrontLayout::render()` المُعلَّق حالياً. تُنظَّف فقط من الروابط الميتة للمجال القديم، **بنفس القرارات بالضبط** المتخذة بخطوة 4.1/4.2 أعلاه (نفس الجدول، نفس الشروط `@can`، نفس الأقسام) — نسخة مطابقة منطقياً لـ `asideH.blade.php`/`navH.blade.php` المُحدَّثين، فقط بتخطيط عمودي بدل أفقي. لا حاجة لتفعيل الفرع المعلَّق فعلياً بهذه المرحلة (يبقى معلَّقاً) — فقط تحديث محتوى الملفات لتكون جاهزة يوم يُفعَّل.
