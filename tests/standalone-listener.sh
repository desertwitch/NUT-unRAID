#!/bin/bash

set -euo pipefail

repo_root="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
defaults="$repo_root/source/nut-dw/usr/local/emhttp/plugins/nut-dw/nut-defaults/upsd.conf"
rc_nut="$repo_root/source/nut-dw/etc/rc.d/rc.nut"

grep -qx 'LISTEN 127.0.0.1 3493' "$defaults"

source "$rc_nut"

config="$(mktemp)"
trap 'rm -f "$config"' EXIT

set_upsd_listener standalone "$config"
grep -qx 'LISTEN 127.0.0.1 3493' "$config"

cat > "$config" <<'EOF'
# Keep this comment.
LISTEN 0.0.0.0 3493
  listen 192.0.2.1 3493
EOF
set_upsd_listener standalone "$config"
[ "$(grep -Eic '^[[:space:]]*LISTEN[[:space:]]' "$config")" -eq 1 ]
[ "$(head -n 1 "$config")" = 'LISTEN 127.0.0.1 3493' ]

set_upsd_listener netserver "$config"
[ "$(grep -Eic '^[[:space:]]*LISTEN[[:space:]]' "$config")" -eq 1 ]
[ "$(head -n 1 "$config")" = 'LISTEN 0.0.0.0 3493' ]

echo "Standalone listener defaults are restricted to loopback."
