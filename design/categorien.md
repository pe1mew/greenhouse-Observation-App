# Categorieën en tags — voorbeelden

> Dit document is in het Nederlands omdat het de operator-gerichte taxonomie illustreert. De interne keys (Engels) blijven onveranderd — die staan in de database en in de CSV-export. De Nederlandse weergavenamen zijn wat de operator op het scherm ziet en kunnen door de admin worden aangepast via FR-TAX-020 zonder dat de historie breekt.

| | |
|---|---|
| Document | Categorieën en tags — voorbeelden |
| Doel | De vijf vaste categorieën uit [OS §3] in de praktijk illustreren — per categorie een boom met de mogelijke tags, hun Nederlandse weergavenaam, hun interne key, en een concreet voorbeeld van wanneer een operator de tag zou aantikken |
| Verwante documenten | `operatorObservationStrategy.md` §3 (de taxonomie zoals gedefinieerd), `functionalRequirements.md` (FR-TAX-* voor de spelregels), `../manual/userManual.md` (operator-handleiding, gebruikt deze tags in voorbeelden) |

---

## Hoe een waarneming wordt opgebouwd

Per waarneming één **categorie** en één **tag** (verplicht), plus optioneel ernst (1..5), notitie en foto. Twee taps voor het routinegeval:

```
Tap 1 → kies categorie (M3, altijd 5 keuzes)
Tap 2 → kies tag binnen die categorie (M4, ≤ 6 keuzes)
```

De tag wordt direct bij de tweede tap opgeslagen — geen aparte "verzenden"-knop. Daarna verschijnt M5 (bevestiging) waar je optioneel een notitie of foto kunt toevoegen.

---

## 1. Welzijnscheck — `wellbeing`

De routinematige *"ik ben er even langsgelopen, hier is mijn indruk"*. Verreweg de meest voorkomende waarneming. Bewijst óók dat iemand de kas heeft bezocht, niet alleen *wat* er gezien is.

```
Welzijnscheck
├─ Alles goed (all_good)
│   Niets bijzonders, klimaat lijkt prima, gewas oogt gezond.
│   Voorbeeld: harvester loopt 's ochtends de kas door, ziet niets
│   afwijkends, tikt deze tag — bewijst dat er iemand is geweest en
│   niets te melden viel.
│
└─ Iets niet pluis (concern)
    Er klopt iets niet, maar ik kan het niet specifiek benoemen.
    Voorbeeld: planten hangen iets meer dan gisteren, geen duidelijke
    oorzaak. Voeg een notitie + foto toe en signaleer dat de analist
    er later naar moet kijken.
```

---

## 2. Omgeving — `environment`

Dingen buiten de kas die de regelaar niet ziet, of die de binnenklimaatsensoren niet volledig vangen.

```
Omgeving
├─ Storm (weather_storm)
│   Wind, regen of hagel boven gemiddelde intensiteit.
│   Voorbeeld: hagelbui begint, ramen M3 stonden open. Tik direct +
│   voeg notitie toe ("hagel begon ~15:40, ramen sloten 15:45 via
│   veiligheidslogica").
│
├─ Bewolkt (weather_overcast)
│   Langdurig donker weer dat het temperatuurverloop beïnvloedt.
│   Voorbeeld: hele dag bewolkt. Je verwacht andere kastemperaturen
│   dan met zon en markeert het zodat de analist het meeneemt.
│
├─ Obstakel gezien (obstacle_seen)
│   Iets blokkeert beweging van een raam of belichting.
│   Voorbeeld: tak van buitenstaande boom hangt tegen raam M2; raam
│   zal niet meer volledig openen tot je het verhelpt.
│
└─ Lawaai van buiten (external_noise)
    Bouwwerkzaamheden, oogstmachines, vuurwerk e.d.
    Voorbeeld: een maaier op het naburige perceel — kan stof- of
    trillinginvloed hebben op gevoelige metingen.
```

---

## 3. Gewas — `crop`

Alles wat het gewas zelf doet, oploopt of ondervindt.

```
Gewas
├─ Groeistadium veranderd (crop_stage_change)
│   Plant gaat over naar een nieuwe fase (bloei, zetting, oogst,
│   vergelen).
│   Voorbeeld: eerste tomaten kleuren rood. De analist gebruikt dit
│   moment om de "rijping-curve" in het model te ijken.
│
├─ Plaag (crop_pest)
│   Insecten of andere dieren zichtbaar.
│   Voorbeeld: bladluizen op een paar planten gezien. Zet ernst op
│   2 (nog lokaal), voeg een foto toe.
│
├─ Ziekte (crop_disease)
│   Visuele symptomen van ziekte: verkleuring, plekken, schimmel,
│   rot.
│   Voorbeeld: meeldauw op onderste bladeren. Ernst 3, notitie
│   geeft de locatie aan.
│
└─ Anders aan gewas (crop_other)
    Iets gewas-gerelateerd dat niet in bovenstaande past.
    Voorbeeld: een paar planten zijn omgevallen onder hun eigen
    gewicht. Notitie + foto.
```

---

## 4. Sensor / regeling — `sensor_control`

De **mening** van de operator over hoe de regelaar zich gedraagt — niet wat de regelaar zélf registreert. Dit is de meest waardevolle categorie voor de afstemmingscampagne van de regelaar in de eerste twee maanden na installatie (zie A7).

```
Sensor / regeling
├─ Sensor lijkt fout (sensor_drift_suspect)
│   Aflezing klopt niet met wat ik zie of voel.
│   Voorbeeld: thermometer zegt 22 °C, maar het voelt als 28 °C.
│   Sensor drijft misschien of staat in de zon.
│
├─ Ramen te ver open (control_too_open)
│   Regelaar opent ramen waar ik ze dicht had verwacht.
│   Voorbeeld: koude wind van buiten, ramen staan toch open.
│   Mogelijk verkeerd setpoint of foutieve overruling.
│
├─ Ramen te ver dicht (control_too_closed)
│   Het is te warm en de ramen zouden moeten openen.
│   Voorbeeld: 32 °C binnen, ramen staan dicht. Je verwacht
│   ventilatie maar krijgt het niet.
│
├─ Oscillatie waargenomen (oscillation_noticed)
│   Ramen of belichting gaan herhaaldelijk aan/uit binnen korte
│   tijd — onnodig schakelen.
│   Voorbeeld: in 10 minuten 4× achter elkaar open/dicht. Duidt op
│   te krap geconfigureerde hysterese (dwell time).
│
└─ Handmatig ingegrepen (manual_override)
    Ik heb zelf iets aangepast aan de regelaar.
    Voorbeeld: setpoint t_max_day verhoogd naar 28 omdat paprika's
    slap hingen. Bewijst de aanpassing voor latere review.
```

---

## 5. Onderhoud — `maintenance`

Onderhoudsacties die je zelf uitvoert. Belangrijk voor de analist: een schone sensor of een gerepareerd raam verandert het meetsignaal, dus dit moet apart vindbaar zijn.

```
Onderhoud
├─ Sensoren schoongemaakt (maint_clean_sensors)
│   Sensorbehuizing afgeveegd of kalk weggehaald.
│   Voorbeeld: thermometer en hygrometer afgenomen met een droge
│   doek. Aflezingen gaan misschien even af- of oplopen.
│
├─ Ramen gecontroleerd (maint_window_check)
│   Raammechaniek getest of geïnspecteerd.
│   Voorbeeld: handmatig open/dicht gestuurd via de regelaar,
│   gecontroleerd op piepen of haperen.
│
└─ Ander onderhoud (maint_other)
    Onderhoudsactie die niet in bovenstaande past.
    Voorbeeld: belichtingslamp vervangen, irrigatieleiding
    gerepareerd, raamglas schoongemaakt.
```

---

## Optionele velden bij elke waarneming

Naast de verplichte categorie + tag staan voor elke waarneming open:

| Veld | Wanneer toevoegen | Voorbeeld |
|---|---|---|
| **Ernst (1..5)** | Wanneer "hoe erg" relevant is — vooral bij `concern`, `crop_pest`, `crop_disease`, en de sensor/regeling-tags | Bladluizen op 3 planten → ernst 1; bladluizen op > 50 % → ernst 4 |
| **Notitie** | Wanneer de tag alleen onvoldoende vertelt — vrije tekst | Bij `weather_storm`: *"Hagel begon 15:40, ramen M2 nog open"* |
| **Foto** | Wanneer beeld sterker spreekt dan tekst | Bij `crop_pest`: foto van het aangetaste blad zodat de analist het later kan inschatten |
| **Tijdstip** | Standaard staat het op "nu". Aanpassen wanneer je achteraf invoert | *"Ik liep om 15:30 langs en zag het, maar tik het pas in om 16:00"* |

---

## Tellingen

- **5 categorieën**, vast op lanceermoment.
- **2 + 4 + 4 + 5 + 3 = 18 tags** in de standaardset uit [OS §3].
- Per categorie maximaal **6 actieve tags** zichtbaar in M4 (FR-TAX-060) — er is dus ruimte voor groei.
- De admin kan via FR-TAX-010 / 020 / 040 tags **toevoegen, hernoemen, of archiveren** — historie blijft leesbaar omdat de interne key (Engels) niet verandert.

---

*De interne keys (`all_good`, `weather_storm`, enz.) zijn de stabiele identifiers (FR-TAX-030). Ze veranderen niet wanneer de admin de Nederlandse weergavenaam aanpast. Daarom blijven oude waarnemingen leesbaar, en lukt de analist altijd de cross-time vergelijking in de CSV-export.*
