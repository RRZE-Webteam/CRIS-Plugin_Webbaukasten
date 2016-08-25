CRIS-Plugin_Webbaukasten
========================

Version 2.0 (Stand 25.08.2016)

Einbinden von Daten aus dem FAU-Forschungsinformationssystem <b>CRIS</b> in Webseiten

Aktuell werden folgende in CRIS erfasste Forschungsleistungen unterstützt:
- Publikationen
- Auszeichnungen

## Installation
- Installation analog zu den übrigen Webbaukasten-Plugins in /vkdaten/tools/cris/
- Fügen Sie außerdem in die Datei ssi/head.shtm folgende Zeile ein:<br />
  <code><link href="/vkdaten/tools/cris/cris.css" type="text/css" rel="stylesheet"></code>

## Include
- Publikationsliste (automatisch nach Jahren gegliedert):<br />
  <code><!--#include virtual="/vkdaten/tools/cris/cris.php?show=publications" --></code>
- Auszeichnungen (automatisch nach Jahren sortiert):<br />
  <code><!--#include virtual="/vkdaten/tools/cris/cris.php?show=awards" --></code>
- Forschungsprojekte:<br />
  <code><!--#include virtual="/vkdaten/tools/cris/cris.php?show=projects" --></code>

## Mögliche Zusatzoptionen:
Die verschiedenen Zusatzoptionen können miteinander kombiniert werden. Die Parameter werden dabei mit einem "?" hinter die Include-URL gehängt, mehrere Parameter werden durch "&" getrennt (Beispiele siehe unten).

### Gliederung
- <b>orderby=year</b>: Liste nach Jahren absteigend gegliedert (Voreinstellung bei Publikationen)
- <b>orderby=type</b>: Liste nach Publikations- bzw. Auszeichnungstypen gegliedert. Die Reihenfolge kann in den Einstellungen nach Belieben festgelegt werden.

##### Filter
- <b>year=2015</b>: Nur Einträge aus einem bestimmten Jahr
- <b>start=2000</b>: Nur Einträge ab einem bestimmten Jahr
- <b>type=XXX</b>: Es werden nur Einträge eines bestimmten Typs angezeigt:
	- Publikationen:
		- buecher
		- zeitschriftenartikel
		- sammelbandbeitraege
		- herausgeberschaften
		- konferenzbeitraege
		- uebersetzungen
		- abschlussarbeiten
		- andere
	- Auszeichnungen:
		- preise
		- stipendien
		- mitgliedschaften
		- andere
        - Projekte:
                - einzelfoerderung
                - teilprojekt
                - gesamtprojekt
                - graduiertenkolleg
                - eigenmittel
- <b>publication=12345678</b>: Nur eine einzelne Publikation (hier die CRIS-ID der Publikation angeben)
- <b>awardnameid=123</b>: Nur eine einzelne Auszeichnung (hier die CRIS-ID der Auszeichnung angeben)
- <b>award=12345678</b>: Nur eine einzelne Preisverleihung (hier die CRIS-ID der Verleihung angeben)<br>
  Hinweis zum Unterschied zwischen awardnameid und award: <b>awardnameid</b> bedeutet die ID eines Preises, der normalerweise mehrfach vergeben wird, z.B. der "Gottfried-Wilhelm-Leibniz-Preis". <b>award</b> (bzw. dessen ID) bedeutet die konkrete, einmalige Verleihung dieses Preises an eine bestimmte Person.
- <b>project=123456: Nur ein einzelnes Projekt. Hier ist die Ausgabe ausführlicher, u.a. mit Nennung der Projektbeteiligen (Projektleiter und -mitarbeiter) und einer Liste der dazugehörigen Publikationen.
- <b>items=5</b>: Nur die ersten 5 Publikationen anzeigen. In dem Fall werden "orderby"-Parameter ignoriert – es wird eine nicht gegliederte Liste ausgegeben.
- Filter lassen sich auch kombinieren: z.B. year=2014&type=buecher (= alle Bücher aus dem Jahr 2014)

### Darstellung

#### Publikationen
- <b>quotation=apa</b> bzw. <b>quotation=mla</b>: Ausgabe im Zitationsstil APA bzw. MLA

#### Auszeichnungen
- <b>display=gallery</b>: Bildergalerie mit Bild des Preisträgers und Angaben zum Preis
- <b>showname=0</b>: Der Name des Preisträgers wird nicht angezeigt. Das kann z.B. bei Darstellungen auf einer Personenprofilseite sinnvoll sein.
- <b>showyear=0</b>: Die Jahreszahl wird nicht angezeigt (z.B. für eine nach Jahren gegliederten Ansicht).
- <b>showawardname=0</b>: Der Name der Auszeichnung wird nicht angezeigt (z.B. bei der Ausgabe awardnameid=123).

#### Projekte
- <b>hide=details,abstract,publications: Ein oder mehrere Elemente können ausgeblendet werden.

### ID überschreiben
Die in den Einstellungen festgelegte CRIS-ID kann überschrieben werden, entweder durch die ID einer anderen Organisationseinheit, oder durch die ID einer einzelnen Person:
- <b>orgID=123456</b> für eine von den Einstellungen abweichende Organisations-ID. Sie können auch mehrere Organisations-IDs angeben, durch Komma getrennt: orga=123456,987654.
- <b>persID=123456</b> für die Publikationen bzw. Auszeichnungen einer konkreten Person. Bei Projekten werden hier nur die Projekte angezeigt, bei denen die Person Projektleiter ist/war (Standard: <b>role=leader</b>). Projekte, an jemand nur beteiligt war, erhalten Sie durch einen separaten Shortcode mit dem Parameter <b>role=member</b>.

### Sortierung
Publikationslisten können nach dem Zeitstempel der Erstellung oder der letzten Bearbeitung des Datensatzes sortiert werden. In dem Fall wird eine nicht gegliederte Liste ausgegeben.
- <b>sortby=created</b>
- <b>sortby=updated</b>

## Beispiele
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
CRISOrgNr | 1234567 <em>oder</em>123456, 987654 | CRIS-Organisationsnummer. Sie können auch mehrere Organisations-IDs angeben, durch Komma getrennt. |
Reihenfolge_Publikationen | konferenzbeitraege&#124;zeitschriftenartikel&#124;buecher&#124;sammelbandbeitraege&#124;uebersetzungen&#124;herausgeberschaften&#124;abschlussarbeiten&#124;andere | Reihenfolge, wenn die Publikationsliste nach Publikationstypen gegliedert werden soll|
Pfad_Personenseite | /cris/person.shtml | für Links von Publikations- und Mitarbeiterlisten auf Personen-Detailseite |
Personeninfo_Univis | person | Autoren in Publikationslisten verlinken?<br />person = Link zur UnivIS-Personenseite auf diesem Webauftritt (UnivIS-Plugin muss installiert und eingerichtet sein)<br />cris = Link zur Personenseite auf cris.fau.de<br />none = keinen Link setzen |
Pfad_Personenseite_Univis | /wir-ueber-uns/mitarbeiter/mitarbeiter.shtml | Pfad zur UnivIS-Personenseite |
BibTex | 1 | Soll unter den einzelnen Publikationen ein Link zum BibTex-Export der Publikationsdaten angezeigt werden? 1=ja, 0=nein.|
Reihenfolge_Auszeichnungen | preise&#124;stipendien&#124;mitgliedschaften&#124;andere | Reihenfolge, wenn die Auszeichnungen nach Typen gegliedert werden sollen|
Personeninfo_Univis_Auszeichnungen | person | Preisträger verlinken?<br />person = Link zur UnivIS-Personenseite auf diesem Webauftritt (UnivIS-Plugin muss installiert und eingerichtet sein)<br />cris = Link zur Personenseite auf cris.fau.de<br />none = keinen Link setzen |
Sprache | de | Sprache z.B. der Publikationstypen. Bislang verfügbar: de und en.|
Cache_Zeit | 43200 | Wie lange sollen die Seiten im Cache zwischengespeichert werden? Angabe in Sekunden: 43200 Sek. = 12 Std.|