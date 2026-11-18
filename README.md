# micro-ebusd MQTT für Symcon

Dieses Symcon-Modul bindet den **eBUS Adapter Shield (v5/C6) mit micro-ebusd** über den eingebauten **Symcon MQTT Server** an. Den großen ebusd-Dienst und seine HTTP-Schnittstelle (`/data`) braucht es dafür nicht.

- Eine Instanz pro eBUS-Gerät (circuit), z. B. bei einer Vaillant-Anlage: `hmu` (Wärmepumpe), `ctlv3` (sensoCOMFORT 720), `vr_71`, `vwzio`, `broadcast` (z. B. Außentemperatur).
- Eine weitere Instanz mit **leerem Gerät** zeigt die Statuswerte des Adapters selbst (uptime, signal, rssi …).
- Empfangene Werte werden automatisch als Variablen angelegt.
- **Deutsche Namen, Einheiten und Statustexte:** Das Modul bringt einen Katalog der Vaillant-Definitionen mit (`MicroEbusdMQTT/vaillant.json`, 630 Nachrichten für hmu, ctlv3, vr_71, vwzio, broadcast). Daraus bekommen die Variablen deutsche Namen und Einheiten (°C, kW, kWh, bar, h …). Wertelisten wie Betriebsarten oder Statuscodes werden zu Integer-Variablen mit deutschen Texten, z. B. Aus / Zeitgesteuert / Manuell. Das Modul nutzt dafür Darstellungen, keine Variablenprofile. Die Übersicht aller Nachrichten steht in [NACHRICHTEN.md](NACHRICHTEN.md).
- Nachrichten aus der Liste „aktiv abfragen“ werden im eingestellten Intervall per `…/get` angefordert.
- Schreiben ist nur möglich, wenn „Schreiben erlauben“ an ist und der Adapter die Nachricht als schreibbar meldet. Dafür vorher „Nachrichtenliste anfordern“ drücken. Bei Nachrichten mit mehreren Feldern schickt das Modul alle Felder mit den zuletzt empfangenen Werten zurück; fehlt einer, schreibt es nicht.

Topics von micro-ebusd laut https://token.ebusd.eu/mqtt:

| Topic | Bedeutung |
|---|---|
| `<Basis>/<circuit>/<name>` | Wert als JSON, z. B. `{"value":35.56}` |
| `<Basis>/<circuit>/<name>/get` | Wert anfordern |
| `<Basis>/<circuit>/<name>/set` | Wert schreiben (JSON-Objekt) |
| `<Basis>/<circuit>/list` | Nachrichtenliste anfordern; die Antwort kommt auf `<Basis>/<circuit>/any/found` |

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
MEBUS_StartProbe($id);                                // Prüflauf: jede lesbare Nachricht abfragen und Antwort festhalten
MEBUS_Set($id, 'HwcTempDesired', '{"value":50}');     // schreiben (nur mit Freigabe)
MEBUS_ApplyTranslations($id);                         // Namen, Einheiten, Statustexte neu setzen
```

Bei Wertelisten erwartet `MEBUS_Set` die Originalnamen aus der Definition, z. B. `{"value":"auto"}`. Beim Bedienen der Variable in Symcon übersetzt das Modul selbst.

Stand 2026-11-18 (Version 1.4): In Symcon 9.1 mit nachgestellten MQTT-Nachrichten geprüft (Empfang, Abfragen mit eigenem Intervall, Liste, Prüflauf mit Wert/leer/keine Antwort, Löschen, Schreiben bis 1.3). Noch nicht an einem echten Adapter geprüft. Die Übersetzungen sind eine eigene Übertragung der englischen Definitionen.
