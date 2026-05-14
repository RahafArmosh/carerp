# POS Customer Creation Test Guide

## Overview
This document describes how to test the customer creation functionality from the POS page.

## Route Information
- **Route Name**: `pos.customer.create`
- **URL**: `/pos/customer/create`
- **Method**: POST
- **Controller**: `CustomerController@storeFromPos`
- **Middleware**: `auth`, `XSS`, `RevalidateBackHistory`

## Prerequisites
1. User must be logged in
2. User must have `create customer` permission
3. `Account Receivables` chart account must exist for the creator
4. CSRF token must be included in the request

## Testing Steps

### Method 1: Manual Browser Testing

1. **Navigate to POS Page**
   - Go to `/pos` in your browser
   - Make sure you're logged in

2. **Open Customer Creation Modal**
   - Click the "+" button next to the customer search field
   - The modal should open with fields: Name, Contact, Email

3. **Fill in Customer Details**
   - Name: Required (e.g., "Test Customer")
   - Contact: Required, must match regex `/^([0-9\s\-\+\(\)]*)$/` (e.g., "1234567890")
   - Email: Optional (e.g., "test@example.com")

4. **Submit the Form**
   - Click "Create" button
   - Check browser console (F12) for:
     - Route URL being called
     - Request data
     - Response data

5. **Expected Results**
   - Modal should close
   - Success message should appear (toastr notification)
   - Customer should be auto-selected in the POS customer search field
   - Customer should be saved in the database

### Method 2: Browser Console Testing

Open browser console (F12) on the POS page and run:

```javascript
// Test customer creation
$.ajax({
    url: '/pos/customer/create',
    type: 'POST',
    data: {
        name: 'Test Customer ' + new Date().getTime(),
        contact: '1234567890',
        email: 'test' + new Date().getTime() + '@example.com',
        _token: $('meta[name="csrf-token"]').attr('content') || '{{ csrf_token() }}'
    },
    headers: {
        'X-Requested-With': 'XMLHttpRequest',
        'Accept': 'application/json'
    },
    success: function(response) {
        console.log('Success:', response);
        alert('Customer created: ' + response.customer.name);
    },
    error: function(xhr) {
        console.error('Error:', xhr);
        console.error('Status:', xhr.status);
        console.error('Response:', xhr.responseJSON);
        alert('Error: ' + (xhr.responseJSON?.error || 'Unknown error'));
    }
});
```

### Method 3: Check Laravel Logs

After attempting to create a customer, check the Laravel log file:

```bash
tail -f storage/logs/laravel.log
```

Look for log entries starting with:
- `=== storeFromPos START ===`
- `Attempting to save customer`
- `Customer saved successfully`
- `=== storeFromPos SUCCESS ===`

### Method 4: Database Verification

After creating a customer, verify it was saved:

```sql
SELECT * FROM customers 
WHERE name LIKE 'Test Customer%' 
ORDER BY created_at DESC 
LIMIT 1;
```

## Common Issues and Solutions

### Issue 1: Route Not Found (404)
**Solution**: 
- Clear route cache: `php artisan route:clear`
- Verify route exists in `routes/web.php` line 439

### Issue 2: Permission Denied (403)
**Solution**: 
- Check user has `create customer` permission
- Verify user is logged in

### Issue 3: Account Receivables Not Found (422)
**Solution**: 
- Create an "Account Receivables" chart account for the creator
- Or provide `chart_account` in the request

### Issue 4: Validation Error (422)
**Solution**: 
- Ensure `name` field is provided
- Ensure `contact` field matches regex pattern (numbers, spaces, dashes, plus, parentheses)
- Check browser console for specific validation errors

### Issue 5: Customer Not Auto-Selected
**Solution**: 
- Verify modal is included with `autoSelectCustomer => true`
- Check browser console for JavaScript errors
- Verify customer search input fields exist: `#customer_search`, `#customer_id`

## Debugging

### Enable Detailed Logging
The controller now includes detailed logging. Check `storage/logs/laravel.log` for:
- Request data
- User information
- Validation results
- Save attempts
- Success/failure responses

### Browser Console Debugging
Check browser console for:
- AJAX request URL
- Request data
- Response status and data
- JavaScript errors

### Network Tab
In browser DevTools → Network tab:
- Find the POST request to `/pos/customer/create`
- Check Request Headers (should include `X-Requested-With: XMLHttpRequest`)
- Check Request Payload
- Check Response Status and Body

## Expected Response Format

### Success Response (200)
```json
{
    "success": true,
    "message": "Customer successfully created.",
    "customer": {
        "id": 123,
        "name": "Test Customer",
        "contact": "1234567890",
        "email": "test@example.com"
    }
}
```

### Error Response (422)
```json
{
    "error": "The name field is required.",
    "errors": {
        "name": ["The name field is required."]
    }
}
```

### Error Response (403)
```json
{
    "error": "Permission denied."
}
```

## Test Checklist

- [ ] Route is accessible
- [ ] Modal opens correctly
- [ ] Form validation works (name required, contact required)
- [ ] Customer is created successfully
- [ ] Success message appears
- [ ] Customer is auto-selected in POS
- [ ] Customer appears in database
- [ ] Logs show successful creation
- [ ] Error handling works (test with invalid data)
