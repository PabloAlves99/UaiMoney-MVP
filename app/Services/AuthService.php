<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Session;
use App\Repositories\UserRepository;
use DomainException;

final class AuthService
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly Session $session
    ) {
    }


    public function register(
        string $nome,
        string $login,
        string $email,
        string $senha,
        string $confirmacaoSenha
    ): int {
        $nome = trim($nome);

        $login = strtolower(
            trim($login)
        );

        $email = strtolower(
            trim($email)
        );


        /*
         * Nome
         */

        if ($nome === '') {
            throw new DomainException(
                'O nome é obrigatório.'
            );
        }


        /*
         * Login
         */

        if (
            !preg_match(
                '/^[a-zA-Z0-9._-]{3,30}$/',
                $login
            )
        ) {
            throw new DomainException(
                'O login deve possuir entre 3 e 30 caracteres e conter apenas letras, números, ponto, hífen ou underline.'
            );
        }


        /*
         * E-mail
         */

        if (
            !filter_var(
                $email,
                FILTER_VALIDATE_EMAIL
            )
        ) {
            throw new DomainException(
                'Informe um e-mail válido.'
            );
        }


        /*
         * Senha
         */

        if (strlen($senha) < 8) {
            throw new DomainException(
                'A senha deve possuir pelo menos 8 caracteres.'
            );
        }

        if ($senha !== $confirmacaoSenha) {
            throw new DomainException(
                'As senhas não conferem.'
            );
        }


        /*
         * Duplicidades
         */

        if (
            $this->userRepository
                ->findByLogin($login) !== null
        ) {
            throw new DomainException(
                'Este login já está em uso.'
            );
        }

        if (
            $this->userRepository
                ->findByEmail($email) !== null
        ) {
            throw new DomainException(
                'Este e-mail já está em uso.'
            );
        }


        /*
         * Hash
         */

        $senhaHash = password_hash(
            $senha,
            PASSWORD_DEFAULT
        );


        /*
         * Persistência
         */

        return $this->userRepository->create(
            $nome,
            $login,
            $email,
            $senhaHash
        );
    }


    public function login(
        string $identifier,
        string $senha
    ): array {
        $identifier = strtolower(
            trim($identifier)
        );

        if (
            $identifier === '' ||
            $senha === ''
        ) {
            throw new DomainException(
                'Informe login/e-mail e senha.'
            );
        }


        /*
         * Buscar usuário
         */

        $usuario = $this->userRepository->findByIdentifier($identifier);


        /*
         * Credenciais inválidas
         */

        if (
            $usuario === null ||
            !password_verify(
                $senha,
                $usuario['senha_hash']
            )
        ) {
            throw new DomainException(
                'Credenciais inválidas.'
            );
        }


        /*
         * Usuário inativo
         */

        if ((int) $usuario['ativo'] !== 1) {
            throw new DomainException(
                'Este usuário está inativo.'
            );
        }


        /*
         * Trocar ID da sessão após autenticação
         */

        $this->session->regenerate();


        /*
         * Guardamos somente o ID.
         */

        $this->session->set(
            'usuario_id',
            (int) $usuario['id']
        );


        /*
         * Nunca retornamos o hash da senha.
         */

        unset(
            $usuario['senha_hash']
        );

        return $usuario;
    }


    public function logout(): void
    {
        $this->session->destroy();
    }


    public function currentUser(): ?array
    {
        $usuarioId = $this->session->get(
            'usuario_id'
        );

        if (!is_int($usuarioId)) {
            return null;
        }

        $usuario = $this->userRepository->findById($usuarioId);

        if (
            $usuario === null ||
            (int) $usuario['ativo'] !== 1
        ) {
            return null;
        }

        unset(
            $usuario['senha_hash']
        );

        return $usuario;
    }


    public function isAuthenticated(): bool
    {
        return $this->currentUser() !== null;
    }
}