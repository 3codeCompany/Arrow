# Esotiq

1. Instalacja
    * App
    
        ```
        git clone ..........
        ```
    * Composer
        ```
        composer install
        ```
    * Front
        ```
        yarn install
        yarn run build
        ```


1. SERVER

    * PHP
    
        ```
        php -S 127.0.0.1:8000 public/index.php
        ```
    
    * APACHE
    
        ```apacheconfig
        NameVirtualHost *:80
        <VirtualHost *:80>
            DocumentRoot "C:/dev/www"
            ServerName localhost
            ServerAlias www.localhost
            <Directory "C:/dev/www">
                Order allow,deny
                Allow from all
            </Directory>
        </VirtualHost>
        ```

2. Konfiguracja

    * ENV
    
        Definiujemy dla każdego środowiska, ten plik nie przychodzi z gita. 
        
        ```dotenv
        ### ENV
        
        APP_ENV=dev
        APP_DEBUG_LIVE_ROUTING_SCAN=1
        APP_DEBUG_WEBPACK_DEV_SERVER=1 
        
        ### DATABASE
        DATABASE_URL=mysql:dbname=db_name;host=127.0.0.1
        DATABASE_USER=db_user
        DATABASE_PASSWORD=db_pass
        ```
        * APP_DEBUG_LIVE_ROUTING_SCAN - live routing scanner active
        * APP_DEBUG_WEBPACK_DEV_SERVER - live debug front
        
    * /app/config/project.yaml
    
3. Konsola    

    * Routing list
        
        `php bin/console debug:router`
        
        `php bin/console debug:router filter`
    
    * Clear cache
        
        `php bin/console cache:clear`
