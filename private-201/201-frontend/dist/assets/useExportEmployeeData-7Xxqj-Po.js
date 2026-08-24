import{r as w,E as c,ao as k}from"./index-BtP9JMpM.js";function U(){const y=w(!1),f=w([]),h=w(0),C=async(r={})=>{try{y.value=!0;const a=await k.getEmployeeData();return f.value=a.data.data||a.data||[],f.value.length>0&&(console.log("Sample employee data:",f.value[0]),console.log("Employee name field:",f.value[0].name),console.log("Employee email field:",f.value[0].email),console.log("All employee fields:",Object.keys(f.value[0]))),f.value}catch(a){throw c.error("Failed to load employee data: "+(a.response?.data?.message||a.message)),console.error("Fetch error:",a),a}finally{y.value=!1}},v=async(r,a="employee_data.csv")=>{try{if(!r||r.length===0){c.warning("No data to export");return}const e=Object.keys(r[0]),n=g=>typeof g=="boolean"?g?"Y":"N":g,s=[e.join(","),...r.map(g=>e.map(m=>{let t=g[m]??"";return t=n(t),typeof t=="string"&&(t=t.trim()),`"${String(t).replace(/"/g,'""')}"`}).join(","))].join(`
`),i=new Blob([s],{type:"text/csv;charset=utf-8;"}),l=document.createElement("a"),b=URL.createObjectURL(i);l.setAttribute("href",b),l.setAttribute("download",a),l.style.visibility="hidden",document.body.appendChild(l),l.click(),document.body.removeChild(l),c.success("CSV export completed successfully")}catch(e){throw console.error("CSV export failed:",e),c.error("Failed to export CSV file"),e}},E=async(r,a="employee_data.xlsx")=>{try{const e=await k.exportData("xlsx"),n=e.headers["content-type"]||"application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",s=new Blob([e.data],{type:n}),i=URL.createObjectURL(s),l=document.createElement("a");l.href=i,l.download=a||"employee_data.xlsx",document.body.appendChild(l),l.click(),document.body.removeChild(l),URL.revokeObjectURL(i),c.success("Excel export completed successfully")}catch(e){throw console.error("Excel export failed:",e),c.error("Failed to export Excel file"),e}},D=async(r,a="employee_data.pdf")=>{try{if(!r||r.length===0){c.warning("No data to export");return}const e=_(r),n=window.open("","_blank");if(!n){c.error("Please allow popups to export PDF");return}n.document.write(e),n.document.close(),n.onload=()=>{setTimeout(()=>{n.print(),c.success(`Use your browser's "Save as PDF" option in the print dialog`)},250)}}catch(e){throw console.error("PDF export failed:",e),c.error("Failed to export PDF file"),e}},_=r=>{const a=new Date().toLocaleDateString("en-US",{year:"numeric",month:"long",day:"numeric"});let e=r.length>0?Object.keys(r[0]):[];e=e.filter(t=>t!=="id");const n=["name","photo","employee_no","access_no"],s=[];n.forEach(t=>{e.includes(t)&&s.push(t)}),e.forEach(t=>{s.includes(t)||s.push(t)}),e=s;const i=t=>String(t).replace(/&/g,"&amp;").replace(/</g,"&lt;").replace(/>/g,"&gt;").replace(/"/g,"&quot;").replace(/'/g,"&#039;"),l=t=>{if(!t||typeof t!="string")return"";const o=t.trim();if(!o)return"";if(o.startsWith("data:")||/^https?:\/\//i.test(o))return o;if(/^[A-Za-z0-9+/=]+$/.test(o)&&o.length>40)return`data:image/jpeg;base64,${o}`;const u="http://localhost:8082/api".replace(/\/api\/?$/i,""),p=o.startsWith("/")?o:`/${o}`;return u?`${u}${p}`:p},b=new Set(["is_dual_citizent","by_birth","by_naturalization","is_shifting","is_plantilla","is_employee","is_teaching","active","is_hold"]),g=(t,o)=>{if(!b.has(t))return o;if(typeof o=="boolean")return o?"Y":"N";if(o===1||o===0)return o===1?"Y":"N";if(typeof o=="string"){const d=o.trim();if(d==="1")return"Y";if(d==="0")return"N"}return o};let m="";r.forEach((t,o)=>{m+="<tr>",m+=`<td style="border: 1px solid #ddd; padding: 8px; text-align: center;">${o+1}</td>`,e.forEach(d=>{const u=t[d]??"";let p=u;if(typeof u=="number"?p=u.toLocaleString():u instanceof Date&&(p=new Date(u).toLocaleDateString()),p=g(d,p),/photo|avatar|image/i.test(String(d))){const P=l(p);m+=`<td style="border: 1px solid #ddd; padding: 8px; text-align: center;">
                        ${P?`<img src="${i(P)}" alt="Photo" style="max-height: 60px; max-width: 80px; object-fit: cover; border-radius: 4px;" />`:""}
                    </td>`}else m+=`<td style="border: 1px solid #ddd; padding: 8px;">${i(p)}</td>`}),m+="</tr>"});let x="<tr>";return x+='<th style="border: 1px solid #ddd; padding: 10px; text-align: center; background-color: #4a5568; color: white;">#</th>',e.forEach(t=>{const o=t.replace(/_/g," ").replace(/\b\w/g,d=>d.toUpperCase());x+=`<th style="border: 1px solid #ddd; padding: 10px; background-color: #4a5568; color: white;">${o}</th>`}),x+="</tr>",`
            <!DOCTYPE html>
            <html>
            <head>
                <title>Employee Data Export</title>
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
                        font-size: 11px;
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
                    img {
                        display: inline-block;
                    }
                    tr:nth-child(even) {
                        background-color: #f9f9f9;
                    }
                </style>
            </head>
            <body>
                <h1>Employee Data Export</h1>
                <div class="report-info">
                    <p>Generated on: ${a}</p>
                    <p>Total Records: ${r.length}</p>
                </div>
                <table>
                    <thead>
                        ${x}
                    </thead>
                    <tbody>
                        ${m}
                    </tbody>
                </table>
            </body>
            </html>
        `};return{loading:y,employeeData:f,exportProgress:h,fetchEmployeeData:C,exportData:async(r,a,e)=>{try{h.value=0,y.value=!0;const n=setInterval(()=>{h.value<90&&(h.value+=10)},100);switch(r.toLowerCase()){case"csv":await v(a,e||"employee_data.csv");break;case"xlsx":case"excel":await E(a,e||"employee_data.xlsx");break;case"pdf":await D(a,e||"employee_data.pdf");break;default:throw new Error("Unsupported export format")}clearInterval(n),h.value=100,setTimeout(()=>{h.value=0},2e3)}catch(n){throw h.value=0,n}finally{y.value=!1}},exportToCSV:v,exportToExcel:E,exportToPDF:D,getFilteredData:(r,a={})=>!a||Object.keys(a).length===0?r:r.filter(e=>Object.entries(a).every(([n,s])=>{if(!s)return!0;const i=e[n];return typeof i=="string"?i.toLowerCase().includes(s.toLowerCase()):i===s})),getPdfPreviewUrl:r=>{if(!r||r.length===0)return"";const a=_(r),e=new Blob([a],{type:"text/html;charset=utf-8;"});return URL.createObjectURL(e)}}}export{U as u};
