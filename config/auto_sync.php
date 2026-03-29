<?php
// =========================================================================
// Auto-Sync Database Script
// This script automatically dumps the MySQL database to `database/BookYourShow.sql`
// whenever state-modifying requests (like POST or deletions) are made.
// This ensures that when the folder is copied to another device,
// the `.sql` file always contains the latest data seamlessly!
// =========================================================================

function sync_database_to_file() {
    // Dynamically find the XAMPP directory based on the project path
    $xampp_base = stristr(__DIR__, 'htdocs', true);
    if (!$xampp_base) {
        $xampp_base = 'C:\\xampp\\'; // Fallback
    }
    
    // Path to mysqldump.exe
    $mysqldump = rtrim($xampp_base, '\\/') . DIRECTORY_SEPARATOR . 'mysql' . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'mysqldump.exe';
    
    // Only proceed if mysqldump exists
    if (file_exists($mysqldump) && defined('DB_HOST')) {
        $backup_file = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'database' . DIRECTORY_SEPARATOR . 'BookYourShow.sql';
        
        $pass_str = (defined('DB_PASS') && DB_PASS !== '') ? '-p"' . DB_PASS . '"' : '';
        
        // Build the command
        $cmd = "\"$mysqldump\" -h \"" . DB_HOST . "\" -u \"" . DB_USER . "\" $pass_str \"" . DB_NAME . "\" > \"$backup_file\"";
        
        // Execute the command synchronously
        exec($cmd);
    }
}

// Register this function to run at the absolute end of the script execution,
// AFTER all database inserts/updates have completely finished.
register_shutdown_function(function() {
    // Only run on state-changing requests, or occasionally on normal loads
    $is_post = ($_SERVER['REQUEST_METHOD'] === 'POST');
    // Common GET actions that modify data
    $is_delete = false;
    foreach ($_GET as $key => $val) {
        if (stripos($key, 'delete') !== false || stripos($key, 'action') !== false || stripos($key, 'approve') !== false || stripos($key, 'reject') !== false) {
            $is_delete = true;
            break;
        }
    }
    
    if ($is_post || $is_delete || rand(1, 10) === 1) {
        sync_database_to_file();
    }
});
?>
