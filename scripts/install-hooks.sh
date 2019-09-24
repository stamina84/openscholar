#!/usr/bin/env bash

if [ "${COMPOSER_DEV_MODE}" != 1 ]; then
   echo "No Dev, not installing pre-commit hooks."
   exit
fi

GIT_DIR=$(git rev-parse --git-dir)

echo "Installing hooks..."
ln -s ./../../scripts/pre-commit.sh ${GIT_DIR}/hooks/pre-commit
ln -s ./../../scripts/prepare-commit-msg.sh ${GIT_DIR}/hooks/prepare-commit-msg
echo "Done!"
