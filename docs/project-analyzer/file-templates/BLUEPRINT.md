# BLUEPRINT — الخطة الشاملة

> المرجع الأعلى مستوى للمشروع. يُقرأ قبل أي شيء.

---

## نظرة عامة

**المشروع:** [اسم المشروع]
**نوع المشروع:** [saas / client-web / إلخ]
**التاريخ:** [تاريخ البداية]

---

## المعمارية التقنية

**Stack:**
```
Backend:  Laravel [version] + MySQL
Frontend: Vue 3 + Inertia.js + Tailwind CSS
Auth:     [Breeze / Sanctum / إلخ]
Storage:  [Local / S3]
Deploy:   [VPS Ubuntu / إلخ]
```

**Data Model الرئيسي (entities):**
```
[Entity1]          → [وصف مختصر + علاقاتها الرئيسية]
[Entity2]          → [وصف]
[Entity3]          → [وصف]
```

**ERD مختصر:**
```
[Entity1] 1──N [Entity2]
[Entity1] N──N [Entity3] (via pivot: [pivot_table])
```

**Integrations:**
- [اسم الـ integration] — [السبب + المرحلة التي تدخل فيها]

---

## تسلسل المراحل

| المرحلة | المحتوى | الأيام | التخصص | الحالة |
|---------|---------|--------|--------|--------|
| Phase 0 | Planning & Architecture | ~2 | Full-stack | [ ] |
| Phase 1 | UX | ~4 | UX | [ ] |
| Phase 2 | UI Design System | ~5 | Design | [ ] |
| Phase 3 | Setup | ~2 | Full-stack | [ ] |
| Phase N | [اسم المرحلة] | ~X | [تخصص] | [ ] |

**إجمالي تقديري:** [X أيام / X أسابيع]

---

## المخاطر العامة

| الخطر | التأثير | الخطة |
|-------|---------|-------|
| ⚠️ [خطر 1] | [تأثيره] | [research spike / قرار / buffer] |
| ⚠️ [خطر 2] | [تأثيره] | [...] |
| ℹ️ [ملاحظة] | [تأثيرها] | [...] |

---

## Critical Path (الاعتماديات الرئيسية)

```
Phase 0 (Planning) 
  ↓
Phase 3 (Setup) 
  ↓
Phase 4 (Auth)        ← بوابة كل الـ features
  ↓
Phase 5 (Core)        ← يعتمد على: P3·Setup, P4·Auth
  ↓
Phase N (Launch)      ← يعتمد على اكتمال كل شيء
```

**اعتماديات عابرة للمراحل:**
- `[P#·#.X]` يجب يخلص قبل `[P#·#.Y]`
- `[P#·#.X]` يجب يخلص قبل بداية Phase [N]

---

## القرارات المحسومة

| القرار | الاختيار | السبب |
|--------|---------|-------|
| [قرار] | [الخيار] | [السبب المختصر] |

---

## المقترحات اللاحقة (بعد المشروع)

موجودة في: [`../../future-enhancements.md`](../../future-enhancements.md)
