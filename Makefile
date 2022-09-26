# 单元测试运行镜像
RUNTIME_IMAGE := anoxia/php-swoole:7.4-alpine3.12

OS := $(shell uname | awk '{print tolower($$0)}')
MACHINE := $(shell uname -m)

# 基础路径
BASE_DIR := $(shell pwd)

.PHONY: help
## help: 打印帮助信息
help:
	@echo "使用说明:"
	@sed -n 's/^##//p' ${MAKEFILE_LIST} | column -t -s ':' |  sed 's/^/ /'

## dev: 启动本地开发环境
.PHONY: dev
dev:
	@watchexec -w src -w tests make unit-test

## unit-test: 单元测试
.PHONY: unit-test
unit-test: info
	@docker run --rm -v `pwd`:/app $(RUNTIME_IMAGE) sh /app/tests/run.sh

info:
	@echo "> 环境信息"
	@echo 'basedir:' $(BASE_DIR)
	@echo 'os:     ' $(OS)
	@echo 'arch:   ' $(MACHINE)
	@echo ""

