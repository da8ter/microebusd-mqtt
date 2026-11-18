<?php

declare(strict_types=1);

/**
 * Prüflauf, Abfrageintervalle und Aktualität.
 *
 * micro-ebusd meldet auf eine Abfrage nur bei Erfolg etwas zurück (token.ebusd.eu/mqtt: „sent
 * asynchronously on success“). Fragt man eine Nachricht ab, die das Gerät nicht kennt, passiert
 * nichts, und die Variable bleibt still stehen. Der Prüflauf fragt deshalb jede lesbare Nachricht
 * einzeln ab und hält fest, was zurückkam: ein Wert, nur leere Felder (null) oder nichts.
 */
trait MicroEbusdProbe
{
    /** Abstand zwischen zwei Abfragen im Prüflauf; der Adapter arbeitet sie nacheinander ab. */
    private static int $probeSpacingMs = 2000;
    /** So lange wartet der Prüflauf nach einer Abfrage auf die Antwort. */
    private static int $probeAnswerWait = 30;
    /** Als veraltet gilt ein abgefragter Wert nach so vielen Abfrageintervallen ohne Antwort. */
    private static int $staleFactor = 3;

    /** Startet den Prüflauf über alle aktiv lesbaren Nachrichten aus der Liste des Adapters. */
    public function StartProbe(): void
    {
        if ($this->circuit() === '') {
            echo $this->Translate('The adapter status instance has no message list.');
            return;
        }
        $names = [];
        foreach ($this->readJsonAttribute('Messages') as $name => $info) {
            if (empty($info['passive'])) {
                $names[] = (string) $name;
            }
        }
        if ($names === []) {
            echo $this->Translate('No readable messages known yet. Press "Request message list from adapter" first and wait until the list is complete.');
            return;
        }
        sort($names, SORT_NATURAL | SORT_FLAG_CASE);
        $this->SetBuffer('Probe', (string) json_encode(['queue' => $names, 'next' => 0, 'req' => [], 'started' => time()]));
        $this->SetTimerInterval('Probe', self::$probeSpacingMs);
        $minutes = (int) ceil((count($names) * self::$probeSpacingMs / 1000 + self::$probeAnswerWait) / 60);
        $this->updateProbeLabel();
        echo sprintf($this->Translate('Check started for %d messages. It takes about %d minutes; the table fills in when it is done.'), count($names), $minutes);
    }

    /** Bricht einen laufenden Prüflauf ab; bisherige Ergebnisse werden verworfen. */
    public function StopProbe(): void
    {
        $this->SetTimerInterval('Probe', 0);
        $this->SetBuffer('Probe', '');
        $this->updateProbeLabel();
    }

    /** Setzt die Abfrageliste im offenen Formular auf die Nachrichten, die im Prüflauf einen Wert lieferten. */
    public function UseProbeResult(): void
    {
        $result = $this->readJsonAttribute('ProbeResult');
        $intervals = $this->pollIntervals();
        $rows = [];
        foreach ($result as $name => $r) {
            if (($r['r'] ?? '') === 'value') {
                $rows[] = ['Name' => (string) $name, 'Interval' => $intervals[(string) $name] ?? 0];
            }
        }
        if ($rows === []) {
            echo $this->Translate('No check result with values yet. Run the check first.');
            return;
        }
        $this->UpdateFormField('PollMessages', 'values', (string) json_encode($rows));
        echo sprintf($this->Translate('%d answering messages entered in the query list. Set the intervals and save with "Apply changes".'), count($rows));
    }

    /**
     * Löscht die Variablen der Nachrichten, die im Prüflauf nicht oder nur leer geantwortet haben
     * und seitdem auch nicht von selbst mit einem Wert kamen.
     */
    public function DeleteSilentVariables(): void
    {
        $result = $this->readJsonAttribute('ProbeResult');
        if ($result === []) {
            echo $this->Translate('No check result yet. Run the check first.');
            return;
        }
        $seen = $this->seenBuffer();
        $map = $this->readJsonAttribute('IdentMap');
        $removed = 0;
        foreach ($map as $ident => $entry) {
            $name = (string) ($entry['name'] ?? '');
            $r = $result[$name] ?? null;
            if (!is_array($r) || !in_array($r['r'] ?? '', ['none', 'null'], true)) {
                continue;
            }
            if ((int) ($seen[$name]['v'] ?? 0) > (int) ($r['t'] ?? 0)) {
                continue; // inzwischen doch mit Wert gekommen (z. B. passiv)
            }
            $this->UnregisterVariable((string) $ident);
            unset($map[$ident]);
            $removed++;
        }
        $this->WriteAttributeString('IdentMap', (string) json_encode($map));
        echo sprintf($this->Translate('%d variables of messages without an answer removed.'), $removed);
    }

    /** Ein Schritt des Prüflaufs: nächste Abfrage senden bzw. nach der Wartezeit auswerten. */
    private function probeTick(): void
    {
        $state = json_decode($this->GetBuffer('Probe'), true);
        if (!is_array($state)) {
            $this->SetTimerInterval('Probe', 0);
            return;
        }
        $queue = (array) $state['queue'];
        $next = (int) $state['next'];
        if ($next < count($queue)) {
            $name = (string) $queue[$next];
            $state['req'][$name] = time();
            $state['next'] = $next + 1;
            $this->SetBuffer('Probe', (string) json_encode($state));
            $this->Get($name);
            if ($state['next'] % 10 === 0) {
                $this->updateProbeLabel();
            }
            return;
        }
        $last = $state['req'] === [] ? 0 : max($state['req']);
        if (time() - $last < self::$probeAnswerWait) {
            return;
        }
        $this->finishProbe($state);
    }

    private function finishProbe(array $state): void
    {
        $this->SetTimerInterval('Probe', 0);
        $this->SetBuffer('Probe', '');
        $seen = $this->seenBuffer();
        $result = $this->readJsonAttribute('ProbeResult');
        $count = ['value' => 0, 'null' => 0, 'none' => 0];
        foreach ($state['req'] as $name => $asked) {
            $s = $seen[$name] ?? [];
            if ((int) ($s['v'] ?? 0) >= $asked) {
                $r = 'value';
            } elseif ((int) ($s['n'] ?? 0) >= $asked) {
                $r = 'null';
            } else {
                $r = 'none';
            }
            $result[$name] = ['r' => $r, 't' => (int) $asked];
            $count[$r]++;
        }
        $this->WriteAttributeString('ProbeResult', (string) json_encode($result));
        $this->SendDebug('Probe', json_encode($count), 0);
        $this->LogMessage(sprintf('Check finished: %d with value, %d empty, %d without answer', $count['value'], $count['null'], $count['none']), KL_MESSAGE);
        $this->updateProbeLabel();
        $this->refreshKnownMessages();
    }

    /**
     * Merkt sich, wann eine Nachricht zuletzt kam: v = mit mindestens einem Wert, n = nur leer.
     * Nur im Puffer: ein Attribut würde bei jeder Nachricht die Einstellungen schreiben.
     */
    private function noteSeen(string $name, bool $hasValue): void
    {
        $seen = $this->seenBuffer();
        $seen[$name][$hasValue ? 'v' : 'n'] = time();
        $this->SetBuffer('Seen', (string) json_encode($seen));
    }

    private function seenBuffer(): array
    {
        $data = json_decode($this->GetBuffer('Seen'), true);
        return is_array($data) ? $data : [];
    }

    /** Zuletzt mit Wert gekommen: Puffer, nach einem Neustart ersatzweise die Variablen der Nachricht. */
    private function lastValueTime(string $name, array $seen, array $byName): int
    {
        $t = (int) ($seen[$name]['v'] ?? 0);
        foreach ($byName[$name] ?? [] as $vid) {
            $t = max($t, (int) IPS_GetVariable($vid)['VariableUpdated']);
        }
        return $t;
    }

    /** Abfrage-Timer: nur die Nachrichten, deren eigenes Intervall abgelaufen ist. */
    private function pollTick(): void
    {
        $default = max(1, $this->ReadPropertyInteger('PollInterval'));
        $last = json_decode($this->GetBuffer('LastPoll'), true);
        $last = is_array($last) ? $last : [];
        $now = time();
        foreach ($this->pollIntervals() as $name => $interval) {
            $every = $interval > 0 ? $interval : $default;
            // eine Sekunde Spiel, damit der Timer-Versatz keine Runde überspringt
            if ($now - (int) ($last[$name] ?? 0) >= $every - 1) {
                $this->Get($name);
                $last[$name] = $now;
            }
        }
        $this->SetBuffer('LastPoll', (string) json_encode($last));
    }

    /** Name => eigenes Intervall in Sekunden (0 = Intervall der Instanz). */
    private function pollIntervals(): array
    {
        $rows = json_decode($this->ReadPropertyString('PollMessages'), true);
        $out = [];
        foreach (is_array($rows) ? $rows : [] as $row) {
            $name = trim((string) ($row['Name'] ?? ''));
            if ($name !== '' && !isset($out[$name])) {
                $out[$name] = max(0, (int) ($row['Interval'] ?? 0));
            }
        }
        return $out;
    }

    /** Ergebnis- und Aktualitätsspalten der Nachrichtentabelle. */
    private function freshnessColumns(string $name, array $result, array $seen, array $byName, array $intervals, int $default): array
    {
        $r = (string) ($result[$name]['r'] ?? '');
        $check = match ($r) {
            'value' => $this->Translate('value'),
            'null'  => $this->Translate('empty (no sensor)'),
            'none'  => $this->Translate('no answer'),
            default => ''
        };
        $t = $this->lastValueTime($name, $seen, $byName);
        $age = $t > 0 ? time() - $t : -1;
        $state = '';
        if (isset($intervals[$name])) {
            $every = $intervals[$name] > 0 ? $intervals[$name] : $default;
            $state = ($age < 0 || $age > self::$staleFactor * $every) ? $this->Translate('outdated') : $this->Translate('current');
        }
        return [
            'Check'    => $check,
            'LastSeen' => $age < 0 ? '–' : $this->formatAge($age),
            'State'    => $state,
            'rowColor' => $state === $this->Translate('outdated') ? '#FFC0C0' : ''
        ];
    }

    private function formatAge(int $seconds): string
    {
        if ($seconds < 120) {
            return sprintf($this->Translate('%d s ago'), $seconds);
        }
        if ($seconds < 7200) {
            return sprintf($this->Translate('%d min ago'), intdiv($seconds, 60));
        }
        if ($seconds < 172800) {
            return sprintf($this->Translate('%d h ago'), intdiv($seconds, 3600));
        }
        return sprintf($this->Translate('%d days ago'), intdiv($seconds, 86400));
    }

    /** Variablen-IDs je Nachrichtenname. */
    private function variablesByName(): array
    {
        $out = [];
        foreach ($this->readJsonAttribute('IdentMap') as $ident => $entry) {
            $vid = @IPS_GetObjectIDByIdent((string) $ident, $this->InstanceID);
            if (is_int($vid) && $vid > 0 && isset($entry['name'])) {
                $out[(string) $entry['name']][] = $vid;
            }
        }
        return $out;
    }

    private function probeLabel(): string
    {
        $state = json_decode($this->GetBuffer('Probe'), true);
        if (is_array($state)) {
            return sprintf($this->Translate('Check running: %d of %d messages queried.'), (int) $state['next'], count((array) $state['queue']));
        }
        $result = $this->readJsonAttribute('ProbeResult');
        if ($result === []) {
            return $this->Translate('No check yet. The check queries every readable message once and records whether a value, an empty answer or nothing comes back.');
        }
        $count = ['value' => 0, 'null' => 0, 'none' => 0];
        $t = 0;
        foreach ($result as $r) {
            $count[$r['r'] ?? 'none'] = ($count[$r['r'] ?? 'none'] ?? 0) + 1;
            $t = max($t, (int) ($r['t'] ?? 0));
        }
        return sprintf($this->Translate('Last check %s: %d with value, %d empty, %d without answer.'),
            date('d.m.Y H:i', $t), $count['value'], $count['null'], $count['none']);
    }

    private function updateProbeLabel(): void
    {
        $this->UpdateFormField('ProbeStatus', 'caption', $this->probeLabel());
    }
}
