#!/bin/bash
#Fix permission in plugin directory and set it to that of the current directory
permissions='0775'
echo -e "Permissions: Setting permissions to ${permissions}"
# We can accept the directory as an argument
dir=$3
if [ -z "$dir" ]; then
	dir=$(pwd)
fi
echo -e "Permissions: Directory is ${dir}"
chmod -cR "${permissions}" "${dir}" > /dev/null

# We can accept the group and owner as arguments
dirOwner=$1
if [ -z "$dirOwner" ]; then
	dirOwner=$(stat -c '%U' "${dir}")
fi
dirGroup=$2
if [ -z "$dirGroup" ]; then
	dirGroup=$(stat -c '%G' "${dir}")
fi
echo -e "Permissions: Updating ownership to ${dirOwner}:${dirGroup}"
chown -cR "${dirOwner}:${dirGroup}" "${dir}"> /dev/null
echo -e "Permissions: Updating storage permissions to 0777"
chmod -cR 0777 storage > /dev/null
chmod -cR 0775 assets > /dev/null
echo -e "Permissions: Done"
exit 0
