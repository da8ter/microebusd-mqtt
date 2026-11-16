<?php

declare(strict_types=1);

/**
 * micro-ebusd MQTT
 *
 * Bindet den eBUS-Adapter (v5/C6) mit micro-ebusd über den Symcon MQTT Server an.
 * micro-ebusd veröffentlicht jede Nachricht als <Basis>/<circuit>/<name> mit JSON-Payload,
 * z. B. ebusd/hmu/RunDataFlowTemp = {"value":35.56}.
 * Anfragen: <Basis>/<circuit>/<name>/get, .../set (JSON), <Basis>/<circuit>/any/list -> .../found
 */
class MicroEbusdMQTT extends IPSModule
{
    private const MQTT_SERVER_MODULE = '{C6D2AEB3-6E1F-4B2E-8E69-3A1A00246850}';
    private const DATA_ID_TX = '{043EA491-0325-4ADD-8FC2-A30C8EEB4D3F}';

    private const STATUS_ACTIVE = 102;
    private const STATUS_NO_TOPIC = 201;

    public function Create()
    {
        parent::Create();

        $this->ConnectParent(self::MQTT_SERVER_MODULE);

        $this->RegisterPropertyString('BaseTopic', 'ebusd');
        $this->RegisterPropertyString('Circuit', 'hmu');
        $this->RegisterPropertyBoolean('AutoCreate', true);
        $this->RegisterPropertyString('PollMessages', '[]');
        $this->RegisterPropertyInteger('PollInterval', 60);
        $this->RegisterPropertyBoolean('WriteEnabled', false);
        $this->RegisterPropertyInteger('PayloadMode', 0);

        // Name => ['write' => bool, 'passive' => bool] aus den "found"-Antworten
        $this->RegisterAttributeString('Messages', '{}');
        // Ident => ['name' => Nachricht, 'field' => Feld, 'fields' => alle Felder der Nachricht]
        $this->RegisterAttributeString('IdentMap', '{}');
        // -1 = unbekannt, 0 = Text, 1 = HEX (wie der MQTT Server die Payload liefert)
        $this->RegisterAttributeInteger('DetectedHex', -1);

        $this->RegisterTimer('Poll', 0, 'MEBUS_PollNow($_IPS[\'TARGET\']);');
    }

    public function ApplyChanges()
    {
        parent::ApplyChanges();

        $base = $this->baseTopic();
        if ($base === '') {
            $this->SetReceiveDataFilter('(?!)');
            $this->SetTimerInterval('Poll', 0);
            $this->SetStatus(self::STATUS_NO_TOPIC);
            return;
        }

        $circuit = $this->circuit();
        $prefix = '.*"Topic":"' . $this->quoteTopic($base . '/');
        if ($circuit === '') {
            // Adapter-Status: genau eine Ebene unter dem Basis-Topic
            $filter = $prefix . '[^/\\\\"]+".*';
        } else {
            $filter = $prefix . $this->quoteTopic($circuit . '/') . '.*';
        }
        $this->SetReceiveDataFilter($filter);
        $this->SendDebug('Filter', $filter, 0);

        $interval = $this->ReadPropertyInteger('PollInterval');
        $active = ($circuit !== '' && $interval > 0 && count($this->pollNames()) > 0);
        $this->SetTimerInterval('Poll', $active ? $interval * 1000 : 0);

        $this->refreshActions();
        $this->SetStatus(self::STATUS_ACTIVE);
    }

    public function GetConfigurationForm()
    {
        $form = json_decode((string) file_get_contents(__DIR__ . '/form.json'), true);
        $messages = $this->readJsonAttribute('Messages');
        ksort($messages, SORT_NATURAL | SORT_FLAG_CASE);

        $values = [];
        foreach ($messages as $name => $info) {
            $values[] = [
                'Name'    => (string) $name,
                'Write'   => !empty($info['write']) ? 'ja' : '',
                'Passive' => !empty($info['passive']) ? 'ja' : ''
            ];
        }

        $form['actions'][] = [
            'type'     => 'List',
            'name'     => 'KnownMessages',
            'caption'  => 'Bekannte Nachrichten (' . count($values) . ') – per "Nachrichtenliste anfordern" füllen',
            'rowCount' => 12,
            'add'      => false,
            'delete'   => false,
            'columns'  => [
                ['caption' => 'Name', 'name' => 'Name', 'width' => 'auto'],
                ['caption' => 'schreibbar', 'name' => 'Write', 'width' => '110px'],
                ['caption' => 'passiv', 'name' => 'Passive', 'width' => '90px']
            ],
            'values'   => $values
        ];

        return json_encode($form);
    }

    public function ReceiveData($JSONString)
    {
        $data = json_decode($JSONString, true);
        if (!is_array($data) || !isset($data['Topic'])) {
            return '';
        }

        $topic = (string) $data['Topic'];
        $payload = $this->decodePayload((string) ($data['Payload'] ?? ''));
        $this->SendDebug('Empfangen', $topic . ' = ' . $payload, 0);

        $base = $this->baseTopic() . '/';
        if ($base === '/' || strpos($topic, $base) !== 0) {
            return '';
        }

        $parts = explode('/', substr($topic, strlen($base)));
        $circuit = $this->circuit();

        if ($circuit === '') {
            if (count($parts) === 1 && $parts[0] !== '') {
                $this->handleStatus($parts[0], $payload);
            }
            return '';
        }

        if (strcasecmp($parts[0], $circuit) !== 0) {
            return '';
        }

        if (count($parts) === 2 && $parts[1] !== '') {
            $this->handleMessage($parts[1], $payload);
        } elseif (count($parts) === 3 && $parts[2] === 'found') {
            $this->handleFound($payload);
        }
        // .../get, .../set, .../list sind Anfragen (auch unsere eigenen) und werden ignoriert

        return '';
    }

    public function RequestAction($Ident, $Value)
    {
        if (!$this->ReadPropertyBoolean('WriteEnabled')) {
            throw new Exception('Schreiben ist in dieser Instanz nicht freigegeben.');
        }

        $map = $this->readJsonAttribute('IdentMap');
        if (!isset($map[$Ident])) {
            throw new Exception('Unbekannte Variable: ' . $Ident);
        }

        $entry = $map[$Ident];
        $fields = $entry['fields'] ?? [$entry['field']];
        $single = count($fields) === 1;

        // micro-ebusd erwartet beim Schreiben alle Felder der Nachricht als JSON-Objekt
        $payload = [];
        foreach ($fields as $field) {
            $ident = $this->makeIdent($entry['name'], $single ? '' : (string) $field);
            if ($ident === $Ident) {
                $payload[$field] = $Value;
            } else {
                $vid = @IPS_GetObjectIDByIdent($ident, $this->InstanceID);
                $payload[$field] = ($vid !== false) ? GetValue($vid) : null;
            }
        }

        $this->Set($entry['name'], json_encode($payload));
    }

    /** Fordert die Nachrichtenliste des Geräts an (Antwort kommt auf .../any/found). */
    public function RequestList()
    {
        $circuit = $this->circuit();
        if ($circuit === '') {
            echo 'Für die Adapter-Statusinstanz gibt es keine Nachrichtenliste.';
            return;
        }
        $this->publish($this->baseTopic() . '/' . $circuit . '/any/list', '');
        echo 'Anfrage gesendet. Die Liste erscheint nach dem erneuten Öffnen der Instanz.';
    }

    /** Fragt alle Nachrichten aus der Abfrageliste aktiv ab. */
    public function PollNow()
    {
        foreach ($this->pollNames() as $name) {
            $this->Get($name);
        }
    }

    /** Fordert eine einzelne Nachricht an, z. B. MEBUS_Get(12345, 'RunDataFlowTemp'). */
    public function Get(string $Name)
    {
        $circuit = $this->circuit();
        if ($circuit === '' || $Name === '') {
            return;
        }
        $this->publish($this->baseTopic() . '/' . $circuit . '/' . $Name . '/get', '');
    }

    /** Schreibt eine Nachricht, z. B. MEBUS_Set(12345, 'HwcTempDesired', '{"value":50}'). */
    public function Set(string $Name, string $JsonPayload)
    {
        if (!$this->ReadPropertyBoolean('WriteEnabled')) {
            throw new Exception('Schreiben ist in dieser Instanz nicht freigegeben.');
        }
        $decoded = json_decode($JsonPayload, true);
        if (!is_array($decoded)) {
            throw new Exception('Die Payload muss ein JSON-Objekt sein, z. B. {"value":50}.');
        }
        $circuit = $this->circuit();
        $this->publish($this->baseTopic() . '/' . $circuit . '/' . $Name . '/set', json_encode($decoded));
        // Neuen Stand gleich wieder abholen
        $this->Get($Name);
    }

    // ---------------------------------------------------------------------------------------

    private function handleMessage(string $name, string $payload)
    {
        $decoded = json_decode($payload, true);
        if (is_array($decoded)) {
            $values = $decoded;
        } else {
            $values = ['value' => ($decoded !== null) ? $decoded : $payload];
        }

        $fields = [];
        foreach ($values as $key => $value) {
            $key = (string) $key;
            if ($key !== '' && $key[0] === '$') {
                continue; // $qq / $zz = Sender / Empfänger
            }
            $fields[$key] = $value;
        }
        if (count($fields) === 0) {
            return;
        }

        $wanted = $this->ReadPropertyBoolean('AutoCreate') || in_array($name, $this->pollNames(), true);
        $single = count($fields) === 1;
        $map = $this->readJsonAttribute('IdentMap');
        $mapChanged = false;

        foreach ($fields as $field => $value) {
            $ident = $this->makeIdent($name, $single ? '' : $field);
            $vid = @IPS_GetObjectIDByIdent($ident, $this->InstanceID);

            if ($vid === false) {
                if (!$wanted) {
                    continue;
                }
                $type = $this->variableType($value);
                $profile = ($type === VARIABLETYPE_FLOAT && stripos($name, 'temp') !== false) ? '~Temperature' : '';
                $caption = $single ? $name : $name . ' ' . $field;
                $this->MaintainVariable($ident, $caption, $type, $profile, 0, true);
                $vid = @IPS_GetObjectIDByIdent($ident, $this->InstanceID);
                if ($vid === false) {
                    continue;
                }
            }

            $entry = ['name' => $name, 'field' => $field, 'fields' => array_keys($fields)];
            if (!isset($map[$ident]) || $map[$ident] != $entry) {
                $map[$ident] = $entry;
                $mapChanged = true;
            }

            $this->setVariable($vid, $ident, $value);
        }

        if ($mapChanged) {
            $this->WriteAttributeString('IdentMap', json_encode($map));
            $this->refreshActions();
        }
    }

    private function handleFound(string $payload)
    {
        $data = json_decode($payload, true);
        if (!is_array($data) || !isset($data['messages']) || !is_array($data['messages'])) {
            $this->SendDebug('Liste', 'Unerwartetes Format: ' . $payload, 0);
            return;
        }

        $messages = $this->readJsonAttribute('Messages');
        $circuit = $this->circuit();
        foreach ($data['messages'] as $msg) {
            if (!is_array($msg) || !isset($msg['name'])) {
                continue;
            }
            if (isset($msg['circuit']) && strcasecmp((string) $msg['circuit'], $circuit) !== 0) {
                continue;
            }
            $messages[(string) $msg['name']] = [
                'write'   => !empty($msg['write']),
                'passive' => !empty($msg['passive'])
            ];
        }
        $this->WriteAttributeString('Messages', json_encode($messages));
        $this->SendDebug('Liste', count($messages) . ' Nachrichten bekannt', 0);
        $this->refreshActions();
    }

    private function handleStatus(string $name, string $payload)
    {
        $decoded = json_decode($payload, true);
        $value = ($decoded === null && $payload !== 'null') ? $payload : $decoded;
        if (is_array($value)) {
            $value = json_encode($value);
        }

        $ident = $this->makeIdent('Adapter_' . $name, '');
        $vid = @IPS_GetObjectIDByIdent($ident, $this->InstanceID);
        if ($vid === false) {
            if (!$this->ReadPropertyBoolean('AutoCreate')) {
                return;
            }
            $this->MaintainVariable($ident, 'Adapter ' . $name, $this->variableType($value), '', 0, true);
            $vid = @IPS_GetObjectIDByIdent($ident, $this->InstanceID);
            if ($vid === false) {
                return;
            }
        }
        $this->setVariable($vid, $ident, $value);
    }

    private function setVariable(int $vid, string $ident, $value)
    {
        if (is_array($value)) {
            $value = json_encode($value);
        }
        switch (IPS_GetVariable($vid)['VariableType']) {
            case VARIABLETYPE_BOOLEAN:
                $value = is_string($value) ? in_array(strtolower($value), ['1', 'true', 'on', 'ja'], true) : (bool) $value;
                break;
            case VARIABLETYPE_INTEGER:
                $value = (int) $value;
                break;
            case VARIABLETYPE_FLOAT:
                $value = (float) $value;
                break;
            default:
                $value = ($value === null) ? '' : (is_bool($value) ? ($value ? 'true' : 'false') : (string) $value);
        }
        $this->SetValue($ident, $value);
    }

    private function variableType($value): int
    {
        if (is_bool($value)) {
            return VARIABLETYPE_BOOLEAN;
        }
        if (is_int($value) || is_float($value)) {
            return VARIABLETYPE_FLOAT; // Zahlen immer als Float, sonst wechselt der Typ bei 34 / 34.5
        }
        return VARIABLETYPE_STRING;
    }

    /** Schaltet die Bedienbarkeit der Variablen passend zu "schreibbar" und WriteEnabled. */
    private function refreshActions()
    {
        $write = $this->ReadPropertyBoolean('WriteEnabled');
        $messages = $this->readJsonAttribute('Messages');
        foreach ($this->readJsonAttribute('IdentMap') as $ident => $entry) {
            if (@IPS_GetObjectIDByIdent((string) $ident, $this->InstanceID) === false) {
                continue;
            }
            $name = $entry['name'] ?? '';
            if ($write && !empty($messages[$name]['write'])) {
                $this->EnableAction((string) $ident);
            } else {
                $this->DisableAction((string) $ident);
            }
        }
    }

    private function publish(string $topic, string $payload)
    {
        $this->SendDebug('Senden', $topic . ' = ' . $payload, 0);
        if (!$this->HasActiveParent()) {
            $this->SendDebug('Senden', 'Kein aktiver MQTT Server/Client', 0);
            return;
        }
        $data = [
            'DataID'           => self::DATA_ID_TX,
            'PacketType'       => 3,
            'QualityOfService' => 0,
            'Retain'           => false,
            'Topic'            => $topic,
            'Payload'          => $this->useHex() ? bin2hex($payload) : $payload
        ];
        $this->SendDataToParent(json_encode($data, JSON_UNESCAPED_SLASHES));
    }

    /**
     * Je nach Symcon-Version liefert der MQTT Server die Payload als HEX oder als Text.
     * Im Automatikmodus wird das anhand der ersten JSON-Nachricht erkannt und gemerkt.
     */
    private function decodePayload(string $raw): string
    {
        $mode = $this->ReadPropertyInteger('PayloadMode');
        if ($mode === 2) {
            return $raw;
        }
        if ($mode === 1) {
            return $this->isHex($raw) ? (string) hex2bin($raw) : $raw;
        }

        $trim = ltrim($raw);
        if ($trim !== '' && ($trim[0] === '{' || $trim[0] === '[')) {
            $this->rememberEncoding(0);
            return $raw;
        }
        if ($this->isHex($raw)) {
            $bin = (string) hex2bin($raw);
            $binTrim = ltrim($bin);
            if ($binTrim !== '' && ($binTrim[0] === '{' || $binTrim[0] === '[')) {
                $this->rememberEncoding(1);
                return $bin;
            }
            $detected = $this->ReadAttributeInteger('DetectedHex');
            if ($detected === 1 || ($detected === -1 && $this->isPrintable($bin))) {
                return $bin;
            }
        }
        return $raw;
    }

    private function isHex(string $raw): bool
    {
        return $raw !== '' && strlen($raw) % 2 === 0 && ctype_xdigit($raw);
    }

    private function isPrintable(string $s): bool
    {
        return $s !== '' && preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', $s) === 0 && mb_check_encoding($s, 'UTF-8');
    }

    private function rememberEncoding(int $hex)
    {
        if ($this->ReadAttributeInteger('DetectedHex') !== $hex) {
            $this->WriteAttributeInteger('DetectedHex', $hex);
            $this->SendDebug('Payload', $hex ? 'MQTT Server liefert HEX' : 'MQTT Server liefert Text', 0);
        }
    }

    private function useHex(): bool
    {
        $mode = $this->ReadPropertyInteger('PayloadMode');
        if ($mode === 1) {
            return true;
        }
        if ($mode === 2) {
            return false;
        }
        // Automatisch: so senden, wie empfangen wird; Standard HEX (Symcon 8.x)
        return $this->ReadAttributeInteger('DetectedHex') !== 0;
    }

    private function baseTopic(): string
    {
        return trim($this->ReadPropertyString('BaseTopic'), " /\t");
    }

    private function circuit(): string
    {
        return trim($this->ReadPropertyString('Circuit'), " /\t");
    }

    private function pollNames(): array
    {
        $rows = json_decode($this->ReadPropertyString('PollMessages'), true);
        $names = [];
        if (is_array($rows)) {
            foreach ($rows as $row) {
                $name = trim((string) ($row['Name'] ?? ''));
                if ($name !== '') {
                    $names[] = $name;
                }
            }
        }
        return array_values(array_unique($names));
    }

    private function makeIdent(string $name, string $field): string
    {
        $raw = ($field === '') ? $name : $name . '_' . $field;
        return preg_replace('/[^A-Za-z0-9_]/', '_', $raw);
    }

    /** Teil eines Topics für den Empfangsfilter; "/" darf im JSON auch als "\/" stehen. */
    private function quoteTopic(string $part): string
    {
        return str_replace('/', '\\\\?/', preg_quote($part));
    }

    private function readJsonAttribute(string $name): array
    {
        $data = json_decode($this->ReadAttributeString($name), true);
        return is_array($data) ? $data : [];
    }
}
