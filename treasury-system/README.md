# Barangay Sto. Rosario - Treasurer Management System

A comprehensive web-based treasurer management system designed for Barangay Sto. Rosario, Magallanes, Agusan del Norte.

## Features

### Dashboard

- Real-time statistics overview (Total Collections, Disbursements, Cedula Issued, Brgy Clearance, BIR Collections)
- Collections by Category chart (Tax Revenue, Tax on Goods & Services, Operating & Services, Other Collections)
- Recent transactions display (Payments and Disbursements)
- Quick access to all modules
- Interactive Chart.js visualizations

### Search Functionality

- Search payee/taxpayer across all records
- Unified search across Payments, Cedula, BIR, and Disbursements
- View full details toggle
- Transaction history summary
- Print-ready reports

### Payment Records

- Record payments for various services (Barangay Clearance, Business Permit, Barangay ID, etc.)
- Autocomplete with auto-fill for repeat customers
- Operating and Services categorization
- Track BIR fees and taxes
- Generate receipts with OR numbers
- Complete payment history

### Cedula Management

- Issue Community Tax Certificates (CTC)
- Store complete resident information (Address, Birth Date, TIN, Height, Weight, etc.)
- Autocomplete with full auto-fill from previous records
- Auto-generate cedula numbers
- OR Number tracking
- Nature of Collection field
- Track all issued cedulas

### BIR Records

- Manual input for 1% and 5% withholding tax
- **Withholding tax deduction system** (tax is deducted from gross amount, not added)
- Calculate Net Amount to Payee (Gross - Withholding Tax)
- Track TIN and payee information
- Total withholding tax computation
- Comprehensive BIR reports

### Disbursement Records

- Record all disbursements
- Track check numbers and DV numbers
- Monitor fund allocation
- Manage payroll and BIR deductions
- Release amount tracking
- Complete disbursement history

### Monthly Collections Report

- Itemized monthly collection statements
- **Flexible manual entry system** (add unlimited custom entries with category selection)
- Tax Revenue tracking (Real Property Tax, etc.)
- Tax on Goods & Services (Internal Revenue Allotment, etc.)
- Operating & Services (includes all Cedula payments)
- Other Collections
- Add/Edit/Delete manual entries
- Category-based organization
- Print-ready reports with totals and grand total

## Design Theme

The system features the official Barangay Sto. Rosario color scheme:

- **Primary Blue**: #1F3A93 (Dark Blue)
- **Secondary Blue**: #1e3a5f (Navy Blue)
- **White Text**: #ffffff on blue backgrounds for visibility
- Clean, modern, and responsive design
- Mobile-friendly interface
- Circular logo display in all pages (80x80px with white border)

## Installation

### Prerequisites

- XAMPP (or any PHP/MySQL environment)
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Modern web browser (Chrome, Firefox, Edge)

### Setup Steps

1. **Copy Project Files**

   ```
   Copy the project folder to: C:\xampp\htdocs\Treasurer_management_system
   ```

2. **Create Database**
   - Open phpMyAdmin (http://localhost/phpmyadmin)
   - Create a new database named `treasurer_management`

3. **Import Database Schema**
   - In phpMyAdmin, select the `treasurer_management` database
   - Click "Import" tab
   - Choose `database.sql` file from the project root
   - Click "Go" to import
   - **Sample data is included** (5 payments, 5 cedulas, 3 BIR records, 5 disbursements, 6 manual entries)

   **Existing database?**
   - If you already imported an older schema, run `update_forgot_password.sql` once to add email + OTP tables.

4. **Configure Database Connection**
   - Open `config/database.php`
   - Update credentials if needed (default: root, no password)

5. **Access the System**
   - Open browser and navigate to: `http://localhost/Treasurer_management_system`
   - Login with default credentials

6. **Forgot Password OTP Email (Free Setup)**
   - Install PHPMailer:

     ```
     composer require phpmailer/phpmailer
     ```

   - Open `config/mail.php` and set your SMTP credentials.
   - Make sure each user has a valid email in the `users` table.
   - Recommended free option: Gmail SMTP with an app password.

## Database Tables

### Core Tables (6 tables)

1. **users** - System users and authentication (MD5 password hashing)
2. **payments** - Payment records with operating_services field
3. **cedula** - Community Tax Certificates with OR numbers and nature_of_collection
4. **bir_records** - BIR withholding tax records (gross_amount, one_percent, five_percent, total_amount, net_amount)
5. **disbursements** - Disbursement records with check numbers and release amounts
6. **monthly_manual_entries** - Flexible manual entries for monthly report (unlimited custom entries with categories)

### Database Details

- **Character Set**: utf8mb4
- **Collation**: utf8mb4_general_ci
- **Foreign Keys**: Set on all related tables
- **Timestamps**: Automatic created_at and updated_at tracking

## Default Credentials

```
Username: treasurer
Password: treasurer123
```

**Important**: Change the default password after first login for security.

## Key Features & Workflows

### Auto-fill System

- Type a name in Payments or Cedula forms
- System searches across all records
- Click autocomplete suggestion to auto-fill all fields
- Saves time for repeat customers

### Manual Entry Management

- Navigate to Monthly Collections
- Add unlimited custom entries (e.g., "Share on Real Property Tax", "National Tax Allotment")
- Select category: Tax Revenue, Tax on Goods & Services, Operating & Services, or Other
- Entries automatically appear in report under their category
- Delete entries as needed

### BIR Withholding Tax Calculation

```
Example:
Total Amount Paid (Gross): ₱2,062.00
1% Withholding Tax: ₱18.41 (manual input)
5% Withholding Tax: ₱92.05 (manual input)
─────────────────────────────────
Total Withholding: ₱110.46 (auto-calculated)
Net to Payee: ₱1,951.54 (auto-calculated: 2,062.00 - 110.46)
```

### Search Workflow

1. Click "Search Payee" in sidebar
2. Enter name in search box
3. View summary statistics (Total Transactions, Total Amount, etc.)
4. Click "View Full Details" on any transaction to see complete information
5. Print report if needed

## System Requirements

- **PHP**: 7.4 or higher (with mysqli extension)
- **MySQL**: 5.7 or higher
- **Web Server**: Apache (XAMPP recommended)
- **Browser**: Chrome 90+, Firefox 88+, Edge 90+, or equivalent
- **Screen Resolution**: 1366x768 or higher recommended

## Security Features

- Session-based authentication
- MD5 password hashing
- Role-based access control (treasurer role)
- SQL injection prevention (prepared statements)
- XSS protection (htmlspecialchars)
- Session timeout on logout
- CSRF protection on forms
- Email OTP password reset (requires SMTP configuration)

## File Structure

```
Treasurer_management_system/
├── assets/
│   ├── css/
│   │   └── style.css (Blue theme styles)
│   └── images/
│       └── logo.jpg (Barangay logo)
├── config/
│   ├── database.php (DB connection)
│   └── session.php (Session management)
├── treasurer/
│   ├── dashboard.php (Main dashboard)
│   ├── search.php (Search functionality)
│   ├── bir/ (BIR records module)
│   ├── cedula/ (Cedula module with autocomplete)
│   ├── collections/ (Monthly collections with manual entries)
│   ├── disbursement/ (Disbursement module)
│   └── payments/ (Payments module with autocomplete)
├── index.php (Login page with logo and favicon)
├── logout.php (Logout handler)
├── database.sql (Complete schema with sample data)
├── update_bir_table.sql (Migration for BIR table updates)
└── README.md (This file)
```

## Browser Compatibility

- Google Chrome 90+
- Mozilla Firefox 88+
- Microsoft Edge 90+
- Safari 14+
- Internet Explorer: Not supported

## Troubleshooting

### Common Issues

**1. Cannot login / Invalid credentials**

- Check if database was imported correctly
- Default password is `treasurer123` (MD5 hash: 5f4dcc3b5aa765d61d8327deb882cf99)

**2. Logo not displaying**

- Ensure `assets/images/logo.jpg` exists
- Check file permissions

**3. BIR form error: "Unknown column 'net_amount'"**

- Run the migration script: `update_bir_table.sql`
- Or manually add the column: `ALTER TABLE bir_records ADD COLUMN net_amount DECIMAL(12, 2) NOT NULL;`

**4. Monthly Collections not showing manual entries**

- Ensure category names match exactly: "Tax Revenue", "Tax on Goods & Services", "Operating & Services", "Other"
- Check `monthly_manual_entries` table for correct entry_type values

**5. Dashboard chart not reflecting new data**

- Clear browser cache
- Check if manual entries exist in `monthly_manual_entries` table
- Verify entry_type values match expected categories

## Future Enhancements (Roadmap)

- [ ] User management (add/edit/delete users)
- [ ] Backup and restore functionality
- [ ] Email notifications for due payments
- [ ] PDF generation for receipts
- [ ] Advanced reporting with filters
- [ ] Audit trail/activity logs
- [ ] Mobile app version
- [ ] Data export to Excel

## Version History

- **v2.0.0** (January 2026) - Complete redesign with blue theme, logo integration, BIR withholding fix, flexible manual entries
- **v1.5.0** (January 2026) - Added search functionality, autocomplete, manual entry system
- **v1.0.0** (Initial Release) - Basic treasurer management with yellow theme

## Support & Contact

For support, bug reports, or feature requests:

- **Barangay**: Sto. Rosario
- **Municipality**: Magallanes
- **Province**: Agusan del Norte
- **System Admin**: Barangay Treasurer
- **Technical Support**: Contact your system administrator

## License & Credits

© 2026 Barangay Sto. Rosario. All Rights Reserved.

**Technologies Used:**

- PHP 7.4+
- MySQL 5.7+
- HTML5, CSS3, JavaScript
- Chart.js for data visualization
- Font Awesome 6.0 for icons

**Developed for**: Barangay Sto. Rosario, Magallanes, Agusan del Norte

---

_This system is designed to streamline treasurer operations and improve financial record-keeping for the barangay._
