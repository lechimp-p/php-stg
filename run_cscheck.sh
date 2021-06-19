#!/bin/bash

vendor/bin/php-cs-fixer fix --dry-run --using-cache=no --config=.cs_format.php_cs $@
