files_and_lines = {
    "includes/modals/project_modal.php": [21],
    "assets/css/cards.css": [91, 92, 93, 162],
    "assets/js/components/ajax_helper.js": [21, 22, 23]
}

for filepath, lines in files_and_lines.items():
    print(f"\n--- {filepath} ---")
    try:
        with open(filepath, "r") as f:
            content = f.readlines()
        for line in lines:
            if line - 1 < len(content):
                print(f"Line {line}: {content[line-1].strip()}")
            else:
                print(f"Line {line}: (Out of bounds)")
    except Exception as e:
        print(f"Error reading file: {e}")
