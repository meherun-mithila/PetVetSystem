# Staff Member Dashboard

This folder contains all the staff-related functionality for the PetVet Veterinary Clinic Management System.

## File Structure

### Core Files

1. **`dashboard.php`** - Main staff dashboard with overview statistics
   - Today's appointments
   - Pending appointments
   - Recent patients
   - Available doctors
   - Quick stats overview

2. **`appointments.php`** - Appointments management
   - Today's appointments display
   - Pending appointments
   - All appointments table
   - Appointment statistics

3. **`patients.php`** - Patient management
   - Patient listing with owner information
   - Patient statistics by species
   - Patient details and registration dates

4. **`doctors.php`** - Doctor management
   - Available doctors display
   - All doctors listing
   - Doctor statistics and availability

5. **`billing.php`** - Billing and payment management
   - Medical records with billing information
   - Revenue tracking
   - Payment status monitoring

6. **`medical_reports.php`** - Medical records management
   - Complete medical records listing
   - Diagnosis and treatment information
   - Medical history tracking

7. **`reports.php`** - Analytics and reporting
   - Monthly appointments chart
   - Revenue analytics
   - Patient and doctor statistics
   - Interactive charts using Chart.js

8. **`profile.php`** - Staff profile management
   - Staff member information
   - Account details
   - Quick actions

## Features

### Dashboard Overview
- Real-time statistics
- Quick access to all functions
- Today's schedule overview
- Recent activity tracking

### Appointments Management
- View today's appointments
- Track pending appointments
- Appointment history
- Time-based sorting

### Patient Management
- Complete patient database
- Owner information
- Species and breed tracking
- Registration history

### Doctor Management
- Doctor availability tracking
- Specialization information
- Contact details
- Appointment statistics

### Billing System
- Medical record costs
- Payment tracking
- Revenue analytics
- Outstanding payments

### Medical Reports
- Complete medical history
- Diagnosis records
- Treatment plans
- Cost tracking

### Analytics & Reports
- Monthly appointment trends
- Revenue analysis
- Patient demographics
- Doctor performance metrics

### Profile Management
- Staff information display
- Account management
- Quick navigation

## Database Compatibility

All files include dynamic column detection to handle both old and new database schemas:
- `appointment_date` vs `date` columns
- `appointment_time` vs `time` columns
- `phone` vs `contact` columns
- `created_at` column handling

## Security Features

- Session-based authentication
- Staff-only access control
- Input sanitization
- Error handling
- SQL injection prevention

## Navigation

All pages include consistent navigation with:
- Dashboard link
- Function-specific pages
- Profile access
- Logout functionality

## Styling

- Tailwind CSS for modern UI
- Responsive design
- Consistent color scheme
- Professional veterinary clinic theme

## Usage

1. Access through main login system
2. Select "Staff Member" user type
3. Navigate through different functions using the top navigation
4. All data is real-time from the database
5. Charts and statistics update automatically

## Requirements

- PHP 7.4+
- MySQL database
- Web server (Apache/Nginx)
- Modern web browser with JavaScript enabled 