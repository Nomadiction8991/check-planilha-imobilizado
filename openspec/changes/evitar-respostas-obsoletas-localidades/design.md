## Context

O script compartilhado de localidades inicializa três pares de campos em paralelo e dispara uma nova carga de cidades a cada troca de UF. Cada resposta atualmente pode atualizar o mesmo campo sem confirmar se ainda representa a escolha mais recente.

## Goals / Non-Goals

**Goals:**

- Associar cada carga de estados ou cidades a um ciclo de atualização identificável.
- Cancelar requisições fetch anteriores quando o navegador oferecer AbortController.
- Ignorar respostas e erros de requisições que deixaram de ser atuais.
- Preservar o fallback existente para navegadores sem AbortController e mensagens atuais para erro.

**Non-Goals:**

- Alterar os endpoints ou o contrato JSON do backend.
- Adicionar dependências externas ou reescrever os formulários em framework.
- Validar a cidade no servidor contra uma nova fonte de dados.

## Decisions

### Uma sequência por par de campos

`initPair` manterá um contador de sequência local e o passará às cargas de cidades. Antes de uma nova consulta, a sequência será incrementada; a resposta só poderá preencher o select se sua sequência ainda for a atual e se a UF do select continuar igual à UF consultada. Isso protege inclusive ambientes em que o cancelamento não esteja disponível ou seja ignorado pelo servidor.

Alternativa considerada: comparar somente `stateSelect.value` no retorno. Essa opção não distingue duas consultas sucessivas para a mesma UF e permite que uma resposta mais antiga substitua uma carga mais nova, por isso foi descartada.

### Cancelamento cooperativo

`fetchJson` receberá opcionalmente um `AbortSignal`, e `loadCities` usará um `AbortController` por par. Ao iniciar outra carga, o controller anterior será abortado. Erros de abort não deverão registrar erro nem substituir a mensagem da carga vigente.

Alternativa considerada: bloquear o select de estado durante cada consulta. Isso evita concorrência, mas piora a navegação e não cobre respostas já iniciadas; foi descartado em favor da invalidação explícita.

### Estado visual somente pela carga vigente

A mensagem de carregamento será aplicada imediatamente apenas depois de invalidar a sequência anterior. Preenchimento, erro e habilitação serão condicionados à carga vigente. A troca para estado vazio continuará usando a função de reset existente.

## Risks / Trade-offs

- [AbortController indisponível] → a sequência local continua impedindo respostas obsoletas de alterar a interface.
- [Servidor responde depois do abort] → a verificação de sequência e UF descarta o retorno.
- [Usuário alterna várias vezes] → cada nova carga cancela a anterior e mantém apenas a última como fonte de estado.

## Migration Plan

Nenhuma migração de dados é necessária. Publicar o script junto com os testes; rollback consiste em restaurar a versão anterior do arquivo compartilhado caso seja detectada incompatibilidade no navegador.
