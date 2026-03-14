const fs = require('fs');
const path = require('path');

const portalDir = 'C:\\xampp\\htdocs\\rooqflow\\portal';
const folders = ['clients', 'users', 'finance', 'contracts', 'api', 'communication', 'admin', 'profile'];

const targetRootFolders = ['public', 'management', 'contract', 'assets', 'app'];

folders.forEach(folder => {
    const dirPath = path.join(portalDir, folder);
    if (!fs.existsSync(dirPath)) return;
    
    fs.readdirSync(dirPath).forEach(file => {
        if (!file.endsWith('.php')) return;
        
        const filePath = path.join(dirPath, file);
        let content = fs.readFileSync(filePath, 'utf8');
        let originalContent = content;

        // Replace `require_once '../app...` to `require_once '../../app...` if missing __DIR__
        // Wait, the safest Regex is to just look for `../` followed by one of the target root folders
        // Except if it's already `../../`
        
        for (const rootFolder of targetRootFolders) {
            // Regex match `../rootFolder` but STRICTLY NOT `../../rootFolder`
            // Using negative lookbehind or just simple replace if we carefully match `['"]../rootFolder`
            
            // For strings: '../public/login' -> '../../public/login'
            const regexStr = new RegExp(`(['"])\\.\\.\\/${rootFolder}`, 'g');
            content = content.replace(regexStr, `$1../../${rootFolder}`);
            
            // Also catch header("Location: ../public/login");
            const regexLoc = new RegExp(`(Location:\\s*)\\.\\.\\/${rootFolder}`, 'g');
            content = content.replace(regexLoc, `$1../../${rootFolder}`);
        }
        
        // Also fix the case where header function was changed accidentally to redirect inside the portal
        // Wait, header("Location: clients.php") in client-add.php should be fine if it's "clients", it resolves to clients.php in same dir.

        if (content !== originalContent) {
            fs.writeFileSync(filePath, content);
            console.log(`Updated relative root paths in ${folder}/${file}`);
        }
    });
});
