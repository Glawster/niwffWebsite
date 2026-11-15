/* Progressive enhancement for club venue and weekly session entry. */
"use strict";
document.addEventListener("DOMContentLoaded",()=>{
 const picker=document.getElementById("event-venue-picker"),choice=document.getElementById("event-venue-choice"),eventVenue=document.getElementById("venue");
 if(picker&&choice&&eventVenue){
  picker.hidden=false;
  choice.addEventListener("change",()=>{
   if(choice.value!=="") eventVenue.value=choice.value;
   else if(Array.from(choice.options).some(o=>o.value!==""&&o.value===eventVenue.value)) eventVenue.value="";
   eventVenue.focus();
  });
  eventVenue.addEventListener("input",()=>{
   choice.value=Array.from(choice.options).some(o=>o.value===eventVenue.value)?eventVenue.value:"";
  });
 }
 const editor=document.getElementById("schedule-editor"),hidden=document.getElementById("schedule-data");
 const venueText=document.getElementById("venue"),sessionText=document.getElementById("sessions");
 if(!editor||!hidden||!venueText||!sessionText)return;
 const form=editor.closest("form"),legacy=[venueText.closest(".field"),sessionText.closest(".field")];
 const optional=editor.dataset.optional==="yes";
 const hadText=!!(venueText.value.trim()||sessionText.value.trim());
 const days=["Monday","Tuesday","Wednesday","Thursday","Friday","Saturday","Sunday"];
 let data={venues:[],sessions:[]};
 try{const saved=JSON.parse(hidden.value);if(saved&&Array.isArray(saved.venues)&&Array.isArray(saved.sessions))data=saved;}catch(e){}
 let active=false;
 const el=(tag,attrs={},text="")=>{const x=document.createElement(tag);for(const [k,v] of Object.entries(attrs))x.setAttribute(k,v);if(text)x.textContent=text;return x;};
 const button=(label,fn)=>{const b=el("button",{type:"button",class:label.startsWith("Remove")?"btn btn-outline schedule-remove":"btn btn-outline"},label);b.addEventListener("click",fn);return b;};
 const field=(label,input)=>{const wrap=el("label",{class:"schedule-field field"});wrap.append(el("span",{},label),input);return wrap;};
 const input=(type,value,placeholder="",max=0)=>{const x=el("input",{type,placeholder});x.value=value||"";x.required=!optional;if(max)x.maxLength=max;if(type==="time")x.step="60";return x;};
 const select=(items,value)=>{const x=el("select");x.required=!optional;for(const [val,label] of items)x.append(el("option",{value:String(val)},label));x.value=String(value??"");return x;};
 const toolbar=el("div",{class:"schedule-toolbar button-row"}),body=el("div",{class:"schedule-body"});
 editor.append(toolbar,body);
 const sync=()=>{hidden.value=active?JSON.stringify(data):"";};
 const render=()=>{
  toolbar.replaceChildren(button(active?"Use free-text entry instead":"Use guided venue and session entry",()=>{
   if(active){active=false;}else{active=true;if(!data.venues.length)data.venues.push({name:"",address:""});if(!data.sessions.length)data.sessions.push({day:"",venue:0,start:"",end:"",category:""});}
   render();
  }));
  body.replaceChildren();
  for(const x of legacy)x.hidden=active;
  venueText.required=sessionText.required=!active&&!optional;
  hidden.disabled=!active;sync();
  if(!active)return;
  if(hadText){
   const previous=el("details");previous.append(el("summary",{},"Previously entered venue and session details"),el("p",{},venueText.value),el("p",{},sessionText.value));body.append(previous);
  }
  body.append(el("h2",{},"Playing locations"),el("p",{},"Enter each venue once. You can select it for any number of sessions."));
  const venueList=el("div",{class:"schedule-list"}),sessionList=el("div",{class:"schedule-list"});
  const venueSelectors=[];
  const refreshOptions=()=>{
   for(const [sel,row] of venueSelectors){
    sel.replaceChildren(el("option",{value:""},"Select location"));
    data.venues.forEach((v,i)=>sel.append(el("option",{value:String(i)},v.name.trim()||"Location "+(i+1))));
    sel.value=Number.isInteger(row.venue)?String(row.venue):"";
   }
  };
  data.venues.forEach((v,i)=>{
   const row=el("fieldset",{class:"schedule-row schedule-location"});row.append(el("legend",{},"Location "+(i+1)));
   const name=input("text",v.name,"e.g. Aurora Leisure Centre",160),address=input("text",v.address,"Full address or postcode",300);
   name.addEventListener("input",()=>{v.name=name.value;refreshOptions();sync();});
   address.addEventListener("input",()=>{v.address=address.value;sync();});
   row.append(field("Venue name",name),field("Address",address));
   if(optional||data.venues.length>1)row.append(button("Remove location",()=>{
    if(data.sessions.some(s=>s.venue===i)){alert("Move sessions to another location before removing this location.");return;}
    data.venues.splice(i,1);for(const s of data.sessions)if(s.venue>i)s.venue--;render();
   }));
   venueList.append(row);
  });
  body.append(venueList,button("＋ Add location",()=>{if(data.venues.length<10){data.venues.push({name:"",address:""});render();}}));
  body.append(el("h2",{},"Weekly sessions"),el("p",{},"Choose a day and location, then enter start and finish times in 24-hour format."));
  data.sessions.forEach((s,i)=>{
   const row=el("fieldset",{class:"schedule-row schedule-session"});row.append(el("legend",{},"Session "+(i+1)));
   const day=select([["","Select day"],...days.map(d=>[d,d])],s.day),location=select([],s.venue),start=input("time",s.start),end=input("time",s.end),category=select([["","Any / not specified"],["Men","Men"],["Women","Women"],["Mixed","Mixed"]],s.category);
   category.required=false;venueSelectors.push([location,s]);
   for(const [key,control] of [["day",day],["start",start],["end",end],["category",category]])control.addEventListener("input",()=>{s[key]=control.value;sync();});
   location.addEventListener("change",()=>{s.venue=location.value===""?null:Number(location.value);sync();});
   row.append(field("Day",day),field("Location",location),field("Start time",start),field("Finish time",end),field("Session category",category));
   if(optional||data.sessions.length>1)row.append(button("Remove session",()=>{data.sessions.splice(i,1);render();}));
   sessionList.append(row);
  });
  body.append(sessionList,button("＋ Add session",()=>{if(data.sessions.length<30){data.sessions.push({day:"",venue:data.venues.length===1?0:null,start:"",end:"",category:""});render();}}));
  refreshOptions();sync();
 };
 editor.hidden=false;
 if(data.venues.length||data.sessions.length)active=true;
 else if(!hadText&&!optional){active=true;data.venues.push({name:"",address:""});data.sessions.push({day:"",venue:0,start:"",end:"",category:""});}
 form.addEventListener("submit",sync);
 render();
});
