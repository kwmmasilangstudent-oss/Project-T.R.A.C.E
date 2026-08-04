# Resident Profiling Implementation Plan

## Task Overview
Create a comprehensive resident profiling administrative interface with:
- System theme adaptation (dark mode following system preferences)
- Clear visibility in light mode
- Tabbed form interface for structured data entry
- Input masking for specific fields (PCN, currency formats)
- Select dropdowns for enums
- Clear boolean switches/checkboxes for indicators
- Clean, comment-free source code

## Implementation Plan

1. **Theme Handling**
   - Ensure .light class is applied when theme='light' in settings
   - Implement system theme detection via prefers-color-scheme media query
   - Ensure sidebar follows system theme while maintaining visibility

2. **CSS Modifications**
   - Add .light class with proper light mode variables
   - Update body background for light mode
   - Adjust text colors for visibility in light mode
   - Ensure all form elements remain readable

3. **Sidebar Adaptation**
   - Remove hardcoded dark mode styles from sidebar
   - Implement system theme adaptation via prefers-color-scheme
   - Maintain readability in both light and dark modes

4. **Form Implementation**
   - Add PCN field with proper masking
   - Implement dynamic age calculation from birthdate
   - Add conditional logic for PWD disability type field
   - Ensure all form elements are visible in light mode

5. **Verification**
   - Test theme switching functionality
   - Verify all form elements remain clearly visible
   - Confirm all validation and dynamic features work correctly

## Implementation Steps

1. **Theme Configuration**
   - Ensure theme setting defaults to 'light' in database
   - Verify header.php applies correct class based on theme setting

2. **CSS Enhancements**
   - Add .light class with comprehensive light mode variables
   - Update body background for light mode
   - Adjust text colors for optimal visibility
   - Ensure all UI components adapt properly

3. **Sidebar Implementation**
   - Remove hardcoded dark mode styling from sidebar
   - Implement system theme adaptation via prefers-color-scheme
   - Ensure fonts remain visible in light mode

4. **Form Implementation**
   - Add PCN field with proper masking
   - Implement age calculation from birthdate
   - Add conditional PWD field visibility
   - Ensure all fields have proper validation

5. **Verification**
   - Test system theme adaptation
   - Verify light mode visibility across all pages
   - Confirm all required functionality works as expected

## Implementation Steps

1. Create .light class with proper light mode variables
2. Update body background for light mode
3. Modify sidebar to follow system theme without losing visibility
4. Implement dynamic age calculation in JavaScript
5. Add conditional PWD field display
6. Verify all pages render correctly in light mode