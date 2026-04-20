#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
SRC_DIR="$ROOT_DIR/database/seed-assets/menus"
DST_DIR="$ROOT_DIR/public/images/menus"

if [[ ! -d "$SRC_DIR" ]]; then
  echo "Source introuvable: $SRC_DIR" >&2
  exit 1
fi

mkdir -p "$DST_DIR"
cp -f "$SRC_DIR"/* "$DST_DIR"/

echo "Images seed synchronisees vers $DST_DIR"
