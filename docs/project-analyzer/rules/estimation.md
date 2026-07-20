# قواعد التقدير: الحجم + الساعات

## جدول الأحجام

| الحجم | الساعات | ما يصلح له |
|-------|---------|------------|
| XS | 1–2h | Config بسيط، migration وحدها، إضافة field، تعديل بسيط |
| S | 2–4h | Endpoint بسيط + test، component واحد، seeder، middleware |
| M | 4–8h (~يوم) | Feature متكاملة خفيفة: API + validation + UI component |
| L | 2–3 أيام | Feature معقّدة: logic + UI + tests + edge cases |
| XL | +3 أيام | **ممنوع — كسّرها** |

---

## قواعد التقدير

### 1. قدّر المهمة N.X كاملة (الكبيرة)
لا تقدّر الـ sub-tasks، قدّر المهمة N.X وضع المجموع.

### 2. اكتب الحجم والساعات سوا
```
الحجم: M (~6h)
الحجم: L (~2 أيام / ~12h)
الحجم: S (~3h)
```

### 3. رجّح للأعلى دائماً
التقديرات تُخطئ عادةً للأقل. اجعل التقدير "متفائل معقول" مش "متفائل مثالي".

### 4. الساعات في نوشن
اكتب الرقم في `Estimated Time (hrs)`:
- XS → 1.5
- S → 3
- M → 6
- L → 12

### 5. تقدير المرحلة الكاملة
في OVERVIEW.md (جدول المهام): اجمع ساعات الـ N.X كلها وحوّلها لأيام (~8h = يوم).
في TRACKER.md (جدول المراحل): اكتب الأيام التقديرية للمرحلة.

---

## أمثلة تقديرية

```
01 — Laravel init + Git setup                 → XS (~1h)
02 — Inertia + Vue + Tailwind install          → S (~3h)
03 — Auth: Login/Register/Email Verify         → M (~6h)
04 — User roles + permissions (Spatie)         → S (~4h)
05 — CRUD: Projects (Model+Migration+API+UI)   → M (~8h)
06 — Financial Engine: Ledger + Transactions   → L (~14h)
07 — Multi-tenant: tenant isolation logic      → L (~12h)
08 — Stripe billing + webhook handler          → L (~10h)
09 — Reports: Dashboard charts (Vue+API)       → M (~7h)
```

---

## معايرة التقديرات (بمرور الوقت)

نوشن يتتبّع `Estimated Time (hrs)` و `Actual Time (hrs)`.
بعد كل مشروع: قارن الاثنين لكل مهمة وعدّل جدول الأحجام حسب تجربتك الفعلية.

هدف: بعد 3 مشاريع، تصبح تقديراتك دقيقة ±20%.
