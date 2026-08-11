# 🛡️ ANTIGRAVITY INTEGRITY AUDITOR v1.2.1
# "La Ley se cumple o se documenta"

$projectRoot = $PSScriptRoot
$violations = 0

Write-Host "`n🏛️ INICIANDO AUDITORÍA DE INTEGRIDAD ÉLITE..." -ForegroundColor Cyan
Write-Host "------------------------------------------------"

# 1. ESCANEO DE COLORES HARDCODEADOS (CSS)
Write-Host "`n🔍 Escaneando Colores Hardcodeados en CSS..." -ForegroundColor Yellow
$cssFiles = Get-ChildItem -Path "$projectRoot\styles" -Filter *.css -Recurse
foreach ($file in $cssFiles) {
    $content = Get-Content $file.FullName
    $lineNum = 0
    foreach ($line in $content) {
        $lineNum++
        $trimmed = $line.Trim()
        # Buscar #HEX
        if ($trimmed -match '#[a-fA-F0-9]{3,6}') {
            if ($trimmed -notmatch 'var\(--el-') {
                Write-Host "  [!] HEX detectado en $($file.Name):$lineNum -> $trimmed" -ForegroundColor Red
                $violations++
            }
        }
        # Buscar rgba fijos
        if ($trimmed -match 'rgba?\(') {
            if ($trimmed -notmatch 'var\(--el-') {
                Write-Host "  [!] RGB fijo en $($file.Name):$lineNum -> $trimmed" -ForegroundColor Red
                $violations++
            }
        }
    }
}

# 2. ESCANEO DE PROPIEDADES FÍSICAS (CSS)
Write-Host "`n🔍 Escaneando Propiedades Físicas (Logical Properties Violation)..." -ForegroundColor Yellow
foreach ($file in $cssFiles) {
    $content = Get-Content $file.FullName
    $lineNum = 0
    foreach ($line in $content) {
        $lineNum++
        $l = $line.ToLower()
        if ($l -like '*-left*' -or $l -like '*-right*' -or $l -like '*left:*' -or $l -like '*right:*') {
            Write-Host "  [!] Dirección Física en $($file.Name):$lineNum -> $($line.Trim())" -ForegroundColor Red
            $violations++
        }
    }
}

# 3. ESCANEO DE ESTILOS INLINE (PHP)
Write-Host "`n🔍 Escaneando Estilos Inline en PHP..." -ForegroundColor Yellow
$phpFiles = Get-ChildItem -Path "$projectRoot\php" -Filter *.php -Recurse
foreach ($file in $phpFiles) {
    $content = Get-Content $file.FullName
    $lineNum = 0
    foreach ($line in $content) {
        $lineNum++
        if ($line.ToLower().Contains('style=') -or $line.ToLower().Contains('style =')) {
            Write-Host "  [!] Style Inline en $($file.Name):$lineNum -> $($line.Trim())" -ForegroundColor Red
            $violations++
        }
    }
}

Write-Host "`n------------------------------------------------"
if ($violations -eq 0) {
    Write-Host "OK: SISTEMA INTEGRO. ESTANDAR ELITE CUMPLIDO." -ForegroundColor Green
} else {
    Write-Host "FAIL: SE HAN ENCONTRADO $violations VIOLACIONES DE INTEGRIDAD." -ForegroundColor DarkRed
}
Write-Host "------------------------------------------------"
