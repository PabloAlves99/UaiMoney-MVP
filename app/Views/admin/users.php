<?php

use App\Core\Html as H;

$values = array_replace($editing ?? [], $old);
$value = fn(string $key, mixed $default = '') => $values[$key] ?? $default;
?>
<div class="page-heading">
    <div><p class="eyebrow">ADMINISTRAÇÃO</p><h1>Usuários</h1><p class="text-secondary">Cadastre e mantenha os acessos do UaiMoney.</p></div>
    <?php if ($editing): ?><a class="btn btn-outline-secondary" href="<?= H::escape($basePath . '/admin/usuarios') ?>">Cancelar edição</a><?php endif; ?>
</div>
<div class="row g-4">
    <div class="col-xl-7"><section class="card"><div class="card-body">
        <div class="section-heading"><h2 class="h4">Acessos cadastrados</h2><span class="text-secondary small"><?= count($users) ?> usuários</span></div>
        <?php foreach ($users as $user): ?>
            <article class="movement-row"><div class="movement-main"><strong><?= H::escape($user['nome']) ?></strong><small>@<?= H::escape($user['login']) ?> · <?= H::escape($user['email']) ?></small><small>Criado em <?= H::date(substr($user['criado_em'], 0, 10)) ?></small></div><div class="movement-value"><span class="status-pill"><?= $user['tipo'] === 'admin' ? 'Administrador' : 'Usuário' ?></span><small><?= (int) $user['ativo'] ? 'Ativo' : 'Inativo' ?></small></div><div class="movement-actions"><a class="btn btn-sm btn-outline-secondary" href="<?= H::escape($basePath . '/admin/usuarios/' . $user['id'] . '/editar') ?>">Editar</a></div></article>
        <?php endforeach; ?>
    </div></section></div>
    <div class="col-xl-5"><section class="card"><div class="card-body"><h2 class="h4"><?= $editing ? 'Editar usuário' : 'Novo usuário' ?></h2><p class="small text-secondary"><?= $editing ? 'Deixe a senha em branco para mantê-la.' : 'O usuário poderá entrar assim que estiver ativo.' ?></p>
        <form method="post" action="<?= H::escape($basePath . '/admin/usuarios' . ($editing ? '/' . $editing['id'] . '/editar' : '')) ?>" class="vstack gap-3"><?= H::fields($csrfToken) ?>
            <div><label class="form-label" for="user-name">Nome</label><input id="user-name" name="nome" maxlength="100" required class="form-control" value="<?= H::escape($value('nome')) ?>"></div>
            <div><label class="form-label" for="user-login">Login</label><input id="user-login" name="login" maxlength="30" required class="form-control" value="<?= H::escape($value('login')) ?>"></div>
            <div><label class="form-label" for="user-email">E-mail</label><input id="user-email" name="email" type="email" required class="form-control" value="<?= H::escape($value('email')) ?>"></div>
            <div><label class="form-label" for="user-role">Perfil</label><select id="user-role" name="tipo" class="form-select"><option value="usuario" <?= $value('tipo', 'usuario') === 'usuario' ? 'selected' : '' ?>>Usuário</option><option value="admin" <?= $value('tipo') === 'admin' ? 'selected' : '' ?>>Administrador</option></select></div>
            <div><label class="form-label" for="user-theme">Tema</label><select id="user-theme" name="tema" class="form-select"><option value="light" <?= $value('tema', 'light') === 'light' ? 'selected' : '' ?>>Claro</option><option value="dark" <?= $value('tema') === 'dark' ? 'selected' : '' ?>>Escuro</option></select></div>
            <div><label class="form-label" for="user-password"><?= $editing ? 'Nova senha' : 'Senha' ?></label><input id="user-password" name="senha" type="password" autocomplete="new-password" <?= $editing ? '' : 'required' ?> class="form-control"><small class="form-text">Entre 8 e 72 caracteres.</small></div>
            <div><label class="form-label" for="user-confirmation">Confirmar senha</label><input id="user-confirmation" name="confirmacao_senha" type="password" autocomplete="new-password" <?= $editing ? '' : 'required' ?> class="form-control"></div>
            <div><label class="form-label" for="user-active">Situação</label><select id="user-active" name="ativo" class="form-select"><option value="1" <?= (string) $value('ativo', '1') === '1' ? 'selected' : '' ?>>Ativo</option><option value="0" <?= (string) $value('ativo') === '0' ? 'selected' : '' ?>>Inativo</option></select></div>
            <button class="btn btn-uai-primary"><?= $editing ? 'Salvar alterações' : 'Cadastrar usuário' ?></button>
        </form>
    </div></section></div>
</div>
