#!/usr/bin/env bash

# Run composer command through docker.
COMMAND="docker-compose exec php composer $@";
$COMMAND;
