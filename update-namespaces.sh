#!/bin/bash

# Update all PHP files to use new namespace
find /Users/x/Coding/McDart_Coding/MagicAnalytics/NfxMagicAnalytics -name "*.php" -type f -exec sed -i '' \
  -e 's/namespace Cbax\\ModulAnalytics/namespace Nfx\\MagicAnalytics/g' \
  -e 's/use Cbax\\ModulAnalytics/use Nfx\\MagicAnalytics/g' \
  -e 's/Cbax\\ModulAnalytics\\/Nfx\\MagicAnalytics\\/g' {} \;

# Update services.xml
sed -i '' 's/Cbax\\ModulAnalytics/Nfx\\MagicAnalytics/g' /Users/x/Coding/McDart_Coding/MagicAnalytics/NfxMagicAnalytics/src/Resources/config/services.xml

# Update routes.xml  
sed -i '' 's/Cbax\\ModulAnalytics/Nfx\\MagicAnalytics/g' /Users/x/Coding/McDart_Coding/MagicAnalytics/NfxMagicAnalytics/src/Resources/config/routes.xml

# Update config.xml
sed -i '' 's/CbaxModulAnalytics/NfxMagicAnalytics/g' /Users/x/Coding/McDart_Coding/MagicAnalytics/NfxMagicAnalytics/src/Resources/config/config.xml

echo "Namespace update complete!"