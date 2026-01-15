FROM php:8.4-cli-alpine AS composer

WORKDIR /usr/src/app

# Add the extension installer.
ADD --chmod=0755 https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/

# Install platform dependencies.
RUN install-php-extensions @composer zip

ENTRYPOINT ["/usr/local/bin/composer", "--no-cache", "--ignore-platform-req=ext-xdebug"]

FROM composer AS app

# Copy the package manifest.
# This allows changes to the manifest to invalidate vendor cache layers.
COPY composer.json /usr/src/app/composer.json

# Validate the package manifest.
# Checking the lock is skipped, because the vendor packages are not yet installed.
RUN composer validate --strict --no-check-lock

# Install.
RUN composer install --no-dev --no-progress --no-autoloader --ignore-platform-req=ext-xdebug

# Copy in the source files.
COPY src src

# Build the autoload files using an authoritative class map, for efficiency.
RUN composer dump-autoload --classmap-authoritative --strict-ambiguous --strict-psr

ENTRYPOINT ["/usr/local/bin/php"]

FROM app AS tests

# Install platform dependencies.
RUN install-php-extensions xdebug

# Install vendor software.
RUN composer install --no-progress --no-autoloader

# Copy in the test files.
COPY tests tests

# Build the autoload files not-optimized to allow for discovery during development.
RUN composer dump-autoload

# Copy in the PHPUnit configuration.
COPY phpunit.xml phpunit.xml

# Enable code coverage.
ENV XDEBUG_MODE=coverage

# Configure the entrypoint.
ENTRYPOINT ["/usr/src/app/vendor/bin/phpunit", "--configuration", "/usr/src/app/phpunit.xml"]
