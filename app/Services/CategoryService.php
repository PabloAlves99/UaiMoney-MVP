<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\CategoryRepository;
use DomainException;

final class CategoryService
{
    public function __construct(
        private readonly CategoryRepository $categoryRepository
    ) {
    }


    public function list(
        int $usuarioId
    ): array {
        $grupos = $this
            ->categoryRepository
            ->listGroups($usuarioId);

        $subgrupos = $this
            ->categoryRepository
            ->listSubgroups($usuarioId);


        /*
         * Preparamos cada grupo para receber
         * seus subgrupos.
         */

        foreach ($grupos as &$grupo) {
            $grupo['subgrupos'] = [];
        }

        unset($grupo);


        /*
         * Montamos um índice:
         *
         * grupo ID -> posição no array.
         */

        $grupoIndex = [];

        foreach ($grupos as $index => $grupo) {
            $grupoIndex[(int) $grupo['id']]
                = $index;
        }


        /*
         * Colocamos cada subgrupo dentro
         * do seu respectivo grupo.
         */

        foreach ($subgrupos as $subgrupo) {

            $grupoId = (int)
                $subgrupo['grupo_id'];

            if (!isset($grupoIndex[$grupoId])) {
                continue;
            }

            $index =
                $grupoIndex[$grupoId];

            $grupos[$index]['subgrupos'][]
                = $subgrupo;
        }


        return $grupos;
    }


    public function createGroup(
        int $usuarioId,
        string $nome,
        string $tipo
    ): int {
        $nome = trim($nome);
        $tipo = strtolower(
            trim($tipo)
        );


        if ($nome === '') {
            throw new DomainException(
                'Informe o nome da categoria.'
            );
        }


        if (strlen($nome) > 100) {
            throw new DomainException(
                'O nome da categoria é muito longo.'
            );
        }


        if (
            !in_array(
                $tipo,
                ['receita', 'despesa'],
                true
            )
        ) {
            throw new DomainException(
                'Tipo de categoria inválido.'
            );
        }


        if (
            $this->categoryRepository
                ->groupExists(
                    $usuarioId,
                    $nome,
                    $tipo
                )
        ) {
            throw new DomainException(
                'Esta categoria já existe.'
            );
        }


        return $this
            ->categoryRepository
            ->createGroup(
                $usuarioId,
                $nome,
                $tipo
            );
    }


    public function createSubgroup(
        int $usuarioId,
        int $grupoId,
        string $nome,
        ?string $descricao
    ): int {
        $nome = trim($nome);

        $descricao = $descricao !== null
            ? trim($descricao)
            : null;


        if ($nome === '') {
            throw new DomainException(
                'Informe o nome da subcategoria.'
            );
        }


        if (strlen($nome) > 100) {
            throw new DomainException(
                'O nome da subcategoria é muito longo.'
            );
        }


        if ($descricao === '') {
            $descricao = null;
        }


        $grupo = $this
            ->categoryRepository
            ->findGroupById(
                $grupoId,
                $usuarioId
            );


        if (
            $grupo === null ||
            (int) $grupo['ativo'] !== 1
        ) {
            throw new DomainException(
                'Categoria inválida.'
            );
        }


        if (
            $this->categoryRepository
                ->subgroupExists(
                    $grupoId,
                    $nome
                )
        ) {
            throw new DomainException(
                'Esta subcategoria já existe.'
            );
        }


        return $this
            ->categoryRepository
            ->createSubgroup(
                $grupoId,
                $nome,
                $descricao
            );
    }


    public function deactivateGroup(
        int $usuarioId,
        int $grupoId
    ): void {
        $grupo = $this
            ->categoryRepository
            ->findGroupById(
                $grupoId,
                $usuarioId
            );


        if ($grupo === null) {
            throw new DomainException(
                'Categoria não encontrada.'
            );
        }


        $this->categoryRepository
            ->deactivateGroup(
                $grupoId,
                $usuarioId
            );
    }


    public function deactivateSubgroup(
        int $usuarioId,
        int $subgrupoId
    ): void {
        $this->categoryRepository
            ->deactivateSubgroup(
                $subgrupoId,
                $usuarioId
            );
    }
}