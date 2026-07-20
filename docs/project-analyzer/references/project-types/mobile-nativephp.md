# نوع المشروع: Mobile (NativePHP)

## متى تستخدمه
تطبيق موبايل بـ NativePHP + Laravel backend. iOS و/أو Android.

## Phase Skeleton

```
phase-0-planning         → متطلبات، screens list، data model، API design، platform (iOS/Android/both)
phase-1-ux               → user flows موبايل، wireframes (mobile-first)
phase-2-ui               → design system موبايل، components، screens
phase-3-backend-setup    → Laravel API، auth (Sanctum/Passport)، DB، env
phase-4-native-setup     → NativePHP init، project structure، navigation، env
phase-5-auth             → Login/Register، token management، biometric لو مطلوب
phase-6-core-screens     → الـ screens الرئيسية + API integration
phase-7-device-features  → camera، push notifications، offline، GPS لو مطلوب
phase-8-testing-launch   → Testing، App Store/Play Store، deploy backend، monitoring
```

## تقدير أولي
```
phase-0: 2-3 أيام
phase-1: 3-5 أيام
phase-2: 4-7 أيام
phase-3: 2-3 أيام
phase-4: 2-3 أيام
phase-5: 2-3 أيام
phase-6: 7-15 يوم
phase-7: 3-7 أيام
phase-8: 3-5 أيام
```

## تخصصات
- Backend: phase-3, phase-5 (API side)
- Mobile: phase-4, phase-5 (app side), phase-6, phase-7
- Design: phase-2 (mobile-specific guidelines: touch targets, safe areas, gestures)

## ملاحظات NativePHP-specific
- حدّد في planning: iOS only / Android only / كلاهما (يؤثر على التقدير)
- Safe areas، notch handling في phase-4
- Push notifications: FCM setup في phase-7
