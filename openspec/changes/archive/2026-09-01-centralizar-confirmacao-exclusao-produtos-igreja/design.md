## Context

O layout `layouts/migration.blade.php` centralizou a captura de submits com `data-confirm` declarativo para diálogos de confirmação de exclusão em todas as telas de cadastros (usuários, administrações, dependências e tipos de bem).
A tela de listagem de igrejas (`resources/views/churches/index.blade.php`) ainda possuía um bloco `<script>` ad-hoc que adicionava um listener em `.delete-products-form` com `window.confirm`, sem utilizar o padrão declarativo `data-confirm` do layout.

## Goals / Non-Goals

**Goals:**
- Adicionar `data-confirm` declarativo no formulário de exclusão de produtos por igreja.
- Remover o script ad-hoc inline de confirmação na view de igrejas, unificando a UX com o layout padrão.
- Adicionar testes automatizados no `LegacyChurchManagementTest` verificando a presença do atributo declarativo.

**Non-Goals:**
- Alterar o backend do endpoint `migration.churches.delete-products` ou a contagem de produtos.
- Alterar comportamento de outros cadastros já padronizados.

## Decisions

- **Decisão 1: data-confirm no formulário de exclusão de produtos**: Inserir `data-confirm="Excluir todos os produtos desta igreja? Esta ação não pode ser desfeita."` (ou contextualizado com o nome da igreja) diretamente no `<form class="delete-products-form" ...>`.
- **Decisão 2: Remoção do script ad-hoc inline**: Ao remover o script inline e utilizar o ouvinte global de `submit` em `layouts/migration.blade.php`, o comportamento de confirmação é 100% consistente em toda a plataforma.

## Risks / Trade-offs

- [Risco] A confirmação anterior tentava buscar assincronamente a quantidade de produtos antes do prompt.
- [Mitigação] Uma confirmação explícita com o nome da igreja e alerta de ação irreversível é mais rápida, não depende de requisição de rede que possa falhar silenciosamente ou congelar a UI móvel, e se alinha perfeitamente com os demais cadastros.
