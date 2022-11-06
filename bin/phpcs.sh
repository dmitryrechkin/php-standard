#! /bin/bash

SCRIPT_DIR=$(dirname "${BASH_SOURCE[0]}")
PROJECT_DIR=$SCRIPT_DIR/../../../..
BIN_DIR=$SCRIPT_DIR/../../../bin
STANDARD_FILEPATH=$SCRIPT_DIR/../php-standard.xml

if [[ -d $PROJECT_DIR/src ]]; then
	$BIN_DIR/phpcs --report=code --parallel=7 --cache --standard=$STANDARD_FILEPATH $PROJECT_DIR/src
fi

if [[ -d $PROJECT_DIR/includes ]]; then
	$BIN_DIR/phpcs --report=code --parallel=7 --cache --standard=$STANDARD_FILEPATH $PROJECT_DIR/includes
fi

if [[ -d $PROJECT_DIR/tests ]]; then
	$BIN_DIR/phpcs --report=code --parallel=7 --cache --standard=$STANDARD_FILEPATH $PROJECT_DIR/tests
fi