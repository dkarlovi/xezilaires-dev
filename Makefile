ifndef BUILD_ENV
BUILD_ENV=php7.4
endif

DOCQA_DOCKER_IMAGE=dkarlovi/docqa:latest
DOCQA_DOCKER_COMMAND=docker run --init --interactive --rm --user "$(shell id -u):$(shell id -g)"  --volume "$(shell pwd)/var/tmp/docqa:/.cache" --volume "$(shell pwd):/project" --workdir /project ${DOCQA_DOCKER_IMAGE}

PHPQA_DOCKER_IMAGE=jakzal/phpqa:1.28-${BUILD_ENV}-alpine
PHPQA_DOCKER_COMMAND=docker run --init --interactive --rm --env "COMPOSER_CACHE_DIR=/composer/cache" --user "$(shell id -u):$(shell id -g)" --volume "$(shell pwd)/var/tmp/phpqa:/tmp" --volume "$(shell pwd):/project" --volume "${HOME}/.composer:/composer" --workdir /project ${PHPQA_DOCKER_IMAGE}

dist: composer-normalize cs phpstan psalm test doc
ci: check test doc
check: composer-validate cs-check phpstan psalm
test: infection
doc: markdownlint textlint proselint vale

composer-validate: ensure composer-normalize-check
	sh -c "${PHPQA_DOCKER_COMMAND} composer validate"

composer-install: fetch ensure
	sh -c "${PHPQA_DOCKER_COMMAND} composer upgrade"

composer-install-lowest: fetch ensure
	sh -c "${PHPQA_DOCKER_COMMAND} composer upgrade --with-all-dependencies --prefer-lowest"

composer-normalize: ensure
	sh -c "${PHPQA_DOCKER_COMMAND} composer normalize"

composer-normalize-check: ensure
	sh -c "${PHPQA_DOCKER_COMMAND} composer normalize --dry-run"

cs: ensure
	sh -c "${PHPQA_DOCKER_COMMAND} php-cs-fixer fix --using-cache=false --diff -vvv"

cs-check: ensure
	sh -c "${PHPQA_DOCKER_COMMAND} php-cs-fixer fix --using-cache=false --dry-run --diff -vvv"

phpstan: ensure
	sh -c "${PHPQA_DOCKER_COMMAND} phpstan analyse"

psalm: ensure
	sh -c "${PHPQA_DOCKER_COMMAND} psalm --show-info=false --threads max"

phpunit:
	sh -c "${PHPQA_DOCKER_COMMAND} vendor/bin/phpunit --verbose"

benchmark: ensure
	sh -c "${PHPQA_DOCKER_COMMAND} phpbench run --iterations=10 --report=aggregate -v"

phpunit-coverage: ensure
	sh -c "${PHPQA_DOCKER_COMMAND} phpdbg -qrr vendor/bin/phpunit --verbose --coverage-text --log-junit=var/junit.xml --coverage-xml var/coverage-xml/"

infection: phpunit-coverage
	sh -c "${PHPQA_DOCKER_COMMAND} phpdbg -qrr /tools/infection run --verbose --show-mutations --no-interaction --only-covered --coverage var/ --min-msi=100 --min-covered-msi=100 --threads 4"

markdownlint: ensure
	sh -c "${DOCQA_DOCKER_COMMAND} markdownlint *.md"

proselint: ensure
	sh -c "${DOCQA_DOCKER_COMMAND} proselint README.md"

textlint: ensure
	sh -c "${DOCQA_DOCKER_COMMAND} textlint -c docs/.textlintrc.dist README.md"

vale: ensure
	sh -c "${DOCQA_DOCKER_COMMAND} vale --config docs/.vale.ini.dist README.md"

ensure:
	mkdir -p ${HOME}/.composer var/tmp/docqa var/tmp/phpqa

fetch:
	docker pull "${DOCQA_DOCKER_IMAGE}"
	docker pull "${PHPQA_DOCKER_IMAGE}"

clean:
	rm -rf var/
