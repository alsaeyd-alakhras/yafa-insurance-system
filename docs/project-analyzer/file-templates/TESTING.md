# Phase [N] — [اسم المرحلة]: TESTING

> هذا الملف يجيب على سؤال واحد: **كيف نعرف أن هذه المرحلة خلصت صح؟**

---

## Automated Tests

```bash
# شغّل tests المرحلة
php artisan test --filter=Phase[N]

# أو tests محدّدة
php artisan test tests/Feature/[FeatureName]Test.php
```

**Tests المطلوبة لهذه المرحلة:**
- [ ] `[TestClass]` — [ما يختبره]
- [ ] `[TestClass]` — [ما يختبره]

---

## Manual Checks (فحص يدوي)

### [قسم 1 — e.g. Auth Flow]
- [ ] [خطوة] → النتيجة المتوقّعة: [النتيجة]
- [ ] [خطوة] → النتيجة المتوقّعة: [النتيجة]

### [قسم 2 — e.g. Edge Cases]
- [ ] [حالة خاصة] → [النتيجة المتوقّعة]
- [ ] [حالة خطأ] → [يظهر الـ error الصح]

### الحالات الثلاث (لمراحل الـ UI)
- [ ] **Empty state:** الصفحة بدون بيانات تظهر [رسالة/illustration] مناسبة
- [ ] **Loading state:** يظهر skeleton/spinner أثناء التحميل
- [ ] **Error state:** خطأ الـ network يظهر رسالة واضحة + retry

---

## Database Checks

```sql
-- تحقّق من:
SELECT COUNT(*) FROM [table]; -- > 0
DESCRIBE [table]; -- الأعمدة صحيحة
```

---

## تخلص لما (Definition of Done)

المرحلة اكتملت لما:

- [ ] كل الـ automated tests تمرّ بدون أخطاء
- [ ] كل الـ manual checks تمّت
- [ ] لا console errors في المتصفّح
- [ ] لا php errors في `storage/logs/`
- [ ] [معيار خاص 1 بهذه المرحلة]
- [ ] [معيار خاص 2]
- [ ] TRACKER.md مُحدَّث: مهام المرحلة `[x]` أو `[~]`
