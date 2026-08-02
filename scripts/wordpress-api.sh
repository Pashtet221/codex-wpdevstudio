#!/usr/bin/env bash
set -euo pipefail

API="${WORDPRESS_URL%/}/wp-json/codex-bridge/v1"
AUTH="${WORDPRESS_USERNAME}:${WORDPRESS_APP_PASSWORD}"

curl_api() {
  curl --silent --show-error --fail-with-body --user "$AUTH" "$@"
}

case "${1:-help}" in
  health)
    curl_api "$API/health"
    ;;
  pages)
    curl_api "$API/posts?post_type=page&per_page=100"
    ;;
  posts)
    curl_api "$API/posts?post_type=post&per_page=100"
    ;;
  wp-plugins)
    bridge_response="$(mktemp)"
    if curl_api "$API/posts?post_type=wp-plugins&per_page=100" >"$bridge_response"; then
      cat "$bridge_response"
      rm -f "$bridge_response"
      exit 0
    fi
    rm -f "$bridge_response"
    echo "codex-bridge wp-plugins is unavailable; falling back to WordPress REST post type plugin" >&2
    curl_api "${WORDPRESS_URL%/}/wp-json/wp/v2/plugin?per_page=100"
    ;;
  find)
    curl_api --get --data-urlencode "search=${2:-}" "$API/posts"
    ;;
  get)
    curl_api "$API/posts/$2"
    ;;
  get-wp-plugin)
    curl_api "${WORDPRESS_URL%/}/wp-json/wp/v2/plugin/$2"
    ;;
  create)
    curl_api -X POST -H "Content-Type: application/json" --data-binary @"$2" "$API/posts"
    ;;
  create-wp-plugin)
    curl_api -X POST -H "Content-Type: application/json" --data-binary @"$2" "$API/posts"
    ;;
  acf)
    curl_api "$API/posts/$2/acf"
    ;;
  update)
    curl_api -X PATCH -H "Content-Type: application/json" --data-binary @"$3" "$API/posts/$2"
    ;;
  update-wp-plugin)
    bridge_response="$(mktemp)"
    if curl_api -X PATCH -H "Content-Type: application/json" --data-binary @"$3" "$API/posts/$2" >"$bridge_response"; then
      cat "$bridge_response"
      rm -f "$bridge_response"
      exit 0
    fi
    rm -f "$bridge_response"
    echo "codex-bridge wp-plugins update is unavailable; falling back to WordPress REST post type plugin" >&2
    curl_api -X PATCH -H "Content-Type: application/json" --data-binary @"$3" "${WORDPRESS_URL%/}/wp-json/wp/v2/plugin/$2"
    ;;
  update-acf)
    curl_api -X PATCH -H "Content-Type: application/json" --data-binary @"$3" "$API/posts/$2/acf"
    ;;
  scan-links)
    curl_api -X POST "$API/links/scan"
    ;;
  replace-links)
    curl_api -X POST -H "Content-Type: application/json" --data-binary @"$2" "$API/links/replace"
    ;;
  audit)
    curl_api "$API/audit"
    ;;
  *)
    echo "health pages posts wp-plugins find get get-wp-plugin create create-wp-plugin acf update update-wp-plugin update-acf scan-links replace-links audit"
    ;;
esac
