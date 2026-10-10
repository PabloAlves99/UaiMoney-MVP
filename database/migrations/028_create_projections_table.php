<?php

declare(strict_types=1);

return function (PDO $pdo): void {
    $pdo->exec("CREATE TABLE projecoes (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        usuario_id INTEGER NOT NULL REFERENCES usuarios(id) ON UPDATE CASCADE ON DELETE CASCADE,
        nome TEXT NOT NULL CHECK (LENGTH(TRIM(nome)) BETWEEN 1 AND 100),
        origem_tipo TEXT NOT NULL CHECK (origem_tipo IN ('investimento', 'objetivo')),
        investimento_id INTEGER REFERENCES investimentos(id) ON UPDATE CASCADE ON DELETE CASCADE,
        objetivo_id INTEGER REFERENCES metas_financeiras(id) ON UPDATE CASCADE ON DELETE CASCADE,
        aporte_mensal_centavos INTEGER NOT NULL CHECK (aporte_mensal_centavos >= 0),
        taxa_quatro_casas INTEGER NOT NULL CHECK (taxa_quatro_casas BETWEEN 0 AND 1000000),
        periodicidade_taxa TEXT NOT NULL CHECK (periodicidade_taxa IN ('mensal', 'anual')),
        valor_meta_centavos INTEGER CHECK (valor_meta_centavos IS NULL OR valor_meta_centavos > 0),
        criado_em TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
        atualizado_em TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
        CHECK (
            (origem_tipo = 'investimento' AND investimento_id IS NOT NULL AND objetivo_id IS NULL AND valor_meta_centavos IS NOT NULL)
            OR
            (origem_tipo = 'objetivo' AND objetivo_id IS NOT NULL AND investimento_id IS NULL AND valor_meta_centavos IS NULL)
        )
    )");
    $pdo->exec('CREATE INDEX idx_projecoes_usuario ON projecoes(usuario_id, atualizado_em DESC)');
    $pdo->exec('CREATE INDEX idx_projecoes_investimento ON projecoes(investimento_id)');
    $pdo->exec('CREATE INDEX idx_projecoes_objetivo ON projecoes(objetivo_id)');
};
