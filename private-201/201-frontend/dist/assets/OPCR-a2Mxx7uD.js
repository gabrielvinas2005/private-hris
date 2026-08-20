import{P as ye}from"./PageScaffold-e9m5iALX.js";import{_ as ne,r as R,c as j,C as d,D as re,d as H,e as h,G as e,H as t,I as k,N as de,a4 as be,ad as we,a3 as Ce,J as ce,f as i,L as xe,h as p,M as Re,s as Pe,ae as Oe,F as X,k as te,y as U,O as fe,j as u,S as De,K as ke,w as oe,g as J,P as $e,Q as Ve,u as pe,R as Ae,E as w,V as Ne,B as se,ag as K,o as Fe,A as Se}from"./index-BtP9JMpM.js";import{u as Ee}from"./useExportEmployeeData-7Xxqj-Po.js";const Te={class:"opcr-list"},ze={class:"action-buttons"},Le={class:"flex justify-between items-center"},Me={class:"font-medium"},Ye={class:"text-sm"},je={class:"font-medium"},He={class:"action-buttons"},Ue={__name:"OPCRList",props:{opcrRatings:{type:Array,default:()=>[]},loading:{type:Boolean,default:!1}},emits:["refresh","add","view","edit","review","delete"],setup(m,{emit:Q}){const v=m,z=Q,{exportToExcel:E}=Ee(),D=R(!1),r=R(""),V=R("400px"),$=R([{key:"office",label:"Office"},{key:"period",label:"Period"},{key:"year",label:"Year"},{key:"status",label:"Status"}]),P=R(["office","period","year","status"]),A=j(()=>{if(!r.value)return v.opcrRatings;const l=r.value.toLowerCase();return v.opcrRatings.filter(n=>n.division?.toLowerCase().includes(l)||n.month_from?.toLowerCase().includes(l)||n.month_to?.toLowerCase().includes(l))}),L=j(()=>{const l=new Date().getFullYear();return v.opcrRatings.filter(n=>n.year===l||n.year===l.toString()).length}),g=j(()=>new Set(v.opcrRatings.map(n=>n.division)).size),f=j(()=>v.opcrRatings.length),x=l=>"info",N=l=>"Active",I=()=>{z("refresh")},G=()=>{z("add")},y=l=>{z("view",l)},_=l=>{z("edit",l)},c=l=>{z("review",l)},a=l=>{z("delete",l)},o=()=>{try{if(A.value.length===0){w.warning("No data to print");return}const l=W(A.value),n=window.open("","_blank");if(!n){w.error("Please allow popups to print");return}n.document.write(l),n.document.close(),n.onload=()=>{setTimeout(()=>{n.print(),n.close()},250)}}catch(l){console.error("Print failed:",l),w.error("Failed to print OPCR records")}},b=async()=>{try{if(D.value=!0,A.value.length===0){w.warning("No data to export");return}const l=A.value.map(s=>({ID:s.id||"",Office:s.division||"",Year:s.year||"","Month From":s.month_from||"","Month To":s.month_to||"",Status:N(s)})),n=`opcr_records_${new Date().toISOString().split("T")[0]}.xlsx`;await E(l,n)}catch(l){console.error("Excel export failed:",l)}finally{D.value=!1}},F=async()=>{try{if(D.value=!0,A.value.length===0){w.warning("No data to export");return}const l=W(A.value),n=window.open("","_blank");if(!n){w.error("Please allow popups to export PDF");return}n.document.write(l),n.document.close(),n.onload=()=>{setTimeout(()=>{n.print(),w.success(`Use your browser's "Save as PDF" option in the print dialog`)},250)}}catch(l){console.error("PDF export failed:",l),w.error("Failed to export PDF file")}finally{D.value=!1}},W=l=>{const n=new Date().toLocaleDateString("en-US",{year:"numeric",month:"long",day:"numeric"});let s="";return l.forEach((M,Y)=>{s+=`
      <tr>
        <td style="border: 1px solid #ddd; padding: 8px; text-align: center;">${Y+1}</td>
        <td style="border: 1px solid #ddd; padding: 8px;">${M.department||""}</td>
        <td style="border: 1px solid #ddd; padding: 8px;">${M.division||"N/A"}</td>
        <td style="border: 1px solid #ddd; padding: 8px;">${M.section||""}</td>
        <td style="border: 1px solid #ddd; padding: 8px; text-align: center;">${M.year||"N/A"}</td>
        <td style="border: 1px solid #ddd; padding: 8px;">${M.month_from||""}</td>
        <td style="border: 1px solid #ddd; padding: 8px;">${M.month_to||""}</td>
        <td style="border: 1px solid #ddd; padding: 8px;">${N()}</td>
      </tr>
    `}),`
    <!DOCTYPE html>
    <html>
    <head>
      <title>OPCR Records Report</title>
      <style>
        @media print {
          @page {
            margin: 1cm;
            size: A4 landscape;
          }
        }
        body {
          font-family: Arial, sans-serif;
          margin: 20px;
          color: #333;
        }
        h1 {
          text-align: center;
          color: #333;
          margin-bottom: 10px;
        }
        .report-info {
          text-align: center;
          color: #666;
          margin-bottom: 20px;
        }
        table {
          width: 100%;
          border-collapse: collapse;
          margin-top: 20px;
          font-size: 12px;
        }
        th {
          background-color: #4a5568;
          color: white;
          border: 1px solid #ddd;
          padding: 10px 8px;
          text-align: left;
          font-weight: bold;
        }
        td {
          border: 1px solid #ddd;
          padding: 8px;
        }
        tr:nth-child(even) {
          background-color: #f9f9f9;
        }
      </style>
    </head>
    <body>
      <h1>OPCR Records Report</h1>
      <div class="report-info">
        <p>Generated on: ${n}</p>
        <p>Total Records: ${l.length}</p>
      </div>
      <table>
        <thead>
          <tr>
            <th style="width: 50px;">No.</th>
            <th>Office</th>
            <th style="width: 80px;">Year</th>
            <th>Month From</th>
            <th>Month To</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
          ${s}
        </tbody>
      </table>
    </body>
    </html>
  `},q=l=>{const n=P.value.indexOf(l);if(n>-1)if(P.value.length>1)P.value.splice(n,1);else{w.warning("At least one column must be visible");return}else P.value.push(l)},T=l=>{q(l)};return(l,n)=>{const s=d("el-statistic"),M=d("el-icon"),Y=d("el-card"),B=d("el-col"),Z=d("el-row"),ee=d("el-input"),O=d("el-button"),C=d("el-checkbox"),ae=d("el-dropdown-item"),me=d("el-dropdown-menu"),ve=d("el-dropdown"),ue=d("el-tag"),le=d("el-table-column"),_e=d("el-table"),ge=d("el-empty"),he=re("loading");return h(),H("div",Te,[e(Z,{gutter:16,class:"mb-6"},{default:t(()=>[e(B,{span:6},{default:t(()=>[e(Y,{shadow:"hover",class:"metric-card"},{suffix:t(()=>[e(M,{class:"metric-icon total"},{default:t(()=>[e(k(de))]),_:1})]),default:t(()=>[e(s,{title:"Total OPCR Records",value:m.opcrRatings.length},null,8,["value"])]),_:1})]),_:1}),e(B,{span:6},{default:t(()=>[e(Y,{shadow:"hover",class:"metric-card"},{suffix:t(()=>[e(M,{class:"metric-icon year"},{default:t(()=>[e(k(be))]),_:1})]),default:t(()=>[e(s,{title:"Current Year",value:L.value},null,8,["value"])]),_:1})]),_:1}),e(B,{span:6},{default:t(()=>[e(Y,{shadow:"hover",class:"metric-card"},{suffix:t(()=>[e(M,{class:"metric-icon departments"},{default:t(()=>[e(k(we))]),_:1})]),default:t(()=>[e(s,{title:"Departments",value:g.value},null,8,["value"])]),_:1})]),_:1}),e(B,{span:6},{default:t(()=>[e(Y,{shadow:"hover",class:"metric-card"},{suffix:t(()=>[e(M,{class:"metric-icon pending"},{default:t(()=>[e(k(Ce))]),_:1})]),default:t(()=>[e(s,{title:"Pending Reviews",value:f.value},null,8,["value"])]),_:1})]),_:1})]),_:1}),e(Y,{shadow:"never",class:"mb-4"},{default:t(()=>[e(Z,{gutter:16,align:"middle"},{default:t(()=>[e(B,{span:8},{default:t(()=>[e(ee,{modelValue:r.value,"onUpdate:modelValue":n[0]||(n[0]=S=>r.value=S),placeholder:"Search OPCR records...","prefix-icon":k(ce),clearable:""},null,8,["modelValue","prefix-icon"])]),_:1}),e(B,{span:16,class:"text-right"},{default:t(()=>[i("div",ze,[e(O,{icon:k(xe),onClick:o,loading:D.value,title:"Print"},{default:t(()=>[...n[2]||(n[2]=[p(" Print ",-1)])]),_:1},8,["icon","loading"]),e(O,{icon:k(Re),onClick:b,loading:D.value,title:"Export to Excel"},{default:t(()=>[...n[3]||(n[3]=[p(" Excel ",-1)])]),_:1},8,["icon","loading"]),e(O,{icon:k(de),onClick:F,loading:D.value,title:"Export to PDF"},{default:t(()=>[...n[4]||(n[4]=[p(" PDF ",-1)])]),_:1},8,["icon","loading"]),e(ve,{onCommand:T},{dropdown:t(()=>[e(me,null,{default:t(()=>[(h(!0),H(X,null,te($.value,S=>(h(),U(ae,{key:S.key,class:"col-item",onClick:n[1]||(n[1]=fe(()=>{},["stop"]))},{default:t(()=>[e(C,{"model-value":P.value.includes(S.key),onChange:ie=>q(S.key)},{default:t(()=>[p(u(S.label),1)]),_:2},1032,["model-value","onChange"])]),_:2},1024))),128))]),_:1})]),default:t(()=>[e(O,{icon:k(Pe),title:"Column Visibility"},{default:t(()=>[n[5]||(n[5]=p(" Column Visibility ",-1)),e(M,{class:"el-icon--right"},{default:t(()=>[e(k(Oe))]),_:1})]),_:1},8,["icon"])]),_:1}),e(O,{icon:k(De),onClick:I,loading:m.loading},{default:t(()=>[...n[6]||(n[6]=[p(" Refresh ",-1)])]),_:1},8,["icon","loading"]),e(O,{type:"primary",icon:k(ke),onClick:G},{default:t(()=>[...n[7]||(n[7]=[p(" Create New OPCR ",-1)])]),_:1},8,["icon"])])]),_:1})]),_:1})]),_:1}),e(Y,{shadow:"never"},{header:t(()=>[i("div",Le,[n[8]||(n[8]=i("span",{class:"font-medium"},"OPCR Records",-1)),e(ue,{type:"info"},{default:t(()=>[p(u(A.value.length)+" records",1)]),_:1})])]),default:t(()=>[oe((h(),U(_e,{data:A.value,border:"",stripe:"",height:V.value},{default:t(()=>[P.value.includes("office")?(h(),U(le,{key:0,prop:"division",label:"Office","min-width":"260"},{default:t(({row:S})=>[i("div",Me,u(S.division),1)]),_:1})):J("",!0),P.value.includes("period")?(h(),U(le,{key:1,label:"Period",width:"200"},{default:t(({row:S})=>[i("div",Ye,[i("div",null,[n[9]||(n[9]=i("strong",null,"From:",-1)),p(" "+u(S.month_from),1)]),i("div",null,[n[10]||(n[10]=i("strong",null,"To:",-1)),p(" "+u(S.month_to),1)])])]),_:1})):J("",!0),P.value.includes("year")?(h(),U(le,{key:2,prop:"year",label:"Year",width:"80",align:"center"},{default:t(({row:S})=>[i("span",je,u(S.year||"N/A"),1)]),_:1})):J("",!0),P.value.includes("status")?(h(),U(le,{key:3,label:"Status",width:"120"},{default:t(({row:S})=>[e(ue,{type:x(S),size:"small"},{default:t(()=>[p(u(N(S)),1)]),_:2},1032,["type"])]),_:1})):J("",!0),e(le,{label:"Actions",width:"240",fixed:"right"},{default:t(({row:S})=>[i("div",He,[e(O,{type:"primary",size:"small",icon:k($e),onClick:ie=>y(S),title:"View Details"},null,8,["icon","onClick"]),e(O,{type:"success",size:"small",icon:k(Ve),onClick:ie=>_(S),title:"Edit OPCR"},null,8,["icon","onClick"]),e(O,{type:"warning",size:"small",icon:k(pe),onClick:ie=>c(S),title:"Review Office Heads"},null,8,["icon","onClick"]),e(O,{type:"danger",size:"small",icon:k(Ae),onClick:ie=>a(S),title:"Delete OPCR"},null,8,["icon","onClick"])])]),_:1})]),_:1},8,["data","height"])),[[he,m.loading]]),!m.loading&&A.value.length===0?(h(),U(ge,{key:0,description:"No OPCR records found"},{default:t(()=>[e(O,{type:"primary",onClick:G},{default:t(()=>[...n[11]||(n[11]=[p("Create First OPCR",-1)])]),_:1})]),_:1})):J("",!0)]),_:1})])}}},Ie=ne(Ue,[["__scopeId","data-v-aecc69a4"]]),qe={class:"dialog-footer"},Be={__name:"OPCRForm",props:{modelValue:{type:Boolean,default:!1},opcrData:{type:Object,default:null},formData:{type:Object,default:null},loading:{type:Boolean,default:!1}},emits:["update:modelValue","save"],setup(m,{emit:Q}){const v=m,z=Q,E=R(null),D=R(!1),r=Ne({id:0,division:0,month_from:null,month_to:null,year:new Date().getFullYear()}),V=new Date().getFullYear(),$=Array.from({length:5},(y,_)=>V-2+_),P=[{id:1,name:"January"},{id:2,name:"February"},{id:3,name:"March"},{id:4,name:"April"},{id:5,name:"May"},{id:6,name:"June"},{id:7,name:"July"},{id:8,name:"August"},{id:9,name:"September"},{id:10,name:"October"},{id:11,name:"November"},{id:12,name:"December"}],A={division:[{required:!0,message:"Please select an office",trigger:"change"}],month_from:[{required:!0,message:"Please select starting month",trigger:"change"}],month_to:[{required:!0,message:"Please select ending month",trigger:"change"}],year:[{required:!0,message:"Please select a year",trigger:"change"}]},L=j({get:()=>v.modelValue,set:y=>z("update:modelValue",y)}),g=j(()=>f.value?"Edit OPCR":"Create New OPCR"),f=j(()=>v.opcrData&&v.opcrData.id>0),x=j(()=>v.formData?.divisions||[]),N=j(()=>x.value);se(()=>v.opcrData,y=>{y?(r.id=y.id||0,r.division=y.division_id||0,r.month_from=y.month_from_id||y.month_from||null,r.month_to=y.month_to_id||y.month_to||null,r.year=y.year||new Date().getFullYear()):(r.id=0,r.division=0,r.month_from=null,r.month_to=null,r.year=new Date().getFullYear())},{immediate:!0}),se(()=>v.formData?.opcr_ratings,y=>{if(y&&y.length>0){const _=y[0];(!v.opcrData||r.id===0)&&(r.id=_.id||0,r.division=_.division_id||0,r.month_from=_.month_from||null,r.month_to=_.month_to||null,r.year=_.year||new Date().getFullYear())}},{immediate:!0});const I=async()=>{try{if(await E.value.validate(),r.month_from&&r.month_to&&r.month_from>r.month_to){w.error("Start month cannot be later than end month");return}D.value=!0;const y={id:r.id,division:r.division,month_from:r.month_from,month_to:r.month_to,year:r.year};z("save",y)}catch(y){console.error("Validation failed:",y)}finally{D.value=!1}},G=()=>{L.value=!1};return(y,_)=>{const c=d("el-option"),a=d("el-select"),o=d("el-form-item"),b=d("el-col"),F=d("el-row"),W=d("el-alert"),q=d("el-form"),T=d("el-button"),l=d("el-dialog"),n=re("loading");return h(),U(l,{modelValue:L.value,"onUpdate:modelValue":_[4]||(_[4]=s=>L.value=s),title:g.value,width:"70%","close-on-click-modal":!1,"close-on-press-escape":!1},{footer:t(()=>[i("div",qe,[e(T,{onClick:G},{default:t(()=>[..._[6]||(_[6]=[p("Cancel",-1)])]),_:1}),e(T,{type:"primary",onClick:I,loading:D.value},{default:t(()=>[p(u(D.value?"Saving...":f.value?"Update OPCR":"Create OPCR"),1)]),_:1},8,["loading"])])]),default:t(()=>[oe((h(),H("div",null,[e(q,{model:r,rules:A,ref_key:"formRef",ref:E,"label-position":"top",onSubmit:fe(I,["prevent"])},{default:t(()=>[e(F,{gutter:16},{default:t(()=>[e(b,{span:12},{default:t(()=>[e(o,{label:"Office",prop:"division",required:""},{default:t(()=>[e(a,{modelValue:r.division,"onUpdate:modelValue":_[0]||(_[0]=s=>r.division=s),placeholder:"Select Office",style:{width:"100%"},filterable:""},{default:t(()=>[e(c,{label:"No Division",value:0}),(h(!0),H(X,null,te(N.value,s=>(h(),U(c,{key:s.id,label:s.name,value:s.id},null,8,["label","value"]))),128))]),_:1},8,["modelValue"])]),_:1})]),_:1})]),_:1}),e(F,{gutter:16},{default:t(()=>[e(b,{span:12},{default:t(()=>[e(o,{label:"Year",prop:"year",required:""},{default:t(()=>[e(a,{modelValue:r.year,"onUpdate:modelValue":_[1]||(_[1]=s=>r.year=s),placeholder:"Select Year",style:{width:"100%"}},{default:t(()=>[(h(!0),H(X,null,te(k($),s=>(h(),U(c,{key:s,label:s.toString(),value:s},null,8,["label","value"]))),128))]),_:1},8,["modelValue"])]),_:1})]),_:1})]),_:1}),e(F,{gutter:16},{default:t(()=>[e(b,{span:12},{default:t(()=>[e(o,{label:"Month From",prop:"month_from",required:""},{default:t(()=>[e(a,{modelValue:r.month_from,"onUpdate:modelValue":_[2]||(_[2]=s=>r.month_from=s),placeholder:"Select Starting Month",style:{width:"100%"}},{default:t(()=>[(h(),H(X,null,te(P,s=>e(c,{key:s.id,label:s.name,value:s.id},null,8,["label","value"])),64))]),_:1},8,["modelValue"])]),_:1})]),_:1}),e(b,{span:12},{default:t(()=>[e(o,{label:"Month To",prop:"month_to",required:""},{default:t(()=>[e(a,{modelValue:r.month_to,"onUpdate:modelValue":_[3]||(_[3]=s=>r.month_to=s),placeholder:"Select Ending Month",style:{width:"100%"}},{default:t(()=>[(h(),H(X,null,te(P,s=>e(c,{key:s.id,label:s.name,value:s.id},null,8,["label","value"])),64))]),_:1},8,["modelValue"])]),_:1})]),_:1})]),_:1}),e(W,{title:"OPCR Setup Information",type:"info",closable:!1,class:"mb-4"},{default:t(()=>[..._[5]||(_[5]=[i("p",null,"This will create an OPCR evaluation period for the selected office.",-1),i("ul",{class:"mt-2 ml-4"},[i("li",null,"Only office heads will be included"),i("li",null,"You can review and rate office heads after creating this OPCR"),i("li",null,"The evaluation period will be from the selected start month to end month")],-1)])]),_:1})]),_:1},8,["model"])])),[[n,m.loading]])]),_:1},8,["modelValue","title"])}}},Qe=ne(Be,[["__scopeId","data-v-969650ae"]]),Ge={key:0},We={class:"flex items-center"},Je={class:"info-item"},Ke={class:"info-item"},Xe={class:"info-item"},Ze={class:"info-item"},et={class:"flex justify-between items-center"},tt={class:"flex items-center"},at={class:"mb-4"},lt={class:"position-info"},ot={class:"position-name"},nt={class:"employee-name text-muted"},it={class:"dialog-footer"},st={key:0},rt={class:"flex justify-between items-center mb-2"},dt={class:"font-medium text-base"},ut={style:{"text-align":"right",width:"100%"}},ct={__name:"OfficeHeadReview",props:{modelValue:{type:Boolean,default:!1},reviewData:{type:Array,default:()=>[]},loading:{type:Boolean,default:!1}},emits:["update:modelValue","save-ratings","get-adjectival-rating"],setup(m,{emit:Q}){const v=m,z=Q,E=R(""),D=R([]),r=R("400px"),V=R(!1),$=R(null),P=[{key:"hr",label:"HR Calibration"},{key:"pmt",label:"PMT Calibration"}],A=j({get:()=>v.modelValue,set:c=>z("update:modelValue",c)}),L=j(()=>v.reviewData.length>0?v.reviewData[0]:null),g=j(()=>{const c=L.value;return c?`Office Head Review - ${c.department||"N/A"}`:"Office Head Performance Review"}),f=j(()=>{if(!E.value)return D.value;const c=E.value.toLowerCase();return D.value.filter(a=>(a.first_name||"").toLowerCase().includes(c)||(a.last_name||"").toLowerCase().includes(c)||(a.employee_no||"").toLowerCase().includes(c)||(a.position||"").toLowerCase().includes(c))}),x=c=>`${c.first_name||""} ${c.middle_name||""} ${c.last_name||""}`.trim(),N=(c,a)=>c?.recalibration_levels&&c.recalibration_levels[a]?c.recalibration_levels[a].toString().toUpperCase():"Pending",I=(c,a)=>{const o=N(c,a).toLowerCase();return o.includes("approved")||o.includes("completed")?"success":o.includes("pending")||o.includes("draft")?"info":o.includes("rejected")?"danger":"warning"},G=c=>{if(!c||!c.outputs)return[];const a={hr:1,pmt:2};return c.outputs.flatMap(o=>(o.recalibrations||[]).map(b=>({...b,category:o.category,success_indicators:o.success_indicators,actual_accomplishments:o.actual_accomplishments,_order:a[b.recalibration_level]||99}))).sort((o,b)=>o._order-b._order||o.id-b.id)},y=c=>{const a={hr:[],pmt:[]};return G(c).forEach(o=>{const b=o.recalibration_level;a[b]&&a[b].push(o)}),a};se(()=>v.reviewData,c=>{c&&c.length>0&&(D.value=c.map(a=>({...a,period_start:a.period_start||a.month_from||"",period_end:a.period_end||a.month_to||""})))},{immediate:!0});const _=c=>{$.value=c,V.value=!0};return(c,a)=>{const o=d("el-icon"),b=d("el-col"),F=d("el-row"),W=d("el-card"),q=d("el-tag"),T=d("el-input"),l=d("el-table-column"),n=d("el-button"),s=d("el-table"),M=d("el-empty"),Y=d("el-dialog"),B=d("el-descriptions-item"),Z=d("el-descriptions"),ee=d("el-drawer"),O=re("loading");return h(),H(X,null,[e(Y,{modelValue:A.value,"onUpdate:modelValue":a[2]||(a[2]=C=>A.value=C),title:g.value,width:"90%","close-on-click-modal":!1,"close-on-press-escape":!1,top:"5vh"},{footer:t(()=>[i("div",it,[e(n,{onClick:a[1]||(a[1]=C=>A.value=!1)},{default:t(()=>[...a[12]||(a[12]=[p("Close",-1)])]),_:1})])]),default:t(()=>[oe((h(),H("div",null,[L.value?(h(),H("div",Ge,[e(W,{shadow:"never",class:"mb-4"},{header:t(()=>[i("div",We,[e(o,{class:"mr-2"},{default:t(()=>[e(k(de))]),_:1}),a[5]||(a[5]=i("span",null,"OPCR Information",-1))])]),default:t(()=>[e(F,{gutter:16},{default:t(()=>[e(b,{span:12},{default:t(()=>[i("div",Je,[a[6]||(a[6]=i("label",null,"Office:",-1)),i("span",null,u(L.value.division),1)])]),_:1}),e(b,{span:12},{default:t(()=>[i("div",Ke,[a[7]||(a[7]=i("label",null,"Year:",-1)),i("span",null,u(L.value.year),1)])]),_:1})]),_:1}),e(F,{gutter:16},{default:t(()=>[e(b,{span:6},{default:t(()=>[i("div",Xe,[a[8]||(a[8]=i("label",null,"Period From:",-1)),i("span",null,u(L.value.period_start||"N/A"),1)])]),_:1}),e(b,{span:6},{default:t(()=>[i("div",Ze,[a[9]||(a[9]=i("label",null,"Period To:",-1)),i("span",null,u(L.value.period_end||"N/A"),1)])]),_:1})]),_:1})]),_:1}),e(W,{shadow:"never"},{header:t(()=>[i("div",et,[i("div",tt,[e(o,{class:"mr-2"},{default:t(()=>[e(k(pe))]),_:1}),a[10]||(a[10]=i("span",null,"Office Head Performance Ratings",-1))]),e(q,{type:"info"},{default:t(()=>[p(u(D.value.length)+" office heads",1)]),_:1})])]),default:t(()=>[i("div",at,[e(F,{gutter:16,align:"middle"},{default:t(()=>[e(b,{span:12},{default:t(()=>[e(T,{modelValue:E.value,"onUpdate:modelValue":a[0]||(a[0]=C=>E.value=C),placeholder:"Search office heads...","prefix-icon":k(ce),clearable:""},null,8,["modelValue","prefix-icon"])]),_:1})]),_:1})]),e(s,{data:f.value,border:"",stripe:"",height:r.value,class:"rating-table"},{default:t(()=>[e(l,{label:"Office Head","min-width":"260"},{default:t(({row:C})=>[i("div",lt,[i("div",ot,u(x(C)),1),i("div",nt,u(C.employee_no)+" | "+u(C.position),1)])]),_:1}),e(l,{prop:"recalibration_overall",label:"Status",width:"180"},{default:t(({row:C})=>[e(q,{type:"info"},{default:t(()=>[p(u(C.recalibration_overall||"Pending"),1)]),_:2},1024)]),_:1}),e(l,{label:"HR",width:"120"},{default:t(({row:C})=>[e(q,{type:I(C,"hr")},{default:t(()=>[p(u(N(C,"hr")),1)]),_:2},1032,["type"])]),_:1}),e(l,{label:"PMT",width:"120"},{default:t(({row:C})=>[e(q,{type:I(C,"pmt")},{default:t(()=>[p(u(N(C,"pmt")),1)]),_:2},1032,["type"])]),_:1}),e(l,{label:"Action",width:"140"},{default:t(({row:C})=>[e(n,{type:"primary",plain:"",size:"small",onClick:ae=>_(C)},{default:t(()=>[...a[11]||(a[11]=[p(" View ",-1)])]),_:1},8,["onClick"])]),_:1})]),_:1},8,["data","height"]),!m.loading&&f.value.length===0?(h(),U(M,{key:0,description:"No office heads found for this OPCR"})):J("",!0)]),_:1})])):J("",!0)])),[[O,m.loading]])]),_:1},8,["modelValue","title"]),e(ee,{modelValue:V.value,"onUpdate:modelValue":a[4]||(a[4]=C=>V.value=C),title:"OPCR Preview",size:"60%"},{footer:t(()=>[i("div",ut,[e(n,{onClick:a[3]||(a[3]=C=>V.value=!1)},{default:t(()=>[...a[15]||(a[15]=[p("Close",-1)])]),_:1})])]),default:t(()=>[$.value?(h(),H("div",st,[e(Z,{column:2,border:""},{default:t(()=>[e(B,{label:"Office Head"},{default:t(()=>[p(u(x($.value)),1)]),_:1}),e(B,{label:"Employee No."},{default:t(()=>[p(u($.value.employee_no||"N/A"),1)]),_:1}),e(B,{label:"Position"},{default:t(()=>[p(u($.value.position||"N/A"),1)]),_:1}),e(B,{label:"Status"},{default:t(()=>[p(u($.value.status_name||"No status"),1)]),_:1}),e(B,{label:"Period From"},{default:t(()=>[p(u($.value.period_start||"N/A"),1)]),_:1}),e(B,{label:"Period To"},{default:t(()=>[p(u($.value.period_end||"N/A"),1)]),_:1})]),_:1}),a[13]||(a[13]=i("h4",{class:"mt-4 mb-2"},"Outputs",-1)),e(s,{data:$.value.outputs||[],size:"small",border:""},{default:t(()=>[e(l,{prop:"category",label:"Category","min-width":"200"}),e(l,{prop:"success_indicators",label:"Success Indicators","min-width":"200"}),e(l,{prop:"actual_accomplishments",label:"Accomplishment","min-width":"200"}),e(l,{prop:"quality_rating",label:"Quality",width:"90"}),e(l,{prop:"efficiency_rating",label:"Efficiency",width:"100"}),e(l,{prop:"timeliness_rating",label:"Timeliness",width:"110"}),e(l,{prop:"average_rating",label:"Average",width:"90"}),e(l,{prop:"remarks",label:"Remarks","min-width":"160"})]),_:1},8,["data"]),a[14]||(a[14]=i("h4",{class:"mt-4 mb-2"},"Recalibrations",-1)),(h(),H(X,null,te(P,C=>i("div",{key:C.key,class:"mb-4"},[i("div",rt,[i("span",dt,u(C.label),1),e(q,{size:"small",type:"info"},{default:t(()=>[p(u(y($.value)[C.key]?.length||0)+" entries",1)]),_:2},1024)]),e(s,{data:y($.value)[C.key]||[],size:"small",border:""},{default:t(()=>[e(l,{prop:"category",label:"Category","min-width":"200"}),e(l,{prop:"success_indicators",label:"Success Indicators","min-width":"200"}),e(l,{prop:"actual_accomplishments",label:"Accomplishment","min-width":"200"}),e(l,{prop:"quality_rating",label:"Quality",width:"90"}),e(l,{prop:"efficiency_rating",label:"Efficiency",width:"100"}),e(l,{prop:"timeliness_rating",label:"Timeliness",width:"110"}),e(l,{prop:"average_rating",label:"Average",width:"90"}),e(l,{prop:"remarks",label:"Remarks","min-width":"160"}),e(l,{prop:"status_name",label:"Status",width:"140"},{default:t(({row:ae})=>[p(u(ae.status_name||ae.status||"Pending"),1)]),_:1})]),_:1},8,["data"]),(y($.value)[C.key]||[]).length===0?(h(),U(M,{key:0,description:"No entries"})):J("",!0)])),64))])):J("",!0)]),_:1},8,["modelValue"])],64)}}},ft=ne(ct,[["__scopeId","data-v-4adef808"]]),pt={class:"opcr-view"},mt={key:0,class:"space-y-6"},vt={class:"flex justify-between items-center"},_t={class:"info-item"},gt={class:"info-item"},ht={class:"info-item"},yt={class:"info-item"},bt={class:"info-item"},wt={class:"info-item"},Ct={class:"info-item"},xt={class:"flex justify-between items-center"},Rt={class:"position-info"},Pt={class:"position-name"},Ot={class:"employee-name text-muted"},Dt={class:"dialog-footer"},kt={key:0},$t={style:{"text-align":"right",width:"100%"}},Vt={__name:"OPCRView",props:{modelValue:{type:Boolean,default:!1},opcrData:{type:Object,default:null},loading:{type:Boolean,default:!1}},emits:["update:modelValue"],setup(m,{emit:Q}){const v=m,z=Q,E=R(!1),D=R([]),r=R(!1),V=R(null),$=j({get:()=>v.modelValue,set:a=>z("update:modelValue",a)}),P=j(()=>D.value.length?D.value.map(a=>({...a,name:`${a.first_name} ${a.middle_name} ${a.last_name}`.trim(),period_start:a.period_start,period_end:a.period_end})):[]),A=j(()=>P.value[0]?.period_start||""),L=j(()=>P.value[0]?.period_end||"");se(()=>v.opcrData,a=>{a&&a.id&&N(a.id)},{immediate:!0});const g=a=>a?new Date(a).toLocaleDateString():"N/A",f=a=>"info",x=a=>"Active",N=async a=>{try{E.value=!0;const o=await K.getReviewData(a);D.value=o.data.data||o.data||[]}catch(o){console.error("Failed to load OPCR employee data:",o),w.error("Failed to load OPCR office head data"),D.value=[]}finally{E.value=!1}},I=()=>{try{if(!v.opcrData||P.value.length===0){w.warning("No OPCR data to print");return}const a=y(),o=window.open("","_blank");if(!o){w.error("Please allow popups to print");return}o.document.write(a),o.document.close(),o.onload=()=>{setTimeout(()=>{o.print(),o.close()},250)}}catch(a){console.error("Print failed:",a),w.error("Failed to print OPCR")}},G=async()=>{try{if(!v.opcrData||P.value.length===0){w.warning("No OPCR data to export");return}const a=y(),o=window.open("","_blank");if(!o){w.error("Please allow popups to export PDF");return}o.document.write(a),o.document.close(),o.onload=()=>{setTimeout(()=>{o.print(),w.success(`Use your browser's "Save as PDF" option in the print dialog`)},250)}}catch(a){console.error("PDF export failed:",a),w.error("Failed to export PDF file")}},y=()=>{const a=new Date().toLocaleDateString("en-US",{year:"numeric",month:"long",day:"numeric"});let o="";return P.value.forEach((b,F)=>{o+=`
      <tr>
        <td style="border: 1px solid #ddd; padding: 8px; text-align: center;">${F+1}</td>
        <td style="border: 1px solid #ddd; padding: 8px;">${b.name||""}</td>
        <td style="border: 1px solid #ddd; padding: 8px;">${b.position||""}</td>
        <td style="border: 1px solid #ddd; padding: 8px;">${b.status_name||"No status"}</td>
      </tr>
    `}),`
    <!DOCTYPE html>
    <html>
    <head>
      <title>OPCR Report - ${v.opcrData?.department||"N/A"}</title>
      <style>
        @media print {
          @page {
            margin: 1cm;
            size: A4;
          }
        }
        body {
          font-family: Arial, sans-serif;
          margin: 20px;
          color: #333;
        }
        .header {
          text-align: center;
          margin-bottom: 30px;
          border-bottom: 2px solid #333;
          padding-bottom: 20px;
        }
        .header h1 {
          margin: 0;
          font-size: 24px;
          color: #333;
        }
        .header h2 {
          margin: 5px 0;
          font-size: 18px;
          color: #666;
        }
        .info-section {
          margin-bottom: 20px;
          padding: 15px;
          background-color: #f9f9f9;
          border: 1px solid #ddd;
        }
        .info-row {
          display: flex;
          margin-bottom: 10px;
        }
        .info-label {
          font-weight: bold;
          width: 150px;
        }
        .info-value {
          flex: 1;
        }
        table {
          width: 100%;
          border-collapse: collapse;
          margin-top: 20px;
          font-size: 12px;
        }
        th {
          background-color: #4a5568;
          color: white;
          padding: 10px 8px;
          text-align: left;
          font-weight: bold;
          border: 1px solid #ddd;
        }
        td {
          padding: 8px;
          border: 1px solid #ddd;
        }
        tr:nth-child(even) {
          background-color: #f9f9f9;
        }
        .footer {
          margin-top: 30px;
          text-align: right;
          font-size: 11px;
          color: #666;
        }
      </style>
    </head>
    <body>
      <div class="header">
        <h1>OFFICE PERFORMANCE COMMITMENT AND REVIEW (OPCR)</h1>
        <h2>${v.opcrData?.department||"N/A"}</h2>
      </div>
      
      <div class="info-section">
        <div class="info-row">
          <div class="info-label">Department:</div>
          <div class="info-value">${v.opcrData?.department||"N/A"}</div>
        </div>
        <div class="info-row">
          <div class="info-label">Office:</div>
          <div class="info-value">${v.opcrData?.division||"N/A"}</div>
        </div>
        <div class="info-row">
          <div class="info-label">Year:</div>
          <div class="info-value">${v.opcrData?.year||"N/A"}</div>
        </div>
        <div class="info-row">
          <div class="info-label">Section:</div>
          <div class="info-value">${v.opcrData?.section||"N/A"}</div>
        </div>
        <div class="info-row">
          <div class="info-label">Period:</div>
          <div class="info-value">${A.value||v.opcrData?.month_from||"N/A"} to ${L.value||v.opcrData?.month_to||"N/A"}</div>
        </div>
      </div>
      
      <table>
        <thead>
          <tr>
            <th style="width: 5%;">#</th>
            <th style="width: 30%;">Office Head Name</th>
            <th style="width: 25%;">Position</th>
            <th style="width: 20%;">Status</th>
          </tr>
        </thead>
        <tbody>
          ${o}
        </tbody>
      </table>
      
      <div class="footer">
        <p>Generated on: ${a}</p>
      </div>
    </body>
    </html>
  `},_=a=>`${a.first_name||""} ${a.middle_name||""} ${a.last_name||""}`.trim(),c=a=>{V.value=a,r.value=!0};return(a,o)=>{const b=d("el-tag"),F=d("el-col"),W=d("el-row"),q=d("el-card"),T=d("el-table-column"),l=d("el-button"),n=d("el-table"),s=d("el-empty"),M=d("el-dialog"),Y=d("el-descriptions-item"),B=d("el-descriptions"),Z=d("el-drawer"),ee=re("loading");return h(),H(X,null,[e(M,{modelValue:$.value,"onUpdate:modelValue":o[1]||(o[1]=O=>$.value=O),title:`OPCR Details - ${m.opcrData?.department||"N/A"}`,width:"90%","close-on-click-modal":!1},{footer:t(()=>[i("div",Dt,[e(l,{onClick:o[0]||(o[0]=O=>$.value=!1)},{default:t(()=>[...o[14]||(o[14]=[p("Close",-1)])]),_:1}),e(l,{type:"primary",onClick:I},{default:t(()=>[...o[15]||(o[15]=[p("Print",-1)])]),_:1}),e(l,{type:"success",onClick:G},{default:t(()=>[...o[16]||(o[16]=[p("Export",-1)])]),_:1})])]),default:t(()=>[oe((h(),H("div",pt,[m.opcrData?(h(),H("div",mt,[e(q,{shadow:"never"},{header:t(()=>[i("div",vt,[o[4]||(o[4]=i("h3",{class:"text-lg font-semibold"},"OPCR Information",-1)),e(b,{type:f(m.opcrData),size:"large"},{default:t(()=>[p(u(x(m.opcrData)),1)]),_:1},8,["type"])])]),default:t(()=>[e(W,{gutter:20},{default:t(()=>[e(F,{span:6},{default:t(()=>[i("div",_t,[o[5]||(o[5]=i("label",null,"Department:",-1)),i("span",null,u(m.opcrData.department||"N/A"),1)])]),_:1}),e(F,{span:6},{default:t(()=>[i("div",gt,[o[6]||(o[6]=i("label",null,"Office:",-1)),i("span",null,u(m.opcrData.division||"N/A"),1)])]),_:1}),e(F,{span:6},{default:t(()=>[i("div",ht,[o[7]||(o[7]=i("label",null,"Section:",-1)),i("span",null,u(m.opcrData.section||"N/A"),1)])]),_:1}),e(F,{span:6},{default:t(()=>[i("div",yt,[o[8]||(o[8]=i("label",null,"Year:",-1)),i("span",null,u(m.opcrData.year||"N/A"),1)])]),_:1})]),_:1}),e(W,{gutter:20},{default:t(()=>[e(F,{span:6},{default:t(()=>[i("div",bt,[o[9]||(o[9]=i("label",null,"Period From:",-1)),i("span",null,u(A.value||m.opcrData.month_from||"N/A"),1)])]),_:1}),e(F,{span:6},{default:t(()=>[i("div",wt,[o[10]||(o[10]=i("label",null,"Period To:",-1)),i("span",null,u(L.value||m.opcrData.month_to||"N/A"),1)])]),_:1}),e(F,{span:6},{default:t(()=>[i("div",Ct,[o[11]||(o[11]=i("label",null,"Created Date:",-1)),i("span",null,u(g(m.opcrData.created_at)),1)])]),_:1})]),_:1})]),_:1}),e(q,{shadow:"never"},{header:t(()=>[i("div",xt,[o[12]||(o[12]=i("h3",{class:"text-lg font-semibold"},"Office Head Performance Ratings",-1)),e(b,{type:"info",size:"large"},{default:t(()=>[p(u(P.value.length)+" office heads",1)]),_:1})])]),default:t(()=>[oe((h(),H("div",null,[P.value.length>0?(h(),U(n,{key:0,data:P.value,border:"",stripe:""},{default:t(()=>[e(T,{prop:"employee_no",label:"Office Head","min-width":"260"},{default:t(({row:O})=>[i("div",Rt,[i("div",Pt,u(_(O)),1),i("div",Ot,u(O.position||"N/A"),1)])]),_:1}),e(T,{prop:"status_name",label:"Status",width:"160"},{default:t(({row:O})=>[e(b,{type:"info"},{default:t(()=>[p(u(O.status_name||"No status"),1)]),_:2},1024)]),_:1}),e(T,{label:"Action",width:"140"},{default:t(({row:O})=>[e(l,{type:"primary",plain:"",size:"small",onClick:C=>c(O)},{default:t(()=>[...o[13]||(o[13]=[p(" View ",-1)])]),_:1},8,["onClick"])]),_:1})]),_:1},8,["data"])):(h(),U(s,{key:1,description:"No office heads found"}))])),[[ee,E.value]])]),_:1})])):(h(),U(s,{key:1,description:"No OPCR data available"}))])),[[ee,m.loading]])]),_:1},8,["modelValue","title"]),e(Z,{modelValue:r.value,"onUpdate:modelValue":o[3]||(o[3]=O=>r.value=O),title:"OPCR Preview",size:"60%"},{footer:t(()=>[i("div",$t,[e(l,{onClick:o[2]||(o[2]=O=>r.value=!1)},{default:t(()=>[...o[18]||(o[18]=[p("Close",-1)])]),_:1})])]),default:t(()=>[V.value?(h(),H("div",kt,[e(B,{column:2,border:""},{default:t(()=>[e(Y,{label:"Office Head"},{default:t(()=>[p(u(_(V.value)),1)]),_:1}),e(Y,{label:"Employee No."},{default:t(()=>[p(u(V.value.employee_no||"N/A"),1)]),_:1}),e(Y,{label:"Position"},{default:t(()=>[p(u(V.value.position||"N/A"),1)]),_:1}),e(Y,{label:"Status"},{default:t(()=>[p(u(V.value.status_name||"No status"),1)]),_:1}),e(Y,{label:"Period From"},{default:t(()=>[p(u(V.value.period_start||"N/A"),1)]),_:1}),e(Y,{label:"Period To"},{default:t(()=>[p(u(V.value.period_end||"N/A"),1)]),_:1})]),_:1}),o[17]||(o[17]=i("h4",{class:"mt-4 mb-2"},"Outputs",-1)),e(n,{data:V.value.outputs||[],size:"small",border:""},{default:t(()=>[e(T,{prop:"output",label:"Output","min-width":"200"}),e(T,{prop:"success_indicators",label:"Success Indicators","min-width":"200"}),e(T,{prop:"accomplishment",label:"Accomplishment","min-width":"200"}),e(T,{prop:"quality_rating",label:"Quality",width:"90"}),e(T,{prop:"efficiency_rating",label:"Efficiency",width:"100"}),e(T,{prop:"timeliness_rating",label:"Timeliness",width:"110"}),e(T,{prop:"average_rating",label:"Average",width:"90"}),e(T,{prop:"remarks",label:"Remarks","min-width":"160"})]),_:1},8,["data"])])):J("",!0)]),_:1},8,["modelValue"])],64)}}},At=ne(Vt,[["__scopeId","data-v-3caf699f"]]);function Nt(){const m=R(!1),Q=R([]),v=R(null),z=R([]),E=R(null);return{loading:m,opcrRatings:Q,formData:v,reviewData:z,currentOPCR:E,fetchOPCRRatings:async()=>{try{m.value=!0;const g=await K.getOPCRRatings();return Q.value=g.data.data||g.data||[],Q.value}catch(g){throw w.error("Failed to load OPCR ratings: "+(g.response?.data?.message||g.message)),console.error("Fetch error:",g),g}finally{m.value=!1}},fetchFormData:async(g=0)=>{try{m.value=!0;const f=await K.getFormData(g);return v.value=f.data.data||f.data||{},E.value=v.value.opcr_ratings?.[0]||null,v.value}catch(f){throw w.error("Failed to load form data: "+(f.response?.data?.message||f.message)),f}finally{m.value=!1}},saveOPCR:async(g,f)=>{try{m.value=!0;const x=await K.saveOPCR(g,f);return w.success(x.data.message||"OPCR saved successfully"),x.data}catch(x){const N=x.response?.data?.message||"Save failed";throw w.error(N),x}finally{m.value=!1}},fetchReviewData:async g=>{try{m.value=!0;const f=await K.getReviewData(g);return z.value=f.data.data||f.data||[],z.value}catch(f){throw w.error("Failed to load review data: "+(f.response?.data?.message||f.message)),f}finally{m.value=!1}},getAdjectivalRating:async g=>{try{const f=await K.getAdjectivalRating(g),x=f.data.data||f.data||[];return x.length>0?x[0].adjectival_rating:""}catch(f){return console.error("Failed to get adjectival rating:",f),""}},saveRatings:async(g,f)=>{try{m.value=!0;const x=await K.saveRatings(g,f);return w.success(x.data.message||"Ratings saved successfully"),x.data}catch(x){const N=x.response?.data?.message||"Save ratings failed";throw w.error(N),x}finally{m.value=!1}},deleteOPCR:async g=>{try{m.value=!0;const f=await K.destroy(g);return w.success(f.data.message||"OPCR deleted successfully"),f.data}catch(f){const x=f.response?.data?.message||"Delete failed";throw w.error(x),f}finally{m.value=!1}}}}const Ft={__name:"OPCR",setup(m){const{loading:Q,opcrRatings:v,formData:z,reviewData:E,fetchOPCRRatings:D,fetchFormData:r,saveOPCR:V,fetchReviewData:$,getAdjectivalRating:P,saveRatings:A,deleteOPCR:L}=Nt(),g=R(!1),f=R(!1),x=R(!1),N=R(null),I=R(!1),G=R(!1),y=R(!1),_=async()=>{try{await D()}catch(l){console.error("Failed to reload OPCR ratings:",l)}},c=async()=>{try{I.value=!0,N.value=null,await r(0),g.value=!0}catch(l){console.error("Failed to load form data:",l),w.error("Failed to load form data")}finally{I.value=!1}},a=async l=>{try{I.value=!0,N.value=l,await r(l.id),g.value=!0}catch(n){console.error("Failed to load form data:",n),w.error("Failed to load form data")}finally{I.value=!1}},o=l=>{N.value=l,x.value=!0},b=async l=>{try{G.value=!0,await $(l.id),f.value=!0}catch(n){console.error("Failed to load review data:",n),w.error("Failed to load office head review data")}finally{G.value=!1}},F=async l=>{try{await V(l.id,l),g.value=!1,await _()}catch(n){console.error("Save failed:",n)}},W=async l=>{try{const n=E.value.length>0?E.value[0].id:0;await A(n,l),f.value=!1,w.success("Office head ratings saved successfully")}catch(n){console.error("Save ratings failed:",n)}},q=async l=>{try{return await P(l)}catch(n){return console.error("Failed to get adjectival rating:",n),""}},T=async l=>{try{await Se.confirm("Are you sure you want to delete this OPCR record? This action cannot be undone.","Confirm Delete",{type:"warning"}),await L(l.id),await _()}catch(n){n!=="cancel"&&console.error("Delete failed:",n)}};return Fe(()=>{_()}),(l,n)=>(h(),U(ye,{title:"OPCR",subtitle:"Office Performance Commitment and Review"},{default:t(()=>[e(Ie,{"opcr-ratings":k(v),loading:k(Q),onRefresh:_,onAdd:c,onView:o,onEdit:a,onReview:b,onDelete:T},null,8,["opcr-ratings","loading"]),e(Qe,{modelValue:g.value,"onUpdate:modelValue":n[0]||(n[0]=s=>g.value=s),"opcr-data":N.value,"form-data":k(z),loading:I.value,onSave:F},null,8,["modelValue","opcr-data","form-data","loading"]),e(ft,{modelValue:f.value,"onUpdate:modelValue":n[1]||(n[1]=s=>f.value=s),"review-data":k(E),loading:G.value,onSaveRatings:W,onGetAdjectivalRating:q},null,8,["modelValue","review-data","loading"]),e(At,{modelValue:x.value,"onUpdate:modelValue":n[2]||(n[2]=s=>x.value=s),"opcr-data":N.value,loading:y.value},null,8,["modelValue","opcr-data","loading"])]),_:1}))}},zt=ne(Ft,[["__scopeId","data-v-f0fbc257"]]);export{zt as default};
