#!/bin/zsh

set -e

source "$(dirname "$0")/config.env

echo "Återskapar '$DB'."
mysql -u "$USER" -h "$HOST" -e

echo "Init script."
for f in $(ls -l ../init/*.sql | sort); do
    echo "Kör $f"
    mysql -u "$USER" -h "$HOST" "$DB" < "$f"
done

echo "Migrerar"
for f in $(ls -l ../migrations/*.sql 2>/dev/null | sort); do
    echo "Kör $f"
    mysql -u "$USER" -h "$HOST" "$DB" < "$f"
done

echo "Databas återställd"
