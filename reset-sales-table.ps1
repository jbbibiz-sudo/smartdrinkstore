# Arrêter en cas d'erreur
$ErrorActionPreference = "Stop"

Write-Host "🔄 Reset de la table sales..." -ForegroundColor Yellow

# 1. Supprimer les anciennes migrations
Write-Host "📁 Suppression des anciennes migrations..." -ForegroundColor Cyan
Get-ChildItem database/migrations -Filter "*sales*" | Remove-Item -Force

# 2. Créer la nouvelle migration
Write-Host "✨ Création de la nouvelle migration..." -ForegroundColor Cyan
php artisan make:migration create_sales_and_sale_items_tables_complete

Write-Host ""
Write-Host "⚠️  PROCHAINES ÉTAPES" -ForegroundColor Yellow
Write-Host "1. Copiez le contenu de la migration dans le fichier généré" -ForegroundColor White
Write-Host "2. Lancez la commande suivante" -ForegroundColor White
Write-Host "   php artisan migrate:fresh" -ForegroundColor Green
Write-Host ""