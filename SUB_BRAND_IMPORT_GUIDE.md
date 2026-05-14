# Sub-Brand Import Guide

## Overview
This guide explains how to import sub-brands with both default brand and additional brands using Excel/CSV files.

## Excel File Format

### Required Columns:
1. **Sub Brand Name** - The name of the sub-brand
2. **Default Brand ID** - The ID of the primary/default brand
3. **Additional Brands ID** - Comma-separated IDs of additional brands (optional)

### Example Format:
```csv
Sub Brand Name,Default Brand ID,Additional Brands ID
Air Max,1,2 3
Jordan,1,
Dunk,1,2
Ultra Boost,2,1
Stan Smith,2,
Yeezy,2,1 3
```

## Column Details

### 1. Sub Brand Name
- **Required**: Yes
- **Type**: Text
- **Description**: The name of the sub-brand
- **Example**: "Air Max", "Jordan", "Dunk"

### 2. Default Brand ID
- **Required**: Yes
- **Type**: Number
- **Description**: The ID of the primary brand this sub-brand belongs to
- **Example**: 1, 2, 3
- **Note**: Must exist in your brands table

### 3. Additional Brands ID
- **Required**: No
- **Type**: Text (comma-separated numbers)
- **Description**: IDs of additional brands this sub-brand can belong to
- **Example**: "2 3", "1", "1 2 3"
- **Note**: 
  - Separate multiple IDs with spaces or commas
  - Must exist in your brands table
  - Cannot be the same as Default Brand ID

## Import Process

### Step 1: Prepare Your Data
1. Create an Excel/CSV file with the required columns
2. Ensure all brand IDs exist in your system
3. Use the correct column headers (case-sensitive)

### Step 2: Upload File
1. Go to Sub-Brands → Import
2. Select your Excel/CSV file
3. Click Import

### Step 3: Verification
1. Check the logs for any warnings or errors
2. Verify imported sub-brands in the Sub-Brands list
3. Confirm both default and additional brand relationships

## Examples

### Example 1: Simple Sub-Brand
```csv
Sub Brand Name,Default Brand ID,Additional Brands ID
Jordan,1,
```
- Creates "Jordan" sub-brand
- Default brand: Brand ID 1 (Nike)
- No additional brands

### Example 2: Sub-Brand with Additional Brands
```csv
Sub Brand Name,Default Brand ID,Additional Brands ID
Air Max,1,2 3
```
- Creates "Air Max" sub-brand
- Default brand: Brand ID 1 (Nike)
- Additional brands: Brand ID 2 (Adidas), Brand ID 3 (Puma)

### Example 3: Multiple Sub-Brands
```csv
Sub Brand Name,Default Brand ID,Additional Brands ID
Air Max,1,2 3
Jordan,1,
Dunk,1,2
Ultra Boost,2,1
Stan Smith,2,
```

## Validation Rules

### Required Fields
- Sub Brand Name: Cannot be empty
- Default Brand ID: Must be a valid brand ID

### Brand Validation
- Default Brand ID must exist in brands table
- Additional Brand IDs must exist in brands table
- Additional Brand IDs cannot be the same as Default Brand ID
- All brands must belong to the current user (created_by)

### Data Processing
- Empty rows are skipped
- Invalid brand IDs are logged as warnings
- Duplicate sub-brand names are allowed (different brands)

## Error Handling

### Common Issues:
1. **Invalid Brand ID**: Brand doesn't exist or doesn't belong to user
2. **Empty Sub Brand Name**: Row is skipped
3. **Invalid Additional Brand ID**: Additional brand is ignored
4. **Duplicate Default/Additional**: Additional brand same as default is ignored

### Logging:
- All imports are logged with success/warning details
- Check `storage/logs/laravel.log` for detailed information

## Tips

### Best Practices:
1. **Test with Small Files**: Start with a few rows to test the format
2. **Verify Brand IDs**: Ensure all brand IDs exist before importing
3. **Use Consistent Formatting**: Follow the exact column headers
4. **Check Logs**: Review logs after import for any issues

### Data Preparation:
1. **Export First**: Use the export feature to see the exact format
2. **Validate Brand IDs**: Check your brands table for valid IDs
3. **Clean Data**: Remove extra spaces and ensure consistent formatting

## Troubleshooting

### Import Fails:
- Check file format (must be .xlsx, .xls, or .csv)
- Verify column headers match exactly
- Ensure file is not corrupted

### Data Not Imported:
- Check logs for validation errors
- Verify brand IDs exist
- Ensure user has proper permissions

### Partial Import:
- Some rows may be skipped due to validation errors
- Check logs to see which rows failed and why
- Fix data and re-import failed rows

## Support

If you encounter issues:
1. Check the application logs
2. Verify your data format matches the examples
3. Ensure all referenced brands exist in your system
4. Contact your system administrator if problems persist
