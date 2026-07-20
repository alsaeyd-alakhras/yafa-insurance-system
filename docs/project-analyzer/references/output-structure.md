# هيكل المخرجات: البنية الكاملة لمجلد المشروع

## البنية العامة

```
project-root/
│
├── README.md                        ← بوابة الدخول: وصف + خريطة + من وين تبدأ
├── CLAUDE.md                        ← قواعد العمل لـ Claude Code (تُقرأ كل جلسة)
├── STATUS.md                        ← وين واقفين هلأ (يُحدَّث آخر كل جلسة)
├── DECISIONS.md                     ← القرارات المحسومة + Pending
│
├── analysis/                        ← المصدر الخام (بينك وبين العميل)
│   ├── client-files/                ← ملفات العميل الأصلية (قراءة فقط، لا تعدّل)
│   ├── client-meetings/             ← ملاحظات الاجتماعات بالتاريخ
│   ├── requirements-raw.md          ← المتطلبات كما وصلت قبل الفلترة
│   └── questions-log.md             ← أسئلتك للعميل + أجوبته
│
├── research/                        ← مخرجات دور الـ PM
│   ├── PRODUCT.md                   ← رؤية + personas + قيمة
│   ├── COMPETITORS.md               ← تحليل المنافسين + الفجوات
│   ├── ROADMAP.md                   ← Now / Next / Later
│   └── market/                      ← أي بحث سوق إضافي
│
├── docs/                            ← الحقيقة المعتمدة (مخرجات Analyst + PjM)
│   ├── 00-ANALYSIS.md               ← فهم + افتراضات + أسئلة (بوابة التحليل)
│   ├── BRIEF.md                     ← العقد: scope + requirements + stories
│   ├── BLUEPRINT.md                 ← معمارية + data model + جدول المراحل + مخاطر
│   │
│   ├── phases/
│   │   ├── phase-0-planning/
│   │   ├── phase-1-ux/              ← (شرطية: فقط لو يحتاج UX جديدة)
│   │   ├── phase-2-ui/              ← (شرطية)
│   │   └── phase-N-[name]/
│   │       ├── 01-task-name.md      ← spec مهمة (metadata + خطوات + AC)
│   │       ├── 02-task-name.md
│   │       ├── OVERVIEW.md          ← هدف المرحلة + جدول مهامها + مخاطرها
│   │       └── TESTING.md           ← شرط إغلاق المرحلة + checklist
│   │
│   ├── INSTRUCTIONS.md              ← تعليمات Claude Code والمبرمجين
│   ├── TRACKER.md                   ← المصدر الرسمي لتتبع المهام (checkboxes)
│   ├── future-enhancements.md       ← خارج الـ scope (لا ينزل نوشن)
│   └── by-discipline/               ← (عند الطلب فقط) منظور التخصصات — مشتق
│
└── [ملفات الكود: app/, src/, ...]
```

## Right-sizing حسب حجم المشروع

| الحجم | الملفات |
|---|---|
| صغير (landing / موقع تسويقي) | README + CLAUDE + STATUS + TRACKER + BRIEF مختصر — بدون research/ ولا phases متعددة |
| متوسط (client-web / أداة) | كل شي ما عدا research/ (يكفي قسم قيمة داخل BRIEF) |
| كامل (SaaS / منتج خاص) | البنية كاملة |

---

## قواعد التسمية

### مجلدات المراحل
- Format: `phase-[رقم]-[اسم]` — الرقم من 0: `phase-0-planning`, `phase-1-ux`, `phase-3-setup`
- الاسم إنجليزي lowercase-hyphens

### ملفات المهام
- Format: `[رقمان]-[اسم].md` — مثال: `01-laravel-init.md`, `03-auth-scaffold.md`

### الترقيم في TRACKER
```
Phase N: N.1, N.2 ...        ← مهام رئيسية (تنزل نوشن)
         N.1.1, N.1.2 ...    ← sub-tasks (داخل ملف المهمة فقط)
```

---

## مسؤولية كل ملف + قالبه

| الملف | المسؤولية | القالب |
|---|---|---|
| `README.md` | بوابة الدخول | [`README-PROJECT.md`](../file-templates/README-PROJECT.md) |
| `CLAUDE.md` | قواعد جلسات Claude Code | [`CLAUDE-PROJECT.md`](../file-templates/CLAUDE-PROJECT.md) |
| `STATUS.md` | "وين واقفين" — يُحدَّث كل جلسة | [`STATUS.md`](../file-templates/STATUS.md) |
| `DECISIONS.md` | سجل القرارات — يمنع تكرار المرفوض | [`DECISIONS.md`](../file-templates/DECISIONS.md) |
| `research/PRODUCT.md` | رؤية المنتج | [`PRODUCT.md`](../file-templates/PRODUCT.md) |
| `research/COMPETITORS.md` | المنافسين والفجوات | [`COMPETITORS.md`](../file-templates/COMPETITORS.md) |
| `research/ROADMAP.md` | Now/Next/Later | [`ROADMAP.md`](../file-templates/ROADMAP.md) |
| `docs/00-ANALYSIS.md` | بوابة التحليل — لا تخطيط قبل إجاباتها | [`ANALYSIS.md`](../file-templates/ANALYSIS.md) |
| `docs/BRIEF.md` | العقد — كل مهمة تُقاس عليه | [`BRIEF.md`](../file-templates/BRIEF.md) |
| `docs/BLUEPRINT.md` | المرجع الأعلى: معمارية + مراحل + critical path | [`BLUEPRINT.md`](../file-templates/BLUEPRINT.md) |
| `phase-N/OVERVIEW.md` | شرح المرحلة | [`OVERVIEW.md`](../file-templates/OVERVIEW.md) |
| `phase-N/TESTING.md` | شرط "المرحلة خلصت" | [`TESTING.md`](../file-templates/TESTING.md) |
| `NN-task.md` | spec مهمة واحدة | [`task.md`](../file-templates/task.md) |
| `docs/INSTRUCTIONS.md` | تعليمات التنفيذ | [`INSTRUCTIONS.md`](../file-templates/INSTRUCTIONS.md) |
| `docs/TRACKER.md` | التتبع الرسمي | [`TRACKER.md`](../file-templates/TRACKER.md) |
| `docs/future-enhancements.md` | بكت الأفكار المحتجزة | [`FUTURE.md`](../file-templates/FUTURE.md) |

---

## ملاحظات مهمة

1. **`analysis/client-files/` قراءة فقط** — ملفات العميل لا تُعدَّل أبداً.
2. **الفصل الجوهري**: `analysis/` = خام وفوضوي، `docs/` = الحقيقة المعتمدة. التنفيذ يقرأ من `docs/` فقط ويرجع لـ `analysis/` عند الغموض.
3. **TRACKER = التتبع، STATUS = السياق الحي** — الاثنان يُحدَّثان بعد كل مهمة.
4. **`future-enhancements.md` لا ينزل نوشن.**
5. **`by-discipline/` مشتق** — لا يُحدَّث يدوياً.
