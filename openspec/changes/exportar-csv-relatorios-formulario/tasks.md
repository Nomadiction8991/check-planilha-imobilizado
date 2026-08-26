# Tasks: Exportar CSV dos Relatórios de Formulário

- [x] Teste unitário do CSV 14.1 (colunas, condição legível, nota só nas condições 1/3, nome de arquivo) — RED
- [x] Teste unitário do CSV 14.6 (colunas antes/depois, filtro de edições relevantes, nome de arquivo) — RED
- [x] Testes de erro: formulário inválido e igreja inexistente — RED
- [x] Implementar `downloadFormularioCsv` no serviço + contrato — GREEN
- [x] Testes de feature da rota (download OK, redirect sem igreja, redirect sem itens, botão na prévia) — RED
- [x] Implementar rota + método do controller + botão na view — GREEN
- [x] php -l nos arquivos alterados; suíte completa verde (538 testes)
- [ ] Commit + push (deploy automático)

## Bônus descoberto pelo TDD
- [x] Corrigido espaço duplo nos títulos com dependência em
      buildCurrentProductTitle e buildChangeHistoryTitle (bug real de
      produção detectado pelos testes do CSV).
