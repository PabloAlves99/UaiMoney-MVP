<?php
declare(strict_types=1);
require __DIR__.'/run.php';

$before = $count;
$service = new \App\Services\CategoryService($categories);
$group = $service->createGroup(1,'Gestão de categorias','despesa','essencial');
$sub = $service->createSubgroup(1,$group,'Subcategoria ativa',null);
$disabledSub = $service->createSubgroup(1,$group,'Subcategoria desativada',null);
$service->deactivateSubgroup(1,$disabledSub);
$management = $service->management(1);
$managed = array_column($management['groups'],null,'id')[$group];
check((int)$managed['subcategorias_ativas']===1 && (int)$managed['subcategorias_total']===2,'Active and total subcategory counts');
check(!array_filter($management['groups'],fn($g)=>(int)$g['usuario_id']!==1),'Management list enforces owner');
$service->editGroup(1,$group,['nome'=>'Nome editado','tipo'=>'despesa']);
check($service->group(1,$group)['classificacao']==='variavel','Unchecking essential updates classification');
$service->updateClassification(1,$group,'essencial');
check($service->group(1,$group)['classificacao']==='essencial','Essential checkbox saves');
$service->deactivateGroup(1,$group);
check(!(int)$service->group(1,$group)['ativo'],'Category deactivated');
check(!array_filter($categories->listSubgroups(1),fn($s)=>(int)$s['grupo_id']===$group),'Parent deactivation removes children from selectable lists');
check((int)$service->subgroup(1,$sub)['ativo']===1,'Parent deactivation preserves individual child state');
$service->editGroup(1,$group,['nome'=>'Nome inativo editado','tipo'=>'despesa','essencial'=>'1']);
$service->editSubgroup(1,$disabledSub,['nome'=>'Subcategoria inativa editada','descricao'=>'Descrição atualizada']);
check($service->group(1,$group)['nome']==='Nome inativo editado','Inactive category can be edited');
check($service->subgroup(1,$disabledSub)['descricao']==='Descrição atualizada','Inactive subcategory can be edited');
rejects(fn()=>$service->reactivateSubgroup(1,$disabledSub),'Subcategory cannot reactivate while parent is inactive');
$service->reactivateGroup(1,$group);
check((int)$service->subgroup(1,$disabledSub)['ativo']===0,'Restoring parent preserves individually disabled child');
check(count(array_filter($categories->listSubgroups(1),fn($s)=>(int)$s['grupo_id']===$group))===1,'Restoring parent makes its previously active children available');
$service->reactivateSubgroup(1,$disabledSub);
check(count(array_filter($categories->listSubgroups(1),fn($s)=>(int)$s['grupo_id']===$group))===2,'Individual subcategory reactivation');
foreach ([fn()=>$service->editGroup(2,$group,['nome'=>'Ataque']),fn()=>$service->editSubgroup(2,$sub,['nome'=>'Ataque']),fn()=>$service->reactivateGroup(2,$group),fn()=>$service->reactivateSubgroup(2,$sub)] as $action) rejects($action,'Owner isolation for edit and restore');
$duplicate = $service->createGroup(1,'Duplicate name','despesa');
rejects(fn()=>$service->editGroup(1,$group,['nome'=>'DUPLICATE NAME','tipo'=>'despesa']),'Category duplicates follow database collation');
$service->createSubgroup(1,$group,'Duplicate child',null);
rejects(fn()=>$service->editSubgroup(1,$sub,['nome'=>'DUPLICATE CHILD']),'Subcategory duplicates follow database collation');
$tx->create(1,$sub,$a,'Histórico preservado','12','2025-01-01','2025-01-01','2025-01-01','efetivada','pix',null);
rejects(fn()=>$service->editGroup(1,$group,['nome'=>'Nome inativo editado','tipo'=>'receita']),'Used category cannot change financial type');
check($service->group(1,$group)['tipo']==='despesa','Financial type is preserved');
$unused = $service->createGroup(1,'Sem histórico','despesa');
$service->editGroup(1,$unused,['nome'=>'Sem histórico','tipo'=>'receita','essencial'=>'1']);
check($service->group(1,$unused)['tipo']==='receita' && $service->group(1,$unused)['classificacao']==='variavel','Unused category may change type; income never becomes essential');
// Render both lists, including escaping and all action endpoints.
$service->deactivateGroup(1,$group);
$data = $service->management(1);
$allGroups=$data['groups'];$basePath='';$csrfToken='test';$error=null;$old=[];$selectedGroup='';
foreach ([false,true] as $inactive) {
 $grupos=array_values(array_filter($allGroups,fn($g)=>(bool)$g['ativo']!==$inactive));
 $subgrupos=array_values(array_filter($data['subgroups'],fn($s)=>((bool)$s['ativo']&&(bool)$s['grupo_ativo'])!==$inactive));
 ob_start();require dirname(__DIR__).'/app/Views/categories/index.php';$html=ob_get_clean();
 check(str_contains($html,'category-table') && str_contains($html,'Essencial'),'Category table renders');
 check(str_contains($html,$inactive ? '/reativar' : '/desativar'),'Correct lifecycle actions in each list');
 check(str_contains($html,'/editar'),'Editing available in both lists');
 libxml_use_internal_errors(true);
 $document = new DOMDocument(); $document->loadHTML('<?xml encoding="utf-8" ?>'.$html);
 $xpath = new DOMXPath($document);
 check($xpath->query('//table')->length===1,'One table contains categories and subcategories');
 check($xpath->query('//button[@data-category-expand]')->length===count($grupos),'Every category has an accessible expansion control');
 check($xpath->query('//span[contains(@class,"text-bg-secondary")]')->length===0,'Expense type never uses neutral badges');
 if (!$inactive) {
  check($xpath->query('//button[@data-parent-id]')->length===count($grupos),'Subcategory creation is attached to each active category');
  check($xpath->query('//input[@name="grupo_id" and @type="hidden"]')->length===1,'Subcategory modal receives the category from its row');
 }

}
echo 'OK: '.($count-$before)." verificações de edição, reativação, histórico, permissões e tabelas.\n";
