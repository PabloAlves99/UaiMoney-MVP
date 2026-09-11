```text
UaiMoney-MVP/
│
├── app/
│   ├── Controllers/
│   ├── Core/
│   ├── Models/
│   ├── Repositories/
│   ├── Services/
│   └── Views/
│
├── config/
│
├── database/
│   ├── migrations/
│   └── seeds/
│
├── public/
│   ├── css/
│   ├── js/
│   ├── media/
│   └── index.php
│
├── routes/
│
├── storage/
│   ├── database/
│   └── logs/
│
└── README.md
```

A ideia de cada pasta é:

* `app/Core`: conexão, sessão, roteamento e classes-base.
* `Controllers`: recebe a requisição e decide o que fazer.
* `Services`: regras de negócio.
* `Repositories`: acesso ao banco.
* `Models`: representação das entidades.
* `Views`: telas PHP.
* `config`: configurações do sistema.
* `database/migrations`: criação e evolução das tabelas.
* `database/seeds`: dados iniciais.
* `public`: única pasta que futuramente deve ficar exposta pelo Apache.
* `routes`: definição das rotas.
* `storage/database`: banco SQLite.
* `storage/logs`: logs da aplicação.


```powershell
# Criar a estrutura do projeto UaiMoney-MVP
$projectRoot = "UaiMoney-MVP"
New-Item -ItemType Directory -Path $projectRoot -Force | Out-Null

# Criar todos os diretórios
$directories = @(
    "$projectRoot/app/Controllers",
    "$projectRoot/app/Core",
    "$projectRoot/app/Models",
    "$projectRoot/app/Repositories",
    "$projectRoot/app/Services",
    "$projectRoot/app/Views",
    "$projectRoot/config",
    "$projectRoot/database/migrations",
    "$projectRoot/database/seeds",
    "$projectRoot/public/css",
    "$projectRoot/public/js",
    "$projectRoot/public/media",
    "$projectRoot/routes",
    "$projectRoot/storage/database",
    "$projectRoot/storage/logs"
)

foreach ($dir in $directories) {
    New-Item -ItemType Directory -Path $dir -Force | Out-Null
}

# Criar arquivos vazios
New-Item -ItemType File -Path "$projectRoot/public/index.php" -Force | Out-Null
New-Item -ItemType File -Path "$projectRoot/README.md" -Force | Out-Null

Write-Host "✓ Estrutura do projeto 'UaiMoney-MVP' criada com sucesso!" -ForegroundColor Green
```