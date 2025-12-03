# Script per fermare il sito dei tornei

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "  Arresto Tournament Manager" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

Write-Host "Arresto del container..." -ForegroundColor Yellow
docker-compose down

if ($LASTEXITCODE -eq 0) {
    Write-Host ""
    Write-Host "Container arrestato con successo!" -ForegroundColor Green
    Write-Host ""
} else {
    Write-Host ""
    Write-Host "Errore durante l'arresto." -ForegroundColor Red
    Write-Host ""
}

Read-Host "Premi INVIO per uscire"
