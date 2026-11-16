param([string]$Dir, [string]$Out)

# circuit -> file
$circuitFiles = [ordered]@{
    'hmu'       = @('08.hmu.tsp', '08.hmu.HW5103.tsp')
    'ctlv3'     = @('15.ctlv2.tsp')
    'vr_71'     = @('26.vr_71.tsp')
    'vwzio'     = @('76.vwz.tsp')
    'broadcast' = @('broadcast.tsp')
}
$includeMap = @{
    'Hcmode_inc' = 'hcmode_inc.tsp'; 'Errors_inc' = 'errors_inc.tsp'; 'B516_inc' = 'b516_inc.tsp'
    'Hwcmode_inc' = 'hwcmode_inc.tsp'; 'Service_inc' = 'service_inc.tsp'; 'Tempsetpoints_inc' = 'tempsetpoints_inc.tsp'
    'Timer_inc' = 'timer_inc.tsp'; 'Timerhc_inc' = 'timerhc_inc.tsp'; 'Timerhwc_inc' = 'timerhwc_inc.tsp'
    'Timercc_inc' = 'timercc_inc.tsp'; 'Timercool_inc' = 'timercool_inc.tsp'; 'Timertariff_inc' = 'timertariff_inc.tsp'
    'Quick_inc' = 'quick_inc.tsp'; 'Roomtempoffset_inc' = 'roomtempoffset_inc.tsp'; 'Mcmode_inc' = 'mcmode_inc.tsp'
    'Iotesthp_inc' = 'iotesthp_inc.tsp'
    'Yield3d43_inc' = 'yield3d43_inc.tsp'; 'Yield3f40_inc' = 'yield3f40_inc.tsp'; 'Yield4445_inc' = 'yield4445_inc.tsp'
    'Yield8485_inc' = 'yield8485_inc.tsp'; 'Yield8485r_inc' = 'yield8485r_inc.tsp'
}

function Read-Tsp([string]$file) {
    $p = Join-Path $Dir $file
    if (-not (Test-Path $p)) { return $null }
    return [System.IO.File]::ReadAllText($p, [System.Text.Encoding]::UTF8)
}

$scalars = @{}   # name -> @{unit; values; base}
$enums = @{}     # name -> ordered list
$models = @{}    # name -> model (helpers, last wins)

function Parse-File([string]$text) {
    $lines = $text -split "`n"
    $pending = @{ doc = $null; unit = $null; values = $null; divisor = $null; flags = @(); inherit = $null; ext = $false }
    $result = New-Object System.Collections.ArrayList
    $i = 0
    while ($i -lt $lines.Count) {
        $line = $lines[$i].Trim()
        if ($line -match '^/\*\*\s*(.*?)\s*\*/$') { $pending.doc = $Matches[1]; $i++; continue }
        if ($line -match '^@unit\("([^"]*)"\)') { $pending.unit = $Matches[1] }
        if ($line -match '@values\((\w+)\)') { $pending.values = $Matches[1] }
        if ($line -match '@divisor\(([\d.]+)\)') { $pending.divisor = $Matches[1] }
        if ($line -match '^@inherit\(([^)]*)\)') { $pending.inherit = $Matches[1] }
        if ($line -match '^@ext\b') { $pending.ext = $true }
        if ($line -match '^@base\b') { $pending.flags += 'base' }
        if ($line -match '^@write\b') { $pending.flags += 'write' }
        if ($line -match '^@passive\b') { $pending.flags += 'passive' }
        if ($line -match '^@auth\("(\w+)"\)') { $pending.flags += ('auth:' + $Matches[1]) }
        if ($line -match '^scalar\s+(\w+)\s+extends\s+(\w+)') {
            $scalars[$Matches[1]] = @{ unit = $pending.unit; values = $pending.values; base = $Matches[2]; divisor = $pending.divisor; doc = $pending.doc }
            $pending = @{ doc = $null; unit = $null; values = $null; divisor = $null; flags = @(); inherit = $null; ext = $false }
            $i++; continue
        }
        if ($line -match '^enum\s+(\w+)\s*\{') {
            $name = $Matches[1]; $vals = New-Object System.Collections.ArrayList
            $i++
            while ($i -lt $lines.Count -and $lines[$i].Trim() -ne '}') {
                if ($lines[$i].Trim() -match '^(\w+)\s*:\s*(0x[0-9a-fA-F]+|-?\d+)') {
                    $v = $Matches[2]; $n = if ($v.StartsWith('0x')) { [Convert]::ToInt32($v.Substring(2), 16) } else { [int]$v }
                    [void]$vals.Add(@{ name = $Matches[1]; value = $n })
                }
                $i++
            }
            $enums[$name] = $vals
            $pending = @{ doc = $null; unit = $null; values = $null; divisor = $null; flags = @(); inherit = $null; ext = $false }
            $i++; continue
        }
        if ($line -match '^model\s+(\w+)(?:<\w+>)?\s*(?:is\s+(\w+)<(\w+)>)?\s*\{(\s*\})?') {
            $m = @{ name = $Matches[1]; doc = $pending.doc; flags = $pending.flags; inherit = $pending.inherit; ext = $pending.ext; fields = New-Object System.Collections.ArrayList; template = $Matches[2]; templateArg = $Matches[3] }
            $closed = [bool]$Matches[4]
            $pending = @{ doc = $null; unit = $null; values = $null; divisor = $null; flags = @(); inherit = $null; ext = $false }
            $i++
            $fp = @{ doc = $null; unit = $null; values = $null; divisor = $null; out = $false; maxLength = $false; const = $false }
            while (-not $closed -and $i -lt $lines.Count -and $lines[$i].Trim() -ne '}') {
                $l = $lines[$i].Trim()
                if ($l -match '^/\*\*\s*(.*?)\s*\*/$') { $fp.doc = $Matches[1] }
                if ($l -match '@unit\("([^"]*)"\)') { $fp.unit = $Matches[1] }
                if ($l -match '@values\((\w+)\)') { $fp.values = $Matches[1] }
                if ($l -match '@divisor\(([\d.]+)\)') { $fp.divisor = $Matches[1] }
                if ($l -match '@out\b') { $fp.out = $true }
                if ($l -match '@constValue') { $fp.const = $true }
                if ($l -match '(?:^|\s)(\w+)\s*:\s*([\w.]+)\s*;') {
                    [void]$m.fields.Add(@{ name = $Matches[1]; type = $Matches[2]; doc = $fp.doc; unit = $fp.unit; values = $fp.values; divisor = $fp.divisor; out = $fp.out; const = $fp.const })
                    $fp = @{ doc = $null; unit = $null; values = $null; divisor = $null; out = $false; maxLength = $false; const = $false }
                }
                $i++
            }
            [void]$result.Add($m)
            $i++; continue
        }
        if ($line -match '^union\s+_includes') {
            $i++
            while ($i -lt $lines.Count -and $lines[$i].Trim() -ne '}') {
                if ($lines[$i].Trim() -match '^(\w+)') { [void]$result.Add(@{ include = $Matches[1] }) }
                $i++
            }
            $i++; continue
        }
        if ($line -ne '' -and -not $line.StartsWith('@') -and -not $line.StartsWith('//')) {
            $pending = @{ doc = $null; unit = $null; values = $null; divisor = $null; flags = @(); inherit = $null; ext = $false }
        }
        $i++
    }
    return $result
}

# pass 1: templates
foreach ($f in @('root_templates.tsp', '_templates.tsp')) { [void](Parse-File (Read-Tsp $f)) }

# pass 2: all files, collect per file
$allFiles = @{}
Get-ChildItem $Dir -Filter *.tsp | ForEach-Object {
    if ($_.Name -notin @('root_templates.tsp', '_templates.tsp')) {
        $allFiles[$_.Name] = Parse-File (Read-Tsp $_.Name)
    }
}
foreach ($k in $allFiles.Keys) { foreach ($m in $allFiles[$k]) { if ($m.name -and -not $m.inherit -and -not $m.ext) { $models[$m.name] = $m } } }
$tm = Parse-File (Read-Tsp '_templates.tsp'); foreach ($m in $tm) { if ($m.name) { $models[$m.name] = $m } }
$tm = Parse-File (Read-Tsp 'root_templates.tsp'); foreach ($m in $tm) { if ($m.name -and -not $models.ContainsKey($m.name)) { $models[$m.name] = $m } }

function Resolve-Field($f, [string]$prefix, $acc, [int]$depth) {
    if ($f.const -or $f.type -in @('IGN', 'HEX') -or $f.type -eq 'skip') { return }
    $name = if ($prefix) { $f.name } else { $f.name }
    $t = $f.type
    if ($models.ContainsKey($t) -and $depth -lt 4) {
        foreach ($sf in $models[$t].fields) { Resolve-Field $sf $f.name $acc ($depth + 1) }
        return
    }
    $unit = $f.unit; $values = $f.values; $doc = $f.doc; $base = $t
    $guard = 0
    while ($scalars.ContainsKey($t) -and $guard -lt 6) {
        $s = $scalars[$t]
        if (-not $unit -and $s.unit) { $unit = $s.unit }
        if (-not $values -and $s.values) { $values = $s.values }
        $base = $s.base; $t = $s.base; $guard++
    }
    if ($unit) { $unit = $unit.Replace([string][char]0xC2 + [char]0xB0, [string][char]0xB0) }
    [void]$acc.Add([ordered]@{ name = $name; type = $f.type; unit = $unit; enum = $values; doc = $doc; out = $f.out })
}

$messages = [ordered]@{}
function Add-Messages([string]$circuit, $parsed, [string]$source) {
    foreach ($m in $parsed) {
        if ($m.include) {
            $incFile = $includeMap[$m.include]
            if ($incFile -and $allFiles.ContainsKey($incFile)) { Add-Messages $circuit $allFiles[$incFile] $incFile }
            continue
        }
        if (-not $m.name) { continue }
        if (-not ($m.inherit -or $m.ext)) { continue }
        if ($m.name -cmatch '^[a-z]') { continue }
        $fields = New-Object System.Collections.ArrayList
        foreach ($f in $m.fields) { Resolve-Field $f '' $fields 0 }
        $key = $m.name
        if (-not $messages.Contains($key)) {
            $messages[$key] = [ordered]@{ name = $key; doc = $m.doc; circuits = @(); sources = @(); fields = $fields }
        }
        $e = $messages[$key]
        if (-not $e.Contains('byCircuit')) { $e.byCircuit = [ordered]@{} }
        if (-not $e.byCircuit.Contains($circuit)) { $e.byCircuit[$circuit] = $fields }
        if ($circuit -notin $e.circuits) { $e.circuits += $circuit }
        if ($source -notin $e.sources) { $e.sources += $source }
        if (-not $e.doc -and $m.doc) { $e.doc = $m.doc }
    }
}
foreach ($c in $circuitFiles.Keys) { foreach ($f in $circuitFiles[$c]) { Add-Messages $c $allFiles[$f] $f } }

$usedEnums = [ordered]@{}
foreach ($m in $messages.Values) { foreach ($f in $m.fields) { if ($f.enum -and $enums.ContainsKey($f.enum)) { $usedEnums[$f.enum] = $enums[$f.enum] } } }

$outObj = [ordered]@{ messages = $messages; enums = $usedEnums }
[System.IO.File]::WriteAllText($Out, ($outObj | ConvertTo-Json -Depth 8), (New-Object System.Text.UTF8Encoding($false)))
"messages: $($messages.Count)  enums: $($usedEnums.Count)"
$messages.Values | Group-Object { $_.circuits -join ',' } | ForEach-Object { "{0}: {1}" -f $_.Name, $_.Count }
