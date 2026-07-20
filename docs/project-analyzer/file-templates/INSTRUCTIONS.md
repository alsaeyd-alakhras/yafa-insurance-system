# INSTRUCTIONS — تعليمات التنفيذ

> هذا الملف لـ Claude Code والمبرمجين. اقرأه قبل أي كود.

---

## كيف تبدأ

1. اقرأ `BRIEF.md` لتفهم الـ scope الكامل.
2. اقرأ `BLUEPRINT.md` للمعمارية والـ data model.
3. ابدأ من أول مهمة `[ ]` في `TRACKER.md`.
4. لكل مهمة: افتح ملفها في المرحلة المناسبة واتبع خطواتها.

---

## كيف تمشي على TRACKER.md

```
[ ]  → لسا ما بدأت
[~]  → شغّالة الآن أو جاهزة مراجعة
[x]  → خلصت وتأكّدنا
⛔   → مبلوكة (اقرأ الـ dependency)
```

**قاعدة:** بعد كل مهمة N.X:
1. غيّر `[ ]` لـ `[x]` في TRACKER.md
2. غيّر Status في نوشن لـ `Done`
3. commit بالاسم: `feat(phaseN): [N.X] task name`

---

## اصطلاحات الكود (Laravel + Vue)

**Structure (Laravel):**
```
app/
  Http/Controllers/[Domain]/
  Models/
  Services/         ← business logic هنا، لا في Controllers
  Actions/          ← single-action classes للعمليات المعقّدة
resources/
  js/
    Pages/[Domain]/
    Components/[Domain]/
    Composables/
```

**Naming:**
- Controllers: `UserController`, `ProjectController`
- Services: `ProjectService`, `BillingService`
- Vue Pages: `PascalCase.vue` (e.g. `ProjectIndex.vue`)
- Composables: `use[Name].js` (e.g. `useProjects.js`)

**Commits:**
```
feat(phase3): [3.2] implement onboarding wizard
fix(phase4): resolve user roles permission issue
chore(setup): configure Tailwind RTL plugin
```

---

## كيف تستخدم Figma MCP (لمراحل UI)

لما تنفّذ مهمة Frontend تحتاج component من Figma:

```
"اقرأ component [اسم الـ component] من Figma وابنه بـ Vue + Tailwind"
```

Figma MCP بيعطيك: التوكنز، الـ variants، الـ spacing. استخدمها مباشرة.

**مهم:** تأكد أن Figma Variables تطابق Tailwind config قبل البناء.

---

## قواعد مهمة

1. **لا تبدأ مهمة N.2 قبل N.1** — احترم الـ dependencies.
2. **لا تضيف features خارج الـ BRIEF** — أي إضافة → اسأل أو ضعها في future-enhancements.
3. **لا migrations بدون rollback** — كل `up()` له `down()`.
4. **tests للـ happy path على الأقل** — كل endpoint رئيسي.
5. **لا أسرار في الكود** — كل config في `.env`.
6. **بعد كل مرحلة** — شغّل `TESTING.md` الخاص بها قبل الانتقال.

---

## لما تواجه مشكلة

1. افتح ملف المهمة — ربما الـ "ملفات تتأثر" تعطيك سياقاً.
2. افتح OVERVIEW.md للمرحلة — في "ملاحظات إضافية" معلومات.
3. افتح BLUEPRINT.md — للقرارات المعمارية.
4. لو المشكلة تقنية ومش محسومة → أضف research spike task وسجّل النتيجة.
