# 📱 PWA - Progressive Web App

## ✅ Sistema Configurado como PWA

O **Check Planilha** agora pode ser instalado como aplicativo em qualquer dispositivo (celular, tablet ou desktop)!

---

## 🎯 Recursos PWA Implementados

### 1. **Instalação em Qualquer Página**
- ✅ Botão de instalação aparece automaticamente
- ✅ Funciona em **todas as páginas** do sistema
- ✅ Disponível mesmo antes do login

### 2. **Funcionamento Offline**
- ✅ Service Worker com cache inteligente
- ✅ Assets estáticos (CSS, JS, imagens) em Cache First
- ✅ Páginas dinâmicas em Network First com fallback
- ✅ Página offline personalizada

### 3. **Experiência Nativa**
- ✅ Ícone na tela inicial
- ✅ Tela de splash personalizada
- ✅ Barra de status do tema
- ✅ Atalhos rápidos (Android)

---

## 📲 Como Instalar

### Android (Chrome/Edge)

1. **Acesse o sistema** via navegador
2. **Aguarde** o botão "Instalar App" aparecer (canto inferior)
3. **Toque no botão** ou no menu ⋮ → "Instalar app"
4. Confirme a instalação
5. **Pronto!** O ícone aparecerá na tela inicial

### iPhone/iPad (Safari)

1. **Acesse o sistema** via Safari
2. **Toque no ícone de compartilhar** (quadrado com seta para cima)
3. Role e toque em **"Adicionar à Tela de Início"**
4. Personalize o nome (opcional)
5. Toque em **"Adicionar"**
6. **Pronto!** O ícone aparecerá na tela inicial

### Desktop (Chrome/Edge)

1. **Acesse o sistema** via navegador
2. **Clique no botão "Instalar App"** que aparece automaticamente
   - Ou clique no ícone ⊕ na barra de endereço
   - Ou menu ⋮ → "Instalar Check Planilha..."
3. Confirme a instalação
4. **Pronto!** O app abrirá em janela própria

---

## 🎨 Personalizações PWA

### Manifest (`/public/manifest-prod.json`)
```json
{
  "name": "Check Planilha - Sistema de Gestão",
  "short_name": "CheckPlanilha",
  "description": "Sistema de Gestão de Planilhas e Produtos",
  "theme_color": "#667eea",
  "background_color": "#ffffff",
  "display": "standalone",
  "orientation": "portrait"
}
```

### Atalhos Rápidos (Android)
- **Ver Planilhas** → `/spreadsheets/view`
- **Menu Principal** → `/menu`

---

## 🔧 Arquitetura Técnica

### Service Worker (`/public/sw.js`)

**Estratégias de Cache:**

1. **Cache First** (Assets Estáticos)
   - CSS, JS, imagens
   - CDN (Bootstrap, Bootstrap Icons)
   - Resposta imediata, atualização em background

2. **Network First** (Conteúdo Dinâmico)
   - Páginas de planilhas
   - Dados de produtos
   - API calls
   - Fallback para cache se offline

3. **Offline Fallback**
   - Página offline personalizada
   - Mensagem amigável com botão "Tentar Novamente"

### Script de Instalação (`/assets/js/pwa-install.js`)

**Funcionalidades:**
- ✅ Detecta quando o app pode ser instalado
- ✅ Mostra botão flutuante automaticamente
- ✅ Adiciona opção no menu do usuário
- ✅ Feedback visual (toasts) durante instalação
- ✅ Auto-oculta após 30 segundos (pode ser reaberto)
- ✅ Detecta se já está instalado

**API Global:**
```javascript
// Mostrar botão de instalação manualmente
PWAInstall.show();

// Ocultar botão
PWAInstall.hide();

// Verificar se já está instalado
if (PWAInstall.isInstalled()) {
  console.log('App já instalado!');
}

// Verificar se pode ser instalado
if (PWAInstall.canInstall()) {
  console.log('Instalação disponível!');
}
```

---

## 📊 Requisitos para Instalação

### Navegadores Suportados

| Navegador | Plataforma | Suporte |
|-----------|-----------|---------|
| Chrome | Android, Desktop | ✅ Total |
| Edge | Android, Desktop | ✅ Total |
| Firefox | Android | ✅ Parcial |
| Safari | iOS 11.3+ | ✅ Via "Adicionar à Tela" |
| Opera | Android, Desktop | ✅ Total |

### Critérios PWA (Todos atendidos ✅)

- [x] Servido via **HTTPS** (ou `localhost`)
- [x] Arquivo `manifest.json` válido
- [x] Service Worker registrado
- [x] Ícones em múltiplos tamanhos (192x192, 512x512)
- [x] `start_url` configurado
- [x] `display: standalone`
- [x] Nome e descrição definidos

---

## 🧪 Testar PWA

### Chrome DevTools

1. Abra **DevTools** (F12)
2. Vá para aba **Application**
3. Seção **Manifest**: Verifique configurações
4. Seção **Service Workers**: Verifique status
5. **Lighthouse** → Execute auditoria PWA

### Testar Offline

1. Instale o app
2. Abra **DevTools** → **Network**
3. Marque **"Offline"**
4. Recarregue a página
5. Deve mostrar conteúdo em cache ou página offline

---

## 🔄 Atualização do Service Worker

### Versão Atual
- **v4.0.0** - Sistema PWA completo

### Como Forçar Atualização

O Service Worker verifica atualizações automaticamente a cada 60 segundos quando o app está aberto.

**Forçar manualmente:**
1. DevTools → Application → Service Workers
2. Clique em "Update" ou "Unregister"
3. Recarregue a página

**Limpar cache:**
```javascript
// No console do navegador
caches.keys().then(keys => {
  keys.forEach(key => caches.delete(key));
  location.reload();
});
```

---

## 📈 Benefícios do PWA

### Para Usuários
- 🚀 **Acesso rápido** - Ícone na tela inicial
- 📵 **Funciona offline** - Cache inteligente
- 💾 **Economiza dados** - Conteúdo cacheado
- 📱 **Experiência nativa** - Sem barra de navegador
- 🔔 **Notificações** (futuro) - Push notifications

### Para o Sistema
- ⚡ **Performance melhorada** - Cache de assets
- 📊 **Métricas de engajamento** - Instalações rastreáveis
- 🔒 **Segurança** - HTTPS obrigatório
- 💰 **Redução de custos** - Menos tráfego de rede
- 📱 **Multiplataforma** - Um código, todas as plataformas

---

## 🐛 Troubleshooting

### Botão de instalação não aparece

**Possíveis causas:**
- App já está instalado (verifique: `PWAInstall.isInstalled()`)
- Navegador não suporta PWA
- Conexão não é HTTPS
- Service Worker não registrado

**Solução:**
1. Abra console: Procure por erros
2. Verifique: `PWAInstall.canInstall()`
3. Force exibição: `PWAInstall.show()`

### Service Worker não registra

**Solução:**
1. Verifique console: `[SW] ...` messages
2. DevTools → Application → Service Workers
3. Verifique se `/sw.js` está acessível
4. Limpe cache e recarregue

### Conteúdo não atualiza

**Causa:** Service Worker servindo versão em cache

**Solução:**
1. Incremente `CACHE_VERSION` em `/public/sw.js`
2. Recarregue a página (pode levar até 60s)
3. Ou: DevTools → Application → Clear storage

### iOS não instala

**Lembrete:** iOS usa método diferente!
- Não há botão "Instalar" automático
- Use: Safari → Compartilhar → "Adicionar à Tela de Início"

---

## 🔮 Roadmap Futuro

### Recursos Planejados
- [ ] **Push Notifications** - Alertas de atualizações
- [ ] **Background Sync** - Sincronização em background
- [ ] **Periodic Background Sync** - Atualizações automáticas
- [ ] **Share Target API** - Receber compartilhamentos
- [ ] **Screenshots no Manifest** - Preview na instalação
- [ ] **Ícones adaptáveis** - Melhor suporte Android

---

## 📚 Referências

- [MDN - Progressive Web Apps](https://developer.mozilla.org/en-US/docs/Web/Progressive_web_apps)
- [web.dev - Learn PWA](https://web.dev/learn/pwa/)
- [Google - PWA Checklist](https://web.dev/pwa-checklist/)
- [Can I Use - Service Worker](https://caniuse.com/serviceworkers)

---

## ✅ Checklist de Implementação

- [x] Manifest.json configurado
- [x] Service Worker implementado
- [x] Script de instalação criado
- [x] Ícones configurados
- [x] Meta tags PWA adicionadas
- [x] HTTPS configurado
- [x] Offline fallback
- [x] Cache strategies
- [x] Botão de instalação automático
- [x] Compatibilidade multiplataforma
- [x] Testes em Android, iOS e Desktop
- [x] Documentação completa

🎉 **PWA totalmente funcional!**
