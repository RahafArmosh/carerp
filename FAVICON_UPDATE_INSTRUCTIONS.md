# Favicon Update Instructions for Orbix

## Overview
The project name has been changed to "Orbix" throughout the application. To complete the branding update, you need to replace the favicon files.

## Favicon Files to Replace

The following favicon files should be replaced with your Orbix favicon:

1. **`public/favicon.ico`** - Main favicon file (16x16, 32x32, 48x48 sizes)
2. **`public/assets/images/favicon.png`** - PNG version of favicon
3. **`public/assets/images/favicon.svg`** - SVG version of favicon (optional, for modern browsers)

## Recommended Favicon Sizes

- **favicon.ico**: Should contain multiple sizes (16x16, 32x32, 48x48)
- **favicon.png**: 32x32 or 64x64 pixels
- **favicon.svg**: Vector format (scalable)

## How to Create Favicon

1. Create your Orbix logo/icon design
2. Use an online favicon generator (e.g., https://favicon.io, https://realfavicongenerator.net)
3. Generate all required formats and sizes
4. Replace the files listed above

## Default Favicon Location

The application uses company-specific favicons stored in:
- `public/uploads/logo/favicon.png` (or company-specific favicon)
- Falls back to `public/assets/images/favicon.png` if no company favicon is set

## Testing

After replacing the favicon files:
1. Clear browser cache (Ctrl+F5 or Cmd+Shift+R)
2. Check browser tab to see new favicon
3. Verify favicon appears in all pages

## Note

The favicon references in the code are already configured to use the correct paths. You only need to replace the actual image files.

