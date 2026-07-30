# PDF Field Mapping Guide

## Overview
Your PhilHealth PMRF PDF doesn't have interactive form fields, so we need to use **coordinate-based text placement**. This means we place text at specific X,Y coordinates on the PDF.

## How to Map Fields

### Step 1: Get the Coordinate Grid
1. Click the **"📐 Get Coordinate Grid"** button in your PhilHealth PMRF page
2. This downloads a PDF with a coordinate grid overlay
3. Print it or open it side-by-side with your actual PDF template

### Step 2: Find Field Positions
1. Open your actual `philhealth_PMRF.pdf` template
2. For each field you want to fill, note its position:
   - **X coordinate**: Distance from the LEFT edge (in points, where 1 inch = 72 points)
   - **Y coordinate**: Distance from the BOTTOM edge (in points)
   - **Important**: Y=0 is at the BOTTOM, Y=792 is at the TOP

### Step 3: Update Coordinates
1. Open `pr-frontend/src/config/pdfFieldCoordinates.js`
2. Find the field you want to map
3. Update the `x` and `y` values with the coordinates you found
4. Adjust `fontSize` and `maxWidth` as needed

### Example:
```javascript
member_last_name: {
  x: 50,      // 50 points from left edge
  y: 700,     // 92 points from top (792 - 700 = 92)
  fontSize: 10,
  maxWidth: 150,
},
```

## Quick Reference

### PDF Coordinate System
- **Page Size**: 612 x 792 points (8.5" x 11" = US Letter)
- **X-axis**: 0 (left) → 612 (right)
- **Y-axis**: 0 (bottom) → 792 (top)
- **1 inch = 72 points**

### Converting Measurements
- **Inches to Points**: `inches × 72`
- **Millimeters to Points**: `(mm ÷ 25.4) × 72`
- **Y from Top**: `Y = 792 - distanceFromTop`

## Tools to Help Find Coordinates

### Method 1: Using Adobe Acrobat
1. Open PDF in Adobe Acrobat
2. Go to Tools → Prepare Form
3. Hover over fields to see coordinates
4. Or use the measuring tool

### Method 2: Using PDF.js (Browser)
1. Open PDF in browser
2. Right-click → Inspect
3. Look for coordinate information in developer tools

### Method 3: Manual Measurement
1. Print your PDF template
2. Use a ruler to measure from edges
3. Convert to points:
   - If field is 1.5" from left: `1.5 × 72 = 108 points`
   - If field is 2" from top: `792 - (2 × 72) = 648 points`

## Field Mapping Checklist

Update coordinates for these fields in `pdfFieldCoordinates.js`:

### Personal Details
- [ ] `member_last_name`
- [ ] `member_first_name`
- [ ] `member_middle_name`
- [ ] `member_name_extension`
- [ ] `date_of_birth_month`
- [ ] `date_of_birth_day`
- [ ] `date_of_birth_year`
- [ ] `place_of_birth`

### Identification Numbers
- [ ] `philhealth_no`
- [ ] `tin_no`
- [ ] `philsys_id` (if needed)

### Contact Information
- [ ] `mobile_number`
- [ ] `email`
- [ ] `home_phone` (if needed)
- [ ] `business_phone` (if needed)

### Addresses
- [ ] `permanent_address`
- [ ] `permanent_zip`
- [ ] `mailing_address`
- [ ] `mailing_zip`

### Checkboxes
- [ ] `purpose_registration`
- [ ] `purpose_updating`
- [ ] `sex_male`
- [ ] `sex_female`
- [ ] `civil_status_single`
- [ ] `civil_status_married`
- [ ] `citizenship_filipino`
- [ ] `citizenship_dual`
- [ ] `citizenship_foreign`

## Testing Your Mappings

1. Update coordinates in `pdfFieldCoordinates.js`
2. Select an employee in the form
3. Click "Preview Form"
4. Check the console for placement logs
5. Verify text appears in correct positions
6. Adjust coordinates as needed

## Tips

- **Start with one field** to get the coordinate system right
- **Use the coordinate grid PDF** as a reference
- **Test incrementally** - don't map all fields at once
- **Font size matters** - adjust if text is too big/small
- **Multiline text** - use `multiline: true` for long addresses
- **Checkbox positions** - may need fine-tuning for X marks

## Need Help?

If you're stuck:
1. Check browser console for error messages
2. Verify PDF template path is correct
3. Ensure coordinates are within page bounds (0-612 for X, 0-792 for Y)
4. Test with a simple field first (like last_name)

