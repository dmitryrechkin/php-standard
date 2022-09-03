#! /bin/bash

SCRIPT_DIR=$(dirname "${BASH_SOURCE[0]}")
PROJECT_DIR=$SCRIPT_DIR/../../../..
BIN_DIR=$SCRIPT_DIR/../../../bin

$BIN_DIR/phplint --exclude=vendor $PROJECT_DIR/
