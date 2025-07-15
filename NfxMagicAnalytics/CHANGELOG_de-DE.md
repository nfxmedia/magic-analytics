# 4.1.2
- Fix: Profitberechnung für netto und taxfree Bestellungen
- Fix: Error in verschiedenen Statistken bei Bestellungen mit Artikelanzahl 0

# 4.1.1
- Neu: Mehr Daten in Umsatz nach Partner Statistik: Daten für Bestellungen ohne Partner Code
- Neu: Mehr Daten in Umsatz nach Kampagne Statistik: Daten für Bestellungen ohne Kampagne
- Fix: Fehler in den Statistiken der Abbruchanalyse
- Fix: Fehler bei Berechnungen in den Pickware Erp Pro Retour Statistiken

# 4.1.0
- Neu: 5 neue Statistiken, die Teilretouren und Refunds berücksichtigen - aber nur bei Nutzung von Pickware Erp Pro Retouren
- Neu: Lieferadresse in Tabelle der zugehörigen Bestellungen
- Neu: Gutscheinstatistik jetzt 2 Übersichten, gruppiert nach Aktionscode und nach Rabattaktion - besser bei individuellen Codes
- Neu: Mehr Daten für Produktimpression Statistik - Anzahl Verkäufe und Conversion Rate
- Neu: Neuer zusätzlicher Filter für Produktimpressions zum Ausschliessen von Partnercodes - um z.B. importierte Marktplatzbestellungen nicht zu berücksichtigen
- Neu: Neuer zusätzlicher Filter für Conversion Statistiken zum Ausschliessen von Partnercodes - um z.B. importierte Marktplatzbestellungen nicht zu berücksichtigen
- Neu: In Statistik "Mit auslaufendem Lagerbestand" nicht aktive Produkte ausgeschlossen
- Neu: Unterstützung für komprimierte payload Spalte in cart Tabelle für Abbruchstatistiken
- Neu: Mehr Daten für Umsatzstärkste Kunden: Letztes Login
- Fix: Fehler in Profitberechnung in Bestellungentabelle
- Fix: In Umsatz nach Kategorien wird die Kategorieauswahl nicht mehr nach jeder Änderung geschlossen
- Fix: Kategorien mit Produkten zugewiesen per dynamischer Produktgruppe in Kategorie-Statistiken b

# 4.0.9
- Neu: CSV export von Daten zugehöriger Bestellungen
- Neu: CSV export von Daten zugehöriger Produkte
- Neu: Profit Spalte für zugehörige Bestellungen
- Neu: Bestellungen nach Uhrzeit beachtet jetzt Zeitzone
- Neu: Speichern des Rechnungsdatums für Statistiken nach Rechnungsdatum jetzt abschaltbar über Schalter "Zusätzliche Orderdaten speichern wenn möglich"
- Neu: Speichern des Rechnungsdatums funktioniert jetzt auch, wenn Datum nicht als String sondern als Objekt kommt
- Fix: Fehler bei den Werten der Y-Achse in einigen Charts berichtigt

# 4.0.8
- Neu: Produkte mit wenig/hohem Lagerbestand jetzt ohne inaktive Produkte
- Neu: Mehr daten für Umsatz nach Produkten Statistik
- Neu: Handbuch Link in Action Bar
- Neu: Zusätzlicher Filter für Einzel-Produkt-Statistik: Varianten zusammenfassen
- Fix: Abbruchanalyse angepasst an letzte Änderungen von Shopware an der cart Tabelle
- Fix: Spalten in csv Export in Umsatz nach produkten mit Varianten zusammenfassen

# 4.0.7
- Fix: Fehler bei, Lieferstatus-Filter
- Fix: Fehler bei falscher language id von der administration

# 4.0.6
- Fix: Kompabilitätsproblem beim Entfernen des Foreign Key Contraints

# 4.0.5
- Neu: Eine neue Statistik in Kategorie Bestellungen: Bestellungen (Quartal)
- Neu: Weitere Filter in den Statistiken der Kategorie Nach Rechnungsdatum
- Fix: Fehler in Besucher Zugriffsquellen
- Fix: Foreign Key Constraint aus Rechnungsdatum Tabelle entfernt

# 4.0.4
- Fix: Fehler in Altersverteilung Statistik behoben
- Neu: Kategorie Statistiken nach Rechnungsdatum
- Neu: 4 neue Statistiken in Kategorie nach Rechnungsdatum:
- Bestellungen (täglich)
- Bestellungen (monatlich)
- Bestellungen (Quartal)
- Umsatz nach Rechnungsland
- Neu: 3 Statistiken überarbeitet für mehr Daten:
- Bestellungen (täglich)
- Bestellungen (monatlich)
- Umsatz nach Produkten

# 4.0.3
- Fix: Fehler in Abbruchanalyse behoben

# 4.0.2
- Fix: Fehler in Abbruchanalyse behoben

# 4.0.1
- Fix: Fehler im Cronjob behoben

# 4.0.0
- Anpassung an Shopware 6.6

# 3.0.8
- Neu: In der Statistik abgebrochene Warenkörbe Artikel jetzt mit Produktnummer und Link zur Produktdetailseite

# 3.0.7
- Fix: Einige kleinere Fehler beseitigt

# 3.0.6
- Fix: Problem bei Besuchererfassung von Saleschannels mit Unterverzeichnissen in der URL
- Neu: Statistik Umsatz nach Besteller Typ (Gast/Registriert)

# 3.0.5
- Neu: IP-Blacklist in den Plugin-Einstellungen für Besucherzählung
- Neu: E-Mail-Spalte in der Kundenverkaufsstatistik
- Neu: Benutzerabhängige Dashboard-Statistik-Einstellungen
- Neu: Favoriten-Statistiken benutzerabhängig wählbar
- Fix: Erfassung der Besucherdaten für SW 6.5 verbessert
- Neu: Option zur Anzeige von Parent Produkten anstelle von Varianten-Produkten mit zusammengefassten Daten
- Neu: Option für Produkte ohne Umsatz in der Produktgewinnstatistik ausblenden
- Neu: Neue Funktion zur Anzeige bestellter Produkte: Für einige Statistiken wird ein Link in der Aktionsspalte zu einer Übersicht, die die zugrunde liegenden Produkte anzeigt
- Fix: Währungsfehler in der Aktionsstatistik behoben
- Neu: Viele weitere Tabellen jetzt sortierbar
- Neu: Zusätzliche Berechtigung für Dashboard Statistiken, so können Rechte für Dashboard und Statistik Modul getrennt vergeben werden
- Fix: Kunden online Bug beseitigt

# 3.0.4
- Fix: Fehler in Variant und Produkten mit Filter Statistiken beseitigt

# 3.0.3
- Neu: 2 neue Kategorie Statistiken
- Fix: Kleinere Fehler beseitigt

# 3.0.2
- Fehler in den Statistiken, die die DB Tabelle order delivery nutzen, beseitigt

# 3.0.1
- Neue Statistiken in Marketing: Conversion (täglich), Conversion (monatlich)
- Neues Feature zusätzliche Filter: Neuer Menüpunkt in Sidebar unter Optionen, noch nicht bei allen Statistiken aktiv,
  führt zu für die ausgewählte Statistik individuellen Filtern, mehr dieser Filter werden kommen
- Neues Feature zugehörige Bestellungen anzeigen: Bei einigen Statistiken Verlinkung in der Action Spalte  
  zu einer Übersicht, die die zugehörigen Bestellungen anzeigt, wird auf mehr Statistiken ausgeweitet
- Aktualisieren und CSV Export Buttons in Header verschoben
- Statistiken Umsatz nach Kampagne und nach Partner nach Marketing verschoben
- Sortierung nach Spalten in ausgewählten Statistiken, mehr werden folgen

# 3.0.0
- Anpassungen an Shopware 6.5

# 2.3.8
- Neue Statistik: Umsatz nach Währung
- Erweiterung der 3 Statistiken Produktimpressionen, Herstellerimpressionen, Kategorieimpressionen mit:
- Unterscheidung von angemeldeten und nicht angemeldeten Besuchern
- Berücksichtigung des Kundengruppenfilters

# 2.3.7
- Fehler in der Statistik "Alle abgebrochenen Warenkörbe" beseitigt

# 2.3.6
- Statistik Alle Abgebrochenen Warenkörbe an Shopware Update angepasst
- 3 neue Statistiken: Umsatz nach Steuerrate, Umsatz nach Anrede, Kunden nach Anrede
- Verbesserung der Besuchererfassung
- Shopware ACL Rechtesystem eingeführt: Neue Berechtigung Inhalte -> Coolbax Statistik 
  (zu finden unter Einstellungen -> Benutzer & Rechte -> Rollen -> Bearbeiten)
  Diese muss auf mindestens Ansehen gesetzt sein, sonst kein Menüpunkt Statistik und keine Statistik auf Dashboard!
  Wenn Dashboard Statistiken bearbeitet werden sollen, muss Coolbax Statistik auf Bearbeiten gesetzt sein.
  Benutzer mit Administrator Rechten müssen nichts ändern.

# 2.3.5
- Fehler beseitigt in Gutschein Statistiken
- Fehler beseitigt in Statistik Optionen
- Fehler beseitigt in responsiveness
- Neue Statistik: Derzeitige Besucher und Logins

# 2.3.4
- Fehler beseitigt in den Statistics Optionen, Plugin Settings
- Besucher Erfassung Schreibfehler (write exceptions) abgefangen
- Performance verbessert

# 2.3.3
- Fehler beim Zählen der Kategorie Klicks beseitigt

# 2.3.2
- Neuer globaler Filter nach Kundengruppen
- Verbesserung der Erstbestellungen Statistik
- Mehr Einstellungen für CSV Export hinzugefügt

# 2.3.1
- Verbesserung der Datumsanzeige
- Verbesserung bei der Datenerfassung

# 2.3.0
- Neue Statistik "Cross-Selling" hinzugefügt unter Produkte

# 2.2.9
- Bug Fixing für Dashboard Statistiken

# 2.2.8
- Verbesserungen und Bug Fixing für Dashboard Statistiken
- Schnellübersicht erweitert um Besucher und Klicks

# 2.2.7
- Verbesserte Namensanzeige bei Varianten
- Kompatibilitätsproblem mit SW <= 6.4.5 bei Datumsauswahl beseitigt
- Mehr vordefinierte Datumsbereiche** 

# 2.2.6
- Abgebrochene Warenkörbe verbessert mit Produktanzeige
- Aktionscode zu Gutscheinstatistik ergänzt
- Neue Statistik: Lexikon Impressions
- Neue Statistik: Einzel-Produkt Statistiken

# 2.2.5
- Fehler beim Erfassen der Besucher beseitigt

# 2.2.4
- Erstbestellungen-Statistik verbessert

# 2.2.3
- Fehler beim Erfassen der Besucher beseitigt
- Erstbestellungen-Statistik verbessert

# 2.2.2
- Fehler bei Produkten ohne Hersteller gelöst

# 2.2.1
- Neue Statistiken für Produkt Klicks, Kategorie Klicks, Hersteller Klicks, Seiten Klicks, Besucher

# 2.2.0
- Fix für Chart Mouseover Anzeige Fehler

# 2.1.9
- Summen-Anzeige in der Schnellübersicht verbessert

# 2.1.8
- Anpassungen für das Erweiterungsplugin

# 2.1.7
- Fehler bei Erstbestellungen gefixt
- Verbesserungen für Abbruchanalyse

# 2.1.6
- Verbesserung der Performance
- Chart-Typ Vorauswahl in der Plugin-Konfiguration gilt jetzt auch für das Dashboard

# 2.1.5
- Neues Feature: Optionale Anzeige von auszuwählenden Statistiken auf dem Dashboard
- Kleinere Fehlerkorrekturen
- Neue Statistiken
- Neue Spalte in Schnellübersicht: Erstbestellungen

# 2.1.4
- Neue Optionen: Auswahl Zahlungsstatus und Lieferstatus, die nicht in den Statistiken berücksichtigt werden
- Neue Spalte in Schnellübersicht, die anzeigt, wie viele der neuen Kunden des Tages schon mindestens eine Bestellung getätigt haben
- Neue Option: Vorauswahl des Chart-Typs für Statistiken mit Chart-Typ-Auswahl

# 2.1.3
- Fehler mit dem End-Datum in einigen Statistiken behoben

# 2.1.2
- Verbesserung bei der Datenerfassung von Bestellungen (Gerät, Browser, OS)

# 2.1.1
- Fehler beseitigt in der Statistik Umsatz nach Zahlungsart
- Verbesserungen für Zeitzonen Bugs

# 2.1.0
- Anpassung der Verlinkung an neue Route bei Gutscheinstatistiken
- Verbesserungen bei der Verarbeitung der Datumswerte

# 2.0.9
- Verbesserungen in der Statistik Produkt Ertrag

# 2.0.8
- Bug beseitigt in den Browser, Device and OS Statistiken

# 2.0.7
- Bugs beseitigt: bei der Suche ohne Suchterm, Zeitzonen Probleme, Probleme bei der Hersteller Statistik mit Produkten ohne Hersteller

# 2.0.6
- Bug bei line items mit falscher orderVersionId umgangen

# 2.0.5
- Fehler im CSV Export der Umsatz nach Produkt Statistik berichtigt

# 2.0.4
- Änderungen bei einigen Snippets
- Änderungen beim csv Export - Zwischenspeicherung jetzt mit SW Filesystem
- Trennzeichen für csv Files wählbar in der config

# 2.0.3
- Fehler in Zahlungsart und Hersteller Statistik beseitigt
- Performance verbessert

# 2.0.2
- 2 kleine Fehler beseitigt
- Snippet Produkt Stream geändert zu dynamische Produktgruppe

# 2.0.1
- 9 neue Statistiken hinzugefügt (Suche, Devices, Varianten, Produktgewinn, ...)
- Beginnen mit der Speicherung von Suchanfragendaten

# 2.0.0
- Anpassungen für das Shopware 6.4 Update

# 1.0.7
- Verbesserung der Darstellung der Namen von Varianten

# 1.0.6
- Änderungen beim Erstellen des CSV Export Pfades
- Änderungen beim CSV Export und Download
- Neue Option: Brutto/Netto Auswahl in der Konfiguration
- Neue Option: Auswahl Orderstatus, die nicht in den Statistiken berücksichtigt werden
- Verbesserungen bei den Berechnungen der Gutscheinstatistik

# 1.0.5
- Vorbereitung des Plugins für Erweiterungen

# 1.0.4
- Korrektur beim Setzen der Default Configs während des Aktivierungsprozesses

# 1.0.3
- Korrektur beim Einlesen des Datumsfilters für bestimmte Servereinstellungen

# 1.0.2
- CSV Export der Statistikdaten ergänzt
- Balkendiagramm als weitere Option ergänzt
- Navigation Tree neu geordnet mit Gruppen
- Viele neue Statistiken
- Erweiterung alter Statistiken

# 1.0.1
- Fehler bei Namen von Varianten beseitigt
- Neue Spalte Produktnummer in Tabellen mit Produkten

# 1.0.0
- Erste Version des Plugin für Shopware 6
