# Vaillant eBUS-Nachrichten auf Deutsch

Erzeugt aus den Gerätedefinitionen von [john30/ebusd-configuration](https://github.com/john30/ebusd-configuration/tree/master/src/vaillant) (src/vaillant). Die deutschen Bezeichnungen sind eine eigene Übersetzung; wo die Vorlage keine Beschreibung hat, ist die Bedeutung aus dem Namen abgeleitet. Ob eine Nachricht an der eigenen Anlage tatsächlich Werte liefert, zeigt erst die Nachrichtenliste des Adapters.

Felder: Bei Nachrichten mit mehreren Werten steht jedes Feld mit Einheit bzw. Werteliste in eckigen Klammern.

## hmu: Wärmepumpe (aroTHERM, Adresse 08)

| Name | Bedeutung | Einheit / Werte |
|---|---|---|
| `State` | Wärmepumpe Zustand | Wert 1 [–]; Energie [kWh]; Ein/Aus [off=Aus, on=Ein]; Zustand [ready=Bereit, error=Fehler, heating=Heizen, cooling=Kühlen, heating_water=Warmwasser] |
| `YieldHcDay` | Umweltertrag Heizen heute | kWh |
| `YieldCoolDay` | Ertrag Kühlen heute | kWh |
| `YieldHwcDay` | Umweltertrag Warmwasser heute | kWh |
| `YieldHcMonth` | Umweltertrag Heizen diesen Monat | kWh |
| `CopHcMonth` | Leistungszahl (COP) Heizen diesen Monat |  |
| `YieldHc` | Umweltertrag Heizen gesamt | kWh |
| `CopHc` | Leistungszahl (COP) Heizen gesamt |  |
| `YieldHwcMonth` | Umweltertrag Warmwasser diesen Monat | kWh |
| `CopHwcMonth` | Leistungszahl (COP) Warmwasser diesen Monat |  |
| `YieldHwc` | Umweltertrag Warmwasser gesamt | kWh |
| `CopHwc` | Leistungszahl (COP) Warmwasser gesamt |  |
| `Hours` | Betriebsstunden gesamt | h |
| `HoursHc` | Betriebsstunden Heizen | h |
| `HoursCool` | Betriebsstunden Kühlen | h |
| `HoursHwc` | Betriebsstunden Warmwasser | h |
| `TargetFlowTemp` | Vorlauf-Solltemperatur | °C |
| `FlowTemp` | Vorlauftemperatur | °C |
| `EnergyIntegral` | Energieintegral | °min |
| `CurrentYieldPower` | Aktuelle Umweltwärmeleistung | kW |
| `CurrentConsumedPower` | Aktuelle elektrische Leistungsaufnahme | kW |
| `CurrentCompressorUtil` | Verdichter-Modulation | % |
| `AirIntakeTemp` | Lufteintrittstemperatur | °C |
| `TargetTempHc` | Heizkreis-Solltemperatur | °C |
| `SourceTempInput` | Wärmequelle Eintrittstemperatur | °C |
| `SourceTempOutput` | Wärmequelle Austrittstemperatur | °C |
| `BuildingCircuitFlow` | Volumenstrom Heizkreis | l/h |
| `FlowPressure` | Anlagendruck Heizkreis | bar |
| `SourcePressure` | Druck Quellenkreis | bar |
| `TotalEnergyUsage` | Energieverbrauch gesamt | kWh |
| `CompressorHours` | Verdichter Betriebsstunden | h |
| `CompressorStarts` | Verdichter Starts |  |
| `BuildingPumpHours` | Heizkreispumpe Betriebsstunden | h |
| `BuildingPumpStarts` | Heizkreispumpe Starts |  |
| `FourWayValveHours` | 4-Wege-Ventil Betriebsstunden | h |
| `FourWayValveSwitches` | 4-Wege-Ventil Schaltungen |  |
| `EEVSteps` | Expansionsventil Schritte |  |
| `FourWayValveSwitchingOperations` | 4-Wege-Ventil Schaltvorgänge |  |
| `Fan1Hours` | Lüfter 1 Betriebsstunden | h |
| `Fan1Starts` | Lüfter 1 Starts |  |
| `Fan2Hours` | Lüfter 2 Betriebsstunden | h |
| `Fan2Starts` | Lüfter 2 Starts |  |
| `HeatCurve` | Heizkurve |  |
| `SummerSwitchOffTemp` | Sommerabschalttemperatur | °C |
| `HcBivalencePoint` | Bivalenzpunkt Heizen | °C |
| `HwcBivalencePoint` | Bivalenzpunkt Warmwasser | °C |
| `MaxFlowTemp` | Maximale Vorlauftemperatur | °C |
| `MinFlowTemp` | Minimale Vorlauftemperatur | °C |
| `HcModeActive` | Heizbetrieb aktiv | no=Nein, yes=Ja |
| `HwcModeActive` | Warmwasserbetrieb aktiv | no=Nein, yes=Ja |
| `HwcChargeHysteresis` | Hysterese Speicherladung | K |
| `ImmersionHeaterMode` | Heizstab-Betrieb | off=Aus, on=Ein |
| `CompressorHysteresisHc` | Verdichter-Hysterese Heizen | K |
| `CompressorHysteresisCooling` | Verdichter-Hysterese Kühlen | K |
| `HwcMode` | Warmwasser-Modus (Eco/Normal/Balance) | eco=Eco, normal=Normal, balance=Balance |
| `TargetTempHwc` | Warmwasser-Solltemperatur | °C |
| `YieldTotal` | Umweltertrag gesamt | kWh |
| `ConsumptionTotal` | Stromverbrauch gesamt | kWh |
| `YieldThisYear1` | Umweltertrag dieses Jahr, Monat 1 | Monat [–]; Energie [kWh] |
| `YieldThisYear2` | Umweltertrag dieses Jahr, Monat 2 | Monat [–]; Energie [kWh] |
| `YieldThisYear3` | Umweltertrag dieses Jahr, Monat 3 | Monat [–]; Energie [kWh] |
| `YieldThisYear4` | Umweltertrag dieses Jahr, Monat 4 | Monat [–]; Energie [kWh] |
| `YieldThisYear5` | Umweltertrag dieses Jahr, Monat 5 | Monat [–]; Energie [kWh] |
| `YieldThisYear6` | Umweltertrag dieses Jahr, Monat 6 | Monat [–]; Energie [kWh] |
| `YieldThisYear7` | Umweltertrag dieses Jahr, Monat 7 | Monat [–]; Energie [kWh] |
| `YieldThisYear8` | Umweltertrag dieses Jahr, Monat 8 | Monat [–]; Energie [kWh] |
| `YieldThisYear9` | Umweltertrag dieses Jahr, Monat 9 | Monat [–]; Energie [kWh] |
| `YieldThisYear10` | Umweltertrag dieses Jahr, Monat 10 | Monat [–]; Energie [kWh] |
| `YieldThisYear11` | Umweltertrag dieses Jahr, Monat 11 | Monat [–]; Energie [kWh] |
| `YieldThisYear12` | Umweltertrag dieses Jahr, Monat 12 | Monat [–]; Energie [kWh] |
| `ConsumptionThisYear1` | Stromverbrauch dieses Jahr, Monat 1 | Monat [–]; Energie [kWh] |
| `ConsumptionThisYear2` | Stromverbrauch dieses Jahr, Monat 2 | Monat [–]; Energie [kWh] |
| `ConsumptionThisYear3` | Stromverbrauch dieses Jahr, Monat 3 | Monat [–]; Energie [kWh] |
| `ConsumptionThisYear4` | Stromverbrauch dieses Jahr, Monat 4 | Monat [–]; Energie [kWh] |
| `ConsumptionThisYear5` | Stromverbrauch dieses Jahr, Monat 5 | Monat [–]; Energie [kWh] |
| `ConsumptionThisYear6` | Stromverbrauch dieses Jahr, Monat 6 | Monat [–]; Energie [kWh] |
| `ConsumptionThisYear7` | Stromverbrauch dieses Jahr, Monat 7 | Monat [–]; Energie [kWh] |
| `ConsumptionThisYear8` | Stromverbrauch dieses Jahr, Monat 8 | Monat [–]; Energie [kWh] |
| `ConsumptionThisYear9` | Stromverbrauch dieses Jahr, Monat 9 | Monat [–]; Energie [kWh] |
| `ConsumptionThisYear10` | Stromverbrauch dieses Jahr, Monat 10 | Monat [–]; Energie [kWh] |
| `ConsumptionThisYear11` | Stromverbrauch dieses Jahr, Monat 11 | Monat [–]; Energie [kWh] |
| `ConsumptionThisYear12` | Stromverbrauch dieses Jahr, Monat 12 | Monat [–]; Energie [kWh] |
| `EnableTestEEVPosition` | TestmenÃ¼ freischalten: Position Expansionsventil |  |
| `TestEEVPosition` | Position Expansionsventil (Test T.0.21) | % |
| `EnableTestCompressorExitTemp` | TestmenÃ¼ freischalten: Verdichter-Austrittstemperatur |  |
| `TestCompressorExitTemp` | Verdichter-Austrittstemperatur (Test T.0.55) | °C |
| `EnableTestCompressorIntakeTemp` | Testmenü freischalten: Verdichter-Eintrittstemperatur |  |
| `TestCompressorInletTemp` | Verdichter-Eintrittstemperatur (Test T.0.56) | °C |
| `EnableTestWaterThroughput` | TestmenÃ¼ freischalten: Heizwasser-Volumenstrom |  |
| `TestWaterThroughput` | Heizwasser-Volumenstrom (Test T.0.45) | l/h |
| `EnableTestCompressorSpeed` | TestmenÃ¼ freischalten: Verdichterdrehzahl |  |
| `TestCompressorSpeed` | Verdichterdrehzahl (Test T.0.93) | U/s |
| `TestBuildingPumpPower` | Leistung Heizkreispumpe (Test T.0.01) | % |
| `TestFan1` | Lüfter 1 (Test T.0.02) | % |
| `TestFan2` | Lüfter 2 (Test T.0.02) | % |
| `TestCondensateTrayHeater` | Kondensatwannenheizung (Test T.0.19) | off=Aus, on=Ein |
| `TestFourWayValve` | 4-Wege-Ventil (Test T.0.20) | off=Aus, on=Ein |
| `TestHeatingCoilCompressor` | Kurbelwannenheizung Verdichter (Test T.0.23) | off=Aus, on=Ein |
| `EnableTestFlowTemp` | TestmenÃ¼ freischalten: Vorlauftemperatur |  |
| `TestFlowTemp` | Vorlauftemperatur (Test T.0.40) | °C |
| `EnableTestReturnTemp` | TestmenÃ¼ freischalten: Rücklauftemperatur |  |
| `TestReturnTemp` | Rücklauftemperatur (Test T.0.41) | °C |
| `TestBuildingWaterPress` | Wasserdruck Heizkreis (Test T.0.42) | bar |
| `TestAirIntakeTemp` | Lufteintrittstemperatur (Test T.0.48) | °C |
| `TestEEVOutletTemp` | Temperatur nach Expansionsventil (Test T.0.57) | °C |
| `TestCondensorOutletTemp` | Verflüssiger-Austrittstemperatur (Test T.0.59) | °C |
| `EnableTestHighPress` | TestmenÃ¼ freischalten: Hochdruck |  |
| `TestHighPress` | Hochdruck (Test T.0.63) | bar |
| `TestLowPress` | Niederdruck (Test T.0.64) | bar |
| `TestHighPressureSwitch` | Hochdruckschalter (Test T.0.67) | open=offen, close=geschlossen |
| `EnableTestEvaporationTemp` | TestmenÃ¼ freischalten: Verdampfungstemperatur |  |
| `TestEvaporationTemp` | Verdampfungstemperatur (Test T.0.85) | °C |
| `EnableTestCondensateTemp` | TestmenÃ¼ freischalten: Kondensationstemperatur |  |
| `TestCondensateTemp` | Kondensationstemperatur (Test T.0.86) | °C |
| `TestOverheatingTargetValue` | Überhitzung Sollwert (Test T.0.87) | K |
| `TestOverheatingActualValue` | Überhitzung Istwert (Test T.0.88) | K |
| `TestSubcoolingTargetValue` | Unterkühlung Sollwert (Test T.0.89) | K |
| `TestSubcoolingActualValue` | Unterkühlung Istwert (Test T.0.90) | K |
| `TestTempSwitchCompressorOutlet` | Temperaturschalter Verdichteraustritt (Test T.0.123) | open=offen, close=geschlossen |
| `TestPrioritySwitchingValve` | Vorrang-Umschaltventil Warmwasser (Test T.1.02) | heat=Heizen, water=Warmwasser |
| `TestCylinderTemp` | Speichertemperatur (Test T.1.44) | °C |
| `TestLockoutContactS20` | Sperrkontakt S20 (Test T.1.46) | close=geschlossen, open=offen |
| `TestOutdoorTemp` | Außentemperatur (Testmenü) | °C |
| `TestSystemTemp` | Systemtemperatur (Test T.1.70) | °C |
| `TestDCFStatus` | DCF-Empfang (Test T.1.71) | nosignal=kein Signal, ok=OK, sync=synchronisiert, valid=gültig |
| `TestLockoutContactS21` | Sperrkontakt S21 (Test T.1.72) | close=geschlossen, open=offen |
| `TestMA1Output` | Multifunktionsausgang MA1 (Test T.1.119) | off=Aus, on=Ein |
| `TestImmersionHeaterSafetyCutOut` | Sicherheitstemperaturbegrenzer Heizstab (Test T.1.124) | close=geschlossen, open=offen |
| `TestMIInput` | Multifunktionseingang ME (Test T.1.125) |  |
| `TestMO2Output` | Multifunktionsausgang MA2 (Test T.1.126) | off=Aus, on=Ein |
| `TestMO3Output` | Multifunktionsausgang MA3 (Test T.1.127) | off=Aus, on=Ein |
| `TestCondensorInletTemp` | Verflüssiger-Eintrittstemperatur (Testmenü) | °C |
| `TestFlowTempImmersionHeater` | Vorlauftemperatur Heizstab (Test T.1.130) | °C |
| `EnableTestEEVTemp` | TestmenÃ¼ freischalten: Temperatur Expansionsventil |  |
| `TestEEVTemp` | Temperatur Expansionsventil (Testmenü) | °C |
| `EnableiveMonitorMain` | Live-Monitor freischalten |  |
| `LiveMonitorMain` | Live-Monitor Status | Status [Controller_starting=Regler startet, Standby=Standby, Supply_on=Versorgung ein, Heating_Flow_ok=Heizen: Durchfluss OK, Heating_temperature_self_test=Heizen: T…]; Funktion [Heating=Heizen, Cooling=Kühlen] |
| `LiveMonitorDesiredSupplyTemp` | Live-Monitor Vorlauf-Solltemperatur | °C |
| `LiveMonitorCurrentSupplyTemp` | Live-Monitor Vorlauftemperatur | °C |
| `LiveMonitorCurrentYieldPower` | Live-Monitor Umweltwärmeleistung | kW |
| `LiveMonitorCurrentConsumedPower` | Live-Monitor elektrische Leistungsaufnahme | kW |
| `LiveMonitorCurrentCompressorUtil` | Live-Monitor Verdichter-Modulation | % |
| `LiveMonitorAirIntakeTemp` | Live-Monitor Lufteintrittstemperatur | °C |
| `StatCompressorHours` | Statistik Verdichter Betriebsstunden | h |
| `StatCompressorStarts` | Statistik Verdichter Starts |  |
| `StatBuildingPumpHours` | Statistik Heizkreispumpe Betriebsstunden | h |
| `StatBuildingPumpStarts` | Statistik Heizkreispumpe Starts |  |
| `StatFourWayValveHours` | Statistik 4-Wege-Ventil Betriebsstunden | h |
| `StatFourWayValveSwitchingOperations` | Statistik 4-Wege-Ventil Schaltvorgänge |  |
| `StatFan1Hours` | Statistik Lüfter 1 Betriebsstunden | h |
| `StatFan1Starts` | Statistik Lüfter 1 Starts |  |
| `StatEEVSteps` | Statistik Expansionsventil Schritte |  |
| `RunDataCompressorSpeed` | Verdichterdrehzahl | rps |
| `RunDataEEVPositionAbs` | Expansionsventil Position (absolut) |  |
| `RunDataFan1Speed` | Lüfter 1 Drehzahl | rpm |
| `RunDataFan2Speed` | Lüfter 2 Drehzahl | rpm |
| `RunDataStatuscode` | Statuscode Wärmepumpe | frost_protect=Frostschutz, standby=Standby, heat_compressor_shutdown=Heizen: Verdichter abgeschaltet, heat_compressor_blocked=Heizen: Verdichter gesperrt, he… |
| `RunDataElectricPowerConsumption` | Elektrische Leistungsaufnahme | W |
| `RunDataHighPressure` | Hochdruck Kältekreis | bar |
| `RunDataCompressorInletTemp` | Verdichter-Eintrittstemperatur (Sauggas) | °C |
| `RunDataCompressorOutletTemp` | Verdichter-Austrittstemperatur (Heißgas) | °C |
| `RunDataEEVOutletTemp` | Temperatur nach Expansionsventil | °C |
| `RunDataFlowTemp` | Vorlauftemperatur | °C |
| `RunDataReturnTemp` | Rücklauftemperatur | °C |
| `RunStatsHMUHours` | Betriebsstunden Wärmepumpe | h |
| `RunStatsHcHours` | Betriebsstunden Heizen | h |
| `RunStatsHwcHours` | Betriebsstunden Warmwasser | h |
| `RunStatsCompressorHours` | Verdichter Betriebsstunden | h |
| `RunStatsCompressorStarts` | Verdichter Starts |  |
| `RunStatsBuildingPumpHours` | Heizkreispumpe Betriebsstunden | h |
| `RunDataBuildingCPumpPower` | Leistung Heizkreispumpe | % |
| `RunStatsBuildingCPumpStarts` | Heizkreispumpe Starts |  |
| `RunStats4PortValveHours` | 4-Wege-Ventil Betriebsstunden | h |
| `RunStats4PortValveSwitches` | 4-Wege-Ventil Schaltungen |  |
| `RunStatsFan1Hours` | Lüfter 1 Betriebsstunden | h |
| `RunStatsFan1Starts` | Lüfter 1 Starts |  |
| `RunStatsFan2Hours` | Lüfter 2 Betriebsstunden | h |
| `RunStatsFan2Starts` | Lüfter 2 Starts |  |
| `RunDataAirInletTemp` | Lufteintrittstemperatur | °C |
| `RunStatsCompressorHc` | Verdichter im Heizbetrieb | Laufzeit [min]; Starts [–] |
| `RunStatsCompressorHwc` | Verdichter im Warmwasserbetrieb | Laufzeit [min]; Starts [–] |
| `DateTime` | Datum und Uhrzeit | DCF-Empfang [nosignal=kein Signal, ok=OK, sync=synchronisiert, valid=gültig]; Uhrzeit [–]; Datum [–]; Außentemperatur [°C] |
| `Status16` | Außentemperatur (Status 16) | °C |
| `SetMode` | Betriebsvorgabe | Heizmodus [auto=Automatik, off=Aus, heat=Heizen, water=Warmwasser]; Vorlauf-Solltemperatur [°C]; Warmwasser-Solltemperatur [°C]; Warmwasser-Vorlauf-Solltemperatur [°C]; Heizen gesperrt [–]; Warmwasserzapfung gesperrt [–]; Speicherladung gesperrt [–]; Heizkreispumpe fernsteuern [–]; Zusatzheizung freigegeben [–]; Kühlen freigegeben [–] |
| `Status01` | Status 01 (Temperaturen) | Vorlauftemperatur [°C]; Rücklauftemperatur [°C]; Außentemperatur [°C]; Warmwassertemperatur [°C]; Speichertemperatur [°C]; Pumpenstatus [off=Aus, on=Ein, overrun=Nachlauf, hwc=Warmwasser] |
| `Status02` | Status 02 (Warmwasser) | Warmwasser-Betriebsart [disabled=deaktiviert, on=Ein, off=Aus, auto=Automatik]; Maximaltemperatur [°C]; Ist-Temperatur [°C]; Maximaltemperatur 2 [°C]; Ist-Temperatur 2 [°C] |
| `Status` | Status | Temperatur [°C]; Druck [bar]; Druck 2 [bar]; Heizmodus [off=Aus, cooling=Kühlen, heat=Heizen, water=Warmwasser] |
| `StatusCirPump` | Zirkulationspumpe | off=Aus, on=Ein |
| `Currenterror` | Aktuelle Fehler | Fehler 1 [–]; Fehler 2 [–]; Fehler 3 [–]; Fehler 4 [–]; Fehler 5 [–] |
| `Errorhistory` | Fehlerhistorie | Status [–]; Uhrzeit [–]; Datum [–]; Fehler 1 [–] |
| `Clearerrorhistory` | Fehlerhistorie löschen | no=Nein, yes=Ja |
| `StatSolarEnergySum` | Solarertrag gesamt (Statistik) | kWh |
| `StatSolarEnergySumHc` | Solarertrag Heizen (Statistik) | kWh |
| `StatSolarEnergySumHwc` | Solarertrag Warmwasser (Statistik) | kWh |
| `StatEnvironmentEnergySum` | Umweltenergie gesamt (Statistik) | kWh |
| `StatEnvironmentEnergySumHc` | Umweltenergie Heizen (Statistik) | kWh |
| `StatEnvironmentEnergySumHwc` | Umweltenergie Warmwasser (Statistik) | kWh |
| `StatEnvironmentEnergySumCool` | Umweltenergie Kühlen (Statistik) | kWh |
| `StatElectricEnergySum` | Stromverbrauch gesamt (Statistik) | kWh |
| `StatElectricEnergySumHc` | Stromverbrauch Heizen (Statistik) | kWh |
| `StatElectricEnergySumHwc` | Stromverbrauch Warmwasser (Statistik) | kWh |
| `StatElectricEnergySumCool` | Stromverbrauch Kühlen (Statistik) | kWh |
| `StatFuelSum` | Brennstoffverbrauch gesamt (Statistik) | kWh |
| `StatFuelSumHc` | Brennstoffverbrauch Heizen (Statistik) | kWh |
| `StatFuelSumHwc` | Brennstoffverbrauch Warmwasser (Statistik) | kWh |
| `CompressorHc` | Verdichter Heizen (Laufzeit/Starts) | Laufzeit [min]; Starts [–] |
| `CompressorHwc` | Verdichter Warmwasser (Laufzeit/Starts) | Laufzeit [min]; Starts [–] |
| `CompressorBlocktime` | Verdichter-Sperrzeit | min |
| `SupplyTempWeighted` | Gewichtete Vorlauftemperatur | °C |
| `YieldCoolingMonth` | Ertrag Kühlen diesen Monat | kWh |
| `CopCoolingMonth` | Leistungszahl (COP) Kühlen diesen Monat |  |
| `YieldCooling` | Ertrag Kühlen gesamt | kWh |
| `CopCooling` | Leistungszahl (COP) Kühlen gesamt |  |
| `PowerConsumptionHmu` | Leistungsaufnahme Wärmepumpe | kW |

## ctlv3: Regler sensoCOMFORT VRC 720 (Adresse 15)

| Name | Bedeutung | Einheit / Werte |
|---|---|---|
| `HcBivalencePoint` | Bivalenzpunkt Heizen | °C |
| `HwcBivalencePoint` | Bivalenzpunkt Warmwasser | °C |
| `YieldTotal` | Umweltertrag gesamt | kWh |
| `Currenterror` | Aktuelle Fehler | Fehler 1 [–]; Fehler 2 [–]; Fehler 3 [–]; Fehler 4 [–]; Fehler 5 [–] |
| `Errorhistory` | Fehlerhistorie | Status [=]; Uhrzeit [–]; Datum [–]; Fehler 1 [–] |
| `Clearerrorhistory` | Fehlerhistorie löschen | no=Nein, yes=Ja |
| `StatSolarEnergySum` | Solarertrag gesamt (Statistik) | kWh |
| `StatSolarEnergySumHc` | Solarertrag Heizen (Statistik) | kWh |
| `StatSolarEnergySumHwc` | Solarertrag Warmwasser (Statistik) | kWh |
| `StatEnvironmentEnergySum` | Umweltenergie gesamt (Statistik) | kWh |
| `StatEnvironmentEnergySumHc` | Umweltenergie Heizen (Statistik) | kWh |
| `StatEnvironmentEnergySumHwc` | Umweltenergie Warmwasser (Statistik) | kWh |
| `StatEnvironmentEnergySumCool` | Umweltenergie Kühlen (Statistik) | kWh |
| `StatElectricEnergySum` | Stromverbrauch gesamt (Statistik) | kWh |
| `StatElectricEnergySumHc` | Stromverbrauch Heizen (Statistik) | kWh |
| `StatElectricEnergySumHwc` | Stromverbrauch Warmwasser (Statistik) | kWh |
| `StatElectricEnergySumCool` | Stromverbrauch Kühlen (Statistik) | kWh |
| `StatFuelSum` | Brennstoffverbrauch gesamt (Statistik) | kWh |
| `StatFuelSumHc` | Brennstoffverbrauch Heizen (Statistik) | kWh |
| `StatFuelSumHwc` | Brennstoffverbrauch Warmwasser (Statistik) | kWh |
| `ContinuousHeating` | Dauerheizen unterhalb Außentemperatur | °C |
| `FrostOverrideTime` | Frostschutz-Verzögerung | h |
| `GlobalSystemOff` | System komplett aus | no=Nein, yes=Ja |
| `HwcParallelLoading` | Warmwasser-Parallelladung | off=Aus, on=Ein |
| `MaxRoomHumidity` | Maximale Raumluftfeuchte | % |
| `HybridManager` | Hybridmanager | trivalence=trivalent, bivalence=bivalent |
| `TariffAuxHeater` | Tarif Zusatzheizung |  |
| `TariffPriHeaterLow` | Tarif Wärmepumpe Niedertarif |  |
| `TariffPriHeaterHigh` | Tarif Wärmepumpe Hochtarif |  |
| `AdaptHeatCurve` | Automatische Heizkurvenanpassung | no=Nein, yes=Ja |
| `MaxCylinderChargeTime` | Maximale Speicherladezeit | min |
| `HwcLockTime` | Warmwasser-Sperrzeit | min |
| `SolarFlowRate` | Solar-Volumenstrom | l/min |
| `PumpAdditionalTime` | Pumpennachlaufzeit | min |
| `HcAlternativePoint` | Alternativpunkt Heizen | °C |
| `BackupBoiler` | Zusatzheizung | off=Aus, heat=Heizen, water=Warmwasser, heatandwater=Heizen und Warmwasser |
| `HcEmergencyTemp` | Notbetrieb-Temperatur Heizen | °C |
| `CylinderChargeHyst` | Hysterese Speicherladung | K |
| `CylinderChargeOffset` | Offset Speicherladung | K |
| `HwcLegionellaTime` | Legionellenschutz Uhrzeit |  |
| `HwcLegionellaDay` | Legionellenschutz Tag | off=Aus, Mo=Montag, Tu=Dienstag, We=Mittwoch, Th=Donnerstag, Fr=Freitag, Sa=Samstag, Su=Sonntag, daily=täglich |
| `MaintenanceDate` | Nächste Wartung |  |
| `OffsetOutsideTemp` | Korrektur Außentemperatur | K |
| `FM5Config` | Konfiguration VR 71 / FM5 |  |
| `Date` | Datum |  |
| `Time` | Uhrzeit |  |
| `HydraulicScheme` | Hydraulikschema |  |
| `CoolingOutsideTempThreshold` | Kühlen ab Außentemperatur | °C |
| `WaterPressure` | Anlagendruck | bar |
| `DewPointOffset` | Taupunkt-Offset | °C |
| `SolarYieldTotal` | Solarertrag gesamt | kWh |
| `ESCO` | ESCO-Sperrfunktion | heatpump=Wärmepumpe, backup=Zusatzheizung, heatpumpandbackup=Wärmepumpe und Zusatzheizung, heat=Heizen, cool=Kühlen, heatandcool=Heizen und Kühlen |
| `HwcFlowTempMax` | Maximale Vorlauftemperatur Warmwasser | °C |
| `SystemFlowTemp` | Systemvorlauftemperatur | °C |
| `MultiRelaySetting` | Multifunktionsrelais | circulation=Zirkulationspumpe, dryer=Entfeuchter, zone=Zonenventil, legiopump=Legionellenpumpe, not_connected=nicht angeschlossen, cool=Kühlen |
| `PrFuelSumHcThisMonth` | Brennstoffverbrauch Heizen diesen Monat | kWh |
| `PrEnergySumHcThisMonth` | Energieverbrauch Heizen diesen Monat | kWh |
| `PrEnergySumHwcThisMonth` | Energieverbrauch Warmwasser diesen Monat | kWh |
| `PrFuelSumHwcThisMonth` | Brennstoffverbrauch Warmwasser diesen Monat | kWh |
| `PrFuelSumHcLastMonth` | Brennstoffverbrauch Heizen letzten Monat | kWh |
| `PrEnergySumHcLastMonth` | Energieverbrauch Heizen letzten Monat | kWh |
| `PrEnergySumHwcLastMonth` | Energieverbrauch Warmwasser letzten Monat | kWh |
| `PrFuelSumHwcLastMonth` | Brennstoffverbrauch Warmwasser letzten Monat | kWh |
| `PrFuelSumHc` | Brennstoffverbrauch Heizen gesamt | kWh |
| `PrEnergySumHc` | Energieverbrauch Heizen gesamt | kWh |
| `PrEnergySumHwc` | Energieverbrauch Warmwasser gesamt | kWh |
| `PrFuelSumHwc` | Brennstoffverbrauch Warmwasser gesamt | kWh |
| `MultiInputSetting` | Multifunktionseingang | not_connected=nicht angeschlossen, circulation=Zirkulation, photovoltaic=Photovoltaik, external_cooling=externes Kühlen |
| `Installer1` | Fachhandwerker Name (Teil 1) |  |
| `Installer2` | Fachhandwerker Name (Teil 2) |  |
| `PhoneNumber1` | Telefonnummer (Teil 1) |  |
| `PhoneNumber2` | Telefonnummer (Teil 2) |  |
| `OutsideTemp` | Außentemperatur | °C |
| `KeyCodeforConfigMenu` | Code Fachhandwerkerebene |  |
| `OutsideTempAvg` | Außentemperatur gemittelt | °C |
| `MaintenanceDue` | Wartung fällig | no=Nein, yes=Ja |
| `GreenIQ` | green iQ | off=Aus, on=Ein |
| `HwcStorageTempTop` | Warmwasserspeicher oben | °C |
| `HwcStorageTempBottom` | Warmwasserspeicher unten | °C |
| `HcStorageTempTop` | Pufferspeicher oben | °C |
| `HcStorageTempBottom` | Pufferspeicher unten | °C |
| `BufferChargeOffset` | Offset Pufferladung | K |
| `HwcEnabled` | Warmwasser aktiviert | no=Nein, yes=Ja |
| `HwcStatus` | Warmwasser-Status | auto=Automatik, holidayaway=Urlaub, load=Schnellladung, off=Aus |
| `HwcCircPumpStatus` | Zirkulationspumpe | off=Aus, on=Ein |
| `HwcOpMode` | Warmwasser-Betriebsart | off=Aus, auto=Zeitgesteuert, manual=Manuell |
| `HwcTempDesired` | Warmwasser-Solltemperatur | °C |
| `HwcStorageTemp` | Warmwasser-Speichertemperatur | °C |
| `HwcReheatingActive` | Warmwasser-Nachladung aktiv | no=Nein, yes=Ja |
| `HwcFlowTemp` | Warmwasser-Vorlauf-Solltemperatur | °C |
| `HwcHolidayStartPeriod` | Warmwasser Urlaub Beginn |  |
| `HwcHolidayEndPeriod` | Warmwasser Urlaub Ende |  |
| `HwcSFMode` | Warmwasser Sonderfunktion | auto=keine, load=Schnellladung |
| `HwcHolidayStartTime` | Warmwasser Urlaub Beginn Uhrzeit |  |
| `HwcHolidayEndTime` | Warmwasser Urlaub Ende Uhrzeit |  |
| `Hc1CircuitType` | Heizkreis 1 Kreistyp | inactive=inaktiv, heat=Heizkreis, fixed=Festwert, water=Warmwasser, returnincr=Rücklaufanhebung |
| `Hc1DesiredReturnTemp` | Heizkreis 1 Rücklauf-Solltemperatur | °C |
| `Hc1DewPointMonitoring` | Heizkreis 1 Taupunktüberwachung | off=Aus, on=Ein |
| `Hc1CoolingEnabled` | Heizkreis 1 Kühlen freigegeben | off=Aus, on=Ein |
| `Hc1FlowTempDesired` | Heizkreis 1 Vorlauf-Solltemperatur | °C |
| `Hc1FlowTemp` | Heizkreis 1 Vorlauftemperatur | °C |
| `Hc1ExtHwcTempDesired` | Heizkreis 1 externes Warmwasser Solltemperatur | °C |
| `Hc1DewPointOffset` | Heizkreis 1 Taupunkt-Offset | K |
| `Hc1ExcessTemp` | Heizkreis 1 Überhöhung | K |
| `Hc1FixedDesiredTemp` | Heizkreis 1 Festwert-Solltemperatur | °C |
| `Hc1SetbackModeTemp` | Heizkreis 1 Absenktemperatur (Festwert) | °C |
| `Hc1SetbackMode` | Heizkreis 1 Absenkmodus | eco=Eco (aus), normal=Normal (Absenken) |
| `Hc1HeatCurve` | Heizkreis 1 Heizkurve |  |
| `Hc1HeatingFlowTempMax` | Heizkreis 1 maximale Vorlauftemperatur Heizen | °C |
| `Hc1CoolingFlowTempMin` | Heizkreis 1 minimale Vorlauftemperatur Kühlen | °C |
| `Hc1HeatingFlowTempMin` | Heizkreis 1 minimale Vorlauftemperatur Heizen | °C |
| `Hc1ExtHwcOpMode` | Heizkreis 1 externes Warmwasser Betriebsart | off=Aus, auto=Zeitgesteuert, manual=Manuell |
| `Hc1SummerTempLimit` | Heizkreis 1 Sommerabschaltung ab Außentemperatur | °C |
| `Hc1RoomTempModulation` | Heizkreis 1 Raumaufschaltung | inactive=inaktiv, active=aktiv, expanded=erweitert |
| `Hc1ScreedDryingDay` | Heizkreis 1 Estrichtrocknung Tag |  |
| `Hc1ScreedDryingDesiredTemp` | Heizkreis 1 Estrichtrocknung Solltemperatur | °C |
| `Hc1ExtHwcActive` | Heizkreis 1 externes Warmwasser aktiv | off=Aus, on=Ein |
| `Hc1ExtHeatDemand` | Heizkreis 1 externe Wärmeanforderung | off=Aus, on=Ein |
| `Hc1MixerMovement` | Heizkreis 1 Mischerbewegung |  |
| `Hc1HeatCurveAdaption` | Heizkreis 1 Heizkurvenanpassung |  |
| `Hc1Status` | Heizkreis 1 Status | off=Aus, on=Ein |
| `Hc1PumpStatus` | Heizkreis 1 Pumpe | off=Aus, heat=Heizen, cool=Kühlen, exthwc=externes Warmwasser |
| `Hc2CircuitType` | Heizkreis 2 Kreistyp | inactive=inaktiv, heat=Heizkreis, fixed=Festwert, water=Warmwasser, returnincr=Rücklaufanhebung |
| `Hc2DesiredReturnTemp` | Heizkreis 2 Rücklauf-Solltemperatur | °C |
| `Hc2DewPointMonitoring` | Heizkreis 2 Taupunktüberwachung | off=Aus, on=Ein |
| `Hc2CoolingEnabled` | Heizkreis 2 Kühlen freigegeben | off=Aus, on=Ein |
| `Hc2FlowTempDesired` | Heizkreis 2 Vorlauf-Solltemperatur | °C |
| `Hc2FlowTemp` | Heizkreis 2 Vorlauftemperatur | °C |
| `Hc2ExtHwcTempDesired` | Heizkreis 2 externes Warmwasser Solltemperatur | °C |
| `Hc2DewPointOffset` | Heizkreis 2 Taupunkt-Offset | K |
| `Hc2ExcessTemp` | Heizkreis 2 Überhöhung | K |
| `Hc2FixedDesiredTemp` | Heizkreis 2 Festwert-Solltemperatur | °C |
| `Hc2SetbackModeTemp` | Heizkreis 2 Absenktemperatur (Festwert) | °C |
| `Hc2SetbackMode` | Heizkreis 2 Absenkmodus | eco=Eco (aus), normal=Normal (Absenken) |
| `Hc2HeatCurve` | Heizkreis 2 Heizkurve |  |
| `Hc2HeatingFlowTempMax` | Heizkreis 2 maximale Vorlauftemperatur Heizen | °C |
| `Hc2CoolingFlowTempMin` | Heizkreis 2 minimale Vorlauftemperatur Kühlen | °C |
| `Hc2HeatingFlowTempMin` | Heizkreis 2 minimale Vorlauftemperatur Heizen | °C |
| `Hc2ExtHwcOpMode` | Heizkreis 2 externes Warmwasser Betriebsart | off=Aus, auto=Zeitgesteuert, manual=Manuell |
| `Hc2SummerTempLimit` | Heizkreis 2 Sommerabschaltung ab Außentemperatur | °C |
| `Hc2RoomTempModulation` | Heizkreis 2 Raumaufschaltung | inactive=inaktiv, active=aktiv, expanded=erweitert |
| `Hc2ScreedDryingDay` | Heizkreis 2 Estrichtrocknung Tag |  |
| `Hc2ScreedDryingDesiredTemp` | Heizkreis 2 Estrichtrocknung Solltemperatur | °C |
| `Hc2ExtHwcActive` | Heizkreis 2 externes Warmwasser aktiv | off=Aus, on=Ein |
| `Hc2ExtHeatDemand` | Heizkreis 2 externe Wärmeanforderung | off=Aus, on=Ein |
| `Hc2MixerMovement` | Heizkreis 2 Mischerbewegung |  |
| `Hc2HeatCurveAdaption` | Heizkreis 2 Heizkurvenanpassung |  |
| `Hc2Status` | Heizkreis 2 Status | off=Aus, on=Ein |
| `Hc2PumpStatus` | Heizkreis 2 Pumpe | off=Aus, heat=Heizen, cool=Kühlen, exthwc=externes Warmwasser |
| `Hc3CircuitType` | Heizkreis 3 Kreistyp | inactive=inaktiv, heat=Heizkreis, fixed=Festwert, water=Warmwasser, returnincr=Rücklaufanhebung |
| `Hc3DesiredReturnTemp` | Heizkreis 3 Rücklauf-Solltemperatur | °C |
| `Hc3DewPointMonitoring` | Heizkreis 3 Taupunktüberwachung | off=Aus, on=Ein |
| `Hc3CoolingEnabled` | Heizkreis 3 Kühlen freigegeben | off=Aus, on=Ein |
| `Hc3FlowTempDesired` | Heizkreis 3 Vorlauf-Solltemperatur | °C |
| `Hc3FlowTemp` | Heizkreis 3 Vorlauftemperatur | °C |
| `Hc3ExtHwcTempDesired` | Heizkreis 3 externes Warmwasser Solltemperatur | °C |
| `Hc3DewPointOffset` | Heizkreis 3 Taupunkt-Offset | K |
| `Hc3ExcessTemp` | Heizkreis 3 Überhöhung | K |
| `Hc3FixedDesiredTemp` | Heizkreis 3 Festwert-Solltemperatur | °C |
| `Hc3SetbackModeTemp` | Heizkreis 3 Absenktemperatur (Festwert) | °C |
| `Hc3SetbackMode` | Heizkreis 3 Absenkmodus | eco=Eco (aus), normal=Normal (Absenken) |
| `Hc3HeatCurve` | Heizkreis 3 Heizkurve |  |
| `Hc3HeatingFlowTempMax` | Heizkreis 3 maximale Vorlauftemperatur Heizen | °C |
| `Hc3CoolingFlowTempMin` | Heizkreis 3 minimale Vorlauftemperatur Kühlen | °C |
| `Hc3HeatingFlowTempMin` | Heizkreis 3 minimale Vorlauftemperatur Heizen | °C |
| `Hc3ExtHwcOpMode` | Heizkreis 3 externes Warmwasser Betriebsart | off=Aus, auto=Zeitgesteuert, manual=Manuell |
| `Hc3SummerTempLimit` | Heizkreis 3 Sommerabschaltung ab Außentemperatur | °C |
| `Hc3RoomTempModulation` | Heizkreis 3 Raumaufschaltung | inactive=inaktiv, active=aktiv, expanded=erweitert |
| `Hc3ScreedDryingDay` | Heizkreis 3 Estrichtrocknung Tag |  |
| `Hc3ScreedDryingDesiredTemp` | Heizkreis 3 Estrichtrocknung Solltemperatur | °C |
| `Hc3ExtHwcActive` | Heizkreis 3 externes Warmwasser aktiv | off=Aus, on=Ein |
| `Hc3ExtHeatDemand` | Heizkreis 3 externe Wärmeanforderung | off=Aus, on=Ein |
| `Hc3MixerMovement` | Heizkreis 3 Mischerbewegung |  |
| `Hc3HeatCurveAdaption` | Heizkreis 3 Heizkurvenanpassung |  |
| `Hc3Status` | Heizkreis 3 Status | off=Aus, on=Ein |
| `Hc3PumpStatus` | Heizkreis 3 Pumpe | off=Aus, heat=Heizen, cool=Kühlen, exthwc=externes Warmwasser |
| `Z1CoolingOpMode` | Zone 1 Betriebsart Kühlen | off=Aus, auto=Zeitgesteuert, manual=Manuell |
| `Z1CoolingSetbackTemp` | Zone 1 Absenktemperatur Kühlen | °C |
| `Z1HolidayStartPeriod` | Zone 1 Urlaub Beginn |  |
| `Z1HolidayEndPeriod` | Zone 1 Urlaub Ende |  |
| `Z1HolidayTemp` | Zone 1 Urlaubstemperatur | °C |
| `Z1OpMode` | Zone 1 Betriebsart Heizen | off=Aus, auto=Zeitgesteuert, manual=Manuell |
| `Z1QuickVetoTemp` | Zone 1 Quick-Veto Temperatur | °C |
| `Z1SetbackTemp` | Zone 1 Absenktemperatur Heizen | °C |
| `Z1SFMode` | Zone 1 Sonderfunktion | auto=keine, ventilation=Stoßlüften, veto=Quick-Veto |
| `Z1RoomTemp` | Zone 1 Raumtemperatur | °C |
| `Z1ValveStatus` | Zone 1 Zonenventil | close=geschlossen, open=offen |
| `Z1RoomZoneMapping` | Zone 1 Raumfühler-Zuordnung | none=keine, VRC700=VRC 700, VR91_1=VR 91 (1), VR91_2=VR 91 (2), VR91_3=VR 91 (3) |
| `Z1TempDesired` | Zone 1 Raum-Solltemperatur Heizen | °C |
| `Z1CoolingTempDesired` | Zone 1 Raum-Solltemperatur Kühlen | °C |
| `Z1Shortname` | Zone 1 Kurzname |  |
| `Z1Name1` | Zone 1 Name (Teil 1) |  |
| `Z1Name2` | Zone 1 Name (Teil 2) |  |
| `Z1TimeSlotActive` | Zone 1 Zeitfenster Heizen aktiv | no=Nein, yes=Ja |
| `Z1CoolingTimeSlotActive` | Zone 1 Zeitfenster Kühlen aktiv | no=Nein, yes=Ja |
| `Z1Status` | Zone 1 Status | auto=Automatik, ventilation=Stoßlüften, veto=Quick-Veto, holidayaway=Urlaub, load=Schnellladung, off=Aus |
| `Z1QuickVetoEndTime` | Zone 1 Quick-Veto Ende Uhrzeit |  |
| `Z1HolidayEndTime` | Zone 1 Urlaub Ende Uhrzeit |  |
| `Z1HolidayStartTime` | Zone 1 Urlaub Beginn Uhrzeit |  |
| `Z1ManualTemp` | Zone 1 manueller Sollwert Heizen | °C |
| `Z1CoolingManualTemp` | Zone 1 manueller Sollwert Kühlen | °C |
| `Z1QuickVetoEndDate` | Zone 1 Quick-Veto Ende Datum |  |
| `Z1QuickVetoDuration` | Zone 1 Quick-Veto Dauer | h |
| `Z1RoomHumidity` | Zone 1 Raumluftfeuchte | % |
| `Z2CoolingOpMode` | Zone 2 Betriebsart Kühlen | off=Aus, auto=Zeitgesteuert, manual=Manuell |
| `Z2CoolingSetbackTemp` | Zone 2 Absenktemperatur Kühlen | °C |
| `Z2HolidayStartPeriod` | Zone 2 Urlaub Beginn |  |
| `Z2HolidayEndPeriod` | Zone 2 Urlaub Ende |  |
| `Z2HolidayTemp` | Zone 2 Urlaubstemperatur | °C |
| `Z2OpMode` | Zone 2 Betriebsart Heizen | off=Aus, auto=Zeitgesteuert, manual=Manuell |
| `Z2QuickVetoTemp` | Zone 2 Quick-Veto Temperatur | °C |
| `Z2SetbackTemp` | Zone 2 Absenktemperatur Heizen | °C |
| `Z2SFMode` | Zone 2 Sonderfunktion | auto=keine, ventilation=Stoßlüften, veto=Quick-Veto |
| `Z2RoomTemp` | Zone 2 Raumtemperatur | °C |
| `Z2ValveStatus` | Zone 2 Zonenventil | close=geschlossen, open=offen |
| `Z2RoomZoneMapping` | Zone 2 Raumfühler-Zuordnung | none=keine, VRC720=VRC 720, VR92_1=VR 92 (1), VR92_2=VR 92 (2), VR92_3=VR 92 (3) |
| `Z2TempDesired` | Zone 2 Raum-Solltemperatur Heizen | °C |
| `Z2CoolingTempDesired` | Zone 2 Raum-Solltemperatur Kühlen | °C |
| `Z2Shortname` | Zone 2 Kurzname |  |
| `Z2Name1` | Zone 2 Name (Teil 1) |  |
| `Z2Name2` | Zone 2 Name (Teil 2) |  |
| `Z2TimeSlotActive` | Zone 2 Zeitfenster Heizen aktiv | no=Nein, yes=Ja |
| `Z2CoolingTimeSlotActive` | Zone 2 Zeitfenster Kühlen aktiv | no=Nein, yes=Ja |
| `Z2Status` | Zone 2 Status | auto=Automatik, ventilation=Stoßlüften, veto=Quick-Veto, holidayaway=Urlaub, load=Schnellladung, off=Aus |
| `Z2QuickVetoEndTime` | Zone 2 Quick-Veto Ende Uhrzeit |  |
| `Z2HolidayEndTime` | Zone 2 Urlaub Ende Uhrzeit |  |
| `Z2HolidayStartTime` | Zone 2 Urlaub Beginn Uhrzeit |  |
| `Z2ManualTemp` | Zone 2 manueller Sollwert Heizen | °C |
| `Z2CoolingManualTemp` | Zone 2 manueller Sollwert Kühlen | °C |
| `Z2QuickVetoEndDate` | Zone 2 Quick-Veto Ende Datum |  |
| `Z2QuickVetoDuration` | Zone 2 Quick-Veto Dauer | h |
| `Z2RoomHumidity` | Zone 2 Raumluftfeuchte | % |
| `Z3CoolingOpMode` | Zone 3 Betriebsart Kühlen | off=Aus, auto=Zeitgesteuert, manual=Manuell |
| `Z3CoolingSetbackTemp` | Zone 3 Absenktemperatur Kühlen | °C |
| `Z3HolidayStartPeriod` | Zone 3 Urlaub Beginn |  |
| `Z3HolidayEndPeriod` | Zone 3 Urlaub Ende |  |
| `Z3HolidayTemp` | Zone 3 Urlaubstemperatur | °C |
| `Z3OpMode` | Zone 3 Betriebsart Heizen | off=Aus, auto=Zeitgesteuert, manual=Manuell |
| `Z3QuickVetoTemp` | Zone 3 Quick-Veto Temperatur | °C |
| `Z3SetbackTemp` | Zone 3 Absenktemperatur Heizen | °C |
| `Z3SFMode` | Zone 3 Sonderfunktion | auto=keine, ventilation=Stoßlüften, veto=Quick-Veto |
| `Z3RoomTemp` | Zone 3 Raumtemperatur | °C |
| `Z3ValveStatus` | Zone 3 Zonenventil | close=geschlossen, open=offen |
| `Z3RoomZoneMapping` | Zone 3 Raumfühler-Zuordnung | none=keine, VRC720=VRC 720, VR92_1=VR 92 (1), VR92_2=VR 92 (2), VR92_3=VR 92 (3) |
| `Z3TempDesired` | Zone 3 Raum-Solltemperatur Heizen | °C |
| `Z3CoolingTempDesired` | Zone 3 Raum-Solltemperatur Kühlen | °C |
| `Z3Shortname` | Zone 3 Kurzname |  |
| `Z3Name1` | Zone 3 Name (Teil 1) |  |
| `Z3Name2` | Zone 3 Name (Teil 2) |  |
| `Z3TimeSlotActive` | Zone 3 Zeitfenster Heizen aktiv | no=Nein, yes=Ja |
| `Z3CoolingTimeSlotActive` | Zone 3 Zeitfenster Kühlen aktiv | no=Nein, yes=Ja |
| `Z3Status` | Zone 3 Status | auto=Automatik, ventilation=Stoßlüften, veto=Quick-Veto, holidayaway=Urlaub, load=Schnellladung, off=Aus |
| `Z3QuickVetoEndTime` | Zone 3 Quick-Veto Ende Uhrzeit |  |
| `Z3HolidayEndTime` | Zone 3 Urlaub Ende Uhrzeit |  |
| `Z3HolidayStartTime` | Zone 3 Urlaub Beginn Uhrzeit |  |
| `Z3ManualTemp` | Zone 3 manueller Sollwert Heizen | °C |
| `Z3CoolingManualTemp` | Zone 3 manueller Sollwert Kühlen | °C |
| `Z3QuickVetoEndDate` | Zone 3 Quick-Veto Ende Datum |  |
| `Z3QuickVetoDuration` | Zone 3 Quick-Veto Dauer | h |
| `Z3RoomHumidity` | Zone 3 Raumluftfeuchte | % |
| `SolarPumpKick` | Solarpumpe Anstoßfunktion | off=Aus, on=Ein |
| `SolarCollectorTemp` | Kollektortemperatur | °C |
| `SolarCollectorTempMin` | Kollektortemperatur minimal | °C |
| `SolarCollectorTempMax` | Kollektortemperatur maximal | °C |
| `SolarYieldTemp` | Solarertragsfühler | °C |
| `SolarPump` | Solarpumpe | off=Aus, on=Ein |
| `SolarPumpHours` | Solarpumpe Betriebsstunden | h |
| `SolarCylinder1TempMax` | Solarspeicher maximale Ladetemperatur | °C |
| `SolarCylinder1SwitchOnDifferential` | Solar Einschaltdifferenz | K |
| `SolarCylinder1SwitchOffDifferential` | Solar Ausschaltdifferenz | K |
| `HwcTimer_Timeslots` | Zeitprogramm Warmwasser Anzahl Zeitfenster | Mo [–]; Di [–]; Mi [–]; Do [–]; Fr [–]; Sa [–]; So [–] |
| `HwcTimer_Monday0` | Zeitprogramm Warmwasser Montag (1. Zeitfenster) | von [–]; bis [–] |
| `HwcTimer_Monday` | Zeitprogramm Warmwasser Montag | Anzahl Zeitfenster [–]; von [–]; bis [–] |
| `HwcTimer_Tuesday0` | Zeitprogramm Warmwasser Dienstag (1. Zeitfenster) | von [–]; bis [–] |
| `HwcTimer_Tuesday` | Zeitprogramm Warmwasser Dienstag | Anzahl Zeitfenster [–]; von [–]; bis [–] |
| `HwcTimer_Wednesday0` | Zeitprogramm Warmwasser Mittwoch (1. Zeitfenster) | von [–]; bis [–] |
| `HwcTimer_Wednesday` | Zeitprogramm Warmwasser Mittwoch | Anzahl Zeitfenster [–]; von [–]; bis [–] |
| `HwcTimer_Thursday0` | Zeitprogramm Warmwasser Donnerstag (1. Zeitfenster) | von [–]; bis [–] |
| `HwcTimer_Thursday` | Zeitprogramm Warmwasser Donnerstag | Anzahl Zeitfenster [–]; von [–]; bis [–] |
| `HwcTimer_Friday0` | Zeitprogramm Warmwasser Freitag (1. Zeitfenster) | von [–]; bis [–] |
| `HwcTimer_Friday` | Zeitprogramm Warmwasser Freitag | Anzahl Zeitfenster [–]; von [–]; bis [–] |
| `HwcTimer_Saturday0` | Zeitprogramm Warmwasser Samstag (1. Zeitfenster) | von [–]; bis [–] |
| `HwcTimer_Saturday` | Zeitprogramm Warmwasser Samstag | Anzahl Zeitfenster [–]; von [–]; bis [–] |
| `HwcTimer_Sunday0` | Zeitprogramm Warmwasser Sonntag (1. Zeitfenster) | von [–]; bis [–] |
| `HwcTimer_Sunday` | Zeitprogramm Warmwasser Sonntag | Anzahl Zeitfenster [–]; von [–]; bis [–] |
| `CcTimer_Timeslots` | Zeitprogramm Zirkulation Anzahl Zeitfenster | Mo [–]; Di [–]; Mi [–]; Do [–]; Fr [–]; Sa [–]; So [–] |
| `CcTimer_Monday0` | Zeitprogramm Zirkulation Montag (1. Zeitfenster) | von [–]; bis [–] |
| `CcTimer_Monday` | Zeitprogramm Zirkulation Montag | Anzahl Zeitfenster [–]; von [–]; bis [–] |
| `CcTimer_Tuesday0` | Zeitprogramm Zirkulation Dienstag (1. Zeitfenster) | von [–]; bis [–] |
| `CcTimer_Tuesday` | Zeitprogramm Zirkulation Dienstag | Anzahl Zeitfenster [–]; von [–]; bis [–] |
| `CcTimer_Wednesday0` | Zeitprogramm Zirkulation Mittwoch (1. Zeitfenster) | von [–]; bis [–] |
| `CcTimer_Wednesday` | Zeitprogramm Zirkulation Mittwoch | Anzahl Zeitfenster [–]; von [–]; bis [–] |
| `CcTimer_Thursday0` | Zeitprogramm Zirkulation Donnerstag (1. Zeitfenster) | von [–]; bis [–] |
| `CcTimer_Thursday` | Zeitprogramm Zirkulation Donnerstag | Anzahl Zeitfenster [–]; von [–]; bis [–] |
| `CcTimer_Friday0` | Zeitprogramm Zirkulation Freitag (1. Zeitfenster) | von [–]; bis [–] |
| `CcTimer_Friday` | Zeitprogramm Zirkulation Freitag | Anzahl Zeitfenster [–]; von [–]; bis [–] |
| `CcTimer_Saturday0` | Zeitprogramm Zirkulation Samstag (1. Zeitfenster) | von [–]; bis [–] |
| `CcTimer_Saturday` | Zeitprogramm Zirkulation Samstag | Anzahl Zeitfenster [–]; von [–]; bis [–] |
| `CcTimer_Sunday0` | Zeitprogramm Zirkulation Sonntag (1. Zeitfenster) | von [–]; bis [–] |
| `CcTimer_Sunday` | Zeitprogramm Zirkulation Sonntag | Anzahl Zeitfenster [–]; von [–]; bis [–] |
| `SilentTimer_Timeslots` | Zeitprogramm Flüsterbetrieb Anzahl Zeitfenster | Mo [–]; Di [–]; Mi [–]; Do [–]; Fr [–]; Sa [–]; So [–] |
| `SilentTimer_Monday0` | Zeitprogramm Flüsterbetrieb Montag (1. Zeitfenster) | von [–]; bis [–] |
| `SilentTimer_Monday` | Zeitprogramm Flüsterbetrieb Montag | Anzahl Zeitfenster [–]; von [–]; bis [–] |
| `SilentTimer_Tuesday0` | Zeitprogramm Flüsterbetrieb Dienstag (1. Zeitfenster) | von [–]; bis [–] |
| `SilentTimer_Tuesday` | Zeitprogramm Flüsterbetrieb Dienstag | Anzahl Zeitfenster [–]; von [–]; bis [–] |
| `SilentTimer_Wednesday0` | Zeitprogramm Flüsterbetrieb Mittwoch (1. Zeitfenster) | von [–]; bis [–] |
| `SilentTimer_Wednesday` | Zeitprogramm Flüsterbetrieb Mittwoch | Anzahl Zeitfenster [–]; von [–]; bis [–] |
| `SilentTimer_Thursday0` | Zeitprogramm Flüsterbetrieb Donnerstag (1. Zeitfenster) | von [–]; bis [–] |
| `SilentTimer_Thursday` | Zeitprogramm Flüsterbetrieb Donnerstag | Anzahl Zeitfenster [–]; von [–]; bis [–] |
| `SilentTimer_Friday0` | Zeitprogramm Flüsterbetrieb Freitag (1. Zeitfenster) | von [–]; bis [–] |
| `SilentTimer_Friday` | Zeitprogramm Flüsterbetrieb Freitag | Anzahl Zeitfenster [–]; von [–]; bis [–] |
| `SilentTimer_Saturday0` | Zeitprogramm Flüsterbetrieb Samstag (1. Zeitfenster) | von [–]; bis [–] |
| `SilentTimer_Saturday` | Zeitprogramm Flüsterbetrieb Samstag | Anzahl Zeitfenster [–]; von [–]; bis [–] |
| `SilentTimer_Sunday0` | Zeitprogramm Flüsterbetrieb Sonntag (1. Zeitfenster) | von [–]; bis [–] |
| `SilentTimer_Sunday` | Zeitprogramm Flüsterbetrieb Sonntag | Anzahl Zeitfenster [–]; von [–]; bis [–] |
| `Z1Timer_Timeslots` | Zeitprogramm Zone 1 Heizen Anzahl Zeitfenster | Mo [–]; Di [–]; Mi [–]; Do [–]; Fr [–]; Sa [–]; So [–] |
| `Z1Timer_Monday0` | Zeitprogramm Zone 1 Heizen Montag (1. Zeitfenster) | von [–]; bis [–]; Temperatur [°C] |
| `Z1Timer_Monday` | Zeitprogramm Zone 1 Heizen Montag | Anzahl Zeitfenster [–]; von [–]; bis [–]; Temperatur [°C] |
| `Z1Timer_Tuesday0` | Zeitprogramm Zone 1 Heizen Dienstag (1. Zeitfenster) | von [–]; bis [–]; Temperatur [°C] |
| `Z1Timer_Tuesday` | Zeitprogramm Zone 1 Heizen Dienstag | Anzahl Zeitfenster [–]; von [–]; bis [–]; Temperatur [°C] |
| `Z1Timer_Wednesday0` | Zeitprogramm Zone 1 Heizen Mittwoch (1. Zeitfenster) | von [–]; bis [–]; Temperatur [°C] |
| `Z1Timer_Wednesday` | Zeitprogramm Zone 1 Heizen Mittwoch | Anzahl Zeitfenster [–]; von [–]; bis [–]; Temperatur [°C] |
| `Z1Timer_Thursday0` | Zeitprogramm Zone 1 Heizen Donnerstag (1. Zeitfenster) | von [–]; bis [–]; Temperatur [°C] |
| `Z1Timer_Thursday` | Zeitprogramm Zone 1 Heizen Donnerstag | Anzahl Zeitfenster [–]; von [–]; bis [–]; Temperatur [°C] |
| `Z1Timer_Friday0` | Zeitprogramm Zone 1 Heizen Freitag (1. Zeitfenster) | von [–]; bis [–]; Temperatur [°C] |
| `Z1Timer_Friday` | Zeitprogramm Zone 1 Heizen Freitag | Anzahl Zeitfenster [–]; von [–]; bis [–]; Temperatur [°C] |
| `Z1Timer_Saturday0` | Zeitprogramm Zone 1 Heizen Samstag (1. Zeitfenster) | von [–]; bis [–]; Temperatur [°C] |
| `Z1Timer_Saturday` | Zeitprogramm Zone 1 Heizen Samstag | Anzahl Zeitfenster [–]; von [–]; bis [–]; Temperatur [°C] |
| `Z1Timer_Sunday0` | Zeitprogramm Zone 1 Heizen Sonntag (1. Zeitfenster) | von [–]; bis [–]; Temperatur [°C] |
| `Z1Timer_Sunday` | Zeitprogramm Zone 1 Heizen Sonntag | Anzahl Zeitfenster [–]; von [–]; bis [–]; Temperatur [°C] |
| `Z1CoolingTimer_Timeslots` | Zeitprogramm Zone 1 Kühlen Anzahl Zeitfenster | Mo [–]; Di [–]; Mi [–]; Do [–]; Fr [–]; Sa [–]; So [–] |
| `Z1CoolingTimer_Monday0` | Zeitprogramm Zone 1 Kühlen Montag (1. Zeitfenster) | von [–]; bis [–]; Temperatur [°C] |
| `Z1CoolingTimer_Monday` | Zeitprogramm Zone 1 Kühlen Montag | Anzahl Zeitfenster [–]; von [–]; bis [–] |
| `Z1CoolingTimer_Tuesday0` | Zeitprogramm Zone 1 Kühlen Dienstag (1. Zeitfenster) | von [–]; bis [–]; Temperatur [°C] |
| `Z1CoolingTimer_Tuesday` | Zeitprogramm Zone 1 Kühlen Dienstag | Anzahl Zeitfenster [–]; von [–]; bis [–] |
| `Z1CoolingTimer_Wednesday0` | Zeitprogramm Zone 1 Kühlen Mittwoch (1. Zeitfenster) | von [–]; bis [–]; Temperatur [°C] |
| `Z1CoolingTimer_Wednesday` | Zeitprogramm Zone 1 Kühlen Mittwoch | Anzahl Zeitfenster [–]; von [–]; bis [–] |
| `Z1CoolingTimer_Thursday0` | Zeitprogramm Zone 1 Kühlen Donnerstag (1. Zeitfenster) | von [–]; bis [–]; Temperatur [°C] |
| `Z1CoolingTimer_Thursday` | Zeitprogramm Zone 1 Kühlen Donnerstag | Anzahl Zeitfenster [–]; von [–]; bis [–] |
| `Z1CoolingTimer_Friday0` | Zeitprogramm Zone 1 Kühlen Freitag (1. Zeitfenster) | von [–]; bis [–]; Temperatur [°C] |
| `Z1CoolingTimer_Friday` | Zeitprogramm Zone 1 Kühlen Freitag | Anzahl Zeitfenster [–]; von [–]; bis [–] |
| `Z1CoolingTimer_Saturday0` | Zeitprogramm Zone 1 Kühlen Samstag (1. Zeitfenster) | von [–]; bis [–]; Temperatur [°C] |
| `Z1CoolingTimer_Saturday` | Zeitprogramm Zone 1 Kühlen Samstag | Anzahl Zeitfenster [–]; von [–]; bis [–] |
| `Z1CoolingTimer_Sunday0` | Zeitprogramm Zone 1 Kühlen Sonntag (1. Zeitfenster) | von [–]; bis [–]; Temperatur [°C] |
| `Z1CoolingTimer_Sunday` | Zeitprogramm Zone 1 Kühlen Sonntag | Anzahl Zeitfenster [–]; von [–]; bis [–] |
| `Z2Timer_Timeslots` | Zeitprogramm Zone 2 Heizen Anzahl Zeitfenster | Mo [–]; Di [–]; Mi [–]; Do [–]; Fr [–]; Sa [–]; So [–] |
| `Z2Timer_Monday0` | Zeitprogramm Zone 2 Heizen Montag (1. Zeitfenster) | von [–]; bis [–]; Temperatur [°C] |
| `Z2Timer_Monday` | Zeitprogramm Zone 2 Heizen Montag | Anzahl Zeitfenster [–]; von [–]; bis [–]; Temperatur [°C] |
| `Z2Timer_Tuesday0` | Zeitprogramm Zone 2 Heizen Dienstag (1. Zeitfenster) | von [–]; bis [–]; Temperatur [°C] |
| `Z2Timer_Tuesday` | Zeitprogramm Zone 2 Heizen Dienstag | Anzahl Zeitfenster [–]; von [–]; bis [–]; Temperatur [°C] |
| `Z2Timer_Wednesday0` | Zeitprogramm Zone 2 Heizen Mittwoch (1. Zeitfenster) | von [–]; bis [–]; Temperatur [°C] |
| `Z2Timer_Wednesday` | Zeitprogramm Zone 2 Heizen Mittwoch | Anzahl Zeitfenster [–]; von [–]; bis [–]; Temperatur [°C] |
| `Z2Timer_Thursday0` | Zeitprogramm Zone 2 Heizen Donnerstag (1. Zeitfenster) | von [–]; bis [–]; Temperatur [°C] |
| `Z2Timer_Thursday` | Zeitprogramm Zone 2 Heizen Donnerstag | Anzahl Zeitfenster [–]; von [–]; bis [–]; Temperatur [°C] |
| `Z2Timer_Friday0` | Zeitprogramm Zone 2 Heizen Freitag (1. Zeitfenster) | von [–]; bis [–]; Temperatur [°C] |
| `Z2Timer_Friday` | Zeitprogramm Zone 2 Heizen Freitag | Anzahl Zeitfenster [–]; von [–]; bis [–]; Temperatur [°C] |
| `Z2Timer_Saturday0` | Zeitprogramm Zone 2 Heizen Samstag (1. Zeitfenster) | von [–]; bis [–]; Temperatur [°C] |
| `Z2Timer_Saturday` | Zeitprogramm Zone 2 Heizen Samstag | Anzahl Zeitfenster [–]; von [–]; bis [–]; Temperatur [°C] |
| `Z2Timer_Sunday0` | Zeitprogramm Zone 2 Heizen Sonntag (1. Zeitfenster) | von [–]; bis [–]; Temperatur [°C] |
| `Z2Timer_Sunday` | Zeitprogramm Zone 2 Heizen Sonntag | Anzahl Zeitfenster [–]; von [–]; bis [–]; Temperatur [°C] |
| `Z2CoolingTimer_Timeslots` | Zeitprogramm Zone 2 Kühlen Anzahl Zeitfenster | Mo [–]; Di [–]; Mi [–]; Do [–]; Fr [–]; Sa [–]; So [–] |
| `Z2CoolingTimer_Monday0` | Zeitprogramm Zone 2 Kühlen Montag (1. Zeitfenster) | von [–]; bis [–]; Temperatur [°C] |
| `Z2CoolingTimer_Monday` | Zeitprogramm Zone 2 Kühlen Montag | Anzahl Zeitfenster [–]; von [–]; bis [–] |
| `Z2CoolingTimer_Tuesday0` | Zeitprogramm Zone 2 Kühlen Dienstag (1. Zeitfenster) | von [–]; bis [–]; Temperatur [°C] |
| `Z2CoolingTimer_Tuesday` | Zeitprogramm Zone 2 Kühlen Dienstag | Anzahl Zeitfenster [–]; von [–]; bis [–] |
| `Z2CoolingTimer_Wednesday0` | Zeitprogramm Zone 2 Kühlen Mittwoch (1. Zeitfenster) | von [–]; bis [–]; Temperatur [°C] |
| `Z2CoolingTimer_Wednesday` | Zeitprogramm Zone 2 Kühlen Mittwoch | Anzahl Zeitfenster [–]; von [–]; bis [–] |
| `Z2CoolingTimer_Thursday0` | Zeitprogramm Zone 2 Kühlen Donnerstag (1. Zeitfenster) | von [–]; bis [–]; Temperatur [°C] |
| `Z2CoolingTimer_Thursday` | Zeitprogramm Zone 2 Kühlen Donnerstag | Anzahl Zeitfenster [–]; von [–]; bis [–] |
| `Z2CoolingTimer_Friday0` | Zeitprogramm Zone 2 Kühlen Freitag (1. Zeitfenster) | von [–]; bis [–]; Temperatur [°C] |
| `Z2CoolingTimer_Friday` | Zeitprogramm Zone 2 Kühlen Freitag | Anzahl Zeitfenster [–]; von [–]; bis [–] |
| `Z2CoolingTimer_Saturday0` | Zeitprogramm Zone 2 Kühlen Samstag (1. Zeitfenster) | von [–]; bis [–]; Temperatur [°C] |
| `Z2CoolingTimer_Saturday` | Zeitprogramm Zone 2 Kühlen Samstag | Anzahl Zeitfenster [–]; von [–]; bis [–] |
| `Z2CoolingTimer_Sunday0` | Zeitprogramm Zone 2 Kühlen Sonntag (1. Zeitfenster) | von [–]; bis [–]; Temperatur [°C] |
| `Z2CoolingTimer_Sunday` | Zeitprogramm Zone 2 Kühlen Sonntag | Anzahl Zeitfenster [–]; von [–]; bis [–] |
| `Z3Timer_Timeslots` | Zeitprogramm Zone 3 Heizen Anzahl Zeitfenster | Mo [–]; Di [–]; Mi [–]; Do [–]; Fr [–]; Sa [–]; So [–] |
| `Z3Timer_Monday0` | Zeitprogramm Zone 3 Heizen Montag (1. Zeitfenster) | von [–]; bis [–]; Temperatur [°C] |
| `Z3Timer_Monday` | Zeitprogramm Zone 3 Heizen Montag | Anzahl Zeitfenster [–]; von [–]; bis [–]; Temperatur [°C] |
| `Z3Timer_Tuesday0` | Zeitprogramm Zone 3 Heizen Dienstag (1. Zeitfenster) | von [–]; bis [–]; Temperatur [°C] |
| `Z3Timer_Tuesday` | Zeitprogramm Zone 3 Heizen Dienstag | Anzahl Zeitfenster [–]; von [–]; bis [–]; Temperatur [°C] |
| `Z3Timer_Wednesday0` | Zeitprogramm Zone 3 Heizen Mittwoch (1. Zeitfenster) | von [–]; bis [–]; Temperatur [°C] |
| `Z3Timer_Wednesday` | Zeitprogramm Zone 3 Heizen Mittwoch | Anzahl Zeitfenster [–]; von [–]; bis [–]; Temperatur [°C] |
| `Z3Timer_Thursday0` | Zeitprogramm Zone 3 Heizen Donnerstag (1. Zeitfenster) | von [–]; bis [–]; Temperatur [°C] |
| `Z3Timer_Thursday` | Zeitprogramm Zone 3 Heizen Donnerstag | Anzahl Zeitfenster [–]; von [–]; bis [–]; Temperatur [°C] |
| `Z3Timer_Friday0` | Zeitprogramm Zone 3 Heizen Freitag (1. Zeitfenster) | von [–]; bis [–]; Temperatur [°C] |
| `Z3Timer_Friday` | Zeitprogramm Zone 3 Heizen Freitag | Anzahl Zeitfenster [–]; von [–]; bis [–]; Temperatur [°C] |
| `Z3Timer_Saturday0` | Zeitprogramm Zone 3 Heizen Samstag (1. Zeitfenster) | von [–]; bis [–]; Temperatur [°C] |
| `Z3Timer_Saturday` | Zeitprogramm Zone 3 Heizen Samstag | Anzahl Zeitfenster [–]; von [–]; bis [–]; Temperatur [°C] |
| `Z3Timer_Sunday0` | Zeitprogramm Zone 3 Heizen Sonntag (1. Zeitfenster) | von [–]; bis [–]; Temperatur [°C] |
| `Z3Timer_Sunday` | Zeitprogramm Zone 3 Heizen Sonntag | Anzahl Zeitfenster [–]; von [–]; bis [–]; Temperatur [°C] |
| `Z3CoolingTimer_Timeslots` | Zeitprogramm Zone 3 Kühlen Anzahl Zeitfenster | Mo [–]; Di [–]; Mi [–]; Do [–]; Fr [–]; Sa [–]; So [–] |
| `Z3CoolingTimer_Monday0` | Zeitprogramm Zone 3 Kühlen Montag (1. Zeitfenster) | von [–]; bis [–]; Temperatur [°C] |
| `Z3CoolingTimer_Monday` | Zeitprogramm Zone 3 Kühlen Montag | Anzahl Zeitfenster [–]; von [–]; bis [–] |
| `Z3CoolingTimer_Tuesday0` | Zeitprogramm Zone 3 Kühlen Dienstag (1. Zeitfenster) | von [–]; bis [–]; Temperatur [°C] |
| `Z3CoolingTimer_Tuesday` | Zeitprogramm Zone 3 Kühlen Dienstag | Anzahl Zeitfenster [–]; von [–]; bis [–] |
| `Z3CoolingTimer_Wednesday0` | Zeitprogramm Zone 3 Kühlen Mittwoch (1. Zeitfenster) | von [–]; bis [–]; Temperatur [°C] |
| `Z3CoolingTimer_Wednesday` | Zeitprogramm Zone 3 Kühlen Mittwoch | Anzahl Zeitfenster [–]; von [–]; bis [–] |
| `Z3CoolingTimer_Thursday0` | Zeitprogramm Zone 3 Kühlen Donnerstag (1. Zeitfenster) | von [–]; bis [–]; Temperatur [°C] |
| `Z3CoolingTimer_Thursday` | Zeitprogramm Zone 3 Kühlen Donnerstag | Anzahl Zeitfenster [–]; von [–]; bis [–] |
| `Z3CoolingTimer_Friday0` | Zeitprogramm Zone 3 Kühlen Freitag (1. Zeitfenster) | von [–]; bis [–]; Temperatur [°C] |
| `Z3CoolingTimer_Friday` | Zeitprogramm Zone 3 Kühlen Freitag | Anzahl Zeitfenster [–]; von [–]; bis [–] |
| `Z3CoolingTimer_Saturday0` | Zeitprogramm Zone 3 Kühlen Samstag (1. Zeitfenster) | von [–]; bis [–]; Temperatur [°C] |
| `Z3CoolingTimer_Saturday` | Zeitprogramm Zone 3 Kühlen Samstag | Anzahl Zeitfenster [–]; von [–]; bis [–] |
| `Z3CoolingTimer_Sunday0` | Zeitprogramm Zone 3 Kühlen Sonntag (1. Zeitfenster) | von [–]; bis [–]; Temperatur [°C] |
| `Z3CoolingTimer_Sunday` | Zeitprogramm Zone 3 Kühlen Sonntag | Anzahl Zeitfenster [–]; von [–]; bis [–] |
| `RoomHumidity` | Raumluftfeuchte (Regler) | % |
| `RoomTemp` | Raumtemperatur (Regler) | °C |
| `VR92Addr1RoomHumidity` | Fernbedienung VR 92 Adresse 1 Raumluftfeuchte | % |
| `VR92Addr1RoomTemp` | Fernbedienung VR 92 Adresse 1 Raumtemperatur | °C |
| `VR92Addr2RoomHumidity` | Fernbedienung VR 92 Adresse 2 Raumluftfeuchte | % |
| `VR92Addr2RoomTemp` | Fernbedienung VR 92 Adresse 2 Raumtemperatur | °C |
| `VR92Addr3RoomHumidity` | Fernbedienung VR 92 Adresse 3 Raumluftfeuchte | % |
| `VR92Addr3RoomTemp` | Fernbedienung VR 92 Adresse 3 Raumtemperatur | °C |
| `VR92Addr4RoomHumidity` | Fernbedienung VR 92 Adresse 4 Raumluftfeuchte | % |
| `VR92Addr4RoomTemp` | Fernbedienung VR 92 Adresse 4 Raumtemperatur | °C |
| `VR92Addr5RoomHumidity` | Fernbedienung VR 92 Adresse 5 Raumluftfeuchte | % |
| `VR92Addr5RoomTemp` | Fernbedienung VR 92 Adresse 5 Raumtemperatur | °C |
| `VR92Addr6RoomHumidity` | Fernbedienung VR 92 Adresse 6 Raumluftfeuchte | % |
| `VR92Addr6RoomTemp` | Fernbedienung VR 92 Adresse 6 Raumtemperatur | °C |
| `VR92Addr7RoomHumidity` | Fernbedienung VR 92 Adresse 7 Raumluftfeuchte | % |
| `VR92Addr7RoomTemp` | Fernbedienung VR 92 Adresse 7 Raumtemperatur | °C |
| `VR92Addr8RoomHumidity` | Fernbedienung VR 92 Adresse 8 Raumluftfeuchte | % |
| `VR92Addr8RoomTemp` | Fernbedienung VR 92 Adresse 8 Raumtemperatur | °C |

## vr_71: Erweiterungsmodul VR 71 (Adresse 26)

| Name | Bedeutung | Einheit / Werte |
|---|---|---|
| `Currenterror` | Aktuelle Fehler | Fehler 1 [–]; Fehler 2 [–]; Fehler 3 [–]; Fehler 4 [–]; Fehler 5 [–] |
| `Errorhistory` | Fehlerhistorie | Status [–]; Uhrzeit [–]; Datum [–]; Fehler 1 [–] |
| `Clearerrorhistory` | Fehlerhistorie löschen | no=Nein, yes=Ja |
| `StatSolarEnergySum` | Solarertrag gesamt (Statistik) | kWh |
| `StatSolarEnergySumHc` | Solarertrag Heizen (Statistik) | kWh |
| `StatSolarEnergySumHwc` | Solarertrag Warmwasser (Statistik) | kWh |
| `StatEnvironmentEnergySum` | Umweltenergie gesamt (Statistik) | kWh |
| `StatEnvironmentEnergySumHc` | Umweltenergie Heizen (Statistik) | kWh |
| `StatEnvironmentEnergySumHwc` | Umweltenergie Warmwasser (Statistik) | kWh |
| `StatEnvironmentEnergySumCool` | Umweltenergie Kühlen (Statistik) | kWh |
| `StatElectricEnergySum` | Stromverbrauch gesamt (Statistik) | kWh |
| `StatElectricEnergySumHc` | Stromverbrauch Heizen (Statistik) | kWh |
| `StatElectricEnergySumHwc` | Stromverbrauch Warmwasser (Statistik) | kWh |
| `StatElectricEnergySumCool` | Stromverbrauch Kühlen (Statistik) | kWh |
| `StatFuelSum` | Brennstoffverbrauch gesamt (Statistik) | kWh |
| `StatFuelSumHc` | Brennstoffverbrauch Heizen (Statistik) | kWh |
| `StatFuelSumHwc` | Brennstoffverbrauch Warmwasser (Statistik) | kWh |
| `SetActorState` | Relais schalten | Relais 1 [off=Aus, on=Ein]; Relais 2 [off=Aus, on=Ein]; Relais 3 [off=Aus, on=Ein]; Relais 4 [off=Aus, on=Ein]; Relais 5 [off=Aus, on=Ein]; Relais 6 [off=Aus, on=Ein]; Relais 7 [off=Aus, on=Ein]; Relais 8 [off=Aus, on=Ein]; Relais 9 [off=Aus, on=Ein]; Relais 10 [off=Aus, on=Ein]; Relais 11 [off=Aus, on=Ein]; Relais 12 [off=Aus, on=Ein] |
| `Mc1Operation` | Mischerkreis 1 Betrieb | Status [off=Aus, on=Ein]; Vorlauf-Solltemperatur [°C]; Pumpe [off=Aus, on=Ein]; Mischer [%] |
| `Mc2Operation` | Mischerkreis 2 Betrieb | Status [off=Aus, on=Ein]; Vorlauf-Solltemperatur [°C]; Pumpe [off=Aus, on=Ein]; Mischer [%] |
| `Mc3Operation` | Mischerkreis 3 Betrieb | Status [off=Aus, on=Ein]; Vorlauf-Solltemperatur [°C]; Pumpe [off=Aus, on=Ein]; Mischer [%] |
| `SensorData1` | Fühlerwerte S1–S7 | S1 [°C]; S2 [°C]; S3 [°C]; S4 [°C]; S5 [°C]; S6 [°C]; S7 [°C] |
| `SensorData2` | Fühlerwerte S8–S12 | S8 [°C]; S9 [°C]; S10 [°C]; S11 [°C]; S12 [°C]; Sx [°C] |

## vwzio: Inneneinheit / Hydraulikstation (Adresse 76)

| Name | Bedeutung | Einheit / Werte |
|---|---|---|
| `TestOutdoorTemp` | Außentemperatur (Testmenü) | °C |
| `StatSolarEnergySum` | Solarertrag gesamt (Statistik) | kWh |
| `StatSolarEnergySumHc` | Solarertrag Heizen (Statistik) | kWh |
| `StatSolarEnergySumHwc` | Solarertrag Warmwasser (Statistik) | kWh |
| `StatEnvironmentEnergySum` | Umweltenergie gesamt (Statistik) | kWh |
| `StatEnvironmentEnergySumHc` | Umweltenergie Heizen (Statistik) | kWh |
| `StatEnvironmentEnergySumHwc` | Umweltenergie Warmwasser (Statistik) | kWh |
| `StatEnvironmentEnergySumCool` | Umweltenergie Kühlen (Statistik) | kWh |
| `StatElectricEnergySum` | Stromverbrauch gesamt (Statistik) | kWh |
| `StatElectricEnergySumHc` | Stromverbrauch Heizen (Statistik) | kWh |
| `StatElectricEnergySumHwc` | Stromverbrauch Warmwasser (Statistik) | kWh |
| `StatElectricEnergySumCool` | Stromverbrauch Kühlen (Statistik) | kWh |
| `StatFuelSum` | Brennstoffverbrauch gesamt (Statistik) | kWh |
| `StatFuelSumHc` | Brennstoffverbrauch Heizen (Statistik) | kWh |
| `StatFuelSumHwc` | Brennstoffverbrauch Warmwasser (Statistik) | kWh |
| `EnableTestThreeWayValve` | TestmenÃ¼ freischalten: 3-Wege-Ventil |  |
| `TestThreeWayValve` | 3-Wege-Ventil (Testmenü) | heating=Heizen, dhw=Warmwasser |
| `EnableTestHwcTemp` | TestmenÃ¼ freischalten: Warmwassertemperatur |  |
| `TestHwcTemp` | Warmwassertemperatur (Testmenü) | °C |
| `EnableTestOutdoorTemp` | TestmenÃ¼ freischalten: Außentemperatur |  |

## broadcast: Rundsendungen

| Name | Bedeutung | Einheit / Werte |
|---|---|---|
| `OutsideTemp` | Außentemperatur | °C |
| `HwcStatus` | Warmwasser-Status | Ein/Aus [off=Aus, on=Ein]; Vorlauf-Solltemperatur [°C]; Ein/Aus 2 [off=Aus, on=Ein] |
| `Vdatetime` | Datum und Uhrzeit (Broadcast) | Uhrzeit [–]; Datum [–] |
| `Load` | Warmwasser-Schnellladung | off=Aus, on=Ein |
