<?php
/** Read-only UI fixtures generated from the real admin templates with an in-memory DB. */
declare(strict_types=1);
if(PHP_SAPI!=='cli') { http_response_code(404); exit; }
define('GAWDEE_DB',':memory:');
require_once __DIR__.'/../includes/data.php';
$fixtureView = $argv[1] ?? 'storefront';
if(!in_array($fixtureView,['storefront','site-design','products','dashboard','orders','product-editor'],true)) throw new InvalidArgumentException('Unsupported preview.');
$db=gawdee_db();
$db->prepare("INSERT INTO users(name,email,password_hash,role)VALUES('Preview Administrator','preview@example.test',?,'admin')")->execute([password_hash(bin2hex(random_bytes(20)),PASSWORD_DEFAULT)]);
$_SESSION['admin_user_id']=(int)$db->lastInsertId();
$_GET=$fixtureView==='product-editor'?['view'=>'products','edit'=>'ghee-500']:['view'=>$fixtureView]; $_SERVER['REQUEST_METHOD']='GET';
ob_start(); require __DIR__.'/../admin/index.php'; $html=ob_get_clean();
$html=str_replace('<head>','<head><base href="/admin/">',$html);
$directory=__DIR__.'/../artifacts/ui-qa';
if(!is_dir($directory)) mkdir($directory,0775,true);
file_put_contents($directory.'/admin-'.$fixtureView.'.html',$html);
echo 'Generated isolated admin '.$fixtureView." preview.\n";
