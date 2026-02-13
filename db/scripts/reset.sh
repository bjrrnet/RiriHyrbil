#!/bin/zsh
SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
set -e
setopt NULL_GLOB

source "$SCRIPT_DIR/config.env"

echo "Återskapar '$DB'."
sudo mariadb -e "DROP DATABASE IF EXISTS $DB; CREATE DATABASE $DB;" 

echo "Init script."

for f in "$SCRIPT_DIR/../init/"*.sql; do
    echo "Kör $f"
    sudo mariadb "$DB" < "$f"
done

echo "Migrerar"
for f in "S$SCRIPT_DIR/../migrations/"*.sql; do
    echo "Kör $f"
    sudo mariadb "$DB" < "$f"
done

echo "Databas återställd"
