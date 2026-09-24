<?php use App\Core\Html as H; ?>
<p class="eyebrow">EXTRATO COMPARTILHADO</p>
<h1 class="h3 mb-2"><?= H::escape($share['conta_nome']) ?></h1>
<p class="text-secondary">Digite a senha informada pelo proprietário para consultar as movimentações.</p>
<?php if ($error): ?><div class="alert alert-danger"><?= H::escape($error) ?></div><?php endif; ?>
<form method="post" class="vstack gap-3">
    <input type="hidden" name="_token" value="<?= H::escape($csrfToken) ?>">
    <div><label class="form-label" for="password">Senha de acesso</label><input class="form-control" id="password" name="senha" type="password" required autofocus autocomplete="current-password"></div>
    <button class="btn btn-uai-primary">Acessar extrato</button>
</form>
