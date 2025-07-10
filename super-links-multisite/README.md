# Super Links Multisite

Uma versão modificada do plugin Super Links Light especialmente otimizada para **WordPress Multisite com subdomínios**. Este plugin permite criar e gerenciar links de afiliados usando seu próprio domínio sem necessidade de ativação ou licenciamento.

## 🚀 Principais Modificações

### ✅ Removido Sistema de Ativação
- **Sem licenciamento**: O plugin funciona imediatamente após a instalação
- **Sem verificação de servidor**: Removidas todas as chamadas para servidores externos
- **Sem chaves de ativação**: Não há necessidade de códigos ou emails de ativação

### 🌐 Suporte Completo ao WordPress Multisite
- **Compatível com subdomínios**: Otimizado para instalações multisite com subdomínios
- **Ativação automática**: Quando ativado em rede, instala automaticamente em todos os sites
- **Isolamento de dados**: Cada site mantém seus próprios links e configurações
- **Suporte a múltiplos domínios**: Funciona perfeitamente com diferentes subdomínios

## 📋 Funcionalidades Disponíveis

### ✨ Recursos Principais
- **Encurtador de links**: Crie links curtos usando seu próprio domínio
- **Redirecionamento PHP**: Sistema robusto de redirecionamento
- **Links para WhatsApp e Telegram**: Suporte específico para redes sociais
- **Categorização**: Organize seus links em categorias
- **Testes A/B**: Compare performance entre diferentes destinos
- **Importação**: Importe links de outros plugins compatíveis

### 🛠️ Recursos Avançados
- **Clonagem de páginas**: Duplique páginas e posts facilmente
- **Sistema de cookies**: Controle de cookies para rastreamento
- **Métricas de links**: Acompanhe clicks e performance
- **Links inteligentes**: Sistema automatizado de redirecionamento
- **Popups**: Sistema de popups integrado

## 🔧 Instalação

### Requisitos
- WordPress 5.0 ou superior
- PHP 7.4 ou superior
- WordPress Multisite (opcional, mas recomendado)

### Passos de Instalação

1. **Faça upload do plugin**:
   ```bash
   # Via FTP ou painel de controle
   Copie a pasta 'super-links-multisite' para wp-content/plugins/
   ```

2. **Ative o plugin**:
   - **WordPress Normal**: Vá em Plugins > Plugins Instalados > Ativar "Super Links Multisite"
   - **WordPress Multisite**: Vá em Rede de Sites > Plugins > Ativar em Rede "Super Links Multisite"

3. **Configuração automática**:
   - O plugin irá criar automaticamente as tabelas necessárias
   - Não há necessidade de configuração adicional

## 🎯 Como Usar

### Criando Seu Primeiro Link

1. Acesse **Super Links Multisite** no menu do WordPress
2. Clique em **"Criar Links"**
3. Clique em **"Novo link"**
4. Preencha:
   - **Nome do link**: Identificação interna
   - **Palavra-chave**: Será usada na URL (ex: `seusite.com/palavra-chave`)
   - **URL de destino**: Para onde o link irá redirecionar
5. Salve e seu link estará pronto!

### Exemplo de Uso
```
Link criado: seusite.com/oferta-especial
Redireciona para: https://produto.com/affiliate?id=12345
```

### Multisite - Exemplo com Subdomínios
```
Site principal: exemplo.com
Subdominios: loja.exemplo.com, blog.exemplo.com

Cada subdomínio pode ter seus próprios links:
- loja.exemplo.com/desconto → link específico da loja
- blog.exemplo.com/artigo → link específico do blog
```

## ⚙️ Configurações

### Configurações Gerais
- Acesse **Super Links Multisite > Configurações**
- Configure opções de cache (Redis se disponível)
- Configure opções específicas de multisite

### Configurações Multisite
- **Compartilhamento entre sites**: Escolha se os links podem ser compartilhados entre sites da rede
- **Isolamento de dados**: Mantenha dados separados por site
- **Configuração de domínios**: Gerencie domínios e subdomínios

## 🔄 Migração do Plugin Original

Se você já usa o Super Links Light original:

1. **Backup**: Faça backup completo do seu site
2. **Desative**: Desative o plugin original
3. **Instale**: Instale esta versão modificada
4. **Ative**: Ative o novo plugin
5. **Verifique**: Seus links existentes serão mantidos automaticamente

## 🚨 Diferenças da Versão Original

| Recurso | Versão Original | Esta Versão |
|---------|----------------|-------------|
| Sistema de Ativação | ✅ Obrigatório | ❌ Removido |
| Verificação Online | ✅ Sim | ❌ Não |
| Multisite Nativo | ⚠️ Limitado | ✅ Completo |
| Update Checker | ✅ Automático | ❌ Manual |
| Promoções da Versão Pro | ✅ Presentes | ❌ Removidas |

## 📝 Notas Importantes

### ⚠️ Avisos
- **Sem atualizações automáticas**: Esta versão não recebe updates automáticos
- **Uso responsável**: Use apenas se você tem direito ao plugin original
- **Backup regular**: Faça backups regulares dos seus dados

### 🔒 Segurança
- Todas as verificações de segurança do WordPress são mantidas
- Não há coleta de dados externos
- Funciona completamente offline

## 🤝 Suporte

### Documentação
- Consulte a documentação original do Super Links para funcionalidades específicas
- Este README cobre apenas as modificações realizadas

### Problemas Conhecidos
- Se você encontrar problemas, verifique:
  1. Compatibilidade da versão do WordPress
  2. Configuração do multisite (se aplicável)
  3. Permissões de arquivos e diretórios

## 📄 Licença

Este plugin mantém a licença original GPL-2.0+. As modificações foram feitas para remover sistemas de ativação e melhorar a compatibilidade com multisite.

---

**Versão**: 2.0.0  
**Compatibilidade**: WordPress 5.0+ | WordPress Multisite  
**Última atualização**: 2025

> **Importante**: Esta é uma versão modificada do plugin original Super Links Light. Use apenas se você possui licença válida do produto original.