# 🚀 Instalação Rápida - Site Cloner Plugin v1.1

## 📥 Download
O arquivo `site-cloner-plugin-v1.1.zip` está pronto para instalação no WordPress.

## 🔧 Instalação

### Passo 1: Upload do Plugin
1. Acesse seu WordPress Admin
2. Vá para **Plugins > Adicionar Novo**
3. Clique em **Enviar Plugin**
4. Selecione o arquivo `site-cloner-plugin-v1.1.zip`
5. Clique em **Instalar Agora**
6. Clique em **Ativar Plugin**

### Passo 2: Configuração Inicial

#### 🌐 Para WordPress Multisite (Super Admin):
1. Acesse **Meus Sites > Administração da Rede**
2. Vá para **Site Cloner > Configurações**
3. Configure as configurações de rede centralizadas

#### 🏠 Para Sites Individuais:
1. Acesse **Site Cloner** no menu lateral
2. Vá para **Configurações** (disponível apenas para super admins no multisite)
3. Ajuste os parâmetros conforme necessário:
   - Tempo máximo: 300 segundos (padrão)
   - Memória: 512M (padrão)
   - Timeout: 60 segundos (padrão)
   - **SSL Verify**: Desabilitado (padrão - para melhor compatibilidade)

## ⚡ Uso Imediato

### Clonar um Site
1. Acesse **Site Cloner > Clone Site**
2. Digite a URL (ex: `https://exemplo.com`)
3. Configure as opções desejadas
4. Clique em **Iniciar Clone**
5. Acompanhe o progresso em **Status**

### Importar ZIP
1. Acesse **Site Cloner > Import ZIP**
2. Selecione o arquivo ZIP
3. Digite o título da página
4. Escolha: Rascunho ou Publicar
5. Clique em **Importar ZIP**

## 🆕 Novidades da v1.1

### ✅ **Correções de Interface**
- ❌ **Corrigido**: Plugin aparecendo em duplicidade
- ❌ **Corrigido**: Configurações agora restritas apenas a super admins no multisite
- ✅ **Novo**: Menu específico para administração de rede
- ✅ **Novo**: Configurações centralizadas para toda a rede

### ✅ **Melhorias de Conectividade**
- ❌ **Corrigido**: Erro cURL 35 (Connection reset by peer)
- ✅ **Novo**: Configurações SSL flexíveis
- ✅ **Novo**: Sistema de retry com backoff exponencial
- ✅ **Novo**: Headers HTTP aprimorados
- ✅ **Novo**: Melhor detecção de redirecionamentos

### ✅ **Configurações Avançadas**
- ✅ **Novo**: User Agent configurável
- ✅ **Novo**: Opções de verificação SSL
- ✅ **Novo**: Controle de redirecionamentos
- ✅ **Novo**: Logs detalhados de conexão

## 🎯 Funcionalidades Principais

✅ **Clone Completo**: HTML, CSS, imagens, vídeos
✅ **Elementor**: Detecção e conversão automática
✅ **Assets**: Salvos na biblioteca de mídia
✅ **Redirecionamentos**: Rastreamento automático
✅ **Progresso**: Acompanhamento em tempo real
✅ **Logs**: Sistema detalhado de depuração
✅ **ZIP**: Exportação/importação de clones
✅ **Multisite**: Suporte completo com configurações de rede
✅ **SSL**: Configurações flexíveis para sites com certificados inválidos

## 🔍 Requisitos Mínimos

- WordPress 5.0+
- PHP 7.4+
- Memória: 512MB+
- Extensões: curl, zip, dom

## 🆘 Soluções para Problemas Comuns

### ❌ "Connection reset by peer" ou erro cURL 35?
**✅ CORRIGIDO na v1.1!**
- O plugin agora tenta múltiplas configurações SSL
- Retry automático com configurações diferentes
- Logs detalhados para diagnóstico

### ❌ Plugin aparece em duplicidade?
**✅ CORRIGIDO na v1.1!**
- Menus separados para admin regular e rede
- Interface específica para cada contexto

### ❌ Não consigo acessar configurações no multisite?
**✅ CORRIGIDO na v1.1!**
- Configurações agora disponíveis apenas para super admins
- Menu específico na administração de rede

### ⚠️ Problema com Tempo Limite?
- Aumente o tempo em **Configurações**
- Verifique recursos do servidor

### ⚠️ Elementor não Detectado?
- Instale o plugin Elementor
- Ative o suporte nas configurações

### ⚠️ Assets não Baixam?
- Desabilite verificação SSL nas configurações
- Teste a URL manualmente
- Verifique logs detalhados

## 🌐 Para Administradores de Rede

### Configurações Centralizadas
- Acesse **Administração da Rede > Site Cloner**
- Configure limites para toda a rede
- Controle permissões de sites individuais

### Monitoramento
- Visualize jobs de todos os sites
- Acompanhe estatísticas da rede
- Logs centralizados

## 📱 Contato

Para suporte técnico ou dúvidas, consulte o arquivo `README.md` para documentação completa.

---
**Plugin v1.1 pronto para uso! 🎉**

**Principais melhorias**: Conectividade aprimorada, interface corrigida, suporte multisite completo