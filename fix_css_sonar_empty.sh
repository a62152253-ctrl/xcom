#!/bin/bash
# includes/modals/project_modal.php has no other errors, now for style.css warnings:

# Remove empty blocks explicitly
sed -i 's/{\s*}//g' assets/css/style.css
sed -i '/^[[:space:]]*$/d' assets/css/style.css
