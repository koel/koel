#!/usr/bin/env bash

if [[ `git status --porcelain` ]]; then
  echo 'ERROR: Please commit all changes before tagging a new version.'
  exit 1
fi

if [ -z "$1" ]; then
  echo 'ERROR: You must supply a version number.'
  exit 1
fi

TAG=$1
echo $TAG > .version
cd ./resources/assets
git tag $TAG
git push --tags

cd ../..
git add .
git commit -m "chore: bump version to ${TAG}"
git push
git tag $TAG
git tag latest -f
git push --tags -f
