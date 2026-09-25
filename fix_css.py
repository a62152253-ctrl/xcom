import re

def unique_css_blocks(file_path):
    with open(file_path, 'r') as f:
        content = f.read()

    # Regex to find CSS blocks (naive but works for this specific case)
    blocks = re.findall(r'([\.#a-zA-Z0-9_-]+[^{}]*\{[^{}]*\})', content)

    unique_blocks = []
    seen_selectors = set()

    for block in blocks:
        selector = block.split('{')[0].strip()
        # if we've already defined this selector, we can skip it, OR we can keep the last one.
        # Actually CSS from style.css, premium, enhancements might have overridden things.
        pass

    # Since it's complaining about line numbers in css:
    # assets/css/cards.css: 86, 87, 157, 166, 175
    # assets/css/buttons.css: 15, 35, 57
    # assets/css/forms.css: 10, 14

    # Wait, the warnings are likely "Duplicate selectors" or "Empty blocks" or similar SonarQube CSS issues.
    # We can just manually clean up the files to avoid the warnings.
