CRIS-Plugin_Webbaukasten
========================

Einbinden von Daten aus der FAU-Forschungsdatenbank CRIS in Webseiten

## Version 1.1 (Stand 25.02.2015):

- Installation analog zu den übrigen Webbaukasten-Plugins in /vkdaten/tools/cris/
- Folgende Includes verfügbar:
  - Publikationsliste (automatisch nach Jahren gegliedert):<br />
  <code><!--#include virtual="/vkdaten/tools/cris/publikationsliste.php" --></code>
  - Publikationsliste nach Typen gegliedert:<br />
  <code><!--#include virtual="/vkdaten/tools/cris/publikationsliste.php/typ" --></code>
  - Publikationslisten für einzelne Publikationstypen (alle 8 derzeit in CRIS erfassten):<br />
    <code><!--#include virtual="/vkdaten/tools/cris/publikationsliste.php/buecher" --></code><br />
    <code><!--#include virtual="/vkdaten/tools/cris/publikationsliste.php/zeitschriften" --></code><br />
    <code><!--#include virtual="/vkdaten/tools/cris/publikationsliste.php/tagungsbeitraege" --></code><br />
    <code><!--#include virtual="/vkdaten/tools/cris/publikationsliste.php/sammelbandbeitraege" --></code><br />
    <code><!--#include virtual="/vkdaten/tools/cris/publikationsliste.php/herausgeberschaften" --></code><br />
    <code><!--#include virtual="/vkdaten/tools/cris/publikationsliste.php/abschlussarbeiten" --></code><br />
    <code><!--#include virtual="/vkdaten/tools/cris/publikationsliste.php/uebersetzungen" --></code><br />
    <code><!--#include virtual="/vkdaten/tools/cris/publikationsliste.php/andere" --></code>
  - Publikationslisten für einzelne Jahre:<br />
    <code><!--#include virtual="/vkdaten/tools/cris/publikationsliste.php/2014" --></code>
  - Organigramm:<br />
    <code><!--#include virtual="/vkdaten/tools/cris/organigramm.php" --></code>
  - Mitarbeiterliste:<br />
    <code><!--#include virtual="/vkdaten/tools/cris/mitarbeiterliste.php" --></code>
  - Personen-Detailseite:<br />
    <code><!--#include virtual="/vkdaten/tools/cris/person.php" --></code>
- Konfiguration über /vkdaten/cris.conf (über NavEditor > Erweitert > Konfiguration editierbar):<br />
  <code>CRISOrgNr			1234567</code><br />
  <code>Zeige_Publikationen	1   #Publikationsliste in Personen-Detail-Ansicht anzeigen? 1=ja, 0=nein</code><br />
Reihenfolge_Publikationen	Journal article|Article in edited volumes|Translation|Book|Editorial|Conference Contribution|Thesis|Other
  <code>Pfad_Personenseite	/cris/person.shtml   #für Links von Publikations- und Mitarbeiterlisten auf Personen-Detailseite</code><br />
  <code>Cache_Zeit			18000   #Gültigkeitsdauer der Cache-Dateien in Sekunden</code><br />
  <code>Ignoriere_Jobs		FoDa-Administrator/in|Andere	#Funktionen, die im Organigramm nicht aufgef&uuml;hrt werden sollen</code>
- Cache
- Suche der CRIS-OrgNr anhand der FAU-OrgNr

## Todos
- <strike>Reihenfolge Jobs in Organigramm</strike> Done.
- <strike>in Konfig-Datei Jobs definieren, die im Organigramm nicht angezeigt werden</strike> Done.
- <strike>Plugin auch für Wordpress</strike> Done.
- <strike>Reihenfolge der Publikationstypen konfigurierbar machen</strike>
- Name der Person in URL statt ID

## Mögliche Erweiterungen:
- Bild auf Personen-Detailseite einbinden
  - aus CRIS übernehmen (noch unklar, ob überhaupt möglich) oder
  - lokal auf dem Webauftritt analog zu UnivIS-Plugin
- Eigenen Text in Personenseite einbinden (analog zu UnivIS-Plugin)
- Einbindung FAU-Visitenkarten
