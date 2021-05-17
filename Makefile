
COMPOSER_OPTIONS=--optimize-autoloader --no-interaction --classmap-authoritative

default: composer
	vendor/bin/php-cs-fixer fix
#	vendor/bin/psalm --no-cache
#	vendor/bin/phpunit

composer:
	composer install ${COMPOSER_OPTIONS} --quiet

update:
	composer update ${COMPOSER_OPTIONS}
