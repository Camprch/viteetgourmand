#!/usr/bin/env bash

set -euo pipefail

BASE_URL="${1:-}"

if [[ -z "${BASE_URL}" ]]; then
  echo "Usage: $0 <base_url>"
  echo "Example: $0 https://app.viteetgourmand.example"
  exit 1
fi

PATHS=(
  "/"
  "/menus"
  "/contact"
  "/mentions-legales"
  "/cgv"
  "/login"
  "/register"
  "/reset-password"
)

echo "Smoke test on: ${BASE_URL}"

for path in "${PATHS[@]}"; do
  url="${BASE_URL%/}${path}"
  code="$(curl -sS -o /dev/null -w "%{http_code}" "${url}")"
  if [[ "${code}" != "200" ]]; then
    echo "FAIL ${path} -> HTTP ${code}"
    exit 2
  fi
  echo "OK   ${path} -> HTTP ${code}"
done

echo "Smoke test passed."
