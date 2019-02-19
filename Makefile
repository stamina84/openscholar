.PHONY: build

DIR := ${CURDIR}

build: vendor
		docker run -it --rm -v "$(DIR)":/usr/src/app -w /usr/src/app node:10.15.1 sh -c "cd profile/themes/os_base && npm install && ./node_modules/.bin/gulp sass"

vendor: composer.json composer.lock
		docker-compose exec -T php composer install
