# PWA & Native packaging for QSYS

هذه التعليمات تشرح كيف تختبر ميزة PWA وكيف تغلف التطبيق كـ APK أو EXE لاحقًا.

اختبار PWA محليًا

1. شغّل الموقع على خادم محلي (يفضل عبر HTTPS). أسهل طريقة:
   - من داخل مجلد المشروع: `npx http-server -p 8080`
   - أو استخدم `php -S localhost:8000` إذا كنت تحتاج تشغيل PHP محليًا.
2. للوصول عبر HTTPS بسرعة استخدم ngrok: `ngrok http 80` أو `ngrok http 8000`، ثم افتح الرابط في متصفح Chrome على أندرويد.
3. افتح DevTools -> Application -> Manifest وService Workers للتأكد من التسجيل.
4. استخدم Lighthouse (DevTools) لتشغيل اختبار PWA والحصول على اقتراحات.

ملاحظات مهمة
- لعمل Install كتطبيق على Android/Windows يلزم HTTPS (أو localhost).
- صفحات الدخول/الموظف (`employee_login.php`, `employee.php`) مُستثناة من الكاش في الـ Service Worker لأسباب أمنية.

إنشاء حزمة Android (Capacitor)

1. جهّز build للـ frontend أو استخدم `server.url` لمطالبة التطبيق بتحميل الموقع المستضاف.
2. ثبّت: `npm install @capacitor/core @capacitor/cli --save`
3. `npx cap init qsys com.example.qsys` ثم عدّل `capacitor.config.json` ليكون `webDir` مناسب.
4. `npx cap add android` ثم `npx cap open android` لبناء APK عبر Android Studio.

إنشاء حزمة Desktop (Electron)

1. أنشئ مشروع Node بسيط مع `electron` وملف `main.js` يقوم بتحميل الموقع عبر `win.loadURL('https://...')`.
2. استخدم `electron-builder` لبناء exe/installer.

إذا رغبت أقدر أفتح Pull Request على الفرع feature/pwa مع كل التعديلات هذه وأضيف وصف اختباري + checklist ثم أضع رابط الـ PR هنا.
