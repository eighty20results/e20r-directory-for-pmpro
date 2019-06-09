#!/bin/bash
#
# Build script for Eighty/20 Results - Extended Membership Directory for Paid Memberships Pro
#
# Copyright (c) 2015 - 2019 Eighty/20 Results by Wicked Strong Chicks, LLC
#
short_name="e20r-directory-for-pmpro"
server="eighty20results.com"
include=(components css inc includes/yahnis-elsts javascript languages ${short_name}.php readme.txt)
exclude=( *.yml *.phar composer.* vendor)
#build=()
plugin_path="${short_name}"
version=$(egrep "^Version:" ../${short_name}.php | sed 's/[[:alpha:]|(|[:space:]|\:]//g' | awk -F- '{printf "%s", $1}')
metadata="../metadata.json"
src_path="../"
dst_path="../build/${plugin_path}"
kit_path="../build/kits"
kit_name="${kit_path}/${short_name}-${version}"

echo "Building kit for version ${version}"

mkdir -p ${kit_path}
mkdir -p ${dst_path}
mkdir -p ${src_path}/includes

if [[ -f  ${kit_name} ]]
then
    echo "Kit is already present. Cleaning up"
    rm -rf ${dst_path}
    rm -f ${kit_name}
fi

for p in ${include[@]}; do
    if [[ 'includes/yahnis-elsts' == ${p} ]] || [[ 'includes/autoload.php' == ${p} ]] || [[ 'includes/composer' == ${p} ]] || [[ 'includes/bin' == ${p} ]] ; then
        cp -R ${src_path}${p} ${dst_path}/includes
    else
        cp -R ${src_path}${p} ${dst_path}
    fi
done

#mkdir -p ${dst_path}/plugin-updates/vendor/
#for b in ${build[@]}; do
#    cp ${src_path}${b} ${dst_path}/plugin-updates/vendor/
#done

for e in ${exclude[@]}; do
    find ${dst_path} -type d -iname ${e} -exec rm -rf {} \;
done

cp
cd ${dst_path}/..
zip -r ${kit_name}.zip ${plugin_path}
ssh ${server} "cd ./${server}/protected-content/ ; mkdir -p \"${short_name}\""
scp ${kit_name}.zip ${server}:./${server}/protected-content/${short_name}/
scp ${metadata} ${server}:./${server}/protected-content/${short_name}/
ssh ${server} "cd ./${server}/protected-content/ ; ln -sf \"${short_name}\"/\"${short_name}\"-\"${version}\".zip \"${short_name}\".zip"
rm -rf ${dst_path}


