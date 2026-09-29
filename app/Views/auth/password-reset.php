<?php use App\Core\Html as H; ?>
<h1 class="h4 mb-3"><?= $step === 'done' ? 'Senha redefinida' : ($step === 'sent' ? 'Confira seu e-mail' : 'Redefinir senha') ?></h1>
<?php if ($error !== null): ?><div class="alert alert-danger" role="alert"><?= H::escape($error) ?></div><?php endif; ?>
<?php if ($message !== null): ?><div class="alert alert-info" role="status"><?= H::escape($message) ?></div><?php endif; ?>
<?php if ($step === 'sent'): ?>
<p class="text-secondary small">Abra a mensagem do UaiMoney e clique em <strong>Redefinir minha senha</strong>. Na página aberta, informe o código recebido e escolha sua nova senha.</p>
<p class="text-secondary small">O código expira em 10 minutos a partir da solicitação. Se não encontrar a mensagem, confira a pasta de spam.</p>
<p class="small mb-0"><a href="<?= H::escape($basePath . '/esqueci-senha') ?>">Solicitar outro e-mail</a></p>
<?php endif; ?>
<?php if (in_array($step, ['request', 'reset'], true)): ?>
<p class="text-secondary small"><?= $step === 'request' ? 'Informe seu e-mail cadastrado para receber o código de recuperação.' : 'Digite o código recebido por e-mail e escolha sua nova senha. O código vale por 10 minutos e pode ser usado uma única vez.' ?></p>
<form method="post" action="<?= H::escape($basePath . ($step === 'request' ? '/esqueci-senha' : '/redefinir-senha')) ?>">
    <input type="hidden" name="_token" value="<?= H::escape($csrfToken) ?>">
    <div class="mb-3">
        <label class="form-label" for="email">E-mail cadastrado</label>
        <input class="form-control" type="email" id="email" name="email" autocomplete="email" maxlength="254" value="<?= H::escape($email) ?>" required>
    </div>
    <?php if ($step === 'reset'): ?>
    <div class="mb-3">
        <label class="form-label" for="code">Código de 6 dígitos</label>
        <input class="form-control" id="code" name="code" inputmode="numeric" autocomplete="one-time-code" pattern="[0-9]{6}" minlength="6" maxlength="6" required aria-describedby="code-help">
        <div class="form-text" id="code-help">Use o código do e-mail mais recente.</div>
    </div>
    <div class="mb-3">
        <label class="form-label" for="senha">Nova senha</label>
        <input class="form-control" type="password" id="senha" name="senha" autocomplete="new-password" minlength="8" maxlength="72" required aria-describedby="password-help">
        <div class="form-text" id="password-help">Use pelo menos 8 caracteres. Prefira uma senha longa e exclusiva.</div>
    </div>
    <div class="mb-4">
        <label class="form-label" for="confirmacao">Confirme a nova senha</label>
        <input class="form-control" type="password" id="confirmacao" name="confirmacao" autocomplete="new-password" minlength="8" maxlength="72" required>
    </div>
    <?php endif; ?>
    <button class="btn btn-uai-primary w-100" type="submit"><?= $step === 'request' ? 'Enviar código' : 'Salvar nova senha' ?></button>
</form>
<?php if ($step === 'reset'): ?><p class="text-center small mt-3"><a href="<?= H::escape($basePath . '/esqueci-senha') ?>">Solicitar novo código</a></p><?php endif; ?>
<?php endif; ?>
<p class="text-center mt-4 mb-0"><a href="<?= H::escape($basePath . '/login') ?>">Voltar para entrar</a></p>
