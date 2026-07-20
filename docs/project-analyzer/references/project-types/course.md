# نوع المشروع: Course / منصة تعليمية

## متى تستخدمه
منصة تعليمية، كورس أونلاين، LMS. مثل JinnEdu. يشمل: محتوى، دروس، تقدّم الطالب، اختبارات، شهادات.

## Phase Skeleton

```
phase-0-planning         → هيكل المحتوى، learning path، data model (courses/lessons/progress)، auth roles
phase-1-ux               → student journey، instructor flow، wireframes
phase-2-ui               → design system، components (video player، quiz، progress bar)
phase-3-setup            → Laravel، Vue/Inertia، media storage (S3/local)، env
phase-4-auth-roles       → Auth، student/instructor/admin roles
phase-5-content-mgmt     → Courses، Sections، Lessons CRUD، media upload
phase-6-learning         → Enrollment، progress tracking، quizzes، completion
phase-7-extras           → Certificates، payments (لو مطلوب)، notifications
phase-8-launch           → Testing، deploy، CDN للميديا، monitoring
```

## Right-Sizing
- كورس بدون payments → احذف من phase-7
- بدون quizzes → احذف من phase-6
- محتوى نصي فقط بدون video → خفّف phase-5
