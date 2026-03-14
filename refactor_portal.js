const fs = require('fs');
const path = require('path');

const portalDir = 'C:\\xampp\\htdocs\\rooqflow\\portal';

// Map of folder -> array of files
const structure = {
    'clients': ['clients.php', 'client-add.php', 'client-edit.php', 'client-finance.php'],
    'users': ['users.php', 'user-add.php', 'user-edit.php', 'user-delete.php'],
    'finance': ['expenses.php', 'payroll.php', 'user-payroll.php', 'audit-finance.php'],
    'contracts': ['default-contract.php', 'generate_contract.php'],
    'api': ['search_api.php', 'toggle_status_api.php'],
    'communication': ['chat.php'],
    'admin': ['settings.php', 'activity-logs.php'],
    'profile': ['profile.php']
};

console.log('Starting refactoring process...');

// 1. Create Directories and Move Files
for (const [folder, files] of Object.entries(structure)) {
    const folderPath = path.join(portalDir, folder);
    if (!fs.existsSync(folderPath)) {
        fs.mkdirSync(folderPath);
        console.log(`Created folder: ${folder}`);
    }

    for (const file of files) {
        const oldPath = path.join(portalDir, file);
        const newPath = path.join(folderPath, file);
        
        if (fs.existsSync(oldPath)) {
            fs.renameSync(oldPath, newPath);
            console.log(`Moved ${file} to ${folder}/`);
        }
    }
}

// 2. Update Includes in Moved Files
for (const [folder, files] of Object.entries(structure)) {
    const folderPath = path.join(portalDir, folder);
    
    for (const file of files) {
        const filePath = path.join(folderPath, file);
        if (!fs.existsSync(filePath)) continue;

        let content = fs.readFileSync(filePath, 'utf8');
        let originalContent = content;

        // Update includes directory
        content = content.replace(/require_once\s+['"]includes\/(header|footer|sidebar)\.php['"]\s*;/g, "require_once '../includes/$1.php';");
        content = content.replace(/require_once\s+['"]\.\.\/includes\/(header|footer|sidebar)\.php['"]\s*;/g, "require_once '../includes/$1.php';");
        
        content = content.replace(/include\s+['"]includes\/(header|footer|sidebar)\.php['"]\s*;/g, "include '../includes/$1.php';");
        
        // Update App directory includes (__DIR__ . '/../app -> __DIR__ . '/../../app)
        // This regex catches __DIR__ . '/../app/Config/Database.php' etc.
        content = content.replace(/__DIR__\s*\.\s*['"]\/\.\.\/app/g, "__DIR__ . '/../../app");

        if (content !== originalContent) {
            fs.writeFileSync(filePath, content);
            console.log(`Updated includes in ${folder}/${file}`);
        }
    }
}

console.log('Refactoring complete.');
