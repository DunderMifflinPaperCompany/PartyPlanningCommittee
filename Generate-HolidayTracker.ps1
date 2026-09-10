# Belsnickel's Holiday Tracker Generator
# Belsnickel judges this script as ADMIRABLE for its robust holiday planning capabilities!

param(
    [int]$Year = (Get-Date).Year,
    [string]$Country = "US",
    [string]$OutputFile = "HolidayTracker.md"
)

# Belsnickel demands proper error handling - impish scripts that crash deserve coal!
$ErrorActionPreference = "Stop"

Write-Host "🎄 Belsnickel is generating the holiday tracker for year $Year..." -ForegroundColor Green
Write-Host "Belsnickel judges your holiday planning needs and finds them... adequate." -ForegroundColor Yellow

try {
    # Belsnickel approves of the date.nager.at API - admirable and free!
    $apiUrl = "https://date.nager.at/api/v3/PublicHolidays/$Year/$Country"
    $holidays = $null
    
    try {
        Write-Host "Fetching holidays from: $apiUrl" -ForegroundColor Cyan
        
        # Belsnickel demands robust API calls with proper headers
        $holidays = Invoke-RestMethod -Uri $apiUrl -Method Get -Headers @{
            "User-Agent" = "Belsnickel-Holiday-Tracker/1.0"
        } -TimeoutSec 10
        
        if ($holidays -and $holidays.Count -gt 0) {
            Write-Host "Belsnickel has retrieved $($holidays.Count) holidays from API. Admirable data collection!" -ForegroundColor Green
        } else {
            throw "API returned empty data"
        }
    } catch {
        Write-Host "⚠️ Impish network or API issue detected: $($_.Exception.Message)" -ForegroundColor Yellow
        Write-Host "🎄 Belsnickel will use his superior backup holiday data instead!" -ForegroundColor Cyan
        
        # Belsnickel's admirable fallback - predefined US holidays for reliable operation
        $holidays = @(
            @{ date = "$Year-01-01"; name = "New Year's Day"; global = $true }
            @{ date = "$Year-01-20"; name = "Martin Luther King Jr. Day"; global = $true }
            @{ date = "$Year-02-17"; name = "Presidents' Day"; global = $true }
            @{ date = "$Year-05-26"; name = "Memorial Day"; global = $true }
            @{ date = "$Year-06-19"; name = "Juneteenth"; global = $true }
            @{ date = "$Year-07-04"; name = "Independence Day"; global = $true }
            @{ date = "$Year-09-01"; name = "Labor Day"; global = $true }
            @{ date = "$Year-10-13"; name = "Columbus Day"; global = $false }
            @{ date = "$Year-11-11"; name = "Veterans Day"; global = $true }
            @{ date = "$Year-11-27"; name = "Thanksgiving Day"; global = $true }
            @{ date = "$Year-12-25"; name = "Christmas Day"; global = $true }
        )
        
        Write-Host "Belsnickel has loaded $($holidays.Count) backup holidays. Admirable preparedness!" -ForegroundColor Green
    }
    
    # Belsnickel judges holiday categorization as necessary for proper party planning
    $majorHolidays = @("New Year's Day", "Independence Day", "Thanksgiving Day", "Christmas Day", "Labor Day", "Memorial Day")
    
    # Generate markdown content with Belsnickel's superior formatting
    $markdownContent = @"
# 🎄 Belsnickel's Office Holiday Tracker $Year

*Belsnickel has judged these holidays worthy of office celebration. Choose wisely between impish neglect and admirable observance!*

> **Belsnickel's Decree:** This document contains all holidays that demand proper office recognition. 
> Failure to celebrate appropriately will result in coal distribution and disappointed glares.

## Holiday Calendar

The following holidays have been retrieved from the superior date.nager.at API and judged by Belsnickel:

| Date | Holiday Name | Type | Belsnickel's Judgment |
|------|--------------|------|---------------------|
"@

    # Sort holidays by date - Belsnickel demands chronological order!
    $sortedHolidays = $holidays | Sort-Object date
    
    foreach ($holiday in $sortedHolidays) {
        $date = [DateTime]::Parse($holiday.date).ToString("MMMM dd, yyyy")
        $dayOfWeek = [DateTime]::Parse($holiday.date).ToString("dddd")
        $name = $holiday.name
        $isNational = if ($holiday.global) { "National" } else { "Regional" }
        
        # Belsnickel's judgment on each holiday
        $judgment = if ($majorHolidays -contains $name) {
            "**ADMIRABLE** - Major celebration required! 🎉"
        } elseif ($holiday.global) {
            "**Admirable** - Office recognition recommended 👍"
        } else {
            "Adequate - Optional observance 📝"
        }
        
        $markdownContent += "`n| $date ($dayOfWeek) | $name | $isNational | $judgment |"
    }
    
    # Add Belsnickel's footer commentary
    $markdownContent += @"


## Belsnickel's Holiday Planning Guidelines

### 🎯 Major Holidays (Admirable Celebration Required)
These holidays demand full office participation with proper decorations, food, and festivities:
"@

    $majorCelebrations = $sortedHolidays | Where-Object { $majorHolidays -contains $_.name }
    foreach ($holiday in $majorCelebrations) {
        $date = [DateTime]::Parse($holiday.date).ToString("MMMM dd")
        $markdownContent += "`n- **$($holiday.name)** ($date) - Belsnickel expects excellence!"
    }

    $markdownContent += @"


### 📋 Holiday Planning Checklist
Belsnickel judges your party planning by these admirable standards:

- [ ] **Decorations**: Transform the office into a festive wonderland (impish cubicles will be noted)
- [ ] **Food & Treats**: Provide abundant refreshments (empty stomachs lead to impish behavior)
- [ ] **Activities**: Plan engaging team activities (boring parties earn coal)
- [ ] **Recognition**: Acknowledge team members' contributions (gratitude is admirable)
- [ ] **Photos**: Document the celebration for posterity (memories matter to Belsnickel)

### ⚡ Belsnickel's Party Planning Rules

1. **Impish Behaviors to Avoid:**
   - Last-minute planning (shows poor preparation)
   - Forgetting dietary restrictions (inconsiderate and impish)
   - Skipping decorations (lazy and uninspiring)
   - No team involvement (exclusion is impish)

2. **Admirable Practices:**
   - Plan celebrations 2-3 weeks in advance
   - Include everyone in the festivities
   - Respect all dietary needs and preferences
   - Create lasting positive memories

---

*Generated by Belsnickel's Holiday Tracker on $(Get-Date -Format "MMMM dd, yyyy 'at' HH:mm")*  
*API Source: [date.nager.at](https://date.nager.at) with Belsnickel's admirable backup data*

**Remember:** Belsnickel is always watching your party planning efforts. Choose between impish neglect and admirable celebration!
"@

    # Write the file with Belsnickel's blessing
    $markdownContent | Out-File -FilePath $OutputFile -Encoding UTF8
    
    Write-Host "✅ Belsnickel has successfully generated '$OutputFile'" -ForegroundColor Green
    Write-Host "🎄 The holiday tracker contains $($holidays.Count) holidays for your admirable party planning!" -ForegroundColor Cyan
    Write-Host "📝 Belsnickel judges this output as ADMIRABLE - use it wisely!" -ForegroundColor Yellow

} catch {
    Write-Error "❌ Impish error occurred while generating holiday tracker: $($_.Exception.Message)"
    Write-Host "🎄 Belsnickel is displeased with this failure! Check your internet connection and try again." -ForegroundColor Red
    exit 1
}