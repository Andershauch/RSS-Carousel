# Roadmap

## Status ved dagens afslutning

Pluginet er nu paa en stabil base med:

- fungerende WordPress-installation via korrekt release-zip
- loebende versionsstyring i GitHub
- fast release-flow med commit, build af zip og push
- testet frontend i Local
- mobilkarrusel med klik, swipe og visuel feedback

Seneste fungerende release er:

- `rss-news-carousel-1.0.7.zip`

## Naeste fokusomraade

Naeste arbejdsspor er at undersoege, om pluginet kan vise indhold baseret paa hashtags fra:

- Instagram
- X
- Facebook

Maalet skal ikke vaere at love alt paa en gang. Maalet skal vaere at finde den mest realistiske og stabile loesning, som kan vedligeholdes i et WordPress-plugin uden at goere projektet skroebeligt.

## Vigtig realitet foer vi gaar videre

Hashtag-integration paa sociale platforme er ikke som almindelige RSS-feeds.

Det kraever typisk:

- app-oprettelse hos platformen
- API-noegler eller tokens
- app review eller saerlige tilladelser
- hensyn til rate limits
- tydelig haandtering af fejl, udloebne tokens og manglende adgang

Det betyder, at dette spor skal bygges mere som en "provider integration" end som en normal feed-udvidelse.

## Konservativ anbefaling

Vi boer ikke bygge alle tre platforme samtidig.

Den sikreste raekkefolge er:

1. X
2. Instagram
3. Facebook

Grunden er:

- X har en ret tydelig soege-API til nyere opslag
- Instagram har hashtag-soegning, men med tungere Meta-krav
- Facebook er den mest usikre i forhold til egentlig hashtag-soegning paa offentlige opslag

## Fase 1: Arkitektur og afklaring

Foerste fase boer vaere helt uden stor frontend-aendring.

Vi skal lave:

- en provider-model, saa pluginet kan skelne mellem `rss`, `instagram`, `x` og senere `facebook`
- en faelles datastruktur, saa sociale opslag kan normaliseres ind i samme format som feed-items
- en separat cache-strategi for sociale kilder
- adminfelter til credentials, men foerst naar vi kender kravene praecist

Vi boer fortsat genbruge den eksisterende normaliserede struktur saa langt som muligt:

- `title`
- `url`
- `source`
- `published_at`
- `excerpt`
- `image`
- `categories`
- `guid`

Men vi faar sandsynligvis brug for ekstra felter senere:

- `platform`
- `author_name`
- `author_handle`
- `engagement`
- `media_type`
- `media_url`
- `permalink`

## Fase 2: X-hashtag visning

Dette er den mest realistiske foerste sociale integration.

Foreloebig plan:

1. Tilfoej en provider-klasse for X.
2. Lav adminfelter til Bearer Token og soegestreng.
3. Brug hashtag-query, fx `(#tottenham OR #coys) -is:retweet`.
4. Normaliser resultaterne ind i pluginets faelles item-format.
5. Cache resultatet haardt for at skaane API-forbrug.
6. Vis opslag i samme karrusel som oevrige items eller i en separat kildevisning.

Det vi skal vaere opmaerksomme paa:

- X Recent Search daekker nyere opslag og kraever udvikleradgang
- adgangsniveau og rate limits kan aendre sig
- medieudtraek skal testes saerskilt

## Fase 3: Instagram-hashtag visning

Instagram skal behandles som en separat og mere restriktiv integration.

Foreloebig plan:

1. Verificer praecis hvilken Instagram/Meta-konto der skal bruges.
2. Verificer om vi skal bruge Business- eller Creator-konto.
3. Opret Meta app og forbind den korrekt til side/konto.
4. Lav hashtag-opslag via Instagram Graph API.
5. Hent `recent media` eller `top media` for relevante hashtags.
6. Normaliser data til pluginets item-format.

Det vi skal vaere opmaerksomme paa:

- Meta-opsaetning er ofte den svaere del, ikke selve koden
- token-levetid og fornyelse skal taenkes ind tidligt
- app review og permissions kan blive en reel blokering
- vi boer ikke love "plug and play" foer vi har testet det med en rigtig konto

## Fase 4: Facebook

Facebook boer behandles som et undersoegelsesspor, ikke et loefte.

Det vi ved lige nu:

- almindelig offentlig hashtag-soegning paa Facebook er usikker som plugin-retning
- selv laesning af offentligt sideindhold kan kraeve saerlige features og review

Min anbefaling er derfor:

- vi planlaegger ikke "Facebook hashtags" som v1-maal
- vi undersoeger i stedet, om der er en realistisk vej via Facebook Page-indhold eller en mere begraenset integration
- hvis det ikke er robust, skal vi hellere sige det tydeligt end bygge noget halvt

## Foreslaaet arbejdsraekkefolge naeste gang

1. Afklare praecist hvilket socialt indhold du vil vise foerst.
2. Starte med X som foerste provider.
3. Designe settings-strukturen til sociale credentials.
4. Bygge en intern provider-arkitektur uden at aendre den nuvaerende frontend for meget.
5. Teste mod rigtige API-svar i Local.

## Hvad du skal vaere opmaerksom paa

Foer vi gaar videre, er det vigtigt at du taenker over disse punkter:

- Hvilke hashtags er vigtigst?
- Skal sociale posts blandes sammen med RSS i samme karrusel, eller vises separat?
- Vil du kun vise egne konti, eller ogsaa offentlige opslag fra andre?
- Har du allerede adgang til en X developer-app?
- Har du en Meta Business Manager / Facebook app / Instagram Business-konto klar?
- Er det vigtigt at kunne moderere eller filtrere opslag manuelt?

## Min anbefaling til beslutning

Hvis maalet er at faa noget brugbart hurtigt, saa boer vi naeste gang satse paa:

- X foerst
- Instagram bagefter
- Facebook kun hvis vi kan verificere en stabil og lovlig vej

Det vil give den bedste balance mellem:

- vaerdi
- stabilitet
- tidsforbrug
- realistisk drift

## Kilder der boer bruges som udgangspunkt naeste gang

X:

- `https://docs.x.com/x-api/posts/search/quickstart/recent-search`
- `https://developer.x.com/en/docs/x-api/search-overview`

Instagram / Meta:

- `https://developers.facebook.com/docs/instagram-api/`
- `https://developers.facebook.com/docs/instagram-api/guides/hashtag-search/`
- `https://developers.facebook.com/docs/instagram-platform/instagram-graph-api/reference/ig-hashtag/recent-media`

Facebook / Meta:

- `https://developers.facebook.com/docs/apps/review/feature#reference-PAGES_ACCESS`

## Praktisk note til naeste arbejdssession

Vi skal holde fast i den arbejdsgang, der virker:

1. lave aendringen
2. teste i Local naar det er relevant
3. committe
4. bygge ny release-zip
5. pushe til GitHub
6. huske at opdatere versionsnummeret ved rigtige plugin-releases
