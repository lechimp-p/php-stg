#!/bin/bash

vendor/bin/php-cs-fixer fix --dry-run --config=.cs_format.php_cs $@
