# قواعد التوزيع: التخصص والفريق

## وضعان للتوزيع

### الوضع الافتراضي: توزيع بالتخصص
لما لا يوجد فريق محدّد (أو الشغل بـ AI agents).

كل مهمة تحمل تاغ تخصص واحد في metadata:
```
- التخصص: Backend
```

التخصصات المتاحة:
```
Backend       → Laravel، API، DB، queues، migrations
Frontend      → Vue، Inertia، components، Tailwind UI
Full-stack    → مهمة تجمع طبقتين بشكل لا يُقسَم منطقياً
UX            → user flows، wireframes، IA
Design        → visual design، design system، Figma
Mobile        → NativePHP، screens، device features
DevOps        → deployment، server، CI/CD، monitoring
QA            → testing، quality assurance، bug verification
```

**قاعدة:** مهمة بتخصصين → كسّرها لمهمتين.
**استثناء وحيد:** Full-stack لما الفصل مصطنع (مثل: صفحة بسيطة لا يستحق فصل backend/frontend).

---

### وضع الفريق: توزيع بالاسم

يُفعَّل فقط لما يقول المستخدم "وزّع على الفريق" ويعطي الأسماء.

**الخطوات:**
1. اقرأ Team DB لتعرف: الاسم، Role، Status (Active فقط)، Workload indicator.
2. طابق تخصص المهمة مع Role:
   - `Backend` ← Role: Backend أو Full-stack
   - `Frontend` ← Role: Frontend أو Full-stack
   - `Design/UX` ← Role: Designer
   - `Full-stack` ← Role: Full-stack (الأفضل) أو من له أقل حمل
3. لو تخصصان يصلحان، اختر صاحب `Workload indicator` الأقل.
4. أضف `Assigned To` في Notion Tasks DB.

---

## By-Discipline Projection (عند الطلب)

لما يطلب المستخدم "اعملي توزيع حسب التخصص" أو "بدي ملف لكل تخصص":

1. أنشئ `docs/by-discipline/` مجلد.
2. لكل تخصص موجود في المشروع، أنشئ ملف: `backend.md`، `frontend.md`، إلخ.
3. كل ملف يحتوي: قائمة tasks خاصة بهذا التخصص، بنفس الترقيم الأصلي (`P3·3.1`, `P4·4.2`).
4. **مهم:** هذا مجلد مشتق (projection) — المصدر الرسمي هو TRACKER.md. لا تعدّل by-discipline يدوياً.

### مثال `docs/by-discipline/backend.md`
```markdown
# Backend Tasks

## Phase 3 — Auth & Onboarding
- [ ] P3 · 3.1 — Auth: Login/Register/Email Verify (~6h)
- [ ] P3 · 3.2 — Onboarding API endpoint (~2h)
- [ ] P3 · 3.3 — Roles & Permissions (Spatie) (~4h)

## Phase 4 — Core Domain
- [ ] P4 · 4.1 — Projects CRUD API (~6h)
- [ ] P4 · 4.2 — Tasks API + relationships (~5h)
```
