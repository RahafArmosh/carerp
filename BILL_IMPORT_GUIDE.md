# Bill Import Example for User 34

## File Created
- **Filename**: `bill_import_example_user34_2025-10-23_15-52-47.xlsx`
- **Location**: Project root directory

## File Structure

### Row 1: Bill Headers
```
vender_id | bill_date | due_date | warehouse_id | category_id | order_number | salesman_id | tax_id | currency_id | exchange_rate
```

### Row 2: Bill Data
```
1 | 2025-10-23 | 2025-11-23 | 1 | 1 | ORD-001 | 1 | 1 | 1 | 1.0
```

### Row 3: Sub-Product Headers
```
product_id | quantity | sale_price | purchase_price | product_no | Gender | Color | Size | Style | Number Size | Internal Reference
```

### Rows 4-6: Sub-Product Data
```
1 | 5 | 100.00 | 80.00 | PROD-001 | Male | Blue | L | Casual | 42 | REF-001
2 | 3 | 150.00 | 120.00 | PROD-002 | Female | Red | M | Formal | 38 | REF-002
3 | 2 | 200.00 | 160.00 | PROD-003 | Unisex | Black | XL | Sports | 44 | REF-003
```

## Before Importing - IMPORTANT!

### 1. Verify Required Data Exists
You need to check that these IDs exist in your database for user 34:

```sql
-- Check vendor exists
SELECT id, name FROM venders WHERE id = 1 AND created_by = [user_34_creator_id];

-- Check warehouse exists  
SELECT id, name FROM warehouses WHERE id = 1 AND created_by = [user_34_creator_id];

-- Check category exists
SELECT id, name FROM product_service_categories WHERE id = 1 AND created_by = [user_34_creator_id];

-- Check products exist
SELECT id, name FROM product_services WHERE id IN (1,2,3) AND created_by = [user_34_creator_id];

-- Check tax exists
SELECT id, name FROM taxes WHERE id = 1 AND created_by = [user_34_creator_id];

-- Check currency exists
SELECT id, name FROM currencies WHERE id = 1;
```

### 2. Update the Example File
Replace the example IDs with actual IDs from your database:

- **vender_id**: Use actual vendor ID
- **warehouse_id**: Use actual warehouse ID  
- **category_id**: Use actual category ID
- **product_id**: Use actual product IDs
- **tax_id**: Use actual tax ID
- **currency_id**: Use actual currency ID

### 3. Custom Fields
The example includes custom fields (Gender, Color, Size, etc.). Make sure:
- These custom fields exist in your database
- They are associated with the correct category
- They have `module = 'sub-product'`

## Import Process

### 1. Access Import
- Go to **Bills** → **Import** button
- Click the import button in the bills index page

### 2. Upload File
- Select the updated Excel file
- Click **Upload**

### 3. Monitor Progress
- The import runs as a background job
- Check `storage/logs/laravel.log` for any errors
- You'll be notified when complete

## What Gets Created

### 1. Bill Record
- New bill with ID based on last bill number
- All bill fields populated from Row 2
- `created_by` set to user 34's creator ID

### 2. Sub-Products
- One sub-product per data row (Rows 4-6)
- Product quantities updated
- Custom field values saved

### 3. Bill Products
- Links between bill and sub-products
- Tax calculations applied
- Pricing information stored

### 4. Bill Accounts
- Vendor account entries
- Category account entries
- Tax account entries (if applicable)

### 5. Warehouse Products
- Inventory updates if warehouse specified
- Quantity adjustments

## Troubleshooting

### Common Issues
1. **"Vendor not found"**: Check vendor ID exists and is accessible
2. **"Product not found"**: Verify product IDs exist
3. **"Custom field not found"**: Ensure custom fields are properly configured
4. **"Permission denied"**: User must have proper permissions

### Error Logs
Check `storage/logs/laravel.log` for detailed error information:
```bash
tail -f storage/logs/laravel.log
```

### Failed Jobs
If import fails, check failed jobs:
```bash
php artisan queue:failed
```

## Success Indicators
- Bill appears in bills list
- Sub-products created with correct quantities
- Inventory updated (if warehouse specified)
- No errors in logs
- Success message displayed

## Next Steps After Import
1. **Review Bill**: Check the created bill for accuracy
2. **Verify Products**: Ensure sub-products are correct
3. **Check Inventory**: Confirm warehouse quantities updated
4. **Test Workflow**: Process the bill through normal workflow
