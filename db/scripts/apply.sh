#!/bin/zsh
set -e
setopt NULL_GLOB

source "$(dirname "$0")/config.env"

echo "Migrerar till '$DB'."
for f in ../migrations/*.sql; do
    echo "Running $f"
    sudo mariadb "$DB" < "$f"
    done

echo "Slutförd."
