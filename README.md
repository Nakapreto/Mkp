# Site Cloner - Plugin WordPress

Um plugin WordPress avançado para clonar sites externos com suporte completo ao Elementor e WordPress Multisite.

## 🚀 Características

- ✅ Clone completo de sites externos
- ✅ Detecção automática de URLs de redirecionamento
- ✅ Download automático de todos os assets (imagens, vídeos, CSS, fontes)
- ✅ Suporte inteligente para vídeos (YouTube/Vimeo mantidos como embeds, outros baixados)
- ✅ Conversão automática de links internos
- ✅ Detecção e suporte completo ao Elementor/Elementor Pro
- ✅ Páginas clonadas editáveis no Elementor
- ✅ Importação/exportação via arquivos ZIP
- ✅ Interface administrativa intuitiva
- ✅ Acompanhamento de progresso em tempo real
- ✅ Sistema de logs detalhado
- ✅ Suporte ao WordPress Multisite
- ✅ Assets salvos na biblioteca de mídia

## 📋 Requisitos

- WordPress 5.0+
- PHP 7.4+
- Extensões PHP: `curl`, `zip`, `dom`, `libxml`
- Memória: 512MB+ recomendado
- Elementor (opcional, para funcionalidades avançadas)

## 📦 Instalação

### Método 1: Upload Manual

1. Baixe o arquivo ZIP do plugin
2. Acesse `WordPress Admin > Plugins > Adicionar Novo > Enviar Plugin`
3. Selecione o arquivo ZIP e clique em "Instalar Agora"
4. Ative o plugin

### Método 2: FTP

1. Extraia o arquivo ZIP
2. Envie a pasta `site-cloner` para `/wp-content/plugins/`
3. Acesse `WordPress Admin > Plugins` e ative o "Site Cloner"

## ⚙️ Configuração

### Configurações Básicas

1. Acesse `WordPress Admin > Site Cloner > Configurações`
2. Configure os parâmetros conforme necessário:
   - **Tempo Máximo de Execução**: Tempo limite para o processo de clone
   - **Limite de Memória**: Memória disponível para o processo
   - **Timeout de Download**: Tempo limite para download de cada arquivo
   - **Tamanho Máximo de Arquivo**: Limite de tamanho para arquivos individuais

### Configurações Avançadas

- **Suporte ao Elementor**: Ative para detectar e converter sites Elementor
- **Suporte ao Multisite**: Ative para funcionalidades de rede

## 🎯 Como Usar

### 1. Clonando um Site

1. Acesse `Site Cloner > Clone Site`
2. Digite a URL do site que deseja clonar
3. Configure as opções:
   - **Título da Página**: Nome da página no WordPress (opcional)
   - **Status da Página**: Rascunho ou Publicar
   - **Assets para Baixar**: Selecione quais tipos baixar
   - **Suporte Elementor**: Ative se o site usar Elementor
4. Clique em "Iniciar Clone"
5. Acompanhe o progresso na aba "Status"

### 2. Importando um ZIP

1. Acesse `Site Cloner > Import ZIP`
2. Selecione o arquivo ZIP exportado pelo plugin
3. Digite o título da página
4. Escolha o status (rascunho ou publicar)
5. Clique em "Importar ZIP"

### 3. Acompanhando o Status

1. Acesse `Site Cloner > Status`
2. Visualize todos os jobs de clonagem
3. Clique em "Ver Log" para detalhes
4. Acompanhe o progresso em tempo real

## 🔧 Funcionalidades Detalhadas

### Processamento de Assets

#### Imagens
- Download automático de todas as imagens
- Suporte a `srcset` para imagens responsivas
- Conversão de URLs relativas para absolutas
- Salvamento na biblioteca de mídia do WordPress

#### Vídeos
- **YouTube/Vimeo**: Mantidos como embeds originais
- **Vídeos hospedados**: Baixados para o servidor
- Suporte a elementos `<video>` e `<source>`
- Detecção automática de plataformas de vídeo

#### CSS e Fontes
- Download de arquivos CSS externos
- Processamento de `@font-face` e URLs dentro do CSS
- Google Fonts mantidas como links externos
- Outras fontes baixadas localmente

#### JavaScript (Opcional)
- Skip automático de bibliotecas comuns (jQuery, Google Analytics)
- Download opcional de scripts personalizados

### Integração com Elementor

#### Detecção Automática
- Identifica sites construídos com Elementor
- Reconhece classes e atributos específicos
- Verifica arquivos CSS/JS do Elementor

#### Conversão Inteligente
- Extração de dados do Elementor do HTML
- Conversão de HTML para estrutura Elementor
- Configuração automática de meta fields
- Geração de CSS específico da página

#### Edição no Elementor
- Páginas clonadas são editáveis no Elementor
- Preservação da estrutura original
- Compatibilidade com Elementor Pro

### Sistema de Links

#### Conversão Automática
- URLs relativas convertidas para absolutas
- Links internos redirecionados para WordPress
- Preservação de links externos
- Mapeamento inteligente de URLs

#### Rastreamento de Redirecionamentos
- Seguimento automático de redirecionamentos 301/302
- Resolução da URL final antes do clone
- Suporte a múltiplos redirecionamentos
- Log detalhado do processo

## 📊 Interface Administrativa

### Dashboard Principal
- Formulário de clonagem intuitivo
- Opções de configuração avançadas
- Informações importantes e dicas
- Validação em tempo real

### Página de Status
- Lista de todos os jobs
- Indicadores visuais de progresso
- Logs detalhados por job
- Opções de cancelamento

### Página de Importação
- Upload de arquivos ZIP
- Configuração de importação
- Validação de arquivos
- Feedback em tempo real

### Configurações
- Parâmetros de performance
- Limites de arquivo e tempo
- Opções de compatibilidade
- Configurações de rede (Multisite)

## 🌐 Suporte ao Multisite

### Funcionalidades de Rede
- Configurações centralizadas para toda a rede
- Assets compartilhados entre sites
- Administração por super admins
- Políticas de segurança unificadas

### Por Site
- Configurações específicas por site
- Biblioteca de mídia individual
- Usuários com permissões específicas
- Logs separados por site

## 🔒 Segurança

### Validação de Arquivos
- Verificação de tipos MIME
- Validação de assinaturas de arquivo
- Limites de tamanho configuráveis
- Sanitização de nomes de arquivo

### Permissões
- Verificação de capacidades do usuário
- Nonces para todas as operações AJAX
- Validação de URLs de origem
- Logs de auditoria

### Limitações
- Rate limiting para downloads
- Timeouts configuráveis
- Validação de domínios (opcional)
- Blacklist de extensões

## 🚨 Resolução de Problemas

### Erros Comuns

#### "Tempo limite excedido"
- Aumente o `max_execution_time` nas configurações
- Verifique a configuração do servidor
- Considere clonar sites menores primeiro

#### "Memória insuficiente"
- Aumente o `memory_limit` nas configurações
- Verifique os recursos do servidor
- Reduza o número de assets simultaneamente

#### "Erro ao baixar assets"
- Verifique a conectividade com o site
- Teste a URL manualmente
- Verifique se o site bloqueia bots

#### "Elementor não detectado"
- Verifique se o Elementor está instalado
- Confirme se o site realmente usa Elementor
- Ative o suporte manual nas configurações

### Logs e Debug

#### Ativar Debug
```php
// wp-config.php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

#### Localização dos Logs
- WordPress: `/wp-content/debug.log`
- Plugin: Admin > Site Cloner > Status > Ver Log

#### Informações Úteis
- URL sendo clonada
- Assets sendo processados
- Erros específicos
- Tempo de execução

## 🔄 Backup e Manutenção

### Backup Automático
- Arquivos ZIP gerados automaticamente
- Salvos em `/wp-content/uploads/site-cloner/exports/`
- Inclui conteúdo HTML e metadados
- Assets organizados por tipo

### Limpeza Automática
- Remoção de assets antigos (configurável)
- Limpeza de arquivos temporários
- Otimização de banco de dados
- Logs rotativos

### Manutenção Manual
- Acesse `Site Cloner > Configurações`
- Configure limpeza automática
- Execute limpeza manual quando necessário
- Monitore uso de espaço em disco

## 🤝 Contribuição

### Estrutura do Código
```
site-cloner/
├── site-cloner.php              # Arquivo principal
├── includes/                    # Classes PHP
│   ├── class-site-cloner.php
│   ├── class-site-cloner-admin.php
│   ├── class-site-cloner-processor.php
│   ├── class-site-cloner-assets.php
│   ├── class-site-cloner-elementor.php
│   ├── class-site-cloner-media.php
│   └── class-site-cloner-ajax.php
├── assets/                      # CSS e JavaScript
│   ├── css/admin.css
│   └── js/admin.js
└── README.md
```

### Padrões de Código
- PSR-4 para autoloading
- WordPress Coding Standards
- Documentação PHPDoc completa
- Prefixos únicos para funções

### Como Contribuir
1. Faça um fork do repositório
2. Crie uma branch para sua feature
3. Implemente as mudanças
4. Teste extensivamente
5. Envie um pull request

## 📝 Changelog

### Versão 1.0.0
- ✅ Lançamento inicial
- ✅ Clonagem básica de sites
- ✅ Suporte ao Elementor
- ✅ Interface administrativa
- ✅ Sistema de assets
- ✅ Importação/exportação ZIP
- ✅ Suporte ao Multisite

## 📄 Licença

Este plugin é licenciado sob a GPL v2 ou posterior.

## 📞 Suporte

Para suporte técnico, abra uma issue no repositório ou entre em contato com a equipe de desenvolvimento.

---

**Desenvolvido com ❤️ para a comunidade WordPress**