#!/bin/zsh
set -e

source "$(dirname "$0")/config.env"

echo "Migrerar till '$DB'."
for f in $(ls -l ../migrations/*.sql 2>/dev/null | sort); do
    echo "Running $f"
    mysql -u "$USER -p"$PASS" -h "$HOST" "$DB" < "$f"
done

echo "Slutförd."
