# User Management System - PetVet

This document describes the user management functionality for the PetVet veterinary clinic system.

## Overview

The user management system allows new users to sign up for accounts and existing users to delete their accounts. All user data is stored in the `users` table in the database.

## Files Created

- `user_management.php` - Main user management interface
- `USER_MANAGEMENT_README.md` - This documentation file

## Features

### 1. User Registration (Sign Up)
- **Required Fields**: Full Name, Email Address, Password, Confirm Password
- **Optional Fields**: Phone Number, Address
- **Validation**:
  - All required fields must be filled
  - Email must be in valid format
  - Password must be at least 6 characters long
  - Passwords must match
  - Email must be unique (not already registered)
- **Security**: Passwords are stored as-is (consider implementing hashing in production)

### 2. Account Deletion
- **Required Fields**: Email Address, Password
- **Validation**:
  - User credentials must be verified
  - Cannot delete account if user has related data (pets, appointments)
- **Safety Features**:
  - Confirmation dialog before deletion
  - Checks for related data to prevent orphaned records
  - Clear warning about permanent data loss

## Database Structure

The system uses the existing `users` table with the following structure:

```sql
CREATE TABLE `users` (
  `user_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `address` text DEFAULT NULL
);
```

## Usage

### For New Users
1. Navigate to `user_management.php`
2. Fill out the registration form
3. Click "Create Account"
4. Use the new credentials to login at `index.php`

### For Existing Users (Account Deletion)
1. Navigate to `user_management.php`
2. Fill out the deletion form with your credentials
3. Confirm the deletion when prompted
4. Account will be permanently removed

### Access Points
- **Main Login Page**: Link added to `index.php` header
- **Direct Access**: Navigate to `user_management.php`

## Security Considerations

### Current Implementation
- Basic form validation
- SQL injection prevention using prepared statements
- Session management
- Input sanitization using `htmlspecialchars()`

### Recommended Improvements for Production
- Password hashing (e.g., using `password_hash()` and `password_verify()`)
- CSRF protection
- Rate limiting for registration attempts
- Email verification for new accounts
- Stronger password requirements
- Logging of user actions

## Error Handling

The system provides user-friendly error messages for:
- Validation failures
- Database connection issues
- Duplicate email addresses
- Invalid credentials for deletion
- Related data preventing deletion

## Integration

The user management system integrates with the existing PetVet system:
- Uses the same database connection (`config.php`)
- Follows the same design patterns and styling
- Compatible with existing user authentication
- Maintains consistency with the clinic's branding

## Testing

To test the system:
1. Ensure the database is running and accessible
2. Navigate to `user_management.php`
3. Test registration with valid and invalid data
4. Test account deletion with valid and invalid credentials
5. Verify that new users can login after registration

## Troubleshooting

### Common Issues
1. **Database Connection Failed**: Check `config.php` database settings
2. **Registration Fails**: Verify all required fields are filled
3. **Deletion Fails**: Ensure user has no related data (pets, appointments)
4. **Styling Issues**: Ensure Tailwind CSS is loading properly

### Debug Mode
Enable error logging by checking the PHP error log or adding:
```php
error_reporting(E_ALL);
ini_set('display_errors', 1);
```

## Future Enhancements

Potential improvements for future versions:
- User profile management
- Password reset functionality
- Email notifications
- Admin user management interface
- User activity logging
- Multi-factor authentication
- Social media login integration

## Support

For technical support or questions about the user management system, please refer to the main PetVet documentation or contact the development team.
