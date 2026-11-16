# micro-ebusd MQTT für IP-Symcon

Dieses Symcon-Modul bindet den **eBUS Adapter Shield (v5/C6) mit micro-ebusd** über den eingebauten **Symcon MQTT Server** an. Den großen ebusd-Dienst und seine HTTP-Schnittstelle (`/data`) braucht es dafür nicht.

- Eine Instanz pro eBUS-Gerät (circuit), z. B. bei einer Vaillant-Anlage: `hmu` (Wärmepumpe), `ctlv3` (sensoCOMFORT 720), `vr_71`, `vwzio`, `broadcast` (z. B. Außentemperatur).
- Eine weitere Instanz mit **leerem Gerät** zeigt die Statuswerte des Adapters selbst (uptime, signal, rssi …).
- Empfangene Werte werden automatisch als Variablen angelegt. Zahlen werden immer als Float angelegt; Namen mit „Temp“ bekommen das Profil `~Temperature`.
- Nachrichten aus der Liste „aktiv abfragen“ werden im eingestellten Intervall per `…/get` angefordert.
- Schreiben ist nur möglich, wenn „Schreiben erlauben“ an ist und der Adapter die Nachricht als schreibbar meldet. Dafür vorher „Nachrichtenliste anfordern“ drücken.

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
   - Oder den Ordner `symcon-microebusd-mqtt` auf der SymBox in das Modulverzeichnis kopieren (auf der SymBox `/mnt/data/symcon/modules/`) und Symcon neu starten.
2. **Instanz anlegen:** *Instanz hinzufügen → „micro-ebusd MQTT Gerät“.* Gibt es noch keinen MQTT Server, legt Symcon ihn automatisch an, zusammen mit einem Server Socket auf Port 1883.
3. **MQTT Server einstellen:** Im Server Socket den Port 1883 prüfen. Im MQTT Server einen Benutzer und ein Passwort vergeben.
4. **Adapter einstellen** (http://<IP des Adapters> → Configuration → MQTT):
   - URI: `mqtt://<Benutzer>:<Passwort>@<IP der SymBox>:1883/ebusd`. Das Topic hinter dem letzten `/` ist das Basis-Topic.
   - Speichern, dann *+Reboot*.
5. **Instanz konfigurieren:**
   - Basis-Topic `ebusd`, Gerät z. B. `hmu`.
   - Unter Abfragen z. B. `RunDataFlowTemp`, `RunDataReturnTemp`, `CurrentConsumedPower`, `CurrentYieldPower` eintragen, Intervall 60 s.
6. Für `ctlv3` eine zweite Instanz anlegen, z. B. mit `OutsideTemp`, `HwcStorageTemp`, `HwcTempDesired`, `Hc1FlowTemp`.

## Fehlersuche

- Im Debug-Fenster der Instanz stehen alle empfangenen (`Empfangen`) und gesendeten (`Senden`) Topics.
- **Payload-Kodierung:** Je nach Symcon-Version reicht der MQTT Server die Payload als HEX oder als Text weiter. Mit „Automatisch“ erkennt das Modul das an der ersten JSON-Nachricht. Klappt das Senden nicht (der Adapter reagiert nicht auf `get`), die Kodierung fest auf HEX bzw. Text stellen.
- **Kommt gar nichts an:** Prüfen, ob der Adapter verbunden ist (Statusseite des Adapters, MQTT). Prüfen, ob die Instanz am richtigen MQTT Server hängt (Gateway).
- **micro-ebusd braucht ein gültiges Token.** Das Ablaufdatum steht auf der Statusseite des Adapters.

## PHP-Funktionen

```php
MEBUS_Get($id, 'RunDataFlowTemp');                    // einen Wert anfordern
MEBUS_PollNow($id);                                   // alle Abfrage-Nachrichten anfordern
MEBUS_RequestList($id);                               // Nachrichtenliste holen
MEBUS_Set($id, 'HwcTempDesired', '{"value":50}');     // schreiben (nur mit Freigabe)
```

Stand 2026-11-16, ungetestet: Der Code wurde nicht in Symcon ausgeführt. Geprüft sind nur das JSON und die Klammern.
