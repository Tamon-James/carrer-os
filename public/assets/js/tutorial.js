(function(){
  const coach=document.querySelector('[data-tutorial-coach]');
  if(!coach)return;
  let target=null;
  function highlight(){
    document.querySelectorAll('.tutorial-highlight').forEach(el=>el.classList.remove('tutorial-highlight'));
    target=document.querySelector('[data-tutorial-target="'+coach.dataset.target+'"]');
    if(!target)return;
    target.classList.add('tutorial-highlight');
    target.scrollIntoView({behavior:'smooth',block:'center'});
  }
  function activate(){
    highlight();
    target?.focus({preventScroll:true});
    if(target?.matches('button,[href]'))target.click();
    const dialogId=coach.dataset.dialog;
    if(dialogId){
      window.setTimeout(function(){
        const dialog=document.getElementById(dialogId);
        if(dialog?.open){
          target?.classList.remove('tutorial-highlight');
          dialog.classList.add('tutorial-highlight');
          coach.querySelector('p').textContent='入力欄を埋めて、下部の「保存」をクリックすると次のステップへ進みます。';
          coach.querySelector('[data-tutorial-focus]').textContent='入力欄へ移動';
          coach.dataset.target='';
          dialog.querySelector('input:not([type="hidden"]),textarea,select')?.focus();
        }
      },150);
    }
  }
  coach.querySelector('[data-tutorial-focus]')?.addEventListener('click',activate);
  highlight();
})();
