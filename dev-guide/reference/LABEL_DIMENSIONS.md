# Label Dimensions Reference

This document tracks supported label formats and their exact dimensions for printing.

## T&J (Tom & Jerry) 103

Used for printing QR codes and book metadata stickers.

| Attribute | Value |
|-----------|-------|
| **Label Name** | Tom & Jerry 103 |
| **Label Width** | 63 mm |
| **Label Height** | 31 mm |
| **Horizontal Pitch** | 67 mm |
| **Vertical Pitch** | 38 mm |
| **Top Margin** | 9 mm |
| **Side Margin** | 3 mm |
| **Number Across** | 3 |
| **Number Down** | 4 |
| **Page Width** | 210 mm |
| **Page Height** | 164 mm |

### Calculated Gaps (Internal use)
- **Horizontal Gap**: 4 mm (Pitch 67mm - Width 63mm)
- **Vertical Gap**: 7 mm (Pitch 38mm - Height 31mm)

### Configuration in System
This is stored in the `print_profiles` table and managed via the **Print Profiles** resource in Filament.
