# المرحلة 2 — المصادقة والصلاحيات (Fortify + Roles + Policies)

## الهدف

تجهيز عمود `role` على `users` (لتصنيف عام + قيمة افتراضية عند الإنشاء)، تنظيف موديل `User` من الحقول الوهمية، وتفعيل نظام `RoleUser` الموجود فعلياً كآلية الفحص الفعلية للصلاحيات لكل الكيانات الجديدة الستة + `User`.

## خطوة 2.1 — Migration: إضافة `role` لجدول `users`

migration جديدة (مثال: `2026_07_20_000001_add_role_to_users_table.php`):
```php
$table->enum('role', ['admin', 'receptionist'])->default('receptionist')->after('super_admin');
```
بما أنه يوجد 7 migrations فقط بالمشروع حالياً ولا يوجد أي migration يعدّل `users` بعد إنشائه، هذا migration إضافي بسيط بدون تعقيد ترحيل بيانات. `down()` تحذف العمود فقط.

## خطوة 2.2 — تنظيف موديل `User`

الوضع الحالي: `app/Models/User.php` يحتوي `phone`, `user_type`, `is_active` بالـ `$fillable` **بدون أي عمود فعلي يقابلهم بأي migration** — بقايا من نظام "بوابة الموظف الذاتية" بالمشروع القديم غير المستخدم هنا.

**القرار: حذفهم نهائياً، بدون إضافة أعمدة حقيقية لهم.**

- `$fillable`: حذف `phone`, `user_type`, `is_active` — إضافة `role`.
- `casts()`: حذف `is_active => boolean` (عمود وهمي).
- حذف علاقة `person(): HasOne` بالكامل (لا يوجد موديل `Person` بهذا المشروع).
- إضافة method helpers للفحص السريع بالكود (تُستخدم بشكل رئيسي لقيمة `role` الافتراضية عند إنشاء مستخدم، وليس كفحص صلاحيات فعلي — انظر خطوة 2.3):
```php
public function isAdmin(): bool { return $this->role === 'admin'; }
public function isReceptionist(): bool { return $this->role === 'receptionist'; }
```

**لماذا الحذف وليس إضافة أعمدة حقيقية:** هذه الحقول موجودة فقط لأن `UserController` و `Gate::before` بالكود الحالي يشيرون لها من نمط المشروع القديم. الوثيقة المرجعية (`01-entities.md` §8) تحدد صراحة عمود `role` بدل `user_type` القديم. تأكيد أن الحذف آمن: الكود بـ `Gate::before` الذي يفحص `user_type == 'employee'` موجود داخل `if` معلَّق (كود ميت فعلياً، بدون أي تأثير حالياً).

## خطوة 2.3 — قرار نمط الصلاحيات (محسوم، مُعدَّل بعد مراجعة المستخدم)

**القرار النهائي: `RoleUser` هو آلية الفحص الفعلية لكل الصلاحيات — وليس `role` فقط. عمود `role` يبقى للتصنيف العام ولتحديد الصلاحيات الافتراضية (Default Ability Set) عند إنشاء مستخدم جديد فقط.**

هذا القرار يُلغي التوصية المبدئية بالخطة (الاعتماد فقط على `role` عبر `Gate::before` مع تجاهل `RoleUser`) بناءً على تفضيل صريح من المستخدم: نظام صلاحيات جزئي عبر `role_user` أسهل وأمرن من قفل كل شي بمنطق دورين ثابت، خصوصاً أن الحالات الحدّية (مثال: استقبال بصلاحية حذف مستخدمين، انظر خطوة 2.4 جدول `UserPolicy`) لا يمكن التعبير عنها بمنطق `role` بسيط.

**الآلية بالتحديد:**

- `Gate::before` بـ `AppServiceProvider::boot()` يبقى بـ bypass واحد فقط: `super_admin` (البوليان الحالي). **يُحذف** الـ bypass المقترح سابقاً لـ `role=admin` — لا يعود هناك تجاوز تلقائي لكل الصلاحيات لمجرد `role=admin`.
```php
Gate::before(function ($user, $ability) {
    if ($user instanceof User && $user->super_admin) {
        return true;
    }
});
```
- كل الـ Policies الجديدة تعتمد بالكامل على `ModelPolicy::__call` (فحص `RoleUser` — هل عند هذا المستخدم صف بـ `role_name` يطابق الـ ability المشتقة من اسم الموديل والـ method).
- عند إنشاء مستخدم جديد بـ `UserController::store()`، يُبنى صف `RoleUser` تلقائياً لكل الصلاحيات المرتبطة بالكيانات الجديدة حسب `role` المختار (admin يحصل تلقائياً على كل صلاحيات الكيانات الستة + `users`، receptionist يحصل تلقائياً على `visits.view/create/update/delete-own` + `employees.view` + `dependents.view` فقط) — هذا الافتراضي *قابل للتعديل يدوياً بعدها* من واجهة تعديل المستخدم (checkbox لكل صلاحية، بنفس روح `EMPLOYEE_DEFAULT_ABILITIES` بالكود القديم لكن مُحدَّث للكيانات الجديدة).
- **لا نبني واجهة صلاحيات معقدة** — فقط قائمة checkboxes بسيطة بصفحة تعديل/إنشاء المستخدم (نفس نمط `data/abilities.php` الحالي المستخدم لعرض التسميات)، مع تمييز واضح إذا هذا الصف `role=admin` أو `role=receptionist` (يُستخدم فقط لملء الصلاحيات الافتراضية أول مرة، وكوسم/تصنيف عام بالجدول).

**ملخص الفرق عن التوصية المبدئية:**

| | التوصية المبدئية (قبل التعديل) | القرار النهائي |
|---|---|---|
| الفحص الفعلي | `role` عبر `Gate::before` | `RoleUser` (صلاحيات فردية لكل مستخدم) |
| دور `role` | التصنيف + الفحص معاً | تصنيف عام + قيمة افتراضية فقط |
| مرونة الاستثناءات | غير ممكنة (استقبال ما يقدر ياخد صلاحية أدمن) | ممكنة (استقبال بصلاحية معينة إضافية إذا لزم) |
| واجهة إدارة صلاحيات | لا تُبنى | تُبنى، بسيطة (checkboxes)، تشبه النمط القديم |

**تنظيف مرافق:** حذف الـ 3 استدعاءات `Gate::define()` الصريحة (`admins.super`, `reports.view`, `checklist_admin.manage`) من `AppServiceProvider` — أصبحت كود ميت بعد حذف الـ routes/controllers المرتبطة فيها بالمرحلة 1 (و`admins.super` غير مستخدم بأي مكان آخر بالكود أصلاً).

**تحديث `data/abilities.php`:** بدل حذف الملف أو إبقائه شبه ميت (كما كان مقترحاً بالمرحلة 1 خطوة 1.6)، **يُحدَّث فعلياً** بإضافة مجموعات الكيانات الجديدة الستة (`organization_units`, `medical_departments`, `employees`, `dependents`, `visits`, `survey_submissions`) بنفس شكل المجموعات الموجودة (`view`, `create`, `update`, `delete`)، بالإضافة لصلاحية خاصة `visits.delete-own` (حذف الزيارات التي سجّلها المستخدم نفسه فقط — أضعف من `visits.delete` العامة، انظر `VisitPolicy` بخطوة 2.4).

## خطوة 2.4 — نمط الـ Policy لكل كيان (مُعدَّل)

كل الـ Policies الجديدة بـ `app/Policies/`، مقابلة لموديل جديد بـ `app/Models/` (يُنشأ بالمرحلة 3). لا حاجة لتسجيل يدوي — Laravel auto-discovery (`{Model}Policy`) يكفي.

| Policy | النمط | السبب |
|---|---|---|
| `OrganizationUnitPolicy` | `extends ModelPolicy`، فاضية (نسخة من `UserPolicy.php`) | فحص كامل عبر `RoleUser` (`organization-units.view/create/update/delete`)؛ الافتراضي: admin=الكل، receptionist=لا شي |
| `MedicalDepartmentPolicy` | `extends ModelPolicy`، فاضية | نفس الشي — فحص `RoleUser` كامل |
| `SurveySubmissionPolicy` | `extends ModelPolicy`، فاضية | فحص `RoleUser` (الموافقة/الرفض تُصمَّم كـ ability `update`) |
| `EmployeePolicy` | `extends ModelPolicy`، فاضية بالكامل (بدون override) | الفحص الكامل عبر `RoleUser`؛ الافتراضي: admin=الكل، receptionist=`view` فقط — لكن هذا الآن اختيار سياسة افتراضية عند الإنشاء، مش قيد صارم بالـ Policy نفسها، فيصير قابل للتعديل لاحقاً لمستخدم استقبال محدد لو لزم |
| `DependentPolicy` | نفس شكل `EmployeePolicy` | نفس المنطق |
| `VisitPolicy` | **مخصص (مش فاضي)** — راجع تفصيل أسفل | حذف الزيارة صار مسموح فعلياً (وليس ممنوعاً بالكامل كما كانت التوصية المبدئية) لكن بشرط ملكية السجل بالنسبة للاستقبال |
| `UserPolicy` | موجود مسبقاً، فاضٍ، بدون تغيير بنيوي | فحص `RoleUser` كامل (`users.view/create/update/delete`) — الافتراضي: admin=الكل (باستثناءات، انظر أدناه)، receptionist=لا شي افتراضياً، **لكن قابل للتفعيل يدوياً** لمستخدم استقبال محدد إذا مُنح الصلاحية صراحة (القرار الصريح للمستخدم: "ممكن استقبال ان كان معه صلاحية يمسح المستخدمين") |

### تفصيل `VisitPolicy` (منطق ملكية، وليس فحص `RoleUser` قياسي فقط)

```php
class VisitPolicy
{
    public function viewAny(User $user): bool { return true; } // كلا الدورين
    public function view(User $user, Visit $visit): bool { return true; }
    public function create(User $user): bool { return true; } // كلا الدورين (حسب مصفوفة الصلاحيات)
    public function update(User $user, Visit $visit): bool { return true; }

    public function delete(User $user, Visit $visit): bool
    {
        // صلاحية حذف عامة (تُمنح افتراضياً للأدمن عبر RoleUser: visits.delete)
        if ($user->roles->where('role_name', 'visits.delete')->isNotEmpty()) {
            return true;
        }
        // صلاحية "حذف ما سجّلته أنت فقط" (تُمنح افتراضياً للاستقبال عبر RoleUser: visits.delete-own)
        if ($user->roles->where('role_name', 'visits.delete-own')->isNotEmpty()) {
            return $visit->recorded_by === $user->id;
        }
        return false;
    }
}
```

**السبب:** القرار الصريح للمستخدم — أي مستخدم (أدمن أو استقبال) يقدر يحذف زيارة، لكن الاستقبال محدود بالزيارات التي سجّلها بنفسه (`recorded_by = هو`)، بينما الأدمن (أو أي مستخدم يملك `visits.delete` العامة) يحذف أي زيارة. حذف مباشر (hard delete)، بدون حالة "ملغية" وسيطة — القرار الصريح: "خلص حذف بشكل مباشر وخلص". كل حذف يُسجَّل بـ `activity_logs` (`event_type = visit_deleted`) بغض النظر عن مين حذف.

## خطوة 2.5 — Fortify وتأثير الـ role على التنقل

- **لا تغيير مطلوب** على `Fortify::authenticateUsing()` بـ `FortifyServiceProvider` — الفحص الحالي يعتمد على username/email + password فقط، لا يشير لـ role.
- **لا تغيير مطلوب** على `LoginResponse` — يعيد التوجيه لـ `/` بغض النظر عن الدور.
- الـ nav (`asideH.blade.php`، تفصيل بالمرحلة 4) يستمر باستخدام `@can('view', Model::class)` لكل قسم كما هو — الفرق أن الفحص وراءه الآن `RoleUser` بدل `role` مباشرة، لكن النتيجة النهائية (تقلص القائمة تلقائياً حسب صلاحيات المستخدم الفعلية) نفسها، وأكثر دقة (يعكس صلاحيات فردية حقيقية وليس فقط الدور العام).

## خطوة 2.6 — فحص `LogLastUserActivity`

خطوة تحقق سريعة بوقت التنفيذ: التأكد إنه يحدّث `last_activity` فقط ولا يشير لـ `person`/`user_type`/`phone`. إذا نظيف، يبقى كما هو؛ إذا فيه إشارة لحقول وهمية، تُحذف بهذه المرحلة.

## خطوة 2.7 — قيود خاصة على حذف/تعديل المستخدمين (`UserController::destroy`)

قرار صريح إضافي من المستخدم يتجاوز فحص `RoleUser` القياسي بحالات معينة:

1. **لا مستخدم يقدر يحذف نفسه** — بغض النظر عن صلاحياته أو `super_admin`.
2. **`super_admin = true` يقدر يحذف أي حساب آخر** (أدمن أو استقبال) — يستمر بتجاوز `Gate::before` كما هو.
3. **مستخدم `role=admin` عادي (بدون `super_admin`) لا يقدر يحذف مستخدم آخر بـ `role=admin`** — حتى لو يملك صلاحية `users.delete` عبر `RoleUser`. هذا القيد **إضافي فوق** فحص الـ Policy القياسي، يُفرض داخل `UserController::destroy()` مباشرة (وليس بـ `UserPolicy` نفسها، لأنه قيد على *هوية الهدف* وليس فحص صلاحية عام):
```php
public function destroy(Request $request, User $user)
{
    $this->authorize('delete', User::class);

    abort_if($user->id === auth()->id(), 403, 'لا يمكنك حذف حسابك الخاص.');
    abort_if($user->role === 'admin' && !auth()->user()->super_admin, 403, 'فقط المدير العام (super admin) يقدر يحذف حساب أدمن آخر.');

    // ... الحذف الفعلي
}
```
4. **استقبال بصلاحية `users.delete`** (إن مُنحت له صراحة عبر `RoleUser`، غير افتراضية) يقدر يحذف مستخدمين آخرين (بما فيهم أدمن عادي؟ لا — نفس القيد رقم 3 يطبَّق بغض النظر عن دور من يحذف، فقط `super_admin` يتجاوزه) — القيد مبني على *دور الهدف المُراد حذفه* وليس دور من يحذف، فيطبَّق بشكل موحّد.
