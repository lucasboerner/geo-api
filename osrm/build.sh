#!/usr/bin/env bash

set -euo pipefail

REGION="${REGION:-europe/germany}"
PROFILE="${OSRM_PROFILE:-car}"
PBF_URL="${GEOFABRIK_URL:-https://download.geofabrik.de/${REGION}-latest.osm.pbf}"

DATA_DIR="${OSRM_DATA_DIR:-/data}"
PBF="${DATA_DIR}/region.osm.pbf"
OSRM_FILE="${DATA_DIR}/region.osrm"
MARKER="${DATA_DIR}/.osrm-build-complete"

if [[ -f "$MARKER" ]]; then
  echo "[osrm-init] customized graph already present ($MARKER) — skipping build."
  exit 0
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
