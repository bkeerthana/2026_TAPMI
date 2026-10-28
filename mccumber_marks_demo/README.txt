McCumber Marks Laboratory — XAMPP
==================================
START HERE
1. Extract mccumber_marks_demo into C:\xampp\htdocs\
   Correct result: C:\xampp\htdocs\mccumber_marks_demo\index.php
2. Start Apache and MySQL in the XAMPP Control Panel.
3. Open http://localhost/mccumber_marks_demo/install.php
4. Click “Initialize / keep existing data”.
5. Open http://localhost/mccumber_marks_demo/
6. Open guide.html for classroom activities and answers.

DEMO ACCOUNTS
Faculty: faculty / Teach@123
Student Asha: student1 / Learn@123
Student Ravi: student2 / Learn@123
All names and records are fictional.

CONFIGURATION
config.php defaults: host 127.0.0.1, MySQL port 3306, database
mccumber_marks_demo, user root, blank password (common XAMPP default).
If your settings differ, edit config.php. Do not change your MySQL password
to blank merely for this lab. Setup needs CREATE DATABASE and table-write rights.
The app uses PHP 7.4+ with PDO MySQL; PHP 8.x is recommended.
If Apache uses port 8080, use http://localhost:8080/mccumber_marks_demo/.

DATABASE INITIALIZATION
install.php creates users, marks and audit_log. Passwords are hashed using
PHP password_hash. It inserts missing seed rows without overwriting existing
marks. Reset clears only this dedicated demo's three tables and reseeds them.
Do not point config.php at a database containing unrelated data.
Reset logs out the current browser; other sessions are not invalidated.

MODES (faculty changes mode in the application)
Protected: student sees own record; faculty edits; PHP validates 0–40 / 0–60.
Vulnerable component validation: faculty edits; only 0–100 individual values
and total <=100 are enforced. 70 + 10 is wrongly accepted.
Vulnerable record visibility: logged-in students see everyone's marks;
component validation remains enforced. Students still cannot edit marks.
Modes persist across logout in the SAME browser session. Select a mode as
faculty, log out, then log in as student1 in that same browser. A new private
window has a different session and defaults to protected mode.
Switching back to protected mode does NOT repair previously saved bad marks.
Correct the record as faculty or reset the demo database.

SCORING
Total = internal + examination. Distinction >=75; First class >=60;
Pass >=50; otherwise Below pass. These are fictional teaching rules,
not institutional assessment regulations. Total and grade are calculated
on display; database stores component marks. Passing is based on total only.

SAFETY AND LIMITATIONS
Local classroom application; PHP denies non-loopback requests. Open using
localhost or 127.0.0.1 on the XAMPP computer. Do not publish this demo.
Protected mode refers to the demonstrated controls, not production readiness.
No HTTPS automation, real student data, real banking, Burp, SQL injection,
or denial-of-service traffic is needed. POST is not encryption.
The local installer is deliberately accessible for classroom resets.
Audit entries are a demonstration, not tamper-proof evidence.

TROUBLESHOOTING
404: confirm folder nesting and Apache port.
Setup needed: start MySQL; run install.php; check config.php.
Access denied: check database credentials/permissions, not student credentials.
Could not find driver: enable PDO MySQL in the active PHP configuration;
restart Apache. XAMPP normally includes it.
Expired form: reload the page and submit again.
Port conflict: resolve the XAMPP service conflict or configure the relevant port.
Database issues in another app (such as DVWA) are independent of this package.

VERIFICATION
Source and archive checks performed during preparation. The authoring
runtime had no PHP/MySQL; end-to-end execution in XAMPP was not performed.
Use the smoke-check checklist in the instructor guide before class.
