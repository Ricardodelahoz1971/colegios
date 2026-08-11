# 🏛️ MANUAL DE DESPLIEGUE SEGURO: EXCLUSIÓN DE AUTOLOGIN
**Autor:** Ingeniero de Seguridad Ares  
**Aprobado por:** Ing. Ricardo  
**Estatus:** NORMA INNEGOCIABLE DE DESPLIEGUE A PRODUCCIÓN  

---

## 1. 🎯 Objetivo
Garantizar con un 100% de efectividad que el archivo de desarrollo rápido `php/autologin.php` sea **físicamente erradicado** de cualquier empaquetado, compilación o transferencia destinada al servidor web de producción.

---

## 2. 🛡️ La Regla de Oro
> [!IMPORTANT]
> **NORMA DE PUREZA #24:**  
> Ningún despliegue a producción será considerado válido, seguro o certificado si el archivo `php/autologin.php` existe en el directorio de destino. Se prohíbe confiar exclusivamente en el bloqueo por IP (`127.0.0.1`) en entornos productivos debido al riesgo de suplantación de IP (IP Spoofing) a través de proxies inversos mal configurados.

---

## 3. 🛠️ Métodos de Automatización para la Exclusión

### Método A: Si se despliega usando Git (Recomendado)
Para evitar que el archivo sea subido accidentalmente al repositorio central de producción, agregue la ruta al archivo `.gitignore` local:

1. Abra o cree el archivo `.gitignore` en la raíz del proyecto.
2. Inyecte la siguiente regla explícita:
   ```text
   # Excluir puerta de desarrollo autologin de producción
   php/autologin.php
   ```
3. Si el archivo ya estaba rastreado por Git, ejecute el siguiente comando para removerlo del índice sin borrarlo físicamente de su entorno local de desarrollo:
   ```bash
   git rm --cached php/autologin.php
   ```

---

### Método B: Si se despliega usando Rsync (Servidores Linux/Unix)
Si transfiere los archivos mediante Rsync en sus scripts de despliegue, añada el parámetro `--exclude` para evitar que el archivo viaje por la red:

```bash
rsync -avz --exclude='php/autologin.php' ./sistema_escolar/ usuario@tuservidor.com:/var/www/html/sistema_escolar/
```

---

### Método C: Si se despliega mediante un Script de Build (Ejemplo en Windows PowerShell)
Si genera un archivo zip o empaqueta el sistema de forma manual para cargarlo a un panel de control (cPanel, Plesk), ejecute este script limpiador antes de empaquetar:

```powershell
# 🧹 Script de Limpieza Pre-Despliegue Ares (Windows PowerShell)
$ProductionBuildPath = "C:\Users\Admin\Desktop\sistema_escolar_build"

# 1. Copiar el proyecto a una carpeta temporal de compilación
Copy-Item -Path "c:\xampp\htdocs\sistema_escolar" -Destination $ProductionBuildPath -RecurRecurse -Force

# 2. Purgar físicamente el autologin de la compilación
$AutologinPath = Join-Path $ProductionBuildPath "php\autologin.php"
if (Test-Path $AutologinPath) {
    Remove-Item -Path $AutologinPath -Force
    Write-Host "✅ [SEGURIDAD] autologin.php fue físicamente purgado de la carpeta de producción." -ForegroundColor Green
} else {
    Write-Host "⚠️ [ADVERTENCIA] autologin.php no se encontró en la ruta de compilación." -ForegroundColor Yellow
}
```

---

## 4. 📋 Checklist de Validación Manual (Antes de salir a producción)
Antes de declarar el sistema como "Listo para Producción", el Ingeniero Ricardo o el auditor líder deben validar manualmente los siguientes tres puntos:

- `[ ]` **Verificación de URL**: Intentar acceder de forma externa a `https://tuservidor.com/php/autologin.php`. El servidor debe retornar **`404 Not Found`** (si se aplicó la Opción A) o en su defecto **`403 Forbidden`** (si se aplicó la Opción B y el proxy está bien configurado).
- `[ ]` **Inspección de Archivos en Producción**: Acceder vía FTP / SSH a la carpeta de producción y validar que el archivo `php/autologin.php` **no exista**.
- `[ ]` **Auditoría de Logs**: Revisar que el auditor estático local `antigravity_auditor.php` retorne **`0 VIOLACIONES`** antes de dar el visto bueno al empaquetado final.
