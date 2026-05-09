# Runbook do Napkin

## Regras de Curadoria
- Repriorize a cada leitura.
- Mantenha apenas notas recorrentes e de alto valor.
- Máximo de 10 itens por categoria.
- Cada item inclui data + "Faça isto em vez disso".

## Execução e Validação
1. **[2026-03-28] Validar mudanças de backend PHP com lint antes de concluir**
   Faça isto em vez disso: rode `php -l` nos arquivos alterados e revise warnings/erros imediatamente.
2. **[2026-03-28] Revisar trecho após patch autocorrigido**
   Faça isto em vez disso: quando um patch for autocorrigido, releia o bloco alterado e rode checagem de sintaxe antes de seguir.
3. **[2026-04-08] Aliases POST legados devem preservar método e evitar colisões**
   Faça isto em vez disso: use redirecionamento `307` para encaminhar payloads de formulário e aplique `whereNumber()` em rotas com parâmetros para não capturar literais como `delete`.
4. **[2026-04-15] Páginas com `layouts.migration` precisam de auth mock em teste**
   Faça isto em vez disso: em testes de páginas que usam o layout compartilhado, faça mock de `LegacyAuthSessionServiceInterface` para `currentUser`, `currentChurch` e `availableChurches` antes de renderizar a view.
5. **[2026-04-16] Testes com `session_start()` precisam de save path gravável**
   Faça isto em vez disso: aponte `session.save_path` para `sys_get_temp_dir()` ou outro diretório gravável antes de criar/ler sessão nativa em feature tests.
6. **[2026-05-09] Commits amplos precisam de tema dominante**
   Faça isto em vez disso: leia `git diff --stat` e os untracked antes do commit; nomeie pelo fluxo principal em vez de um arquivo isolado.
7. **[2026-05-09] API de cidades precisa de fallback**
   Faça isto em vez disso: consulte a fonte principal e uma segunda fonte compatível; cubra no teste a queda da primeira API.
8. **[2026-05-09] Localidades precisam de peça única**
   Faça isto em vez disso: centralize listas de UF/cidade em um asset compartilhado e deixe as views só declararem os campos.

## Shell e Confiabilidade
1. **[2026-03-28] Evitar suprimir warning de filesystem no PHP**
   Faça isto em vez disso: valide permissões/caminhos e retorne erro controlado sem emitir saída antes de redirect/header.
2. **[2026-05-09] Bind mount em SELinux precisa de label**
   Faça isto em vez disso: use `:z` nos volumes do `docker-compose` e só valide `storage` depois de preparar diretórios/permissões no bootstrap.

## Regras do Domínio
1. **[2026-03-28] Upload de importação deve falhar com mensagem amigável**
   Faça isto em vez disso: checar/criar diretório de destino com validação e, em caso de falha, interromper fluxo com resposta de erro sem warnings.
2. **[2026-03-28] Importação deve aceitar fallback de diretório em ambiente restrito**
   Faça isto em vez disso: quando `storage/importacao` não estiver disponível, usar fallback seguro em `/tmp/check-planilha-imobilizado/importacao` e validar path permitido nos serviços.
3. **[2026-03-28] Paginação deve usar helper único**
   Faça isto em vez disso: renderizar paginação com `\App\Helpers\PaginationHelper::render()` para preservar filtros e manter UI consistente.
4. **[2026-03-28] Remover bloco legado inativo com markup/CSS quebrado**
   Faça isto em vez disso: quando encontrar trecho desativado (feature flag false) com código duplicado/corrompido em view, substituir por stub limpo para evitar ruído de análise e regressões indiretas.
5. **[2026-05-09] Ordem do menu é global em `configuracoes.menu_order`**
   Faça isto em vez disso: ordenar o menu no composer/serviço e salvar a sequência arrastável na mesma linha de configurações.
6. **[2026-05-09] Card de filtros precisa alternar fixo e solto**
   Faça isto em vez disso: injete o toggle de fixar no layout base e persista o estado por página sem quebrar o modo automático.
7. **[2026-05-09] Tela de etiquetas deve filtrar dentro da própria view**
   Faça isto em vez disso: abrir `/products/label` sem igreja e só aplicar `comum_id` escolhido no formulário da página.
8. **[2026-05-09] Importação de planilha precisa avisar por igreja**
   Faça isto em vez disso: destacar em vermelho na tela inicial e na prévia que o fluxo importa a igreja inteira, não dependência.

## Diretrizes do Usuário
1. **[2026-03-28] Responder em PT-BR com foco em ação**
   Faça isto em vez disso: implementar correções diretamente no código e resumir o que foi validado.
