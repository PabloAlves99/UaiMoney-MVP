<?php
use App\Core\Html as H;
$groups = [];
foreach ($categories as $category) $groups[$category['grupo_id']] = H::flowLabel($category['grupo_nome'], $category['tipo']);
?>
<div class="col-sm-6 col-lg-3"><label class="form-label" for="filter-grupo_id">Categoria</label><select class="form-select" id="filter-grupo_id" name="grupo_id" data-category-filter><option value="">Todas</option><?php foreach ($groups as $id => $name): ?><option value="<?= (int) $id ?>" <?= (string) ($filters['grupo_id'] ?? '') === (string) $id ? 'selected' : '' ?>><?= H::escape($name) ?></option><?php endforeach; ?></select></div>
<div class="col-sm-6 col-lg-3"><label class="form-label" for="filter-subgrupo_id">Subcategoria</label><select class="form-select" id="filter-subgrupo_id" name="subgrupo_id" data-subcategory-filter><option value="">Todas</option><?php foreach ($categories as $category): ?><option value="<?= (int) $category['id'] ?>" data-group="<?= (int) $category['grupo_id'] ?>" <?= (string) ($filters['subgrupo_id'] ?? '') === (string) $category['id'] ? 'selected' : '' ?>><?= H::escape(H::flowLabel($category['grupo_nome'] . ' · ' . $category['nome'], $category['tipo'])) ?></option><?php endforeach; ?></select></div>
