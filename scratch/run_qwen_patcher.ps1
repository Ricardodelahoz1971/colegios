param (
    [string]$FilePath,
    [string]$PromptFile
)

if (-not (Test-Path $FilePath)) {
    Write-Error "El archivo no existe: $FilePath"
    exit 1
}

$Prompt = [System.IO.File]::ReadAllText($PromptFile, [System.Text.Encoding]::UTF8)
$OriginalContent = [System.IO.File]::ReadAllText($FilePath, [System.Text.Encoding]::UTF8)

# Formular el cuerpo de la peticion a Ollama (sin enviar el archivo completo para optimizar velocidad)
$SystemPrompt = @"
Eres un programador experto. Debes generar unicamente un JSON que represente una lista de reemplazos para modificar el archivo recibido.
El formato debe ser un JSON Array de objetos:
[
  {
    "target": "bloque de texto exacto a buscar (debe ser unico y existir en el archivo)",
    "replacement": "bloque de texto con las modificaciones aplicadas"
  }
]
Reglas:
1. No incluyas explicaciones.
2. No incluyas markdown (sin ```json).
3. Devuelve estrictamente el JSON crudo.
4. El target debe ser exacto, respetando saltos de linea y espacios.
"@

$FullPrompt = "INSTRUCCIONES DE MODIFICACION:`n$Prompt"

$Body = @{
    model = "qwen2.5-coder:3b"
    system = $SystemPrompt
    prompt = $FullPrompt
    stream = $false
    options = @{
        temperature = 0.0
    }
} | ConvertTo-Json -Depth 10

# Realizar la peticion a Ollama
Write-Host "Enviando solicitud a Qwen en Ollama para obtener parches rapidos..."
$Response = Invoke-RestMethod -Uri "http://localhost:11434/api/generate" -Method Post -Body $Body -ContentType "application/json; charset=utf-8"

if ($Response -and $Response.response) {
    $JsonResponse = $Response.response.Trim()
    
    # Limpiar posibles bloques markdown si Qwen ignoro la regla
    $JsonResponse = $JsonResponse -replace '^```json\r?\n', ''
    $JsonResponse = $JsonResponse -replace '^```\r?\n', ''
    $JsonResponse = $JsonResponse -replace '\r?\n```$', ''
    
    Write-Host "Respuesta JSON recibida de Qwen:"
    Write-Host $JsonResponse
    
    try {
        $Replacements = ConvertFrom-Json $JsonResponse
        
        $ModifiedContent = $OriginalContent
        foreach ($Rep in $Replacements) {
            $Target = $Rep.target
            $Replacement = $Rep.replacement
            
            if (-not $ModifiedContent.Contains($Target)) {
                Write-Error "El texto target no fue encontrado exactamente en el archivo: $Target"
                exit 1
            }
            
            $ModifiedContent = $ModifiedContent.Replace($Target, $Replacement)
        }
        
        [System.IO.File]::WriteAllText($FilePath, $ModifiedContent, [System.Text.Encoding]::UTF8)
        Write-Host "Archivo modificado exitosamente por parches de Qwen: $FilePath"
    } catch {
        Write-Error "Error al procesar el JSON de parches: $_"
        exit 1
    }
} else {
    Write-Error "No se recibio respuesta valida de Ollama."
    exit 1
}
