#!/bin/bash

if ! [ -x "$(command -v unzip)" ]; then
    echo "You don't have unzip installed"
    exit 1
fi

mkdir -p storage/geo
cd storage/geo
wget http://download.geonames.org/export/dump/US.zip && unzip -o US.zip && rm US.zip
wget http://download.geonames.org/export/dump/hierarchy.zip && unzip -o hierarchy.zip && rm hierarchy.zip
cd ../../
sed -i 's/\/\/Igaster\\LaravelCities\\GeoServiceProvider::class/Igaster\\LaravelCities\\GeoServiceProvider::class/g' config/app.php
php artisan app:foreign-key-checks --disable
php artisan app-geo:seed US
php artisan app:foreign-key-checks --enable
sed -i 's/Igaster\\LaravelCities\\GeoServiceProvider::class/\/\/Igaster\\LaravelCities\\GeoServiceProvider::class/g' config/app.php
exit 0

