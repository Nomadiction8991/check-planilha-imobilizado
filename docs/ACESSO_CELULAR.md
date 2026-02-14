# 📱 Como Acessar do Celular

## ✅ Configuração Concluída

O sistema já está pronto para acesso via celular! As seguintes configurações foram aplicadas:

1. ✅ **Firewall liberado** - Portas 8080 e 8443 abertas
2. ✅ **Certificado SSL atualizado** - Suporte para IP `10.0.0.128`
3. ✅ **Docker configurado** - Expondo portas para rede local

---

## 📡 Como Acessar

### Pré-requisitos
- ✅ PC e celular conectados na **mesma rede WiFi**
- ✅ Docker containers rodando (`docker compose up -d`)

### No Celular

#### 1️⃣ Abra o navegador (Chrome, Firefox, Safari, etc.)

#### 2️⃣ Digite o endereço:

**HTTPS (Recomendado para microfone/câmera):**
```
https://10.0.0.128:8443
```

**HTTP (alternativa, mas sem microfone):**
```
http://10.0.0.128:8080
```

#### 3️⃣ Aceitar o Aviso de Segurança

Como o certificado é auto-assinado, você verá um aviso:

**Android (Chrome):**
1. Toque em **"Avançado"**
2. Toque em **"Continuar para 10.0.0.128 (não seguro)"**

**iPhone (Safari):**
1. Toque em **"Mostrar detalhes"**
2. Toque em **"Visitar este site"**
3. Confirme novamente

#### 4️⃣ Fazer Login

Use suas credenciais normais do sistema.

---

## 🎤 Usando Microfone no Celular

**Importante:** O reconhecimento de voz **só funciona via HTTPS**!

1. Acesse via `https://10.0.0.128:8443`
2. Toque no botão flutuante roxo de **microfone** (canto inferior direito)
3. Permita o acesso ao microfone quando solicitado
4. O botão ficará **vermelho pulsando** = ativo
5. Fale os números: "um dois três quatro"
6. O código será preenchido automaticamente

---

## 📷 Usando Câmera no Celular

1. Toque no botão flutuante rosa de **câmera** (canto inferior direito)
2. Permita o acesso à câmera quando solicitado
3. Aponte para um código de barras
4. A leitura será automática

---

## 🔧 Troubleshooting

### ❌ Não consigo acessar

**Verificar se PC e celular estão na mesma WiFi:**
- No PC: `ip addr show` ou `hostname -I`
- No celular: Configurações → WiFi → Nome da rede

**Verificar se o Docker está rodando:**
```bash
docker compose ps
```
Deve mostrar containers `web` e `db` com status `Up`

**Verificar firewall:**
```bash
sudo ufw status
```
Deve mostrar portas 8080 e 8443 permitidas

**Testar do próprio PC primeiro:**
```bash
curl -k https://10.0.0.128:8443
```

### ❌ Certificado sempre rejeitado no celular

Isso é normal! Apenas aceite o aviso. É um certificado auto-assinado para desenvolvimento.

Para **produção**, você precisaria de um certificado válido (ex: Let's Encrypt).

### ❌ Microfone não funciona

- ✅ Certifique-se de usar **HTTPS** (porta 8443)
- ✅ Permita o acesso ao microfone quando solicitado
- ✅ Abra o console do navegador para ver erros (Chrome: Menu → Mais ferramentas → Console do desenvolvedor)

### ❌ O IP mudou

Se o IP do seu PC mudar (ex: após reconectar WiFi), você precisa:

1. Verificar novo IP: `hostname -I`
2. Atualizar certificado SSL
3. Reiniciar container

**Script rápido:**
```bash
# No diretório do projeto
IP=$(hostname -I | awk '{print $1}')
echo "Novo IP: $IP"

# Atualizar /tmp/openssl-san.cnf com novo IP
# Regenerar certificado
# Reiniciar container
docker compose restart web
```

---

## 📊 Informações Técnicas

**IP do PC:** `10.0.0.128`

**Portas Expostas:**
- `8080` → HTTP (redireciona para HTTPS)
- `8443` → HTTPS (principal)

**Certificado SSL:**
- Válido para: `localhost`, `127.0.0.1`, `10.0.0.128`
- Válido por: 365 dias
- Tipo: Auto-assinado (desenvolvimento)

**Container Docker:**
- Nome: `check-planilha-imobilizado-ccb-web-1`
- Imagem: `checkplanilha:local`
- Apache 2.4 + PHP 8.3

---

## 🌐 Acesso de Fora da Rede Local (opcional)

Se você quiser acessar de **fora da sua casa** (ex: internet móvel):

1. Configure **port forwarding** no seu roteador
2. Aponte portas `8080` e `8443` para `10.0.0.128`
3. Use seu **IP externo** (descobrir em: https://meuip.com.br/)
4. Acesse via: `https://SEU_IP_EXTERNO:8443`

⚠️ **Atenção:** Isso expõe o sistema na internet. Use apenas para desenvolvimento!
