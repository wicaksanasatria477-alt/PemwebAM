<?php
// Front controller: api/index.php?r=<modul>/<aksi>
define('SPADA', 1);
require __DIR__ . '/bootstrap.php';
foreach (glob(__DIR__ . '/routes/*.php') as $f) require $f;

$r = (string)($_GET['r'] ?? '');
if (!preg_match('#^[a-z_]+(/[a-z_]+)?$#', $r)) fail('Endpoint tidak valid.', 404);

$method = $_SERVER['REQUEST_METHOD'] === 'POST' ? 'POST' : 'GET';
if ($_SERVER['REQUEST_METHOD'] !== 'GET' && $_SERVER['REQUEST_METHOD'] !== 'POST') fail('Metode tidak didukung.', 405);

$fn = $GLOBALS['ROUTES'][$method][$r] ?? null;
if (!$fn) fail('Endpoint tidak ditemukan.', isset($GLOBALS['ROUTES'][$method === 'GET' ? 'POST' : 'GET'][$r]) ? 405 : 404);

if ($method === 'POST') csrf_check();   // semua perubahan data wajib membawa token CSRF
$fn();
