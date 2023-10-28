#!/bin/sh
# This script is used to update the gear data from the Wynncraft API.

TARGET_DIR=$(cd $(dirname "$0")/.. >/dev/null 2>&1 && pwd)

cd $TARGET_DIR

# Download the json file from Wynncraft API
curl -X POST -d '{"type":["weapons","armour","accessories"]}' -H "Content-Type: application/json" -o gear.json.tmp "https://api.wynncraft.com/v3/item/search?fullResult=True"

if [ ! -s gear.json.tmp ]; then
    rm gear.json.tmp
    echo "Error: Wynncraft API is not working, aborting"
    exit
fi

# Sort the items and keys in the json file, since the Wynncraft API is not stable in its order
# This will also get rid of the timestamp, which would mess up the md5sum
jq --sort-keys -r '.' < gear.json.tmp > gear.json.tmp2
# Minimalize the json file
jq -c < gear.json.tmp2 > gear.json
rm gear.json.tmp gear.json.tmp2

# To be able to review new data, we also need an expanded, human-readable version
jq '.' < gear.json > gear_expanded.json

# Calculate md5sum of the new gear data
MD5=$(md5sum $TARGET_DIR/gear.json | cut -d' ' -f1)

# Update ulrs.json with the new md5sum for dataStaticGearAdvanced
jq '. = [.[] | if (.id == "dataStaticGearAdvanced") then (.md5 = "'$MD5'") else . end]' < urls.json > urls.json.tmp
mv urls.json.tmp urls.json
