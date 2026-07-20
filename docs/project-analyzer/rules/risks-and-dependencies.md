# المخاطر والاعتماديات

## المخاطر (Risks)

### أنواع المخاطر

| النوع | مثال | الحل |
|-------|------|------|
| **مجهول تقني** | "ما جرّبنا هذا الـ API من قبل" | Research Spike task |
| **تكامل خارجي** | Stripe، Twilio، Google Maps | مهمة integration مستقلة + research أولاً |
| **متطلب غامض** | "شيء مش واضح من طلب العميل" | اسأل في ANALYSIS.md |
| **تعقيد مقدَّر أقل** | feature بدت بسيطة لكنها معقّدة | أضف buffer (30%) في التقدير |
| **dependency خارجي** | approval من طرف ثالث، API key مش جاهز | اذكره كـ blocker في BLUEPRINT |

### كيف تسجّل المخاطر

**داخل كل مرحلة — في `OVERVIEW.md`:**
```markdown
## المخاطر
- ⚠️ integration مع X API: لم نجرّبه بعد → research spike مطلوب (مهمة 2.1)
- ⚠️ الـ real-time notifications: قرار معمارة (polling vs WebSocket) غير محسوم
```

**على مستوى المشروع — في `BLUEPRINT.md`:**
```markdown
## المخاطر العامة
- ⚠️ Multi-tenancy: قرار schema لم يُحسَم بعد (per-schema vs tenant_id)
- ⚠️ Stripe API: مش متاحة في كل المناطق — تحقّق من support
- ℹ️ ضغط timeline: Phase 5 و 6 قد يتداخلان — تحتاج مراجعة أسبوعية
```

### Research Spike Tasks
أي إشي غير محسوم تقنياً → مهمة بـ `Type = Research`:
```
- التخصص: Backend (أو التخصص المعني)
- الحجم: S (~2-4h)
- النوع: Research
- الهدف: اتخاذ قرار محدّد وتوثيقه
- معايير القبول: قرار مكتوب + approach محدّد + update في BLUEPRINT
```

---

## الاعتماديات (Dependencies)

### التسجيل في ملف المهمة

كل مهمة تعتمد على مهمة أخرى تُسجَّل في metadata:
```markdown
- يعتمد على: 0.2        ← مهمة من نفس المرحلة
- يعتمد على: P3·3.1     ← مهمة من مرحلة أخرى
- يعتمد على: 2.1, 2.3   ← أكثر من مهمة
```

### الـ Critical Path في BLUEPRINT.md

```markdown
## تسلسل الاعتماديات (Critical Path)
Phase 0 → Phase 3 (setup) → Phase 4 (auth) → Phase 5 (core) → Phase 9 (launch)

اعتماديات عابرة للمراحل:
- P5·5.1 (Data Model) يجب يخلص قبل أي CRUD في P5 أو P6
- P3·3.3 (Roles) يجب يخلص قبل أي authorization في P5+
- P2·2.1 (Design Tokens) يجب يخلص قبل بداية أي component في P3+
```

### علامات الـ Blocking في TRACKER.md
مهمة مبلوكة بسبب مهمة أخرى:
```markdown
- [ ] P5 · 5.3 — Transactions CRUD ⛔ (تنتظر 5.1 — Data Model)
```

---

## القاعدة العامة

**لكل phase:** قبل كتابة OVERVIEW.md، اسأل:
1. ما الذي يمكن أن يعوق هذه المرحلة؟
2. ما الذي يجب أن يخلص قبل بداية هذه المرحلة؟
3. هل في شيء غير محسوم تقنياً؟

ثلاثة أسئلة تكشف 90% من المخاطر والاعتماديات.
