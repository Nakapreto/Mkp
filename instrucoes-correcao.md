# Instruções para Correção do Plugin Multisite Super Links

## ⚠️ SOLUÇÃO IMEDIATA - Criação do Arquivo Faltante

### Passo 1: Acesse seu servidor
- Use FTP, cPanel File Manager, ou SSH para acessar seu servidor
- Navegue até: `/home1/andr6521/public_html/wp-content/plugins/multisite-super-links/`

### Passo 2: Verifique a estrutura
Certifique-se de que existe:
- ✅ `multisite-super-links.php` (arquivo principal)
- ❌ `admin/` (pasta - pode estar faltando)
- ❌ `admin/admin-functions.php` (arquivo - está faltando)

### Passo 3: Crie a pasta admin (se não existir)
```bash
mkdir admin
```

### Passo 4: Crie o arquivo admin-functions.php
1. Na pasta `admin/`, crie um novo arquivo chamado `admin-functions.php`
2. Copie todo o conteúdo do arquivo `admin-functions.php` que foi gerado
3. Cole no novo arquivo e salve

### Passo 5: Defina as permissões corretas
```bash
chmod 644 admin/admin-functions.php
chmod 755 admin/
```

### Passo 6: Teste o site
- Acesse sua área administrativa do WordPress
- Verifique se o erro desapareceu
- O plugin deve funcionar com funcionalidade básica

## 🔧 SOLUÇÃO RECOMENDADA - Reinstalação Completa

### Método 1: Via WordPress Admin (Recomendado)
1. **Desative o plugin** atual:
   - Admin → Plugins → Localizar "Multisite Super Links"
   - Clique em "Desativar"

2. **Delete o plugin**:
   - Clique em "Deletar"
   - Confirme a exclusão

3. **Reinstale o plugin**:
   - Admin → Plugins → Adicionar Novo
   - Pesquise por "Multisite Super Links"
   - Instale e ative

### Método 2: Via FTP/cPanel
1. **Faça backup** da pasta atual (se houver configurações):
   ```bash
   cp -r multisite-super-links/ multisite-super-links-backup/
   ```

2. **Delete a pasta atual**:
   ```bash
   rm -rf multisite-super-links/
   ```

3. **Baixe e extraia o plugin**:
   - Baixe do repositório oficial: https://wordpress.org/plugins/
   - Extraia e faça upload via FTP

## 🎯 VERIFICAÇÃO PÓS-CORREÇÃO

### Teste estes pontos:
- [ ] Site carrega sem erros fatais
- [ ] Admin do WordPress acessível
- [ ] Plugin aparece na lista de plugins
- [ ] Não há avisos de erro no log

### Logs para monitorar:
```bash
tail -f /home1/andr6521/public_html/wp-content/debug.log
```

## 🚨 SOLUÇÃO DE EMERGÊNCIA

Se o site ainda não funcionar, renomeie temporariamente a pasta do plugin:

```bash
mv multisite-super-links/ multisite-super-links-disabled/
```

Isso desativará o plugin e permitirá que o site funcione normalmente.

## 📝 COMANDOS ÚTEIS

### Verificar se o arquivo existe:
```bash
ls -la /home1/andr6521/public_html/wp-content/plugins/multisite-super-links/admin/
```

### Verificar permissões:
```bash
ls -la /home1/andr6521/public_html/wp-content/plugins/multisite-super-links/
```

### Verificar logs de erro:
```bash
tail -n 50 /home1/andr6521/public_html/wp-content/debug.log
```

## ⚡ SOLUÇÃO RÁPIDA - Uma Linha

Se você tem acesso SSH, execute:

```bash
cd /home1/andr6521/public_html/wp-content/plugins/multisite-super-links/ && mkdir -p admin && touch admin/admin-functions.php && echo "<?php // Arquivo temporário para evitar erro fatal" > admin/admin-functions.php
```

## 📞 Suporte Adicional

Se nenhuma solução funcionar:
1. Verifique se há conflitos com outros plugins
2. Teste com tema padrão do WordPress
3. Verifique se a versão do WordPress é compatível
4. Considere usar um plugin alternativo

## ✅ Lista de Verificação Final

- [ ] Backup realizado
- [ ] Arquivo `admin-functions.php` criado ou plugin reinstalado
- [ ] Permissões corretas aplicadas
- [ ] Site testado e funcionando
- [ ] Logs verificados
- [ ] Plugin configurado conforme necessário