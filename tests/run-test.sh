#!/bin/env sh

cd /app

echo '> 运行 PHPStan'
./vendor/bin/phpstan analyze -c phpstan.src.neon.dist

echo "> 运行单元测试"
./vendor/bin/phpunit
