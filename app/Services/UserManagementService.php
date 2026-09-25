<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\UserRepository;
use DomainException;

final class UserManagementService
{
    public function __construct(private readonly UserRepository $users)
    {
    }

    public function list(): array
    {
        return $this->users->list();
    }

    public function get(int $id): array
    {
        return $this->users->findById($id) ?? throw new DomainException('Usuário não encontrado.');
    }

    public function create(array $input): void
    {
        [$nome, $login, $email, $tipo, $ativo, $tema, $senha] = $this->data($input, true);
        $this->assertUnique($login, $email);
        $this->users->create($nome, $login, $email, password_hash($senha, PASSWORD_DEFAULT), $tipo, $tema);
        if (!$ativo) {
            $created = $this->users->findByLogin($login);
            if ($created) $this->users->update((int) $created['id'], $nome, $login, $email, $tipo, 0, $tema, null);
        }
    }

    public function update(int $actor, int $id, array $input): void
    {
        $current = $this->get($id);
        [$nome, $login, $email, $tipo, $ativo, $tema, $senha] = $this->data($input, false);
        $this->assertUnique($login, $email, $id);
        if ($current['tipo'] === 'admin' && ($tipo !== 'admin' || !$ativo) && $this->adminCount() === 1) {
            throw new DomainException('Mantenha pelo menos um administrador ativo.');
        }
        if ($actor === $id && !$ativo) {
            throw new DomainException('Você não pode desativar o próprio acesso.');
        }
        $this->users->update($id, $nome, $login, $email, $tipo, $ativo ? 1 : 0, $tema, $senha === '' ? null : password_hash($senha, PASSWORD_DEFAULT));
    }

    private function data(array $input, bool $creating): array
    {
        $nome = trim((string) ($input['nome'] ?? ''));
        $login = strtolower(trim((string) ($input['login'] ?? '')));
        $email = strtolower(trim((string) ($input['email'] ?? '')));
        $tipo = (string) ($input['tipo'] ?? 'usuario');
        $ativo = ($input['ativo'] ?? '1') === '1';
        $tema = (string) ($input['tema'] ?? 'light');
        $senha = (string) ($input['senha'] ?? '');
        $confirmacao = (string) ($input['confirmacao_senha'] ?? '');
        if ($nome === '' || mb_strlen($nome) > 100) throw new DomainException('Informe um nome de até 100 caracteres.');
        if (!preg_match('/^[a-zA-Z0-9._-]{3,30}$/', $login)) throw new DomainException('O login deve possuir entre 3 e 30 caracteres e conter apenas letras, números, ponto, hífen ou underline.');
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) throw new DomainException('Informe um e-mail válido.');
        if (!in_array($tipo, ['usuario', 'admin'], true)) throw new DomainException('Selecione um perfil válido.');
        if (!in_array($tema, ['light', 'dark'], true)) throw new DomainException('Selecione um tema válido.');
        if (($creating || $senha !== '') && (strlen($senha) < 8 || strlen($senha) > 72)) throw new DomainException('A senha deve possuir entre 8 e 72 caracteres.');
        if (($creating || $senha !== '') && $senha !== $confirmacao) throw new DomainException('As senhas não conferem.');
        return [$nome, $login, $email, $tipo, $ativo, $tema, $senha];
    }

    private function assertUnique(string $login, string $email, ?int $except = null): void
    {
        foreach ([[$this->users->findByLogin($login), 'login'], [$this->users->findByEmail($email), 'e-mail']] as [$user, $label]) {
            if ($user && (int) $user['id'] !== $except) throw new DomainException('Este ' . $label . ' já está em uso.');
        }
    }

    private function adminCount(): int
    {
        return count(array_filter($this->users->list(), fn(array $user) => $user['tipo'] === 'admin' && (int) $user['ativo'] === 1));
    }
}
