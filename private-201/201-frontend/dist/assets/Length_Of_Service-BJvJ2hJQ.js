import{r as y,an as re,E as v,_ as de,c as k,C as i,D as ie,y as u,e as d,H as a,f as l,j as p,w as ce,G as s,I as x,J as pe,d as N,F as T,k as $,h as D,L as ue,am as me,N as he,s as ge,O as fe,g as _,u as _e,o as ve}from"./index-BtP9JMpM.js";import{P as ye}from"./PageScaffold-e9m5iALX.js";import{u as xe}from"./useExportEmployeeData-7Xxqj-Po.js";function be(){const b=y(!1),m=y([]);return{loading:b,lengthOfServiceRecords:m,fetchLengthOfServiceRecords:async()=>{try{b.value=!0;const r=await re.getLengthOfServiceRecords();return m.value=r.data.data||r.data||[],m.value}catch(r){throw v.error("Failed to load length of service records: "+(r.response?.data?.message||r.message)),console.error("Fetch error:",r),r}finally{b.value=!1}}}}const we={class:"grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6"},Se={class:"bg-white rounded-lg shadow-sm border border-gray-200 p-6"},Ee={class:"text-center"},ke={class:"text-3xl font-bold text-gray-900 mb-2"},De={class:"bg-white rounded-lg shadow-sm border border-gray-200 p-6"},Ce={class:"text-center"},Le={class:"text-3xl font-bold text-blue-600 mb-2"},Oe={class:"bg-white rounded-lg shadow-sm border border-gray-200 p-6"},Pe={class:"text-center"},Re={class:"text-3xl font-bold text-green-600 mb-2"},Fe={class:"bg-white rounded-lg shadow-sm border border-gray-200 p-6"},Ne={class:"text-center"},Te={class:"text-3xl font-bold text-purple-600 mb-2"},$e={class:"bg-white rounded-lg shadow"},Ve={class:"px-6 py-4 border-b border-gray-200"},ze={class:"flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4"},Be={class:"flex items-center space-x-4"},Ye={class:"relative"},Ae={class:"action-buttons"},He={class:"flex items-center space-x-3"},Me={class:"min-w-0"},Ue={class:"font-medium text-gray-900 truncate"},je={class:"text-center"},Ie={class:"font-bold text-lg text-blue-600"},We={class:"text-sm"},Ge={class:"px-6 py-4 border-t border-gray-200"},qe={class:"flex items-center justify-between"},Je={class:"text-sm text-gray-500"},Qe={__name:"LengthOfServiceList",props:{lengthOfServiceRecords:{type:Array,default:()=>[]},loading:{type:Boolean,default:!1}},setup(b){const m=b,{exportToExcel:L}=xe(),r=y(!1),w=y(""),C=y(""),S=y(1),E=y(25),V=y("calc(100vh - 400px)"),z=[{key:"employee_no",label:"Employee No."},{key:"employee",label:"Employee"},{key:"department",label:"Department"},{key:"position",label:"Position"},{key:"employment_type",label:"Employment Type"},{key:"date_hired",label:"Date Hired"},{key:"length_years",label:"Years of Service"},{key:"length_detailed",label:"Detailed Service"},{key:"branch",label:"Branch"}],c=y(["employee_no","employee","department","position","employment_type","date_hired","length_years","length_detailed"]),B=k(()=>[...new Set(m.lengthOfServiceRecords.map(e=>e.department).filter(Boolean))].sort()),g=k(()=>{let t=m.lengthOfServiceRecords;if(w.value){const e=w.value.toLowerCase();t=t.filter(n=>n.name?.toLowerCase().includes(e)||n.employee_no?.toLowerCase().includes(e)||n.department?.toLowerCase().includes(e)||n.position?.toLowerCase().includes(e))}return C.value&&(t=t.filter(e=>e.department===C.value)),t}),Y=k(()=>{const t=(S.value-1)*E.value,e=t+E.value;return g.value.slice(t,e)}),A=k(()=>m.lengthOfServiceRecords.length),H=k(()=>m.lengthOfServiceRecords.filter(t=>parseFloat(t.length)<1).length),M=k(()=>m.lengthOfServiceRecords.filter(t=>{const e=parseFloat(t.length);return e>=1&&e<=5}).length),U=k(()=>m.lengthOfServiceRecords.filter(t=>parseFloat(t.length)>5).length),j=t=>{const e=c.value.indexOf(t);e>-1?c.value.splice(e,1):c.value.push(t)},I=t=>{E.value=t,S.value=1},W=t=>{S.value=t},P=t=>t?new Date(t).toLocaleDateString():"",G=t=>{if(!t)return"";const e=t.toLowerCase();return e.includes("permanent")?"success":e.includes("contractual")?"warning":e.includes("casual")?"info":""},q=()=>{try{if(g.value.length===0){v.warning("No data to print");return}const t=F(g.value),e=window.open("","_blank");if(!e){v.error("Please allow popups to print");return}e.document.write(t),e.document.close(),e.onload=()=>{setTimeout(()=>{e.print(),e.close()},250)}}catch(t){console.error("Print failed:",t),v.error("Failed to print length of service records")}},J=async()=>{try{if(r.value=!0,g.value.length===0){v.warning("No data to export");return}const t=g.value.map(n=>({ID:n.id||"","Employee No.":n.employee_no||"","Employee Name":n.name||"",Department:n.department||"",Position:n.position||"","Employment Type":n.employment_type||"","Date Hired":n.date_hired?P(n.date_hired):"","Years of Service":n.length||"","Detailed Service":n.lenth||"",Branch:n.branch||""})),e=`length_of_service_${new Date().toISOString().split("T")[0]}.xlsx`;await L(t,e)}catch(t){console.error("Excel export failed:",t)}finally{r.value=!1}},Q=async()=>{try{if(r.value=!0,g.value.length===0){v.warning("No data to export");return}const t=F(g.value),e=window.open("","_blank");if(!e){v.error("Please allow popups to export PDF");return}e.document.write(t),e.document.close(),e.onload=()=>{setTimeout(()=>{e.print(),v.success(`Use your browser's "Save as PDF" option in the print dialog`)},250)}}catch(t){console.error("PDF export failed:",t),v.error("Failed to export PDF file")}finally{r.value=!1}},F=t=>{const e=new Date().toLocaleDateString("en-US",{year:"numeric",month:"long",day:"numeric"});let n="";return t.forEach((h,R)=>{n+=`
      <tr>
        <td style="border: 1px solid #ddd; padding: 8px; text-align: center;">${R+1}</td>
        <td style="border: 1px solid #ddd; padding: 8px;">${h.employee_no||""}</td>
        <td style="border: 1px solid #ddd; padding: 8px;">${h.name||""}</td>
        <td style="border: 1px solid #ddd; padding: 8px;">${h.department||""}</td>
        <td style="border: 1px solid #ddd; padding: 8px;">${h.position||""}</td>
        <td style="border: 1px solid #ddd; padding: 8px;">${h.employment_type||""}</td>
        <td style="border: 1px solid #ddd; padding: 8px;">${h.date_hired?P(h.date_hired):""}</td>
        <td style="border: 1px solid #ddd; padding: 8px; text-align: center;">${h.length||""}</td>
        <td style="border: 1px solid #ddd; padding: 8px;">${h.lenth||""}</td>
        <td style="border: 1px solid #ddd; padding: 8px;">${h.branch||""}</td>
      </tr>
    `}),`
    <!DOCTYPE html>
    <html>
    <head>
      <title>Length of Service Report</title>
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
      <h1>Length of Service Report</h1>
      <div class="report-info">
        <p>Generated on: ${e}</p>
        <p>Total Records: ${t.length}</p>
      </div>
      <table>
        <thead>
          <tr>
            <th style="width: 50px;">No.</th>
            <th>Employee No.</th>
            <th>Employee Name</th>
            <th>Department</th>
            <th>Position</th>
            <th>Employment Type</th>
            <th>Date Hired</th>
            <th style="width: 100px;">Years of Service</th>
            <th>Detailed Service</th>
            <th>Branch</th>
          </tr>
        </thead>
        <tbody>
          ${n}
        </tbody>
      </table>
    </body>
    </html>
  `};return(t,e)=>{const n=i("el-icon"),h=i("el-input"),R=i("el-option"),X=i("el-select"),O=i("el-button"),Z=i("el-checkbox"),K=i("el-dropdown-item"),ee=i("el-dropdown-menu"),te=i("el-dropdown"),f=i("el-table-column"),le=i("el-avatar"),oe=i("el-tag"),ne=i("el-table"),ae=i("el-pagination"),se=ie("loading");return d(),u(ye,{title:"Length of Service",subtitle:"View employee length of service records"},{default:a(()=>[l("div",we,[l("div",Se,[l("div",Ee,[l("div",ke,p(A.value),1),e[5]||(e[5]=l("div",{class:"text-sm font-medium text-gray-500 uppercase tracking-wide"},"TOTAL EMPLOYEES",-1))])]),l("div",De,[l("div",Ce,[l("div",Le,p(H.value),1),e[6]||(e[6]=l("div",{class:"text-sm font-medium text-gray-500 uppercase tracking-wide"},"NEW EMPLOYEES (< 1 YEAR)",-1))])]),l("div",Oe,[l("div",Pe,[l("div",Re,p(M.value),1),e[7]||(e[7]=l("div",{class:"text-sm font-medium text-gray-500 uppercase tracking-wide"},"EXPERIENCED (1-5 YEARS)",-1))])]),l("div",Fe,[l("div",Ne,[l("div",Te,p(U.value),1),e[8]||(e[8]=l("div",{class:"text-sm font-medium text-gray-500 uppercase tracking-wide"},"VETERAN (> 5 YEARS)",-1))])])]),l("div",$e,[l("div",Ve,[l("div",ze,[l("div",Be,[l("div",Ye,[s(h,{modelValue:w.value,"onUpdate:modelValue":e[0]||(e[0]=o=>w.value=o),placeholder:"Search employees...",style:{width:"300px"},clearable:""},{prefix:a(()=>[s(n,null,{default:a(()=>[s(x(pe))]),_:1})]),_:1},8,["modelValue"])]),s(X,{modelValue:C.value,"onUpdate:modelValue":e[1]||(e[1]=o=>C.value=o),placeholder:"Filter by department",clearable:"",style:{width:"300px"}},{default:a(()=>[(d(!0),N(T,null,$(B.value,o=>(d(),u(R,{key:o,label:o,value:o},null,8,["label","value"]))),128))]),_:1},8,["modelValue"])]),l("div",Ae,[s(O,{plain:"",onClick:q,loading:r.value},{default:a(()=>[s(n,null,{default:a(()=>[s(x(ue))]),_:1}),e[9]||(e[9]=D(" Print ",-1))]),_:1},8,["loading"]),s(O,{plain:"",onClick:J,loading:r.value},{default:a(()=>[s(n,null,{default:a(()=>[s(x(me))]),_:1}),e[10]||(e[10]=D(" Excel ",-1))]),_:1},8,["loading"]),s(O,{plain:"",onClick:Q,loading:r.value},{default:a(()=>[s(n,null,{default:a(()=>[s(x(he))]),_:1}),e[11]||(e[11]=D(" PDF ",-1))]),_:1},8,["loading"]),s(te,{trigger:"click",class:"ml-2"},{dropdown:a(()=>[s(ee,{class:"col-menu"},{default:a(()=>[(d(),N(T,null,$(z,o=>s(K,{key:o.key,class:"col-item",onClick:e[2]||(e[2]=fe(()=>{},["stop"]))},{default:a(()=>[s(Z,{"model-value":c.value.includes(o.key),onChange:Ze=>j(o.key)},{default:a(()=>[D(p(o.label),1)]),_:2},1032,["model-value","onChange"])]),_:2},1024)),64))]),_:1})]),default:a(()=>[s(O,{plain:""},{default:a(()=>[s(n,null,{default:a(()=>[s(x(ge))]),_:1}),e[12]||(e[12]=D(" Column Visibility ",-1))]),_:1})]),_:1})])])]),ce((d(),u(ne,{data:Y.value,border:"",stripe:"",height:V.value,style:{width:"100%"}},{default:a(()=>[c.value.includes("employee_no")?(d(),u(f,{key:0,prop:"employee_no",label:"Employee No.",width:"120",fixed:"left"})):_("",!0),c.value.includes("employee")?(d(),u(f,{key:1,label:"Employee","min-width":"200",fixed:"left"},{default:a(({row:o})=>[l("div",He,[s(le,{size:32,src:o.photo?`data:image/jpeg;base64,${o.photo}`:null,class:"flex-shrink-0"},{default:a(()=>[s(n,null,{default:a(()=>[s(x(_e))]),_:1})]),_:1},8,["src"]),l("div",Me,[l("div",Ue,p(o.name),1)])])]),_:1})):_("",!0),c.value.includes("department")?(d(),u(f,{key:2,prop:"department",label:"Department","min-width":"180"})):_("",!0),c.value.includes("position")?(d(),u(f,{key:3,prop:"position",label:"Position","min-width":"180"})):_("",!0),c.value.includes("employment_type")?(d(),u(f,{key:4,prop:"employment_type",label:"Employment Type",width:"140"},{default:a(({row:o})=>[s(oe,{type:G(o.employment_type),size:"small"},{default:a(()=>[D(p(o.employment_type),1)]),_:2},1032,["type"])]),_:1})):_("",!0),c.value.includes("date_hired")?(d(),u(f,{key:5,prop:"date_hired",label:"Date Hired",width:"120"},{default:a(({row:o})=>[l("span",null,p(P(o.date_hired)),1)]),_:1})):_("",!0),c.value.includes("length_years")?(d(),u(f,{key:6,prop:"length",label:"Years of Service",width:"140",align:"center"},{default:a(({row:o})=>[l("div",je,[l("div",Ie,p(o.length),1),e[13]||(e[13]=l("div",{class:"text-xs text-gray-500"},"years",-1))])]),_:1})):_("",!0),c.value.includes("length_detailed")?(d(),u(f,{key:7,prop:"lenth",label:"Detailed Service","min-width":"160"},{default:a(({row:o})=>[l("span",We,p(o.lenth),1)]),_:1})):_("",!0),c.value.includes("branch")?(d(),u(f,{key:8,prop:"branch",label:"Branch","min-width":"150"})):_("",!0)]),_:1},8,["data","height"])),[[se,b.loading]]),l("div",Ge,[l("div",qe,[l("div",Je," Showing "+p((S.value-1)*E.value+1)+" to "+p(Math.min(S.value*E.value,g.value.length))+" of "+p(g.value.length)+" results ",1),s(ae,{"current-page":S.value,"onUpdate:currentPage":e[3]||(e[3]=o=>S.value=o),"page-size":E.value,"onUpdate:pageSize":e[4]||(e[4]=o=>E.value=o),"page-sizes":[10,25,50,100],total:g.value.length,layout:"sizes, prev, pager, next, jumper",onSizeChange:I,onCurrentChange:W},null,8,["current-page","page-size","total"])])])])]),_:1})}}},Xe=de(Qe,[["__scopeId","data-v-c7bbf072"]]),lt={__name:"Length_Of_Service",setup(b){const{loading:m,lengthOfServiceRecords:L,fetchLengthOfServiceRecords:r}=be();return ve(async()=>{try{await r()}catch(w){console.error("Failed to fetch length of service records:",w)}}),(w,C)=>(d(),u(Xe,{"length-of-service-records":x(L),loading:x(m)},null,8,["length-of-service-records","loading"]))}};export{lt as default};
