# Passo a Passo: Modo igreja

## O que é
O modo igreja faz o notebook da igreja servir o sistema para os celulares da rede local.

- **Sem internet:** o notebook cria a própria rede Wi-Fi para os celulares.
- **Com internet:** o notebook repassa os pedidos dos celulares ao servidor online.

## O que a pessoa pode fazer
- **Ligar a rede dos celulares:** na tela Conexão, clique em "Ativar modo offline". A rede padrão se chama `CheckPlanilha`.
- **Conectar um celular:** ligue o celular na rede do notebook e digite no navegador o endereço que aparece na tela Conexão. Por exemplo: `http://10.42.0.1:8080`.
- **Ver a situação:** a faixa no topo mostra se o notebook está online ou offline e qual endereço usar.
- **Desligar a rede dos celulares:** na tela Conexão, clique em "Desligar modo offline".

## Configuração
As configurações ficam no arquivo `modo-igreja.env`, dentro da pasta de dados do programa. Se o arquivo não existir, o lançador do Linux cria um.

- `MODO_IGREJA_ATIVO=true`: liga o modo igreja. Por padrão, ele fica desligado.
- `MODO_IGREJA_PORTA`: porta do sistema. Padrão: `8080`.
- `MODO_IGREJA_REDE_NOME`: nome da rede dos celulares. Padrão: `CheckPlanilha`.
- `MODO_IGREJA_SERVIDOR_URL`: endereço do servidor online, usado no repasse e na sincronização.
- `MODO_IGREJA_APARELHO_TOKEN`: código deste notebook para a sincronização. Não compartilhe este valor.

## Como ligar no Linux
1. Rode o lançador do Linux do pacote do sistema.
2. O lançador confere se a porta está livre. Se não estiver, ele para e avisa. Feche o outro programa, ou troque `MODO_IGREJA_PORTA` por outra porta, como `8081`.
3. Depois de subir o servidor, o lançador espera até 10 segundos. Se o servidor não iniciar, ele mostra uma mensagem de erro.

## Como ligar no Windows
Use o instalador do Windows. Antes de usar a rede dos celulares no Windows, leia a seção "Limitações conhecidas".

## Como o notebook decide se está online
- A cada minuto, o notebook pergunta ao servidor se ele responde.
- Depois de 2 falhas seguidas, o notebook passa para offline.
- Depois de 2 respostas seguidas, ele volta para online.

## Problemas comuns
- **Aparece "Sem endereço de rede para os celulares":** ligue o modo offline na tela Conexão. No Linux, o endereço só aparece quando o notebook tem uma rede principal.
- **O lançador diz que a porta está em uso:** feche o programa que usa a porta, ou troque `MODO_IGREJA_PORTA`.
- **O lançador diz que o servidor não iniciou:** leia a mensagem completa do lançador e confira a porta.

## Limitações conhecidas
- **Windows:** a rede dos celulares ainda não foi testada num Windows real. Sem internet, a ligação pode ser recusada. No Windows, a rede não liga sozinha: é preciso ligar pela tela Conexão.
- **Segurança da rede:** o servidor usado é o do próprio PHP, feito para testes e redes controladas. Ele aceita conexões de qualquer rede do notebook. Use o notebook só em rede de confiança e, se possível, bloqueie as outras redes no firewall.
- **Atraso na tela:** a situação da rede dos celulares fica guardada por até 15 segundos. Por isso, a tela pode demorar um pouco para mostrar uma mudança.
