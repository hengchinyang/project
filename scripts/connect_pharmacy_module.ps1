param(
    [string]$PharmacyRoot = 'C:\xampp\htdocs\MediCareConnect-Pharmacy-Lawliet\MediCareConnect-Pharmacy-Lawliet',
    [string]$PatientRoot = 'C:\xampp\htdocs\project'
)

$ErrorActionPreference = 'Stop'
$expectedPharmacyRoot = 'C:\xampp\htdocs\MediCareConnect-Pharmacy-Lawliet\MediCareConnect-Pharmacy-Lawliet'
$expectedPatientRoot = 'C:\xampp\htdocs\project'
$resolvedPharmacyRoot = (Resolve-Path -LiteralPath $PharmacyRoot).Path
$resolvedPatientRoot = (Resolve-Path -LiteralPath $PatientRoot).Path
if ($resolvedPharmacyRoot -ne $expectedPharmacyRoot -or $resolvedPatientRoot -ne $expectedPatientRoot) {
    throw 'Unexpected module path; integration stopped.'
}

$envPath = Join-Path $resolvedPharmacyRoot '.env'
$patientKeyPath = 'C:\xampp\medicare-connect-secrets\patient-record-service.key'
$pharmacyKeyPath = 'C:\xampp\medicare-connect-secrets\pharmacy-service.key'
if (-not (Test-Path -LiteralPath $envPath) -or -not (Test-Path -LiteralPath $patientKeyPath)) {
    throw 'A required environment or API-key file is missing.'
}

$lines = [System.Collections.Generic.List[string]](Get-Content -LiteralPath $envPath)
function Set-EnvValue([string]$Name, [string]$Value) {
    for ($index = 0; $index -lt $lines.Count; $index++) {
        if ($lines[$index].StartsWith($Name + '=', [StringComparison]::Ordinal)) {
            $lines[$index] = $Name + '=' + $Value
            return
        }
    }
    $lines.Add($Name + '=' + $Value)
}

$patientKey = (Get-Content -Raw -LiteralPath $patientKeyPath).Trim()
$pharmacyKeyLine = $lines | Where-Object { $_.StartsWith('PHARMACY_API_KEY=', [StringComparison]::Ordinal) } | Select-Object -First 1
if ($patientKey -eq '' -or $null -eq $pharmacyKeyLine) { throw 'An API key is empty or missing.' }
$pharmacyKey = $pharmacyKeyLine.Substring('PHARMACY_API_KEY='.Length).Trim()
if ($pharmacyKey -eq '') { throw 'The Pharmacy API key is empty.' }

Set-EnvValue 'APP_URL' 'http://localhost/MediCareConnect-Pharmacy-Lawliet/MediCareConnect-Pharmacy-Lawliet'
Set-EnvValue 'PATIENT_RECORD_API_ENDPOINT' 'http://localhost/project/api/patient-record-summary.php'
Set-EnvValue 'PATIENT_RECORD_API_KEY' $patientKey
[System.IO.File]::WriteAllLines($envPath, $lines)

$secretDirectory = Split-Path -Parent $pharmacyKeyPath
if (-not (Test-Path -LiteralPath $secretDirectory)) { New-Item -ItemType Directory -Path $secretDirectory | Out-Null }
[System.IO.File]::WriteAllText($pharmacyKeyPath, $pharmacyKey + [Environment]::NewLine)

Copy-Item -LiteralPath (Join-Path $resolvedPatientRoot 'WebService\xsd\patient_record_request.xsd') -Destination (Join-Path $resolvedPharmacyRoot 'docs\xsd\patient_record_request.xsd') -Force
Copy-Item -LiteralPath (Join-Path $resolvedPatientRoot 'WebService\xsd\patient_record_response.xsd') -Destination (Join-Path $resolvedPharmacyRoot 'docs\xsd\patient_record_response.xsd') -Force
Copy-Item -LiteralPath (Join-Path $resolvedPatientRoot 'WebService\examples\patient_record_request.xml') -Destination (Join-Path $resolvedPharmacyRoot 'docs\xml\patient-record-summary-request.xml') -Force
Copy-Item -LiteralPath (Join-Path $resolvedPatientRoot 'WebService\examples\patient_record_response.xml') -Destination (Join-Path $resolvedPharmacyRoot 'docs\xml\patient-record-summary-response.xml') -Force

Write-Output 'Pharmacy endpoint, API keys, and Patient Record XML contract are aligned.'
