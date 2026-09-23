<?php use App\Core\Html as H; ?>
<p class="eyebrow"><?= (int)$code ?></p><h1 class="h3"><?= H::escape($title) ?></h1><p class="text-secondary">Confira o endereço ou volte para continuar organizando suas finanças.</p><a class="btn btn-uai-primary" href="<?= H::escape($basePath.'/') ?>">Voltar ao início</a>
