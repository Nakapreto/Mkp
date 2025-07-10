# MultiSite Super Links

Plugin avançado para gerenciamento de links de afiliados em WordPress MultiSite com subdomínios. Uma versão inspirada no popular plugin Super Links brasileiro, otimizada especificamente para redes multisite.

## 🚀 Funcionalidades Principais

### 🔗 Gerenciamento de Links
- **Camuflagem de Links Avançada**: Transforme links feios de afiliados em URLs bonitas usando seu próprio domínio
- **Links Inteligentes**: Adicione automaticamente links em palavras-chave específicas do seu conteúdo
- **Redirecionamento Inteligente**: Detecta bots e crawlers, mostrando páginas diferentes para visitantes reais
- **Múltiplos Tipos de Redirecionamento**: 301, 302, iframe, pixel bridge

### 🍪 Rastreamento Duplo de Cookies
- **Proteção Total**: Sistema duplo de cookies garante que você não perca nenhuma comissão
- **Backup Automático**: Cookie de backup em caso de falha do cookie principal
- **Rastreamento Cross-Device**: Funciona mesmo quando o usuário troca de dispositivo
- **Armazenamento no Banco**: Backup dos dados de cookie no banco de dados

### 📄 Clonagem de Páginas
- **Clone em Segundos**: Copie qualquer página de vendas para seu WordPress com 1 clique
- **Processamento Automático**: URLs, imagens e CSS são automaticamente ajustados
- **Links de Afiliado Automáticos**: Detecta e camufla automaticamente links de afiliados
- **Pixel Integration**: Adiciona automaticamente pixels do Facebook/Google
- **SEO Otimizado**: Meta tags adequadas para páginas clonadas

### 📊 Estatísticas Avançadas
- **Dashboard Completo**: Visualize cliques, conversões e ROI em tempo real
- **Análise Geográfica**: Estatísticas por país e região
- **Análise de Dispositivos**: Mobile, Desktop, Tablet
- **Tracking de Referrers**: Facebook, Google, direto, etc.
- **Exportação CSV**: Exporte dados para análise externa
- **Relatórios de Performance**: Links com melhor e pior performance

### 🧠 Links Inteligentes
- **Automação Total**: Adicione links automaticamente baseado em palavras-chave
- **Regras Condicionais**: Configure quando e onde aplicar os links
- **Limite de Aplicações**: Controle quantas vezes um link aparece
- **Exclusões Inteligentes**: Evite links em categorias ou posts específicos
- **Teste A/B**: Teste diferentes estratégias de linkagem

### 🔄 Redirecionamento de Saída
- **Exit Intent**: Detecta quando visitante vai sair e mostra oferta
- **Back Button Intercept**: Captura tentativa de voltar no navegador
- **Modal Customizável**: Interface bonita para oferecer produtos
- **Múltiplos Triggers**: Tempo, scroll, mouse movement, etc.
- **Configuração Flexível**: Por página ou global

### 🌐 MultiSite Otimizado
- **Subdomain Ready**: Funciona perfeitamente com subdomínios
- **Network Admin**: Painel de administração da rede
- **Estatísticas Globais**: Veja performance de toda a rede
- **Configuração por Site**: Cada site pode ter suas próprias regras
- **Shared Resources**: Compartilhe links entre sites da rede

## 📋 Requisitos

- WordPress 5.0 ou superior
- PHP 7.4 ou superior
- WordPress MultiSite configurado para subdomínios
- MySQL 5.6 ou superior
- mod_rewrite habilitado

## 🛠️ Instalação

1. **Upload do Plugin**
   ```bash
   # Via FTP ou painel do WordPress
   wp-content/plugins/multisite-super-links/
   ```

2. **Ativação na Rede**
   - Acesse Network Admin > Plugins
   - Ative o "MultiSite Super Links"

3. **Configuração Inicial**
   - Configure as opções gerais em Super Links > Configurações
   - Defina o prefixo dos links (padrão: 'msl')
   - Configure integração com Facebook Pixel e Google Analytics

## 🚀 Uso Rápido

### Criar um Link Camuflado

1. Acesse **Super Links > Gerenciar Links**
2. Clique em **"Adicionar Novo Link"**
3. Preencha:
   - **Título**: Nome do link
   - **URL do Afiliado**: Link original
   - **Slug Personalizado** (opcional)
   - **Categoria** (opcional)
4. Configure opções avançadas:
   - ✅ Habilitar camuflagem
   - ✅ Habilitar pixel tracking
   - ✅ Código do pixel
5. **Salvar**

Seu link será: `https://seusite.com/msl/slug-personalizado`

### Clonar uma Página

1. Acesse **Super Links > Clonar Páginas**
2. Insira a URL da página que deseja clonar
3. Configure opções:
   - ✅ Camuflar links de afiliados automaticamente
   - ✅ Abrir links em nova aba
   - ✅ Remover scripts de tracking originais
   - ✅ Adicionar código de tracking próprio
4. Clique **"Clonar Página"**
5. Visualize o resultado e publique

### Configurar Links Inteligentes

1. Acesse **Super Links > Links Inteligentes**
2. Clique **"Nova Regra"**
3. Configure:
   - **Nome da Regra**: "Produtos de Marketing Digital"
   - **Palavras-chave**: "marketing digital, afiliado, vendas online"
   - **URL do Link**: seu link camuflado
   - **Condições**: Posts, páginas específicas, categorias
4. Defina limites:
   - **Max. substituições por conteúdo**: 3
   - **Max. aplicações da regra**: 100
5. **Salvar Regra**

### Configurar Exit Redirect

1. Acesse **Super Links > Configurações**
2. Aba **"Redirecionamento de Saída"**
3. Configure:
   - ✅ Habilitar exit redirect
   - **URL de destino**: página de oferta
   - **Título do modal**: "Espere! Oferta especial!"
   - **Mensagem**: texto convincente
   - **Triggers**: exit intent, back button, tempo
4. **Salvar Configurações**

## 🎯 Funcionalidades Avançadas

### Shortcodes Disponíveis

```php
// Exibir links em grid
[msl_links columns="3" category="ofertas" limit="6"]

// Exibir com estatísticas
[msl_links show_stats="true" category="principais"]

// Apenas uma categoria específica
[msl_links category="hotmart" columns="2"]
```

### Hooks para Desenvolvedores

```php
// Ação disparada quando há conversão
add_action('msl_conversion_tracked', function($link_id, $conversion_data) {
    // Seu código personalizado
    error_log("Conversão no link $link_id: " . print_r($conversion_data, true));
});

// Filtro para modificar URLs de redirecionamento
add_filter('msl_redirect_url', function($url, $link_id) {
    // Adicionar parâmetros personalizados
    return add_query_arg('custom_param', 'value', $url);
}, 10, 2);

// Filtro para excluir conteúdo do processamento de links inteligentes
add_filter('msl_intelligent_links_content', function($content) {
    // Processar ou modificar conteúdo antes dos links inteligentes
    return $content;
});
```

### Funções Helper

```php
// Obter URL camuflada
$cloaked_url = msl_get_cloaked_url($link_id, $site_id);

// Verificar se é multisite com subdomínios
if (msl_is_multisite_subdomain()) {
    // Código específico para subdomínios
}

// Registrar conversão manualmente
MSLTracker.trackConversion({
    value: 97.00,
    currency: 'BRL',
    transaction_id: 'TXN123'
});
```

## 📊 Análise e Relatórios

### Dashboard Principal
- **Visão Geral**: Total de links, cliques, conversões
- **Gráfico de Performance**: Cliques e conversões por dia
- **Top Links**: Links com melhor performance
- **Taxa de Conversão**: Média geral e por período

### Relatórios Detalhados
- **Por Link**: Estatísticas específicas de cada link
- **Por Fonte**: Facebook, Google, direto, etc.
- **Por Dispositivo**: Mobile vs Desktop vs Tablet
- **Por Localização**: Países e regiões
- **Exportação**: CSV para análise externa

### Network Analytics (MultiSite)
- **Visão da Rede**: Estatísticas de todos os sites
- **Ranking de Sites**: Performance por subdomínio
- **Relatório Consolidado**: Dados agregados da rede

## 🔧 Configurações Avançadas

### Configurações de Cookie
```php
// Duração do cookie (dias)
update_option('msl_cookie_duration', 60);

// Habilitar cookie duplo
update_option('msl_enable_double_cookie', true);

// Habilitar redirecionamento inteligente
update_option('msl_enable_intelligent_redirect', true);
```

### Configurações de Pixel
```php
// Facebook Pixel ID
update_option('msl_facebook_pixel_id', 'SEU_PIXEL_ID');

// Google Analytics ID
update_option('msl_google_analytics_id', 'UA-XXXXXXXX-X');
```

### Configurações de Links Inteligentes
```php
// Máximo de links por conteúdo
update_option('msl_max_intelligent_links_per_content', 5);

// Habilitar links inteligentes
update_option('msl_enable_intelligent_links', true);

// Posts excluídos
update_option('msl_intelligent_links_excluded_posts', array(123, 456));
```

## 🎨 Personalização de Estilo

### CSS Customizado
```css
/* Links inteligentes */
.msl-intelligent-link {
    color: #007cba;
    text-decoration: underline;
    font-weight: bold;
}

.msl-intelligent-link:hover {
    color: #005a87;
    text-decoration: none;
}

/* Grid de links (shortcode) */
.msl-links-grid {
    display: grid;
    gap: 20px;
    margin: 20px 0;
}

.msl-columns-2 { grid-template-columns: repeat(2, 1fr); }
.msl-columns-3 { grid-template-columns: repeat(3, 1fr); }
.msl-columns-4 { grid-template-columns: repeat(4, 1fr); }

/* Modal de exit redirect */
#msl-exit-overlay {
    backdrop-filter: blur(5px);
}

/* Página de loading */
.msl-loading-spinner {
    animation: spin 1s linear infinite;
}
```

## 🔒 Segurança

### Proteções Implementadas
- **Nonce Verification**: Todos os AJAX requests verificados
- **Capability Checks**: Verificação de permissões
- **SQL Injection Prevention**: Prepared statements
- **XSS Protection**: Sanitização de dados
- **CSRF Protection**: Tokens de segurança

### Boas Práticas
- Use sempre HTTPS em produção
- Configure firewall para proteger wp-admin
- Mantenha WordPress e plugins atualizados
- Use senhas fortes para usuários administrativos
- Faça backups regulares do banco de dados

## 🚨 Troubleshooting

### Links Não Redirecionam
1. Verifique se mod_rewrite está habilitado
2. Acesse Configurações > Permalinks e salve novamente
3. Verifique se há conflitos com outros plugins
4. Confirme se o slug do link está correto

### Estatísticas Não Aparecem
1. Verifique se JavaScript está habilitado
2. Confirme se não há bloqueadores de anúncios
3. Teste em modo anônimo do navegador
4. Verifique logs de erro do servidor

### Páginas Clonadas Não Carregam
1. Verifique se a URL original está acessível
2. Confirme se não há bloqueio de iframe
3. Teste com diferentes User-Agents
4. Verifique timeouts do servidor

### Performance Lenta
1. Configure cache adequadamente
2. Otimize banco de dados regularmente
3. Limite número de links inteligentes
4. Use CDN para assets estáticos

## 📝 Changelog

### Versão 1.0.0
- ✅ Lançamento inicial
- ✅ Camuflagem de links básica
- ✅ Rastreamento duplo de cookies
- ✅ Clonagem de páginas
- ✅ Links inteligentes
- ✅ Exit redirect
- ✅ Estatísticas avançadas
- ✅ Suporte completo a MultiSite
- ✅ Network admin interface
- ✅ Shortcodes e widgets
- ✅ API para desenvolvedores

## 🆘 Suporte

### Recursos de Ajuda
- **Documentação Completa**: Guias detalhados para cada funcionalidade
- **Vídeo Tutoriais**: Passo a passo em vídeo
- **FAQ**: Perguntas frequentes
- **Fórum da Comunidade**: Discussões e dicas

### Solicitação de Recursos
Tem uma ideia para melhorar o plugin? Abra uma issue ou entre em contato!

### Relatório de Bugs
Encontrou um problema? Por favor, inclua:
- Versão do WordPress
- Versão do plugin
- Logs de erro
- Passos para reproduzir

## 📄 Licença

Este plugin é distribuído sob a licença GPL v2 ou posterior.

## 🏆 Créditos

Inspirado no plugin Super Links brasileiro de Fábio Vasconcelos, adaptado e otimizado para WordPress MultiSite com funcionalidades avançadas.

---

**Desenvolvido para maximizar suas conversões e proteger suas comissões de afiliado! 🚀**