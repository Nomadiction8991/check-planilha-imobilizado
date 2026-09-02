## Context

Consulte `proposal.md` para a motivação. A persistência das análises resolve o diretório do projeto em `storage/tmp`, enquanto o comando de manutenção calculava o padrão a partir da localização física do próprio arquivo PHP. Esses caminhos divergem porque o comando está um nível de diretório mais profundo.

## Goals / Non-Goals

**Goals:**

- Compartilhar a resolução de diretório entre a persistência e a limpeza.
- Preservar a injeção de diretório alternativo usada pelos testes.
- Evitar qualquer operação de banco ou remoção fora do diretório de análises.

**Non-Goals:**

- Alterar estados que qualificam uma importação como ativa.
- Alterar o conteúdo JSON ou o fluxo de importação.
- Limpar registros de banco automaticamente sem a opção explícita já existente.

## Decisions

- Usar `storage_path('tmp')` como padrão do comando, pois é o contrato já utilizado pela persistência da análise. A alternativa seria calcular um caminho relativo a `__DIR__`, mas isso depende da estrutura do código e não do armazenamento configurado pela aplicação.
- Manter `setStorageDir()` como override explícito para testes e manutenção controlada. A alternativa de ler sempre o diretório global dificultaria testes isolados e operações seguras.
- Adicionar um teste com o diretório temporário padrão resolvido por `storage_path`, além dos testes existentes com diretório injetado, para impedir regressão silenciosa.

## Risks / Trade-offs

- [Risco] O diretório padrão pode não existir em uma instalação nova. → [Mitigação] O comportamento atual de avisar e encerrar com sucesso será preservado; a persistência continua responsável por criar o diretório quando necessário.
- [Risco] Um teste padrão pode deixar arquivos no armazenamento local. → [Mitigação] Usar um diretório temporário configurado durante o teste e restaurar a configuração ao final, sem tocar no armazenamento de produção.

## Migration Plan

Nenhuma migração de banco. O deploy normal passa a apontar a manutenção para o diretório correto. O rollback consiste em reverter o commit caso necessário.

## Open Questions

Nenhuma.
