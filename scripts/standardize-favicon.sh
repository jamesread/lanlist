#!/usr/bin/env bash

for f in *.ico
do
	convert -resize 16x16 $f[0] ../resources/images/organizer-favicons/${f%.*}.png 
done
