const fs = require('node:fs');
const vm = require('node:vm');
const assert = require('node:assert/strict');
(async () => {
  function node() {
    return { dataset:{}, attrs:{}, events:{}, children:[], textContent:'', classList:{contains:()=>false},
      setAttribute(k,v){this.attrs[k]=v},getAttribute(k){return this.attrs[k] ?? null},hasAttribute(k){return k in this.attrs},removeAttribute(k){delete this.attrs[k]},
      append(...items){this.children.push(...items)},replaceChildren(...items){this.children=items},addEventListener(k,fn){this.events[k]=fn} };
  }
  const header=node();header.textContent='Descrição';header.dataset.sortKey='descricao';
  const table=node();table.setAttribute('data-server-sort','');table.tHead={rows:[{cells:[header]}]};
  const original={remove(){table.tBodies=[]}};table.tBodies=[original];
  table.before=()=>{};table.appendChild=body=>table.tBodies.push(body);
  let paginationUpdated=false;
  table.closest=()=>({querySelector:()=>({replaceWith(){paginationUpdated=true}})});
  const sorted={name:'sorted rows'};
  const updated={tBodies:[sorted],closest:()=>({querySelector:()=>({})})};
  let initialize,requested,fail=false,navigationCount=0;
  const document={querySelectorAll:()=>[table],querySelector:()=>null,addEventListener:(k,fn)=>initialize=fn,createElement:()=>node(),importNode:body=>body};
  const window={location:{href:'https://example.test/movimentacoes?conta_id=7&pagina=3',assign(){navigationCount++}}};
  vm.runInNewContext(fs.readFileSync('public/js/table-sort.js','utf8'),{
    document,window,Intl,URL,DOMParser:class{parseFromString(){return {querySelector:()=>updated}}},
    fetch:async(url)=>{requested=url;return {ok:!fail,redirected:false,text:async()=>'<table></table>'}}
  });
  initialize();const button=header.children[0];
  await button.events.click();
  assert.equal(navigationCount,0);assert.equal(window.location.href,'https://example.test/movimentacoes?conta_id=7&pagina=3');
  assert.equal(requested.searchParams.get('conta_id'),'7');assert.equal(requested.searchParams.get('pagina'),'1');
  assert.equal(requested.searchParams.get('direcao'),'asc');assert.equal(header.getAttribute('aria-sort'),'ascending');
  assert.equal(table.tBodies[0],sorted);assert(paginationUpdated);assert.equal(button.disabled,false);
  fail=true;await button.events.click();
  assert.equal(header.getAttribute('aria-sort'),'ascending');assert.equal(table.tBodies[0],sorted);assert.equal(navigationCount,0);assert.equal(button.disabled,false);
  console.log('OK: ordenação assíncrona sem navegação, filtros preservados, paginação atualizada e falha sem perda de linhas.');
})().catch(error=>{console.error(error);process.exitCode=1});
