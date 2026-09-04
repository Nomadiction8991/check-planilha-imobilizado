## Context

A sessão pública já é permitida pelo middleware de sessão e hoje pode ser encerrada por uma rota POST que limpa suas chaves, mas essa rota não é oferecida no layout. O layout compartilhado tem dois pontos de navegação relevantes: o cabeçalho para telas largas e o drawer/menu para telas pequenas. A tela de seleção pública já é a entrada natural para um novo atendimento.

## Goals / Non-Goals

**Goals:**

- Tornar a saída pública encontrável nos dois modos de navegação.
- Reutilizar a rota POST existente e a proteção CSRF do grupo web.
- Isolar a limpeza do contexto público da sessão administrativa.
- Retornar à seleção pública após a saída, sem expor o login administrativo.

**Non-Goals:**

- Alterar o modelo de autenticação administrativa.
- Alterar produtos, igrejas ou qualquer dado persistido.
- Criar uma nova sessão, dependência ou fluxo de confirmação.

## Decisions

1. **Usar a rota pública existente com novo destino.** A rota já é POST e o controlador já concentra as chaves públicas. Alterar seu redirecionamento para `public.access.create` evita duplicação e garante que uma saída repetida seja idempotente. Uma rota adicional seria desnecessária.

2. **Renderizar a ação no cabeçalho e no menu mobile.** O cabeçalho permanece compacto com um botão rotulado e o menu mobile recebe um botão com o mesmo texto. Condicionar ambos a `session('public_acesso')` evita misturar ações administrativas no atendimento público. A alternativa de deixar somente o menu mobile reduziria a descoberta em telas largas.

3. **Manter formulário HTML POST com CSRF.** O formulário funciona sem JavaScript, preserva acessibilidade e segue a proteção já aplicada ao grupo web. Um link GET seria inadequado porque encerraria uma sessão por navegação e permitiria efeitos colaterais via prefetch.

4. **Usar rótulo textual explícito e alvo de toque adequado.** O texto "Sair do acesso público" comunica o contexto e será renderizado como botão com área de toque existente no componente de navegação. A ação não usará apenas ícone, tooltip ou emoji.

## Risks / Trade-offs

- [Risco] O layout compartilhado pode não receber o contexto esperado em testes de renderização. → [Mitigação] Cobrir a presença e ausência das ações em testes de feature, usando a sessão pública real do request.
- [Risco] A sessão pública conter chaves legadas adicionais no futuro. → [Mitigação] Manter a lista de chaves públicas centralizada no controlador e cobrir todas as chaves atualmente usadas.
- [Risco] O texto ocupar mais espaço em telas estreitas. → [Mitigação] Usar o botão textual no menu mobile e um rótulo acessível/visual compacto no cabeçalho, preservando o alvo de toque.

## Migration Plan

A alteração é compatível com sessões existentes e não exige migração de banco. Após o deploy, validar a tela pública, acionar a saída e confirmar o retorno à seleção; em caso de regressão, reverter o commit sem alterar dados persistidos.
