<?php use App\Core\Html as H; ?>
<a class="back-link" href="<?= H::escape($basePath . '/contas/' . (int) $account['id']) ?>">← Voltar para a conta</a>
<div class="page-heading"><div><p class="eyebrow">COMPARTILHAMENTO</p><h1><?= H::escape($account['nome']) ?></h1><p class="text-secondary">Crie um acesso somente de leitura ao extrato desta conta.</p></div></div>
<?php if ($flash): ?><div class="alert alert-<?= H::escape($flash['type']) ?>"><?= H::escape($flash['message']) ?></div><?php endif; ?>
<?php if ($newToken):
    $shareUrl = $basePath . '/compartilhado/' . $newToken;
?>
<section class="card mb-4 border-success"><div class="card-body">
    <h2 class="h5">Copie o novo link agora</h2>
    <p class="small text-secondary">Por segurança, o token completo não será exibido novamente.</p>
    <div class="input-group"><input id="share-url" class="form-control font-monospace" readonly value="<?= H::escape($shareUrl) ?>"><button class="btn btn-outline-secondary" type="button" data-copy-target="share-url">Copiar</button></div>
</div></section>
<?php endif; ?>
<div class="row g-4">
    <div class="col-lg-7"><section class="card h-100"><div class="card-body">
        <h2 class="h5">Gerar link de acesso</h2>
        <p class="text-secondary">O link dá acesso apenas às movimentações desta conta. Gerar outro link revoga imediatamente o anterior.</p>
        <form method="post" action="<?= H::escape($basePath . '/contas/' . (int) $account['id'] . '/compartilhar') ?>" class="vstack gap-3" data-confirm="Gerar um novo link? O link anterior deixará de funcionar.">
            <?= H::fields($csrfToken) ?>
            <div><label class="form-label" for="share-password">Senha adicional (opcional)</label><input class="form-control" id="share-password" name="senha" type="password" minlength="6" maxlength="200" autocomplete="new-password"><div class="form-text">Sem senha, quem tiver o link poderá ver. Com senha, serão necessários o link e a senha.</div></div>
            <div><button class="btn btn-uai-primary"><?= $share && (int) $share['ativo'] ? 'Gerar novo link' : 'Ativar compartilhamento' ?></button></div>
        </form>
    </div></section></div>
    <div class="col-lg-5"><section class="card h-100"><div class="card-body">
        <h2 class="h5">Estado atual</h2>
        <?php if ($share && (int) $share['ativo']): ?>
            <p><span class="badge text-bg-success">Ativo</span></p>
            <dl><dt>Final do token</dt><dd class="font-monospace">…<?= H::escape($share['token_hint']) ?></dd><dt>Senha adicional</dt><dd><?= $share['senha_hash'] ? 'Ativada' : 'Não utilizada' ?></dd><dt>Último acesso</dt><dd><?= $share['ultimo_acesso_em'] ? H::escape(date('d/m/Y H:i', strtotime($share['ultimo_acesso_em']))) : 'Ainda não acessado' ?></dd></dl>
            <form method="post" action="<?= H::escape($basePath . '/contas/' . (int) $account['id'] . '/compartilhar/desativar') ?>" data-confirm="Desativar este compartilhamento? O link deixará de funcionar."><?= H::fields($csrfToken) ?><button class="btn btn-outline-danger">Desativar acesso</button></form>
        <?php else: ?><p class="text-secondary">Esta conta não possui um link público ativo.</p><?php endif; ?>
    </div></section></div>
</div>
<section class="card mt-4"><div class="card-body"><h2 class="h5">O que será exibido</h2><p class="mb-0 text-secondary">Nome da conta, datas, descrições, entradas, saídas e gráfico mensal. Categorias, observações, dados pessoais e ações de edição não são compartilhados.</p></div></section>
<script>document.querySelector('[data-copy-target]')?.addEventListener('click',async e=>{const input=document.getElementById(e.currentTarget.dataset.copyTarget);const absolute=new URL(input.value,location.origin).href;await navigator.clipboard.writeText(absolute);input.value=absolute;e.currentTarget.textContent='Copiado';});</script>
