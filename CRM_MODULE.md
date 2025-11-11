# CRM Module - Setup and Testing Guide

## 📋 What Was Created

### 1. Database Migration
**File:** `database/migrations/006_crm_leads.sql`
- Creates 3 tables: `leads`, `lead_activities`, `lead_appointments`
- Includes sample data (2 test leads)

### 2. Backend Files
**Model:** `models/Lead.php`
- CRUD operations (Create, Read, Update, Delete)
- Filter by stage, status, assigned user, source
- Star toggle functionality
- Activity tracking

**Controller:** `controllers/LeadController.php`
- JSON API for DataTables
- Handles all AJAX requests (create, update, delete, toggle star)
- CSRF protection enabled

### 3. Frontend Files
**View:** `crm.php`
- Main CRM page with stage tabs
- Lead management table
- Add/Edit lead modal form
- Quick entry sections

**JavaScript:** `assets/js/crm.js`
- DataTables initialization
- Stage filtering
- Sorting (newest/oldest/starred)
- Add/Edit/Delete operations
- Star toggle
- Global search

### 4. Navigation
**Updated:** `includes/header.php`
- CRM link now points to `/crm.php`

## 🚀 How to Setup

### Step 1: Run Database Migration
```sql
-- Run this in phpMyAdmin or MySQL
SOURCE c:/xampp/htdocs/biziverse-clone/database/migrations/006_crm_leads.sql;
```

Or manually:
1. Open phpMyAdmin
2. Select your database
3. Go to SQL tab
4. Copy entire contents of `006_crm_leads.sql`
5. Execute

### Step 2: Access CRM Module
1. Login to your Biziverse clone
2. Click "CRM" in the Sales section (left sidebar)
3. You should see the Leads & Prospects page

## ✅ Features Implemented

### Core Features
- ✅ Lead creation with full form (business name, contact, email, phone, WhatsApp, etc.)
- ✅ Stage management (Raw, New, Discussion, Demo, Proposal, Decided, Inactive)
- ✅ Assignment to team members
- ✅ Priority levels (Low, Medium, High, Urgent)
- ✅ Star/favorite leads
- ✅ Requirements and notes tracking
- ✅ WhatsApp integration (clickable links)
- ✅ Source tracking (Mail, Call, Website, IndiaMART, etc.)
- ✅ Address management (City, State, Pincode)

### DataTable Features
- ✅ Server-side processing
- ✅ Pagination (10, 25, 50, 100)
- ✅ Global search
- ✅ Column sorting
- ✅ Stage filtering via tabs
- ✅ Sort by newest/oldest
- ✅ Star leads filter
- ✅ Record count display

### UI Features
- ✅ Bootstrap 5 modal for add/edit
- ✅ Select2 for user assignment dropdown
- ✅ Stage badges (colored labels)
- ✅ Edit/Delete action buttons
- ✅ Toast notifications (success/error)
- ✅ Responsive table
- ✅ Clean Biziverse-style design

## 🧪 Testing Checklist

### 1. Database
- [ ] Migration ran successfully
- [ ] Sample leads visible in `leads` table
- [ ] All 3 tables created properly

### 2. Basic Operations
- [ ] CRM page loads without errors
- [ ] Sample leads appear in table
- [ ] Click "Add Lead" button opens modal
- [ ] Fill form and save creates new lead
- [ ] Click edit button loads lead data
- [ ] Update lead saves changes
- [ ] Delete lead removes from table

### 3. Filtering & Sorting
- [ ] Click stage tabs filters leads
- [ ] "Newest First" sorts correctly
- [ ] "Oldest First" sorts correctly
- [ ] "Star Leads" shows only starred
- [ ] Global search finds leads

### 4. Features
- [ ] Star toggle works (click star icon)
- [ ] WhatsApp icon clickable (if number exists)
- [ ] Stage badges display with colors
- [ ] Assigned user shows in table
- [ ] Record count updates correctly

### 5. Form Validation
- [ ] Business name is required (can't save empty)
- [ ] All optional fields work
- [ ] Select2 dropdown works for assignment
- [ ] Close button cancels without saving

## 📊 Sample Data Included

1. **Team Rustic**
   - Contact: Aman Jain
   - Email: aman@teamrustic.com
   - Stage: Discussion
   - Source: Website

2. **URBANWRK PVT LTD**
   - Contact: Rahul Singh
   - Email: rahul@urbanwrk.com
   - Stage: Proposal
   - Source: Referral

## 🔧 Troubleshooting

### Table doesn't load / shows "Loading..."
1. Check browser console (F12) for errors
2. Verify CSRF token is working
3. Check `controllers/LeadController.php` for errors
4. Verify database connection

### Modal doesn't open
1. Check if Bootstrap JS is loaded
2. Check browser console for errors
3. Verify jQuery is loaded before crm.js

### Save button does nothing
1. Check browser console for AJAX errors
2. Verify CSRF token in form
3. Check server PHP error logs
4. Verify controller action exists

### Users not showing in "Assign To" dropdown
1. Check if `users` table has active users
2. Verify database query in crm.php modal section
3. Check Select2 initialization

## 🎯 Next Steps

### Completed ✅
- CRM module fully functional

### Coming Soon 🚀
- Quotes module
- Orders module
- Invoices module
- Recovery module
- Contracts module

### Future Enhancements (CRM)
- Kanban board view
- Appointments calendar
- Activity timeline
- Lead import from Excel
- B2B platform integrations (IndiaMART, etc.)
- Advanced filters
- Lead conversion to customer
- Email integration
- SMS notifications

## 📝 Notes

- All files follow existing project structure
- Uses same auth system as other modules
- CSRF protection enabled on all forms
- Server-side validation included
- Error logging implemented
- Responsive design (mobile-friendly)
- Consistent with Biziverse UI/UX

## 🐛 Known Limitations

1. Import/Export features are placeholders (coming soon alerts)
2. Kanban view not yet implemented
3. Appointments view not yet implemented
4. Advanced filters not yet implemented
5. Activity tracking exists in model but no UI yet
6. No lead conversion to customer yet

---

**Created:** CRM Module for Biziverse Clone
**Status:** Ready for testing
**Files Modified:** 4 created, 1 updated
