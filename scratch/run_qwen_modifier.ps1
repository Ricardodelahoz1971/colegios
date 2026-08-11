param (
    [string]$FilePath,
    [string]$Prompt,
    [string]$PromptFile
)

if (-not (Test-Path $FilePath)) {
    Write-Error "El archivo no existe: $FilePath"
    exit 1
}

# Leer el prompt desde el archivo si se especifica
if ($PromptFile -and (Test-Path $PromptFile)) {
    $Prompt = [System.IO.File]::ReadAllText($PromptFile, [System.Text.Encoding]::UTF8)
}

# Leer el contenido del archivo original
$OriginalContent = [System.IO.File]::ReadAllText($FilePath, [System.Text.Encoding]::UTF8)

# Formular el cuerpo de la peticion a Ollama
$SystemPrompt = "Eres un programador experto. Recibiras el contenido de un archivo de codigo y una instruccion de modificacion. Tu tarea es generar y devolver exclusivamente el contenido COMPLETO del archivo actualizado. NO incluyas explicaciones, NO incluyas formato Markdown, NO incluyas bloques de codigo con ```. Devuelve unicamente el codigo fuente plano listo para ser guardado."
$FullPrompt = "CONTENIDO ORIGINAL DEL ARCHIVO:`n$OriginalContent`n`nINSTRUCCIONES DE MODIFICACION:`n$Prompt"

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
Write-Host "Enviando solicitud a Qwen en Ollama..."
$Response = Invoke-RestMethod -Uri "http://localhost:11434/api/generate" -Method Post -Body $Body -ContentType "application/json; charset=utf-8"

if ($Response -and $Response.response) {
    # Guardar el contenido modificado de vuelta al archivo
    $NewContent = $Response.response
    # Remover posibles tags markdown que Qwen pudiera haber agregado por error
    $NewContent = $NewContent -replace '^```[a-zA-Z0-9]*\r?\n', ''
    $NewContent = $NewContent -replace '\r?\n```$', ''
    
    [System.IO.File]::WriteAllText($FilePath, $NewContent, [System.Text.Encoding]::UTF8)
    Write-Host "Archivo modificado exitosamente por Qwen: $FilePath"
} else {
    Write-Error "No se recibio respuesta valida de Ollama."
}
