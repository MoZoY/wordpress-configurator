#!/bin/bash

# Variables
PLUGIN_FOLDER="naro-config"
BUILD_FOLDER="release"
MAIN_FILE="$PLUGIN_FOLDER/naro-config.php"
VERSION_PREFIX="0.5"
VERSION_DATE=$(date +"%Y%m%d")
VERSION_TIME=$(date +"%H%M%S")
NEW_VERSION="$VERSION_PREFIX.$VERSION_DATE.$VERSION_TIME"

# Update version in PHP file
if [[ -f "$MAIN_FILE" ]]; then
  sed -i.bak -E "s/(Version:\s*)([^\r\n]+)/\1$NEW_VERSION/" "$MAIN_FILE"
fi

# Ensure build folder exists
mkdir -p "$BUILD_FOLDER"

# Create ZIP
ZIP_NAME="$BUILD_FOLDER/naro-config-$VERSION_PREFIX.zip"
rm -f "$ZIP_NAME"
cd "$PLUGIN_FOLDER"
zip -r "../$ZIP_NAME" ./*
cd ..

echo "Packaged as $ZIP_NAME with version $NEW_VERSION"