# الكيانات (Entities)

> ملاحظة: أسماء الحقول هنا بالإنجليزية (snake_case) لتكون جاهزة مباشرة كأعمدة Migration في Laravel. الأنواع مقترحة على مستوى منطقي (Logical) وتحتاج تأكيد نهائي عند تصميم الـ Migration الفعلي.

---

## 1. Organization Unit — الهيكل التنظيمي (شجري)

جدول واحد self-referencing يمثّل (مركز ← دائرة ← قسم) بدلاً من 3 جداول منفصلة. يُستورد من الجداول الثلاثة القديمة ويُعاد ترتيبه هرمياً هنا.

**الجدول:** `organization_units`

| الحقل | النوع | الوصف |
|---|---|---|
| id | bigint, PK | معرّف تلقائي |
| parent_id | bigint, FK → organization_units.id, nullable | الوحدة الأعلى (null = مستوى جذري / مركز) |
| name | string | اسم الوحدة (مركز/دائرة/قسم) |
| level | tinyint | المستوى الهرمي (1 = مركز، 2 = دائرة، 3 = قسم) — تسهيلاً للاستعلامات |
| created_at / updated_at | timestamp | — |

**ملاحظات:**
- لا حاجة لتمييز نوع الوحدة باسم منفصل؛ `level` يكفي لمعرفة إن كانت مركز/دائرة/قسم.
- العلاقة: `organization_units` تحتوي على نفسها (self-referencing hasMany/belongsTo).

---

## 2. Employee — الموظف

**الجدول:** `employees`

| الحقل | النوع | الوصف |
|---|---|---|
| id | bigint, PK | معرّف تلقائي |
| full_name | string | اسم الموظف/ة |
| national_id | string(9), unique | رقم الهوية — فريد إجبارياً |
| gender | enum('male','female') | الجنس |
| marital_status | enum('single','married','polygamous','widowed','divorced') | الحالة الزوجية |
| organization_unit_id | bigint, FK → organization_units.id | التبعية التنظيمية (القسم الإداري الذي يتبع له) |
| status | enum('pending','active','inactive') | حالة الموظف (pending = من الاستبيان بانتظار موافقة الأدمن) |
| source | enum('survey','admin') | كيف أُضيف — عبر الاستبيان أو مباشرة من الأدمن |
| approved_by | bigint, FK → users.id, nullable | الأدمن الذي وافق (إن كان المصدر survey) |
| approved_at | timestamp, nullable | تاريخ الموافقة |
| created_at / updated_at | timestamp | — |

**قواعد:**
- `marital_status = single` → لا يجوز إضافة زوج/ة (لكن الأبناء والوالدان يبقوا اختياريين نظرياً حسب الحالة الواقعية، الأصل حسب الوصف: أعزب = لا زوجة ولا أبناء).
- `national_id` يُفحص عند الإدخال (سواء من الأدمن أو من الاستبيان) لمنع التكرار قبل السماح بالمتابعة.

---

## 3. Dependent — التابعون (زوجات، أبناء، والدين)

كيان موحّد لكل التابعين بدلاً من 3 جداول منفصلة، مع حقل `type` للتمييز. هذا يسهّل الرجوع إليهم كمريض موحّد في الزيارات (انظر قسم Visit).

**الجدول:** `dependents`

| الحقل | النوع | الوصف |
|---|---|---|
| id | bigint, PK | معرّف تلقائي |
| employee_id | bigint, FK → employees.id | الموظف صاحب التبعية |
| type | enum('spouse','child','parent') | نوع التابع |
| full_name | string | الاسم |
| national_id | string(9), unique | رقم الهوية — فريد إجبارياً |
| gender | enum('male','female') | الجنس |
| parent_type | enum('father','mother'), nullable | يُستخدم فقط عند type = parent، لتحديد إن كان أب أو أم |
| created_at / updated_at | timestamp | — |

**قواعد العمل الخاصة بالتابعين:**
- **spouse:** إن كان الموظف أنثى → زوج واحد فقط (ذكر). إن كان الموظف ذكراً → يمكن أكثر من زوجة واحدة (أنثى)، خصوصاً في حالة `marital_status = polygamous`.
- **child:** لا قيد على العدد، الجنس يُحدَّد يدوياً (ذكر/أنثى).
- **parent:** بحد أقصى سجل واحد بـ `parent_type = father` وسجل واحد بـ `parent_type = mother` لكل موظف (تُفرض هذه القاعدة في منطق التطبيق، وليست بالضرورة قيد قاعدة بيانات صارم).
- التحقق من عدم تكرار `national_id` يشمل التابعين أيضاً (نفس الفحص المطبّق على الموظف).

---

## 4. Medical Department — القسم الطبي

**تحديث سياسة (بعد اعتماد جدول خصومات رسمي جديد من الإدارة):** الأقسام المشمولة بالتأمين أصبحت **خمسة** (كانت أربعة أصلاً)، كل قسم له نسبة خصم مستقلة، وبعض الأقسام له أيضاً **حد أقصى لمبلغ الخصم** (وليس فقط نسبة).

**الجدول الرسمي المعتمد:**

| القسم | النسبة | الحد الأقصى لمبلغ الخصم |
|---|---|---|
| الكشف الطبي (clinics) | 100% | لا يوجد (إعفاء كامل) |
| الصيدلية (pharmacy) | 25% | 30 شيكل |
| المختبر (laboratory) | 25% | 30 شيكل |
| البصريات (optics) — **قسم جديد** | 25% | لا يوجد (على كافة خدمات ومنتجات البصريات) |
| الأسنان (dental) — **قسم جديد** | 25% | لا يوجد (على الخدمات العلاجية فقط) |

**قسم "الأشعة" (radiology) الأصلي غير مذكور بالجدول الجديد** — تقرر تعطيله (`is_active = false`) بدل حذفه، حفاظاً على أي بيانات/زيارات سابقة مرتبطة به دون التأثير على الفريدية التاريخية. لا يظهر كخيار عند تسجيل زيارة جديدة طالما `is_active = false`.

**الجدول:** `medical_departments`

| الحقل | النوع | الوصف |
|---|---|---|
| id | bigint, PK | معرّف تلقائي |
| name | enum/string('clinics','laboratory','pharmacy','optics','dental','radiology') | اسم القسم — `radiology` باقٍ بالبيانات لأسباب تاريخية فقط، `is_active=false` |
| discount_percentage | decimal(5,2) | نسبة الخصم الحالية (0 إلى 100). 100 = مجاني بالكامل |
| max_discount_amount | decimal(10,2), nullable | **حقل جديد.** الحد الأقصى لمبلغ الخصم بالشيكل لهذا القسم. لو معبّى، أي خصم محسوب على زيارة يُقارن به ويُؤخذ الأقل (`MIN(amount_before_discount × discount_percentage / 100, max_discount_amount)`). لو null، لا حد أقصى. الحقل مستقل عن قيمة النسبة — ليس مشروطاً بأن تكون النسبة ≥ 25% أو أي عتبة أخرى. |
| is_active | boolean, default true | هل القسم مفعّل حالياً (لا يظهر للاستقبال إن كان false) |
| updated_at | timestamp | آخر تعديل على نسبة الخصم (يفيد كمرجع، ليس بديلاً عن الـ audit log) |

**ملاحظة مهمة:** هذا الجدول يمثّل **الإعداد الحالي** فقط. القيمة الفعلية المطبَّقة على كل زيارة (النسبة والحد الأقصى) تُنسخ (snapshot) داخل `visit_departments` وقت التسجيل، حتى لا تتأثر السجلات القديمة عند تغيير النسبة أو الحد لاحقاً.

---

## 4ب. Clinic — العيادة

**كيان جديد بالكامل**، غير موجود بالتحليل الأصلي. يمثّل قائمة العيادات الطبية المتوفرة (باطنة، عظام، جلدية...) التي يختار الاستقبال منها عند تسجيل زيارة "كشف طبي". مستقل تماماً عن `organization_units` (الهيكل التنظيمي الداخلي لموظفي المؤسسة) وعن `medical_departments` (الأقسام الخمسة الثابتة للخصم) — العيادة هي تفصيل إضافي **داخل** زيارة قسم الكشف الطبي تحديداً.

**الجدول:** `clinics`

| الحقل | النوع | الوصف |
|---|---|---|
| id | bigint, PK | معرّف تلقائي |
| name | string | اسم العيادة |
| is_active | boolean, default true | هل العيادة مفعّلة حالياً (تظهر بقائمة الاختيار) |
| created_at / updated_at | timestamp | — |

قائمة مفتوحة تُدار من الإدارة (إنشاء/تعديل/تعطيل)، على عكس `medical_departments` ذات الصفوف الثابتة.

---

## 5. Visit — الزيارة

الكيان المركزي. **تحديث سياسة:** وحدة الزيارة أصبحت (مريض + يوم + عيادة اختيارية) بدل (مريض + يوم) فقط — انظر تفصيل القاعدة بـ `03-business-rules.md` §2.

**الجدول:** `visits`

| الحقل | النوع | الوصف |
|---|---|---|
| id | bigint, PK | معرّف تلقائي |
| employee_id | bigint, FK → employees.id | الموظف صاحب الرصيد (حتى لو المريض تابع، الرصيد يُخصم من هذا الموظف) |
| patient_employee_id | bigint, FK → employees.id, nullable | مُعبّى فقط إذا كان المريض هو الموظف نفسه (= نفس قيمة employee_id) |
| patient_dependent_id | bigint, FK → dependents.id, nullable | مُعبّى فقط إذا كان المريض أحد التابعين |
| clinic_id | bigint, FK → clinics.id, nullable | **حقل جديد.** العيادة المرتبطة بالزيارة (لو فيها كشف طبي). اختياري — زيارة تحتوي فقط صيدلية/مختبر/بصريات/أسنان بدون كشف طبي تترك هذا الحقل null |
| visit_date | date | تاريخ الزيارة (يُستخدم لفحص التكرار: نفس المريض + نفس التاريخ + نفس العيادة) |
| recorded_by | bigint, FK → users.id | موظف الاستقبال الذي سجّل الزيارة أصلاً |
| last_updated_by | bigint, FK → users.id, nullable | آخر موظف استقبال عدّل على الزيارة (قد يكون مختلفاً عن recorded_by) |
| total_before_discount | decimal(10,2), nullable | إجمالي المبلغ قبل الخصم لكل الأقسام في هذه الزيارة (محسوب من مجموع visit_departments) |
| total_after_discount | decimal(10,2), nullable | إجمالي المبلغ بعد الخصم |
| created_at / updated_at | timestamp | — |

**قاعدة الفريدية (منع الازدواج) — محدّثة:**
- بالضبط عمود واحد من (`patient_employee_id`, `patient_dependent_id`) يجب أن يكون مُعبّى، والآخر null دائماً (يُفرض في منطق التطبيق).
- Unique constraint على `(patient_employee_id, visit_date, clinic_id)` وUnique constraint منفصل على `(patient_dependent_id, visit_date, clinic_id)`.
- **تنبيه فني:** MySQL يعتبر أي صف بقيمة NULL بأحد أعمدة الـ unique constraint غير متعارض تلقائياً مع أي صف آخر بنفس القيم — أي أن قيد الـ DB وحده **لا يمنع** تكرار زيارتين بلا عيادة (`clinic_id = null`) لنفس المريض بنفس اليوم. لذلك: الزيارات التي فيها `clinic_id` معبّى تُفحص بالكامل عبر قيد الـ DB (مريض+يوم+عيادة). الزيارات بلا عيادة (`clinic_id = null`) تُفحص **بمنطق تطبيق إضافي** يطبّق فعلياً قاعدة (مريض+يوم) القديمة — زيارة واحدة كحد أقصى بلا عيادة لكل مريض/يوم.
- مريضان مختلفان (موظف + تابع، أو تابعان مختلفان) بنفس اليوم = زيارتان منفصلتان دائماً، بغض النظر عن العيادة.
- عيادتان مختلفتان لنفس المريض بنفس اليوم = زيارتان منفصلتان، كل واحدة تستهلك من الرصيد الشهري بشكل مستقل.
- أي محاولة تسجيل جديدة تصطدم بأحد القيدين أعلاه يجب أن توجّه موظف الاستقبال لتعديل الزيارة الموجودة (مثلاً بإضافة قسم صيدلية/مختبر عليها) بدلاً من إنشاء زيارة جديدة.

---

## 6. Visit Department (Pivot) — الأقسام المشمولة بالزيارة

يمثّل الأقسام الطبية التي شملتها زيارة معينة، مع تفاصيل الخصم والمبالغ الخاصة بكل قسم داخل هذه الزيارة تحديداً.

**الجدول:** `visit_departments`

| الحقل | النوع | الوصف |
|---|---|---|
| id | bigint, PK | معرّف تلقائي |
| visit_id | bigint, FK → visits.id | الزيارة المرتبطة |
| medical_department_id | bigint, FK → medical_departments.id | القسم الطبي |
| applied_discount_percentage | decimal(5,2) | نسبة الخصم كما كانت لحظة إضافة هذا القسم للزيارة (snapshot من medical_departments.discount_percentage) |
| applied_max_discount_amount | decimal(10,2), nullable | **حقل جديد.** الحد الأقصى لمبلغ الخصم كما كان لحظة الإضافة (snapshot من medical_departments.max_discount_amount) — null إن لم يكن للقسم حد أقصى |
| amount_before_discount | decimal(10,2), nullable | المبلغ الأساسي الذي يُدخله الاستقبال لهذا القسم — حقل اختياري |
| amount_after_discount | decimal(10,2), nullable | المبلغ بعد الخصم — يُحسب تلقائياً: `amount_before_discount - MIN(amount_before_discount × applied_discount_percentage / 100, applied_max_discount_amount ?? ∞)` |
| added_at | timestamp | وقت إضافة هذا القسم للزيارة (يفيد لو أُضيف قسم لاحقاً في نفس اليوم على زيارة موجودة) |
| added_by | bigint, FK → users.id | من أضاف هذا القسم تحديداً |

**قاعدة:** لا يمكن تكرار نفس `medical_department_id` أكثر من مرة داخل نفس `visit_id` (Unique على `visit_id + medical_department_id`).

---

## 7. Activity Log — سجل الحركات (Audit)

سجل خفيف لتوثيق العمليات الجوهرية: تسجيل/تعديل الزيارات، الموافقات على طلبات الاستبيان، وإضافة الموظفين.

**الجدول:** `activity_logs`

| الحقل | النوع | الوصف |
|---|---|---|
| id | bigint, PK | معرّف تلقائي |
| user_id | bigint, FK → users.id, nullable | من قام بالعملية (null إن كانت من النظام تلقائياً، مثلاً إنشاء عبر الاستبيان) |
| action | enum('visit_created','visit_updated','employee_approved','employee_added_via_survey','employee_added_by_admin', ...) | نوع العملية |
| subject_type | string | اسم الموديل المتأثر (Visit, Employee, ...) |
| subject_id | bigint | معرّف السجل المتأثر |
| description | text, nullable | وصف مختصر اختياري للعملية |
| created_at | timestamp | وقت الحدث |

**ملاحظة:** يمكن لاحقاً استبداله بحزمة جاهزة (مثل `spatie/laravel-activitylog`) بدلاً من بناء الجدول يدوياً — قرار تقني يُترك لمرحلة التنفيذ.

---

## 8. User — مستخدم النظام (تسجيل الدخول)

منفصل عن `Employee` — هذا خاص فقط بمن يملك حساب دخول فعلي (أدمن أو موظف استقبال)، وليس كل موظف مؤسسة.

**الجدول:** `users` (جدول Laravel الافتراضي مع تعديل)

| الحقل | النوع | الوصف |
|---|---|---|
| id | bigint, PK | معرّف تلقائي |
| name | string | اسم المستخدم |
| email | string, unique | البريد / اسم الدخول |
| password | string | كلمة المرور (مشفّرة) |
| role | enum('admin','receptionist') | الدور |
| created_at / updated_at | timestamp | — |

---

## 9. Survey Submission — طلب الاستبيان (المرحلة التمهيدية)

يمثّل الإدخال الخام القادم من الرابط العام قبل موافقة الأدمن. يُبنى كـ module منفصل، ويحوّل بياناته إلى `employees` + `dependents` فقط بعد الموافقة.

**الجدول:** `survey_submissions`

| الحقل | النوع | الوصف |
|---|---|---|
| id | bigint, PK | معرّف تلقائي |
| raw_data | json | كامل بيانات الموظف والتابعين كما أُدخلت في الاستبيان (قبل التحويل لجداول رسمية) |
| national_id | string(9) | رقم هوية الموظف مقدّم الطلب — يُفحص فور الإدخال لمنع التكرار |
| status | enum('pending','approved','rejected') | حالة الطلب |
| reviewed_by | bigint, FK → users.id, nullable | الأدمن الذي راجع الطلب |
| reviewed_at | timestamp, nullable | تاريخ المراجعة |
| created_employee_id | bigint, FK → employees.id, nullable | يُملأ بعد الموافقة وربطه بسجل الموظف الرسمي الناتج |
| created_at / updated_at | timestamp | — |

**ملاحظة:** رابط الاستبيان يكون فعّالاً فقط ضمن نافذة زمنية أسبوع واحد من الإطلاق — يُتحكم بهذا عبر إعداد بسيط (تاريخ بداية/نهاية) وليس عبر تعطيل الجدول نفسه.

---

## ملخص العلاقات (Relationships Summary)

```
organization_units (self-referencing)
        │
        │ 1:N
        ▼
    employees ──────────────< dependents (1:N)
        │                          │
        │                          │ patient_dependent_id
        │ patient_employee_id      │
        └──────────┬───────────────┘
                   ▼
                 visits (بالضبط عمود واحد من الاتنين معبّى لكل زيارة) ──> clinics (اختياري، عبر clinic_id)
                          │
                          │ 1:N
                          ▼
                  visit_departments
                          │
                          │ N:1
                          ▼
                medical_departments

users (admin/receptionist) ──< activity_logs
                            ──< visits (recorded_by)
                            ──< survey_submissions (reviewed_by)

survey_submissions ──> employees (بعد الموافقة، created_employee_id)
```

**ملاحظة:** الرصيد الشهري المتبقي لكل موظف **لا يُخزَّن بجدول منفصل** — يُحسب مباشرة وقت الحاجة عبر:
```sql
SELECT COUNT(*) FROM visits
WHERE (patient_employee_id = ? OR patient_dependent_id IN (SELECT id FROM dependents WHERE employee_id = ?))
AND YEAR(visit_date) = ? AND MONTH(visit_date) = ?
```
مع فهرس (index) على `visit_date` و `patient_employee_id` لضمان سرعة الاستعلام. هذا يضمن أن `visits` هو **المصدر الوحيد للحقيقة** بخصوص الاستهلاك الشهري، بدون أي جدول مشتق يحتاج تزامناً يدوياً.