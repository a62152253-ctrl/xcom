#!/bin/bash
# Remove empty rules in style.css which are likely the warnings
sed -i '/{\s*}/d' assets/css/style.css
