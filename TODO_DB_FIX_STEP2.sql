-- فحص/إصلاح سريع لخطأ Duplicate key name 'code'
-- نفّذ هذا فقط بعد الخطأ، أو على قاعدة فارغة قبل تنفيذ ملفات الـ init.

-- إذا كان جدول diwan موجوداً وكان فيه index/constraint باسم code متكرر قد يسبب الخطأ.
-- عادة MySQL يمنع التكرار، لذلك غالباً يكفي تعديل تعريف CREATE في ملفات الـ SQL.
-- لكن هذا السكربت يساعد في تنظيف بقايا غير سليمة.

USE `queuing_system`;

-- احذف القيود ذات الاسم code إن وجدت
-- (لن يعمل إذا لم تكن موجودة بسبب الخطأ، لذلك نستخدم IF EXISTS حيثما أمكن)

-- MySQL لا يدعم DROP INDEX IF EXISTS لبعض الإصدارات بنفس الصيغة؛ نستخدم ديناميكية.
SET @tbl := 'diwan';

-- حذف unique index باسم code إن وجد
SET @sql := (
  SELECT IFNULL(
    GROUP_CONCAT(CONCAT('DROP INDEX `', index_name, '` ON `', table_name, '`;') SEPARATOR ' '),
    'SELECT 1;'
  )
  FROM information_schema.statistics
  WHERE table_schema = DATABASE()
    AND table_name = @tbl
    AND index_name = 'code'
    AND non_unique = 0
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- بعد ذلك نفّذ DB_INIT.sql أو queuing_system.sql المعدّل.

