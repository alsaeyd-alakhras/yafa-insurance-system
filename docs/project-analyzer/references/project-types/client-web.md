# نوع المشروع: Client Web

## متى تستخدمه
تطبيق ويب لعميل: موقع مع backend، لوحة تحكم، CRUD، integrations. ليس SaaS (مش multi-tenant/billing)، ليس landing page بسيط.

## Phase Skeleton

```
phase-0-planning         → متطلبات، data model، قرارات تقنية، auth method
phase-1-ux               → (شرطية) user flows، wireframes
phase-2-ui               → (شرطية) design system، components، screens
phase-3-setup            → Laravel، Vue/Inertia، Tailwind، env، DB
phase-4-auth             → Auth + roles/permissions حسب الطلب
phase-5-core             → الـ core features المطلوبة
phase-6-integrations     → APIs خارجية، payment، email، storage، إلخ
phase-7-polish-launch    → UX polish، testing، deploy، handoff
```

## تقدير أولي
```
phase-0: 1-2 أيام
phase-1: 2-4 أيام
phase-2: 3-6 أيام
phase-3: 1-2 أيام
phase-4: 2-4 أيام
phase-5: 5-15 يوم (حسب الـ scope)
phase-6: 2-5 أيام
phase-7: 2-4 أيام
```

## Right-Sizing
- موقع بسيط بدون dashboard → احذف phase-4 أو خفّفها
- العميل عنده design جاهز → احذف phase-1 و phase-2
- لا integrations → احذف phase-6
