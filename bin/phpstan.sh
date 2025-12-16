#! /bin/bash

SCRIPT_DIR=$(dirname "${BASH_SOURCE[0]}")
PROJECT_DIR=$SCRIPT_DIR/../../../..
BIN_DIR=$SCRIPT_DIR/../../../bin
STANDARD_FILEPATH=$SCRIPT_DIR/../php-standard.neon

if [[ -d $PROJECT_DIR/src ]]; then
	$BIN_DIR/phpstan analyse $PROJECT_DIR/src --configuration=$STANDARD_FILEPATH --memory-limit=1G
fi

if [[ -d $PROJECT_DIR/includes ]]; then
	$BIN_DIR/phpstan analyse $PROJECT_DIR/includes --configuration=$STANDARD_FILEPATH --memory-limit=1G
fi

if [[ -d $PROJECT_DIR/tests ]]; then
	$BIN_DIR/phpstan analyse $PROJECT_DIR/tests --configuration=$STANDARD_FILEPATH --memory-limit=1G
fi