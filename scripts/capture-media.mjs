#!/usr/bin/env node
import fs from 'node:fs';
import os from 'node:os';
import path from 'node:path';
import { spawnSync } from 'node:child_process';
import { fileURLToPath } from 'node:url';
const __dirname=path.dirname(fileURLToPath(import.meta.url));
const wpScript=path.join(__dirname,'wp');
function usage(m=''){if(m)console.error(m);console.error('Usage: scripts/wp capture URL NAME [--selector=CSS] [--mode=page|viewport] [--width=1440] [--height=1200] [--max-height=3000] [--quality=78] [--post-id=123] [--alt=...] [--title=...] [--caption=...] [--description=...] [--set-featured] [--mobile]');process.exit(2)}
const [url,name,...rest]=process.argv.slice(2); if(!url||!name) usage();
const o={url,filename:name,width:1440,height:1200,max_height:3000,quality:78,full_page:true,selector:'',post_id:0,alt:'',title:'',caption:'',description:'',set_featured:false};
for(const a of rest){if(a==='--set-featured')o.set_featured=true;else if(a==='--mobile'){o.width=390;o.height=844;o.full_page=false;}else if(a.startsWith('--selector='))o.selector=a.slice(11);else if(a.startsWith('--mode='))o.full_page=a.slice(7)!=='viewport';else if(a.startsWith('--width='))o.width=Number(a.slice(8));else if(a.startsWith('--height='))o.height=Number(a.slice(9));else if(a.startsWith('--max-height='))o.max_height=Number(a.slice(13));else if(a.startsWith('--quality='))o.quality=Number(a.slice(10));else if(a.startsWith('--post-id='))o.post_id=Number(a.slice(10));else if(a.startsWith('--alt='))o.alt=a.slice(6);else if(a.startsWith('--title='))o.title=a.slice(8);else if(a.startsWith('--caption='))o.caption=a.slice(10);else if(a.startsWith('--description='))o.description=a.slice(14);else if(a.startsWith('--max-width=')||a.startsWith('--wait-for=')||a.startsWith('--delay=')){}else usage(`Unknown option: ${a}`)}
const f=path.join(os.tmpdir(),`cwb-shot-${process.pid}-${Date.now()}.json`); fs.writeFileSync(f,JSON.stringify(o));
try{const r=spawnSync(wpScript,['screenshot-capture',f],{encoding:'utf8',env:process.env});if(r.status!==0){console.error(r.stderr||r.stdout);process.exit(r.status||1)}const data=JSON.parse(r.stdout);if(data.media?.gutenberg&&!data.gutenberg_block)data.gutenberg_block=data.media.gutenberg;console.log(JSON.stringify(data,null,2));}finally{try{fs.unlinkSync(f)}catch{}}
