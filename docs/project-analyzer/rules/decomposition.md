# قواعد تقسيم المهام: الذرّية (Atomicity)

## تعريف المهمة الذرّية

مهمة ذرّية هي مهمة تستطيع أن تقول عنها:
- "هل خلصت؟" → الجواب إما نعم كامل أو لا (لا نصف خلص)
- تنجزها شخص/agent واحد بتخصص واحد
- لها نتيجة واضحة يمكن قياسها

---

## علامات يجب تكسير المهمة

### 1. حجمها XL (أكثر من 3 أيام)
المهمة XL تعني أنك لم تفهم التفاصيل كافياً. كسّرها دائماً بدون استثناء.

### 2. تحتوي "و" بين فعلين غير مترابطين
❌ `"اعمل Auth وعمل onboarding"` → مهمتان
✅ `"01 — Auth: تسجيل + تسجيل دخول"` و `"02 — Onboarding Flow"`

> ملاحظة: "و" داخل نفس الـ feature مقبول: "Login + Register + Email Verify" = مهمة واحدة لأنها وحدة منطقية.

### 3. تخصصان مختلفان
❌ `"اعمل API endpoint وعمل Vue component"` 
✅ `"01 — API: Transactions endpoint"` (Backend) و `"02 — UI: Transactions page"` (Frontend)

### 4. "يعتمد على نفسه"
لو المهمة تقول "اعمل X ثم Y ثم Z" وكل خطوة تحتاج السابقة → Z ذرّية بس X و Y هم المدخلات. وضّح الـ dependencies.

---

## مستويات الترقيم

```
Phase N         → المرحلة (مجلد، لا مهمة نوشن)
N.X             → المهمة الكبيرة (تنزل نوشن) = ملف NN-task.md
N.X.Y           → Sub-task (تبقى في ملف المهمة فقط) = checkbox
```

**الـ N.X يجب أن يكون قابلاً للتقدير بساعات.** لو عجزت تقدّره بشكل معقول → كسّره.

---

## المهمة الجيّدة: الشكل النهائي

```markdown
# 3.2 — Onboarding Flow

- التخصص: Full-stack
- الحجم: M (~6h)
- يعتمد على: 3.1 (Auth)
- الأولوية: High

## الهدف
عمل wizard بسيط بعد أول login يجمع بيانات المستخدم الأساسية.

## المهام
- [ ] 3.2.1 Onboarding model + migration (name, company, role)
- [ ] 3.2.2 Wizard component (Vue) — 2 steps
- [ ] 3.2.3 API endpoint لحفظ البيانات
- [ ] 3.2.4 Redirect logic بعد onboarding

## ملفات تتأثر
`app/Models/UserProfile.php`, `resources/js/Pages/Onboarding/`, `routes/web.php`

## معايير القبول
- المستخدم الجديد يُحوَّل تلقائياً لـ onboarding بعد login
- يمكن تخطّيه (skip) مع warning
- البيانات تُحفَظ وتظهر في profile
```

---

## شائع الأخطاء

| الخطأ | الصح |
|-------|------|
| "Setup المشروع" (ضبابي) | "01 — Laravel init + Git" + "02 — Inertia + Vue setup" |
| "تصميم كل الصفحات" (ضخم) | مهمة لكل مجموعة screens ذات صلة |
| "Backend + Frontend" في مهمة | مهمتان منفصلتان |
| "Testing" آخر المرحلة كمهمة وحدة | TESTING.md للمرحلة + testing tasks مضمّنة في كل مهمة |
