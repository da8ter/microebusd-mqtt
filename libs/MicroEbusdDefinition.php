<?php

declare(strict_types=1);

/**
 * Felddefinitionen aus der HTTP-Schnittstelle des Adapters (token.ebusd.eu/api).
 *
 * GET /api/v1/ebus/<circuit>/<name>/get?from=0 liefert je Feld Einheit, Grenzen und Schrittweite,
 * ohne den Bus zu belasten (from=0 = nur aus dem Zwischenspeicher des Adapters). Das Modul holt die
 * Definition einmal je Nachricht, wenn eine Adresse eingetragen ist. Ohne Adresse oder wenn der
 * Adapter nicht antwortet, gilt wie bisher allein der mitgelieferte Katalog.
 */
trait MicroEbusdDefinition
{
    /** Der Abruf läuft im Empfang einer MQTT-Nachricht und darf ihn nicht lange aufhalten. */
    private static int $httpTimeoutMs = 2000;
    /** Nach einem Fehlschlag so lange keinen neuen Versuch: sonst hinge jeder Empfang am Timeout. */
    private static int $httpPauseSeconds = 300;

    /**
     * Prüft die eingetragene Adresse und meldet, ob der Adapter antwortet. Gelesen wird die Definition
     * einer Nachricht dieser Instanz aus dem Zwischenspeicher (from=0, kein Busverkehr), nicht die
     * Nachrichtenliste: deren Abruf ohne Seitenangabe hat laut einem Nutzerbericht eine Firmware vom
     * 01.11.2026 neu starten lassen (john30/ebusd-esp32 #198).
     */
    public function CheckAdapter(): void
    {
        if ($this->adapterHost() === '') {
            echo $this->Translate('No valid adapter address entered (host name or IP address, optionally with port).');
            return;
        }
        $this->SetBuffer('HttpPause', '');
        $circuit = $this->circuit();
        $names = array_merge($this->pollNames(), array_map('strval', array_keys($this->readJsonAttribute('Messages'))));
        $path = ($circuit !== '' && $names !== [])
            ? '/api/v1/ebus/' . rawurlencode($circuit) . '/' . rawurlencode($names[0]) . '/get?from=0'
            : '/api/v1/ebus/datetime/get?from=0'; // Beispiel aus der Dokumentation des Adapters
        $status = 0;
        $data = $this->httpGetJson($path, $status);
        if ($data !== null) {
            echo $this->Translate('The adapter answers. Units and limits are now read from it when a variable is created or set up again.');
            return;
        }
        // Der Fehlschlag einer Prüfung von Hand soll den Abruf im Betrieb nicht fünf Minuten sperren.
        $this->SetBuffer('HttpPause', '');
        echo ($status > 0)
            ? sprintf($this->Translate('A device answers on this address (HTTP status %d), but not with the data of micro-ebusd.'), $status)
            : $this->Translate('The adapter does not answer on this address.');
    }

    /**
     * Felder einer Nachricht laut Adapter: Feldname (klein) => ['u' => Einheit, 'min', 'max', 'step'].
     * Null, wenn keine Adresse eingetragen ist oder der Adapter nicht geantwortet hat.
     */
    private function adapterDefinition(string $name, bool $refresh = false): ?array
    {
        $circuit = $this->circuit();
        if ($circuit === '' || $name === '' || $this->adapterHost() === '') {
            return null;
        }
        $cache = $this->readJsonAttribute('Definitions');
        if (!$refresh && is_array($cache[$name] ?? null)) {
            return $cache[$name];
        }
        if ((int) $this->GetBuffer('HttpPause') > time()) {
            return is_array($cache[$name] ?? null) ? $cache[$name] : null;
        }
        $data = $this->httpGetJson('/api/v1/ebus/' . rawurlencode($circuit) . '/' . rawurlencode($name) . '/get?from=0');
        if ($data === null) {
            return is_array($cache[$name] ?? null) ? $cache[$name] : null;
        }

        $fields = [];
        foreach (is_array($data['fields'] ?? null) ? $data['fields'] : [] as $field) {
            if (!is_array($field) || !isset($field['name']) || !is_scalar($field['name'])) {
                continue;
            }
            $row = [];
            if (is_string($field['unit'] ?? null) && $field['unit'] !== '') {
                $row['u'] = mb_substr($field['unit'], 0, 12);
            }
            foreach (['min', 'max', 'step'] as $key) {
                if (is_int($field[$key] ?? null) || is_float($field[$key] ?? null)) {
                    $row[$key] = $field[$key];
                }
            }
            $fields[strtolower((string) $field['name'])] = $row;
        }
        // Auch eine Antwort ohne Felder merken: der Adapter kennt die Nachricht dann nicht anders.
        $cache[$name] = $fields;
        $this->WriteAttributeString('Definitions', (string) json_encode($cache));
        return $fields;
    }

    /** Liest eine JSON-Antwort des Adapters; null bei jedem Fehlschlag (dann Pause, siehe oben). */
    private function httpGetJson(string $path, int &$status = 0): ?array
    {
        $url = 'http://' . $this->adapterHost() . $path;
        $handle = curl_init($url);
        curl_setopt_array($handle, [
            CURLOPT_RETURNTRANSFER    => true,
            CURLOPT_CONNECTTIMEOUT_MS => self::$httpTimeoutMs,
            CURLOPT_TIMEOUT_MS        => self::$httpTimeoutMs,
            CURLOPT_FOLLOWLOCATION    => false,
            CURLOPT_PROTOCOLS         => CURLPROTO_HTTP,
            CURLOPT_HTTPHEADER        => ['Accept: application/json']
        ]);
        $body = curl_exec($handle);
        $status = (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE); // 0 = keine Verbindung
        $error = curl_error($handle);
        curl_close($handle);

        $data = is_string($body) ? json_decode($body, true) : null;
        if ($status !== 200 || !is_array($data)) {
            $this->SendDebug('HTTP', $url . ' failed: ' . ($error !== '' ? $error : 'status ' . $status), 0);
            $this->SetBuffer('HttpPause', (string) (time() + self::$httpPauseSeconds));
            return null;
        }
        $this->SendDebug('HTTP', $url . ' = ' . mb_substr((string) $body, 0, 1000), 0);
        return $data;
    }

    /** Host oder IP-Adresse des Adapters, wahlweise mit Port; alles andere gilt als nicht eingetragen. */
    private function adapterHost(): string
    {
        $host = trim($this->ReadPropertyString('AdapterHost'));
        $host = (string) preg_replace('#^http://|/+$#i', '', $host);
        if (preg_match('/^[A-Za-z0-9]([A-Za-z0-9.\-]{0,251}[A-Za-z0-9])?(:\d{1,5})?$/', $host) !== 1) {
            return '';
        }
        return $host;
    }

    /** Nachkommastellen, die zur Schrittweite passen (höchstens 2). */
    private function digitsForStep(int|float $step): int
    {
        if ($step <= 0) {
            return 2;
        }
        $digits = 0;
        while ($digits < 2 && abs($step * (10 ** $digits) - round($step * (10 ** $digits))) > 1e-6) {
            $digits++;
        }
        return $digits;
    }
}
