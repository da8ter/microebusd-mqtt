param([string]$Catalog, [string]$Source, [string]$Out)

$utf8 = New-Object System.Text.UTF8Encoding($false)
$cat = [System.IO.File]::ReadAllText($Catalog, [System.Text.Encoding]::UTF8) | ConvertFrom-Json
$src = [System.IO.File]::ReadAllText($Source, [System.Text.Encoding]::UTF8) | ConvertFrom-Json

function Prop($obj, [string]$name) {
    if ($null -eq $obj) { return $null }
    $p = $obj.PSObject.Properties[$name]
    if ($p) { return $p.Value }
    return $null
}

$enumKeyMap = @{
    'Values_Domestic_hot_water_mod__eco_normal_balance_' = 'HwcModeEco'
    'Values_status' = 'LiveStatus'
    'Values_function' = 'LiveFunction'
    'Values_state' = 'HmuState'
    'Values_TestHighPressureSwitch' = 'HpSwitch'
    'Values_TestTempSwitchCompressorOutlet' = 'HotGasSwitch'
    'Values_TestLockoutContactS20' = 'LockoutS20'
    'Values_TestLockoutContactS21' = 'LockoutS21'
    'Values_TestImmersionHeaterSafetyCutOut' = 'HeaterStb'
    'Values_TestThreeWayValve' = 'ThreeWayValve'
}
function EnumKey([string]$name) {
    if ($enumKeyMap.ContainsKey($name)) { return $enumKeyMap[$name] }
    return $name -replace '^Values_', ''
}

function StripTest([string]$s) { return ($s -replace '\s*\((Test[^)]*|Testmenü)\)$', '') }

function Caption([string]$name) {
    $explicit = Prop $src.messages $name
    if ($explicit) { return $explicit }
    if ($name -cmatch '^Hc(\d)(\w+)$') { $t = Prop $src.hc $Matches[2]; if ($t) { return "Heizkreis $($Matches[1]) $t" } }
    if ($name -cmatch '^Z(\d)(\w+)$') {
        $n = $Matches[1]; $rest = $Matches[2]
        $t = Prop $src.zone $rest; if ($t) { return "Zone $n $t" }
    }
    if ($name -cmatch '^VR92Addr(\d)Room(Humidity|Temp)$') {
        $what = if ($Matches[2] -eq 'Temp') { 'Raumtemperatur' } else { 'Raumluftfeuchte' }
        return "Fernbedienung VR 92 Adresse $($Matches[1]) $what"
    }
    if ($name -cmatch '^YieldThisYear(\d+)$') { return "Umweltertrag dieses Jahr, Monat $($Matches[1])" }
    if ($name -cmatch '^ConsumptionThisYear(\d+)$') { return "Stromverbrauch dieses Jahr, Monat $($Matches[1])" }
    if ($name -cmatch '^Mc(\d)Operation$') { return "Mischerkreis $($Matches[1]) Betrieb" }
    if ($name -cmatch '^(HwcTimer|CcTimer|SilentTimer|Z(\d)Timer|Z(\d)CoolingTimer)_(\w+)$') {
        $prefix = $Matches[1]; $part = $Matches[4]; $zn = if ($Matches[2]) { $Matches[2] } else { $Matches[3] }
        $key = $prefix -replace '^Z\d', 'Z{n}'
        $p = (Prop $src.timerPrefix $key)
        if ($p) {
            $p = $p.Replace('{n}', [string]$zn)
            if ($part -eq 'Timeslots') { return "$p Anzahl Zeitfenster" }
            if ($part -cmatch '^(\w+?)(0?)$') {
                $day = Prop $src.days $Matches[1]
                if ($day) { if ($Matches[2] -eq '0') { return "$p $day (1. Zeitfenster)" } else { return "$p $day" } }
            }
        }
    }
    if ($name -cmatch '^EnableTest(\w+)$') {
        $t = Caption ('Test' + $Matches[1])
        if ($t -and $t -ne ('Test' + $Matches[1])) { return 'Testmenü freischalten: ' + (StripTest $t) }
    }
    return $null
}

function FieldCaption([string]$msg, [string]$field) {
    $mf = Prop $src.messageFields $msg
    $t = Prop $mf $field
    if ($null -ne $t) { return $t }
    if ($msg -match '_Timeslots$') { $t = Prop (Prop $src.messageFields 'HwcTimer_Timeslots') $field; if ($null -ne $t) { return $t } }
    if ($msg -match '^Mc\dOperation$') { $t = Prop (Prop $src.messageFields 'Mc1Operation') $field; if ($null -ne $t) { return $t } }
    if ($field -cmatch '^s(\d+)$') { return "S$($Matches[1])" }
    $t = Prop $src.fields $field
    if ($null -ne $t) { return $t }
    return $null
}

$outMsgs = [ordered]@{}
$missing = New-Object System.Collections.ArrayList
$missingFields = @{}
foreach ($p in $cat.messages.PSObject.Properties) {
    $m = $p.Value
    $de = Caption $m.name
    if (-not $de) { [void]$missing.Add($m.name) }
    $byC = [ordered]@{}
    foreach ($cp in $m.byCircuit.PSObject.Properties) {
        $list = @()
        foreach ($f in @($cp.Value)) {
            if ($null -eq $f -or $f.out) { continue }
            $e = [ordered]@{ n = $f.name }
            if ($f.unit) { $e.u = $f.unit }
            if ($f.enum) { $e.e = (EnumKey $f.enum) }
            $fc = FieldCaption $m.name $f.name
            if ($null -ne $fc) { $e.de = $fc } else { $missingFields[$f.name] = $true }
            $list += $e
        }
        $byC[$cp.Name] = $list
    }
    $outMsgs[$m.name.ToLowerInvariant()] = [ordered]@{ n = $m.name; de = $de; f = $byC }
}

$outEnums = [ordered]@{}
foreach ($p in $cat.enums.PSObject.Properties) {
    $tr = Prop $src.enums $p.Name
    $list = @()
    foreach ($v in @($p.Value)) {
        $de = Prop $tr $v.name
        if (-not $de) { $de = $v.name; [void]$missing.Add("enum $($p.Name).$($v.name)") }
        $list += [ordered]@{ v = [int]$v.value; n = [string]$v.name; de = [string]$de }
    }
    $outEnums[(EnumKey $p.Name)] = $list
}

$result = [ordered]@{ source = 'john30/ebusd-configuration (src/vaillant), übersetzt'; messages = $outMsgs; enums = $outEnums }
[System.IO.File]::WriteAllText($Out, ($result | ConvertTo-Json -Depth 10 -Compress), $utf8)
"messages: $($outMsgs.Count), enums: $($outEnums.Count)"
"missing captions: $($missing.Count)"
$missing | Select-Object -First 80
"missing field captions: " + (($missingFields.Keys | Sort-Object) -join ' ')
