# نوع المشروع: Automation / AI Tool

## متى تستخدمه
أداة أتمتة، بوت، AI agent، skill، script مُجدوَل. لا UI (أو UI بسيطة جداً). مثل: tech-news-weekly، AI skill، Telegram bot، webhook handler.

## Phase Skeleton

```
phase-0-planning         → الهدف الدقيق، المصادر (sources)، output format، delivery channel، جدول التشغيل
phase-1-setup            → Project structure، dependencies، env، logging
phase-2-data-sources     → Fetch/scrape/read من كل مصدر، parsing، normalization
phase-3-pipeline         → Processing logic، AI/LLM integration لو موجود، transformation
phase-4-delivery         → Output channel (Telegram/email/webhook/file)، formatting
phase-5-testing-monitoring → Tests، error handling، retry logic، cron/scheduler، monitoring
```

## ملاحظات
- **لا UX، لا UI** كلياً (أو CLI interface بسيط جداً)
- phase-0 يحدّد: triggered manually / scheduled / event-driven؟
- Error handling مهم جداً لأن لا يوجد مستخدم يرى الأخطاء
- Logging أساسي لـ debugging
- لو Telegram → bot token + getUpdates في phase-4

## مثال: tech-news-weekly
```
phase-0: تحديد المصادر، output template، Telegram setup
phase-1: Python/PHP setup، env، logging
phase-2: fetch من كل مصدر، RSS parsing، web scraping
phase-3: تصفية + ترتيب + AI summary (Anthropic API)
phase-4: Telegram delivery بـ template محدّد
phase-5: cron كل أحد صباحاً، error alerts
```
