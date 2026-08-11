$ErrorActionPreference = "Stop"

if (Test-Path "assets\vendor\katex") { Remove-Item -Path "assets\vendor\katex" -Recurse -Force }
Move-Item -Path "package\katex" -Destination "assets\vendor\katex" -Force
Remove-Item -Path "package" -Recurse -Force

Write-Host "Downloading MathLive..."
Invoke-WebRequest -Uri "https://registry.npmjs.org/mathlive/-/mathlive-0.109.2.tgz" -OutFile "mathlive.tgz"
Write-Host "Extracting MathLive..."
tar -xf mathlive.tgz
if (Test-Path "assets\vendor\mathlive") { Remove-Item -Path "assets\vendor\mathlive" -Recurse -Force }
if (Test-Path "package\dist") {
    Move-Item -Path "package\dist" -Destination "assets\vendor\mathlive" -Force
} else {
    Move-Item -Path "package" -Destination "assets\vendor\mathlive" -Force
}
if (Test-Path "package") { Remove-Item -Path "package" -Recurse -Force }
Remove-Item -Path "mathlive.tgz"

Write-Host "Done!"
