# Design: Busca filtrável de igreja nos filtros de produtos

## Context

Listagem e verificação carregam todas as igrejas no select `comum_id` sem filtro. O acesso público já resolveu o mesmo problema com busca client-side; a solução deve ser reaplicada aqui com o mínimo de desvio.

## Decision

Reaproveitar o padrão já entregue em `public-access/create.blade.php`: input `type=search` com `data-*` + JS inline leve que filtra `option`s por `textContent.toLowerCase().includes(term)`, tratando placeholder (`value=""`, texto "Todas") separadamente e exibindo mensagem acessível (`role=status`, `aria-live=polite`) quando nenhum resultado casa.

Diferenças do caso produtos vs acesso público:
- Valor vazio significa "Todas" (não "Selecione") e continua selecionável; quando há filtro ativo sem resultados o select é desabilitado mas o form ainda pode ser submetido — o desabilitar é apenas visual/informativo porque o submit GET sem `comum_id` equivale a "Todas".
- Duas telas recebem a mesma marcação/JS (`index` e `verification`); manter script inline por view para não criar dependência externa nem tocar layout.

## Alternatives

- Busca server-side com endpoint/autocomplete: descartada — adiciona latência, cache e complexidade desnecessária para lista já carregada no HTML.
- Componente Blade compartilhado: considerado mas adiado — duplicação é pequena e isolada; extrair para partial pode vir em follow-up se o padrão se espalhar para outras telas com seletor de igreja (relatórios, importação).

## Risks

- [Select desabilitado e submit] → Quando desabilitado por "sem resultados", o navegador não envia `comum_id`; comportamento casa com "Todas" — aceitável. Mensagem orienta limpar busca.
- [Acessibilidade] → Label associado ao input, `aria-controls` apontando ao select, região de status com `aria-live`.

## Implementation Notes

- Marcação: `data-product-church-search`, `data-product-church-select`, `data-product-church-status`; id do select continua `comum_id` implícito via `name`.
- JS: guardar `churchOptions` (value !== ""), `placeholder` (value === ""), `applyFilter` idêntico ao de public-access adaptando texto de status para "Nenhuma igreja encontrada...".
