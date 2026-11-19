<?php

declare(strict_types=1);

/**
 * Energiewerte: welche Nachrichten je Gerät sinnvoll abgefragt werden.
 *
 * Es gibt drei Ebenen (an einer aroTHERM plus mit sensoCOMFORT gemessen, 19.11.2026, und in den
 * Diskussionen des ebusd-Projekts beschrieben, john30/ebusd-configuration #490, #600):
 * - Wärmepumpe (hmu): Die Statistik „Stat…EnergySum…“ (Befehl B516) zählt Strom und Umweltenergie
 *   dieses Geräts laufend und auf Wh genau. Fehlt sie, bleiben die Zähler in ganzen kWh.
 * - Inneneinheit (vwz, vwzio): Dieselbe Statistik zählt dort den Heizstab samt Standby.
 * - Regler (ctl…, bas…): „PrEnergySum…“ ist die Summe der ganzen Anlage in ganzen kWh; der Regler
 *   aktualisiert sie nur um Mitternacht. Seine eigene Statistik ist bloß sein Eigenverbrauch.
 */
trait MicroEbusdEnergy
{
    /** Nachricht => Intervall in Sekunden (0 = Standardintervall der Instanz) */
    private static array $energyHeatPumpFine = [
        'StatElectricEnergySum'       => 600,
        'StatElectricEnergySumHc'     => 600,
        'StatElectricEnergySumHwc'    => 600,
        'StatElectricEnergySumCool'   => 600,
        'StatEnvironmentEnergySum'    => 600,
        'StatEnvironmentEnergySumHc'  => 600,
        'StatEnvironmentEnergySumHwc' => 600
    ];
    private static array $energyHeatPumpCoarse = [
        'TotalEnergyUsage' => 600,
        'YieldHc'          => 600,
        'YieldHwc'         => 600
    ];
    private static array $energyHeatPump = [
        'YieldHcDay'           => 600,
        'YieldHwcDay'          => 600,
        'YieldHcMonth'         => 600,
        'YieldHwcMonth'        => 600,
        'CopHc'                => 3600,
        'CopHwc'               => 3600,
        'CopHcMonth'           => 3600,
        'CopHwcMonth'          => 3600,
        'CurrentConsumedPower' => 0,
        'CurrentYieldPower'    => 0,
        // Eingangswerte der berechneten Heizleistung und Leistungszahl
        'BuildingCircuitFlow'             => 0,
        'RunDataFlowTemp'                 => 0,
        'RunDataReturnTemp'               => 0,
        'RunDataElectricPowerConsumption' => 0
    ];
    private static array $energyController = [
        'PrEnergySumHc'           => 3600,
        'PrEnergySumHwc'          => 3600,
        'PrEnergySumHcThisMonth'  => 3600,
        'PrEnergySumHwcThisMonth' => 3600,
        'PrEnergySumHcLastMonth'  => 86400,
        'PrEnergySumHwcLastMonth' => 86400,
        'YieldTotal'              => 3600
    ];
    private static array $energyIndoorUnit = [
        'StatElectricEnergySum'    => 600,
        'StatElectricEnergySumHc'  => 600,
        'StatElectricEnergySumHwc' => 600
    ];

    /**
     * Trägt die Energiewerte dieses Geräts in die Abfrageliste des offenen Formulars ein.
     * Gespeichert wird erst mit „Änderungen übernehmen“: Eigenschaften gehören dem Nutzer.
     */
    public function AddEnergyPreset(): void
    {
        $circuit = strtolower($this->circuit());
        $messages = $this->readJsonAttribute('Messages');
        // Erst die Geräteart: der Adapter-Status und z. B. das VR 71 haben keine Auswahl, mit oder ohne Liste.
        if ($this->energyPreset($circuit, ['StatElectricEnergySum' => []]) === []) {
            echo $this->Translate('There is no selection of energy values for this device. They are available for the heat pump (hmu), the controller (ctl…, bas…) and the indoor unit (vwz…).');
            return;
        }
        if ($messages === []) {
            echo $this->Translate('Request the message list from the adapter first: only messages the device offers are added.');
            return;
        }
        $wanted = $this->energyPreset($circuit, $messages);

        $intervals = $this->pollIntervals();
        $added = 0;
        $missing = 0;
        foreach ($wanted as $name => $interval) {
            if (isset($intervals[$name])) {
                continue;
            }
            if (!isset($messages[$name]) || !empty($messages[$name]['passive'])) {
                $missing++; // das Gerät bietet die Nachricht nicht (zum Abfragen) an
                continue;
            }
            $intervals[$name] = $interval;
            $added++;
        }
        if ($added > 0) {
            $rows = [];
            foreach ($intervals as $name => $interval) {
                $rows[] = ['Name' => (string) $name, 'Interval' => $interval];
            }
            $this->UpdateFormField('PollMessages', 'values', (string) json_encode($rows));
        }
        echo sprintf($this->Translate('%d energy values added to the query list, %d are not offered by this device. Save with "Apply changes".'), $added, $missing);
    }

    /** Auswahl für das Gerät: Nachricht => Intervall. */
    private function energyPreset(string $circuit, array $messages): array
    {
        if (str_starts_with($circuit, 'hmu')) {
            $fine = isset($messages['StatElectricEnergySum']) && empty($messages['StatElectricEnergySum']['passive']);
            return ($fine ? self::$energyHeatPumpFine : self::$energyHeatPumpCoarse) + self::$energyHeatPump;
        }
        if (preg_match('/^(ctl|bas)/', $circuit) === 1) {
            return self::$energyController;
        }
        if (str_starts_with($circuit, 'vwz')) {
            return self::$energyIndoorUnit;
        }
        return [];
    }

    // ---- Berechnete Heizleistung und momentane Leistungszahl --------------------------------------

    private static string $heatingPowerIdent = 'CalcHeatingPower';
    private static string $currentCopIdent = 'CalcCop';
    /** Eingangswerte: je Rolle gilt die erste Nachricht, zu der es eine Variable gibt (Name => Faktor nach Watt). */
    private static array $heatingPowerInputs = [
        'flow'     => ['BuildingCircuitFlow' => 1.0],
        'supply'   => ['RunDataFlowTemp' => 1.0, 'FlowTemp' => 1.0],
        'return'   => ['RunDataReturnTemp' => 1.0],
        'electric' => ['RunDataElectricPowerConsumption' => 1.0, 'CurrentConsumedPower' => 1000.0]
    ];
    /** Wärmekapazität von Wasser in Wh je Liter und Kelvin. */
    private static float $waterHeatCapacity = 1.163;
    /** Unter dieser elektrischen Leistung (Watt) steht der Verdichter; eine Leistungszahl wäre dann Unsinn. */
    private static float $copMinElectricPower = 100.0;

    /**
     * Heizleistung in Watt = Volumenstrom (l/h) × 1,163 × (Vorlauf − Rücklauf). Die Wärmepumpe meldet
     * die abgegebene Leistung nicht selbst, und ihre „Umweltleistung“ blieb an einer aroTHERM plus
     * im Betrieb auf 0 stehen (gemessen 19.11.2026). Gerechnet wird bei jedem neuen Eingangswert mit
     * den jeweils letzten Werten; ist einer davon veraltet, bleibt der alte Stand stehen. Beim
     * Abtauen ist der Wert negativ.
     *
     * Die momentane Leistungszahl ist Heizleistung durch elektrische Leistungsaufnahme. Steht der
     * Verdichter (unter 100 W) oder ist die Heizleistung nicht positiv, ist sie 0.
     */
    private function updateHeatingPower(string $name): void
    {
        if (!$this->ReadPropertyBoolean('HeatingPower')) {
            return;
        }
        $isInput = false;
        foreach (self::$heatingPowerInputs as $names) {
            $isInput = $isInput || isset($names[$name]);
        }
        if (!$isInput) {
            return;
        }

        $values = $this->heatingPowerValues();
        if (!isset($values['flow'], $values['supply'], $values['return'])) {
            return; // ein Eingangswert fehlt (noch) oder ist veraltet
        }
        // + 0.0: aus -0.0 (Volumenstrom 0 bei negativer Spreizung) wird 0.0
        $power = round($values['flow'] * self::$waterHeatCapacity * ($values['supply'] - $values['return'])) + 0.0;
        $this->maintainCalculated(self::$heatingPowerIdent, true);
        $this->SetValue(self::$heatingPowerIdent, $power);

        if (!isset($values['electric'])) {
            return; // ohne frische elektrische Leistung bleibt die Leistungszahl stehen
        }
        $cop = ($values['electric'] >= self::$copMinElectricPower && $power > 0) ? round($power / $values['electric'], 2) : 0.0;
        $this->maintainCalculated(self::$currentCopIdent, true);
        $this->SetValue(self::$currentCopIdent, $cop);
    }

    /** Letzte Eingangswerte je Rolle in Watt bzw. l/h und °C; veraltete und fehlende fehlen im Ergebnis. */
    private function heatingPowerValues(): array
    {
        $intervals = $this->pollIntervals();
        $default = max(60, $this->ReadPropertyInteger('PollInterval'));
        $now = time();
        $values = [];
        foreach (self::$heatingPowerInputs as $role => $names) {
            foreach ($names as $candidate => $factor) {
                $vid = @IPS_GetObjectIDByIdent($this->makeIdent($candidate, ''), $this->InstanceID);
                if (!is_int($vid) || $vid <= 0) {
                    continue;
                }
                $every = (($intervals[$candidate] ?? 0) > 0) ? $intervals[$candidate] : $default;
                if ($now - (int) IPS_GetVariable($vid)['VariableUpdated'] <= 3 * $every) {
                    $values[$role] = (float) GetValue($vid) * $factor;
                }
                break; // die erste vorhandene Variable gilt, auch wenn sie veraltet ist
            }
        }
        return $values;
    }

    /** Entfernt die berechneten Variablen, wenn die Berechnung abgeschaltet ist. */
    private function maintainHeatingPower(): void
    {
        if (!$this->ReadPropertyBoolean('HeatingPower')) {
            $this->maintainCalculated(self::$heatingPowerIdent, false);
            $this->maintainCalculated(self::$currentCopIdent, false);
        }
    }

    /** Legt eine berechnete Variable an bzw. entfernt sie. */
    private function maintainCalculated(string $ident, bool $keep): void
    {
        $vid = @IPS_GetObjectIDByIdent($ident, $this->InstanceID);
        if ($keep === (is_int($vid) && $vid > 0)) {
            return;
        }
        [$caption, $suffix, $digits] = ($ident === self::$currentCopIdent)
            ? ['Current COP (calculated)', '', 1]
            : ['Heating power (calculated)', ' W', 0];
        $this->MaintainVariable($ident, $this->Translate($caption), VARIABLETYPE_FLOAT, [
            'PRESENTATION' => VARIABLE_PRESENTATION_VALUE_PRESENTATION,
            'SUFFIX'       => $suffix,
            'DIGITS'       => $digits
        ], 0, $keep);
    }
}
