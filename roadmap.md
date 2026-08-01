# Roadmap

## Status lige nu

Pluginet staar nu paa en mere moden base med:

- release `2.1.3`
- source-prioritering i settings
- reorder af feed-kilder via drag eller `Up` / `Down`
- sortering der foerst viser nyheder fra de seneste 48 timer
- derefter sortering efter kildeprioritet, keywords og dato
- fast release-flow med commit, tag, build og push

Seneste release-zip ligger her:

- `C:\Users\ander\RSS Carousel\dist\rss-news-carousel-2.1.3.zip`

Git-status ved seneste afslutning:

- commit: `c2aeac9`
- tag: `v2.1.3`
- pushed til `origin/main`

## Naeste fokusomraade

Naeste arbejdsspor er ikke nye features, men maalrettet optimering.

Filteret for alt nyt arbejde skal vaere:

- forbedrer det performance?
- mindsker det fejl eller race conditions?
- goer det cache og sortering mere robuste?

Hvis svaret ikke er ja til mindst et af punkterne, skal vi som udgangspunkt ikke bygge det.

## Overordnet plan

Vi tager optimeringsarbejdet i 3 sikre commits.

### Commit 1

`Decouple feed cache from presentation settings`

Maal:

- dele feed-cache fra rene design- og tekstindstillinger
- undgaa nye feed-kald naar man kun aendrer farver, fonte, overskrifter eller knaptekst

Konkrete opgaver:

- indfoer et separat feed fingerprint i `includes/class-cache.php`
- lad cache-noeglen kun afhaenge af feed-relevante felter:
  - feed URLs
  - keywords
  - source priority
  - cache duration
- fjern designfelter og tekstfelter fra det, der trigger nyt feed-fetch

Forventet gevinst:

- faerre eksterne requests
- hurtigere arbejde i settings
- mindre risiko for unodig cache churn

### Commit 2

`Optimize feed processing and reduce duplicate work`

Maal:

- reducere CPU-arbejde i fetch- og sorteringspipen
- fjerne arbejde paa items der alligevel bliver kasseret

Konkrete opgaver:

- flyt duplicate removal tidligere i `includes/class-feed-fetcher.php`
- gem numerisk timestamp direkte paa item i `includes/class-item-normalizer.php`
- genbrug timestamp i sorteringen i stedet for gentagne `strtotime()`
- forudberegn soegetekst til keyword-match i `includes/class-keyword-filter.php`
- goer media parsing betinget af om media faktisk vises

Forventet gevinst:

- mindre CPU-forbrug
- hurtigere sortering
- bedre robusthed paa store eller dublerede feeds

### Commit 3

`Harden cache rebuild behavior and add regression coverage`

Maal:

- undgaa parallelle cache rebuilds
- mindske risikoen for at gamle fejl kommer tilbage

Konkrete opgaver:

- tilfoej en kort rebuild-lock i `includes/class-feed-fetcher.php`
- gennemgaa manuel refresh og cron-refresh for overlap
- goer tekstmigrering i `includes/class-ntc-settings.php` til en engangsopgradering
- tilfoej regressionstests for:
  - 48 timers prioritet
  - source priority
  - keyword fallback
  - save/load af feed-raekkefoelge
  - cache ikke invalideres ved rene designaendringer

Forventet gevinst:

- faerre race conditions
- faerre regressions
- sikrere videreudvikling

## Anbefalet raekkefoelge

1. Commit 1
2. Commit 2
3. Commit 3

## Ting vi bevidst ikke prioriterer nu

Vi bruger ikke tid paa:

- brede clean-code refactors uden konkret driftseffekt
- nye abstraktionslag kun for strukturens skyld
- nye features der ikke direkte forbedrer performance eller robusthed

## Genstartsprompt til naeste session

Brug denne prompt naeste gang:

`Fortsat arbejde paa RSS Carousel ud fra roadmap.md. Start med Commit 1: decouple feed cache from presentation settings. Hold dig kun til aendringer der forbedrer performance eller mindsker fejl. Laes roadmap.md foerst, undersoeg de relevante filer, implementer aendringen end-to-end, koer relevante checks, opdater versionsnummer hvis det er en rigtig release, byg ny zip i dist og forklar kort hvad der er aendret.`

## Praktisk note til naeste arbejdssession

Vi holder fast i denne arbejdsgang:

1. undersoeg de relevante filer foerst
2. implementer den konkrete forbedring
3. koer checks eller lint hvor det giver mening
4. commit
5. tag hvis det er en release
6. byg release-zip
7. push naar aendringen er klar
