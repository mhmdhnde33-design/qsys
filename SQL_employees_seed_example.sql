-- Example seed for employees (manual password hashes)
-- This file uses placeholders for password_hash.
-- Steps:
-- 1) Generate bcrypt hash for your desired password for each username:
--    http://localhost/queue-management-system/make_hash.php?password=YOUR_PASSWORD
-- 2) Replace the placeholders below with the generated hash.

-- NOTE: table employees must exist first (run SQL_employees.sql)

-- Example: one employee per counter id=1..3
-- Replace COUNTER_ID and USERNAME as needed.

-- Seed: 3 employees linked to counters 1,2,3
-- Use these usernames: emp_1, emp_2, emp_3
-- NOTE: password_hash must be generated via make_hash.php or auto_create_employees.php.

INSERT INTO employees (username, password_hash, counter_id, is_active) VALUES
  ('emp_1', 'PUT_HASH_HERE_FOR_PASSWORD_1', 1, 1),
  ('emp_2', 'PUT_HASH_HERE_FOR_PASSWORD_1', 2, 1),
  ('emp_3', 'PUT_HASH_HERE_FOR_PASSWORD_1', 3, 1)
ON DUPLICATE KEY UPDATE
  counter_id = VALUES(counter_id),
  password_hash = VALUES(password_hash),
  is_active = VALUES(is_active);


