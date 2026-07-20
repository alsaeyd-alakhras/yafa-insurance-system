# نوع المشروع: SaaS

## متى تستخدمه
تطبيق SaaS متكامل: multi-tenant أو single-tenant، فيه auth، subscription/billing، core domain logic، dashboard، reports.

مثال: Mutliq، Hisbat، أي SaaS من البداية.

## Right-Sizing للـ SaaS
- لو التصميم موجود مسبقاً → احذف phase-1-ux و phase-2-ui
- لو بدون billing → احذف أو خفّف phase-saas-launch
- لو MVP محدود → دمج phases أو قلّل tasks

---

## Phase Skeleton (الافتراضي)

```
phase-0-planning         → معمارية، data model، ERD، قرارات تقنية
phase-1-ux               → (شرطية) IA، user flows، wireframes
phase-2-ui               → (شرطية) design tokens، components، hi-fi
phase-3-setup            → Laravel init، Inertia/Vue، Tailwind، packages، DB/env، RTL
phase-4-auth-onboarding  → Auth، email verify، onboarding flow، roles/permissions
phase-5-core-domain      → الـ feature الأساسي للـ SaaS (المحور الرئيسي)
phase-6-business-layer   → الطبقة التجارية (clients، projects، teams، finances إن وجد)
phase-7-extra-features   → ميزات إضافية مطلوبة خارج الـ core
phase-8-reports-polish   → Reports، UX polish، empty states، notifications
phase-9-saas-launch      → Billing/Stripe، multi-tenant isolation، onboarding SaaS، deploy، monitoring
```

## تقدير أولي للأيام (rough guide)
```
phase-0-planning:         2-3 أيام
phase-1-ux:               3-5 أيام
phase-2-ui:               4-7 أيام
phase-3-setup:            2-3 أيام
phase-4-auth-onboarding:  4-6 أيام
phase-5-core-domain:      7-15 يوم (حسب التعقيد)
phase-6-business-layer:   5-10 أيام
phase-7-extra-features:   3-7 أيام
phase-8-reports-polish:   3-5 أيام
phase-9-saas-launch:      3-5 أيام
```

## تخصصات المراحل
- phase-0: Full-stack / Backend
- phase-1: UX
- phase-2: Design
- phase-3: Full-stack
- phase-4: Backend + Frontend
- phase-5+: Backend + Frontend (مقسّمة)
- phase-9: DevOps + Backend

## ملاحظات SaaS-specific
- Multi-tenancy: حدّد في planning هل tenant-per-schema أو single-schema + tenant_id
- Auth: Laravel Breeze/Jetstream/Fortify — قرّر في planning
- Billing: Cashier (Stripe) — أضفه في phase-9 فقط
- Queue: Jobs للـ notifications والـ emails — وضّح في setup
