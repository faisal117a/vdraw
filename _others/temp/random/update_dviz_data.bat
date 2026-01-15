@echo off
echo Updating DViz Data Structure...
node scripts/generate_dviz_index.js
echo.
echo Update Complete! You can now open frontend/DViz/app.html to see new content.
pause
