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
        'CurrentYieldPower'    => 0
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
}
