# AsyncWebFrontend
FrontEnd installation for AsyncWeb

Installation: 
1) Install composer
```bash
curl -sS https://getcomposer.org/installer | php
mv composer.phar /usr/local/bin/composer
```

2) Install project to prod01 directory
```bash
mkdir /srv/www/vhosts/MyProject
cd /srv/www/vhosts/MyProject
git clone https://github.com/scholtz/AsyncWebFrontend.git prod01
cd /srv/www/vhosts/MyProject/prod01
cp composer.json.default composer.json
composer update
```

3) Set permissions 
```bash
chown -R www-data:users .
# or
chown -R user:www-data .

find . -type d -exec chmod 770 {} \; && find . -type f -exec chmod 660 {} \;
```

4) Set up webserver
then add path your virtual host for the domain in Apache, Nginx, or other webserver to /srv/www/vhosts/MyApp/htdocs

For example:
```
server {

	root /srv/www/vhosts/MyProject/prod01/htdocs;
	index index.html index.php;

	server_name www.myproject.com ru.myproject.com;


	location ~ \.php$ {
		location ~ \..*/.*\.php$ {return 404;}
		include fastcgi_params;
		fastcgi_pass  127.0.0.1:9000;
		fastcgi_param APPLICATION_ENV prod01;
	}

	location / {
		try_files $uri $uri/ /index.php;
	}

	# if SSL is not enabled, disable lines below:
	
	ssl_certificate /etc/letsencrypt/live/www.myproject.com/fullchain.pem;
	ssl_certificate_key /etc/letsencrypt/live/www.myproject.com/privkey.pem;
    ssl_trusted_certificate /etc/letsencrypt/live/www.myproject.com/fullchain.pem;	
    
	include snippets/ssl-params.conf;
}
```

Do not forget to reload apache or nginx, for example: 
```
nginx -t 				# test nginx config
service nginx reload 	# reload nginx config
```


5) Set up project
Set up your settings.php file. Use settings.example.php as example usage file.

You can alternativly use the web setup.

6) To upgrade project do the following:
```bash
git fetch origin master
git reset --hard FETCH_HEAD
git clean -df
```
7) Bower
It is recomended to use bower for distribution of javascript libraries
```bash
cd htdocs
bower install jquery
bower install bootstrap
bower install font-awesome
```
