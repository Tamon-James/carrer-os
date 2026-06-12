<?php
declare(strict_types=1);
require __DIR__.'/bootstrap.php';
$uid=require_login();
$repo=new CareerRepository(db());
$companyId=(int)qp('company_id','0');
if (!$repo->company($uid,$companyId)) { http_response_code(404); exit('企業が見つかりません。'); }
if (($_SERVER['REQUEST_METHOD']??'GET')!=='POST') redirect('company.php?id='.$companyId);
csrf_verify();
try {
  $type=post_string('resource_type');
  $base=['application_id'=>post_id('application_id'),'resource_type'=>$type,'category'=>post_string('category')?:'other','display_name'=>post_string('display_name')?:'資料','original_name'=>null,'stored_name'=>null,'mime_type'=>null,'size_bytes'=>null,'external_url'=>null];
  if ($type==='upload') $base=array_merge($base,(new ResourceService())->storeUpload($uid,$_FILES['document']??[]));
  elseif ($type==='external') {
    $url=post_string('external_url');
    if (!is_https_url($url)) throw new RuntimeException('外部リンクはHTTPS URLで入力してください。');
    $base['external_url']=$url;
  } else throw new RuntimeException('資料の種類が不正です。');
  $repo->addResource($uid,$companyId,$base);
  flash('success','資料を登録しました。');
} catch(Throwable $e) { flash('error',$e->getMessage()); }
redirect('company.php?id='.$companyId.'#resources');

