#!/bin/bash

vendor/bin/php-cs-fixer fix --using-cache=no --config=.cs_format.php_cs $@
