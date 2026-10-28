<?php
session_start();
// Local classroom use only, including the intentionally vulnerable modes.
if (!in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1','::1'], true)) {
    http_response_code(403); exit('Open this demo on localhost.');
}
function h($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function db($withDatabase=true) {
    $c=require __DIR__.'/config.php';
    $dsn='mysql:host='.$c['host'].';port='.$c['port'].';charset=utf8mb4';
    if ($withDatabase) $dsn.=';dbname='.$c['database'];
    return new PDO($dsn,$c['username'],$c['password'],[
      PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,
      PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
}
function csrf() {
    if (!isset($_SESSION['csrf'])) $_SESSION['csrf']=bin2hex(random_bytes(24));
    return '<input type="hidden" name="csrf" value="'.h($_SESSION['csrf']).'">';
}
function checkcsrf() {
    if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '!')) {
        http_response_code(400); exit('Form expired. Reload the page.');
    }
}
function grade($total) {
    return $total>=75?'Distinction':($total>=60?'First class':($total>=50?'Pass':'Below pass'));
}
function page($title) {
 echo '<!doctype html><html lang="en"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>'.h($title).'</title><link rel="stylesheet" href="style.css"><body><main><p class="tag">McCumber classroom lab · Fictional records · Localhost only</p><h1>'.h($title).'</h1><nav><a href="index.php">Application</a><a href="guide.html">Activities & setup</a><a href="install.php">Database setup</a></nav>';
}
function endpage() { echo '</main></body></html>'; }
