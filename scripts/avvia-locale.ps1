# Script per avviare il sito dei tornei in locale con Docker
# Requisiti: Docker Desktop installato e in esecuzione

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "  Tournament Manager - Avvio Locale" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

# Controlla se Docker è in esecuzione
Write-Host "Controllo Docker..." -ForegroundColor Yellow
$dockerRunning = docker info 2>&1 | Out-Null
if ($LASTEXITCODE -ne 0) {
    Write-Host "ERRORE: Docker non è in esecuzione!" -ForegroundColor Red
    Write-Host "Avvia Docker Desktop e riprova." -ForegroundColor Yellow
    Write-Host ""
    Read-Host "Premi INVIO per uscire"
    exit 1
}

Write-Host "Docker è attivo!" -ForegroundColor Green
Write-Host ""

# Ferma eventuali container precedenti
Write-Host "Pulizia container precedenti..." -ForegroundColor Yellow
docker-compose down 2>&1 | Out-Null

# Costruisci e avvia il container
Write-Host "Costruzione e avvio del container..." -ForegroundColor Yellow
Write-Host "(Questa operazione potrebbe richiedere qualche minuto la prima volta)" -ForegroundColor Gray
Write-Host ""

docker-compose up -d --build

if ($LASTEXITCODE -eq 0) {
    Write-Host ""
    Write-Host "========================================" -ForegroundColor Green
    Write-Host "  SITO AVVIATO CON SUCCESSO!" -ForegroundColor Green
    Write-Host "========================================" -ForegroundColor Green
    Write-Host ""
    Write-Host "Il sito è disponibile su:" -ForegroundColor Cyan
    Write-Host "  http://localhost:8000" -ForegroundColor White -BackgroundColor Blue
    Write-Host ""
    Write-Host "Premi CTRL+C per fermare il server" -ForegroundColor Yellow
    Write-Host "Oppure esegui: docker-compose down" -ForegroundColor Gray
    Write-Host ""
    
    # Apri il browser automaticamente
    Start-Sleep -Seconds 2
    Write-Host "Apertura del browser..." -ForegroundColor Yellow
    Start-Process "http://localhost:8000"
    
    # Mostra i log in tempo reale
    Write-Host ""
    Write-Host "Log del server (premi CTRL+C per uscire):" -ForegroundColor Cyan
    Write-Host "----------------------------------------" -ForegroundColor Gray
    docker-compose logs -f
} else {
    Write-Host ""
    Write-Host "ERRORE durante l'avvio del container!" -ForegroundColor Red
    Write-Host "Controlla i messaggi di errore sopra." -ForegroundColor Yellow
    Write-Host ""
    Read-Host "Premi INVIO per uscire"
    exit 1
}
