# Set variables
$pluginFolder = "naro-config"
$outputFolder = "build"
$mainFile = "$pluginFolder\naro-config.php"
$versionPrefix = "0.1"
$versionDate = Get-Date -Format "yyyyMMdd"
$versionTime = Get-Date -Format "HHmmss"
$newVersion = "$versionPrefix.$versionDate.$versionTime"

# Update version in PHP file
(Get-Content $mainFile) -replace '(Version:\s*)([^\r\n]+)', "`${1}$newVersion" | Set-Content $mainFile

# Create ZIP
$zipName = "$outputFolder\naro-config-$versionPrefix.zip"
if (-Not (Test-Path $outputFolder)) {
    New-Item -ItemType Directory -Path $outputFolder
}
if (Test-Path $zipName) { Remove-Item $zipName }
Compress-Archive -Path $pluginFolder -DestinationPath $zipName

Write-Host "Packaged as $zipName with version $newVersion"