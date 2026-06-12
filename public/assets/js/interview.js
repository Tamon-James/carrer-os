(function(){
  const root=document.querySelector('[data-interview]');if(!root)return;
  const title=root.querySelector('[data-draft-title]');
  const body=root.querySelector('[data-draft-body]');
  const state=root.querySelector('[data-save-state]');
  const idInput=root.querySelector('[data-draft-id]');
  const prep=root.querySelector('[data-prep-column]');
  const scheduler=root.querySelector('[data-scheduler]');
  const tentativeDialog=document.getElementById('tentative-event-dialog');
  let timer=null,last=body.value,lastTitle=title.value;
  async function save(){
    if(body.value===last&&title.value===lastTitle&&idInput.value)return true;
    state.textContent='保存中...';
    try{
      const response=await fetch('api/interview-draft.php',{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-Token':root.dataset.csrf},body:JSON.stringify({company_id:root.dataset.companyId||null,application_id:root.dataset.applicationId||null,title:title.value,body:body.value})});
      const data=await response.json();
      if(!response.ok)throw new Error(data.error||'保存失敗');
      idInput.value=data.draft_id;last=body.value;lastTitle=title.value;state.textContent='保存済み '+data.saved_at;return true;
    }catch(error){state.textContent='保存失敗';return false;}
  }
  function queue(){state.textContent='未保存';clearTimeout(timer);timer=setTimeout(save,1200);}
  function stamp(){const now=new Date();return '['+String(now.getHours()).padStart(2,'0')+':'+String(now.getMinutes()).padStart(2,'0')+'] ';}
  title.addEventListener('input',queue);body.addEventListener('input',queue);setInterval(save,10000);
  root.querySelector('[data-save-now]').addEventListener('click',save);
  root.querySelector('[data-insert-time]').addEventListener('click',function(){const start=body.selectionStart;body.setRangeText(stamp(),start,start,'end');body.focus();queue();});
  root.querySelector('[data-toggle-prep]').addEventListener('click',function(event){root.classList.toggle('prep-hidden');event.currentTarget.textContent=root.classList.contains('prep-hidden')?'確認欄を表示':'確認欄を隠す';});
  root.querySelector('[data-focus-note]').addEventListener('click',function(){root.classList.add('prep-hidden');body.focus();});
  root.querySelectorAll('[data-toggle-scheduler]').forEach(function(button){button.addEventListener('click',function(){scheduler.classList.toggle('open');});});
  root.querySelectorAll('[data-schedule-start]').forEach(function(slot){slot.addEventListener('click',function(){tentativeDialog.querySelector('[data-tentative-start]').value=slot.dataset.scheduleStart;tentativeDialog.querySelector('[data-tentative-end]').value=slot.dataset.scheduleEnd;tentativeDialog.showModal();});});
  const copySlots=root.querySelector('[data-copy-slots]');
  if(copySlots)copySlots.addEventListener('click',async function(){const text=Array.from(root.querySelectorAll('[data-schedule-label]')).slice(0,10).map(function(slot){return slot.dataset.scheduleLabel;}).join('\n');try{await navigator.clipboard.writeText(text);copySlots.textContent='コピー済み';setTimeout(function(){copySlots.textContent='候補をコピー';},1800);}catch(error){copySlots.textContent='コピー失敗';}});
  [root.querySelector('[data-scheduler-form]'),tentativeDialog.querySelector('[data-tentative-form]')].forEach(function(form){form.addEventListener('submit',async function(event){event.preventDefault();if(await save())form.submit();});});
  root.querySelector('[data-finalize-form]').addEventListener('submit',async function(event){event.preventDefault();if(await save())event.currentTarget.submit();});
  document.addEventListener('keydown',function(event){if((event.ctrlKey||event.metaKey)&&event.key.toLowerCase()==='s'){event.preventDefault();save();}});
  function tick(){root.querySelector('[data-clock]').textContent=new Date().toLocaleTimeString('ja-JP',{hour:'2-digit',minute:'2-digit'});}
  tick();setInterval(tick,1000);
})();
