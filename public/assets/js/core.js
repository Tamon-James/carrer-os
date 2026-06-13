document.addEventListener('click',function(event){
  const toggle=event.target.closest('[data-nav-toggle]');
  if(toggle) document.querySelector('[data-nav]')?.classList.toggle('open');
  const open=event.target.closest('[data-dialog-open]');
  if(open) document.getElementById(open.dataset.dialogOpen)?.showModal();
  const close=event.target.closest('[data-dialog-close]');
  if(close) close.closest('dialog')?.close();
});
document.querySelectorAll('dialog').forEach(function(dialog){
  dialog.addEventListener('click',function(event){if(event.target===dialog)dialog.close();});
});
const resourceType=document.querySelector('[data-resource-type]');
function syncResource(){
  if(!resourceType)return;
  const upload=resourceType.value==='upload';
  document.querySelector('[data-upload-field]')?.toggleAttribute('hidden',!upload);
  document.querySelector('[data-external-field]')?.toggleAttribute('hidden',upload);
}
resourceType?.addEventListener('change',syncResource);syncResource();
const stepType=document.querySelector('[data-step-type]');
const stepTitle=document.querySelector('[data-step-title]');
function syncStepTitle(){
  if(!stepType||!stepTitle)return;
  const briefing=stepType.value==='briefing';
  stepTitle.required=!briefing;
  stepTitle.placeholder=briefing?'説明会の場合は未入力でも登録できます':'ステップ名を入力してください';
}
stepType?.addEventListener('change',syncStepTitle);syncStepTitle();
