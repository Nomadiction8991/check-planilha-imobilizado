# Teste de importação no navegador

O teste usa dados fictícios e abre uma janela virtual de 360 por 800 pixels. Faz login, envia `tests/Fixtures/importacao/relatorio-ficticio.xls`, escolhe a dependência na prévia, confirma e confere duas linhas concluídas na tela e dois produtos no banco.

São necessários Docker, as imagens locais `mysql:8.0` e `check-planilha-php84:test`, dependências PHP em `vendor`, Node.js, o módulo Playwright e seu navegador Chromium já instalado. Informe em `NODE_PATH` o diretório que contém o módulo `playwright`. Por exemplo:

```bash
NODE_PATH=/caminho/para/node_modules bash tests/Browser/importacao/executar.sh
```

O script copia a aplicação para um diretório temporário, cria uma rede e dois containers exclusivos, usa porta local aleatória e armazena o banco em memória. Não usa o arquivo `.env` nem o armazenamento do projeto. Ao terminar, inclusive em caso de erro, remove os containers, a rede e o diretório temporário. Não executa migrações e usa apenas o esquema mínimo necessário ao cenário.

Para testar também a opção **Importar tudo** da igreja, execute com `INVESTIGAR_IMPORTAR_TUDO=1`. Esse cenário confere as ações enviadas e exige duas linhas importadas.

```bash
INVESTIGAR_IMPORTAR_TUDO=1 NODE_PATH=/caminho/para/node_modules bash tests/Browser/importacao/executar.sh
```
