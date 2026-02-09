# Check folder sizes
Get-ChildItem "C:\dev\maxtdesign-cookie-consent" -Directory | ForEach-Object {
    $folder = $_
    $size = (Get-ChildItem $folder.FullName -Recurse -File -ErrorAction SilentlyContinue | Measure-Object -Property Length -Sum).Sum
    [PSCustomObject]@{
        Folder = $folder.Name
        SizeKB = [math]::Round($size/1KB, 2)
    }
} | Sort-Object SizeKB -Descending | Format-Table -AutoSize
