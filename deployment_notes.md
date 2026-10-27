# Client Delivery Portal Deployment Notes

## 1. Files to Upload
Please upload the following files from your local environment to the same locations on your server:

- `admin_dashboard.php`
- `admin_client_project_edit.php` (Updated for modal & passcode support)
- `admin_upload_handler.php`
- `delivery.php` (Updated for CTA & passcode support)
- `download_project.php`
- `migrate_deliveries.php` (Migration script - handles new passcode column)

**Note:** Ensure the directory `uploads/projects/` exists on the server and is writable.

## 2. Database Migration
Run the migration script via your browser or terminal to add the new `passcode` column:

**Via Browser:**
Visit `http://your-domain.com/jlm/migrate_deliveries.php`

**Via Terminal:**
```bash
php migrate_deliveries.php
```

*After running the script successfully, please delete `migrate_deliveries.php` from your server.*

## 3. New Features
1. **Optional Passcode**: You can now set a password for each delivery. Clients will see a secure login screen before they can access the files.
2. **Appreciation Section**: The delivery page now includes a professional thank-you note and links back to your main website and services.
3. **Integrated Modals**: All management happens via smooth, premium-styled modals on the dashboard.
