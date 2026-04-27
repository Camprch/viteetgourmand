#!/usr/bin/env bash

set -euo pipefail

DB_URL="${1:-}"
SQL_FILE="${2:-}"

if [[ -z "${DB_URL}" || -z "${SQL_FILE}" ]]; then
  echo "Usage: $0 <mysql_database_url> <sql_file>"
  echo "Example: $0 \"mysql://user:pass@host:3306/dbname\" database/schema.sql"
  exit 1
fi

if [[ ! -f "${SQL_FILE}" ]]; then
  echo "SQL file not found: ${SQL_FILE}"
  exit 1
fi

readarray -t DB_PARTS < <(
  php -r '
    $url = $argv[1];
    $p = parse_url($url);
    if (!$p || !isset($p["host"], $p["user"], $p["path"])) {
      fwrite(STDERR, "Invalid database URL\n");
      exit(2);
    }
    $host = $p["host"];
    $port = $p["port"] ?? 3306;
    $user = rawurldecode($p["user"]);
    $pass = rawurldecode($p["pass"] ?? "");
    $db   = ltrim($p["path"], "/");
    if ($db === "") {
      fwrite(STDERR, "Database name missing in URL path\n");
      exit(3);
    }
    echo $host, PHP_EOL, $port, PHP_EOL, $user, PHP_EOL, $pass, PHP_EOL, $db, PHP_EOL;
  ' "${DB_URL}"
)

HOST="${DB_PARTS[0]}"
PORT="${DB_PARTS[1]}"
USER="${DB_PARTS[2]}"
PASS="${DB_PARTS[3]}"
DB_NAME="${DB_PARTS[4]}"

echo "Importing ${SQL_FILE} into ${DB_NAME} on ${HOST}:${PORT} ..."
mysql -h "${HOST}" -P "${PORT}" -u "${USER}" -p"${PASS}" "${DB_NAME}" < "${SQL_FILE}"
echo "Import done."
