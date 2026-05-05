import sys

def replace_lines_from_file(target_file, start_line, end_line, replacement_file):
    with open(target_file, 'r', encoding='utf-8') as f:
        target_lines = f.readlines()
    
    with open(replacement_file, 'r', encoding='utf-8') as f:
        replacement_content = f.read()
    
    start_idx = start_line - 1
    end_idx = end_line
    
    new_lines = target_lines[:start_idx] + [replacement_content] + target_lines[end_idx:]
    
    with open(target_file, 'w', encoding='utf-8', newline='') as f:
        f.writelines(new_lines)

if __name__ == "__main__":
    replace_lines_from_file(sys.argv[1], int(sys.argv[2]), int(sys.argv[3]), sys.argv[4])
