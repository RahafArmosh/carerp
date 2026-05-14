# Permissions Added Summary

## Overview
Added comprehensive permission checks for 6 modules: Voucher, POS Refund, Price List, Combo, Stock, and Transfer. All controllers now check permissions while allowing company users to bypass all checks.

## Permissions Created

### Voucher Module
- `view voucher`
- `create voucher`
- `update voucher`
- `delete voucher`

### POS Refund Module
- `view pos refund`
- `create pos refund`
- `update pos refund`
- `delete pos refund`

### Price List Module
- `view price list`
- `create price list`
- `update price list`
- `delete price list`

### Combo Module
- `view combo`
- `create combo`
- `update combo`
- `delete combo`

### Stock Module
- `view stock`
- `create stock`
- `update stock`
- `delete stock`

### Transfer Module
- `view transfer`
- `create transfer`
- `update transfer`
- `delete transfer`

## Controllers Updated

1. **VouchersController** - All CRUD methods protected
2. **PosProductsRefundController** - All CRUD methods protected
3. **WarehousePriceListController** - All CRUD methods protected
4. **ComboOfferController** - All CRUD methods protected
5. **ProductStockController** - Index and update methods protected
6. **StockMovementController** - All CRUD methods protected
7. **WarehouseTransferController** - Index, create, store, and destroy methods protected

## Permission Pattern

All controllers follow this pattern:
```php
if(\Auth::user()->type == 'company' || \Auth::user()->can('permission name'))
{
    // Allow access
}
else
{
    return redirect()->back()->with('error', __('Permission denied.'));
}
```

**Important**: Company users (`type == 'company'`) always have full access to all modules, bypassing permission checks.

## Files Modified

1. `app/Http/Controllers/VouchersController.php`
2. `app/Http/Controllers/PosProductsRefundController.php`
3. `app/Http/Controllers/WarehousePriceListController.php`
4. `app/Http/Controllers/ComboOfferController.php`
5. `app/Http/Controllers/ProductStockController.php`
6. `app/Http/Controllers/StockMovementController.php`
7. `app/Http/Controllers/WarehouseTransferController.php`
8. `app/Models/Utility.php` - Added permissions to auto-create list
9. `database/seeders/NewModulePermissionsSeeder.php` - New seeder created

## How to Use

### Step 1: Run the Seeder
To create all permissions in the database:
```bash
php artisan db:seed --class=NewModulePermissionsSeeder
```

Or permissions will be auto-created when `Utility::addNewData()` is called.

### Step 2: Assign Permissions to Roles
1. Go to Roles & Permissions in admin panel
2. Edit the role you want to assign permissions to
3. Check the permissions you want to grant:
   - View permissions (for listing/viewing)
   - Create permissions (for creating new records)
   - Update permissions (for editing existing records)
   - Delete permissions (for deleting records)
4. Save the role

### Step 3: Assign Role to Users
1. Go to Users management
2. Edit the user
3. Assign the role with the permissions
4. Save

## Notes

- **Company users** automatically have access to all modules (no permission checks)
- **Other users** must have the specific permission assigned to their role
- Permissions are checked at the controller level
- If a user doesn't have permission, they'll see "Permission denied" error message
- All permissions are automatically assigned to the "company" role when created

## Testing

After assigning permissions:
1. Log in as a non-company user with limited permissions
2. Try to access each module
3. Verify that:
   - Users with permission can access the module
   - Users without permission see "Permission denied" error
   - Company users can access everything

