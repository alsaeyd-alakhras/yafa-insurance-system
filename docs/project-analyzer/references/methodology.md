# المنهجية: الخط الكامل (7 خطوات)

> **⚠️ هذا الملف للوضع الكامل فقط** — لما المستخدم يطلب صراحة "خطط المشروع كامل". الوضع الافتراضي هو دور واحد حسب الـ Router في SKILL.md.
>
> **ربط الخطوات بالأدوار:**
> - (قبل الخطوة 1، للمنتجات الخاصة) رؤية + منافسين + أولويات → `roles/product-manager.md`
> - خطوات 1-3 (Clarify, Scope, Architect) → `roles/analyst.md`
> - خطوات 4-7 (Stage, Decompose, Assign, Persist) → `roles/project-manager.md`
>
> **إلزامي:** توقف واطلب موافقة عند الانتقال بين دور ودور.

## المبدأ: Right-Sizing في كل خطوة

في كل خطوة اسأل نفسك: "هل هذا مطلوب لهذا المشروع؟" إذا الجواب لأ، تخطّاه. المشروع يُملي الخطة — الخطة لا تُملي المشروع.

---

## الخطوة 1: Clarify — افهم قبل أي إشي

**المدخل:** ملفات في `inputs/` (word/pdf/md/txt) أو وصف نصي من المستخدم.

**العمل:**
1. حلّل كل ملف حسب نوعه (اقرأ الـ SKILL المناسب لو PDF/Word).
2. استخرج: ما طُلب، من العميل، ما السياق، ما القيود.
3. حدّد ما هو غامض أو ناقص.

**المخرج:** `docs/00-ANALYSIS.md` يحتوي:
- **ما فهمته**: ملخّص الفكرة/الطلب بكلماتك (3-5 جمل)
- **الافتراضات التي أخذتها**: اذكرها صريحاً
- **الأسئلة المجمّعة**: رقّمها، اجعلها محدّدة وقابلة للإجابة
- **الخطوة التالية**: "بعد إجاباتك سأنتج BRIEF وأبدأ التخطيط"

اقرأ [`rules/questions.md`](../rules/questions.md) لمعايير متى تسأل وكيف.

> لو الطلب واضح بما يكفي، يمكن دمج هذه الخطوة مع Scope وإنتاج BRIEF مباشرة مع ذكر الافتراضات.

---

## الخطوة 2: Scope — حدّد المطلوب وحدوده

**المدخل:** `docs/00-ANALYSIS.md` + إجابات المستخدم.

**العمل:**
1. حدّد بالضبط ما هو **مطلوب** (= سيُنفَّذ).
2. حدّد ما هو **غير مطلوب الآن** (= يذهب لـ `docs/future-enhancements.md` فقط).
3. حدّد نوع المشروع (saas / client-web / mobile / إلخ).
4. اجمع: deadline / budget / stack / فريق أو AI agents.

**المخرج:** `docs/BRIEF.md` — الوثيقة المرجعية لكل ما يلي. اقرأ قالبها من [`file-templates/BRIEF.md`](../file-templates/BRIEF.md).

> الـ BRIEF هو عقد التخطيط. كل مرحلة وكل مهمة تُقاس عليه. لو شيء مش بـ BRIEF → future-enhancements.

---

## الخطوة 3: Architect — بنية المشروع

**المدخل:** `docs/BRIEF.md`

**العمل:** (مخرجاته تدخل مباشرة في `BLUEPRINT.md`)

1. **Data Model / ERD أولي**: الكيانات الرئيسية والعلاقات بينها.
2. **قرارات تقنية**: tech stack، auth method، external integrations، أي قرار بنيوي.
3. **API Contracts** (للمشاريع الكبيرة): الـ endpoints الرئيسية فكرة أولية.
4. **نقاط غير محسومة**: سجّلها كـ research spikes (مهام Type=Research).

> لا تُفرِط بالتفاصيل هنا — ارسم الـ skeleton، التفاصيل تظهر بالتنفيذ.

---

## الخطوة 4: Stage — تحديد المراحل

**المدخل:** `docs/BRIEF.md` + نوع المشروع من `references/project-types/`

**العمل:**
1. افتح ملف النوع المناسب من [`references/project-types/`](../references/project-types/).
2. خذ الـ phase skeleton وطبّق **right-sizing**:
   - هل المشروع يحتاج مرحلة UX؟ (لو التصميم موجود → اشطبها)
   - هل المشروع يحتاج مرحلة UI؟ (API service → لأ)
   - هل الـ launch phase كاملة مطلوبة؟ (internal tool → مبسّطة)
3. رقّم المراحل تسلسلياً: `phase-0-planning`, `phase-1-ux`, `phase-2-ui`, `phase-3-setup`, إلخ.
4. لكل مرحلة: حدّد التخصصات المطلوبة فيها وتقدير أولي للأيام.

**مخرج:** جدول المراحل (يدخل BLUEPRINT).

---

## الخطوة 5: Decompose — تقسيم المهام

**المدخل:** المراحل المحدّدة + الـ BRIEF + الـ Architecture

**لكل مرحلة:**
1. حدّد المهام الكبيرة (مستوى N.X مثل `1.1`, `1.2`, `1.3`).
2. لكل مهمة كبيرة: أنتج ملف `NN-task-name.md` بالـ metadata الكامل (اقرأ قالب [`file-templates/task.md`](../file-templates/task.md)).
3. داخل كل ملف مهمة: فصّل الخطوات الفرعية (N.X.Y) كـ checkboxes.
4. تحقّق من atomicity (اقرأ [`rules/decomposition.md`](../rules/decomposition.md)).
5. قدّر الحجم والساعات (اقرأ [`rules/estimation.md`](../rules/estimation.md)).
6. سجّل الاعتماديات (اقرأ [`rules/risks-and-dependencies.md`](../rules/risks-and-dependencies.md)).

**قاعدة ذهبية:** كل مهمة يجب أن يكون لها "تخلص لما" واضح وقابل للقياس.

---

## الخطوة 6: Assign — التوزيع

**المدخل:** المهام مع تخصصاتها

**وضع التخصص (الافتراضي):**
- كل مهمة تحمل تاغ التخصص في metadata: `Backend`, `Frontend`, `UX`, `Design`, `Mobile`, `DevOps`, `QA`, `Full-stack`
- لو أكثر من تخصص: كسّر المهمة

**وضع الفريق (عند الطلب):**
- لو المستخدم أعطى أسماء الفريق → اقرأ [`rules/assignment.md`](../rules/assignment.md) لمنطق التوزيع
- by-discipline projection: لو طُلب → أنتج `docs/by-discipline/` (مجلد منظور تاني)

اقرأ التفاصيل في [`rules/assignment.md`](../rules/assignment.md).

---

## الخطوة 7: Persist — أنتج الملفات + نوشن

### A. الملفات المحلية (md)

أنتج بالترتيب:
1. `docs/00-ANALYSIS.md` (إذا لم يكن موجوداً)
2. `docs/BRIEF.md`
3. `research/README.md` + مجلدات البحث (فارغة للملء لاحقاً)
4. لكل مرحلة: مجلد `docs/phases/phase-N-name/` يحتوي:
   - ملفات المهام `01-task.md`, `02-task.md`, إلخ
   - `OVERVIEW.md` (اقرأ قالبه من [`file-templates/OVERVIEW.md`](../file-templates/OVERVIEW.md))
   - `TESTING.md` (اقرأ قالبه من [`file-templates/TESTING.md`](../file-templates/TESTING.md))
5. `docs/phases/BLUEPRINT.md`
6. `docs/phases/INSTRUCTIONS.md`
7. `docs/phases/TRACKER.md`
8. `docs/future-enhancements.md`

اقرأ [`references/output-structure.md`](output-structure.md) للبنية الكاملة.

### B. نوشن

اقرأ [`references/notion.md`](notion.md) للـ IDs والتعليمات الكاملة.

**الملخّص:**
1. أنشئ صف Project واحد في Projects DB.
2. أنشئ مهام مستوى `N.X` فقط (المهام الكبيرة) — مش المراحل، مش الـ sub-tasks.
3. كل مهمة باسم: `P[رقم المرحلة] · [N.X] — [اسم المهمة]`
4. اربطها بالمشروع عبر `Task - Relation`.
5. لا تلمس أي حقل مالي.
