# Admin Panel - PetVet System

This directory contains the admin panel files for the PetVet veterinary clinic management system.

## Files Overview

- **dashboard.php** - Main admin dashboard with overview statistics
- **staff.php** - Staff management (add, edit, delete staff members)
- **doctors.php** - Doctor management
- **patients.php** - Patient/pet management
- **appointments.php** - Appointment scheduling and management
- **medical_records.php** - Medical records management
- **vaccine_records.php** - Vaccine records management
- **adoption.php** - Pet adoption management
- **users.php** - User account management
- **notifications.php** - System notifications
- **reports.php** - Various system reports

## Database Setup

### Staff Table Setup

Before using the staff management functionality, you need to create the staff table in your database:

1. **Option 1: Run the SQL script directly**
   ```sql
   source admin/setup_staff.sql;
   ```

2. **Option 2: Copy and paste the SQL commands**
   Open your MySQL client (phpMyAdmin, MySQL Workbench, or command line) and run:
   ```sql
   CREATE TABLE IF NOT EXISTS `staff` (
     `staff_id` int(11) NOT NULL AUTO_INCREMENT,
     `name` varchar(255) NOT NULL,
     `email` varchar(255) NOT NULL UNIQUE,
     `phone` varchar(20) DEFAULT NULL,
     `role` varchar(100) NOT NULL,
     `password` varchar(255) NOT NULL,
     `extra_info` text DEFAULT NULL,
     `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
     `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
     PRIMARY KEY (`staff_id`),
     UNIQUE KEY `email` (`email`)
   ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
   ```

## Staff Management Features

The staff.php file provides the following functionality:

### Add New Staff
- Click "Add New Staff" button
- Fill in the form with staff details
- Set a password for staff login
- Choose role from predefined options
- Add optional extra information

### Edit Staff
- Click "Edit" button on any staff row
- Modify staff information
- Optionally change password
- Update extra information

### Delete Staff
- Click "Delete" button on any staff row
- Confirm deletion in the modal

### Staff Roles Available
- Receptionist
- Veterinary Technician
- Nurse
- Assistant
- Manager
- Other

## Security Features

- Password hashing using PHP's built-in `password_hash()` function
- Session-based authentication
- Admin-only access control
- Input validation and sanitization
- SQL injection prevention using prepared statements

## Usage Instructions

1. **Access**: Navigate to `/admin/staff.php` (must be logged in as admin)
2. **Add Staff**: Click "Add New Staff" and fill the form
3. **Edit Staff**: Click "Edit" on any staff row
4. **Delete Staff**: Click "Delete" and confirm
5. **Pagination**: Use the pagination controls to navigate through large staff lists
6. **Page Size**: Adjust the number of staff members displayed per page

## Troubleshooting

### Common Issues

1. **"Database connection failed"**
   - Check your database credentials in `../config.php`
   - Ensure MySQL service is running
   - Verify database name exists

2. **"Table 'staff' doesn't exist"**
   - Run the setup_staff.sql script
   - Check if the table was created successfully

3. **"Access denied"**
   - Ensure you're logged in as an admin user
   - Check session variables and user type

4. **Form not submitting**
   - Check browser console for JavaScript errors
   - Verify all required fields are filled
   - Check PHP error logs for server-side issues

## Notes

- Default sample staff passwords are set to 'password' - change these in production
- Email addresses must be unique across all staff members
- Phone numbers are optional but recommended
- Extra information field can store qualifications, special skills, or notes
- All timestamps are automatically managed by the database 