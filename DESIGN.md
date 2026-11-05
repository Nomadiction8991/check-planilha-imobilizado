# Design System — Ecossistema Anvy Softwares

> **Versão:** 1.0  
> **Estilo:** Rustic Minimal / Minimalismo funcional  
> **Abordagem:** Mobile-first · Responsivo · Acessível · Consistente  
> **Marca mantenedora:** **Anvy Softwares**  
> **Identidade do produto:** independente; cada sistema pode ter **nome e logotipo próprios**.

---

## 1. Objetivo

Este documento define a linguagem visual e os padrões de experiência para **produtos desenvolvidos dentro do ecossistema Anvy Softwares**. Ele não define o nome, a marca comercial nem a logo do sistema em desenvolvimento.

A referência visual é um painel de gestão com aparência **rústica, sóbria e minimalista**: superfícies claras levemente aquecidas, tipografia forte, contornos discretos, ícones de traço simples e um azul reservado para ações e estados relevantes.

**Princípio central:** mostrar apenas o necessário para a tarefa atual, sem esconder funções importantes ou prejudicar a descoberta de recursos.

### Arquitetura de marca

- **Marca institucional:** Anvy Softwares — identificação da empresa responsável pelo ecossistema.
- **Marca do produto:** `{{PRODUCT_NAME}}` — nome próprio e substituível de cada sistema.
- **Logo do produto:** `{{PRODUCT_LOGO}}` — pode ser completamente diferente da logo da Anvy.
- **Vínculo institucional opcional:** “Um produto Anvy Softwares”, preferencialmente na tela *Sobre*, rodapé ou informações institucionais, sem competir visualmente com a marca do produto.
- **Regra:** nunca usar “Anvy Softwares” como título do aplicativo por padrão e nunca inserir a logo institucional automaticamente na barra de navegação do produto.

---

## 2. Direção de arte

| Característica | Diretriz |
|---|---|
| Linguagem | Minimalista, funcional, levemente rústica e contemporânea |
| Sensação | Calma, clareza, segurança e controle |
| Fundo | Off-white quente, não branco puro em toda a interface |
| Superfícies | Claras, com bordas finas e sombras contidas |
| Cor de destaque | Azul extraído da logo oficial da Anvy |
| Contraste | Texto grafite sobre fundos claros |
| Composição | Espaço em branco generoso e densidade baixa de informações |
| Iconografia | Ícones lineares, consistentes, sem excesso de decoração |
| Movimento | Transições curtas e discretas, respeitando movimento reduzido |

**Evitar:** gradientes chamativos, efeitos *glassmorphism*, excesso de cartões, muitos gráficos na tela inicial, botões primários concorrentes, sombras pesadas, cores saturadas sem significado e menus extensos no celular.

---

## 3. Cores e tokens

### 3.1 Origem do azul

A imagem fornecida da logo Anvy usa predominantemente **RGB(37, 131, 197)**, correspondente a **`#2583C5`**. Esse é o **azul-base institucional de referência** para o ecossistema — não uma obrigação de identidade para cada produto.

### 3.2 Paleta de referência (tema claro)

| Token | HEX | Aplicação |
|---|---|---|
| `brand-500` | `#2583C5` | Azul principal da Anvy, branding e elementos de destaque |
| `brand-600` | `#176DA9` | Botão primário e links com melhor contraste em superfícies claras |
| `brand-700` | `#105786` | Hover, texto destacado e interações reforçadas |
| `brand-100` | `#DDECF7` | Estados selecionados, badges e fundos leves |
| `canvas` | `#F7F4ED` | Plano de fundo geral, inspirado na referência |
| `surface` | `#FFFDF9` | Cartões, painéis e formulários |
| `surface-muted` | `#F0EBE3` | Áreas secundárias e agrupamentos suaves |
| `ink` | `#272727` | Textos principais, títulos, ícones |
| `ink-muted` | `#60636A` | Textos auxiliares e metadados |
| `border` | `#D1CDC6` | Bordas e divisões discretas |
| `border-strong` | `#77736E` | Contornos que precisam de mais presença |
| `success` | `#267454` | Confirmação / concluído |
| `warning` | `#986500` | Atenção / pendente |
| `danger` | `#B33A3A` | Erro / ação destrutiva |

**Uso das cores:** aproximadamente 80% superfícies neutras, 15% textos/bordas e até 5% destaques coloridos. Trata-se de uma orientação visual, não de uma regra matemática.

> **Importante:** não utilizar `brand-500` automaticamente para textos pequenos sobre fundo claro. Empregar `brand-700` ou outra combinação que passe nos testes de contraste WCAG.

### 3.3 Temas próprios de produtos

Cada produto deve poder sobrescrever o grupo de tokens `--product-*`, sem modificar os componentes fundamentais. **A cor de produto não precisa ser a cor da Anvy.**

```css
:root {
  /* Identidade do produto: padrão inicial herdado da Anvy */
  --product-primary: #176DA9;
  --product-primary-hover: #105786;
  --product-primary-soft: #DDECF7;

  /* Design system compartilhado */
  --ui-bg: #F7F4ED;
  --ui-surface: #FFFDF9;
  --ui-surface-muted: #F0EBE3;
  --ui-text: #272727;
  --ui-text-muted: #60636A;
  --ui-border: #D1CDC6;
  --ui-focus: #105786;
  --ui-success: #267454;
  --ui-warning: #986500;
  --ui-danger: #B33A3A;

  --radius-sm: 6px;
  --radius-md: 10px;
  --radius-lg: 14px;
  --shadow-card: 0 2px 8px rgb(39 39 39 / 5%);
}

/* Exemplo: um sistema pode ter identidade verde e logo própria */
[data-product-theme="exemplo-verde"] {
  --product-primary: #246B53;
  --product-primary-hover: #194C3B;
  --product-primary-soft: #DDEDE5;
}
```

**Observação:** a associação entre `data-product-theme` e o produto é configurável. O exemplo verde é ilustrativo e não representa uma decisão de marca.

---

## 4. Tipografia

- **Fonte de interface:** `Inter`, com alternativas `system-ui, sans-serif`.
- **Fonte para títulos (opcional):** `IBM Plex Sans` ou `Inter` em peso 600–700.
- **Estética rústica discreta:** pode aparecer em ilustrações, materiais institucionais ou detalhes; **não** aplicar fontes decorativas à interface operacional.
- **Dados técnicos / códigos:** `IBM Plex Mono`, `ui-monospace`, `monospace`.

| Elemento | Celular | Tablet / desktop |
|---|---|---|
| Título da página | 24px / 700 | 28–32px / 700 |
| Título de seção | 18px / 650 | 20px / 650 |
| Texto padrão | 16px / 400 | 14–16px / 400 |
| Texto auxiliar | 14px / 400 | 13–14px / 400 |
| Rótulo de botão | 15–16px / 600 | 14–16px / 600 |
| Números de indicadores | 26px / 700 | 28–32px / 700 |

Altura de linha do texto comum: **1.45 a 1.6**. Priorizar legibilidade em operações prolongadas.

---

## 5. Espaçamento, dimensões e composição

### Escala de espaçamento

Usar uma base de **4px**: `4, 8, 12, 16, 20, 24, 32, 40, 48, 64`.

- Espaçamento interno de cartão: 16px (celular) ou 20–24px (telas maiores).
- Distância entre seções: 24–32px.
- Distância entre controles de formulário: 12–16px.
- Altura de campos e botões principais: **44–48px**, preservando alvos de toque adequados.
- Largura máxima de conteúdo principal: **1440px**, dependendo do módulo.
- Bordas: preferir 1px; raio de 6–14px; **não** arredondar tudo como pílulas.
- Sombras: muito leves, secundárias em relação ao contorno.

---

## 6. Responsividade — mobile-first

| Faixa | Layout e comportamento |
|---|---|
| **Mobile** `< 640px` | Uma coluna; botões de ação visíveis; navegação inferior com 3–5 destinos prioritários quando fizer sentido; tabelas transformadas em cartões ou listas |
| **Tablet** `640–1023px` | Uma ou duas colunas; mais contexto; navegação lateral recolhida ou cabeçalho compacto |
| **Desktop** `>= 1024px` | Navegação lateral persistente; área de busca; indicadores alinhados; tabelas e filtros mais completos |

Breakpoints são pontos de partida, não tamanhos fixos dos dispositivos. Ajustar os componentes conforme a largura **real do contêiner**.

### Celular

- Destacar a **ação principal** e as informações que permitem agir.
- Priorizar buscas, filtros simples e listas recentes.
- Usar cartões compactos para substituir tabelas largas; não ocultar informações críticas.
- Navegação inferior somente para destinos principais; ações menos frequentes podem ficar em menu complementar.
- Usar áreas seguras da tela (`safe-area-inset-*`) e impedir rolagem horizontal acidental.

### Tablet

- Mostrar resumos e listas simultaneamente quando houver espaço.
- Manter controles claros com navegação simplificada.
- Evitar reproduzir o layout de desktop em escala reduzida.

### Desktop

- Sidebar persistente de ~240px, recolhível quando necessário.
- Barra superior com busca global ou contextual, notificações e conta.
- Indicadores em até 3–4 colunas, conforme relevância.
- Tabela operacional com filtros, ordenação e ações contextualizadas.
- Espaços vazios são desejáveis: **não preencher a tela apenas porque há espaço disponível**.

---

## 7. Navegação e arquitetura da interface

### Estrutura recomendada

```text
AppShell
├── ProductIdentity (logo + nome configuráveis)
├── Navigation
│   ├── Visão geral
│   ├── [Módulos do sistema]
│   └── Configurações
├── Topbar
│   ├── Título / localização atual
│   ├── Busca (se útil)
│   ├── Notificações (se houver)
│   └── Conta
└── MainContent
    ├── Cabeçalho da página
    ├── Ação principal
    ├── Resumo contextual (se útil)
    └── Conteúdo principal
```

Os módulos `Pedidos`, `Ativos` e `Relatórios` da referência visual são **exemplos**, não uma estrutura obrigatória para todos os sistemas.

### Princípios de navegação

1. Uma ação primária evidente por contexto.
2. Módulos com rótulos objetivos, não jargões internos.
3. Navegação consistente entre telas.
4. Ações secundárias em menus contextuais **com rótulos acessíveis**.
5. Localização atual sempre identificável.
6. Estado de seleção não deve depender apenas da cor.

---

## 8. Componentes padrões

### Botões

- **Primário:** fundo `--product-primary`, texto branco, foco visível. Usar para *Salvar*, *Criar* ou *Confirmar*.
- **Secundário:** superfície clara, borda discreta, texto escuro. Usar para *Voltar*, *Visualizar* ou opções alternativas.
- **Terciário / ghost:** sem fundo; para ações de baixa prioridade.
- **Destrutivo:** identidade visual de perigo; confirmação proporcional ao risco.
- Estados obrigatórios: `default`, `hover` (quando aplicável), `focus-visible`, `active`, `disabled` e `loading`.

### Cartões e indicadores

- Preferir borda fina, fundo claro e título curto.
- Indicadores devem responder a uma pergunta útil para o usuário.
- Nunca exibir uma sequência longa de indicadores irrelevantes.
- Cartão clicável deve sinalizar interatividade e funcionar via teclado.

### Listas e tabelas

- Exibir informações essenciais: identificação, descrição breve, status e ação.
- Desktop: tabela com cabeçalho claro, filtro e paginação quando necessário.
- Mobile: lista/cartões de linhas compactas; detalhes ao abrir o item.
- Status com **texto + sinal visual**, nunca apenas por cor.
- Estados vazios com instrução útil; erro com orientação recuperável.

### Formulários

- Rótulos persistentes acima dos campos.
- Exemplos e dicas apenas quando agregarem valor.
- Mensagens de erro específicas, próximas ao campo e anunciadas de forma acessível.
- Campos relacionados agrupados visualmente.
- Botão principal ao fim da sequência lógica ou fixo no mobile somente quando não encobrir conteúdo.

### Ícones

Biblioteca sugerida: **Lucide**, com tamanho padrão de **20–24px**, traço consistente de aproximadamente **1.75–2px**. Combinar ícone e texto em navegação importante. Ícones decorativos não precisam de rótulo; ícones interativos precisam de nome acessível.

---

## 9. Acessibilidade e estados

- Buscar conformidade com **WCAG 2.2 AA**.
- Contraste mínimo usual de **4.5:1** para texto normal e **3:1** para texto grande e componentes gráficos relevantes.
- Alvos de toque preferencialmente **44×44px ou maiores**.
- Navegação por teclado, foco visível, rótulos de formulário e uso de HTML semântico.
- Não comunicar estados apenas por cor ou ícone.
- Suportar zoom de texto, redimensionamento e `prefers-reduced-motion`.
- Usar skeleton ou indicador de carregamento apenas quando realmente houver espera; evitar saltos de layout.
- Tratar explicitamente estados **carregando, vazio, erro, sem permissão, sucesso e offline** conforme o caso.

> Validação de contraste e acessibilidade deve ser realizada no produto real, inclusive após qualquer personalização dos tokens.

---

## 10. Padrões de telas

### Visão geral / Dashboard

```text
[Nome do produto]              [Busca] [Conta]

Título da página          [Ação principal]
Descrição breve (opcional)

[Indicador útil] [Indicador útil] [Indicador útil]

Conteúdo principal / itens recentes
[Identificação] [Resumo] [Status] [Ação]
```

No celular, indicadores são empilhados ou reorganizados, e a ação principal fica próxima do contexto. Não reproduzir à força todas as colunas de desktop.

### Listagem

- Cabeçalho, busca, filtros essenciais e ação de criação (quando aplicável).
- Resultados legíveis; número total quando útil; ordenação e paginação.
- Ações por item sem poluição visual.

### Detalhes / Edição

- Título + estado atual + principais ações.
- Informações organizadas por seções bem identificadas.
- Formulários progressivos quando houver muitos campos.
- Confirmação e retorno claro após alterações.

---

## 11. Identidade do produto e uso das logos

### Regras de implementação

A interface deve aceitar configurações independentes:

```ts
interface ProductBranding {
  productName: string;
  productLogoUrl?: string;
  productFaviconUrl?: string;
  primaryColor?: string;
  primaryHoverColor?: string;
  primarySoftColor?: string;
  showAnvyAttribution?: boolean;
}
```

- **Anvy Softwares:** marca corporativa; sua logo original é um ativo institucional separado.
- **Sistema:** identidade própria; pode usar outra cor, símbolo e nome.
- **Fallback:** se ainda não existir logo de produto, usar um ícone neutro/placeholder e o nome provisório do produto — não assumir a logo da Anvy.
- **Aplicação institucional:** usar a marca Anvy apenas em locais definidos pela estratégia de marca, como rodapé “Desenvolvido por Anvy Softwares”, seção Sobre ou página de portfólio.
- **Não redesenhar a marca institucional** nem alterar suas proporções, cores ou formas quando usada.

### Organização sugerida de assets

```text
public/
  branding/
    anvy/
      logo.png                 # arquivo institucional fornecido
    products/
      [product-slug]/
        logo.svg               # opcional
        favicon.svg            # opcional
```

---

## 12. Tema escuro (opcional)

Uma variante escura pode existir, mas deve ser testada separadamente. Não basta inverter o fundo: ajustar superfícies, contraste, bordas, cores de status e estados de interação. **O tema claro é o padrão desta referência visual.**

---

## 13. Regras de consistência e checklist de revisão

Antes de aprovar uma interface, verificar:

- [ ] O nome e a logo apresentados são **do produto**, não automaticamente da Anvy.
- [ ] O azul institucional foi usado como ponto de partida, sem impedir personalizações.
- [ ] A tela apresenta apenas as informações relevantes para a tarefa.
- [ ] Há uma hierarquia clara entre ação principal, secundárias e conteúdo.
- [ ] Mobile, tablet e desktop foram considerados individualmente.
- [ ] Componentes usam tokens compartilhados de cor, tipografia e espaço.
- [ ] Tabelas, formulários e menus continuam utilizáveis em telas estreitas.
- [ ] Contrastes, foco, teclado e leitores de tela foram testados.
- [ ] Estados vazio, carregando, erro e sucesso foram contemplados.
- [ ] A interface permanece simples sem sacrificar funções importantes.

---

## 14. Instruções para designers e agentes de IA

Ao criar uma nova tela para um produto do ecossistema:

1. **Consultar este `DESIGN.md` antes de implementar qualquer componente.**
2. Pedir ou obter do contexto o nome, a logo, os módulos e a identidade do **produto específico**.
3. Se a identidade do produto estiver indefinida, usar os tokens de referência e uma marca temporária neutra, sem nomear o sistema “Anvy Softwares”.
4. Iniciar pelo fluxo mobile e expandir progressivamente para tablet e desktop.
5. Priorizar composição enxuta, fundo quente, tipografia legível, contornos suaves e azul bem dosado.
6. Reaproveitar componentes e tokens antes de criar variações.
7. Evitar criar funções, campos, módulos ou métricas fictícias como se fossem requisitos do sistema; exemplos de layout são apenas exemplos.
8. Verificar responsividade, acessibilidade e estados de interface antes de considerar a tela concluída.

**Resumo visual:** *Rustic Minimal*: funcional, discreto, leve, organizado, responsivo e com identidade de produto desacoplada da marca Anvy Softwares.
