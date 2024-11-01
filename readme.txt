=== Shortcodes KNVB API ===
Contributors: wimarschippers, hoest
Tags: knvb, voetbal, api, soccer, dutch
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Requires at least: 3.0.1
Tested up to: 4.7.3
Stable tag: 1.14.3.10

Voetbal clubs in het bezit van een API sleutel voor de KNVB Dataservice kunnen deze plugin gebruiken om API data te tonen in een wordpress website.

== Description ==
Met deze plugin is het mogelijk om door middel van shortcodes data uit de KNVB Dataservice API op te halen.

Deze shortcodes worden dan vertaalt naar een tabel in de wordpress website. Naast alle reguliere API calls, zoals die staan beschreven op http://api.knvbdataservice.nl/, zitten er in deze plugin ook een aantal extra features zoals het tonen van het clublogo, filteren op alleen thuiswedstrijden, filteren alle wedstrijden of op de volgende/vorige en huidige week.

Voor meer informatie over het gebruik van de verschillende shortcodes, zie onze [handleiding](http://www.manula.com/manuals/datawiresport/knvb-dataservice-wordpress-plugin/1/nl/topic/uitgebreide-installatie)

Hier volgt een lijst van de verschillende soorten shortcodes die beschikbaar zijn binnen de plugin:

* Lijst tonen van alle teams
* Lijst tonen met alle wedstrijden
* Lijst tonen met alle competities
* Lijst tonen met de uitslagen per team
* Lijst tonen met het programma per team
* Lijst tonen met de stand van een team
* Lijst tonen met de uitslagen van een competitie
* Lijst tonen met het programma van een competitie
* Lijst met de standen binnen een competitie
* Details van één specifieke wedstijd

Naast de bovenstaande reguliere shortcodes, kunt u per shortcode extra parameters toevoegen die zorgen extra filtering of sortering:

* Toon club logo bij de teams
* Sorteer wedstrijden op uit en thuis wedstrijden
* Toon enkel de thuis wedstrijden
* Toon de wedstrijden van de huidige, vorige, volgende week, een bepaald week nummer of van alle weken.
* Toon enkel de vriendschappelijke, reguliere of beker wedstrijden
* Toon enkel de wedstrijden binnen een bepaalde poule
* Toon enkel de veld of zaal wedstrijden
* Toon enkel de wedstrijden van de 1ste, 2e, 3e of 4e periode van het seizoen.

== Installation ==
= Automatisch installeren: =

1. Log via uw browser in op de backend van uw wordpress website.
1. Kies voor het menu item `Plugins`.
1. Klik op de knop `Nieuwe plugin`.
1. Zoek op `Shortcodes KNVB API`.
1. Klik op `Nu installeren`.

= Handmatig installeren: =

1. Download de plugin op https://bitbucket.org/WimarSchippers/shortcodes-knvb-api/downloads, er word een .zip bestand naar uw computer gedownload.
2. Pak het zip bestand dat u net gedownload hetb uit op uw computer.
3. Hernoem de uitgepakte map naar `shortcodes-knvb-api`.
4. Log via FTP in op de server waar uw wordpress website staat en navigeer naar de `/wp-content/plugins/` map.
5. Upload de `shortcodes-knvb-api` map, op uw computer, naar de `/wp-content/plugins/` op de server.
6. Log via uw browser in op de backend van uw wordpress website.
7. Kies voor het menu item `Plugins`.
8. Activeer de `Shortcodes KNVB API` plugin.

= Instellen: =

1. Log via uw browser in op de backend van uw wordpress website.
2. Ga naar `Instellingen - KNVB API`.
3. Geef de API sleutel in en de pathnaam van uw club in. Als u het veld cache niet invult of op *0* zet dan zal de plugin geen data cachen.
4. Sla de instellingen op.
5. Aan de indicatie lampjes kunt u zien wat de status van API is en of u verbonden bent.
6. Via de verschillende tabs kunt u alle beschikbare shortcodes zien. Deze kunt u simpelweg kopiëren en plakken in een webpagina om de data zichtbaar te maken op uw website.

== Screenshots ==

1. Instellingen scherm
2. Algemene shortcodes
3. Extra parameters

== Frequently Asked Questions ==
= Hoe stel ik de plugin in? =

* Log via uw browser in op de backend van uw wordpress website.
* Ga naar `Instellingen - KNVB API`.
* Geef de API sleutel in en de pathnaam van uw club in. Als u het veld cache niet invult of op *0* zet dan zal de plugin geen data cachen.
* Sla de instellingen op.
* Aan de indicatie lampjes kunt u zien wat de status van API is en of u verbonden bent.
* Via de verschillende tabs kunt u alle beschikbare shortcodes zien. Deze kunt u simpelweg kopiëren en plakken in een webpagina om de data zichtbaar te maken op uw website.

= Waarom zie ik zie geen text/content op de verschillende tabbladen van het instellingen menu van de plugin? =

* De cache map moet 777 rechten hebben. De cache map word normaal gesproken zelf door de plugin aangemaakt maar als u geen text/content ziet op de verschillende tab bladen van het instellingen scherm van de plugin dan moet u deze rechten wellicht manueel toekennen. Op een Linux server doet u dat als volgt:
* Log via ftp of ssh in op de server waar uw wordpress website staat.
* Navigeer naar de uploads map van de wordpress installatie ( b.v. `cd /var/www/html/wordpress/wp-content/uploads`).
* Controleer of er, in de uploads map, een map bestaat die `shortcodes-knvb-api` heet en of er in de shortcodes map een map staat de `cache` heet (`/uploads/shortcodes-knvb-api/cache`). Bestaat één van beide mappen niet dan moeten deze mappen aangemaakt worden en de 777 rechten aan de cache map worden toegekend. Dit kan allemaal via één command: `mkdir -p -m 777 shortcodes-knvb-api/cache`.
* Staan zowel de `shortcodes-knvb-api` map in de `uploads` map als de `cache` map in de shortcodes map, dan moeten alleen de juiste rechten aan de cache map worden toegekend d.m.v. het volgende command `chmod 777 shortcodes-knvb-api/cache`.
* Refresh het instellingen scherm van de plugin in uw browser en controleer of er text/content in de verschillende tabbladen staat.

== Changelog ==
= 1.14.3.10 =
Release date: Apr 06, 2017

Feature Improvements:

* Show VeldKNVB if VeldClub is empty under Spelsoort column in [knvb uri="/wedstrijden"] shortcode.

= 1.14.3.9 =

Bug fixes:

* Fixed bug that caused column headers in the standing multipoule table not to show.

= 1.14.3.8 =

Bug fixes:

* Fixed bug causing error when trying to hide column's via the `fields="..."` option without having set `headers="1"`

= 1.14.3.7 =

Bug fixes:

* Small bug that required user to manually include jQuery when using the slider. Now using Wordpress's native version of jQuery.

= 1.14.3.6 =

Potential bug fixes:

* If server date is set wrong, weeknummer=C might display next weeks results instead of Current week's results. In an effort to prevent this, now using Wordpress's time instead of the plain php date(). Being unable to test this, this fix might not work.

= 1.14.3.5 =
Release date: Sep 07, 2016

Feature Improvements:

* Fields option: Added additional option `fields="Tijd|Bijzonderheden"` to hide entire table columns.

= 1.14.3.4 =
Release date: Aug 30, 2016

Bug fixes:

* Small bug fix that sometimes causes plugin not being able to be activated

= 1.14.3.3 =
Release date: Aug 19, 2016

Bug fixes:

* Thuis parameter: Sometimes clubname in API does not match team names (e.g. clubname = V.V. Club and team = Club 1 ). This caused the thuis=1 parameter to not work since it was strictly matching the clubname against the team name. Implemented club name field on the plugin's settings page to manually overwrite the API club name.

= 1.14.3.2 =
Release date: Aug 18, 2016

Bug fixes:

* Sorterenopteam parameter: Documentation stated parameter should be called by extra="sorterenopteam=1" but code was expecting extra="sorteeropteam=1".

= 1.14.3.1 =
Release date: Aug 18, 2016

Bug fixes:

* Matches template: Fixed bug that would only show score if hometeam had goals. This prevented the score from being displayed if a match ends in 0-3 for example.

= 1.14.3 =
Release date: Aug 17, 2016

Feature Improvements:

* Shortheaders parameter: Added extra parameter to show abbreviated version of column headers
* Sorterenopteam parameter: Added extra parameter to sort matches by team per date instead of start time per date
* Resultaten parameter: Added extra parameter to limit the amount of results shown of the page
* Weeknummer parameter: Expanded parameter input to also take a combination of P(revious), C(urrent), N(ext) weeknumber

Documentation:

* Shortcode parameters tab: Examples of headers parameter
* Shortcode parameters tab: Examples of shortheaders parameter
* Shortcode parameters tab: Examples of sorterenopteam parameter
* Shortcode parameters tab: Examples of resultaten parameter
* Shortcode parameters tab: Examples of P(revious), C(urrent), N(ext) inputs for the weeknummer parameter

= 1.14.2.2 =
Release date: Jun 27, 2016

Feature Improvements:

* Reverse order results: Reversed the order of the results so that the newest matches are listed at the top.
* Reverse order parameter: Added extra parameter `reverse=1` that will reverse to order of the API output.

= 1.14.2 =
Release date: May 25, 2016

Bug fixes:

* Match template headers: Fixed bug to show headers correctly of matches

= 1.14.2 =
Release date: May 25, 2016

Feature Improvements:

* Headers: Show column headers by adding `headers=1` parameter to shortcode

= 1.14.1 =
Release date: April 29, 2016

Feature Improvements:

* Status light: API status
* Status light: API connect status
* Tabs: Tabs for each type of shortcode
* Tabs: Additional templates for the settings menu
* Templates: Additional template for the teams call
* Caching: Changed the way the caching system works
* Settings: Retrieve the KNVB name through the API rather than manually setting it