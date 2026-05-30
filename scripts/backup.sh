#!/usr/bin/env bash
#
# backup.sh — dump nightly de la DB prod + rotation 7 jours.
#
# Usage (cron) :
#   0 3 * * * /home/sora3439/app.larecreetech.com/larecree/scripts/backup.sh
#
# Sortie : ~/backups/larecree-YYYYMMDD-HHMMSS.sql.gz
# Rotation : garde 7 derniers fichiers, supprime les plus vieux.
#
# Variables à adapter en haut (les hardcoder ici plutôt que dans .env pour
# éviter d'avoir à parser dotenv depuis bash).

set -euo pipefail

# ============ CONFIG ============
DB_HOST="127.0.0.1"
DB_PORT="3306"
DB_NAME="sora3439_app_larecree"
DB_USER="sora3439_ramaAdmin"
DB_PASS="Agencehabitat26@"  # ← reflète .env.local prod (PAS d'URL-encode ici, c'est mysql CLI)
BACKUP_DIR="$HOME/backups"
RETENTION_DAYS=7
# ================================

mkdir -p "$BACKUP_DIR"

TIMESTAMP=$(date +%Y%m%d-%H%M%S)
DUMP_FILE="$BACKUP_DIR/larecree-${TIMESTAMP}.sql.gz"

echo "[$(date +'%H:%M:%S')] ▶ Dump $DB_NAME → $DUMP_FILE"

# mariadb-dump = nouveau nom de mysqldump sur o2switch
DUMP_CMD=$(command -v mariadb-dump 2>/dev/null || command -v mysqldump)
"$DUMP_CMD" \
    --host="$DB_HOST" \
    --port="$DB_PORT" \
    --user="$DB_USER" \
    --password="$DB_PASS" \
    --single-transaction \
    --quick \
    --routines \
    --triggers \
    --default-character-set=utf8mb4 \
    "$DB_NAME" \
    | gzip > "$DUMP_FILE"

SIZE=$(du -h "$DUMP_FILE" | cut -f1)
echo "[$(date +'%H:%M:%S')] ✓ Dump $SIZE OK"

# ============ ROTATION ============
echo "[$(date +'%H:%M:%S')] ▶ Rotation > $RETENTION_DAYS jours"
OLD_FILES=$(find "$BACKUP_DIR" -name 'larecree-*.sql.gz' -type f -mtime "+$RETENTION_DAYS" 2>/dev/null || true)
if [ -n "$OLD_FILES" ]; then
    echo "$OLD_FILES" | xargs -r rm -v
else
    echo "    rien à supprimer."
fi

# Récap
COUNT=$(find "$BACKUP_DIR" -name 'larecree-*.sql.gz' -type f | wc -l)
TOTAL=$(du -sh "$BACKUP_DIR" 2>/dev/null | cut -f1)
echo "[$(date +'%H:%M:%S')] ━━ Backup terminé · $COUNT fichiers · $TOTAL total"
