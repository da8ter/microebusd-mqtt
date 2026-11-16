param([string]$Json, [string]$Out)
$j = [System.IO.File]::ReadAllText($Json, [System.Text.Encoding]::UTF8) | ConvertFrom-Json
$titles = [ordered]@{
    'hmu' = 'hmu: Wärmepumpe (aroTHERM, Adresse 08)'
    'ctlv3' = 'ctlv3: Regler sensoCOMFORT VRC 720 (Adresse 15)'
    'vr_71' = 'vr_71: Erweiterungsmodul VR 71 (Adresse 26)'
    'vwzio' = 'vwzio: Inneneinheit / Hydraulikstation (Adresse 76)'
    'broadcast' = 'broadcast: Rundsendungen'
}
$sb = New-Object System.Text.StringBuilder
[void]$sb.AppendLine('# Vaillant eBUS-Nachrichten auf Deutsch')
[void]$sb.AppendLine('')
[void]$sb.AppendLine('Erzeugt aus den Gerätedefinitionen von [john30/ebusd-configuration](https://github.com/john30/ebusd-configuration/tree/master/src/vaillant) (src/vaillant). Die deutschen Bezeichnungen sind eine eigene Übersetzung; wo die Vorlage keine Beschreibung hat, ist die Bedeutung aus dem Namen abgeleitet. Ob eine Nachricht an der eigenen Anlage tatsächlich Werte liefert, zeigt erst die Nachrichtenliste des Adapters.')
[void]$sb.AppendLine('')
[void]$sb.AppendLine('Felder: Bei Nachrichten mit mehreren Werten steht jedes Feld mit Einheit bzw. Werteliste in eckigen Klammern.')
foreach ($c in $titles.Keys) {
    [void]$sb.AppendLine('')
    [void]$sb.AppendLine('## ' + $titles[$c])
    [void]$sb.AppendLine('')
    [void]$sb.AppendLine('| Name | Bedeutung | Einheit / Werte |')
    [void]$sb.AppendLine('|---|---|---|')
    foreach ($p in $j.messages.PSObject.Properties) {
        $m = $p.Value
        $fp = $m.f.PSObject.Properties[$c]
        if (-not $fp) { continue }
        $fields = @($fp.Value)
        $desc = @()
        foreach ($f in $fields) {
            $t = ''
            if ($f.u) { $t = $f.u }
            if ($f.e) {
                $vals = ($j.enums.($f.e) | ForEach-Object { "$($_.n)=$($_.de)" }) -join ', '
                if ($vals.Length -gt 160) { $vals = $vals.Substring(0, 157) + '…' }
                $t = $vals
            }
            if ($fields.Count -gt 1) {
                $label = if ($f.de) { $f.de } else { $f.n }
                $desc += ("{0} [{1}]" -f $label, $(if ($t) { $t } else { '–' }))
            } elseif ($t) { $desc += $t }
        }
        $line = '| `{0}` | {1} | {2} |' -f $m.n, $m.de, (($desc -join '; ') -replace '\|', '/')
        [void]$sb.AppendLine($line)
    }
}
[System.IO.File]::WriteAllText($Out, $sb.ToString(), (New-Object System.Text.UTF8Encoding($false)))
