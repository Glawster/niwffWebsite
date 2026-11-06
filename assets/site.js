/* NIWFF staging site: content is provisional pending federation verification. */
"use strict";
const NAV=[
  ["/","Home"],["/find-a-club/","Find a Club"],["/competitions/","Events"],
  ["/international-teams/","International Teams"],["/news/","News"],
  ["/resources/","Resources"],["/about/","About"],["/contact/","Contact"]
];
const TEAM=[
  {slug:"over-60s",name:"Over 60s",evidence:"The legacy website contains a named Over-60 squad and references to several international tournaments."},
  {slug:"over-65s",name:"Over 65s",evidence:"The legacy 2025 international competition calendar includes Northern Ireland Over-65s."},
  {slug:"over-70s",name:"Over 70s",evidence:"The legacy calendar and a report of friendly games at Seaview refer to Northern Ireland Over-70s."}
];
const CLUBS=[
  {name:"Hillsborough Walking Football",area:"Lisburn & Castlereagh",town:"Hillsborough / Lisburn",slug:"hillsborough",description:"A community walking football programme associated with Hillsborough Boys FC. Previous times and membership arrangements require confirmation.",link:"https://hillsboroughwalkingfootball.com/"},
  {name:"Scrabo Strollers (Ards)",area:"Ards & North Down",town:"Newtownards",slug:"ards",description:"An established Newtownards walking football club, with historical sessions at Ards Blair Mayne Leisure Centre and Londonderry Park."},
  {name:"Bangor Walking Football",area:"Ards & North Down",town:"Bangor",slug:"bangor",description:"An affiliated club listed on the original NIWFF website."},
  {name:"CALA Walking Football",area:"Belfast",town:"Belfast area",slug:"cala",description:"An affiliated walking football club listed by NIWFF.",link:"https://calawalkingfootballclub.wordpress.com/"},
  {name:"Shankill Walking Football",area:"Belfast",town:"Belfast",slug:"shankill",description:"The original website records sessions at Shankill Leisure Centre; current times have not been verified."},
  {name:"Avoniel Walking Football",area:"Belfast",town:"Belfast",slug:"avoniel",description:"The original website records sessions at Avoniel Leisure Centre; current times have not been verified."},
  {name:"Olympia Walking Football",area:"Belfast",town:"Belfast",slug:"olympia",description:"An affiliated club listed on the original NIWFF website."},
  {name:"BraveHEARTS Walking Football",area:"Belfast",town:"West Belfast",slug:"bravehearts",description:"The H.E.A.R.T. Project's walking football community, historically based at Grosvenor Recreation and Community Centre."},
  {name:"Banbridge Walking Football Club",area:"Armagh, Banbridge & Craigavon",town:"Banbridge / Gilford",slug:"banbridge",description:"A community walking football club formed in 2024, with previously advertised sessions in Gilford and Banbridge."}
];
const EVENTS=[
 ["21–23 Mar 2025","NI Over-60s and Over-70s matches","Bishops Cleeve, Cheltenham"],
 ["28 Mar 2025","NIWFF league tournament","Londonderry Park, Newtownards"],
 ["6 Apr 2025","NI representative matches","Chester"],
 ["27 Apr 2025","NIWFF league tournament","Lagan Valley Leisure Centre"],
 ["30 May 2025","NIWFF league tournament","Bangor Football Club"],
 ["7 Jun 2025","NI teams v Republic of Ireland","Avoniel Leisure Centre"],
 ["3–7 Jul 2025","IWFF World Championship","Malmö, Sweden"],
 ["27 Oct–1 Nov 2025","FIWFA World Nations Cup","Alicante, Spain"]
];
const NEWS=[
 ["Northern Ireland at the World Nations Cup","2025","The legacy news page covers the Northern Ireland Over-60s at the FIWFA World Nations Cup in Alicante."],
 ["NI teams travel to Malmö","2025","Historical coverage of Northern Ireland Over-60, Over-65 and Over-70 squads attending the World Championship in Sweden."],
 ["Representative teams host Republic of Ireland","2025","Coverage of friendly fixtures at Avoniel as part of preparations for international competition."],
 ["Over-70s visit Crusaders at Seaview","Archive","A report on friendly games and the social occasion hosted by Crusaders."],
 ["Carryduff Swifts launch","2025","A historical announcement of a new walking football club in Carryduff."],
 ["Bangor welcomes Winchester","Archive","A report of a friendly walking football tournament and visit from Winchester."],
 ["NIWFF referee training","Archive","The original site reported on referee training events organised with support from the Irish FA."]
];
const path=window.location.pathname.replace(/\/+$/,"")||"/";
const app=document.getElementById("app");
const title=(str)=>{document.title=str+" | NI Walking Football Federation";};
const esc=(str)=>String(str).replace(/[&<>"']/g,(c)=>({"&":"&amp;","<":"&lt;",">":"&gt;","\"":"&quot;","'":"&#39;"}[c]));
const notice=(message="This is a staging preview. Club details, team information, dates and contacts must be verified by NIWFF before launch.")=>'<div class="notice" role="note"><strong>Content awaiting confirmation</strong><p>'+esc(message)+'</p></div>';
const intro=(label,heading,description)=>'<section class="page-hero"><div class="shell"><p class="eyebrow">'+label+'</p><h1>'+heading+'</h1><p>'+description+'</p></div></section>';
const section=(html,extra="")=>'<section class="section '+extra+'"><div class="shell">'+html+'</div></section>';
const card=(icon,heading,body,href,text="Explore")=>'<a class="tile-link" href="'+href+'"><article class="card"><span class="card-icon" aria-hidden="true">'+icon+'</span><h3>'+heading+'</h3><p>'+body+'</p><div class="card-footer">'+text+' <span aria-hidden="true">→</span></div></article></a>';
const teamCard=(t)=>card("⚽","Northern Ireland "+t.name,"International walking football squad, fixtures and historical tournament information.","/international-teams/"+t.slug+"/","View team");
const clubCard=(c)=>'<article class="card club-card" data-area="'+esc(c.area.toLowerCase())+'" data-search="'+esc((c.name+" "+c.town+" "+c.area).toLowerCase())+'"><div class="kicker">'+esc(c.area)+'</div><h3>'+esc(c.name)+'</h3><div class="meta">📍 '+esc(c.town)+'</div><p>'+esc(c.description)+'</p><span class="tag tag-archive">Details under review</span><div class="card-footer"><a href="/find-a-club/?club='+encodeURIComponent(c.slug)+'">View club record →</a></div></article>';
function home(){
 title("Home");
 return '<section class="hero"><div class="shell hero-inner"><div><p class="eyebrow">Northern Ireland Walking Football Federation</p><h1>Football for life.<br>Football for everyone.</h1><p class="lead">Find your local walking football community, discover the international teams and stay connected to the game across Northern Ireland.</p><div class="hero-actions"><a class="btn btn-primary" href="/find-a-club/">Find a club <span aria-hidden="true">→</span></a><a class="btn btn-light" href="/about/">Discover walking football</a></div></div><div class="pitch" aria-hidden="true"><div class="pitch-line"></div><div class="pitch-box"></div><div class="pitch-box right"></div><div class="pitch-ball">⚽</div><div class="pitch-caption">Move • Play • Belong</div></div></div></section>'+
 section('<div class="section-head"><div><p class="eyebrow">Get involved</p><h2>There’s a place for you</h2></div><p>Whether you’re returning to football or discovering it for the first time, start here.</p></div><div class="cards">'+
 card("📍","Find a club","Explore clubs across Northern Ireland and see where to enquire about joining.","/find-a-club/","Browse clubs")+
 card("🏆","Competitions & events","Discover tournaments, fixtures and international walking football activity.","/competitions/","See events")+
 card("🌍","International teams","Meet the Northern Ireland representative age-group teams and explore their history.","/international-teams/","Meet the teams")+
 '</div>')+
 section('<div class="feature-band"><div><p class="eyebrow" style="color:var(--lime)">The game</p><h2>Same love of football. A different pace.</h2><p>Walking football offers a welcoming way to enjoy football, stay active and connect with people. Playing rules and age categories vary by event.</p></div><a href="/resources/" class="btn btn-primary">Learn the basics →</a></div>','slim')+
 section('<div class="section-head"><div><p class="eyebrow">Representing NI</p><h2>International teams</h2></div><p>Explore age-group squad pages and the international programme.</p></div><div class="cards">'+TEAM.map(teamCard).join("")+'</div>','soft')+
 section('<div class="section-head"><div><p class="eyebrow">Federation updates</p><h2>News and events</h2></div></div>'+notice("The recovered website contains historical updates. Current NIWFF events and news will be published after editorial approval.")+'<div class="button-row"><a class="btn btn-dark" href="/news/">Browse the news archive</a><a class="btn btn-outline" href="/competitions/">View competition history</a></div>','slim');
}
function clubs(){
 const selected=new URLSearchParams(location.search).get("club");
 if(selected){
  const c=CLUBS.find(x=>x.slug===selected);
  if(!c) return notFound();
  title(c.name);
  return intro("Find a club",esc(c.name),esc(c.town))+section('<div class="breadcrumb"><a href="/find-a-club/">← Back to club directory</a></div><div class="content-grid"><article class="panel"><h2>About this club</h2><p>'+esc(c.description)+'</p>'+notice("This profile is based on an archived website listing. Session times, eligibility, costs and contact details are not confirmed as current.")+(c.link?'<a class="btn btn-dark" target="_blank" rel="noopener noreferrer" href="'+c.link+'">Visit club website ↗</a>':'<a class="btn btn-dark" href="/contact/">Enquire through NIWFF</a>')+'</article><aside class="panel"><h3>Club record</h3><p><strong>Area:</strong> '+esc(c.area)+'</p><p><strong>Location:</strong> '+esc(c.town)+'</p><p><strong>Verification:</strong> Pending</p></aside></div>');
 }
 title("Find a Club");
 return intro("Get playing","Find a club","Explore walking football clubs in Northern Ireland. These listings are being updated and verified for the new NIWFF website.")+
 section(notice("This is a preview of the directory; club details and active status have not yet been confirmed.")+
 '<div class="filters"><div class="field"><label for="club-query">Search clubs or towns</label><input id="club-query" type="search" placeholder="e.g. Bangor, Belfast, Hillsborough"></div><div class="field"><label for="club-region">Council area</label><select id="club-region"><option value="">All areas</option>'+[...new Set(CLUBS.map(c=>c.area))].sort().map(x=>'<option>'+esc(x)+'</option>').join("")+'</select></div></div><p class="results-count" id="club-count" aria-live="polite">'+CLUBS.length+' clubs listed</p><div class="cards" id="club-list">'+CLUBS.map(clubCard).join("")+'</div><div class="empty" id="club-empty" hidden><h3>No matching clubs</h3><p>Try a different town or area.</p></div>');
}
function international(){
 title("International Teams");
 return intro("Northern Ireland","International Teams","Representing Northern Ireland in walking football across different age categories and international tournaments.")+
 section('<div class="section-head"><div><p class="eyebrow">Our squads</p><h2>Explore the age groups</h2></div><p>Further categories can be added as the federation confirms current squads.</p></div>'+notice("The current playing squads and officials have not yet been confirmed. The age groups below are supported by the archived website.")+'<div class="cards">'+TEAM.map(teamCard).join("")+'</div>')+
 section('<div class="feature-band"><div><h2>International fixtures & results</h2><p>Team fixtures and tournament results will link to a shared, reviewed competition calendar.</p></div><a class="btn btn-primary" href="/competitions/">Competition history →</a></div>','slim');
}
function teamPage(slug){
 const t=TEAM.find(x=>x.slug===slug);if(!t)return notFound();title("International "+t.name);
 return intro("International Teams","Northern Ireland "+t.name,"Representative walking football • "+t.name+" category")+
 section('<div class="breadcrumb"><a href="/international-teams/">← All international teams</a></div>'+notice("Current squad members, team management, eligibility and fixtures must be confirmed before publication.")+
 '<div class="content-grid"><div><article class="panel"><p class="eyebrow">The team</p><h2>About this squad</h2><p>'+esc(t.evidence)+'</p><p>Information about the current season, squad members, staff and international programme will appear here when approved.</p></article><div class="panel" style="margin-top:22px"><h2>Squad & management</h2><div class="empty"><h3>Squad announcement pending</h3><p>Confirmed players, coaches and photographs will be added following NIWFF approval.</p></div></div></div><aside class="panel"><h3>Team overview</h3><p><strong>Category:</strong> '+esc(t.name)+'</p><p><strong>Current season:</strong> Awaiting confirmation</p><p><strong>Fixtures:</strong> Awaiting approval</p><p><strong>Previous tournaments:</strong> Historical records available</p><a href="/competitions/">Browse competitions →</a></aside></div>')+
 section('<div class="section-head"><div><p class="eyebrow">Other squads</p><h2>Explore other age groups</h2></div></div><div class="cards two">'+TEAM.filter(x=>x.slug!==slug).map(teamCard).join("")+'</div>','soft');
}
function competitions(){
 title("Competitions & Events");
 return intro("On the pitch","Competitions & Events","Fixtures, tournaments and representative-team activity across Northern Ireland and beyond.")+
 section('<div class="section-head"><div><p class="eyebrow">Coming up</p><h2>Upcoming events</h2></div></div><div class="empty"><h3>No verified upcoming fixtures yet</h3><p>NIWFF will add approved events to the new calendar when the current programme is confirmed.</p></div>')+
 section('<p class="eyebrow">From the archive</p><h2>2025 competition history</h2>'+notice("These entries were recovered from the original 2025 calendar. They are historical listings, not announcements of upcoming events or confirmed results.")+
 '<div class="timeline">'+EVENTS.map(e=>'<article class="event"><div class="event-date">'+esc(e[0])+'</div><div><h3>'+esc(e[1])+'</h3><p>📍 '+esc(e[2])+'</p><span class="tag tag-archive">Historical listing</span></div></article>').join("")+'</div>','soft');
}
function news(){
 title("News");
 return intro("Latest stories","News & Updates","Federation stories, international team coverage and walking football news from across Northern Ireland.")+
 section(notice("These topics were recovered from the legacy news page. Dates and original articles need editorial review before they are published as full news stories.")+
 '<div class="cards">'+NEWS.map(n=>'<article class="card"><div class="kicker">'+esc(n[1])+' · Archive</div><h3>'+esc(n[0])+'</h3><p>'+esc(n[2])+'</p><span class="tag tag-archive">Historical summary</span></article>').join("")+'</div>');
}
function resources(){
 title("Resources");
 return intro("Playing the game","Resources","An introduction to walking football, how the game works and useful links for players and clubs.")+
 section('<div class="cards">'+card("🚶","Walking, not running","The game adapts the pace of football. Exact interpretations and competition rules should always be checked.","/about/","How it works")+
 card("🤝","Join the community","Find a welcoming place to play and make new connections.","/find-a-club/","Find a club")+
 card("🏟️","Irish FA resources","The Irish Football Association publishes information about walking football in Northern Ireland.","https://www.irishfa.com/irish-fa-foundation/community-volunteering/walking-football","IFA walking football ↗")+'</div>'+
 '<div class="panel" style="margin-top:25px"><h2>Rules and guidance</h2><p>Walking football generally limits running and physical contact and may use restrictions on ball height and heading. Specific rules vary between competitions and governing organisations. Official NIWFF competition rules will be linked here when approved.</p></div>');
}
function about(){
 title("About NIWFF");
 return intro("About the federation","Walking football in Northern Ireland","Supporting an inclusive, safe and enjoyable walking football community.")+
 section('<div class="section-head"><div><p class="eyebrow">Why we play</p><h2>Fun, fitness and friendship</h2></div></div><div class="cards">'+card("💚","Health & wellbeing","A way to stay active at a manageable pace and enjoy being part of a team.","/find-a-club/","Find a session")+
 card("🤝","Community","Meet others, build friendships and enjoy the social side of football.","/find-a-club/","Find your community")+
 card("⚽","Football for life","An opportunity to keep playing or return to the game in later life.","/resources/","Discover the game")+'</div>')+
 section('<div class="panel"><h2>Federation information</h2><p>Governance details, committee roles, policies, safeguarding and the privacy notice will be published after NIWFF confirms the approved content. The existing site is retained while the replacement is reviewed.</p></div>','soft');
}
function contact(){
 title("Contact");
 return intro("Get in touch","Contact NIWFF","Questions about clubs, events, international teams or the federation?")+
 section('<div class="content-grid"><div class="panel"><h2>Contact the federation</h2>'+notice("The former website listed individual contact information. We are confirming the current federation contact route before enabling direct enquiries.")+'<p>For now, you can use the publicly linked NI walking football community group to find federation updates.</p><div class="button-row"><a href="https://www.facebook.com/groups/602931127880903/" class="btn btn-dark" target="_blank" rel="noopener noreferrer">NI walking football Facebook ↗</a><a href="/find-a-club/" class="btn btn-outline">Explore clubs</a></div></div><aside class="panel"><h3>What can we help with?</h3><ul><li>Finding a local club</li><li>Federation events and competitions</li><li>International representative teams</li><li>Club affiliation and general information</li></ul></aside></div>');
}
function notFound(){title("Page not found");return intro("404","Page not found","The page you requested is unavailable.")+section('<p><a href="/" class="btn btn-dark">Return to homepage</a></p>');}
function render(){
 const teamMatch=path.match(/^\/international-teams\/(over-60s|over-65s|over-70s)$/);
 const view=path==="/"?home():path==="/find-a-club"?clubs():path==="/competitions"?competitions():path==="/international-teams"?international():teamMatch?teamPage(teamMatch[1]):path==="/news"?news():path==="/resources"?resources():path==="/about"?about():path==="/contact"?contact():notFound();
 document.getElementById("site-nav").innerHTML=NAV.map(([url,name])=>'<a href="'+url+'" '+((path===url.replace(/\/$/,"")||path===url||((path.startsWith("/international-teams/"))&&url==="/international-teams/"))?'aria-current="page"':'')+'>'+name+'</a>').join("");
 app.innerHTML=view;
 const query=document.getElementById("club-query"),region=document.getElementById("club-region");
 if(query && region){
  const update=()=>{
   const q=query.value.trim().toLowerCase(),a=region.value.toLowerCase();
   let n=0;
   document.querySelectorAll(".club-card").forEach(el=>{const show=el.dataset.search.includes(q)&&(!a||el.dataset.area===a);el.hidden=!show;if(show)n++;});
   document.getElementById("club-count").textContent=n+" club"+(n===1?"":"s")+" listed";
   document.getElementById("club-empty").hidden=n!==0;
  };
  query.addEventListener("input",update);region.addEventListener("change",update);
 }
}
document.getElementById("nav-toggle").addEventListener("click",function(){
 const nav=document.getElementById("site-nav"),open=nav.classList.toggle("open");
 this.setAttribute("aria-expanded",String(open));
});
render();
