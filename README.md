CRIS-Plugin_Webbaukasten
========================

Version 1.6 (Stand 27.10.2015)

Einbinden von Daten aus der FAU-Forschungsdatenbank <b>CRIS</b> in Webseiten

Für die Publikationslisten lassen sich über Parameter verschiedene Ausgabeformen einstellen. Die Titel sind jeweils mit der Detailansicht der Publikation auf http://cris.fau.de verlinkt.

## Installation
- Installation analog zu den übrigen Webbaukasten-Plugins in /vkdaten/tools/cris/

## Include
Publikationsliste (automatisch nach Jahren gegliedert):<br />
<code><!--#include virtual="/vkdaten/tools/cris/publikationsliste.php" --></code>

### Mögliche Zusatzoptionen:
Die verschiedenen Zusatzoptionen können miteinander kombiniert werden. Die Parameter werden dabei mit einem "?" hinter die Include-URL gehängt, mehrere Parameter werden durch "&" getrennt (Beispiele siehe unten).

##### Gliederung
- <b>orderby=year</b>: Liste nach Jahren absteigend gegliedert (Voreinstellung)
- <b>orderby=type</b>: Liste nach Publikationstypen gegliedert. Die Reihenfolge der Publikationstypen kann in den Einstellungen nach Belieben festgelegt werden.

##### Filter
- <b>year=2015</b>: Nur Publikationen aus einem bestimmten Jahr
- <b>start=2000</b>: Nur Publikationen ab einem bestimmten Jahr
- <b>pubtype=buecher</b>: Es werden nur Publikationen eines bestimmten Typs angezeigt:
	- buecher
    - zeitschriftenartikel
    - sammelbandbeitraege
    - herausgeberschaften
    - konferenzbeitraege
    - uebersetzungen
    - abschlussarbeiten
    - andere
- <b>publication="12345678"</b>: Nur eine einzelne Publikation (hier die CRIS-ID der Publikation angeben)
- Filter lassen sich auch kombinieren: z.B. year=2014&pubtype=buecher (= alle Bücher aus 2014)

##### ID überschreiben
Die in den Einstellungen festgelegte CRIS-ID kann überschrieben werden, entweder durch die ID einer anderen Organisationseinheit, oder durch die ID einer einzelnen Person:
- <b>orga=123456</b> für eine von den Einstellungen abweichende Organisations-ID
- <b>person=123456</b> für die Publikationsliste einer konkreten Person

#### Beispiele
- Publikationsliste nach Publikationstypen gegliedert:<br />
  <code><!--#include virtual="/vkdaten/tools/cris/publikationsliste.php?orderby=type" --></code>
- Alle Bücher: <br />
  <code><!--#include virtual="/vkdaten/tools/cris/publikationsliste.php?type=buecher" --></code>
- Alle Publikationen aus dem Jahr 2015, nach Publikationstypen gegliedert:<br />
  <code><!--#include virtual="/vkdaten/tools/cris/publikationsliste.php?year=2015&orderby=type" --></code>
- Publikationslisten ab einem bestimmten Jahr:<br />
  <code><!--#include virtual="/vkdaten/tools/cris/publikationsliste.php/start=2000" --></code>
- Alle Publikationen der Person mit der CRIS-ID 123456 aus dem Jahr 2000, nach Publikationstypen gegliedert
  <code><!--#include virtual="/vkdaten/tools/cris/publikationsliste.php?person=123456&year=2000&orderby=pubtype" --></code>

###Konfiguration
Konfiguration über /vkdaten/cris.conf (über NavEditor > Erweitert > Konfiguration editierbar):<br />

Eintrag | Beispiel | Erklärung |
| ------------- | ------------- | ------------- |
CRISOrgNr | 1234567 | CRIS-Organisationsnummer |
Reihenfolge_Publikationen | Journal article&#124;Article in edited volumes&#124;Translation&#124;Book&#124;Editorial&#124;Conference Contribution&#124;Thesis&#124;Other | Reihenfolge, wenn die Publikationsliste nach Publikationstypen gegliedert werden soll|
Pfad_Personenseite | /cris/person.shtml | für Links von Publikations- und Mitarbeiterlisten auf Personen-Detailseite |
Personeninfo_Univis | 1 | In Publikationslisten Autoren mit ihrer UnivIS-Personenseite verlinken?; 1=ja, 0=nein; UnivIS-Plugin muss installiert und eingerichtet sein |
Pfad_Personenseite_Univis | /wir-ueber-uns/mitarbeiter/mitarbeiter.shtml | Pfad zur UnivIS-Personenseite |
