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
class MicroEbusdMQTT extends IPSModuleStrict
{
    private const DATA_ID_TX = '{043EA491-0325-4ADD-8FC2-A30C8EEB4D3F}';

    private const STATUS_ACTIVE = 102;
    private const STATUS_NO_TOPIC = 201;

    /** Version der Variablen-Metadaten; ältere Einträge werden beim nächsten Wert neu eingerichtet. */
    private const META_VERSION = 4;

    /** Präfix der Variablenprofile, die Version 1.x angelegt hat (werden beim Neu-Einrichten entfernt). */
    private const LEGACY_PROFILE_PREFIX = 'MEBUS.';

    /** Einheit => [Nachkommastellen, Suffix] */
    private const UNITS = [
        '°C'    => [1, ' °C'],
        'K'     => [1, ' K'],
        '%'     => [0, ' %'],
        'bar'   => [2, ' bar'],
        'mbar'  => [0, ' mbar'],
        'kW'    => [1, ' kW'],
        'W'     => [0, ' W'],
        'kWh'   => [1, ' kWh'],
        'h'     => [0, ' h'],
        'min'   => [0, ' min'],
        's'     => [0, ' s'],
        'l/h'   => [0, ' l/h'],
        'l/min' => [2, ' l/min'],
        'm3/h'  => [0, ' m³/h'],
        'rpm'   => [0, ' U/min'],
        'rps'   => [1, ' U/s'],
        'U/s'   => [1, ' U/s'],
        'Hz'    => [0, ' Hz'],
        '°min'  => [0, ' °min']
    ];

    /** Statuswerte des Adapters direkt unter dem Basis-Topic: Name => Suffix */
    private const STATUS_UNITS = [
        'rssi'   => ' dBm',
        'uptime' => ' s',
        'temp'   => ' °C'
    ];

    private static ?array $catalogCache = null;

    public function Create(): void
    {
        parent::Create();

        $this->RegisterPropertyString('BaseTopic', 'ebusd');
        $this->RegisterPropertyString('Circuit', 'hmu');
        $this->RegisterPropertyBoolean('AutoCreate', true);
        $this->RegisterPropertyBoolean('Translate', true);
        $this->RegisterPropertyString('PollMessages', '[]');
        $this->RegisterPropertyInteger('PollInterval', 60);
        $this->RegisterPropertyBoolean('WriteEnabled', false);

        // Name => ['write' => bool, 'passive' => bool] aus den "found"-Antworten
        $this->RegisterAttributeString('Messages', '{}');
        // Ident => Metadaten der Variable (Nachricht, Feld, Typ, Einheit, Enum, Bezeichnung)
        $this->RegisterAttributeString('IdentMap', '{}');

        $this->RegisterTimer('Poll', 0, 'MEBUS_PollNow($_IPS[\'TARGET\']);');
    }

    public function ApplyChanges(): void
    {
        parent::ApplyChanges();

        if (IPS_GetKernelRunlevel() !== KR_READY) {
            $this->RegisterMessage(0, IPS_KERNELSTARTED);
            return;
        }

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

    public function MessageSink(int $TimeStamp, int $SenderID, int $Message, array $Data): void
    {
        if ($Message === IPS_KERNELSTARTED) {
            $this->ApplyChanges();
        }
    }

    public function Destroy(): void
    {
        parent::Destroy();
    }

    public function GetConfigurationForm(): string
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
                ['caption' => 'Query', 'name' => 'Poll', 'width' => '100px', 'edit' => ['type' => 'CheckBox']],
                ['caption' => 'Name', 'name' => 'Name', 'width' => '300px'],
                ['caption' => 'Meaning', 'name' => 'Meaning', 'width' => 'auto'],
                ['caption' => 'writable', 'name' => 'Write', 'width' => '100px'],
                ['caption' => 'passive', 'name' => 'Passive', 'width' => '80px']
            ],
            'values'   => $values
        ];
        $form['actions'][] = [
            'type'    => 'Button',
            'caption' => 'Add checked messages to the query list',
            'onClick' => [
                '$names = [];',
                'foreach ($KnownMessages as $row) { if ($row[\'Poll\']) { $names[] = $row[\'Name\']; } }',
                'MEBUS_AddToPollList($id, json_encode($names));'
            ]
        ];

        return (string) json_encode($form);
    }

    public function ReceiveData(string $JSONString): string
    {
        $data = json_decode($JSONString, true);
        if (!is_array($data) || !isset($data['Topic'])) {
            return '';
        }

        $topic = (string) $data['Topic'];
        $payload = $this->decodePayload((string) ($data['Payload'] ?? ''));
        $this->SendDebug('Receive', $topic . ' = ' . $payload, 0);

        $base = $this->baseTopic() . '/';
        if ($base === '/' || !str_starts_with($topic, $base)) {
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

        // Die Nachrichtenliste kommt je nach Firmware auf .../any/found oder .../any;
        // deshalb zählt der Inhalt, nicht das Topic.
        if ($this->isListAnswer($payload)) {
            $this->handleFound($payload);
        } elseif (count($parts) === 2 && $parts[1] !== '' && $parts[1] !== 'list') {
            $this->handleMessage($parts[1], $payload);
        }
        // .../get, .../set, .../list sind Anfragen (auch unsere eigenen) und werden ignoriert

        return '';
    }

    public function RequestAction(string $Ident, mixed $Value): void
    {
        // Erwartbare Fehlschläge per Ausgabe melden: Symcon macht daraus die Warnung für den Aufrufer.
        $entry = $this->readJsonAttribute('IdentMap')[$Ident] ?? null;
        if (!is_array($entry) || !isset($entry['name'])) {
            echo sprintf($this->Translate('Unknown variable: %s'), $Ident);
            return;
        }
        if (!$this->isWritable((string) $entry['name'])) {
            echo $this->Translate('Writing is not enabled for this instance or the adapter does not report the message as writable.');
            return;
        }

        $map = $this->readJsonAttribute('IdentMap');
        $fields = $entry['fields'] ?? [$entry['field']];
        $single = count($fields) === 1;

        // micro-ebusd erwartet beim Schreiben die Felder der Nachricht als JSON-Objekt
        $payload = [];
        foreach ($fields as $field) {
            $field = (string) $field;
            $ident = $this->makeIdent((string) $entry['name'], $single ? '' : $field);
            if ($ident === $Ident) {
                $raw = $Value;
            } else {
                $vid = @IPS_GetObjectIDByIdent($ident, $this->InstanceID);
                if (!is_int($vid) || $vid <= 0) {
                    // lieber nicht schreiben als ein Feld mit null überschreiben
                    echo sprintf($this->Translate('Not written: the value of field "%s" is unknown. Query the message first.'), $field);
                    return;
                }
                $raw = GetValue($vid);
            }
            $enum = (string) ($map[$ident]['enum'] ?? '');
            if ($enum !== '' && !empty($map[$ident]['num'])) {
                $raw = (int) $raw;
            } elseif ($enum !== '') {
                $raw = $this->enumToName($enum, (int) $raw);
                if (!is_string($raw)) {
                    echo sprintf($this->Translate('Not written: %d is not a valid value for field "%s".'), $raw, $field);
                    return;
                }
            } elseif (is_float($raw) && floor($raw) === $raw && abs($raw) < 1e9) {
                $raw = (int) $raw; // 50 statt 50.0
            }
            $payload[$field] = $raw;
        }

        $this->Set((string) $entry['name'], (string) json_encode($payload));
    }

    /** Fordert die Nachrichtenliste des Geräts an (Antwort kommt auf .../any/found). */
    public function RequestList(): void
    {
        $circuit = $this->circuit();
        if ($circuit === '') {
            echo $this->Translate('The adapter status instance has no message list.');
            return;
        }
        // Der Adapter liefert die Liste seitenweise (rund 20 Einträge und ein Feld "more").
        // handleFound() fordert die Folgeseiten an, solange diese Anforderung läuft.
        $this->SetBuffer('ListPaging', (string) json_encode(['started' => time(), 'seen' => [], 'pages' => 0]));
        $this->publish($this->baseTopic() . '/' . $circuit . '/list', '');
        echo $this->Translate('Request sent. The list fills page by page as the adapter answers.');
    }

    /**
     * Fragt jede bekannte, aktiv lesbare Nachricht einmal ab. So entstehen die Variablen,
     * ohne dass die Namen in die Abfrageliste müssen.
     */
    public function QueryKnown(): void
    {
        $count = 0;
        foreach ($this->readJsonAttribute('Messages') as $name => $info) {
            if (empty($info['passive']) && $this->Get((string) $name)) {
                $count++;
            }
        }
        echo sprintf($this->Translate('%d messages queried. Variables appear as soon as the adapter answers.'), $count);
    }

    /**
     * Trägt Nachrichten in die Abfrageliste des offenen Formulars ein (JSON-Liste von Namen).
     * Gespeichert wird erst mit "Änderungen übernehmen": Eigenschaften gehören dem Nutzer.
     */
    public function AddToPollList(string $Names): void
    {
        $wanted = json_decode($Names, true);
        $messages = $this->readJsonAttribute('Messages');
        $list = $this->pollNames();
        $added = 0;
        $skipped = 0;
        foreach (is_array($wanted) ? $wanted : [] as $name) {
            $name = trim((string) $name);
            if ($name === '' || in_array($name, $list, true)) {
                continue;
            }
            if (!empty($messages[$name]['passive'])) {
                $skipped++; // rein passive Nachrichten lassen sich nicht abfragen
                continue;
            }
            $list[] = $name;
            $added++;
        }
        if ($added > 0) {
            $rows = array_map(static fn (string $n): array => ['Name' => $n], $list);
            $this->UpdateFormField('PollMessages', 'values', (string) json_encode($rows));
        }
        echo sprintf($this->Translate('%d messages added to the query list, %d passive ones skipped. Save with "Apply changes".'), $added, $skipped);
    }

    /** Fragt alle Nachrichten aus der Abfrageliste aktiv ab. */
    public function PollNow(): void
    {
        foreach ($this->pollNames() as $name) {
            $this->Get($name);
        }
    }

    /** Fordert eine einzelne Nachricht an, z. B. MEBUS_Get(12345, 'RunDataFlowTemp'). */
    public function Get(string $Name): bool
    {
        $circuit = $this->circuit();
        if ($circuit === '' || $Name === '') {
            return false;
        }
        return $this->publish($this->baseTopic() . '/' . $circuit . '/' . $Name . '/get', '');
    }

    /** Schreibt eine Nachricht, z. B. MEBUS_Set(12345, 'HwcTempDesired', '{"value":50}'). */
    public function Set(string $Name, string $JsonPayload): bool
    {
        $circuit = $this->circuit();
        if (!$this->ReadPropertyBoolean('WriteEnabled') || $circuit === '' || $Name === '') {
            echo $this->Translate('Writing is not enabled for this instance.');
            return false;
        }
        $decoded = json_decode($JsonPayload, true);
        if (!is_array($decoded) || $decoded === [] || array_is_list($decoded)) {
            echo $this->Translate('The payload must be a JSON object, e.g. {"value":50}.');
            return false;
        }
        if (!$this->publish($this->baseTopic() . '/' . $circuit . '/' . $Name . '/set', (string) json_encode($decoded))) {
            return false;
        }
        // Neuen Stand gleich wieder abholen
        $this->Get($Name);
        return true;
    }

    /**
     * Richtet alle vorhandenen Variablen neu ein (Name, Typ, Darstellung) und fordert die Werte neu an.
     * Selbst vergebene Variablennamen bleiben erhalten.
     */
    public function ApplyTranslations(): void
    {
        $map = $this->readJsonAttribute('IdentMap');
        $names = [];
        $count = 0;
        foreach ($map as $ident => $old) {
            $ident = (string) $ident;
            $vid = @IPS_GetObjectIDByIdent($ident, $this->InstanceID);
            if (!is_int($vid) || $vid <= 0 || !is_array($old) || !isset($old['name'])) {
                continue;
            }
            $fields = $old['fields'] ?? [$old['field'] ?? 'value'];
            $single = count($fields) === 1;
            $info = $this->catalogInfo((string) $old['name']);
            $entry = $this->buildEntry((string) $old['name'], (string) ($old['field'] ?? 'value'), $fields, $single, GetValue($vid), $info);
            if ($this->setupVariable($ident, $entry, $vid, $old)) {
                $map[$ident] = $entry;
                $names[(string) $old['name']] = true;
                $count++;
            }
        }
        $this->WriteAttributeString('IdentMap', (string) json_encode($map));
        $this->refreshActions();
        $removed = $this->removeUnusedLegacyProfiles();
        foreach (array_keys($names) as $name) {
            $this->Get((string) $name);
        }
        echo sprintf($this->Translate('%d variables updated, %d unused profiles of version 1 removed.'), $count, $removed);
    }

    // ---------------------------------------------------------------------------------------

    private function handleMessage(string $name, string $payload): void
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
            // null = der Adapter hat keinen Wert (Fühler fehlt): alten Stand behalten, nichts anlegen.
            // "ignore" sind Füllbytes der Definition.
            if ($value === null || preg_match('/^ignore(_\d+)?$/i', $field) === 1) {
                continue;
            }
            // Rohbytes ("00 00") in Feldern, die der Katalog nicht kennt, sagen nichts aus.
            if (is_string($value) && preg_match('/^[0-9a-f]{2}( [0-9a-f]{2})+$/i', $value) === 1) {
                $info ??= $this->catalogInfo($name);
                if (!isset($info['fields'][strtolower($field)])) {
                    continue;
                }
            }
            $ident = $this->makeIdent($name, $single ? '' : $field);
            $vid = @IPS_GetObjectIDByIdent($ident, $this->InstanceID);
            $exists = is_int($vid) && $vid > 0;
            if (!$exists && !$wanted) {
                continue;
            }

            $entry = $map[$ident] ?? null;
            $current = is_array($entry) && ($entry['v'] ?? 0) === self::META_VERSION && ($entry['lang'] ?? '') === $lang;
            if (!$exists || !$current) {
                $info ??= $this->catalogInfo($name);
                $newEntry = $this->buildEntry($name, $field, $fieldNames, $single, $value, $info);
                if (!$this->setupVariable($ident, $newEntry, $exists ? $vid : 0, is_array($entry) ? $entry : null)) {
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
            if (($entry['d'] ?? null) === 0 && is_float($value) && floor($value) !== $value) {
                // bisher nur ganze Zahlen gesehen, jetzt kommt ein Kommawert
                $entry['d'] = 2;
                $this->MaintainVariable($ident, IPS_GetName($vid), (int) $entry['type'], $this->presentationFor($entry, (bool) ($entry['w'] ?? false)), 0, true);
                $map[$ident] = $entry;
                $mapChanged = true;
            }

            $this->setVariable($ident, $entry, $value);
        }

        if ($mapChanged) {
            $this->WriteAttributeString('IdentMap', (string) json_encode($map));
            $this->refreshActions();
        }
    }

    /** Ermittelt Typ, Einheit, Werteliste und Bezeichnung einer Variable. */
    private function buildEntry(string $name, string $field, array $fields, bool $single, mixed $value, array $info): array
    {
        $meta = $info['fields'][strtolower($field)] ?? null; // micro-ebusd schreibt Feldnamen klein
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
        } elseif ($unit !== '' && ($value === null || is_numeric($value))) {
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
            'enum'    => $enum,
            // Werteliste, die der Adapter als Zahl sendet (0/1): auch als Zahl zurückschreiben
            'num'     => $enum !== '' && (is_int($value) || is_float($value)),
            // Zahlen ohne Einheit: ganze Zahlen ohne Nachkommastellen, bis ein Kommawert kommt
            'd'       => is_int($value) ? 0 : 2
        ];
    }

    /** Legt die Variable an bzw. passt Typ, Darstellung und (automatisch vergebenen) Namen an. */
    private function setupVariable(string $ident, array $entry, int $oldVid, ?array $oldEntry): bool
    {
        $oldName = ($oldVid > 0) ? IPS_GetName($oldVid) : '';

        // Die Eingabe-Darstellungen setzen eine Aktion voraus; die schaltet erst refreshActions() frei.
        $this->MaintainVariable($ident, $entry['caption'], $entry['type'], $this->presentationFor($entry, false), 0, true);
        $vid = @IPS_GetObjectIDByIdent($ident, $this->InstanceID);
        if (!is_int($vid) || $vid <= 0) {
            return false;
        }

        if ($oldVid > 0 && $vid === $oldVid && $oldName !== $entry['caption']) {
            // nur umbenennen, wenn der Name noch der automatisch vergebene ist
            $autoNames = [$entry['name'], $entry['name'] . ' ' . $entry['field']];
            if (is_array($oldEntry) && isset($oldEntry['caption'])) {
                $autoNames[] = (string) $oldEntry['caption'];
            }
            if (in_array($oldName, $autoNames, true)) {
                IPS_SetName($vid, $entry['caption']);
            }
        }
        return true;
    }

    /**
     * Darstellung einer Variable. Anzeigewerte bekommen die Wertanzeige (Wertelisten als Intervalle,
     * weil die Kachel-Visu eine Aufzählung ohne Aktion als nackte Zahl zeigt), bedienbare Variablen
     * die Aufzählung bzw. die Werteingabe.
     */
    private function presentationFor(array $entry, bool $writable): array
    {
        $enum = (string) ($entry['enum'] ?? '');
        if ($enum !== '') {
            $de = ($entry['lang'] ?? 'de') === 'de';
            $rows = [];
            foreach ($this->catalog()['enums'][$enum] ?? [] as $item) {
                $value = (int) $item['v'];
                $text = $de ? (string) $item['de'] : (string) $item['n'];
                if ($writable) {
                    $rows[] = ['Value' => $value, 'Caption' => $text, 'IconActive' => false, 'IconValue' => '', 'Color' => -1];
                } else {
                    $rows[] = [
                        'IntervalMinValue'   => $value,
                        'IntervalMaxValue'   => $value,
                        'ConstantActive'     => true,
                        'ConstantValue'      => $text,
                        'ConversionFactor'   => 1,
                        'PrefixActive'       => false, 'PrefixValue' => '',
                        'SuffixActive'       => false, 'SuffixValue' => '',
                        'DigitsActive'       => false, 'DigitsValue' => 0,
                        'IconActive'         => false, 'IconValue' => '',
                        'ColorActive'        => false, 'ColorValue' => -1,
                        'ContentColorActive' => false, 'ContentColorValue' => -1
                    ];
                }
            }
            $json = (string) json_encode($rows, JSON_UNESCAPED_UNICODE);
            return $writable
                ? ['PRESENTATION' => VARIABLE_PRESENTATION_ENUMERATION, 'OPTIONS' => $json]
                : ['PRESENTATION' => VARIABLE_PRESENTATION_VALUE_PRESENTATION, 'INTERVALS_ACTIVE' => true, 'INTERVALS' => $json];
        }

        $type = (int) ($entry['type'] ?? VARIABLETYPE_STRING);
        if ($type === VARIABLETYPE_BOOLEAN) {
            return ['PRESENTATION' => $writable ? VARIABLE_PRESENTATION_SWITCH : VARIABLE_PRESENTATION_VALUE_PRESENTATION];
        }

        [$digits, $suffix] = self::UNITS[(string) ($entry['unit'] ?? '')] ?? [$entry['d'] ?? null, (string) ($entry['suffix'] ?? '')];
        if ($writable) {
            return ['PRESENTATION' => VARIABLE_PRESENTATION_VALUE_INPUT, 'SUFFIX' => $suffix];
        }
        $presentation = ['PRESENTATION' => VARIABLE_PRESENTATION_VALUE_PRESENTATION, 'SUFFIX' => $suffix];
        if ($type === VARIABLETYPE_FLOAT && $digits !== null) {
            $presentation['DIGITS'] = $digits;
        }
        return $presentation;
    }

    /** Text oder Zahl der Werteliste -> Zahlwert. */
    private function enumToInt(string $enum, mixed $value): ?int
    {
        if (is_int($value) || is_float($value) || (is_string($value) && is_numeric($value))) {
            return (int) $value;
        }
        if (!is_string($value)) {
            return null;
        }
        foreach ($this->catalog()['enums'][$enum] ?? [] as $item) {
            if (strcasecmp((string) $item['n'], $value) === 0) {
                return (int) $item['v'];
            }
        }
        return null;
    }

    /** Zahlwert -> Originalname der Werteliste (für das Schreiben). */
    private function enumToName(string $enum, int $value): string|int
    {
        foreach ($this->catalog()['enums'][$enum] ?? [] as $item) {
            if ((int) $item['v'] === $value) {
                return (string) $item['n'];
            }
        }
        return $value;
    }

    /** Listenantwort: JSON-Objekt mit "messages" als Liste von Einträgen mit "name". */
    private function isListAnswer(string $payload): bool
    {
        if (!str_contains($payload, '"messages"')) {
            return false;
        }
        $data = json_decode($payload, true);
        if (!is_array($data) || !is_array($data['messages'] ?? null) || !array_is_list($data['messages'])) {
            return false;
        }
        foreach ($data['messages'] as $msg) {
            if (!is_array($msg) || !isset($msg['name'])) {
                return false;
            }
        }
        return true;
    }

    /** Entfernt Variablen, die frühere Versionen aus einer Listenantwort auf .../any angelegt haben. */
    private function removeListVariables(): void
    {
        $map = $this->readJsonAttribute('IdentMap');
        $changed = false;
        foreach (['any_circuit', 'any_name', 'any_messages'] as $ident) {
            if (isset($map[$ident])) {
                $this->UnregisterVariable($ident);
                unset($map[$ident]);
                $changed = true;
            }
        }
        if ($changed) {
            $this->WriteAttributeString('IdentMap', (string) json_encode($map));
        }
    }

    private function handleFound(string $payload): void
    {
        $data = json_decode($payload, true);
        if (!is_array($data) || !isset($data['messages']) || !is_array($data['messages'])) {
            $this->SendDebug('List', 'Unexpected format: ' . $payload, 0);
            return;
        }

        $this->removeListVariables();
        $messages = $this->readJsonAttribute('Messages');
        $circuit = $this->circuit();
        $seen = [];
        foreach ($data['messages'] as $msg) {
            if (!is_array($msg) || !isset($msg['name'])) {
                continue;
            }
            if (isset($msg['circuit']) && strcasecmp((string) $msg['circuit'], $circuit) !== 0) {
                continue;
            }
            // Eine Nachricht steht mehrfach in der Liste (lesen/schreiben, aktiv/passiv):
            // schreibbar, sobald eine AKTIVE Fassung schreibbar ist (passiv + schreibbar heißt nur:
            // ein anderes Gerät schreibt sie, wir können das nicht); passiv, wenn es keine aktive gibt.
            $key = (string) $msg['name'];
            $passive = !empty($msg['passive']);
            $seen[$key] = [
                'write'   => (!empty($msg['write']) && !$passive) || !empty($seen[$key]['write']),
                'passive' => $passive && ($seen[$key]['passive'] ?? true)
            ];
        }
        foreach ($seen as $key => $flags) {
            $messages[$key] = $flags;
        }
        $this->WriteAttributeString('Messages', (string) json_encode($messages));
        $this->SendDebug('List', count($data['messages']) . ' received, ' . count($messages) . ' messages known', 0);
        $this->refreshActions();

        $this->requestNextListPage($data['more'] ?? null, count($seen));

        // offenes Formular sofort aktualisieren
        $rows = $this->knownMessageRows();
        $this->UpdateFormField('KnownMessages', 'values', (string) json_encode($rows));
        $this->UpdateFormField('KnownMessages', 'caption', $this->knownMessagesCaption(count($rows)));
    }

    /**
     * Fordert die nächste Seite der Nachrichtenliste an. Der Abruf über "more" ist nicht dokumentiert;
     * gesendet wird der Wert so zurück, wie der Adapter ihn geliefert hat. Abbruch, sobald sich ein
     * "more"-Wert wiederholt (der Adapter ignoriert die Angabe), nach 100 Seiten oder 2 Minuten.
     */
    private function requestNextListPage(mixed $more, int $entries): void
    {
        $state = json_decode($this->GetBuffer('ListPaging'), true);
        if (!is_array($state) || (time() - (int) ($state['started'] ?? 0)) > 120) {
            return; // keine laufende Anforderung dieser Instanz
        }
        $state['pages'] = (int) ($state['pages'] ?? 0) + 1;
        $done = !is_int($more) || $more === 0 || $entries === 0
            || in_array($more, $state['seen'] ?? [], true) || $state['pages'] >= 100;
        if ($done) {
            $this->SendDebug('List', 'Finished after ' . $state['pages'] . ' page(s), last "more": ' . json_encode($more), 0);
            $this->SetBuffer('ListPaging', '');
            return;
        }
        $state['seen'][] = $more;
        $this->SetBuffer('ListPaging', (string) json_encode($state));
        $this->publish($this->baseTopic() . '/' . $this->circuit() . '/list', (string) json_encode(['more' => $more]));
    }

    private function knownMessageRows(): array
    {
        $messages = $this->readJsonAttribute('Messages');
        ksort($messages, SORT_NATURAL | SORT_FLAG_CASE);
        $catalog = $this->catalog()['messages'];
        $yes = $this->Translate('yes');
        $poll = $this->pollNames();
        $rows = [];
        foreach ($messages as $name => $info) {
            $rows[] = [
                'Poll'    => in_array((string) $name, $poll, true),
                'Name'    => (string) $name,
                'Meaning' => (string) ($catalog[strtolower((string) $name)]['de'] ?? ''),
                'Write'   => !empty($info['write']) ? $yes : '',
                'Passive' => !empty($info['passive']) ? $yes : ''
            ];
        }
        return $rows;
    }

    private function knownMessagesCaption(int $count): string
    {
        return sprintf($this->Translate('Known messages (%d) – fill with "Request message list from adapter"'), $count);
    }

    private function handleStatus(string $name, string $payload): void
    {
        $decoded = json_decode($payload, true);
        $value = ($decoded === null && $payload !== 'null') ? $payload : $decoded;
        if (is_array($value)) {
            $value = json_encode($value);
        }

        $ident = $this->makeIdent('Adapter_' . $name, '');
        $vid = @IPS_GetObjectIDByIdent($ident, $this->InstanceID);
        if (!is_int($vid) || $vid <= 0) {
            if (!$this->ReadPropertyBoolean('AutoCreate')) {
                return;
            }
            $type = $this->variableType($value);
            $look = ['type' => $type, 'suffix' => self::STATUS_UNITS[strtolower($name)] ?? '', 'd' => is_int($value) ? 0 : null];
            $this->MaintainVariable($ident, 'Adapter ' . $name, $type, $this->presentationFor($look, false), 0, true);
        }
        $this->setVariable($ident, null, $value);
    }

    private function setVariable(string $ident, ?array $entry, mixed $value): void
    {
        $vid = @IPS_GetObjectIDByIdent($ident, $this->InstanceID);
        if (!is_int($vid) || $vid <= 0) {
            return;
        }
        $enum = (string) ($entry['enum'] ?? '');
        if ($enum !== '') {
            $int = $this->enumToInt($enum, $value);
            if ($int === null) {
                $this->SendDebug('Value', 'Unknown value "' . (is_scalar($value) ? (string) $value : json_encode($value)) . '" for ' . $ident . ' (' . $enum . ')', 0);
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

    private function variableType(mixed $value): int
    {
        if (is_bool($value)) {
            return VARIABLETYPE_BOOLEAN;
        }
        if (is_int($value) || is_float($value)) {
            return VARIABLETYPE_FLOAT; // Zahlen immer als Float, sonst wechselt der Typ bei 34 / 34.5
        }
        return VARIABLETYPE_STRING;
    }

    private function isWritable(string $name): bool
    {
        return $this->ReadPropertyBoolean('WriteEnabled') && !empty($this->readJsonAttribute('Messages')[$name]['write']);
    }

    /** Schaltet Bedienbarkeit und Darstellung der Variablen passend zu "schreibbar" und WriteEnabled. */
    private function refreshActions(): void
    {
        $write = $this->ReadPropertyBoolean('WriteEnabled');
        $messages = $this->readJsonAttribute('Messages');
        $map = $this->readJsonAttribute('IdentMap');
        $changed = false;
        foreach ($map as $ident => $entry) {
            $ident = (string) $ident;
            $vid = @IPS_GetObjectIDByIdent($ident, $this->InstanceID);
            if (!is_int($vid) || $vid <= 0 || !is_array($entry) || ($entry['v'] ?? 0) !== self::META_VERSION) {
                continue; // ältere Einträge richtet der nächste Wert neu ein
            }
            $writable = $write && !empty($messages[$entry['name'] ?? '']['write']);
            if ((bool) ($entry['w'] ?? false) === $writable) {
                continue;
            }
            // Reihenfolge: Die Eingabe-Darstellungen verlangen eine vorhandene Aktion.
            if ($writable) {
                $this->EnableAction($ident);
            }
            $this->MaintainVariable($ident, IPS_GetName($vid), (int) $entry['type'], $this->presentationFor($entry, $writable), 0, true);
            if (!$writable) {
                $this->DisableAction($ident);
            }
            $map[$ident]['w'] = $writable;
            $changed = true;
        }
        if ($changed) {
            $this->WriteAttributeString('IdentMap', (string) json_encode($map));
        }
    }

    /** Entfernt die Variablenprofile aus Version 1.x, die keine Variable mehr benutzt. */
    private function removeUnusedLegacyProfiles(): int
    {
        $profiles = array_filter(IPS_GetVariableProfileList(), static fn ($p) => str_starts_with((string) $p, self::LEGACY_PROFILE_PREFIX));
        if (count($profiles) === 0) {
            return 0;
        }
        $used = [];
        foreach (IPS_GetVariableList() as $vid) {
            $variable = IPS_GetVariable($vid);
            $used[$variable['VariableProfile']] = true;
            $used[$variable['VariableCustomProfile']] = true;
        }
        $removed = 0;
        foreach ($profiles as $profile) {
            if (!isset($used[$profile]) && @IPS_DeleteVariableProfile($profile)) {
                $removed++;
            }
        }
        return $removed;
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
                $info['fields'][strtolower((string) $f['n'])] = $f;
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

    private function publish(string $topic, string $payload): bool
    {
        $this->SendDebug('Send', $topic . ' = ' . $payload, 0);
        if (!$this->HasActiveParent()) {
            $this->SendDebug('Send', 'No active MQTT server/client', 0);
            return false;
        }
        $data = [
            'DataID'           => self::DATA_ID_TX,
            'PacketType'       => 3,
            'QualityOfService' => 0,
            'Retain'           => false,
            'Topic'            => $topic,
            'Payload'          => bin2hex($payload) // Datenfluss ist bei IPSModuleStrict HEX-kodiert
        ];
        $this->SendDataToParent((string) json_encode($data, JSON_UNESCAPED_SLASHES));
        return true;
    }

    /** Der Datenfluss liefert die Payload an ein IPSModuleStrict-Kind HEX-kodiert. */
    private function decodePayload(string $raw): string
    {
        if ($raw !== '' && strlen($raw) % 2 === 0 && ctype_xdigit($raw)) {
            return (string) hex2bin($raw);
        }
        return $raw;
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
        return (string) preg_replace('/[^A-Za-z0-9_]/', '_', $raw);
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
