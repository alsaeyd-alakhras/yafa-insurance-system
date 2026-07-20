# نوشن: التكامل الكامل

## Data Source IDs (لا تغيّرها)

```
Projects DB:   collection://9c8a72f7-f39e-4d8b-9f37-25918b95250c
Tasks DB:      collection://201626fd-cc12-80ac-a0eb-000b39e105ca
Team DB:       collection://2b4626fd-cc12-8092-9246-000bd08fd9db
```

## قاعدة الذهب: ما يُكتَب وما لا يُلمَس

### ✅ تكتب في Projects DB
| الحقل | القيمة |
|-------|--------|
| Name | اسم المشروع |
| Status | `Not started` (في البداية) |
| Source | `Freelancer` أو `Sirbit` أو `My Business` حسب السياق |
| Repo URL | رابط الـ repo لو موجود |
| Notes | وصف مختصر جداً |
| date:Start Date:start | تاريخ البداية لو محدّد |
| date:End Date:start | تاريخ النهاية المتوقّع لو محدّد |

### ❌ لا تلمس في Projects DB
Budget، Payments، Total Income/Expenses، Net Profit، Task-Done rollup — هذه حقول مالية، لا تكتب فيها شيئاً.

### ✅ تكتب في Tasks DB (مهام مستوى 0.x فقط)
| الحقل | القيمة |
|-------|--------|
| Name | `P[N] · [N.X] — [اسم المهمة]` |
| Status | `Not started` |
| Type | Backend / Frontend / UX / Design / Mobile / DevOps / QA / Full-stack / Feature / Research |
| Priority | High / Medium / Low |
| Estimated Time (hrs) | رقم الساعات المقدّرة |
| Details | معايير القبول المختصرة (سطر-سطرين) |
| ⚡ Projects | ربط بالمشروع (JSON array of project URL) |

### ❌ لا تلمس في Tasks DB
Actual Time (hrs)، Assignee (person field)، URL، Formula، Task Age — يملأها المستخدم أثناء التنفيذ.

---

## نظام تسمية المهام

```
P[رقم المرحلة] · [رقم المهمة] — [اسم وصفي]

أمثلة:
P0 · 0.1 — إعداد مشروع Laravel + Git
P0 · 0.2 — تثبيت Inertia + Vue 3
P1 · 1.1 — Sitemap وهيكل المعلومات
P3 · 3.1 — Auth: تسجيل + تسجيل دخول + تحقق البريد
P3 · 3.2 — Onboarding Flow
```

**القاعدة:**
- المراحل لا تُنشأ كصفوف في نوشن — رقمها فقط في اسم المهمة
- Sub-tasks (N.X.Y) لا تنزل نوشن — تبقى في ملفات md
- كل مهمة N.X = صف مستقل في Tasks DB

---

## ربط المهام بالمشروع

استخدم علاقة **`Task - Relation`** حصراً (collection `201626fd-cc12-80ac-a0eb-000b39e105ca`).

عند إنشاء المهمة في Tasks DB، اضبط:
```json
"⚡ Projects": ["<URL of project page>"]
```
هذا يربطها تلقائياً بالمشروع في كلا الاتجاهين.

---

## خريطة Status (TRACKER → نوشن)

| في TRACKER.md | في نوشن Status |
|---------------|----------------|
| `[ ]` لسا | `Not started` |
| `[ ]` مش بدأت بعد | `Backlog` (لو في pipeline بعيد) |
| شغّالة الآن | `In progress` |
| `[~]` جاهزة للمراجعة | `Review` |
| `[x]` خلصت | `Done` |
| مبلوكة | `Blocked` |

---

## تسلسل الإنشاء في نوشن

1. **أنشئ المشروع أولاً** في Projects DB → احفظ URL الصفحة.
2. **أنشئ مهام N.X** في Tasks DB واحدة تلو الأخرى، مرتّبة حسب رقم المرحلة.
3. **اربط كل مهمة** بالمشروع عبر `⚡ Projects`.
4. **تحقّق:** عدد المهام في نوشن = مجموع مهام N.X في كل المراحل.

---

## Team DB (عند الطلب فقط)

**لا تقرأ Team DB** إلا لما يقول المستخدم "وزّع على الفريق" ويعطيك الأسماء.

عند التوزيع:
- اقرأ Team DB لتعرف Role (Frontend/Backend/Designer/Full-stack) و Status (Active فقط).
- اعتمد `Workload indicator` formula لمعرفة الحمل الحالي.
- اربط المهمة بعضو الفريق عبر `Assigned To` في Tasks DB.
