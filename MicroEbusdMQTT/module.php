<?php

declare(strict_types=1);

/**
 * micro-ebusd MQTT
 *
 * Bindet den eBUS-Adapter (v5/C6) mit micro-ebusd über den Symcon MQTT Server an.
 * micro-ebusd veröffentlicht jede Nachricht als <Basis>/<circuit>/<name> mit JSON-Payload,
 * z. B. ebusd/hmu/RunDataFlowTemp = {"value":35.56}.
 * Anfragen: <Basis>/<circuit>/<name>/get, .../set (JSON), <Basis>/<circuit>/list -> <Basis>/<circuit>/any/found
 *
 * vaillant.json: aus john30/ebusd-configuration (src/vaillant) erzeugter Katalog mit
 * deutschen Namen, Einheiten und Wertelisten (Enums).
 */
class MicroEbusdMQTT extends IPSModule
{
    private const MQTT_SERVER_MODULE = '{C6D2AEB3-6E1F-4B2E-8E69-3A1A00246850}';
    private const DATA_ID_TX = '{043EA491-0325-4ADD-8FC2-A30C8EEB4D3F}';

    private const STATUS_ACTIVE = 102;
    private const STATUS_NO_TOPIC = 201;

    /** Version der Variablen-Metadaten; ältere Einträge werden beim nächsten Wert neu eingerichtet. */
    private const META_VERSION = 2;

    /** Einheit => [Profil, Nachkommastellen, Suffix] */
    private const UNIT_PROFILES = [
        '°C'    => ['~Temperature', 1, ''],
        'K'     => ['MEBUS.Kelvin', 1, ' K'],
        '%'     => ['MEBUS.Percent', 0, ' %'],
        'bar'   => ['MEBUS.Bar', 2, ' bar'],
        'mbar'  => ['MEBUS.Millibar', 0, ' mbar'],
        'kW'    => ['MEBUS.kW', 1, ' kW'],
        'W'     => ['MEBUS.Watt', 0, ' W'],
        'kWh'   => ['MEBUS.kWh', 1, ' kWh'],
        'h'     => ['MEBUS.Hours', 0, ' h'],
        'min'   => ['MEBUS.Minutes', 0, ' min'],
        's'     => ['MEBUS.Seconds', 0, ' s'],
        'l/h'   => ['MEBUS.LitersHour', 0, ' l/h'],
        'l/min' => ['MEBUS.LitersMinute', 2, ' l/min'],
        'm3/h'  => ['MEBUS.CubicMetersHour', 0, ' m³/h'],
        'rpm'   => ['MEBUS.RPM', 0, ' U/min'],
        'rps'   => ['MEBUS.RPS', 1, ' U/s'],
        'U/s'   => ['MEBUS.RPS', 1, ' U/s'],
        'Hz'    => ['MEBUS.Hertz', 0, ' Hz'],
        '°min'  => ['MEBUS.KelvinMinutes', 0, ' °min']
    ];

    /** @var array|null */
    private static $catalogCache = null;

    public function Create()
    {
        parent::Create();

        $this->ConnectParent(self::MQTT_SERVER_MODULE);

        $this->RegisterPropertyString('BaseTopic', 'ebusd');
        $this->RegisterPropertyString('Circuit', 'hmu');
        $this->RegisterPropertyBoolean('AutoCreate', true);
        $this->RegisterPropertyBoolean('Translate', true);
        $this->RegisterPropertyString('PollMessages', '[]');
        $this->RegisterPropertyInteger('PollInterval', 60);
        $this->RegisterPropertyBoolean('WriteEnabled', false);
        $this->RegisterPropertyInteger('PayloadMode', 0);

        // Name => ['write' => bool, 'passive' => bool] aus den "found"-Antworten
        $this->RegisterAttributeString('Messages', '{}');
        // Ident => Metadaten der Variable (Nachricht, Feld, Typ, Einheit, Enum, Bezeichnung)
        $this->RegisterAttributeString('IdentMap', '{}');
        // Enum => [Name => Zahlwert] für die in dieser Instanz benutzten Wertelisten
        $this->RegisterAttributeString('Enums', '{}');
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
        $values = $this->knownMessageRows();

        $form['actions'][] = [
            'type'     => 'List',
            'name'     => 'KnownMessages',
            'caption'  => $this->knownMessagesCaption(count($values)),
            'rowCount' => 12,
            'add'      => false,
            'delete'   => false,
            'columns'  => [
                ['caption' => 'Name', 'name' => 'Name', 'width' => '300px'],
                ['caption' => 'Bedeutung', 'name' => 'Meaning', 'width' => 'auto'],
                ['caption' => 'schreibbar', 'name' => 'Write', 'width' => '100px'],
                ['caption' => 'passiv', 'name' => 'Passive', 'width' => '80px']
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

        if (count($parts) === 2 && $parts[1] !== '' && $parts[1] !== 'list') {
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
                $raw = $Value;
            } else {
                $vid = @IPS_GetObjectIDByIdent($ident, $this->InstanceID);
                $raw = ($vid !== false) ? GetValue($vid) : null;
            }
            $enum = (string) ($map[$ident]['enum'] ?? '');
            if ($enum !== '' && $raw !== null) {
                $raw = $this->enumToName($enum, (int) $raw);
            }
            $payload[$field] = $raw;
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
        // laut micro-ebusd-Doku: <Basis>/<circuit>/list, Antwort auf <Basis>/<circuit>/any/found
        $this->publish($this->baseTopic() . '/' . $circuit . '/list', '');
        echo 'Anfrage gesendet. Die Liste füllt sich, sobald der Adapter antwortet.';
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

    /**
     * Richtet alle vorhandenen Variablen neu ein (Name, Typ, Profil) und fordert die Werte neu an.
     * Selbst vergebene Variablennamen bleiben erhalten.
     */
    public function ApplyTranslations()
    {
        $map = $this->readJsonAttribute('IdentMap');
        $names = [];
        foreach ($map as $ident => $old) {
            $ident = (string) $ident;
            $vid = @IPS_GetObjectIDByIdent($ident, $this->InstanceID);
            if ($vid === false || !is_array($old) || !isset($old['name'])) {
                continue;
            }
            $fields = $old['fields'] ?? [$old['field'] ?? 'value'];
            $single = count($fields) === 1;
            $info = $this->catalogInfo((string) $old['name']);
            $entry = $this->buildEntry((string) $old['name'], (string) ($old['field'] ?? 'value'), $fields, $single, GetValue($vid), $info);
            $newVid = $this->setupVariable($ident, $entry, $vid, $old);
            if ($newVid !== false) {
                $map[$ident] = $entry;
                $names[(string) $old['name']] = true;
            }
        }
        $this->WriteAttributeString('IdentMap', json_encode($map));
        $this->refreshActions();
        foreach (array_keys($names) as $name) {
            $this->Get((string) $name);
        }
        echo count($map) . ' Variablen aktualisiert.';
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
        $fieldNames = array_map('strval', array_keys($fields));
        $lang = $this->lang();
        $map = $this->readJsonAttribute('IdentMap');
        $mapChanged = false;
        $info = null;

        foreach ($fields as $field => $value) {
            $field = (string) $field;
            $ident = $this->makeIdent($name, $single ? '' : $field);
            $vid = @IPS_GetObjectIDByIdent($ident, $this->InstanceID);
            if ($vid === false && !$wanted) {
                continue;
            }

            $entry = $map[$ident] ?? null;
            $current = is_array($entry) && ($entry['v'] ?? 0) === self::META_VERSION && ($entry['lang'] ?? '') === $lang;
            if ($vid === false || !$current) {
                if ($info === null) {
                    $info = $this->catalogInfo($name);
                }
                $newEntry = $this->buildEntry($name, $field, $fieldNames, $single, $value, $info);
                $vid = $this->setupVariable($ident, $newEntry, $vid, is_array($entry) ? $entry : null);
                if ($vid === false) {
                    continue;
                }
                $entry = $newEntry;
                $map[$ident] = $entry;
                $mapChanged = true;
            } elseif (($entry['fields'] ?? []) !== $fieldNames) {
                $entry['fields'] = $fieldNames;
                $map[$ident] = $entry;
                $mapChanged = true;
            }

            $this->setVariable($vid, $ident, $entry, $value);
        }

        if ($mapChanged) {
            $this->WriteAttributeString('IdentMap', json_encode($map));
            $this->refreshActions();
        }
    }

    /** Ermittelt Typ, Einheit, Werteliste und Bezeichnung einer Variable. */
    private function buildEntry(string $name, string $field, array $fields, bool $single, $value, array $info): array
    {
        $meta = $info['fields'][$field] ?? null;
        if ($meta === null && $single && count($info['fields']) === 1) {
            $meta = reset($info['fields']);
        }
        $meta = is_array($meta) ? $meta : [];

        $enum = (string) ($meta['e'] ?? '');
        if ($enum !== '' && !isset($this->catalog()['enums'][$enum])) {
            $enum = '';
        }
        $unit = (string) ($meta['u'] ?? '');

        if ($enum !== '') {
            $type = VARIABLETYPE_INTEGER;
            $unit = '';
        } elseif ($unit !== '' && ($value === null || is_int($value) || is_float($value) || is_numeric($value))) {
            $type = VARIABLETYPE_FLOAT;
        } else {
            $type = $this->variableType($value);
            if ($type !== VARIABLETYPE_FLOAT) {
                $unit = '';
            }
        }

        $lang = $this->lang();
        $caption = ($lang === 'de' && $info['de'] !== '') ? $info['de'] : $name;
        if (!$single) {
            $fieldCaption = ($lang === 'de') ? (string) ($meta['de'] ?? '') : '';
            $caption .= ($fieldCaption !== '') ? ' – ' . $fieldCaption : ' ' . $field;
        }

        return [
            'v'       => self::META_VERSION,
            'lang'    => $lang,
            'name'    => $name,
            'field'   => $field,
            'fields'  => $fields,
            'caption' => $caption,
            'type'    => $type,
            'unit'    => $unit,
            'enum'    => $enum
        ];
    }

    /** Legt die Variable an bzw. passt Typ, Profil und (automatisch vergebenen) Namen an. */
    private function setupVariable(string $ident, array $entry, $oldVid, ?array $oldEntry)
    {
        $profile = $this->profileFor($entry);
        $oldName = ($oldVid !== false) ? IPS_GetName($oldVid) : '';

        $this->MaintainVariable($ident, $entry['caption'], $entry['type'], $profile, 0, true);
        $vid = @IPS_GetObjectIDByIdent($ident, $this->InstanceID);
        if ($vid === false) {
            return false;
        }

        if ($oldVid !== false && $vid === $oldVid && $oldName !== $entry['caption']) {
            // nur umbenennen, wenn der Name noch der automatisch vergebene ist
            $autoNames = [$entry['name'], $entry['name'] . ' ' . $entry['field']];
            if (is_array($oldEntry) && isset($oldEntry['caption'])) {
                $autoNames[] = (string) $oldEntry['caption'];
            }
            if (in_array($oldName, $autoNames, true)) {
                IPS_SetName($vid, $entry['caption']);
            }
        }
        return $vid;
    }

    private function profileFor(array $entry): string
    {
        if ($entry['enum'] !== '') {
            return $this->ensureEnumProfile($entry['enum'], $entry['lang']);
        }
        if ($entry['unit'] !== '' && isset(self::UNIT_PROFILES[$entry['unit']])) {
            [$profile, $digits, $suffix] = self::UNIT_PROFILES[$entry['unit']];
            if ($profile[0] !== '~' && !IPS_VariableProfileExists($profile)) {
                IPS_CreateVariableProfile($profile, VARIABLETYPE_FLOAT);
                IPS_SetVariableProfileDigits($profile, $digits);
                IPS_SetVariableProfileText($profile, '', $suffix);
            }
            return $profile;
        }
        return '';
    }

    /** Legt ein Integer-Profil mit den (deutschen) Texten einer Werteliste an. */
    private function ensureEnumProfile(string $enum, string $lang): string
    {
        $items = $this->catalog()['enums'][$enum] ?? [];
        $profile = 'MEBUS.' . ($lang === 'de' ? '' : 'en.') . $enum;
        if (!IPS_VariableProfileExists($profile)) {
            IPS_CreateVariableProfile($profile, VARIABLETYPE_INTEGER);
        }
        $lookup = [];
        foreach ($items as $item) {
            $text = ($lang === 'de') ? (string) $item['de'] : (string) $item['n'];
            IPS_SetVariableProfileAssociation($profile, (int) $item['v'], $text, '', -1);
            $lookup[strtolower((string) $item['n'])] = (int) $item['v'];
        }

        $enums = $this->readJsonAttribute('Enums');
        if (($enums[$enum] ?? null) != $lookup) {
            $enums[$enum] = $lookup;
            $this->WriteAttributeString('Enums', json_encode($enums));
        }
        return $profile;
    }

    private function enumToInt(string $enum, $value): ?int
    {
        if (is_int($value) || is_float($value) || (is_string($value) && is_numeric($value))) {
            return (int) $value;
        }
        if (!is_string($value)) {
            return null;
        }
        $lookup = $this->readJsonAttribute('Enums')[$enum] ?? [];
        $key = strtolower($value);
        return isset($lookup[$key]) ? (int) $lookup[$key] : null;
    }

    /** Zahlwert -> Originalname der Werteliste (für das Schreiben). */
    private function enumToName(string $enum, int $value)
    {
        foreach ($this->catalog()['enums'][$enum] ?? [] as $item) {
            if ((int) $item['v'] === $value) {
                return (string) $item['n'];
            }
        }
        return $value;
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
        $this->SendDebug('Liste', count($data['messages']) . ' empfangen, ' . count($messages) . ' Nachrichten bekannt', 0);
        $this->refreshActions();

        // offenes Formular sofort aktualisieren
        $rows = $this->knownMessageRows();
        $this->UpdateFormField('KnownMessages', 'values', json_encode($rows));
        $this->UpdateFormField('KnownMessages', 'caption', $this->knownMessagesCaption(count($rows)));
    }

    private function knownMessageRows(): array
    {
        $messages = $this->readJsonAttribute('Messages');
        ksort($messages, SORT_NATURAL | SORT_FLAG_CASE);
        $catalog = $this->catalog()['messages'];
        $rows = [];
        foreach ($messages as $name => $info) {
            $rows[] = [
                'Name'    => (string) $name,
                'Meaning' => (string) ($catalog[strtolower((string) $name)]['de'] ?? ''),
                'Write'   => !empty($info['write']) ? 'ja' : '',
                'Passive' => !empty($info['passive']) ? 'ja' : ''
            ];
        }
        return $rows;
    }

    private function knownMessagesCaption(int $count): string
    {
        return 'Bekannte Nachrichten (' . $count . ') – per "Nachrichtenliste anfordern" füllen';
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
        $this->setVariable($vid, $ident, null, $value);
    }

    private function setVariable(int $vid, string $ident, ?array $entry, $value)
    {
        $enum = (string) ($entry['enum'] ?? '');
        if ($enum !== '') {
            $int = $this->enumToInt($enum, $value);
            if ($int === null) {
                $this->SendDebug('Wert', 'Unbekannter Wert "' . (is_scalar($value) ? (string) $value : json_encode($value)) . '" für ' . $ident . ' (' . $enum . ')', 0);
                return;
            }
            $value = $int;
        }
        if (is_array($value)) {
            $value = json_encode($value);
        }
        switch (IPS_GetVariable($vid)['VariableType']) {
            case VARIABLETYPE_BOOLEAN:
                $value = is_string($value) ? in_array(strtolower($value), ['1', 'true', 'on', 'ja', 'yes'], true) : (bool) $value;
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

    /** Katalogeintrag einer Nachricht: deutsche Bezeichnung und Felder (Einheit, Werteliste, Feldname). */
    private function catalogInfo(string $name): array
    {
        $info = ['de' => '', 'fields' => []];
        $msg = $this->catalog()['messages'][strtolower($name)] ?? null;
        if (!is_array($msg)) {
            return $info;
        }
        $info['de'] = (string) ($msg['de'] ?? '');
        $byCircuit = is_array($msg['f'] ?? null) ? $msg['f'] : [];
        $circuit = strtolower($this->circuit());
        $list = $byCircuit[$circuit] ?? (count($byCircuit) > 0 ? reset($byCircuit) : []);
        foreach ((array) $list as $f) {
            if (is_array($f) && isset($f['n'])) {
                $info['fields'][(string) $f['n']] = $f;
            }
        }
        return $info;
    }

    private function catalog(): array
    {
        if (self::$catalogCache === null) {
            $data = json_decode((string) @file_get_contents(__DIR__ . '/vaillant.json'), true);
            if (!is_array($data)) {
                $data = [];
            }
            $data['messages'] = is_array($data['messages'] ?? null) ? $data['messages'] : [];
            $data['enums'] = is_array($data['enums'] ?? null) ? $data['enums'] : [];
            self::$catalogCache = $data;
        }
        return self::$catalogCache;
    }

    private function lang(): string
    {
        return $this->ReadPropertyBoolean('Translate') ? 'de' : 'en';
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
