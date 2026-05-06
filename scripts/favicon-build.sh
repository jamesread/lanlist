#!/usr/bin/env bash

for f in *.ico
do
	magick convert -resize 16x16 $f[0] ../public/resources/images/organizer-favicons/${f%.*}.png 
done
