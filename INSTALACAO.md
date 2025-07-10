# 📦 Instruções de Instalação - Super Links Multisite

## 🎯 O que foi modificado

Este é o plugin **Super Links Light** original que foi **completamente modificado** para:

- ✅ **Remover sistema de ativação/licenciamento**
- ✅ **Adicionar suporte completo ao WordPress Multisite**
- ✅ **Otimizar para subdomínios**
- ✅ **Remover verificações de servidor externo**

## 📋 Pré-requisitos

- WordPress 5.0 ou superior
- PHP 7.4 ou superior  
- WordPress Multisite configurado com subdomínios (recomendado)

## 🚀 Instalação Rápida

### 1. Download
Baixe o arquivo `super-links-multisite.zip` que foi gerado.

### 2. Instalação via WordPress Admin

#### Para WordPress Normal:
1. Acesse **Plugins > Adicionar Novo**
2. Clique em **"Enviar Plugin"**
3. Selecione o arquivo `super-links-multisite.zip`
4. Clique em **"Instalar Agora"**
5. Ative o plugin

#### Para WordPress Multisite:
1. Acesse **Rede de Sites > Plugins**
2. Clique em **"Adicionar Novo"**
3. Clique em **"Enviar Plugin"**
4. Selecione o arquivo `super-links-multisite.zip`
5. Clique em **"Instalar Agora"**
6. **Ativar em Rede** (recomendado)

### 3. Instalação via FTP

Se preferir instalar via FTP:

```bash
# Extrair o arquivo
unzip super-links-multisite.zip

# Enviar via FTP para:
wp-content/plugins/super-links-multisite/

# Depois ativar no WordPress Admin
```

## ⚙️ Configuração Inicial

### 🎛️ Configuração Automática
- O plugin criará **automaticamente** todas as tabelas necessárias
- **Não requer configuração adicional**
- **Funciona imediatamente** após ativação

### 🌐 Para WordPress Multisite

1. **Ative em Rede** (recomendado):
   - Instala automaticamente em todos os sites
   - Cada site mantém seus próprios links
   - Configuração centralizada

2. **Ativação Individual**:
   - Ative site por site se preferir
   - Maior controle por administrador

## 🔧 Verificação da Instalação

### ✅ Checklist Pós-Instalação

1. **Menu visível**: Deve aparecer "Super Links Multisite" no menu admin
2. **Página inicial**: Deve mostrar status "Plugin Ativo e Funcionando"
3. **Banco de dados**: Tabelas criadas automaticamente
4. **Multisite**: Se aplicável, verificar informações na dashboard

### 🧪 Teste Básico

1. Acesse **Super Links Multisite > Criar Links**
2. Clique em **"Novo link"**
3. Crie um link de teste:
   - Nome: "Teste"
   - Palavra-chave: "teste"
   - URL: "https://google.com"
4. Acesse `seusite.com/teste` para verificar redirecionamento

## 🔄 Migração do Plugin Original

Se você já tinha o Super Links Light instalado:

### ⚠️ Backup Obrigatório
```bash
# Fazer backup completo
# - Banco de dados
# - Arquivos do WordPress
# - Configurações
```

### 🔧 Processo de Migração

1. **Desativar plugin original**:
   - Plugins > Super Links Light > Desativar

2. **Remover plugin original** (opcional):
   - Delete o plugin antigo

3. **Instalar nova versão**:
   - Seguir instruções acima

4. **Verificar dados**:
   - Seus links existentes devem aparecer normalmente
   - Configurações são mantidas

## 🚨 Problemas Comuns

### ❌ Plugin não aparece no menu
- **Causa**: Permissões insuficientes
- **Solução**: Verificar se usuário tem permissão `manage_options`

### ❌ Erro ao ativar
- **Causa**: Conflito com plugin original
- **Solução**: Desativar completamente o plugin original primeiro

### ❌ Links não funcionam
- **Causa**: Configuração de permalinks
- **Solução**: 
  1. Ir em **Configurações > Links Permanentes**
  2. Clicar em **"Salvar Alterações"**

### ❌ Problemas no Multisite
- **Causa**: Configuração de subdomínios
- **Solução**: Verificar configuração do WordPress Multisite

## 📞 Suporte Adicional

### 🔍 Debug Mode
Para investigar problemas:

```php
// No wp-config.php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

### 📝 Logs
Verificar logs em:
- `wp-content/debug.log`
- Error logs do servidor

### 🔧 Informações do Sistema
No plugin, vá em **Super Links Multisite** > Dashboard para ver:
- Versão do WordPress
- Versão do PHP  
- Status do Multisite
- Configurações ativas

---

## ✅ Instalação Completa!

Após seguir estes passos, seu plugin deve estar:
- ✅ Instalado e ativo
- ✅ Funcionando sem ativação
- ✅ Compatível com multisite
- ✅ Pronto para criar links

**Próximo passo**: Criar seu primeiro link de teste! 🎉