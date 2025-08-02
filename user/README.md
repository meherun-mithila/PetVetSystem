# User Dashboard System

This folder contains the complete user dashboard system for the PetVet application.

## Folder Structure

```
user/
├── dashboard.php          # Main user dashboard with overview
├── pets.php              # Pet management (add, edit, delete pets)
├── appointments.php       # Appointment booking and management
├── medical_records.php    # View medical records and bills
├── profile.php           # User profile management
├── includes/
│   ├── header.php        # Shared header with navigation
│   ├── footer.php        # Shared footer
│   └── functions.php     # Common functions and utilities
└── README.md             # This file
```

## Features

### Dashboard (dashboard.php)
- Overview of user's pets, appointments, and medical records
- Quick statistics and recent activity
- Links to all major functions

### Pet Management (pets.php)
- Register new pets with detailed information
- Edit existing pet details
- Delete pets from the system
- View all registered pets

### Appointment System (appointments.php)
- Book new appointments with available doctors
- Select pets and appointment times
- View all past and upcoming appointments
- Cancel scheduled appointments
- Track appointment status

### Medical Records (medical_records.php)
- View complete medical history for all pets
- Track diagnosis, treatment, and medications
- View billing information and total costs
- Access doctor information and follow-up details

### Profile Management (profile.php)
- Update personal information
- Change password securely
- View account details and member since date
- Quick access to other sections

## Shared Components

### Header (includes/header.php)
- Session validation and security checks
- Consistent navigation menu
- User information display
- Logout functionality

### Functions (includes/functions.php)
- Database query functions
- Data formatting utilities
- Status and currency helpers
- Error handling

## Security Features

- Session-based authentication
- User-specific data access
- Input validation and sanitization
- SQL injection prevention with prepared statements
- XSS protection with htmlspecialchars

## Database Integration

All pages connect to the main database through `../config.php` and use the following tables:
- `users` - User account information
- `patients` - Pet information linked to users
- `appointments` - Appointment bookings
- `medicalrecords` - Medical history and billing
- `doctors` - Available veterinary doctors

## Usage

1. Users log in through the main `index.php`
2. Upon successful authentication, users are redirected to `user/dashboard.php`
3. Navigation between sections is handled by the shared header
4. All forms include proper validation and error handling
5. Success/error messages are displayed to users

## Styling

The system uses Tailwind CSS for consistent, modern styling with a veterinary-themed color scheme:
- Primary: Vet Blue (#2c5aa0)
- Secondary: Vet Dark Blue (#1e3d72)
- Accent: Vet Coral (#ff6b6b) 