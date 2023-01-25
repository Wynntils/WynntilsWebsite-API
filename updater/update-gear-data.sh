#!/bin/sh
# This script is used to update the gear data from the Wynncraft API.

TARGET_DIR=$(cd $(dirname "$0")/.. >/dev/null 2>&1 && pwd)

# Download the json file from Wynncraft API
wget -O $TARGET_DIR/gear.json.tmp "https://api.wynncraft.com/public_api.php?action=itemDB&category=all"

# Sort the items and keys in the json file, since the Wynncraft API is not stable in its order
# This will also get rid of the timestamp, which would mess up the md5sum
jq --sort-keys -c '{"items":  .items | sort_by(.name)}' < gear.json.tmp > gear.json
rm gear.json.tmp

# Calculate md5sum of the new gear data
MD5=$(md5sum $TARGET_DIR/gear.json | cut -d' ' -f1)

# Update ulrs.json with the new md5sum for dataStaticGear
jq '. = [.[] | if (.id == "dataStaticGear") then (.md5 = "'$MD5'") else . end]' < urls.json > urls.json.tmp
mv urls.json.tmp urls.json
