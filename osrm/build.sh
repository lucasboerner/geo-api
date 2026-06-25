#!/usr/bin/env bash

set -euo pipefail

REGION="${REGION:-de}"
PROFILE="${OSRM_PROFILE:-car}"

DATA_DIR="${OSRM_DATA_DIR:-/data}"
PBF="${DATA_DIR}/region.osm.pbf"
OSRM_FILE="${DATA_DIR}/region.osrm"
MARKER="${DATA_DIR}/.osrm-build-complete"

if [[ -f "$MARKER" ]]; then
  echo "[osrm-init] customized graph already present ($MARKER) — skipping build."
  exit 0
fi

case "$REGION" in
  de|germany)     GEOFABRIK_PATH="europe/germany" ;;
  at|austria)     GEOFABRIK_PATH="europe/austria" ;;
  fr|france)      GEOFABRIK_PATH="europe/france" ;;
  nl|netherlands) GEOFABRIK_PATH="europe/netherlands" ;;
  es|spain)       GEOFABRIK_PATH="europe/spain" ;;
  it|italy)       GEOFABRIK_PATH="europe/italy" ;;
  ch|switzerland) GEOFABRIK_PATH="europe/switzerland" ;;
  be|belgium)     GEOFABRIK_PATH="europe/belgium" ;;
  pl|poland)      GEOFABRIK_PATH="europe/poland" ;;
  europe)         GEOFABRIK_PATH="europe" ;;
  *)              GEOFABRIK_PATH="" ;;
esac

PBF_URL="${GEOFABRIK_URL:-}"
if [[ -z "$PBF_URL" ]]; then
  if [[ -z "$GEOFABRIK_PATH" ]]; then
    echo "[osrm-init] REGION='${REGION}' is not in the built-in map; set GEOFABRIK_URL to its .osm.pbf extract." >&2
    exit 1
  fi
  PBF_URL="https://download.geofabrik.de/${GEOFABRIK_PATH}-latest.osm.pbf"
fi

if ! command -v curl >/dev/null 2>&1; then
  echo "[osrm-init] installing curl ..."
  apt-get update -qq
  apt-get install -y -qq --no-install-recommends curl ca-certificates
fi

mkdir -p "$DATA_DIR"
echo "[osrm-init] REGION='${REGION}' profile='${PROFILE}'"
echo "[osrm-init] extract: ${PBF_URL}"

echo "[osrm-init] downloading .osm.pbf ..."
curl -fSL --retry 3 --retry-delay 5 "$PBF_URL" -o "$PBF"

echo "[osrm-init] osrm-extract (-p /opt/${PROFILE}.lua) ..."
osrm-extract -p "/opt/${PROFILE}.lua" "$PBF"

echo "[osrm-init] osrm-partition ..."
osrm-partition "$OSRM_FILE"

echo "[osrm-init] osrm-customize ..."
osrm-customize "$OSRM_FILE"

{
  echo "region=${REGION}"
  echo "geofabrik_url=${PBF_URL}"
  echo "profile=${PROFILE}"
} > "$MARKER"

echo "[osrm-init] build complete."
