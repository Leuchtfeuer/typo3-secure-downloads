#!/usr/bin/env bash

NEW_RELEASE=$1
DIR=$(pwd)


if [ -n "${NEW_RELEASE}" ]; then
  NEW_RELEASE=v${NEW_RELEASE}
  echo "Create release of version ${NEW_RELEASE}"
else
  echo "No version defined. Exit."
  exit 1;
fi

EXISTS=$(git describe --contains "${NEW_RELEASE}" 2>&1)

if [[ "$EXISTS" == "$NEW_RELEASE" ]]; then
  echo "Release already exists. Exit."
  exit 1;
fi

if [ -d "$DIR" ]; then
  echo "Installing composer dependencies..."
  cd "$DIR/Libraries" || exit
  composer install --no-dev --no-progress --quiet
  echo "Done."

  echo "Create git tag"
  cd "$DIR" || exit
  git tag "$NEW_RELEASE"

  echo "Archive repository..."
  zip -r "../secure_downloads_${1}.zip" ./* -x \*.git\* Build/\* Documentation/\* Tests/\* php-cs-fixer.php codecov.yml LICENSE.txt README.md
  echo "Done."

  echo "Please add and push the git tag: gp --tags"
else
  echo "This script has to be executed from the git root directory!"
  exit 1;
fi

exit 0;