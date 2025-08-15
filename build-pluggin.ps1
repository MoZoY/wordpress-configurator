# Set variables
$pluginFolder = "naro-config"
$buildFolder = "build"
$mainFile = "$pluginFolder\naro-config.php"
$versionPrefix = "0.3"
$versionDate = Get-Date -Format "yyyyMMdd"
$versionTime = Get-Date -Format "HHmmss"
$newVersion = "$versionPrefix.$versionDate.$versionTime"

# Update version in PHP file
(Get-Content $mainFile) -replace '(Version:\s*)([^\r\n]+)', "`${1}$newVersion" | Set-Content $mainFile

# Ensure build folder exists
if (-Not (Test-Path $buildFolder)) {
    New-Item -ItemType Directory -Path $buildFolder
}

# Create ZIP
$zipName = "$buildFolder\naro-config-$versionPrefix.zip"
if (Test-Path $zipName) { Remove-Item $zipName }
Compress-Archive -Path $pluginFolder\* -DestinationPath $zipName

# Check for additionnal files to include in the package
$additionalFiles = "build-includes.txt"
if (Test-Path $additionalFiles) {
    $filesToAdd = Get-Content $additionalFiles
    foreach ($file in $filesToAdd) {
        if (Test-Path $file) {
            Compress-Archive -Path $file -Update -DestinationPath $zipName
        }
    }
}

Write-Host "Packaged as $zipName with version $newVersion"