# المرحلة 3 — Migrations للكيانات الجديدة

## الهدف

إنشاء الجداول السبعة الجديدة بالترتيب الصحيح حسب اعتماديات الـ Foreign Keys، مع تحديد أي القيود تُفرض على مستوى قاعدة البيانات وأيها على مستوى منطق التطبيق فقط.

هذه الخطوة تنفّذ حرفياً ما هو محدد بـ `docs/reference/01-entities.md` — لا قرارات جديدة هنا، فقط ترجمة تقنية دقيقة.

## الترتيب (حسب الاعتمادية)

```
1. organization_units   (self-referencing, لا يعتمد على شي)
2. medical_departments   (مستقل تماماً، يُزرع بـ 4 صفوف ثابتة عبر seeder)
3. employees             (يعتمد على organization_units)
4. dependents            (يعتمد على employees)
5. visits                (يعتمد على employees + dependents)
6. visit_departments     (يعتمد على visits + medical_departments)
7. survey_submissions    (يعتمد على employees + users، لكن الحقول الأساسية تعتمد فقط على users)
```
عمود `role` على `users` (المرحلة 2) لا علاقة FK له بأي من هذه الجداول — لا يهم ترتيبه بالنسبة لهم، فقط يجب أن يوجد قبل أي seeding لمستخدمي اختبار admin/receptionist.

## 1. `create_organization_units_table`

```php
$table->id();
$table->foreignId('parent_id')->nullable()->constrained('organization_units')->nullOnDelete();
$table->string('name');
$table->tinyInteger('level'); // 1=مركز, 2=دائرة, 3=قسم
$table->timestamps();
```
لا قيود unique إضافية مطلوبة بالوثيقة على هذا الجدول.

## 2. `create_medical_departments_table`

```php
$table->id();
$table->string('name'); // 'clinics','laboratory','pharmacy','radiology'
$table->decimal('discount_percentage', 5, 2);
$table->boolean('is_active')->default(true);
$table->timestamp('updated_at')->nullable();
```
**ملاحظة:** الوثيقة تحدد `updated_at` فقط (بدون `created_at`) — الجدول لا يستخدم `timestamps()` القياسي. الجدول يُزرع بـ 4 صفوف ثابتة (clinics/laboratory/pharmacy/radiology) عبر seeder بعد الـ migration مباشرة — لا واجهة إنشاء/حذف لهذا الكيان (انظر المرحلة 5).

## 3. `create_employees_table`

```php
$table->id();
$table->string('full_name');
$table->string('national_id', 9)->unique();
$table->enum('gender', ['male', 'female']);
$table->enum('marital_status', ['single', 'married', 'polygamous', 'widowed', 'divorced']);
$table->foreignId('organization_unit_id')->constrained('organization_units')->restrictOnDelete();
$table->enum('status', ['pending', 'active', 'inactive'])->default('active');
$table->enum('source', ['survey', 'admin']);
$table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
$table->timestamp('approved_at')->nullable();
$table->timestamps();
```
`national_id` unique **على مستوى هذا الجدول فقط بالـ DB** — التفرّد عبر `employees` + `dependents` معاً (المطلوب بالوثيقة) هو منطق تطبيق إضافي (custom validation rule)، لأن DB لا يقدر يفرض unique عبر جدولين مختلفين مباشرة.

## 4. `create_dependents_table`

```php
$table->id();
$table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
$table->enum('type', ['spouse', 'child', 'parent']);
$table->string('full_name');
$table->string('national_id', 9)->unique();
$table->enum('gender', ['male', 'female']);
$table->enum('parent_type', ['father', 'mother'])->nullable();
$table->timestamps();
```
`cascadeOnDelete` على `employee_id`: حذف موظف يحذف تابعيه تلقائياً (متسق مع كون التابعين كيان تابع بالكامل للموظف، بدون معنى مستقل). القيود التالية **منطق تطبيق فقط** (لا قيد DB مباشر):
- عدد الزوجات (1 للإناث، متعدد للذكور حسب `marital_status`)
- حد أقصى سجل واحد `parent_type=father` وواحد `parent_type=mother` لكل موظف
- تفرّد `national_id` عبر `employees`+`dependents` معاً

## 5. `create_visits_table`

```php
$table->id();
$table->foreignId('employee_id')->constrained('employees')->restrictOnDelete();
$table->foreignId('patient_employee_id')->nullable()->constrained('employees')->restrictOnDelete();
$table->foreignId('patient_dependent_id')->nullable()->constrained('dependents')->restrictOnDelete();
$table->date('visit_date');
$table->foreignId('recorded_by')->constrained('users')->restrictOnDelete();
$table->foreignId('last_updated_by')->nullable()->constrained('users')->nullOnDelete();
$table->decimal('total_before_discount', 10, 2)->nullable();
$table->decimal('total_after_discount', 10, 2)->nullable();
$table->timestamps();

$table->unique(['patient_employee_id', 'visit_date']);
$table->unique(['patient_dependent_id', 'visit_date']);
```
**قيد الفريدية مُفرَض على مستوى DB** (وليس فقط منطق تطبيق) — MySQL يسمح بتكرار `NULL` بعمود unique، فهذا يعمل صح: الصفوف اللي فيها `patient_employee_id IS NULL` (لأن المريض تابع) ما تتعارض مع بعضها بهذا القيد، والعكس صحيح بقيد `patient_dependent_id`.

القيد "بالضبط عمود واحد من الاثنين معبّى" (XOR) هو **منطق تطبيق فقط** — لا يوجد CHECK constraint قياسي بـ Laravel migrations لهذا، يُفرض بالـ FormRequest/Controller. (تحسين اختياري لاحقاً: `DB::statement` بـ CHECK صريح على MySQL 8.0.16+ — ليس مطلوباً لهذه المرحلة.)

`restrictOnDelete` على `employee_id`/`patient_employee_id`: يمنع حذف موظف له زيارات — يتماشى مع قرار المرحلة 5 بخصوص حذف الموظفين.

## 6. `create_visit_departments_table`

```php
$table->id();
$table->foreignId('visit_id')->constrained('visits')->cascadeOnDelete();
$table->foreignId('medical_department_id')->constrained('medical_departments')->restrictOnDelete();
$table->decimal('applied_discount_percentage', 5, 2);
$table->decimal('amount_before_discount', 10, 2)->nullable();
$table->decimal('amount_after_discount', 10, 2)->nullable();
$table->timestamp('added_at')->useCurrent();
$table->foreignId('added_by')->constrained('users')->restrictOnDelete();

$table->unique(['visit_id', 'medical_department_id']);
```
`cascadeOnDelete` على `visit_id`: صفوف الأقسام تموت مع الزيارة (لا معنى مستقل لها). **قيد Unique مُفرَض على مستوى DB** — يمنع تكرار نفس القسم الطبي بنفس الزيارة مباشرة.

**ملاحظة:** لا `updated_at` — فقط `added_at` (استخدام `->useCurrent()` بدل `timestamps()` القياسي).

## 7. `create_survey_submissions_table`

```php
$table->id();
$table->json('raw_data');
$table->string('national_id', 9);
$table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
$table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
$table->timestamp('reviewed_at')->nullable();
$table->foreignId('created_employee_id')->nullable()->constrained('employees')->nullOnDelete();
$table->timestamps();
```
**ملاحظة مهمة:** `national_id` هنا **بدون unique بمستوى DB** عمداً — الوثيقة لا تشترط ذلك (طلب مرفوض ممكن نظرياً يُعاد تقديمه لاحقاً بنفس الهوية)، وفحص التكرار عبر `employees`/`dependents`/باقي `survey_submissions` المعلَّقة هو بالكامل منطق تطبيق وقت التقديم (انظر المرحلة 6).

## الموديلات المرافقة (تُنشأ مع الـ migrations، السلوك/الـ scopes بالمرحلة 6)

- `app/Models/OrganizationUnit.php` — `parent()`, `children()` (self-referencing)
- `app/Models/Employee.php` — `organizationUnit()`, `dependents()`, `visits()` (عبر `employee_id`), `approvedBy()`
- `app/Models/Dependent.php` — `employee()`
- `app/Models/MedicalDepartment.php`
- `app/Models/Visit.php` — `employee()`, `patientEmployee()`, `patientDependent()`, `visitDepartments()`, `recordedBy()`, `lastUpdatedBy()`
- `app/Models/VisitDepartment.php` — `visit()`, `medicalDepartment()`, `addedBy()`
- `app/Models/SurveySubmission.php` — `reviewedBy()`, `createdEmployee()`
