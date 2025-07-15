#!/bin/bash

PLUGIN_DIR="/Users/x/Coding/McDart_Coding/MagicAnalytics/NfxMagicAnalytics"

echo "Starting refactoring from Cbax to Nfx..."

# Update PHP files
echo "Updating PHP namespaces..."
find "$PLUGIN_DIR" -name "*.php" -type f | while read file; do
    # Update namespaces
    sed -i '' 's/namespace Cbax\\ModulAnalytics/namespace Nfx\\MagicAnalytics/g' "$file"
    sed -i '' 's/use Cbax\\ModulAnalytics/use Nfx\\MagicAnalytics/g' "$file"
    sed -i '' 's/Cbax\\\\ModulAnalytics/Nfx\\\\MagicAnalytics/g' "$file"
    
    # Update table names
    sed -i '' 's/cbax_analytics_/nfx_analytics_/g' "$file"
    
    # Update API routes
    sed -i '' 's|/api/cbax/analytics|/api/nfx/analytics|g' "$file"
    sed -i '' 's|api\.cbax\.analytics|api.nfx.analytics|g' "$file"
    sed -i '' 's|cbax\.analytics|nfx.analytics|g' "$file"
done

# Update XML files
echo "Updating XML configuration files..."
for file in "$PLUGIN_DIR/src/Resources/config/"*.xml; do
    if [ -f "$file" ]; then
        # Update namespaces
        sed -i '' 's/Cbax\\ModulAnalytics/Nfx\\MagicAnalytics/g' "$file"
        sed -i '' 's/CbaxModulAnalytics/NfxMagicAnalytics/g' "$file"
        
        # Update table names
        sed -i '' 's/cbax_analytics_/nfx_analytics_/g' "$file"
        
        # Update routes
        sed -i '' 's|/cbax/analytics|/nfx/analytics|g' "$file"
        sed -i '' 's|api\.cbax\.analytics|api.nfx.analytics|g' "$file"
        sed -i '' 's|cbax\.analytics|nfx.analytics|g' "$file"
    fi
done

# Update JavaScript files
echo "Updating JavaScript files..."
find "$PLUGIN_DIR/src/Resources/app/administration" -name "*.js" -type f | while read file; do
    # Update module names
    sed -i '' 's/cbax-analytics/nfx-analytics/g' "$file"
    sed -i '' 's/cbax\.analytics/nfx.analytics/g' "$file"
    sed -i '' 's/CbaxModulAnalytics/NfxMagicAnalytics/g' "$file"
    
    # Update API routes
    sed -i '' 's|/api/cbax/analytics|/api/nfx/analytics|g' "$file"
    sed -i '' 's|api\.cbax\.analytics|api.nfx.analytics|g' "$file"
done

# Update Twig files
echo "Updating Twig templates..."
find "$PLUGIN_DIR" -name "*.twig" -type f | while read file; do
    sed -i '' 's/cbax-analytics/nfx-analytics/g' "$file"
    sed -i '' 's/cbax\.analytics/nfx.analytics/g' "$file"
    sed -i '' 's/CbaxModulAnalytics/NfxMagicAnalytics/g' "$file"
done

# Update SCSS files
echo "Updating SCSS files..."
find "$PLUGIN_DIR" -name "*.scss" -type f | while read file; do
    sed -i '' 's/cbax-analytics/nfx-analytics/g' "$file"
done

# Update JSON files
echo "Updating JSON translation files..."
find "$PLUGIN_DIR" -name "*.json" -type f | while read file; do
    sed -i '' 's/cbax-analytics/nfx-analytics/g' "$file"
    sed -i '' 's/cbax\.analytics/nfx.analytics/g' "$file"
    sed -i '' 's/cbaxAnalytics/nfxAnalytics/g' "$file"
    sed -i '' 's/Coolbax/nfx:MEDIA/g' "$file"
done

# Rename directories
echo "Renaming directories..."
if [ -d "$PLUGIN_DIR/src/Resources/app/administration/src/module/cbax-analytics" ]; then
    mv "$PLUGIN_DIR/src/Resources/app/administration/src/module/cbax-analytics" \
       "$PLUGIN_DIR/src/Resources/app/administration/src/module/nfx-analytics"
fi

# Rename component directories
find "$PLUGIN_DIR/src/Resources/app/administration/src/module" -type d -name "*cbax-analytics*" | while read dir; do
    newdir=$(echo "$dir" | sed 's/cbax-analytics/nfx-analytics/g')
    if [ "$dir" != "$newdir" ]; then
        mv "$dir" "$newdir"
    fi
done

# Rename view directories
find "$PLUGIN_DIR/src/Resources/app/administration/src/module" -type d -name "*cbax-analytics*" | while read dir; do
    newdir=$(echo "$dir" | sed 's/cbax-analytics/nfx-analytics/g')
    if [ "$dir" != "$newdir" ]; then
        mv "$dir" "$newdir"
    fi
done

# Update plugin.png if needed (optional - you might want to replace with new logo)
echo "Note: You may want to replace plugin.png with your new logo"

# Update docker-compose.yml volume mount
if [ -f "/Users/x/Coding/McDart_Coding/MagicAnalytics/docker-compose.yml" ]; then
    echo "Updating docker-compose.yml..."
    sed -i '' 's|./CbaxModulAnalytics:|./NfxMagicAnalytics:|g' "/Users/x/Coding/McDart_Coding/MagicAnalytics/docker-compose.yml"
fi

echo "Refactoring complete!"
echo ""
echo "Next steps:"
echo "1. Uninstall CbaxModulAnalytics plugin"
echo "2. Install and activate NfxMagicAnalytics plugin"
echo "3. Clear cache and rebuild assets"