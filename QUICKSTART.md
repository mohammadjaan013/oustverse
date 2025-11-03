# 🚀 Quick Start Guide - Biziverse ERP

## ⚡ 5-Minute Setup

### For Local Development (Windows/XAMPP)

1. **Clone the repository**
   ```bash
   cd C:\xampp\htdocs
   git clone https://github.com/mohammadjaan013/oustverse.git biziverse-erp
   cd biziverse-erp
   ```

2. **Create database**
   - Open phpMyAdmin: `http://localhost/phpmyadmin`
   - Create database: `biziverse_erp`

3. **Import database**
   - In phpMyAdmin, select `biziverse_erp` database
   - Click "Import" tab
   - Import files in this order:
     1. `migrations/001_core_tables.sql`
     2. `database/migrations/002_add_supplier_columns.sql`
     3. `database/migrations/003_purchase_orders.sql`
     4. `database/migrations/004_supplier_invoices.sql`

4. **Configure application**
   ```bash
   copy includes\config.sample.php includes\config.php
   ```
   - Edit `includes/config.php` if needed (default settings work for XAMPP)

5. **Access application**
   - Open browser: `http://localhost/biziverse-erp`
   - Create your first user or use seed data

## 🌐 For InfinityFree Deployment

### Step-by-Step (15 minutes)

1. **Get hosting**
   - Sign up at https://infinityfree.net
   - Create hosting account
   - Choose subdomain (e.g., `yourname.wuaze.com`)

2. **Upload files**
   - Download project ZIP from GitHub
   - Upload via FTP to `/htdocs/` folder
   - OR use File Manager in InfinityFree panel

3. **Create MySQL database**
   - In InfinityFree panel → MySQL Databases
   - Note: hostname, database name, username, password

4. **Import database**
   - Access phpMyAdmin from InfinityFree panel
   - Select your database
   - Import all SQL files from `database/` folder

5. **Configure**
   - Edit `includes/config.php` via FTP
   - Update database credentials
   - Update `BASE_URL` to your domain

6. **Test**
   - Visit: `http://yourname.wuaze.com`
   - Login and test modules

**📖 Detailed instructions**: See [DEPLOYMENT.md](DEPLOYMENT.md)

## 🎯 What's Included

✅ **Inventory Management** - Products, stock tracking, movements
✅ **Suppliers** - Complete supplier database with contacts
✅ **Purchase Orders** - Create POs with approval workflow
✅ **Purchases** - Supplier invoices and payment tracking

## 🔑 Default Login

After running migrations, create a user:

```sql
INSERT INTO users (name, email, password, role, active) 
VALUES ('Admin', 'admin@biziverse.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 1);
```

Login: `admin@biziverse.com` / Password: `password`

## 📚 Documentation

- **README.md** - Project overview and features
- **DEPLOYMENT.md** - Complete InfinityFree deployment guide
- **ARCHITECTURE.md** - Technical architecture details
- **SETUP_GUIDE.md** - Detailed setup instructions

## 🆘 Need Help?

- 📧 Email: mohammadjaan013@gmail.com
- 🐛 Issues: https://github.com/mohammadjaan013/oustverse/issues
- 💬 Discussions: https://github.com/mohammadjaan013/oustverse/discussions

## 📱 Demo

Live demo: Coming soon!

## ⭐ Star this repo if you find it useful!
