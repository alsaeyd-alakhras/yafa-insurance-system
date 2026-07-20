# نوع المشروع: Internal Tool

## متى تستخدمه
أداة داخلية للفريق أو الشركة: dashboard، admin panel، reporting tool، automation UI. ليست موجّهة للعميل النهائي.

## Phase Skeleton

```
phase-0-planning         → متطلبات، users (داخلي)، data sources، integrations
phase-1-ux               → (مختصرة أو بدون) user flows بسيطة، wireframes خفيفة
phase-2-setup            → Laravel، Vue/Inertia، Tailwind، DB، auth بسيطة
phase-3-core             → الوظيفة الأساسية للأداة
phase-4-integrations     → ربط بـ data sources خارجية أو APIs داخلية
phase-5-deploy           → Deploy على VPS، basic monitoring
```

## Right-Sizing
- UX/UI: مبسّطة جداً أو بدون (المستخدمون داخليون وغير حساسين للـ aesthetics)
- Auth: يمكن الاعتماد على basic auth أو middleware بسيط
- Testing: أخف من المشاريع العامة
- لا billing، لا SaaS features
