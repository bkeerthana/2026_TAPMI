<?php
require __DIR__.'/common.php';
try { $d=db(); $d->query('SELECT 1 FROM marks LIMIT 1'); }
catch (Throwable $e) { page('Setup needed'); echo '<section>Start MySQL and <a href="install.php">initialize the database</a>. If already initialized, check config.php.</section>'; endpage(); exit; }
$msg=''; $processing='';
$mode=$_SESSION['mode'] ?? 'protected';
if ($_SERVER['REQUEST_METHOD']==='POST') {
 checkcsrf();
 $action=$_POST['action'] ?? '';
 if ($action==='login') {
  $s=$d->prepare('SELECT * FROM users WHERE username=?'); $s->execute([$_POST['username'] ?? '']); $u=$s->fetch();
  if ($u && password_verify($_POST['password'] ?? '',$u['password_hash'])) {
   session_regenerate_id(true); $_SESSION['user']=['username'=>$u['username'],'role'=>$u['role'],'student_id'=>$u['student_id']];
   $msg='Logged in.';
  } else $msg='Invalid login.';
 } elseif ($action==='logout') { unset($_SESSION['user']); $msg='Logged out.'; }
 elseif ($action==='mode') {
  if (($_SESSION['user']['role'] ?? '')!=='faculty') $msg='Only faculty can change classroom modes.';
  elseif (in_array($_POST['mode'] ?? '',['protected','validation','access'],true)) { $mode=$_POST['mode']; $_SESSION['mode']=$mode; $msg='Mode changed. It applies to this browser session only.'; }
 } elseif ($action==='save') {
  $u=$_SESSION['user'] ?? null;
  if (!$u || $u['role']!=='faculty') $msg='Blocked: only faculty can update marks in every mode.';
  else {
   $id=filter_var($_POST['id'] ?? '',FILTER_VALIDATE_INT);
   $in=filter_var($_POST['internal'] ?? '',FILTER_VALIDATE_INT);
   $ex=filter_var($_POST['exam'] ?? '',FILTER_VALIDATE_INT);
   if ($id===false || $in===false || $ex===false) $msg='Enter whole numbers.';
   elseif ($in<0 || $ex<0 || $in>100 || $ex>100 || $in+$ex>100) $msg='Blocked: each value must be nonnegative and the total must not exceed 100.';
   elseif ($mode!=='validation' && ($in>40 || $ex>60)) $msg='Blocked: internal must be 0–40 and examination must be 0–60.';
   else {
    try {
     $d->beginTransaction();
     $s=$d->prepare('SELECT * FROM marks WHERE id=? FOR UPDATE'); $s->execute([$id]); $old=$s->fetch();
     if (!$old) throw new Exception('Student not found.');
     $s=$d->prepare('UPDATE marks SET internal_mark=?,exam_mark=? WHERE id=?'); $s->execute([$in,$ex,$id]);
     $s=$d->prepare('INSERT INTO audit_log (actor,mode,student_id,old_internal,old_exam,new_internal,new_exam) VALUES (?,?,?,?,?,?,?)');
     $s->execute([$u['username'],$mode,$id,$old['internal_mark'],$old['exam_mark'],$in,$ex]);
     $d->commit();
     $msg='Saved to MySQL.'; $processing='Processing: '.$in.' + '.$ex.' = '.($in+$ex).' → '.grade($in+$ex).'. Storage: marks saved in MySQL; total and grade are computed when displayed.';
    } catch (Throwable $e) { if ($d->inTransaction()) $d->rollBack(); $msg='Save failed; no changes committed.'; }
   }
  }
 }
}
page('Student marks laboratory');
$labels=['protected'=>'Protected','validation'=>'Vulnerable: component validation','access'=>'Vulnerable: record visibility'];
echo '<p class="notice"><strong>Current mode: '.h($labels[$mode]).'</strong></p>';
if ($msg) echo '<p class="notice">'.h($msg).'</p>';
if ($processing) echo '<section>'.h($processing).'</section>';
$u=$_SESSION['user'] ?? null;
if (!$u) {
 echo '<section><h2>Log in</h2><form method="post">'.csrf().'<input type="hidden" name="action" value="login"><label>Username<input name="username" required></label><label>Password<input type="password" name="password" required></label><button>Log in</button></form><p>Faculty: faculty / Teach@123<br>Students: student1 or student2 / Learn@123</p></section>';
} else {
 echo '<p>Signed in: <strong>'.h($u['username']).'</strong> ('.h($u['role']).')</p><form method="post">'.csrf().'<button name="action" value="logout">Log out</button></form>';
 if ($u['role']==='faculty') {
  echo '<section><h2>Classroom mode</h2><form method="post">'.csrf().'<input type="hidden" name="action" value="mode"><select name="mode">';
  foreach ($labels as $key=>$label) echo '<option value="'.h($key).'" '.($mode===$key?'selected':'').'>'.h($label).'</option>';
  echo '</select><button>Apply mode</button></form><p>Validation mode checks only individual values 0–100 and total ≤100. Visibility mode lets a student view all records. Each mode changes one control.</p></section>';
 }
 $s=$d->prepare('SELECT * FROM marks WHERE id=?'); $s->execute([$u['student_id'] ?? 0]);
 $rows=($u['role']==='faculty' || $mode==='access')?$d->query('SELECT * FROM marks ORDER BY id')->fetchAll():$s->fetchAll();
 echo '<section><h2>Stored records</h2><table><tr><th>Student</th><th>Internal /40</th><th>Exam /60</th><th>Total /100</th><th>Grade</th></tr>';
 foreach ($rows as $r) { $t=$r['internal_mark']+$r['exam_mark']; echo '<tr><td>'.h($r['name']).'</td><td>'.h($r['internal_mark']).'</td><td>'.h($r['exam_mark']).'</td><td>'.h($t).'</td><td>'.h(grade($t)).'</td></tr>'; }
 echo '</table></section>';
 if ($u['role']==='faculty') {
  echo '<section><h2>Enter marks</h2><form method="post">'.csrf().'<input type="hidden" name="action" value="save"><label>Student<select name="id">';
  foreach ($rows as $r) echo '<option value="'.h($r['id']).'">'.h($r['name']).'</option>';
  echo '</select></label><label>Internal marks<input type="number" name="internal" step="1" required></label><label>Examination marks<input type="number" name="exam" step="1" required></label><button>Calculate and save</button></form><p>Component maximums are intentionally checked by PHP, so students can compare server responses using the same form.</p></section>';
  echo '<section><h2>Audit trail — latest 20 changes</h2><table><tr><th>Time</th><th>Actor / mode</th><th>Student ID</th><th>Before</th><th>After</th></tr>';
  foreach ($d->query('SELECT * FROM audit_log ORDER BY id DESC LIMIT 20') as $a) echo '<tr><td>'.h($a['changed_at']).'</td><td>'.h($a['actor'].' / '.$a['mode']).'</td><td>'.h($a['student_id']).'</td><td>'.h($a['old_internal'].' + '.$a['old_exam']).'</td><td>'.h($a['new_internal'].' + '.$a['new_exam']).'</td></tr>';
  echo '</table></section>';
 }
}
echo '<section><h2>Follow the information</h2><p><strong>Transmission:</strong> browser submits a POST request. <strong>Processing:</strong> PHP checks permissions and marks, then calculates the total and grade. <strong>Storage:</strong> MySQL retains marks and audit entries.</p><p>POST does not encrypt data. HTTPS is a separate transport control. Protected mode does not configure HTTPS.</p></section>';
endpage();
