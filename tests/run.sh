#!/bin/env sh

cd /app

echo ""
echo "-> 运行 Psalm"
./vendor/bin/psalm

echo ""
echo "-> 运行单元测试"
./vendor/bin/phpunit --colors=always

echo ""
echo "-> 执行 php-cs-fixer"
./vendor/bin/php-cs-fixer fix --config=.php-cs-fixer.dist.php
