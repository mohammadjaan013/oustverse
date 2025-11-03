# Deploying Biziverse ERP to InfinityFree

Complete step-by-step guide to deploy your Biziverse ERP clone on InfinityFree hosting for testing and demo purposes.

## 📋 Prerequisites

- GitHub account with your repository: https://github.com/mohammadjaan013/oustverse
- InfinityFree account (free hosting): https://infinityfree.net
- FTP client (FileZilla recommended): https://filezilla-project.org

## 🚀 Deployment Steps

### Step 1: Create InfinityFree Account

1. Go to https://infinityfree.net
2. Click **"Sign Up"** (top right)
3. Fill in registration details:
   - Email address
   - Password
4. Verify your email address

### Step 2: Create Hosting Account

1. After login, click **"Create Account"**
2. Fill in account details:
   - **Website Domain**: Choose one of these options:
     - Use a subdomain: `yourname.wuaze.com` (free subdomain)
     - Use your own domain (if you have one)
   - **Account Label**: `biziverse-erp` (for your reference)
3. Click **"Create Account"**
4. Wait 2-5 minutes for account activation

### Step 3: Note Your Credentials

After account creation, save these details (found in your account panel):

```
FTP Hostname: ftpupload.net
FTP Username: epiz_XXXXXXXX (your username)
FTP Password: (the password you set)
MySQL Hostname: sqlXXX.infinityfreeapp.com
MySQL Database: epiz_XXXXXXXX_biziverse
MySQL Username: epiz_XXXXXXXX
MySQL Password: (auto-generated, check in "MySQL Databases")
```

### Step 4: Prepare Your Files

1. **On your local machine**, navigate to your project:
   ```bash
   cd C:\xampp\htdocs\biziverse-clone
   ```

2. **Create a ZIP file** of your project:
   - Right-click on the `biziverse-clone` folder
   - Select "Send to" > "Compressed (zipped) folder"
   - Name it `biziverse-erp.zip`

   OR use command line:
   ```bash
   tar -czf biziverse-erp.zip *
   ```

### Step 5: Upload Files via FTP

#### Using FileZilla:

1. **Download and install FileZilla** from https://filezilla-project.org

2. **Connect to your hosting**:
   - Host: `ftpupload.net`
   - Username: `epiz_XXXXXXXX`
   - Password: (your FTP password)
   - Port: `21`
   - Click **"Quickconnect"**

3. **Navigate to the correct folder**:
   - On the remote server (right panel), navigate to `/htdocs/`
   - This is your public web directory

4. **Upload your files**:
   - **Option A**: Upload ZIP and extract online
     - Upload `biziverse-erp.zip` to `/htdocs/`
     - Use InfinityFree's File Manager to extract
   
   - **Option B**: Upload all files directly
     - Select all files in your local project
     - Drag and drop to `/htdocs/` folder
     - Wait for upload to complete (may take 10-30 minutes)

5. **File structure should look like**:
   ```
   /htdocs/
   ├── assets/
   ├── controllers/
   ├── database/
   ├── includes/
   ├── models/
   ├── index.php
   ├── login.php
   └── ... other files
   ```

### Step 6: Create MySQL Database

1. **Login to InfinityFree Control Panel**

2. **Go to "MySQL Databases"** section

3. **Create a new database** (if not already created):
   - Database name will be: `epiz_XXXXXXXX_biziverse`
   - Username: `epiz_XXXXXXXX`
   - Password: (shown in the panel)

4. **Note down these credentials**

### Step 7: Import Database Schema

#### Method 1: Using phpMyAdmin (Recommended)

1. **Access phpMyAdmin**:
   - In InfinityFree control panel, click **"phpMyAdmin"**
   - Login with your MySQL credentials
   
2. **Select your database**:
   - Click on `epiz_XXXXXXXX_biziverse` in left sidebar

3. **Import SQL files**:
   - Click **"Import"** tab
   - Choose file: Start with `database/schema.sql`
   - Click **"Go"**
   - Repeat for all migration files in order:
     1. `database/migrations/001_initial_schema.sql`
     2. `database/migrations/002_add_supplier_columns.sql`
     3. `database/migrations/003_purchase_orders.sql`
     4. `database/migrations/004_supplier_invoices.sql`

#### Method 2: Using MySQL Command (Alternative)

If you have SSH access (not available on free InfinityFree):
```bash
mysql -h sqlXXX.infinityfreeapp.com -u epiz_XXXXXXXX -p epiz_XXXXXXXX_biziverse < schema.sql
```

### Step 8: Configure Application

1. **Update config file**:
   - Using FTP or File Manager, navigate to `/htdocs/includes/`
   - Edit `config.php` (or copy from `config.sample.php`)
   - Update the following:

   ```php
   // Site Information
   define('SITE_NAME', 'Biziverse ERP');
   define('BASE_URL', 'http://yourname.wuaze.com'); // Your actual domain

   // Database Configuration
   define('DB_HOST', 'sqlXXX.infinityfreeapp.com'); // Your MySQL hostname
   define('DB_NAME', 'epiz_XXXXXXXX_biziverse');    // Your database name
   define('DB_USER', 'epiz_XXXXXXXX');              // Your MySQL username
   define('DB_PASS', 'your_mysql_password');        // Your MySQL password

   // Error Reporting (IMPORTANT: Set to 0 for production)
   error_reporting(0);
   ini_set('display_errors', 0);
   ```

2. **Save the file**

### Step 9: Set File Permissions

Using FileZilla or File Manager:

1. **Right-click on the following folders** and set permissions to `755`:
   - `/htdocs/uploads/`
   - `/htdocs/tmp/` (if exists)
   - `/htdocs/cache/` (if exists)

2. **For files**, set permissions to `644`:
   - All `.php` files
   - All `.html` files

### Step 10: Test Your Application

1. **Open your browser** and navigate to:
   ```
   http://yourname.wuaze.com
   ```

2. **You should see the login page**

3. **Test the following**:
   - Login functionality
   - Navigate to different modules
   - Create a test supplier
   - Create a test purchase order
   - Create a test invoice

### Step 11: Create Demo User (Optional)

If you want to create a demo user:

1. **Access phpMyAdmin**
2. **Run this SQL**:
   ```sql
   INSERT INTO users (name, email, password, role, active) 
   VALUES (
     'Demo User', 
     'demo@biziverse.com', 
     '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', -- password: password
     'admin', 
     1
   );
   ```

3. **Login credentials**:
   - Email: `demo@biziverse.com`
   - Password: `password`

## 🔧 Troubleshooting

### Issue 1: Database Connection Error

**Error**: "Could not connect to database"

**Solution**:
- Double-check `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS` in `config.php`
- Ensure MySQL database is created
- Check if MySQL service is running in InfinityFree panel

### Issue 2: Blank White Page

**Error**: White page with no content

**Solution**:
- Enable error reporting temporarily:
  ```php
  error_reporting(E_ALL);
  ini_set('display_errors', 1);
  ```
- Check PHP error logs in InfinityFree control panel
- Ensure all files are uploaded correctly
- Check file permissions

### Issue 3: 404 Error on Pages

**Error**: "Not Found" when clicking links

**Solution**:
- Create `.htaccess` file in root directory:
  ```apache
  RewriteEngine On
  RewriteBase /
  
  # Remove .php extension
  RewriteCond %{REQUEST_FILENAME} !-f
  RewriteRule ^([^\.]+)$ $1.php [NC,L]
  ```

### Issue 4: Slow Performance

**Reason**: InfinityFree has resource limitations

**Solutions**:
- Optimize database queries
- Add caching mechanisms
- Minimize external API calls
- Use CDN for assets (already using CDN for Bootstrap, jQuery)
- Consider upgrading to paid hosting for better performance

### Issue 5: Session Not Working

**Error**: Login works but redirects back to login

**Solution**:
- Check session configuration in `config.php`
- Ensure `session_start()` is called in `auth.php`
- Check if cookies are enabled
- Clear browser cache and cookies

## 📊 InfinityFree Limitations

Be aware of these free hosting limitations:

| Resource | Limit |
|----------|-------|
| Disk Space | 5 GB |
| Bandwidth | Unlimited |
| Databases | 400 |
| MySQL Size | 1 GB per database |
| Hits | 50,000 per day |
| FTP Accounts | 2 |
| Email Accounts | 10 |
| PHP Version | 8.0/8.1 |
| File Upload | 10 MB max |

## 🎯 Performance Optimization Tips

1. **Enable Compression**:
   Add to `.htaccess`:
   ```apache
   <IfModule mod_deflate.c>
     AddOutputFilterByType DEFLATE text/html text/plain text/xml text/css text/javascript application/javascript
   </IfModule>
   ```

2. **Browser Caching**:
   Add to `.htaccess`:
   ```apache
   <IfModule mod_expires.c>
     ExpiresActive On
     ExpiresByType image/jpg "access 1 year"
     ExpiresByType image/jpeg "access 1 year"
     ExpiresByType image/gif "access 1 year"
     ExpiresByType image/png "access 1 year"
     ExpiresByType text/css "access 1 month"
     ExpiresByType application/javascript "access 1 month"
   </IfModule>
   ```

3. **Minimize Database Queries**:
   - Use pagination
   - Add indexes to frequently queried columns
   - Cache common queries

## 🔐 Security Recommendations

Before going live with real data:

1. **Change Default Credentials**:
   - Update all default passwords
   - Use strong, unique passwords

2. **SSL Certificate**:
   - InfinityFree provides free SSL
   - Enable it in control panel
   - Update `BASE_URL` to use `https://`

3. **Secure File Uploads**:
   - Validate file types
   - Limit file sizes
   - Store uploads outside web root if possible

4. **Disable Error Display**:
   ```php
   error_reporting(0);
   ini_set('display_errors', 0);
   ```

5. **Add .htaccess Protection**:
   ```apache
   # Prevent directory browsing
   Options -Indexes
   
   # Protect includes directory
   <Files ~ "\.php$">
     Order allow,deny
     Deny from all
   </Files>
   ```

## 📱 Sharing Demo with Others

### Option 1: Direct Link
Share your URL: `http://yourname.wuaze.com`

### Option 2: Create Demo Account
1. Create a user with limited permissions
2. Share credentials:
   - URL: `http://yourname.wuaze.com`
   - Email: `demo@yourname.com`
   - Password: `demo123`

### Option 3: Create Landing Page
Create an `index.html` with:
```html
<!DOCTYPE html>
<html>
<head>
    <title>Biziverse ERP Demo</title>
</head>
<body>
    <h1>Welcome to Biziverse ERP Demo</h1>
    <p>Test Credentials:</p>
    <ul>
        <li>Email: demo@biziverse.com</li>
        <li>Password: password</li>
    </ul>
    <a href="login.php">Login Here</a>
</body>
</html>
```

## 🆙 Upgrading from Free to Paid Hosting

When ready for production, consider:

1. **Shared Hosting** ($3-10/month):
   - Hostinger, Bluehost, SiteGround
   - Better performance
   - Email support
   - More resources

2. **VPS Hosting** ($10-50/month):
   - DigitalOcean, Linode, Vultr
   - Full control
   - Better security
   - Scalable resources

3. **Cloud Hosting**:
   - AWS, Google Cloud, Azure
   - Pay as you go
   - Enterprise-grade
   - Global infrastructure

## 📞 Support

If you encounter issues:

1. Check InfinityFree forums: https://forum.infinityfree.net
2. Check GitHub Issues: https://github.com/mohammadjaan013/oustverse/issues
3. Contact: mohammadjaan013@gmail.com

## ✅ Deployment Checklist

- [ ] InfinityFree account created
- [ ] Hosting account activated
- [ ] Files uploaded via FTP
- [ ] MySQL database created
- [ ] Database schema imported
- [ ] `config.php` updated with correct credentials
- [ ] File permissions set correctly
- [ ] Application tested in browser
- [ ] Demo user created
- [ ] SSL enabled (optional but recommended)
- [ ] Error reporting disabled
- [ ] Performance optimized
- [ ] Security measures implemented
- [ ] Demo URL shared with testers

---

**Congratulations!** 🎉 Your Biziverse ERP is now live on InfinityFree!

For production deployment, always use paid hosting with proper backups and security measures.
