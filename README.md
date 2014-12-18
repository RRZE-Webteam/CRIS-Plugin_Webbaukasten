CRIS-Plugin_Webbaukasten
========================

Einbinden von Daten aus der Forschungsdatenbank CRIS in Webseiten

## Version 1.0 (Stand 18.12.2014):

- Installation analog zu übrigen Webbaukasten-Plugins in /vkdaten/tools/cris/
- Folgende Includes verfügbar:
  - Publikationsliste (automatisch nach Jahren gegliedert):<br />
  <code><!--#include virtual="/vkdaten/tools/cris/publikationsliste.php" --></code>
  - Publikationsliste nach Typen gegliedert:<br />
  <code><!--#include virtual="/vkdaten/tools/cris/publikationsliste-typ.php" --></code>
  - Publikationslisten für einzelne Publikationstypen (alle 8 derzeit in CRIS erfassten):<br />
    <code><!--#include virtual="/vkdaten/tools/cris/publikationsliste-buecher.php" --></code><br />
    <code><!--#include virtual="/vkdaten/tools/cris/publikationsliste-zeitschriften.php" --></code><br />
    <code><!--#include virtual="/vkdaten/tools/cris/publikationsliste-tagungsbeitraege.php" --></code><br />
    <code><!--#include virtual="/vkdaten/tools/cris/publikationsliste-sammelbandbeitraege.php" --></code><br />
    <code><!--#include virtual="/vkdaten/tools/cris/publikationsliste-herausgeberschaften.php" --></code><br />
    <code><!--#include virtual="/vkdaten/tools/cris/publikationsliste-abschlussarbeiten.php" --></code><br />
    <code><!--#include virtual="/vkdaten/tools/cris/publikationsliste-uebersetzungen.php" --></code><br />
    <code><!--#include virtual="/vkdaten/tools/cris/publikationsliste-andere.php" --></code>
  - Publikationslisten für einzelne Jahre:<br />
    <code><!--#include virtual="/vkdaten/tools/cris/publikationsliste-jahr.php/2014" --></code>
  - Organigramm:<br />
    <code><!--#include virtual="/vkdaten/tools/cris/organigramm.php" --></code>
  - Mitarbeiterliste:<br />
    <code><!--#include virtual="/vkdaten/tools/cris/mitarbeiterliste.php" --></code>
  - Personen-Detailseite:<br />
    <code><!--#include virtual="/vkdaten/tools/cris/person.php" --></code>
- Konfiguration über /vkdaten/cris.conf (über NavEditor > Erweitert > Konfiguration editierbar):<br />
  <code>CRISOrgNr        142477</code><br />
  <code>Zeige_Publikationen        1   #Publikationsliste in Personen-Detail-Ansicht anzeigen? 1=ja, 0=nein</code><br />
  <code>Zeige_Auszeichnungen        1   #Auszeichnungen in Personen-Detail-Ansicht anzeigen? 1=ja, 0=nein</code><br />
  <code>Pfad_Personenseite        /cris/person.shtml   #für Links von Publikations- und Mitarbeiterlisten auf Personen-Detailseite</code><br />
  <code>Cache_Zeit        18000   #Gültigkeitsdauer der Cache-Dateien in Sekunden</code><br />
  <code>Ignoriere_Jobs	FoDa-Administrator/in|Andere	#Funktionen, die im Organigramm nicht aufgef&uuml;hrt werden sollen</code>
- Cache
- Suche der CRIS-OrgNr anhand der FAU-OrgNr

## Todos
- <strike>Reihenfolge Jobs in Organigramm</strike> Done.
- <strike>in Konfig-Datei Jobs definieren, die im Organigramm nicht angezeigt werden</strike> Done.
- Plugin auch für Wordpress


## Mögliche Erweiterungen:
- Bild auf Personen-Detailseite einbinden
  - aus CRIS übernehmen (noch unklar, ob überhaupt möglich) oder
  - lokal auf dem Webauftritt analog zu UnivIS-Plugin
- Eigenen Text in Personenseite einbinden (analog zu UnivIS-Plugin)
- Einbindung FAU-Visitenkarten
