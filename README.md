# Super Links Clone - Plugin WordPress

Um plugin WordPress completo para clonagem de páginas, gerenciamento de links de afiliados, camuflagem de links, rastreamento avançado e muito mais. Clone do plugin Super Links original.

## 🚀 Funcionalidades Principais

### 📄 Clonagem de Páginas
- **Clonagem Completa**: Clone qualquer página da internet em segundos
- **Download de Assets**: Faz download automático de imagens, CSS, JS e outros assets
- **Processamento Inteligente**: Remove scripts desnecessários e corrige links relativos
- **Integração WordPress**: Páginas clonadas são salvas como posts/páginas do WordPress
- **Seguir Redirects**: Segue redirects automaticamente para obter a URL final

### 🔗 Gerenciamento de Links
- **Links Encurtados**: Crie links curtos usando seu próprio domínio
- **Camuflagem de Links**: Exiba o conteúdo da página de destino no seu domínio
- **Categorização**: Organize seus links por categorias
- **Tipos de Redirect**: Suporte para redirects 301, 302 e 307
- **Status de Links**: Controle ativo/inativo para cada link

### 🎯 Rastreamento Avançado
- **Ativação Dupla de Cookies**: Proteja suas comissões de afiliados
- **Analytics Detalhados**: Rastreie cliques, visitantes únicos, países, dispositivos
- **Relatórios Visuais**: Gráficos e estatísticas detalhadas
- **Exportação**: Exporte dados de analytics em CSV
- **Detecção de Dispositivos**: Identifica desktop, mobile, tablet

### 🤖 Clocker do Facebook
- **Proteção Ads**: Protege links contra detecção do Facebook Ads
- **Detecção de Bots**: Identifica bots do Facebook automaticamente
- **Páginas Específicas**: Serve conteúdo diferente para crawlers
- **Meta Tags**: Configuração automática de meta tags Open Graph

### 🧠 Links Inteligentes
- **Substituição Automática**: Substitui palavras-chave por links automaticamente
- **Configuração Flexível**: Configure palavras-chave para cada link
- **Processamento de Conteúdo**: Funciona em posts, páginas e widgets
- **Detecção Inteligente**: Evita substituições dentro de links existentes

### 📤 Redirecionamento de Saída
- **Exit Intent**: Detecta quando usuário está saindo da página
- **Popup de Saída**: Exibe popup ou redireciona para nova página
- **Configuração Flexível**: Configure delay e conteúdo do popup
- **Controle de Frequência**: Evita exibir popup repetidamente

### 📊 Importação de Links
- **Pretty Links**: Importe links do plugin Pretty Links
- **Thirsty Affiliates**: Importe links do plugin Thirsty Affiliates
- **CSV**: Importação via arquivo CSV
- **Migração Fácil**: Migre de outros plugins sem perder dados

## 🛠 Instalação

### Requisitos
- WordPress 5.0 ou superior
- PHP 7.4 ou superior
- MySQL 5.6 ou superior
- cURL habilitado
- Permissões de escrita na pasta de uploads

### Instalação Manual
1. Faça download do plugin
2. Extraia os arquivos na pasta `/wp-content/plugins/super-links-clone/`
3. Ative o plugin no painel administrativo do WordPress
4. Configure as opções em **Super Links > Configurações**

### Via WordPress Admin
1. Acesse **Plugins > Adicionar Novo**
2. Faça upload do arquivo ZIP do plugin
3. Ative o plugin
4. Configure as opções necessárias

## ⚙️ Configuração

### Configurações Básicas
1. **Prefixo de Links**: Defina o prefixo para seus links curtos (padrão: "go")
2. **Rastreamento**: Configure analytics e rastreamento de cookies
3. **Links Inteligentes**: Ative/desative substituição automática
4. **Redirect de Saída**: Configure comportamento de exit intent

### Configurações Avançadas
- **Facebook Clocker**: Configure proteção para Facebook Ads
- **Popup Manager**: Configure popups de conversão
- **Remoção de Dados**: Configure se dados devem ser removidos na desinstalação

## 📚 Como Usar

### Criando um Link
1. Vá para **Super Links > Criar Link**
2. Preencha o título e URL de destino
3. Configure opções avançadas (camuflagem, cookies, etc.)
4. Salve o link

### Clonando uma Página
1. Acesse **Super Links > Clonar Página**
2. Insira a URL da página que deseja clonar
3. Configure opções de clonagem
4. Execute a clonagem

### Configurando Links Inteligentes
1. Marque a opção "Link Inteligente" ao criar um link
2. Adicione palavras-chave separadas por vírgula
3. As palavras-chave serão automaticamente linkadas no conteúdo

### Visualizando Analytics
1. Acesse **Super Links > Analytics**
2. Visualize estatísticas gerais ou específicas de um link
3. Exporte dados em CSV se necessário

## � API e Hooks

### Actions Disponíveis
```php
// Executado quando um link é clicado
do_action('slc_link_clicked', $link_id, $user_ip, $user_agent);

// Executado quando uma página é clonada
do_action('slc_page_cloned', $page_id, $original_url, $post_id);

// Executado quando cookies de afiliado são detectados
do_action('slc_affiliate_cookie_detected', $affiliate_link_id);
```

### Filters Disponíveis
```php
// Filtrar conteúdo antes da clonagem
add_filter('slc_before_clone_content', function($content, $url) {
    // Modifique o conteúdo aqui
    return $content;
}, 10, 2);

// Filtrar links inteligentes
add_filter('slc_smart_links_keywords', function($keywords, $link_id) {
    // Modifique palavras-chave aqui
    return $keywords;
}, 10, 2);
```

### Funções Úteis
```php
// Obter link por slug
$link = slc_get_link_by_slug('meu-link');

// Obter analytics de um link
$analytics = slc_get_link_analytics($link_id);

// Criar link programaticamente
$link_id = slc_create_link([
    'title' => 'Meu Link',
    'slug' => 'meu-link',
    'target_url' => 'https://exemplo.com',
    'cloaked' => true
]);
```

## 📋 Estrutura do Banco de Dados

### Tabela: wp_slc_links
Armazena informações dos links criados.

### Tabela: wp_slc_analytics
Armazena dados de analytics e cliques.

### Tabela: wp_slc_cloned_pages
Armazena informações das páginas clonadas.

## �️ Segurança

- Sanitização de todos os inputs
- Verificação de nonces em formulários
- Escape de outputs
- Validação de permissões de usuário
- Proteção contra SQL Injection
- Verificação de capacidades do usuário

## 🔍 Debugging

### Logs
O plugin registra eventos importantes no log de erros do WordPress:
- Clonagem de páginas
- Criação de links
- Erros de conexão
- Eventos de analytics

### Modo Debug
Ative WP_DEBUG para ver logs detalhados:
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

## ⚡ Performance

### Otimizações
- Cache de queries do banco de dados
- Lazy loading de assets
- Compressão de arquivos CSS/JS
- Otimização de imagens clonadas

### Recomendações
- Use cache de objeto para melhor performance
- Configure CDN para assets estáticos
- Otimize banco de dados regularmente
- Monitore uso de memória

## 🌍 Internacionalização

O plugin está preparado para tradução:
- Domain: `super-links-clone`
- Arquivos POT incluídos
- Suporte para RTL
- Tradução de datas e números

## 📞 Suporte

### Problemas Comuns

**1. cURL Error 35**
- Verifique configurações SSL
- Teste conectividade do servidor
- Configure timeout adequado

**2. Páginas não clonando**
- Verifique permissões de arquivo
- Teste manualmente a URL
- Verifique logs de erro

**3. Links não redirecionando**
- Flush rewrite rules
- Verifique configuração do .htaccess
- Teste sem cache

### Debug Mode
```php
// Adicione ao wp-config.php
define('SLC_DEBUG', true);
```

## � Changelog

### v1.0.0
- Lançamento inicial
- Clonagem de páginas
- Gerenciamento de links
- Analytics básicos
- Camuflagem de links
- Links inteligentes

## 📄 Licença

Este plugin é licenciado sob a GPL v2 ou posterior.

## 🤝 Contribuindo

1. Fork o projeto
2. Crie uma branch para sua feature
3. Commit suas mudanças
4. Push para a branch
5. Abra um Pull Request

## 📱 Contato

Para suporte e dúvidas:
- GitHub Issues
- WordPress Support Forums
- Email de suporte

---

**Desenvolvido com ❤️ para a comunidade WordPress**