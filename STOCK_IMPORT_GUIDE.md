# Stock Import Guide

## Overview
This comprehensive import allows you to import your entire stock in one file, including:
- **Brands** (created automatically if they don't exist)
- **Sub Brands** (created automatically if they don't exist)
- **Products** (created automatically if they don't exist by SKU)
- **Sub Products** (new stock entries)
- **Custom Fields** (for both products and sub-products)

## Excel File Format

### Required Columns:
1. **Brand Name** - The name of the brand (will be created if doesn't exist)
2. **Product SKU** - Unique SKU for the product (will be created if doesn't exist)
3. **Sub Product No** - Product number/VIN for the sub-product (required)

### Optional Columns:

#### Product Information:
- **Sub Brand Name** - Sub-brand name (will be created if doesn't exist)
- **Product Name** - Product name (defaults to "Product {SKU}" if not provided)
- **Product Sale Price** or **Sale Price** - Base sale price (before VAT)
- **Product Purchase Price** or **Purchase Price** - Purchase price
- **Rent Price** - Rental price (default: 0)
- **Product Type** - Type of product (default: "product")
- **Product Description** - Product description

#### Category & Unit:
- **Category ID** - ID of the category
- **Category Name** - Name of the category (alternative to Category ID)
- **Unit ID** - ID of the unit
- **Unit Name** - Name of the unit (alternative to Unit ID)

#### Tax & VAT:
- **Tax** or **Tax ID** - Comma or semicolon separated tax IDs
- **VAT** or **VAT Rate** or **VAT Percentage** - Direct VAT rate percentage (e.g., 5, 10, 15). If provided, this will be used to calculate price with VAT. If not provided, VAT will be calculated from Tax IDs.

#### Sub Product Information:
- **Sub Product Sale Price** - Sale price for this specific sub-product
- **Sub Product Purchase Price** - Purchase price for this specific sub-product
- **Quantity** or **Sub Product Quantity** - Quantity for this sub-product (default: 1) - **Note**: Only used if warehouse columns are not provided
- **Initial Stock** - Initial stock quantity (defaults to quantity)
- **Initial Rate** - Initial rate (defaults to purchase price)
- **Warehouse ID** - Warehouse ID where stock is located - **Note**: Only used if warehouse columns are not provided
- **Sub Product SKU** - SKU for the sub-product

#### Warehouse Columns (Alternative to Warehouse ID):
- **Warehouse Name Columns** - You can add columns with warehouse names as headers (e.g., "Warehouse 1", "Main Warehouse", "Store A")
- Each warehouse column should contain the quantity for that warehouse
- The system will automatically detect warehouse columns by matching column names to warehouse names in your system
- For each warehouse column with quantity > 0, a separate sub-product entry will be created
- **Example**: If you have columns "Main Warehouse" and "Store A" with values 10 and 5, the system will create:
  - One sub-product with 10 units in "Main Warehouse"
  - One sub-product with 5 units in "Store A"
- **Priority**: If warehouse columns are detected, they take priority over "Warehouse ID" and "Quantity" columns

#### Custom Fields:
- For **Product Custom Fields**: Use column names like `Product {FieldName}`, `Product CF {FieldName}`, `CF {FieldName}`, or just `{FieldName}`
- For **Sub Product Custom Fields**: Use column names like `Sub Product {FieldName}`, `Sub Product CF {FieldName}`, `CF {FieldName}`, or just `{FieldName}`

## Example Formats:

### Example 1: Using Warehouse Columns (Recommended for multiple warehouses per product)

```csv
Brand Name,Sub Brand Name,Product SKU,Product Name,Category Name,Unit Name,Product Sale Price,Product Purchase Price,VAT,Sub Product No,Warehouse 1,Warehouse 2,Warehouse 3,Model,Color,VIN
Nike,Air Max,SKU-001,Air Max 90,Shoes,Piece,150.00,100.00,5,VIN001,5,10,3,2024,Black,VIN001
Nike,Jordan,SKU-002,Jordan 1,Shoes,Piece,200.00,150.00,10,VIN003,15,5,10,2024,Red,VIN003
Adidas,Ultra Boost,SKU-003,Ultra Boost 22,Shoes,Piece,180.00,120.00,15,VIN004,8,12,4,2024,Blue,VIN004
```

**Note**: 
- Warehouse column names (e.g., "Warehouse 1", "Warehouse 2") must match warehouse names in your system
- Each warehouse column contains the quantity for that warehouse
- The system will create separate sub-product entries for each warehouse with quantity > 0
- In the example above, VIN001 will create:
  - 5 units in "Warehouse 1"
  - 10 units in "Warehouse 2"
  - 3 units in "Warehouse 3"

### Example 2: Using Traditional Warehouse ID (Single warehouse per row)

```csv
Brand Name,Sub Brand Name,Product SKU,Product Name,Category Name,Unit Name,Product Sale Price,Product Purchase Price,VAT,Sub Product No,Quantity,Warehouse ID,Model,Color,VIN
Nike,Air Max,SKU-001,Air Max 90,Shoes,Piece,150.00,100.00,5,VIN001,1,1,2024,Black,VIN001
Nike,Air Max,SKU-001,Air Max 90,Shoes,Piece,150.00,100.00,5,VIN002,1,1,2024,White,VIN002
Nike,Jordan,SKU-002,Jordan 1,Shoes,Piece,200.00,150.00,10,VIN003,2,1,2024,Red,VIN003
Adidas,Ultra Boost,SKU-003,Ultra Boost 22,Shoes,Piece,180.00,120.00,15,VIN004,1,2,2024,Blue,VIN004
```

**Note**: 
- The VAT column is optional. If provided, it will be used to calculate price with VAT. If not provided, VAT will be calculated from Tax IDs.
- If warehouse columns are present, they take priority over "Warehouse ID" and "Quantity" columns.

## Column Details

### Brand Name
- **Required**: Yes
- **Type**: Text
- **Description**: Brand name (e.g., "Nike", "Adidas")
- **Behavior**: Creates brand if it doesn't exist

### Sub Brand Name
- **Required**: No
- **Type**: Text
- **Description**: Sub-brand name (e.g., "Air Max", "Jordan")
- **Behavior**: Creates sub-brand if it doesn't exist, links to brand

### Product SKU
- **Required**: Yes
- **Type**: Text (must be unique)
- **Description**: Unique SKU for the product
- **Behavior**: Creates product if SKU doesn't exist, updates brand/sub-brand if different

### Product Name
- **Required**: No
- **Type**: Text
- **Description**: Product name
- **Default**: "Product {SKU}"

### Category Name / Category ID
- **Required**: Yes (one of them)
- **Type**: Text (name) or Number (ID)
- **Description**: Product category
- **Note**: Must exist in your system

### Unit Name / Unit ID
- **Required**: Yes (one of them)
- **Type**: Text (name) or Number (ID)
- **Description**: Product unit
- **Note**: Must exist in your system

### Product Sale Price / Sale Price
- **Required**: No
- **Type**: Number
- **Description**: Base sale price (before VAT)
- **Note**: VAT will be automatically added if VAT column or taxes are specified

### Product Purchase Price / Purchase Price
- **Required**: No
- **Type**: Number
- **Description**: Purchase price
- **Default**: 0

### Sub Product No
- **Required**: Yes
- **Type**: Text
- **Description**: Unique product number/VIN for the sub-product
- **Note**: Each row creates a new sub-product entry

### Quantity / Sub Product Quantity
- **Required**: No
- **Type**: Number
- **Description**: Quantity for this sub-product
- **Default**: 1

### Warehouse ID
- **Required**: No
- **Type**: Number
- **Description**: Warehouse where stock is located
- **Note**: Must exist in your system

### Tax / Tax ID
- **Required**: No
- **Type**: Text (comma or semicolon separated IDs)
- **Description**: Tax IDs (e.g., "1,2" or "1;2")
- **Note**: Must exist in your system. VAT will be calculated from these tax rates if VAT column is not provided.

### VAT / VAT Rate / VAT Percentage
- **Required**: No
- **Type**: Number (percentage, e.g., 5, 10, 15)
- **Description**: Direct VAT rate percentage to calculate price with VAT
- **Note**: If provided, this takes priority over calculating VAT from Tax IDs. The system will calculate: Price with VAT = Base Price × (1 + VAT Rate / 100)
- **Example**: If base price is 100 and VAT is 5, the price with VAT will be 105

## Custom Fields

Custom fields are automatically detected based on your system's custom field definitions.

### Product Custom Fields
- Column names can be:
  - `Product {FieldName}` (e.g., "Product Model")
  - `Product CF {FieldName}` (e.g., "Product CF Model")
  - `CF {FieldName}` (e.g., "CF Model")
  - `{FieldName}` (e.g., "Model")

### Sub Product Custom Fields
- Column names can be:
  - `Sub Product {FieldName}` (e.g., "Sub Product VIN")
  - `Sub Product CF {FieldName}` (e.g., "Sub Product CF VIN")
  - `CF {FieldName}` (e.g., "CF VIN")
  - `{FieldName}` (e.g., "VIN")

## Import Process

### Step 1: Prepare Your Data
1. Create an Excel/CSV file with the required columns
2. Ensure categories and units exist in your system (or use names that exist)
3. Ensure warehouses exist if using Warehouse ID
4. Use the correct column headers (case-insensitive)

### Step 2: Upload File
1. Go to Products → Stock Import (or use the import button)
2. Select your Excel/CSV file
3. Click Upload

### Step 3: Verification
1. Check the logs for any warnings or errors
2. Verify imported brands, sub-brands, products, and sub-products
3. Confirm custom field values are saved correctly

## Important Notes

1. **Duplicate Prevention**: 
   - Products are identified by SKU (won't overwrite existing products)
   - Sub-products are identified by Product No + Product ID (won't create duplicates)

2. **Hierarchical Creation**:
   - Brands are created first
   - Sub-brands are created and linked to brands
   - Products are created and linked to brands/sub-brands
   - Sub-products are created and linked to products

3. **Custom Fields**:
   - Product custom fields are saved to the product
   - Sub-product custom fields are saved to the sub-product
   - Custom fields must exist in your system (module: 'product' or 'sub-product')

4. **VAT Calculation**:
   - **Priority 1**: If VAT column is provided, it will be used directly to calculate price with VAT
   - **Priority 2**: If VAT column is not provided, VAT will be calculated from Tax IDs
   - Formula: Price with VAT = Base Price × (1 + VAT Rate / 100)
   - Base sale price is stored in `sale_price_base`
   - Price with VAT is stored in `sale_price`
   - Example: Base price 100, VAT 5% → Price with VAT = 105

5. **Stock Quantity and Warehouse Distribution**:
   - **Using Warehouse Columns**: If warehouse columns are detected, the system creates one sub-product entry per warehouse with quantity > 0
   - **Using Warehouse ID**: If no warehouse columns are found, a single sub-product is created with the specified warehouse_id and quantity
   - Each warehouse column should contain the quantity for that specific warehouse
   - Warehouse column names must match warehouse names in your system (case-insensitive)
   - If product type is "product", multiple sub-products (quantity = 1 each) may be created

## Troubleshooting

### Common Errors:

1. **"Category is required"**
   - Solution: Provide either Category ID or Category Name column

2. **"Unit is required"**
   - Solution: Provide either Unit ID or Unit Name column

3. **"Category not found"**
   - Solution: Ensure the category exists in your system or create it first

4. **"Unit not found"**
   - Solution: Ensure the unit exists in your system or create it first

5. **Custom fields not saving**
   - Solution: Ensure custom fields exist in your system with the correct module ('product' or 'sub-product')

## Example with Warehouse Columns and Custom Fields:

```csv
Brand Name,Sub Brand Name,Product SKU,Product Name,Category Name,Unit Name,Product Sale Price,Product Purchase Price,VAT,Sub Product No,Warehouse 1,Warehouse 2,Product Model,Product Year,Sub Product VIN,Sub Product Color,Sub Product Engine No
Toyota,Camry,TOY-CAM-2024,Toyota Camry 2024,Cars,Unit,25000.00,20000.00,5,CAM001,1,0,2024,2024,CAM001-VIN-001,Black,ENG001
Toyota,Camry,TOY-CAM-2024,Toyota Camry 2024,Cars,Unit,25000.00,20000.00,5,CAM002,0,1,2024,2024,CAM002-VIN-002,White,ENG002
Honda,Accord,HON-ACC-2024,Honda Accord 2024,Cars,Unit,28000.00,22000.00,10,ACC001,2,1,2024,2024,ACC001-VIN-001,Silver,ENG003
```

In this example:
- "Product Model" and "Product Year" are product custom fields
- "Sub Product VIN", "Sub Product Color", and "Sub Product Engine No" are sub-product custom fields
- "Warehouse 1" and "Warehouse 2" are warehouse columns with quantities
- CAM001 creates 1 unit in "Warehouse 1" and 0 in "Warehouse 2" (only Warehouse 1 entry is created)
- ACC001 creates 2 units in "Warehouse 1" and 1 unit in "Warehouse 2" (both entries are created)
- The VAT column (5, 5, 10) will be used to calculate price with VAT. For example:
  - Toyota Camry: Base price 25000, VAT 5% → Price with VAT = 26250
  - Honda Accord: Base price 28000, VAT 10% → Price with VAT = 30800

In this example:
- "Product Model" and "Product Year" are product custom fields
- "Sub Product VIN", "Sub Product Color", and "Sub Product Engine No" are sub-product custom fields

