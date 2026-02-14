# Configuração HTTPS para Desenvolvimento

## Por que HTTPS?

A **Web Speech API** (reconhecimento de voz) do navegador **requer conexão HTTPS** por questões de segurança. Sem HTTPS, o navegador bloqueia o acesso ao microfone.

## Como Usar

### 1. Certificados SSL já estão configurados

Os certificados SSL auto-assinados foram criados em `Dockerfiles/ssl/`:
- `certs/localhost.crt` - Certificado
- `private/localhost.key` - Chave privada

### 2. Acessar o sistema via HTTPS

**Após rodar `docker compose up -d`**, acesse:

```
https://localhost:8443
```

⚠️ **IMPORTANTE**: Seu navegador mostrará um aviso de segurança porque o certificado é auto-assinado.

### 3. Aceitar o certificado no navegador

#### Google Chrome/Edge:
1. Você verá "Sua conexão não é particular"
2. Clique em **"Avançado"**
3. Clique em **"Ir para localhost (não seguro)"**

#### Firefox:
1. Você verá "Aviso: risco potencial de segurança à frente"
2. Clique em **"Avançado"**
3. Clique em **"Aceitar o risco e continuar"**

### 4. Testar o reconhecimento de voz

1. Clique no botão flutuante de **microfone** (roxo no canto inferior direito)
2. Permita o acesso ao microfone quando solicitado
3. O botão deve ficar **vermelho pulsando** = microfone ativo
4. Fale números: "um dois três quatro"
5. O código será preenchido automaticamente no campo de busca

## Portas

- **HTTP**: `http://localhost:8080` (redirecionado para HTTPS)
- **HTTPS**: `https://localhost:8443` ✅ **Use esta**

## Regenerar Certificados (se necessário)

```bash
cd Dockerfiles/ssl
rm -rf certs/* private/*
openssl req -x509 -nodes -days 365 -newkey rsa:2048 \
  -keyout private/localhost.key \
  -out certs/localhost.crt \
  -subj "/C=BR/ST=State/L=City/O=Dev/CN=localhost"
docker compose restart web
```

## Troubleshooting

### Microfone não funciona mesmo em HTTPS

1. Abra o **Console do navegador** (F12 → Console)
2. Verifique as mensagens de erro
3. Verifique se o navegador permitiu o acesso ao microfone:
   - Chrome: Clique no ícone de **cadeado** → **Permissões** → **Microfone: Permitir**

### Redirecionamento HTTP → HTTPS não funciona

Se acessar `http://localhost:8080` e não for redirecionado:
1. Limpe o cache do navegador
2. Acesse diretamente `https://localhost:8443`

### Containers não sobem após rebuild

```bash
docker compose down -v
docker compose build --no-cache
docker compose up -d
```
