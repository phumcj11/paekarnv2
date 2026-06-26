#!/bin/bash
# Wrapper — เรียก move-app-to-paekan.sh (เก็บไว้เพื่อ backward compat กับ deploy เก่า)
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
exec bash "$SCRIPT_DIR/move-app-to-paekan.sh"
