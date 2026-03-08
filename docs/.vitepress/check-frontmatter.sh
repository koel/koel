#!/usr/bin/env bash
# Ensures every docs/*.md page has a `description` in its YAML frontmatter.
# Run: bash docs/.vitepress/check-frontmatter.sh

set -euo pipefail

DOCS_DIR="$(cd "$(dirname "$0")/.." && pwd)"
missing=()

while IFS= read -r file; do
  # Check if file starts with --- (has frontmatter) and contains a description field
  if ! awk '
    BEGIN { in_fm=0; found=0 }
    NR==1 && /^---/ { in_fm=1; next }
    in_fm && /^---/ { exit }
    in_fm && /^description:[ \t]+.+/ { found=1; exit }
    END { exit !found }
  ' "$file"; then
    missing+=("${file#"$DOCS_DIR/"}")
  fi
done < <(find "$DOCS_DIR" -name '*.md' -not -path '*/.vitepress/*' | sort)

if [ ${#missing[@]} -gt 0 ]; then
  echo "ERROR: The following doc pages are missing a 'description' in their frontmatter:"
  for f in "${missing[@]}"; do
    echo "  - $f"
  done
  exit 1
fi

echo "All doc pages have a description in their frontmatter."
