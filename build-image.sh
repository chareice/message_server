#!/bin/bash
. config-default.sh
docker build -t $imageName .
