# مراحل التصميم: UX + UI

## متى تضيف مراحل التصميم؟

| النوع | UX | UI |
|-------|----|----|
| saas | ✅ إذا التصميم غير جاهز | ✅ إذا لا design system |
| client-web | ✅ عادةً | ✅ عادةً |
| mobile-nativephp | ✅ | ✅ |
| landing-marketing | ✅ مختصرة | ✅ |
| course | ✅ مختصرة | ✅ |
| internal-tool | ⚠️ مختصرة جداً أو بدون | ⚠️ ممكن |
| api-service | ❌ | ❌ |
| automation-ai-tool | ❌ | ❌ |

**القاعدة الأساسية:** لو العميل جاء بتصميم جاهز → تخطّى مرحلة UX و/أو UI. اذكر هذا في BRIEF.

---

## مسار التصميم التبعك: Stitch → Figma

```
Google Stitch  →  تصميم أولي سريع (Lo-fi / Concept)
      ↓
   Figma        →  تحسين + design system + handoff للكود
      ↓
Figma MCP       →  كلاود كود يقرأ التصميم مباشرة → كود Vue/Tailwind
```

---

## Phase UX: ما تنتجه

### مهام المرحلة

**01 — IA & Sitemap**
- خريطة الموقع/التطبيق الكاملة (كل screen / page)
- هيكل المعلومات وكيف تتنظّم
- التسلسل الهرمي للمحتوى

**02 — User Flows & Task Flows**
- Flow لكل user journey رئيسي (e.g. onboarding, checkout, core action)
- كل flow: start → end مع decision points وcases الخطأ
- تحديد **كل الحالات**: default, empty, loading, error, success

**03 — Wireframes (Lo-fi)**
- Skeleton لكل screen رئيسي
- Layout وتوزيع العناصر بدون ألوان/جماليات
- تركيز على: navigation، hierarchy، المنطق
- أداة: Stitch (للتوليد السريع)، Figma (للتحسين)

**04 — Usability Notes**
- نقاط تحتاج قرار (هل نضع X هنا أو هناك؟)
- edge cases مهمة للـ UI
- accessibility notes

### ما يدخل TESTING.md للـ UX
- هل جميع الـ flows منطقية ومترابطة؟
- هل الـ empty/loading/error states موثّقة لكل screen؟
- هل لا يوجد dead ends في الـ flows؟
- هل الـ sitemap يغطّي كل ما في الـ BRIEF؟

---

## Phase UI: ما تنتجه

### مهام المرحلة

**01 — Design Tokens & Design System**
- تعريف الـ tokens في Figma Variables:
  ```
  Primitives → Semantic → Component
  color/primary → bg-primary → btn-primary-bg
  ```
- يجب أن تتطابق مع Tailwind CSS variables تبعتك:
  - نفس الأسماء (color-primary → `--color-primary`)
  - نفس قيم spacing (`4 / 8 / 12 / 16...`)
  - typography scale
- Design system: قواعد الألوان، Typography، spacing، border radius، shadows

**02 — Components Library**
- كل component رئيسي: Button, Input, Card, Modal, Nav, إلخ
- Auto-layout على كل شيء
- Variants واضحة: size (sm/md/lg) + state (default/hover/disabled/error/loading)
- أسماء Variants = أسماء Tailwind classes تبعتك

**03 — Hi-Fi Screens**
- كل screen رئيسي بالتصميم الكامل
- كل الحالات: default + empty + loading + error + success
- Responsive: desktop + mobile (لو مطلوب)
- صفحة مخصّصة في Figma للـ developer reference:
  - design tokens الكاملة
  - typography scale مع CSS values
  - spacing system

**04 — Prototype (اختياري)**
- Clickable prototype للـ user flows الرئيسية
- للتحقق من المنطق قبل الكود

### ما يدخل TESTING.md للـ UI
- هل كل component فيه Auto-layout؟
- هل Variants مسمّاة بنفس Tailwind?
- هل الـ tokens متطابقة مع ما هو في Tailwind config؟
- هل كل الحالات (empty/loading/error) مصمّمة؟
- هل صفحة developer reference موجودة في Figma؟

---

## Figma MCP: الـ Handoff الذكي

بعد اكتمال مرحلة UI، المراحل البرمجية تستخدم Figma MCP مباشرة:

**كيف يعمل:**
1. Claude Code يتصل بـ Figma MCP (موصّل مسبقاً).
2. يقرأ الـ nodes والـ components والـ variables من Figma.
3. يُترجم مباشرة لـ Vue components + Tailwind classes.

**شرط النجاح (اكتبه في INSTRUCTIONS.md):**
- Auto-layout على كل component
- Variables = Tailwind tokens (نفس الاسم)
- Component names واضحة تعكس الـ domain

**في ملفات المهام البرمجية (حين تشير للتصميم):**
```
## مرجع التصميم
- Figma Frame: [اسم الـ frame / رابط]
- Figma Components: [اسم المكوّنات المستخدمة]
```

---

## هيكل ملفات مرحلة UX/UI

```
phase-1-ux/
├── 01-ia-sitemap.md        ← خريطة المعلومات
├── 02-user-flows.md        ← الـ flows + decision points
├── 03-wireframes.md        ← مرجع الـ wireframes + screenshots/links
├── OVERVIEW.md             ← شرح المرحلة + checklist UX
└── TESTING.md              ← تحقق UX

phase-2-ui/
├── 01-design-system.md     ← tokens + style guide + Figma link
├── 02-components.md        ← قائمة Components + variants + Figma link
├── 03-screens.md           ← قائمة الـ screens + حالاتها + Figma links
├── OVERVIEW.md
└── TESTING.md
```
