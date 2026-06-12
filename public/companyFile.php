<?php
declare(strict_types=1);
require __DIR__.'/bootstrap.php';
$uid=require_login();
ensure_user_defaults($uid);
$repo=new CareerRepository(db());
if (($_SERVER['REQUEST_METHOD']??'GET')==='POST') {
  csrf_verify();
  try {
    $action=post_string('action','add_company');
    if ($action==='add_master') {
      $repo->addMaster($uid,post_string('master_type'),post_string('master_name'));
      flash('success','分類を追加しました。'); redirect('companyFile.php#masters');
    }
    if ($action==='add_agent') {
      $repo->addAgent($uid,post_string('agent_name'));
      flash('success','エージェントを追加しました。'); redirect('companyFile.php#masters');
    }
    $name=post_string('name');
    if ($name==='') throw new RuntimeException('企業名を入力してください。');
    $url=post_string('corporate_url');
    if ($url!==''&&!is_https_url($url)) throw new RuntimeException('企業URLはHTTPSで入力してください。');
    $id=$repo->addCompany($uid,[
      'name'=>$name,'industry_id'=>post_id('industry_id'),'corporate_url'=>$url?:null,
      'business'=>post_string('business')?:null,'memo'=>post_string('memo')?:null,
    ]);
    flash('success','企業を登録しました。');
    redirect('company.php?id='.$id);
  } catch(Throwable $e) { flash('error',$e->getMessage()); redirect('companyFile.php'); }
}
$q=qp('q');
render('companies',['page_title'=>'企業・応募','active_nav'=>'companies','companies'=>$repo->companies($uid,$q),'masters'=>$repo->masters($uid),'q'=>$q]);
