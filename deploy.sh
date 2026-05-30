#!/usr/bin/env bash
#
# deploy.sh — déploiement prod manuel pour o2switch.
#
# Usage (depuis Terminal cPanel ou SSH) :
#   cd ~/app.larecreetech.com/larecree
#   bash deploy.sh
#
# Étapes (chaque étape doit réussir, sinon arrêt avec set -e) :
#   1. git pull origin main
#   2. composer install --no-dev --optimize-autoloader
#   3. cache:clear --env=prod
#   4. cache:warmup --env=prod
#   5. doctrine:migrations:migrate --no-interaction
#   6. importmap:install + asset-map:compile --env=prod
#   7. chmod var/ pour writability
#
# Couleurs ANSI simples pour repérer chaque étape dans le log.

set -euo pipefail

GREEN="\033[0;32m"
YELLOW="\033[0;33m"
RED="\033[0;31m"
RESET="\033[0m"

step() {
    echo -e "\n${YELLOW}▶ $1${RESET}"
}

ok() {
    echo -e "${GREEN}✓ $1${RESET}"
}

trap 'echo -e "${RED}✗ Échec à l'étape précédente. Arrêt.${RESET}"; exit 1' ERR

cd "$(dirname "$0")"

step "git pull origin main"
git pull origin main
ok "pull OK"

step "composer install (no-dev, optimized)"
composer install --no-dev --optimize-autoloader --no-interaction
ok "composer OK"

step "cache:clear --env=prod"
php bin/console cache:clear --env=prod --no-interaction
ok "cache cleared"

step "cache:warmup --env=prod"
php bin/console cache:warmup --env=prod
ok "cache warmed"

step "doctrine:migrations:migrate --allow-no-migration"
php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration
ok "migrations applied"

step "importmap:install + asset-map:compile --env=prod"
php bin/console importmap:install --env=prod
php bin/console asset-map:compile --env=prod
ok "assets compiled"

step "chmod var/ writable"
chmod -R 775 var/
ok "var/ permissions ok"

echo -e "\n${GREEN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${RESET}"
echo -e "${GREEN}✓ Déploiement terminé.${RESET}"
echo -e "${GREEN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${RESET}"
