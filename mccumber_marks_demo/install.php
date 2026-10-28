<?php
require __DIR__.'/common.php';
page('Initialize the database');
echo '<section><p>This creates a dedicated demo database and three fictional student records. Existing tables are preserved. Reset replaces only the demo marks, audit entries and users.</p>';
$msg='';
if ($_SERVER['REQUEST_METHOD']==='POST') {
 checkcsrf();
 try {
  $c=require __DIR__.'/config.php';
  if (!preg_match('/^[a-zA-Z0-9_]+$/',$c['database'])) throw new Exception('Invalid database name.');
  $d=db(false); $d->exec('CREATE DATABASE IF NOT EXISTS `'.$c['database'].'` CHARACTER SET utf8mb4');
  $d=db();
  $d->exec('CREATE TABLE IF NOT EXISTS users (username VARCHAR(40) PRIMARY KEY, password_hash VARCHAR(255) NOT NULL, role VARCHAR(20) NOT NULL, student_id INT NULL) ENGINE=InnoDB');
  $d->exec('CREATE TABLE IF NOT EXISTS marks (id INT PRIMARY KEY, name VARCHAR(80) NOT NULL, internal_mark INT NOT NULL, exam_mark INT NOT NULL) ENGINE=InnoDB');
  $d->exec('CREATE TABLE IF NOT EXISTS audit_log (id INT AUTO_INCREMENT PRIMARY KEY, changed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, actor VARCHAR(40), mode VARCHAR(20), student_id INT, old_internal INT, old_exam INT, new_internal INT, new_exam INT) ENGINE=InnoDB');
  $d->beginTransaction();
  if (isset($_POST['reset'])) {
   $d->exec('DELETE FROM audit_log'); $d->exec('DELETE FROM marks'); $d->exec('DELETE FROM users');
  }
  $s=$d->prepare('INSERT IGNORE INTO users VALUES (?,?,?,?)');
  foreach ([['faculty','Teach@123','faculty',null],['student1','Learn@123','student',1],['student2','Learn@123','student',2]] as $u) {
   $s->execute([$u[0],password_hash($u[1],PASSWORD_DEFAULT),$u[2],$u[3]]);
  }
  $s=$d->prepare('INSERT IGNORE INTO marks VALUES (?,?,?,?)');
  foreach ([[1,'Asha',30,50],[2,'Ravi',25,40],[3,'Meera',35,55]] as $r) $s->execute($r);
  $d->commit(); unset($_SESSION['user']);
  $msg='Database ready. Open the application and log in.';
 } catch (Throwable $e) {
  if (isset($d) && $d->inTransaction()) $d->rollBack();
  $msg='Setup failed. Start MySQL, check config.php and database-create permissions. Details: '.$e->getMessage();
 }
}
if ($msg) echo '<p class="notice">'.h($msg).'</p>';
echo '<form method="post">'.csrf().'<button>Initialize / keep existing data</button></form><form method="post">'.csrf().'<label><input type="checkbox" required> I understand reset replaces the demo data.</label><button name="reset" value="1">Reset demo data</button></form></section>';
endpage();
