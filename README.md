# PartyPlanningCommittee

Welcome to Belsnickel's Party Planning Committee repository! This repository contains tools and documentation for planning office celebrations and holiday parties.

## 🎄 Holiday Tracker

Use the `Generate-HolidayTracker.ps1` PowerShell script to create a comprehensive markdown document listing all office holidays for the year.

### Usage

```powershell
# Generate holiday tracker for current year
.\Generate-HolidayTracker.ps1

# Generate for a specific year
.\Generate-HolidayTracker.ps1 -Year 2025

# Generate with custom output file
.\Generate-HolidayTracker.ps1 -OutputFile "MyHolidays.md"
```

### Features

- Fetches holiday data from public API (date.nager.at)
- Includes fallback data for reliable operation
- Generates formatted markdown with holiday categories
- Provides Belsnickel's party planning guidelines
- Includes judgmental commentary on celebration importance

The script will generate a `HolidayTracker.md` file with all holidays organized by importance and includes comprehensive party planning guidelines.

**Belsnickel's Note:** This script judges your holiday planning efforts. Use it wisely to avoid coal distribution!