test:
	composer exec -- phpunit tests

lint:
	composer exec -- phpcs --standard=PSR12 src/
