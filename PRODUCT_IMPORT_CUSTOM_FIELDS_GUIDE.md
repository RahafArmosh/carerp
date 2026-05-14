# Product Import with Custom Fields Guide

## Overview
This guide explains how to import products with custom fields using Excel/CSV files. The system now supports dynamic custom field processing during product import.

## Excel File Format

### Required Columns:
1. **name** - Product name (required)
2. **sku** - Product SKU (required)
3. **sale_price** - Sale price (required)
4. **purchase_price** - Purchase price (required)
5. **type** - Product type (required)
6. **quantity** - Product quantity (optional)
7. **description** - Product description (optional)
8. **category** - Category ID (optional)
9. **brand** - Brand ID (optional)
10. **sub_brand** - Sub-brand ID (optional)
11. **unit** - Unit ID (optional)
12. **tax** - Tax IDs (comma/semicolon separated, optional)

### Custom Field Columns:
Any column that matches a custom field name will be automatically processed as a custom field.

## Custom Field Types Supported

### 1. Text Fields
- **Type**: `text`
- **Format**: Any text value
- **Example**: `iPhone 15 Pro`

### 2. Email Fields
- **Type**: `email`
- **Format**: Valid email address
- **Example**: `support@company.com`

### 3. Number Fields
- **Type**: `number`
- **Format**: Numeric value
- **Example**: `123`, `45.67`

### 4. Date Fields
- **Type**: `date`
- **Format**: YYYY-MM-DD
- **Example**: `2025-12-31`

### 5. Textarea Fields
- **Type**: `textarea`
- **Format**: Multi-line text
- **Example**: `This is a long description with multiple lines`

### 6. Dropdown Fields
- **Type**: `dropdown`
- **Format**: Value must match one of the predefined options
- **Example**: If options are `["Small", "Medium", "Large"]`, use `Medium`

## Example Format

```csv
name,sku,sale_price,purchase_price,type,quantity,description,category,brand,sub_brand,unit,tax,Gender,Color,Size,Style,Number Size,Internal Reference
iPhone 15 Pro,IPH15PRO,999.99,799.99,product,50,Latest iPhone with Pro features,1,1,1,1,1,Male,Space Black,Large,Modern,15,REF001
Samsung Galaxy S24,SAMS24,899.99,699.99,product,30,Flagship Android phone,1,2,2,1,2,Female,Titanium Gray,Medium,Contemporary,24,REF002
MacBook Pro 16,MBP16,2499.99,1999.99,product,20,Professional laptop,2,3,3,2,1,Unisex,Silver,Extra Large,Professional,16,REF003
```

## Custom Field Processing

### How It Works:
1. **Field Detection**: System scans Excel headers for custom field names
2. **Field Validation**: Values are validated based on field type
3. **Data Storage**: Valid custom field data is saved to `custom_field_values` table
4. **Error Handling**: Invalid values are logged but don't stop the import

### Field Name Matching:
- Custom field names in Excel must **exactly match** the custom field names in the system
- Case-sensitive matching
- Spaces and special characters are preserved

## Validation Rules

### Required Fields:
- **name**: Cannot be empty
- **sku**: Cannot be empty (must be unique per user)
- **sale_price**: Must be numeric
- **purchase_price**: Must be numeric
- **type**: Cannot be empty

### Custom Field Validation:
- **Email**: Must be valid email format
- **Number**: Must be numeric
- **Date**: Must be in YYYY-MM-DD format
- **Dropdown**: Must match predefined options
- **Text/Textarea**: No validation (always accepted)

### Brand/Sub-brand Validation:
- **brand**: Must exist in brands table
- **sub_brand**: Must exist in sub_brands table
- **category**: Must exist in categories table
- **unit**: Must exist in units table
- **tax**: Must exist in taxes table

## Import Process

### Step 1: Prepare Your Data
1. Create Excel/CSV file with required columns
2. Add custom field columns matching your system's custom fields
3. Ensure all referenced IDs exist in your system
4. Validate data formats

### Step 2: Upload File
1. Go to Products → Import
2. Select your Excel/CSV file
3. Click Import

### Step 3: Monitor Import
1. Check logs for processing details
2. Review any warnings or errors
3. Verify imported products and custom field data

## Examples

### Example 1: Basic Product with Custom Fields
```csv
name,sku,sale_price,purchase_price,type,Gender,Color,Size
iPhone 15 Pro,IPH15PRO,999.99,799.99,product,Male,Space Black,Large
```
- Creates iPhone 15 Pro product
- Saves custom fields: Gender=Male, Color=Space Black, Size=Large

### Example 2: Complex Product with All Fields
```csv
name,sku,sale_price,purchase_price,type,quantity,description,category,brand,sub_brand,unit,tax,Gender,Color,Size,Style,Number Size,Internal Reference
MacBook Pro 16,MBP16,2499.99,1999.99,product,20,Professional laptop,2,3,3,2,1,Unisex,Silver,Extra Large,Professional,16,REF003
```

### Example 3: Multiple Products
```csv
name,sku,sale_price,purchase_price,type,Gender,Color,Size
iPhone 15 Pro,IPH15PRO,999.99,799.99,product,Male,Space Black,Large
Samsung Galaxy S24,SAMS24,899.99,699.99,product,Female,Titanium Gray,Medium
MacBook Pro 16,MBP16,2499.99,1999.99,product,Unisex,Silver,Extra Large
```

## Error Handling

### Common Issues:
1. **Invalid Custom Field Value**: Value doesn't match field type
2. **Missing Required Field**: Required field is empty
3. **Invalid Reference ID**: Brand/category/unit ID doesn't exist
4. **Duplicate SKU**: SKU already exists (updates existing product)

### Logging:
- All custom field processing is logged
- Invalid values are logged as warnings
- Successful imports are logged with details
- Check `storage/logs/laravel.log` for details

## Tips

### Best Practices:
1. **Test with Small Files**: Start with a few products to test custom fields
2. **Verify Custom Fields**: Ensure custom field names match exactly
3. **Check Field Types**: Validate custom field values match their types
4. **Use Consistent Formatting**: Follow the exact column headers

### Data Preparation:
1. **Export First**: Use export to see current custom field structure
2. **Validate References**: Check all IDs exist in your system
3. **Clean Data**: Remove extra spaces and ensure consistent formatting
4. **Test Custom Fields**: Verify custom field values are valid

## Troubleshooting

### Import Fails:
- Check file format (must be .xlsx, .xls, or .csv)
- Verify column headers match exactly
- Ensure file is not corrupted

### Custom Fields Not Imported:
- Check custom field names match exactly (case-sensitive)
- Verify custom field values are valid for their type
- Check logs for validation errors

### Partial Import:
- Some rows may be skipped due to validation errors
- Check logs to see which rows failed and why
- Fix data and re-import failed rows

## Custom Field Management

### Creating Custom Fields:
1. Go to Settings → Custom Fields
2. Create fields for 'product' module
3. Set field type and options
4. Use exact field names in Excel

### Field Types Available:
- Text: Single line text
- Email: Email validation
- Number: Numeric validation
- Date: Date validation (YYYY-MM-DD)
- Textarea: Multi-line text
- Dropdown: Predefined options

## Support

If you encounter issues:
1. Check the application logs
2. Verify your data format matches the examples
3. Ensure all referenced IDs exist in your system
4. Validate custom field names and values
5. Contact your system administrator if problems persist
