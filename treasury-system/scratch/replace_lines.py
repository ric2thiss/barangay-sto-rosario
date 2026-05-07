import sys

def replace_lines(file_path, start_line, end_line, replacement):
    with open(file_path, 'r', encoding='utf-8') as f:
        lines = f.readlines()
    
    # lines are 0-indexed in list, but 1-indexed in task
    start_idx = start_line - 1
    end_idx = end_line
    
    new_lines = lines[:start_idx] + [replacement + '\n'] + lines[end_idx:]
    
    with open(file_path, 'w', encoding='utf-8') as f:
        f.writelines(new_lines)

if __name__ == "__main__":
    file_path = sys.argv[1]
    start_line = int(sys.argv[2])
    end_line = int(sys.argv[3])
    replacement = sys.argv[4]
    replace_lines(file_path, start_line, end_line, replacement)
