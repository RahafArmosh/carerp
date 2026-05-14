# User Login Fix Guide

## Problem
When a user with a limited role tries to login, the login doesn't work. This is usually caused by:
1. Missing dashboard permissions
2. User status fields set incorrectly
3. Missing email verification
4. Missing or invalid user type

## Solution

I've created an Artisan command to diagnose and fix these issues automatically.

### Usage

#### 1. Diagnose Issues (Check Only)
```bash
php artisan user:fix-login user@example.com
```

This will check the user and report all issues without fixing them.

#### 2. Fix Issues Automatically
```bash
php artisan user:fix-login user@example.com --fix
```

This will automatically fix all fixable issues.

### What the Command Checks

1. **is_active** - User must be active (value should be 1)
2. **delete_status** - User must not be deleted (value should be 1)
3. **is_disable** - User account must be enabled (value should be 1)
4. **email_verified_at** - Email should be verified (not null)
5. **type** - User type should be valid (company, employee, user, etc.)
6. **Dashboard Permissions** - User needs at least one dashboard permission:
   - `show account dashboard`
   - `show hrm dashboard`
   - `show crm dashboard`
   - `show project dashboard`
   - `show pos dashboard`
7. **Roles** - User should have at least one role assigned

### Example Output

```
=== User Login Diagnostic Tool ===
Checking user: test@example.com

User found: Test User (ID: 5)
User Type: employee
Created By: 1

1. Checking is_active status...
   ✅ User is active (is_active = 1)
2. Checking delete_status...
   ✅ User is not deleted (delete_status = 1)
3. Checking is_disable status...
   ✅ User account is enabled (is_disable = 1)
4. Checking email verification...
   ✅ Email is verified (2024-01-01 12:00:00)
5. Checking user type...
   ✅ User type is valid: 'employee'
6. Checking dashboard permissions...
   ❌ User has NO dashboard permissions
7. Checking user roles...
   ✅ User has roles:
      - employee (ID: 3)

=== SUMMARY ===
⚠️  Found 1 issue(s):
   - dashboard_permissions

💡 To automatically fix these issues, run:
   php artisan user:fix-login test@example.com --fix
```

### Manual Fix Steps

If you prefer to fix manually:

1. **Check User Status** (in database `users` table):
   ```sql
   SELECT id, email, is_active, delete_status, is_disable, type, email_verified_at 
   FROM users 
   WHERE email = 'user@example.com';
   ```
   
   Update if needed:
   ```sql
   UPDATE users 
   SET is_active = 1, delete_status = 1, is_disable = 1, email_verified_at = NOW()
   WHERE email = 'user@example.com';
   ```

2. **Assign Dashboard Permission**:
   - Go to Roles & Permissions in admin panel
   - Edit the role assigned to the user
   - Check "show account dashboard" permission
   - Save

3. **Verify Role Assignment**:
   - Go to Users management
   - Edit the user
   - Ensure they have a role assigned
   - Save

### Common Issues

#### Issue: "You do not have permission to access any dashboard"
**Solution**: Assign the "show account dashboard" permission to the user's role.

#### Issue: User gets logged out immediately after login
**Solution**: Check `is_active` and `delete_status` fields - they should both be 1.

#### Issue: "Your Account is disable"
**Solution**: Set `is_disable` field to 1 in the database.

### Notes

- The command requires the user's email address
- Use `--fix` flag to automatically fix issues
- Some issues (like missing roles) may require manual intervention
- After fixing, the user should be able to login successfully

