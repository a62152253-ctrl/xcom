import re

def remove_duplicates(filepath):
    with open(filepath, 'r') as f:
        content = f.read()

    # Split by the duplicate sections we noticed (since we just concatenated three files)
    # The first part (lines 1-13 in buttons.css) are the minified versions from style.css
    # Then line 15 starts the unminified versions from enhancements.css

    # We will just take the LAST definition of a selector if they are duplicated,
    # but the easiest way is just to manually use the unminified one (enhancements) and drop the minified one if they conflict.
    # Actually, Sonar is complaining because the selectors are duplicated!

    # Let's remove the minified lines from the top since enhancements.css overrides them anyway, or vice-versa.

    # For now, let's just parse and remove exact duplicate selectors
    blocks = re.findall(r'([^{}]+)\s*\{([^{}]*)\}', content)

    seen = {}

    # We want to preserve order, but overwrite if a selector is seen again.
    # CSS overrides work by taking the last definition.
    for selector, props in blocks:
        sel = selector.strip()
        # Some selectors are media queries, but in our split we don't have nested {} here because regex is simple
        seen[sel] = props

    new_css = []
    for sel, props in seen.items():
        new_css.append(f"{sel} {{{props}}}")

    with open(filepath, 'w') as f:
        f.write("\n".join(new_css))

remove_duplicates('assets/css/buttons.css')
remove_duplicates('assets/css/forms.css')
remove_duplicates('assets/css/cards.css')
