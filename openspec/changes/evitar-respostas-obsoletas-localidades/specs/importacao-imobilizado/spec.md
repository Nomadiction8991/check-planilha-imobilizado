## Purpose

Mantém os campos de localização confiáveis quando o usuário troca de estado rapidamente e as respostas das consultas chegam em ordem diferente da interação.

## MODIFIED Requirements

### Requirement: Localização dinâmica em formulários

Os formulários que permitem selecionar estado e cidade DEVEM manter as opções de cidade sincronizadas com o estado atualmente selecionado, preservando a cidade previamente escolhida somente quando ela pertencer à consulta vigente. Uma resposta de consulta que não corresponda mais à seleção atual DEVE ser descartada sem alterar o campo.

#### Scenario: Resposta antiga chega depois de uma nova seleção
- **GIVEN** uma consulta de cidades para uma UF está pendente
- **WHEN** o usuário seleciona outra UF antes do retorno e a primeira resposta chega depois
- **THEN** o campo de cidade permanece carregando ou exibe somente as cidades da segunda UF, sem reaplicar as opções da primeira

#### Scenario: Troca de estado invalida a cidade anterior
- **GIVEN** uma cidade está selecionada no formulário
- **WHEN** o usuário troca a UF
- **THEN** a cidade escolhida é removida, o campo informa que as cidades estão sendo carregadas e a nova seleção só é liberada após o retorno correspondente

#### Scenario: Consulta vigente retorna erro ou lista vazia
- **GIVEN** a UF atual está selecionada
- **WHEN** a consulta correspondente falha ou retorna dados sem cidades válidas
- **THEN** o campo permanece desabilitado com uma mensagem orientando que não foi possível carregar as cidades

#### Scenario: Resposta vigente retorna cidades
- **GIVEN** a consulta correspondente à UF atual está pendente
- **WHEN** ela retorna cidades válidas
- **THEN** o campo é preenchido com essas opções, é habilitado e uma cidade previamente escolhida só é marcada se estiver presente na lista retornada
