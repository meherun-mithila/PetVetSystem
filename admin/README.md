# Admin Dashboard System

This folder contains the complete administration dashboard system for the PetVet application.

## Folder Structure

```
admin/
├── dashboard.php          # Main admin dashboard with overview and statistics
├── patients.php          # Patient management and viewing
├── appointments.php       # Appointment management and viewing
├── doctors.php           # Doctor management and viewing
├── users.php             # User (pet owner) management and viewing
├── staff.php             # Staff management and viewing
├── medical_records.php    # Medical records management and viewing
├── reports.php           # Reports and analytics
└── README.md             # This file
```

## Features

### Dashboard (dashboard.php)
- Overview of system statistics (patients, appointments, doctors, users)
- Today's appointments display
- Recent patients list
- Quick action buttons for common tasks
- Real-time data from database

### Patient Management (patients.php)
- View all registered patients in the system
- Patient details including owner information
- Species, breed, age, and registration date
- Clean table layout with hover effects

### Appointment Management (appointments.php)
- View all appointments across the system
- Appointment details including pet, owner, doctor, date/time
- Status tracking (Scheduled, Completed, Cancelled)
- Reason for visit information

### Doctor Management (doctors.php)
- View all veterinary doctors
- Doctor information including specialization and contact details
- Availability status tracking
- Professional information display

### User Management (users.php)
- View all pet owners registered in the system
- User contact information and registration dates
- Complete user profile information
- Registration timeline

### Staff Management (staff.php)
- View all clinic staff members
- Staff roles and contact information
- Role-based categorization
- Staff directory

### Medical Records (medical_records.php)
- View all medical records in the system
- Complete medical history with diagnosis and treatment
- Billing information and revenue tracking
- Doctor and patient information

### Reports & Analytics (reports.php)
- Comprehensive system statistics
- Financial summary with revenue tracking
- Monthly appointment trends
- Visual charts and progress bars
- System overview metrics

## Security Features

- Session-based authentication
- Admin-only access control
- Input validation and sanitization
- SQL injection prevention with prepared statements
- XSS protection with htmlspecialchars

## Database Integration

All pages connect to the main database through `../config.php` and use the following tables:
- `users` - Pet owner accounts
- `patients` - Pet information
- `appointments` - Appointment bookings
- `doctors` - Veterinary doctors
- `staff` - Clinic staff
- `medicalrecords` - Medical history and billing

## Navigation

- Consistent header with clinic branding
- Easy navigation between all admin sections
- Dashboard link for quick return to overview
- Logout functionality

## Styling

The system uses Tailwind CSS for consistent, modern styling with:
- Clean, professional design
- Responsive layout
- Color-coded status indicators
- Hover effects and transitions
- Mobile-friendly interface

## Usage

1. Admins log in through the main `index.php`
2. Upon successful authentication, admins are redirected to `admin/dashboard.php`
3. Navigation between sections is handled by the header links
4. All data is displayed in organized tables with proper formatting
5. Real-time statistics and reports are available

## Quick Access

- **Dashboard**: System overview and quick actions
- **Patients**: View and manage all registered pets
- **Appointments**: Monitor all clinic appointments
- **Doctors**: Manage veterinary staff
- **Users**: View pet owner accounts
- **Staff**: Manage clinic staff
- **Medical Records**: Access complete medical history
- **Reports**: Analytics and financial summaries 