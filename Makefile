phpcs:
	./vendor/bin/phpcs

phpstan:
	./vendor/bin/phpstan analyse -c phpstan.neon
