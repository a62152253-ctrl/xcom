files_and_lines = {
    "includes/modals/task_modal.php": [10, 19, 21, 33, 34, 44, 45, 53, 54, 63, 64],
    "includes/modals/project_modal.php": [9, 10, 13, 14, 17, 21, 28],
    "assets/css/cards.css": [86, 87, 157, 166, 175],
    "assets/css/buttons.css": [15, 35, 57],
    "assets/css/forms.css": [10, 14],
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
