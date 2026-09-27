<?php

declare(strict_types=1);

return function (PDO $pdo): void {
    $pdo->exec("UPDATE grupos SET classificacao='variavel' WHERE classificacao='nao_classificada'");
};
