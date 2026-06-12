(function(){
  const dayDialog=document.getElementById('day-dialog');
  const eventDialog=document.getElementById('event-dialog');
  if(!dayDialog||!eventDialog)return;
  const start=eventDialog.querySelector('[data-event-start]');
  const end=eventDialog.querySelector('[data-event-end]');
  function openEvent(startValue,endValue){
    start.value=startValue||'';
    end.value=endValue||'';
    eventDialog.showModal();
  }
  document.addEventListener('click',function(event){
    const day=event.target.closest('[data-day-open]');
    if(day){
      const date=day.dataset.dayOpen;
      dayDialog.querySelector('[data-day-title]').textContent=date+' の予定';
      let found=false;
      dayDialog.querySelectorAll('[data-day-panel]').forEach(function(panel){const show=panel.dataset.dayPanel===date;panel.hidden=!show;if(show)found=true;});
      dayDialog.querySelector('[data-day-empty]').hidden=found;
      dayDialog.querySelector('[data-day-add]').dataset.date=date;
      dayDialog.showModal();
    }
    const add=event.target.closest('[data-day-add]');
    if(add){dayDialog.close();openEvent(add.dataset.date+'T09:00',add.dataset.date+'T10:00');}
    if(event.target.closest('[data-open-event]'))openEvent('','');
    const slot=event.target.closest('[data-slot-start]');
    if(slot)openEvent(slot.dataset.slotStart,slot.dataset.slotEnd);
  });
})();
