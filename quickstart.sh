#!/bin/bash

java -jar floodlight/floodlight.jar -cf floodlight/fl.properties3 &
sudo ./mininet/mn.py -t tree | tee mininet_log.txt &
php -S localhost:55555 &
xdg-open http://localhost:55555;
