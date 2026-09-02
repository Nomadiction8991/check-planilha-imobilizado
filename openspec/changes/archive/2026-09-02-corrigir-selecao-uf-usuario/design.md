## Context

Consulte a motivação em `proposal.md` e o comportamento esperado em `specs/users/spec.md`. O controller já fornece a configuração associativa de estados para as telas de usuário; a view de endereço é compartilhada por criação e edição.

## Goals / Non-Goals

**Goals:**

- Iterar pelas chaves e rótulos da configuração de estados sem perder a associação entre código e nome.
- Manter o valor selecionado em edição e após falha de validação.
- Garantir a regressão por teste funcional que inspecione a resposta renderizada.

**Non-Goals:**

- Alterar a API de localidades, as regras de validação, o banco de dados ou o contrato do controller.
- Redesenhar o formulário ou modificar a lista global de estados.

## Decisions

A view usará `@foreach ($states as $stateCode => $stateLabel)`, enviará o código no atributo `value` e mostrará o nome junto da sigla. A variável já calculada com `old()` continuará sendo a fonte de seleção, pois cobre tanto o cadastro existente quanto o retorno com entrada anterior.

Essa abordagem foi escolhida em vez de converter a configuração no controller ou criar uma segunda lista na view: preserva a fonte única de localidades e mantém a mudança localizada no ponto que interpreta os dados. O teste funcional abrirá a edição com uma UF conhecida e verificará que a opção correspondente contém `value`, `selected` e o rótulo correto.

## Risks / Trade-offs

- [Risk] Uma futura fonte de estados posicional pode não fornecer chave e rótulo como esperado → [Mitigation] manter o contrato associativo documentado pela configuração existente e cobrir a resposta renderizada.
- [Risk] O teste pode validar apenas texto e não a opção selecionada → [Mitigation] verificar explicitamente os atributos HTML da opção.

## Migration Plan

Nenhuma migração de dados é necessária. Após a implementação, executar o teste funcional direcionado, o lint dos arquivos PHP alterados, a validação do OpenSpec e a suíte completa antes do envio. O rollback consiste em reverter o commit da view e do teste, sem impacto em dados persistidos.
