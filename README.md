# Lernwerkstatt v1.1 – Datenbankpräfix

Bei der Neuinstallation kann ein Tabellenpräfix festgelegt werden (Standard `zaiko_`). Alle 14 Tabellen erhalten den Präfix. Bestehende gleichnamige Tabellen werden vor der Installation geprüft und nicht überschrieben. Ein Präfix wird in `config.php` als `DB_PREFIX` gespeichert. Version 1.0 enthält keine automatische Migration; die neue Installation verwendet eigene Tabellen. Vorhandene Daten vorher sichern.

# Lernwerkstatt – adaptive Inventar- und Ausleihverwaltung

**Version 1.0.0-beta (09.10.2026)**. Eigenständige PHP/MySQL-Webanwendung ohne Framework/Composer.

## Installation

1. Leere Datenbank in MySQL 8+ oder MariaDB 10.5+ anlegen (Zeichensatz `utf8mb4`).
2. Alle Dateien und den Ordner `uploads/` per FTP/SFTP in einen eigenen Webserver-Ordner hochladen. Versteckte `.htaccess`-Dateien mitübertragen.
3. PHP 8.1+ mit `pdo_mysql`, `mbstring`, `fileinfo`, Sessions und JSON aktivieren; für die optionale KI ist zusätzlich `curl` nötig. `config.php` muss während der Installation im Anwendungsverzeichnis erstellt werden können, `uploads/` muss beschreibbar sein.
4. `https://IHRE-DOMAIN/PFAD/install.php` öffnen, Zugangsdaten der **leeren** Datenbank und ein Administrator-Passwort (mindestens 12 Zeichen) eingeben.
5. Danach mit dem Administrator anmelden. **`install.php` und `schema.sql` auf dem Server löschen** (lokale Sicherung behalten). Schreibrechte für `config.php` wieder entziehen.
6. Unter **Administration → Dimensionen** Dimensionen und eindeutige Definitionen einrichten, danach Lagerorte, Benutzer und Gegenstände anlegen.
7. Für jedes Objekt Merkmalswerte (0–1), Zuverlässigkeit (0–1) und Anwendbarkeit pflegen. Nicht bekannte Eigenschaften können auf 0,5 mit Konfidenz 0 bleiben.

### Optional: KI-Initialisierung

Unter **Administration → Einstellungen** den KI-Modellnamen (Vorgabe `gpt-4o-mini`) und einen API-Schlüssel konfigurieren. Alternativ Server-Umgebungsvariable `OPENAI_API_KEY` setzen. Nach Anlegen eines Gegenstands auf **Bearbeiten → Mit KI klassifizieren** klicken. Die KI erzeugt einen *Vorschlag*, der alle vorhandenen Objektvektoren überschreibt und manuell überprüft werden sollte. **Titel, Beschreibung, Kategorie, Tags und Dimensionsdefinitionen werden bei Nutzung an die OpenAI-API übertragen.** Kosten gemäß verwendeter API. Ohne API läuft die Anwendung vollständig mit manueller Klassifikation.

> Das Schlüssel-Feld in der Anwendung speichert den API-Schlüssel in der Datenbank. Die Umgebungsvariable ist sicherheitstechnisch vorzuziehen. Niemals sensible personenbezogene Daten an externe Klassifikationsdienste senden.

## Rollen

- **Administrator**: vollständig verwalten (Gegenstände, Fotos, Dimensionen, Lagerorte, Nutzer, Suchbegriffe, Assoziationen, Protokolle, Einstellungen, Ausleihen).
- **Redakteur**: Gegenstände und Fotos bearbeiten, Ausleihen abschließen, Rückgaben erfassen und Ausleihdaten ändern, KI-Klassifikation auslösen.
- **Betrachter**: Suchen, Details sehen und unverbindlich in den Warenkorb legen. Abschließen nur durch Redakteur/Administrator.

## Suche und Training

- Direkte Textsuche in Titel, Schlagworten und Beschreibung.
- Jeder vollständige Suchausdruck wird als Keyword gespeichert; erste Position wird anhand textuell passender inventarisierter Gegenstände geschätzt.
- Vektor-Ähnlichkeit = 1 minus gewichtete normalisierte euklidische Distanz. Dimensionen werden nur mit positiver Konfidenz beider Seiten und bei Anwendbarkeit gewichtet.
- Gesamtranking = 50 % Vektor, 35 % Text, 15 % gelernte Assoziationen. Ohne initiale Daten ist die Textkomponente entscheidend. Die Gewichte sind Startheuristiken, kein validiertes Relevanzmodell.
- Klicks und Warenkorb-Aktionen führen zu schwächerem Feedback; abgeschlossene Ausleihen zu stärkerem Feedback. Gelernte Objektwerte werden nur mit sehr kleiner Lernrate angepasst; Basiswerte bleiben stabil.
- Aufeinanderfolgende Suchen innerhalb von 15 Minuten in derselben Sitzung erzeugen eine schwache Keyword-Assoziation.
- Administratoren können Keywords und Assoziationen korrigieren/löschen sowie Lernwerte zurücksetzen.

## Bestände und Rückgabe

Bestand = Inventarmenge; verfügbar = Bestand minus *noch nicht zurückgegebene* Ausleihmengen. Abschlüsse laufen in einer Datenbanktransaktion mit Bestandsprüfung und Sperre der betroffenen Objektzeilen. Eine Ausleihe besteht aus mehreren Positionen; Teilrückgaben sind möglich. Administratoren können Positionen korrigieren oder eine Ausleihe löschen. Gelernte Suchsignale werden beim Löschen einer Ausleihe **nicht rückwirkend entfernt**.

## Datenschutz und Betrieb

- Diese Version benötigt eine Anmeldung für alle Seiten. Die Suche protokolliert Suchtexte, Zeit, angemeldete Nutzer und Sitzungskennungen; Klicks und Ausleihen können verknüpft werden.
- Unter Administration → Einstellungen lassen sich Such-/Klickprotokolle löschen, die aggregierten Lernwerte bleiben jedoch bestehen.
- Bitte Datenschutz-/Aufbewahrungsregeln der Schule prüfen und nur notwendige Daten über Entleiher speichern.
- Passwort-Hashes über `password_hash()`; Sessions mit HttpOnly und SameSite; CSRF-Schutz für POST; parametrisierte SQL-Queries; Dateitypprüfung für Bilder.
- HTTPS ist dringend empfohlen. Für strikt erzwungenes HTTPS eine Weiterleitung im Webserver/Hosting aktivieren. Die Anwendung erzwingt HTTPS nicht selbst.
- Regelmäßige Backups der MySQL-Datenbank und des Ordners `uploads/` anlegen. `config.php` gesondert sichern.
- Für Nginx/andere Server `.htaccess`-Regeln nachbilden: Directory Listing abschalten, `config.php`, `schema.sql`, `README.md` sperren; Uploads niemals als Skripte ausführen.

## Bekannte Grenzen der Beta-Version

- Die anfängliche Keywordklassifikation basiert auf **Textübereinstimmung im Inventar** und ist keine vollwertige Sprachverständnis-KI. Ohne Texttreffer startet der Vektor neutral und lernt durch Auswahl/Ausleihe.
- Bei vielen Tausend Einträgen prüft die Suche alle Gegenstände und Dimensionen. Später kann eine vorab berechnete Vektormatrix, Cache oder ANN-Index ergänzt werden.
- Die Sitzungskorrelation ist eine Heuristik und kann auch thematisch unverbundene Suchen verbinden.
- Administratoren müssen starke, voneinander unterscheidbare Dimensionen definieren. Der Suchscore ist kein kalibrierter Wahrscheinlichkeitswert.
- Es gibt noch keine automatisierte Ende-zu-Ende-Testumgebung; vor realem Schuleinsatz Installation und Leihabläufe mit einer Testdatenbank prüfen.

## Updates

**Datenbankmigrationen müssen ausdrücklich geprüft werden.** Bei reinem Dateiaustausch bleibt die Datenbank unverändert. Für diese Erstinstallation wird `schema.sql` verwendet. Vor jeder Aktualisierung Backup anlegen; neue Versionen müssen dokumentieren, ob ein SQL-Migrationsschritt nötig ist.
