#!/bin/bash
sudo -u www-data php app/console cache:clear --env=dev
sudo rm -r app/cache/dev/*
sudo -u www-data php app/console cache:warmup --env=dev
