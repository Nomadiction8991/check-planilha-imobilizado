# Tasks: Exportar auditoria em CSV

## 1. Serviço (TDD)
- [x] Criar teste unitário da exportação CSV (linhas filtradas, cabeçalho, BOM, round-trip com separador correto)
- [x] Criar teste unitário de escopo (não administrador só vê o próprio escopo na exportação)
- [x] Adicionar método `exportCsv` ao contrato e implementar no serviço de trilha de auditoria

## 2. Rota e controller (TDD)
- [x] Criar teste de feature do download com filtros aplicados
- [x] Criar teste de feature do redirecionamento amigável quando não há eventos
- [x] Registrar rota GET de exportação protegida pela permissão de auditoria
- [x] Implementar ação `export` no controller de auditoria

## 3. Interface
- [x] Criar teste de feature do botão exportar preservando filtros
- [x] Adicionar botão "Exportar CSV" à view da tela de auditoria

## 4. Qualidade e entrega
- [x] Rodar suíte completa de testes
- [x] Validar change OpenSpec
- [ ] Commit + push + confirmação de saúde da produção
