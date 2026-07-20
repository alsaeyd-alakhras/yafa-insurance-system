# نوع المشروع: API Service

## متى تستخدمه
Laravel API فقط بدون frontend. يخدم mobile app، SPA خارجي، أو third-party. لا UI، لا views.

## Phase Skeleton

```
phase-0-planning         → API design (endpoints، resources، versioning)، auth method، data model
phase-1-setup            → Laravel، Sanctum/Passport، DB، env، API structure
phase-2-auth             → Auth endpoints، token management، refresh، roles
phase-3-core-endpoints   → الـ endpoints الرئيسية (CRUD + business logic)
phase-4-advanced         → Queues، events، webhooks، caching لو مطلوب
phase-5-testing-docs     → Feature tests، API documentation (Scribe/Postman)
phase-6-deploy           → VPS، Nginx، SSL، rate limiting، monitoring
```

## ملاحظات
- **لا UX، لا UI** — هذه المراحل غائبة كلياً
- الـ "design" هنا = API contract design (في phase-0)
- Versioning: قرّر في planning (`/api/v1/` أو header-based)
- Rate limiting: Laravel throttle middleware (في phase-6)
- Docs: Scribe أو Postman collection
