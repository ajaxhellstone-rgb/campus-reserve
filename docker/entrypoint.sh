#!/bin/sh
set -eu

case "${APP_KEY:-}" in
    base64:*) ;;
    '') echo "APP_KEY must be configured in the Render environment." >&2; exit 1 ;;
    *) export APP_KEY="base64:${APP_KEY}" ;;
esac

exec "$@"
