# Gebruikershandleiding — Kaswaarnemingen-app

> **Note for non-Dutch readers.** This manual is intentionally written in Dutch because the operator-facing UI is Dutch (see FR-UI-070 in the FDS). The intended reader is a Dutch-speaking *harvester* or *farmer helper* working in a herenboeren-corporation greenhouse. The companion design documents (FDS, TDS, strategy) remain in English for the implementer audience. Tutoyer ("je"/"jij") is gebruikt; dat past bij de gemeenschapssfeer van een herenboeren-coöperatie.

| Document | Gebruikershandleiding — Kaswaarnemingen-app |
| Doelgroep | Harvesters en boer-helpers van een herenboeren-coöperatie |
| Stijl | Beknopt, praktisch, zonder jargon |
| Verwante documenten | `../design/operatorObservationStrategy.md` (achtergrond), `../design/functionalRequirements.md` (FDS); `adminManual.md` (beheerdershandleiding, Engels — zelfde map) |

---

## Inhoud

1. [Waar gaat het over?](#1-waar-gaat-het-over)
2. [Voor je begint](#2-voor-je-begint)
3. [Een waarneming vastleggen (twee tikken)](#3-een-waarneming-vastleggen-twee-tikken)
4. [De vijf categorieën](#4-de-vijf-categorieën)
5. [Optioneel: notitie, foto, ernst](#5-optioneel-notitie-foto-ernst)
6. [Tijdstip aanpassen](#6-tijdstip-aanpassen)
7. [Eerdere waarnemingen bekijken](#7-eerdere-waarnemingen-bekijken)
8. [Je naam wijzigen](#8-je-naam-wijzigen)
9. [Vergeet mij (cookie wissen)](#9-vergeet-mij-cookie-wissen)
10. [Meerdere kassen](#10-meerdere-kassen)
11. [Wat als je geen internet hebt?](#11-wat-als-je-geen-internet-hebt)
12. [Wat gebeurt er met je gegevens?](#12-wat-gebeurt-er-met-je-gegevens)
13. [Vragen of problemen?](#13-vragen-of-problemen)
14. [Tot slot — over verwachting](#14-tot-slot--over-verwachting)

---

## 1. Waar gaat het over?

De Kaswaarnemingen-app is een eenvoudig hulpmiddel voor jou en je medeoperators om kort vast te leggen wát je in de kas ziet. **Twee tikken** op je telefoon en de waarneming is opgeslagen — meer is het niet. Die gegevens helpen later om de werking van de **kasregelaar** te beoordelen en bij te stellen.

Je hoeft niets te installeren: de app draait gewoon in de browser van je telefoon. Eén keer je naam invoeren, daarna onthoudt de app je via een cookie.

## 2. Voor je begint

### De allereerste keer

1. Loop naar het bordje bij de kas controller en **scan de QR-code** met de camera van je telefoon. De app opent vanzelf in je browser.
2. Op het scherm verschijnt **"Welkom bij <kasnaam>"**. Geef je naam op (bijvoorbeeld "Marja") en bevestig. Twee personen kunnen niet exact dezelfde naam hebben — als jouw naam al bezet is, kies dan een variant ("Marja-W", "Marja van zaterdag", …).
3. Klaar. De app onthoudt je vanaf nu via een cookie. Zet de pagina eventueel op je startscherm voor snelle toegang ("Toevoegen aan beginscherm" in Safari / "Naar beginscherm" in Chrome).

### Volgende keren

Je telefoon herkent je automatisch. Je komt direct in het **startscherm** met:

- Bovenaan een begroeting ("Hallo Marja") en de naam van de actieve kas.
- Een grote knop **+ Snelle waarneming**.
- Daaronder je eigen recente waarnemingen van de laatste 24 uur.
- Onderaan kleine links naar **Instellingen** en **Privacy**.

Heb je nog geen waarnemingen vandaag? Dan zie je een melding zoals *"Nog geen waarnemingen — tik op + Snelle waarneming om te beginnen."* — dat is normaal, geen fout.

## 3. Een waarneming vastleggen (twee tikken)

Het basispatroon, gemaakt om in een paar seconden klaar te zijn:

1. Tik op **+ Snelle waarneming**.
2. Tik op de juiste **categorie** (er zijn er vijf — zie §4).
3. Tik op de juiste **tag** (per categorie maximaal zes).

De waarneming is nu opgeslagen. Je ziet een korte **bevestiging** die na ongeveer twee seconden vanzelf wegklikt. Klaar.

Wil je iets extra's toevoegen (een notitie, een foto)? Doe dat optioneel op het bevestigingsscherm of binnen 24 uur via je recente lijst — meer daarover in §5.

## 4. De vijf categorieën

Kies de categorie die het dichtst bij je waarneming komt. Twijfel je tussen twee? Pak de eerste die je in gedachten neemt — niemand wordt afgerekend op categorisering.

| Categorie | Wanneer gebruiken? |
|---|---|
| **Welzijnscheck** | "Ik liep door de kas, het ziet er goed / zorgwekkend uit." Algemene indruk, geen specifiek voorval. |
| **Omgeving** | Wat er buiten gebeurt en wat invloed heeft binnen: storm, bewolking, een obstakel tegen het glas, geluidsoverlast. |
| **Gewas** | Wat de planten doen: groei-overgang, plagen, ziekte, iets anders aan het gewas. |
| **Sensor / regeling** | Hoe de regelaar zich gedraagt: een sensor lijkt verkeerd, het raam staat te ver open of dicht, de regelaar oscilleert, je hebt handmatig ingegrepen. |
| **Onderhoud** | Iets wat je gedaan hebt aan de installatie: sensoren schoongemaakt, raam gecontroleerd, ander onderhoud. |

Onder elke categorie zie je hooguit zes tags. Tik er één en de waarneming is binnen.

## 5. Optioneel: notitie, foto, ernst

Soms is een tag alleen niet genoeg. Op het bevestigingsscherm — en de eerste 24 uur ook op de detailpagina van een waarneming — kun je:

- **Een notitie toevoegen** — bijvoorbeeld: *"Hagelbui rond 15:40, raam M3 stond open, ging om 15:45 dicht door veiligheidslogica."*
- **Een foto toevoegen** — handig bij plagen, schade, of iets vreemds. Eén foto per waarneming.
- **Een ernst-cijfer geven** (1..5) — alleen als dat ergens op slaat. Laat leeg als je twijfelt; **leeg is niet hetzelfde als 0**.

Foto's worden zo opgeslagen dat ze niet zomaar publiek opvraagbaar zijn. **GPS-info en andere camera-metadata worden automatisch verwijderd** voordat de foto wordt bewaard — dat is goed voor jouw privacy.

## 6. Tijdstip aanpassen

Standaard krijgt je waarneming de tijd dat je hem invoert. Heb je iets eerder gezien (bijvoorbeeld een halfuur geleden, maar je had je telefoon niet bij de hand)? Pas het tijdstip aan in het invoerscherm. De waarneming wordt dan met die eerdere tijd opgeslagen — dat helpt de analist later om de timing correct te koppelen aan wat de regelaar deed.

## 7. Eerdere waarnemingen bekijken

Op het startscherm zie je je **eigen** waarnemingen van de laatste 24 uur, met de nieuwste bovenaan.

- Wil je een volledige geschiedenis? Tik op **Bekijk alles**. Je ziet alle waarnemingen gegroepeerd per dag, met een camera-icoontje bij waarnemingen die een foto hebben.
- Tik op een rij voor de detailpagina. Daar zie je alle velden (tijd, categorie, tag, ernst, notitie, foto).
- **Binnen 24 uur** kun je de waarneming nog wijzigen of verwijderen via knoppen op de detailpagina.
- **Na 24 uur** wordt de waarneming alleen-lezen. Een korte tekst onderaan herinnert je daaraan.

Wil je een waarneming van langer geleden alsnog corrigeren? Vraag het de beheerder (§13).

## 8. Je naam wijzigen

Ga naar **Instellingen** → **Naam wijzigen** en typ de nieuwe naam in. Je bestaande waarnemingen blijven aan jou gekoppeld; alleen de weergegeven naam verandert (ook in oude waarnemingen). De nieuwe naam mag niet al door iemand anders worden gebruikt.

## 9. Vergeet mij (cookie wissen)

Onder **Instellingen** vind je een knop **Vergeet mij**. Tik je daarop, dan:

- Wordt de cookie op je telefoon ongeldig.
- De volgende keer dat je de app opent, wordt je behandeld als een nieuwe gebruiker en moet je opnieuw een naam invoeren. **Je oude naam kan je niet opniewu gebruiken!**
- **Je gebruikerrecord en je oude waarnemingen blijven gewoon bestaan** in het systeem — Vergeet mij wist alleen de koppeling op deze telefoon, niet de geschiedenis.

Wil je dat je gegevens ook echt uit het systeem worden verwijderd? Vraag het de beheerder (§13).

## 10. Meerdere kassen

Werk je in meer dan één kas? Iedere kas heeft zijn eigen QR-code en zijn eigen URL. **Scan de QR van de kas waar je nu bent**, dan stelt de app zich automatisch op die kas in. Bovenaan het startscherm zie je de naam van de actieve kas.

Je hoeft niets handmatig om te schakelen — de scan is voldoende. Iedere waarneming wordt opgeslagen tegen de kas waarin je hem invoert.

Tip: een herenboeren-installatie heeft één korte code per kas (bijvoorbeeld `5E3F`). Op het bordje aan de muur staat die code zichtbaar — zo weet je waar je werkt zonder eerst je telefoon te raadplegen.

## 11. Wat als je geen internet hebt?

In een kas met slecht internet werkt de web-app niet.

Belangrijk: leeg of vernieuw je telefoonbrowser niet. Hierdoor verlies je jouw cookie en weet de web-app niet meer wie je bent. Dit is niet te herstellen.

## 12. Wat gebeurt er met je gegevens?

- Het enige persoonlijke dat we vastleggen is **de naam die je zelf opgeeft** en een **cookie** op je telefoon. Geen e-mailadres, geen wachtwoord, geen sociale-media-koppeling.
- Waarnemingen worden bewaard om de controller mee te kunnen verbeteren; de standaard-bewaartermijn is **één jaar**. Oudere waarnemingen worden automatisch verwijderd, inclusief de bijbehorende foto's.
- Foto's krijgen automatisch een opschoning van metadata (GPS, camera, tijdstempel) voordat ze opgeslagen worden.
- Je kunt op elk moment je eigen gegevens als ZIP downloaden via **Instellingen** → **Download mijn gegevens** (deze functie komt beschikbaar in een latere release).
- De volledige uitleg staat op de **Privacy**-pagina, bereikbaar onderaan elk scherm. Daar staat ook hoe je je rechten kunt uitoefenen.

## 13. Vragen of problemen?

Het systeem heeft één beheerder (admin) per installatie. Zij/hij is de contactpersoon voor:

- Een waarneming wijzigen of verwijderen die jij zelf niet meer kunt aanpassen (na 24 uur).
- Een vergeten of dubbele naam.
- Storingen, foutmeldingen, een witte pagina, of de app reageert vreemd.
- Het verzoek om al je gegevens te verwijderen.
- Algemene vragen over wat er met de waarnemingen gebeurt.

De **contactgegevens van de beheerder** vind je op de **Privacy**-pagina onderaan elke schermweergave.

## 14. Tot slot — over verwachting

Een paar dingen die helpen om dit een nuttig instrument te houden:

- **"Alles goed" is een prima waarneming.** Routineuze "ik liep door de kas, niks bijzonders" is precies wat wij later willen zien om het normale patroon te kunnen vergelijken met de afwijkingen.
- **Geen enkele waarneming is fout.** Dacht je dat een sensor afweek en bleek het mee te vallen? Die waarneming is alsnog waardevol — nu weten we dat het er in jouw oog *uit zag* alsof er iets aan de hand was. Dat is informatie.
- **Het effect zie je niet meteen in de app.** Je waarnemingen worden niet live aan de kas controller teruggestuurd — die werkt zelfstandig door. De analist neemt jouw input mee bij periodieke evaluatie en bijstelling. Dat duurt soms weken. Wees daar niet door teleurgesteld; jouw input doet er wél toe.
- **Vlak na de installatie van de regelaar wordt er veel ingevoerd**, omdat de regelaar nog niet altijd doet wat de gebruikers verwachten. Na een paar weken zakt de inzet vaak — dat is normaal en menselijk. Blijf invoeren wanneer er echt iets te melden valt; dat zijn vaak de waarnemingen die de meeste waarde hebben.
- **Twee tikken is genoeg.** Spendeer niet te veel tijd aan elke registratie. Liever vijf korte waarnemingen dan één perfecte.

Veel succes, en bedankt voor het meekijken in de kas.

---

*Heb je verbeteringen of vragen over deze handleiding? Stuur ze aan de beheerder via de contactgegevens op de Privacy-pagina.*
