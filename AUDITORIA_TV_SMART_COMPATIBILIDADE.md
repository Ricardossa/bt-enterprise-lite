# 🔍 AUDITORIA: TV SMART COMPATIBILITY ISSUES v2.7.0
**Data:** 2026-09-01  
**Arquivo:** `/public/tv_smart.php` + `assets/css/tv_smart.css` + `assets/js/tv_smart.js`  
**Status:** ⚠️ PROBLEMAS ENCONTRADOS COM LG E FIRE TV

---

## 📊 RESUMO DOS PROBLEMAS

| Problema | Impacto | TVs Afetadas | Severidade |
|----------|---------|--------------|-----------|
| Falta viewport meta tag | Escala incorreta em todos os navegadores | LG, Fire TV | 🔴 CRÍTICO |
| Uso de `100vw` causa scrollbar | Layout desalinhado | LG, Fire TV | 🔴 CRÍTICO |
| Grid CSS `75% 25%` sem fallback | Layout quebrado em navegadores antigos | LG TV (Webos antigo) | 🔴 CRÍTICO |
| Font `Segoe UI` indisponível em Linux | Fontes feias/substituição pior | LG, Fire TV (Android) | 🟡 ALTO |
| Sem cache de imagens | Carregamento lento em redes fracas | LG, Fire TV | 🟡 ALTO |
| `speechSynthesis` não funciona bem em LG | Voz saindo errada | LG TV | 🟡 ALTO |
| Sem limite máximo de zoom | Elementos saem da tela | LG TV com zoom browser | 🟡 ALTO |
| Espaçamento em `px` vs `rem` inconsistente | Responsividade quebrada | Todas as TVs | 🟡 MÉDIO |

---

## 🔴 PROBLEMA #1: Falta Viewport Meta Tag
**Arquivo:** `tv_smart.php` - Linha 9  
**Problema:**
```html
<!-- ❌ FALTA ISSO: -->
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
```

**Consequência:**
- LG TV: Renderiza com escala padrão do navegador (difícil ler)
- Fire TV: Aplica zoom automático confuso
- Samsung: Funciona OK por coincidência

**Solução:**
```html
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="mobile-web-app-capable" content="yes">
```

---

## 🔴 PROBLEMA #2: Uso de `100vw` Causa Scrollbar Horizontal
**Arquivo:** `tv_smart.css` - Linhas 20, 42  
**Problema:**
```css
body {
    width: 100vw;  /* ❌ CAUSA SCROLLBAR EM ALGUNS NAVEGADORES */
    overflow: hidden;
}

.tv-layout-smart {
    width: 100vw;  /* ❌ MESMA COISA */
}
```

**Consequência:**
- Fire TV: Scrollbar aparece e desalinha layout
- LG TV: Elemento vai além da tela em ~17px
- Chrome/Samsung: Funciona por ser mais tolerante

**Solução:**
```css
body {
    width: 100%;
    max-width: 100%;
    overflow: hidden;
}

.tv-layout-smart {
    width: 100%;
    max-width: 100%;
}
```

---

## 🔴 PROBLEMA #3: Grid CSS 75% 25% Sem Fallback para Navegadores Antigos
**Arquivo:** `tv_smart.css` - Linha 41  
**Problema:**
```css
.tv-layout-smart {
    display: grid;  /* ❌ NÃO FUNCIONA EM NAVEGADORES ANTIGOS */
    grid-template-columns: 75% 25%;
}
```

**Navegadores afetados:**
- LG WebOS 3.0-4.0 (2016-2017): Suporte ruim a CSS Grid
- Fire TV Stick Gen 1: Sem suporte
- Firefox ESR antigo

**Solução:**
```css
.tv-layout-smart {
    display: flex;  /* Fallback com Flexbox */
    flex-direction: row;
}

.smart-carousel-container {
    flex: 3;  /* 75% */
}

.smart-sidebar-history {
    flex: 1;  /* 25% */
    border-left: 2px solid var(--border-smart);
}
```

---

## 🟡 PROBLEMA #4: Font `Segoe UI` Não Disponível em Linux/Android
**Arquivo:** `tv_smart.css` - Linha 14  
**Problema:**
```css
font-family: 'Segoe UI', system-ui, sans-serif;
/* ❌ Segoe UI NÃO VÊMO EM LG TV (LINUX) E FIRE TV (ANDROID) */
```

**Consequência:**
- Fallback para `system-ui` que em LG/Fire TV é Arial feio
- Texto fica pior que Samsung que tem boas fontes

**Solução:**
```css
font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
/* Adicionado Roboto que é UNIVERSAL EM ANDROID */
```

---

## 🟡 PROBLEMA #5: Sem Otimização de Imagens
**Arquivo:** `tv_smart.js` - Linhas 111-117  
**Problema:**
```javascript
imgHtml = `<img src="${imgUrl}" class="promo-image-smart">`;
/* ❌ SEM CACHE, SEM COMPRESSÃO, SEM FORMATOS MODERNOS */
```

**Impacto:**
- Imagens grandes pesam até 2-5MB
- Em redes lentas (TV residencial), demora carregar
- Sem lazy-loading

**Solução:**
```javascript
// Adicionar atributos de otimização
imgHtml = `
    <img 
        src="${imgUrl}" 
        loading="lazy"
        decoding="async"
        alt="Promoção"
        style="max-height: 300px; width: auto; object-fit: contain;"
        class="promo-image-smart"
    >
`;
```

E no CSS:
```css
.promo-image-smart {
    max-height: 300px;
    width: auto;
    max-width: 90%;
    object-fit: contain;
    will-change: transform;  /* Otimização GPU */
}
```

---

## 🟡 PROBLEMA #6: speechSynthesis Não Funciona Bem em LG TV
**Arquivo:** `tv_smart.js` - Linhas 257-269  
**Problema:**
```javascript
if ('speechSynthesis' in window) {
    const msg = new SpeechSynthesisUtterance(texto);
    msg.lang = 'pt-BR';
    /* ❌ LG TV TEM SINTETIZADOR DE VOZ RUIM
       ❌ VOZ DISTORCIDA OU NÃO FUNCIONA
       ❌ FALLBACK NUNCA É EXECUTADO PORQUE THROW NÃO ACONTECE
    */
    window.speechSynthesis.speak(msg);
}
```

**Consequência:**
- LG TV: Voz estranha ou não funciona
- Fire TV: Às vezes funciona, às vezes não
- Usuário fica esperando mensagem que não chega

**Solução:**
```javascript
anunciarAlexa(c) {
    const header = document.querySelector('.tv-header-smart');
    const audioDing = new Audio('assets/audio/alert.mp3');

    audioDing.play().catch(() => {});
    if (header) header.classList.add('alexa-active');

    setTimeout(async () => {
        const nome = c.nome_cliente ? c.nome_cliente : "Cliente";
        const local = c.guiche;
        const atendente = c.barbeiro;
        
        let texto = local.toUpperCase() === atendente.toUpperCase() 
            ? `${nome}, dirija-se ao ${local}.`
            : `${nome}, dirija-se ao ${local} com ${atendente}.`;

        // CANAL 1: APK Nativo (Existe?)
        if (typeof AndroidVoz !== 'undefined') {
            try {
                AndroidVoz.cancelar();
                AndroidVoz.falar(texto);
                setTimeout(() => { if (header) header.classList.remove('alexa-active'); }, 5000);
                return;
            } catch (e) { console.warn("Canal 1 falhou"); }
        }

        // CANAL 2: Google Translate TTS (Mais confiável)
        try {
            const ttsUrl = `https://translate.google.com/translate_tts?ie=UTF-8&q=${encodeURIComponent(texto)}&tl=pt-BR&client=tw-ob`;
            const audioVoz = new Audio(ttsUrl);
            
            audioVoz.onended = () => {
                if (header) header.classList.remove('alexa-active');
            };
            
            audioVoz.onerror = () => {
                console.warn("Google TTS falhou, tentando fallback");
                tentarSpeechSynthesis();
            };
            
            await audioVoz.play();
            return;
        } catch (e) {
            console.warn("Erro ao tentar Google TTS:", e);
        }

        // CANAL 3: Web Speech API (Último recurso, pode ser horrível)
        function tentarSpeechSynthesis() {
            if ('speechSynthesis' in window) {
                try {
                    window.speechSynthesis.cancel(); // Cancela tudo antes
                    const msg = new SpeechSynthesisUtterance(texto);
                    msg.lang = 'pt-BR';
                    msg.rate = 0.9;  // Mais lento
                    msg.pitch = 1.0;
                    
                    msg.onend = () => {
                        if (header) header.classList.remove('alexa-active');
                    };
                    
                    msg.onerror = (e) => {
                        console.error("Speech synthesis falhou:", e);
                        if (header) header.classList.remove('alexa-active');
                    };
                    
                    window.speechSynthesis.speak(msg);
                } catch (e) {
                    console.error("Erro crítico em speechSynthesis:", e);
                    if (header) header.classList.remove('alexa-active');
                }
            } else {
                console.warn("speechSynthesis não disponível");
                if (header) header.classList.remove('alexa-active');
            }
        }

        if (!('speechSynthesis' in window)) {
            if (header) header.classList.remove('alexa-active');
        }
    }, 800);
},
```

---

## 🟡 PROBLEMA #7: Sem Limite de Zoom / Suporte a Navegadores com Zoom
**Arquivo:** `tv_smart.css` - Linha 14 (viewport)  
**Problema:**
```html
<!-- ❌ FALTA ISSO: -->
<!-- viewport-fit=cover NÃO TEM -->
<!-- BROWSER PODE FAZER ZOOM ACIDENTALMENTE -->
```

**Consequência:**
- Usuário pressiona botão de zoom no controle remoto da LG
- Layout sai da tela completamente

**Solução:**
```html
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">

<!-- Adicionar em CSS para garantir: -->
body { touch-action: none; }  /* Desabilita zoom via pinch */
```

---

## 🟡 PROBLEMA #8: Espaçamento Inconsistente (px vs rem)
**Arquivo:** `tv_smart.css` - Vários lugares  
**Problema:**
```css
.tv-header-smart {
    height: 100px;  /* ❌ PX FIXO */
    padding: 0 40px;
}

.slide-title-smart {
    font-size: 40px;  /* ❌ PX FIXO */
    margin-bottom: 30px;
}
```

**Consequência:**
- Em resolução 1080p: OK
- Em resolução 4K: Elementos parecem minúsculos
- Em resolução 720p: Elementos parecem gigantes

**Solução:**
```css
/* Definir base REM responsiva */
html {
    font-size: 16px;  /* Base 16px em 1080p */
}

/* Em 4K (3840x2160), usar @media */
@media (min-width: 3000px) {
    html { font-size: 32px; }
}

/* Em 720p */
@media (max-height: 760px) {
    html { font-size: 12px; }
}

/* Usar REM em vez de PX */
.tv-header-smart {
    height: 6.25rem;  /* 100px em 16px base */
    padding: 0 2.5rem;  /* 40px em 16px base */
}

.slide-title-smart {
    font-size: 2.5rem;  /* 40px em 16px base */
    margin-bottom: 1.875rem;  /* 30px em 16px base */
}
```

---

## ✅ RECOMENDAÇÕES FINAIS

### Prioridade 1 (FAÇA AGORA):
1. ✅ Adicionar viewport meta tag completo
2. ✅ Trocar `100vw` por `100%`
3. ✅ Adicionar fallback Flexbox para Grid CSS
4. ✅ Melhorar fallback de fonts

### Prioridade 2 (PRÓXIMA SPRINT):
5. ✅ Otimizar imagens (lazy-load, compressão)
6. ✅ Melhorar lógica de voz (TTS mais confiável)
7. ✅ Adicionar limite de zoom via viewport-fit

### Prioridade 3 (FUTURO):
8. ✅ Refatorar espaçamentos para REM responsivo
9. ✅ Testar em diversos browsers/TVs
10. ✅ Adicionar Service Worker para cache offline

---

## 🧪 COMO TESTAR

### Em LG TV:
1. Abrir Firefox (Webos 5+) ou Chrome (Webos 6+)
2. Acessar https://paradaobrigatoriavilas.brandaotech.com.br/tv_smart.php
3. Medir scrollbar horizontal (NÃO DEVE EXISTIR)
4. Testar zoom via controle remoto (NÃO DEVE FUNCIONAR)
5. Verificar se fonts estão OK

### Em Fire TV:
1. Usar Silk Browser
2. Acessar URL
3. Verificar responsividade
4. Testar voz (pode não funcionar bem)

### Em Samsung:
1. Browser padrão
2. Verificar se tudo funciona (benchmark)

---

**Gerado em:** 2026-09-01  
**Próximo Passo:** Implementar correções de Prioridade 1
