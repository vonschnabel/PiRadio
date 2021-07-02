#!/bin/bash

sleep 5

while [ true ]
do
  while  [[ ! -z $(eval "pgrep sox") ]]
  do
  : # do nothing
  done
  /bin/kill -INT $(eval "pgrep fm_transmitter")
  sleep 2
done
