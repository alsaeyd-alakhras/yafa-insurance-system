# نوع المشروع: Landing / Marketing Site

## متى تستخدمه
موقع تسويقي، landing page، صفحة منتج، corporate site. تصميم تقيل، backend خفيف.

## Phase Skeleton

```
phase-0-planning         → goal، target audience، pages list، SEO requirements، CMS أو static؟
phase-1-ux               → user journey، page structure، wireframes (خصوصاً hero + CTA flow)
phase-2-ui               → brand design، design system، hi-fi كل صفحة
phase-3-setup            → Laravel أو static framework، Tailwind، SEO setup
phase-4-pages            → تطوير كل الصفحات
phase-5-cms-forms        → لو في CMS (FilamentPHP/Nova) أو contact forms أو newsletter
phase-6-seo-launch       → SEO meta، sitemap، speed optimization، deploy
```

## Right-Sizing
- صفحة واحدة (true landing) → phase-4 مبسّطة جداً، phase-5 ممكن تختفي
- بدون CMS → احذف phase-5
- تصميم موجود → احذف phase-1 و phase-2

## خصائص هذا النوع
- Performance أهم من features
- CSS/animations قد تأخذ وقتاً أطول من المتوقع
- SEO وspeed بيستاهلوا phase مستقلة
