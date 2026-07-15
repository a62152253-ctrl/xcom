<?php
require_once __DIR__ . '/../includes/header.php';
$userLang=$_SESSION['user_language']??'pl';
?>

<div class="page-header calendar-header">
<div>
<h1 class="page-title"><i class="fa-solid fa-calendar-days"></i> Kalendarz</h1>
<p class="page-description">Zarządzaj terminami zadań, spotkaniami i wydarzeniami.</p>
</div>

<button class="btn btn-primary" onclick="openEventModal()">
<i class="fa-solid fa-plus"></i> Nowe wydarzenie
</button>
</div>

<div class="card calendar-card">
<div id="calendar"></div>
</div>

<div class="modal-overlay" id="event-modal">
<div class="modal-window">

<div class="modal-header">
<h2 class="modal-title">Dodaj wydarzenie</h2>
<button class="modal-close" onclick="closeEventModal()">&times;</button>
</div>

<div class="modal-body">

<div class="form-group">
<label>Tytuł</label>
<input id="event-title" class="form-control">
</div>

<div class="form-group">
<label>Opis</label>
<textarea id="event-description" class="form-control"></textarea>
</div>

<div class="form-row">
<div class="form-group">
<label>Start</label>
<input id="event-start" type="datetime-local" class="form-control">
</div>

<div class="form-group">
<label>Koniec</label>
<input id="event-end" type="datetime-local" class="form-control">
</div>
</div>

<div class="form-group">
<label>Kolor</label>
<input id="event-color" type="color" value="#06b6d4" class="form-control">
</div>

</div>

<div class="modal-footer">

<button id="delete-event" class="btn btn-danger" onclick="deleteEvent()">Usuń</button>

<button class="btn btn-secondary" onclick="closeEventModal()">Anuluj</button>

<button class="btn btn-primary" onclick="saveEvent()">Zapisz</button>

</div>

</div>
</div>


<script>
let calendar,editingEventId=null;
const $=id=>document.getElementById(id);

document.addEventListener('DOMContentLoaded',()=>{

calendar=new FullCalendar.Calendar($('calendar'),{
initialView:'dayGridMonth',
locale:<?=json_encode($userLang)?>,
timeZone:'Europe/Warsaw',
height:'auto',
editable:true,
selectable:true,
dayMaxEvents:true,

headerToolbar:{
left:'prev,next today',
center:'title',
right:'dayGridMonth,timeGridWeek,timeGridDay'
},

events:'/api/calendar.php',

dateClick(info){
openEventModal();
$('event-start').value=info.dateStr+'T09:00';
},

select(info){
openEventModal();
$('event-start').value=info.startStr.substring(0,16);
$('event-end').value=info.endStr.substring(0,16);
},

eventClick(info){
openEventModal(info.event);
},

eventDrop:updateEventDate,
eventResize:updateEventDate

});

calendar.render();

});


function openEventModal(event=null){

editingEventId=event?.id??null;

$('.modal-title').textContent=event?'Edytuj wydarzenie':'Dodaj wydarzenie';

$('delete-event').style.display=event?'block':'none';

if(event){

$('event-title').value=event.title;
$('event-description').value=event.extendedProps.description??'';
$('event-color').value=event.backgroundColor??'#06b6d4';
$('event-start').value=formatDate(event.start);
$('event-end').value=event.end?formatDate(event.end):'';

}else clearForm();

$('event-modal').classList.add('active');

}


function closeEventModal(){
$('event-modal').classList.remove('active');
}


function clearForm(){

$('event-title').value='';
$('event-description').value='';
$('event-start').value='';
$('event-end').value='';
$('event-color').value='#06b6d4';

}


function formatDate(date){
return new Date(date).toISOString().slice(0,16);
}


async function saveEvent(){

let data={
id:editingEventId,
title:$('event-title').value.trim(),
description:$('event-description').value,
start_time:$('event-start').value,
end_time:$('event-end').value,
color:$('event-color').value
};

if(!data.title||!data.start_time)
return alert('Podaj tytuł oraz datę');

if(data.end_time&&data.end_time<data.start_time)
return alert('Błędny zakres dat');

let action=editingEventId?'update':'create';

let res=await fetch('/api/calendar.php?action='+action,{
method:'POST',
headers:{'Content-Type':'application/json'},
body:JSON.stringify(data)
});

let json=await res.json();

if(json.success){
closeEventModal();
calendar.refetchEvents();
}else alert(json.error??'Błąd');

}


async function deleteEvent(){

if(!editingEventId)return;

if(!confirm('Usunąć wydarzenie?'))return;

await fetch('/api/calendar.php?action=delete',{
method:'POST',
headers:{'Content-Type':'application/json'},
body:JSON.stringify({id:editingEventId})
});

closeEventModal();
calendar.refetchEvents();

}


async function updateEventDate(info){

let res=await fetch('/api/calendar.php?action=update_date',{
method:'POST',
headers:{'Content-Type':'application/json'},
body:JSON.stringify({
id:info.event.id,
start:info.event.start.toISOString(),
end:info.event.end?.toISOString()
})
});

let json=await res.json();

if(!json.success)info.revert();

}


document.addEventListener('keydown',e=>{
if(e.key==='Escape')closeEventModal();
});
</script>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>