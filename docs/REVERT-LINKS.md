# 🔄 Revert Link Changes

## Problema
Dopo aver cambiato i link da relativi ad assoluti, tutti i link sono broken sul server.

## Causa
Con `public/` come document root, i link assoluti `/home.php` funzionano solo da dentro `public/`, ma non dalle altre directory.

## Soluzione
Ripristinare i link a percorsi relativi come erano prima:
- Da `public/`: `href="home.php"` (stesso livello)
- Da `views/`, `forms/`, `admin/`: `href="../public/home.php"` (risale di un livello)

## Modifiche Applicate
Script PowerShell ha ripristinato tutti i link da assoluti a relativi in tutti i file PHP.

## Prossimi Passi
1. Commit e push delle modifiche
2. Portainer ricostruirà il container
3. I link dovrebbero tornare a funzionare come prima

**Nota**: Gli avatar e il CSS rimangono corretti (usano percorsi assoluti che funzionano per gli asset).
