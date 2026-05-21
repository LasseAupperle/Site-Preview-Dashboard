# Handleiding — Site Preview Dashboard

## 1. Inleiding

**Site Preview Dashboard** is een WordPress-plugin waarmee je op één pagina een visueel overzicht kunt tonen van alle WordPress-sites die je beheert.

Zo werkt het systeem:

1. De plugin haalt via de **Screenshotone API** een screenshot op van elke site die je hebt toegevoegd.
2. Die screenshot wordt **lokaal opgeslagen** op jouw server (in `/wp-content/uploads/site-previews/`).
3. Op een willekeurige WordPress-pagina toon je het grid via de shortcode `[site-previews]`.
4. Bezoekers zien altijd snel ladende lokale afbeeldingen.
5. Als iemand op een preview klikt, opent er een **popup met een live iframe** van de betreffende site.
6. Elke week vernieuwt WP-Cron automatisch alle screenshots op de achtergrond.

---

## 2. Vereisten

- WordPress **5.8** of hoger
- PHP **7.4** of hoger
- Een gratis account op [screenshotone.com](https://screenshotone.com)
- WP-Cron moet actief zijn op de hoofdsite (standaard het geval bij de meeste hosts)

---

## 3. Stap 1 — Screenshotone account aanmaken

1. Ga naar [screenshotone.com](https://screenshotone.com) en klik op **Sign Up**.
2. Maak een gratis account aan (geen creditcard nodig).
3. Log in en ga naar het **Dashboard**.
4. Kopieer je **Access Key** (een reeks letters en cijfers).

> **Gratis limiet:** 100 screenshots per maand.
> Bij 20 sites die wekelijks worden vernieuwd gebruik je ~80 screenshots per maand — ruim binnen de gratis limiet.

---

## 4. Stap 2 — Plugin installeren op de hoofdsite

1. Comprimeer de map `site-preview-dashboard` naar een zip-bestand.
   - Windows: rechtermuisknop op de map → *Comprimeren naar zip-bestand*
   - Mac: rechtermuisknop → *Comprimeer "site-preview-dashboard"*
2. Ga in WordPress naar **Plugins → Nieuwe plugin toevoegen**.
3. Klik rechtsboven op **Plugin uploaden**.
4. Klik op **Bestand kiezen**, selecteer het zip-bestand en klik **Nu installeren**.
5. Klik na de installatie op **Plugin activeren**.

De plugin is nu actief en verschijnt als **Site Previews** in het linkermenu van het dashboard.

---

## 5. Stap 3 — Plugin instellen

1. Ga in het WordPress-dashboard naar **Site Previews**.
2. Plak onder **Instellingen** je Screenshotone API-sleutel in het veld *Screenshotone API-sleutel*.
3. Kies het gewenste aantal kolommen voor het grid (2, 3 of 4).
4. Klik op **Instellingen opslaan**.

Je ziet bovenaan een melding: *"Instellingen opgeslagen."*

---

## 6. Stap 4 — Sites toevoegen

1. Scrol op de pagina **Site Previews** naar beneden tot het formulier **Nieuwe site toevoegen**.
2. Vul de **Sitenaam** in (bijv. *"Mijn webshop"*).
3. Vul de volledige **URL** in (bijv. `https://mijnwebshop.nl`).
4. Klik op **Site toevoegen**.

De site verschijnt nu in de tabel. Er is nog geen screenshot. Om het eerste screenshot op te halen:

5. Klik op de knop **Vernieuwen** naast de nieuwe site.
6. Wacht tot de pagina *"✓ Klaar"* toont — dit kan 10–30 seconden duren.

Herhaal stappen 1–6 voor elke site die je wilt toevoegen.

---

## 7. Stap 5 — Preview-pagina aanmaken

1. Ga in WordPress naar **Pagina's → Nieuwe pagina**.
2. Geef de pagina een naam, bijv. *"Mijn sites"*.
3. Voeg in de inhoud de shortcode toe:

   ```
   [site-previews]
   ```

4. Klik op **Publiceren**.

De pagina toont nu het grid met alle actieve site-previews. De stijl past zich automatisch aan het aantal ingestelde kolommen aan.

---

## 8. De API-teller gebruiken

Op de pagina **Site Previews** zie je onder **API-gebruik** een teller:

> *Schermafbeeldingen deze maand: X / 100 (gratis limiet)*

- De teller houdt bij hoeveel screenshots er in de **huidige kalendermaand** zijn opgehaald via de API.
- Op de **eerste van elke nieuwe maand** begint de teller automatisch opnieuw bij 0.
- De voortgangsbalk **kleurt rood** als je meer dan 80 van de 100 gratis screenshots hebt gebruikt.
- Als je de limiet van 100 bereikt, mislukken verdere verversingsverzoeken totdat de maand voorbij is.

---

## 9. Handmatig vernieuwen

**Per site:**
- Klik op **Vernieuwen** naast een site in de tabel.
- De plugin haalt een nieuw screenshot op en toont de nieuwe datum en tijd.

**Alle actieve sites tegelijk:**
- Klik bovenaan de tabel op **Vernieuw alle actieve sites**.
- De plugin vernieuwt de sites één voor één en toont de voortgang: *"X / N vernieuwd…"*
- Dit telt elke verversing mee in de maandelijkse API-teller.

---

## 10. Automatische wekelijkse vernieuwing

De plugin maakt gebruik van **WP-Cron**, het ingebouwde taakplannersysteem van WordPress.

- Elke week worden alle **actieve** sites automatisch vernieuwd.
- WP-Cron wordt getriggerd door bezoeken aan jouw WordPress-site. Bij sites met weinig bezoekers kan er enige vertraging optreden.
- De automatische vernieuwing verloopt op de achtergrond — je hoeft niets te doen.

> **Let op:** WP-Cron werkt niet als de plugin is gedeactiveerd.

---

## 11. Volgorde van sites aanpassen

In de admin-tabel kun je de volgorde van sites aanpassen via **drag-and-drop**:

1. Ga naar **Site Previews** in het dashboard.
2. Beweeg je muis naar het sleephandvat (⋮) aan de linkerkant van een rij.
3. Sleep de rij naar de gewenste positie.
4. De nieuwe volgorde wordt automatisch opgeslagen.

De volgorde in de tabel bepaalt ook de volgorde in het preview-grid op de frontend.

---

## 12. Veelgestelde vragen

**Wat als een screenshot mislukt?**

Bij de knop Vernieuwen verschijnt dan *"✗ Screenshot mislukt."* Dit kan twee oorzaken hebben:
- De API-sleutel ontbreekt of is onjuist — controleer dit onder Instellingen.
- De Screenshotone-servers zijn tijdelijk niet bereikbaar — probeer het later opnieuw.

---

**Wat als de popup een foutmelding toont in het iframe?**

Sommige sites blokkeren het tonen in een iframe (via de HTTP-header `X-Frame-Options`). De popup toont dan een foutmelding of een lege pagina. Het screenshot in het grid blijft gewoon zichtbaar. Je kunt de site alsnog bezoeken via de knop **Bezoek site →** in de popup.

---

**Hoe verwijder ik een site?**

Klik op de knop **Verwijder** naast de betreffende site in de tabel. De site en het bijbehorende screenshot-bestand worden permanent verwijderd.

---

**Kan ik de volgorde van sites aanpassen?**

Ja. Gebruik het sleephandvat (⋮) aan de linkerkant van elke rij in de admin-tabel om sites te herordenen via drag-and-drop. De nieuwe volgorde wordt direct opgeslagen.

---

**Wat als ik meer dan 100 screenshots per maand nodig heb?**

De gratis limiet van Screenshotone is 100 per maand. Als je dit niet genoeg vindt, kun je op [screenshotone.com](https://screenshotone.com) een betaald plan kiezen met een hogere limiet. De plugin werkt automatisch door met de hogere limiet zodra je dezelfde API-sleutel gebruikt.

---

*Plugin ontwikkeld door LaunchUp — versie 1.0.0*
