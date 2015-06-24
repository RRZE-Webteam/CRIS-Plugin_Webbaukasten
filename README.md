CRIS-Plugin_Webbaukasten
========================

Einbinden von Daten aus der FAU-Forschungsdatenbank CRIS in Webseiten

## Version 1.2.2 (Stand 24.06.2015):

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
  - Publikationslisten ab einem bestimmten Jahr:<br />
    <code><!--#include virtual="/vkdaten/tools/cris/publikationsliste.php/start-2000" --></code>
- Konfiguration über /vkdaten/cris.conf (über NavEditor > Erweitert > Konfiguration editierbar):<br />

Eintrag | Beispiel | Erklärung |
| ------------- | ------------- | ------------- |
CRISOrgNr | 1234567 | CRIS-Organisationsnummer |
Reihenfolge_Publikationen | Journal article&#124;Article in edited volumes&#124;Translation&#124;Book&#124;Editorial&#124;Conference Contribution&#124;Thesis&#124;Other | Reihenfolge, wenn die Publikationsliste nach Publikationstypen gegliedert werden soll|
Pfad_Personenseite | /cris/person.shtml | für Links von Publikations- und Mitarbeiterlisten auf Personen-Detailseite |
Personeninfo_Univis | 1 | In Publikationslisten Autoren mit ihrer UnivIS-Personenseite verlinken?; 1=ja, 0=nein; UnivIS-Plugin muss installiert und eingerichtet sein |
Pfad_Personenseite_Univis | /wir-ueber-uns/mitarbeiter/mitarbeiter.shtml | Pfad zur UnivIS-Personenseite |
