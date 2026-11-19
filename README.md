# micro-ebusd MQTT für Symcon

Dieses Symcon-Modul bindet den **eBUS Adapter Shield (v5/C6) mit micro-ebusd** über den eingebauten **Symcon MQTT Server** an. Den großen ebusd-Dienst und seine HTTP-Schnittstelle (`/data`) braucht es dafür nicht.

- Eine Instanz pro eBUS-Gerät (circuit), z. B. bei einer Vaillant-Anlage: `hmu` (Wärmepumpe), `ctlv3` (sensoCOMFORT 720), `vr_71`, `vwzio`, `broadcast` (z. B. Außentemperatur).
- Eine weitere Instanz mit **leerem Gerät** zeigt die Statuswerte des Adapters selbst (uptime, signal, rssi …).
- Empfangene Werte werden automatisch als Variablen angelegt.
- **Deutsche Namen, Einheiten und Statustexte:** Das Modul bringt einen Katalog der Vaillant-Definitionen mit (`libs/vaillant.json`, 630 Nachrichten für hmu, ctlv3, vr_71, vwzio, broadcast). Die Variablen heißen wie die Nachricht, dahinter steht die deutsche Bedeutung in Klammern, z. B. „FlowTemp (Vorlauftemperatur)“ oder „Status01 temp_1 (Rücklauftemperatur)“; dazu kommen die Einheiten (°C, kW, kWh, bar, h …). Wertelisten wie Betriebsarten oder Statuscodes werden zu Integer-Variablen mit deutschen Texten, z. B. Aus / Zeitgesteuert / Manuell. Das Modul nutzt dafür Darstellungen, keine Variablenprofile. Die Übersicht aller Nachrichten steht in [NACHRICHTEN.md](NACHRICHTEN.md).
- Nachrichten aus der Liste „aktiv abfragen“ werden im eingestellten Intervall per `…/get` angefordert, **eine pro Sekunde**: Der Adapter verwirft ohne Meldung, was nicht in seine kurze Warteschlange passt (beobachtet: von zehn gleichzeitigen Abfragen kamen nur fünf zurück). Bei vielen Nachrichten das Intervall deshalb nicht kürzer wählen als ihre Anzahl in Sekunden.
- Schreiben ist nur möglich, wenn „Schreiben erlauben“ an ist und der Adapter die Nachricht als schreibbar meldet. Dafür vorher „Nachrichtenliste anfordern“ drücken. Bei Nachrichten mit mehreren Feldern schickt das Modul alle Felder mit den zuletzt empfangenen Werten zurück; fehlt einer, schreibt es nicht.
- **Adresse des Adapters (optional):** Ist sie eingetragen, liest das Modul Einheit, Grenzen und Schrittweite je Feld direkt vom Adapter (HTTP, `…/get?from=0`, also ohne Busverkehr), einmal je Nachricht beim Anlegen der Variable. Die Einheit des Adapters geht dem Katalog vor und gilt auch für Nachrichten, die der Katalog nicht kennt. Beim Schreiben lehnt das Modul Werte außerhalb der Grenzen ab. Für vorhandene Variablen „Vorhandene Variablen neu einrichten“ drücken. Antwortet der Adapter nicht, gilt der Katalog, und das Modul versucht es erst nach fünf Minuten wieder. Wertelisten und deutsche Namen kommen weiter aus dem Katalog.

Topics von micro-ebusd laut https://token.ebusd.eu/mqtt:

| Topic | Bedeutung |
|---|---|
| `<Basis>/<circuit>/<name>` | Wert als JSON, z. B. `{"value":35.56}` |
| `<Basis>/<circuit>/<name>/get` | Wert anfordern |
| `<Basis>/<circuit>/<name>/set` | Wert schreiben (JSON-Objekt) |
| `<Basis>/<circuit>/list` | Nachrichtenliste anfordern; die Antwort kommt seitenweise auf `<Basis>/<circuit>/any` (laut Dokumentation `…/any/found`) |

## Installation

1. **Modul nach Symcon bringen**, eine der beiden Möglichkeiten:
   - Den Ordner in ein eigenes Git-Repository legen (z. B. auf GitHub, auch privat mit Token). Dann in der Symcon-Konsole unter *Kern Instanzen → Modules* bzw. im *Module Control* die Git-URL hinzufügen.
   - Oder den Ordner `microebusd-mqtt` auf der SymBox in das Modulverzeichnis kopieren (auf der SymBox `/mnt/data/symcon/modules/`) und Symcon neu starten.
2. **Instanz anlegen:** *Instanz hinzufügen → „micro-ebusd MQTT Gerät“.* Als Gateway einen MQTT Server wählen oder neu anlegen lassen (mit Server Socket auf Port 1883).
3. **MQTT Server einstellen:** Im Server Socket den Port 1883 prüfen. Im MQTT Server einen Benutzer und ein Passwort vergeben.
4. **Adapter einstellen** (http://<IP des Adapters> → Configuration → MQTT):
   - URI: `mqtt://<Benutzer>:<Passwort>@<IP der SymBox>:1883/ebusd`. Das Topic hinter dem letzten `/` ist das Basis-Topic.
   - Speichern, dann *+Reboot*.
5. **Instanz konfigurieren:**
   - Basis-Topic `ebusd`, Gerät z. B. `hmu`.
   - Unter Abfragen z. B. `RunDataFlowTemp`, `RunDataReturnTemp`, `CurrentConsumedPower`, `CurrentYieldPower` eintragen, Intervall 60 s.
6. Für `ctlv3` eine zweite Instanz anlegen, z. B. mit `OutsideTemp`, `HwcStorageTemp`, `HwcTempDesired`, `Hc1FlowTemp`.

## Welche Werte sind brauchbar? (Prüflauf)

micro-ebusd meldet auf eine Abfrage nur bei Erfolg einen Wert. Kennt das Gerät eine Nachricht nicht, kommt nichts zurück, und die Variable bleibt einfach stehen. Der Katalog enthält alle Vaillant-Geräte und -Stände, an einer Anlage antwortet davon nur ein Teil.

1. **„Nachrichtenliste vom Adapter anfordern“** und warten, bis die Tabelle vollständig ist.
2. **„Prüflauf starten“:** Das Modul fragt jede lesbare Nachricht einmal ab (alle 2 s eine) und wartet danach 30 s auf die Antworten. In der Tabelle steht dann je Nachricht **Wert**, **leer (kein Fühler)** oder **keine Antwort**.
3. **„Abfrageliste = antwortende Nachrichten“** trägt nur die Nachrichten mit Wert in die Abfrageliste ein. Je Nachricht lässt sich ein eigenes Intervall wählen (Standard, 10 Minuten, 1 Stunde, 6 Stunden, 1 Tag), z. B. Temperaturen und Leistung im Standardtakt, Zähler stündlich, Einstellungen täglich. Mit „Änderungen übernehmen“ speichern.
4. **„Variablen ohne Antwort löschen“** entfernt die Variablen der Nachrichten, die im letzten Prüflauf nicht oder nur leer geantwortet haben und seitdem auch nicht von selbst mit einem Wert kamen.

Die Spalte **Letzter Wert** zeigt, wann eine Nachricht zuletzt mit Wert kam. Abgefragte Nachrichten ohne Wert seit dem Dreifachen ihres Intervalls sind als **veraltet** rot markiert. Rein passive Nachrichten (z. B. `Status01`) tauschen Regler und Wärmepumpe von selbst aus; sie lassen sich nicht abfragen und kommen in ihrem eigenen Takt.

## Energiewerte

Der Knopf „Energiewerte dieses Geräts in die Abfrageliste übernehmen“ trägt je Gerät die passenden Nachrichten mit einem sinnvollen Intervall ein (vorher die Nachrichtenliste anfordern, gespeichert wird mit „Änderungen übernehmen“). Es gibt drei Ebenen. Gemessen ist das an einer aroTHERM plus mit sensoCOMFORT 720 und Hydraulikstation; die Deutung stammt aus den Diskussionen des ebusd-Projekts (john30/ebusd-configuration #490 und #600).

| Ebene | Gerät | Nachrichten | Eigenschaften |
|---|---|---|---|
| Wärmepumpe allein | `hmu` | `StatElectricEnergySum` (Strom gesamt), `…Hc` (Heizen), `…Hwc` (Warmwasser), `…Cool` (Kühlen), `StatEnvironmentEnergySum…` (Umweltenergie) | laufend aktuell, auf Wh genau |
| Heizstab samt Standby | `vwz`, `vwzio` | `StatElectricEnergySum…` | laufend aktuell |
| Ganze Anlage | Regler (`ctlv3`, `basv3` …) | `PrEnergySumHc`, `PrEnergySumHwc`, `…ThisMonth`, `…LastMonth`, `YieldTotal` | ganze kWh, der Regler aktualisiert nur um Mitternacht |

- Die Statistik `Stat…` gilt immer für das abgefragte Gerät. Am Regler und am VR 71 ist sie nur deren Eigenverbrauch (Größenordnung 1 kWh im Monat) und sagt nichts über die Anlage.
- Kennt die Wärmepumpe die Statistik nicht, nimmt der Knopf `TotalEnergyUsage`, `YieldHc` und `YieldHwc` (dieselben Zähler in ganzen kWh).
- „Yield“ ist Umweltenergie, nicht abgegebene Wärme. Die Wärmemenge ist Umweltenergie plus Strom.
- `PrFuelSum…` ist Brennstoff und bei einer Wärmepumpe 0.
- Tageswerte für Strom gibt es in den Definitionen des Adapters nicht. Sind die Gesamtwerte als Zähler archiviert, liefert Symcon den Tagesverbrauch daraus.
- Archivierung: Gesamtwerte als „Zähler“, Tages-, Monats- und Vormonatswerte als „Standard“.

**Heizleistung und momentane Leistungszahl:** Die Wärmepumpe meldet ihre abgegebene Leistung nicht selbst. Mit der Einstellung „Heizleistung und momentane Leistungszahl aus Volumenstrom und Spreizung berechnen“ legt die Instanz der Wärmepumpe zwei Variablen an. „Heizleistung (berechnet)“ in Watt ist Volumenstrom (`BuildingCircuitFlow`, l/h) × 1,163 × (Vorlauf `RunDataFlowTemp` − Rücklauf `RunDataReturnTemp`). „Momentane Leistungszahl (berechnet)“ ist diese Heizleistung geteilt durch die elektrische Leistungsaufnahme (`RunDataElectricPowerConsumption`, ersatzweise `CurrentConsumedPower`); steht der Verdichter (unter 100 W) oder ist die Heizleistung nicht positiv, ist sie 0. Die Nachrichten müssen abgefragt werden; der Energie-Knopf trägt sie mit ein. Gerechnet wird bei jedem neuen Eingangswert; ist einer davon älter als das dreifache Abfrageintervall, bleibt der alte Stand stehen. Beim Abtauen ist die Heizleistung negativ. Die Rechnung gilt für Wasser im Heizkreis, und weil die Temperaturen nur auf 0,1 K genau kommen, schwanken beide Werte bei kleiner Spreizung um einige Prozent.

## Update von Version 1.x

Ab Version 1.3 braucht das Modul Symcon 8.1 oder neuer (`IPSModuleStrict`) und nutzt Darstellungen statt Variablenprofilen. Vorhandene Variablen werden beim nächsten empfangenen Wert automatisch umgestellt, sofort geht es mit dem Knopf „Vorhandene Variablen neu einrichten“. Der Knopf entfernt auch die nicht mehr benutzten `MEBUS.*`-Profile aus Version 1.x.

- Objekt-IDs, Archivdaten und selbst vergebene Namen bleiben erhalten.
- Die Einstellung „Payload-Kodierung“ gibt es nicht mehr; Symcon gibt die Kodierung jetzt fest vor.
- Nur beim Update von 1.0/1.1: Bei Wertelisten (bisher Text, jetzt Integer) legt Symcon die Variable neu an. Sie bekommt dabei eine **neue Objekt-ID**, und Archivdaten oder Verknüpfungen zur alten Variable gehen verloren.

## Fehlersuche

- Im Debug-Fenster der Instanz stehen alle empfangenen (`Empfangen`) und gesendeten (`Senden`) Topics.
- **Kommt gar nichts an:** Prüfen, ob der Adapter verbunden ist (Statusseite des Adapters, MQTT). Prüfen, ob die Instanz am richtigen MQTT Server hängt (Gateway).
- **micro-ebusd braucht ein gültiges Token.** Das Ablaufdatum steht auf der Statusseite des Adapters.

## PHP-Funktionen

```php
MEBUS_Get($id, 'RunDataFlowTemp');                    // einen Wert anfordern
MEBUS_PollNow($id);                                   // alle Abfrage-Nachrichten anfordern
MEBUS_RequestList($id);                               // Nachrichtenliste holen
MEBUS_QueryKnown($id);                                // jede bekannte, aktiv lesbare Nachricht einmal abfragen
MEBUS_AddEnergyPreset($id);                           // Energiewerte des Geräts in die Abfrageliste des offenen Formulars eintragen
MEBUS_StartProbe($id);                                // Prüflauf: jede lesbare Nachricht abfragen und Antwort festhalten
MEBUS_Set($id, 'HwcTempDesired', '{"value":50}');     // schreiben (nur mit Freigabe)
MEBUS_ApplyTranslations($id);                         // Namen, Einheiten, Statustexte neu setzen
MEBUS_CheckAdapter($id);                              // prüft, ob der Adapter unter der eingetragenen Adresse antwortet
```

Bei Wertelisten erwartet `MEBUS_Set` die Originalnamen aus der Definition, z. B. `{"value":"auto"}`. Beim Bedienen der Variable in Symcon übersetzt das Modul selbst.

Stand 2026-11-19 (Version 1.4): In Symcon 9.1 mit nachgestellten MQTT-Nachrichten geprüft (Empfang, Abfragen mit eigenem Intervall, Liste, Prüflauf mit Wert/leer/keine Antwort, Löschen, Schreiben bis 1.3). Der Abruf der Felddefinitionen per HTTP und die Adressprüfung sind mit einem nachgestellten Adapter geprüft. Die Energiewerte sind an einer echten Anlage gelesen und gegeneinander gerechnet; die Abfrage mit `{}` läuft an einer echten Anlage, der Energie-Knopf, die berechnete Heizleistung und die Leistungszahl sind nur nachgestellt geprüft. Die Übersetzungen sind eine eigene Übertragung der englischen Definitionen.
