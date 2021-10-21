#!/bin/bash

cp -r vendor/factoryhr/foi_workshop_2021_framework/public .
cp -r vendor/factoryhr/foi_workshop_2021_framework/src/Resources/ src/
cp vendor/factoryhr/foi_workshop_2021_framework/.env.example .env
mkdir "src/Controller"
mkdir "src/Model"
mkdir "src/templates"
