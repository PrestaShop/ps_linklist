#!/bin/bash
set -e

if [ $# -le 0 ]; then
  echo "No version provided. Use:"
  echo "tests/phpstan/phpstan.sh [PrestaShop_version]"
  exit 1
fi

PS_VERSION=$1
BASEDIR=$(dirname "$0")
MODULEDIR=$(cd $BASEDIR/.. && pwd)

if [ ! -f $MODULEDIR/tests/phpstan/phpstan-$PS_VERSION.neon ]; then
  echo "Configuration file for PrestaShop $PS_VERSION does not exist."
  echo "Please try another version."
  exit 2
fi

# Docker images prestashop/prestashop are used to get source files
echo "Pull PrestaShop files (Tag ${PS_VERSION})"

docker rm -f temp-ps || true
docker run -tid --rm --name temp-ps prestashop/prestashop:$PS_VERSION
until docker exec -t temp-ps ls /var/www/html/modules/ps_linklist 2>&1 > /dev/null; do echo 'Wait for extraction'; sleep 2; done

# Clear previous instance of the module in the PrestaShop volume
echo "Clear previous module and copy current one"
docker exec -t temp-ps rm -rf /var/www/html/modules/ps_linklist
docker exec -t temp-ps mkdir -p /var/www/html/modules/ps_linklist
docker cp $MODULEDIR temp-ps:/var/www/html/modules/
echo "Run PHPStan using phpstan-${PS_VERSION}.neon file"

docker exec \
      -e _PS_ROOT_DIR_=/var/www/html \
      --workdir=/var/www/html/modules/ps_linklist \
      temp-ps \
      ./vendor/bin/phpstan analyse \
      --configuration=/var/www/html/modules/ps_linklist/tests/phpstan/phpstan-$PS_VERSION.neon \
      "${@:2}"
