function i(a){return a<1024*1024?`${Math.max(1,Math.ceil(a/1024))} KB`:`${(a/(1024*1024)).toFixed(1)} MB`}export{i as f};
