#!/usr/bin/env python3
import os
import re
import shutil

plugin_dir = "/Users/x/Coding/McDart_Coding/MagicAnalytics/NfxMagicAnalytics"

replacements = {
    # PHP namespaces
    r'namespace Cbax\\ModulAnalytics': 'namespace Nfx\\MagicAnalytics',
    r'use Cbax\\ModulAnalytics': 'use Nfx\\MagicAnalytics',
    r'Cbax\\\\ModulAnalytics': 'Nfx\\\\MagicAnalytics',
    r'Cbax\\ModulAnalytics': 'Nfx\\MagicAnalytics',
    
    # Table names
    r'cbax_analytics_': 'nfx_analytics_',
    
    # API routes
    r'/api/cbax/analytics': '/api/nfx/analytics',
    r'api\.cbax\.analytics': 'api.nfx.analytics',
    r'cbax\.analytics': 'nfx.analytics',
    
    # Module names
    r'cbax-analytics': 'nfx-analytics',
    r'cbaxAnalytics': 'nfxAnalytics',
    r'CbaxModulAnalytics': 'NfxMagicAnalytics',
    
    # Author
    r'Coolbax': 'nfx:MEDIA',
}

def process_file(filepath):
    try:
        with open(filepath, 'r', encoding='utf-8') as f:
            content = f.read()
        
        original_content = content
        for pattern, replacement in replacements.items():
            content = re.sub(pattern, replacement, content)
        
        if content != original_content:
            with open(filepath, 'w', encoding='utf-8') as f:
                f.write(content)
            print(f"Updated: {filepath}")
    except Exception as e:
        print(f"Error processing {filepath}: {e}")

# Process all files
for root, dirs, files in os.walk(plugin_dir):
    for file in files:
        if file.endswith(('.php', '.js', '.json', '.xml', '.twig', '.scss', '.css')):
            filepath = os.path.join(root, file)
            process_file(filepath)

# Rename directories
dirs_to_rename = []
for root, dirs, files in os.walk(plugin_dir):
    for dir_name in dirs:
        if 'cbax-analytics' in dir_name:
            old_path = os.path.join(root, dir_name)
            new_name = dir_name.replace('cbax-analytics', 'nfx-analytics')
            new_path = os.path.join(root, new_name)
            dirs_to_rename.append((old_path, new_path))

# Sort by depth (deepest first) to avoid conflicts
dirs_to_rename.sort(key=lambda x: x[0].count(os.sep), reverse=True)

for old_path, new_path in dirs_to_rename:
    try:
        os.rename(old_path, new_path)
        print(f"Renamed directory: {old_path} -> {new_path}")
    except Exception as e:
        print(f"Error renaming {old_path}: {e}")

# Update docker-compose.yml
docker_compose = "/Users/x/Coding/McDart_Coding/MagicAnalytics/docker-compose.yml"
if os.path.exists(docker_compose):
    process_file(docker_compose)

print("\nRefactoring complete!")
print("\nNext steps:")
print("1. Uninstall CbaxModulAnalytics plugin")
print("2. Install and activate NfxMagicAnalytics plugin")
print("3. Clear cache and rebuild assets")