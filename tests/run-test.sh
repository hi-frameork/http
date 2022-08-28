#!/bin/env sh

cd /app

# echo '> 运行 PHPStan'
# ./vendor/bin/phpstan analyze -c phpstan.src.neon.dist

# echo "> 运行单元测试"
# ./vendor/bin/phpunit --colors=always

echo "> 执行编码风格检查"
./vendor/bin/phpcs

echo "> 运行 Psalm"
./vendor/bin/psalm
