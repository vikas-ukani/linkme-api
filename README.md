CD Project Directory

Rename .env.example to .env

Update correct database configuration in .env

composer update

php artisan migrate
php artisan db:seed
php artisan db:seed --class=CategorySeeder
php artisan db:seed --class=AdminSeeder
php artisan passport:install --force
sudo chmod 600 storage/oauth-\*.key

chown -R $user:$user storage/framework/views storage/framework/cache storage/framework/sessions

php artisan config:clear

To run the schedule jobs make entry in Crontab

-   -   -   -   -   php /var/www/html/linkme-dev/artisan schedule:run 1>> /dev/null 2>&1

[Update the correct root path /var/www/html/linkme-dev]
