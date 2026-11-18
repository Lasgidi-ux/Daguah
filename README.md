# DAGUAH

A marketplace for the African diaspora, built directly in WordPress: a custom block theme on WooCommerce and Dokan, plus a functionality plugin. No page builders.

| Package | Path | Job |
|---|---|---|
| Theme `daguah` | `wp-content/themes/daguah` | Presentation only: tokens, templates, patterns, styles |
| Plugin `daguah-core` | `wp-content/plugins/daguah-core` | Data, blocks, REST, marketplace logic. Survives a theme change |

**Status: Stages 1 to 5 of 8 are built.** Stage 1: environment, tokens, fonts, header, footer, mobile menu, buttons, cards, the cloth generator, motion utilities. Stage 2: the data model, DAGUAH settings, the REST endpoints and the seed command. Stage 3: the home page, as patterns and blocks. Stage 4: the shop, category and product pages, with the Thread, Fit Mirror, landed price and Heritage Passport. Stage 5: stall pages and the stall directory, occasion pages, DAGUAH Circle, Send Home with the gift note, and Market Live. See [docs/plan.md](docs/plan.md) for the plan and what each later stage adds.

## Setup

Needs Node 20 or later.

```bash
npm install
npm run assets
npm run build
```

`npm run assets` copies the self hosted fonts and Phosphor icons out of `node_modules`. `npm run build` compiles scripts and styles for both packages.

### Run it without Docker (WordPress Playground)

```bash
npm run fetch-plugins
npm run playground
```

`fetch-plugins` downloads WooCommerce and Dokan Lite into `.cache/plugins` once. `playground` opens the site at http://127.0.0.1:9400, logged in as admin, with both plugins active and the demo content seeded. WordPress and PHP run in WebAssembly, so nothing else needs installing. The database is in memory: stopping the server resets the site. First boot takes several minutes. Use `npm run playground -- --core-only` for the theme and plugin alone, without seeding.

Playground sends unknown pretty URLs (for example `/no-such-page/`) to the home page, so to see the 404 page there use a missing post ID such as `/?p=999999`. On a normal server the 404 page shows for any unknown address.

### Review screenshots

With the site running:

```bash
npm run screenshots
```

Saves desktop (1280), tablet (768) and phone (390) shots in both modes to `docs/screenshots/`, using a local Chrome or Edge with real device emulation, and reports any page that scrolls sideways.

### Walkthrough videos

```bash
npm run record
```

Records a tour of the site, home page first, then shop, a product, a stall, the stall directory, an occasion, a Circle board, Send Home (writing a gift note), Market Live and a session, to `docs/recordings/daguah-desktop.mp4` (1440 × 900) and `daguah-phone.mp4` (390 × 844). Page loads are cut out and the stops are joined with short dissolves. `npm run record -- desktop` records one. Needs ffmpeg. The recordings are git ignored, as they are large.

### Run it with Docker (wp-env)

```bash
npm run env
```

Uses `.wp-env.json`: PHP 8.2, MySQL, every plugin below, `WP_DEBUG` on. This is the environment to use from Stage 2 onward, because payments, vendor payouts and WP-CLI seeding need a real database. Site at http://localhost:8888, admin / password.

## Plugins

Versions checked against wordpress.org on 12 November 2026 (WordPress 7.1.4).

| Purpose | Plugin | Version | Notes |
|---|---|---|---|
| Commerce | WooCommerce | 11.3.0 | Block cart and block checkout |
| Marketplace | Dokan Lite | 5.3.4 | Vendor stores, commissions, withdraws |
| Marketplace | Dokan Pro | licence needed | Vendor verification, store reviews, refunds, follow store, Stripe Express. Not on wordpress.org: see below |
| Payments, global | WooCommerce Stripe Payment Gateway | 11.2.0 | Cards, Apple Pay, Google Pay |
| Payments, Africa | Paystack for WooCommerce | 5.8.6 | Cards, bank transfer, mobile money |
| Multi currency | WooCommerce Multilingual and Multicurrency | latest, to be pinned in Stage 6 | Runs in multicurrency only mode without WPML. To be vetted against Dokan payouts in Stage 6 |
| SEO | Rank Math | 1.0.282 | Product schema |
| Mail | WP Mail SMTP | 4.10.4 | Transactional mail |
| Custom fields | none | | Native `register_post_meta` with block editor panels, so no ACF Pro licence is needed |

Nothing else is installed.

### Licensed plugins

Dokan Pro is paid and cannot be downloaded by this repo. Put the zip in `licensed/` (git ignored) and add it in `.wp-env.override.json`:

```json
{ "plugins": [ "./licensed/dokan-pro.zip" ] }
```

Every feature that needs Dokan Pro checks for it and degrades to a clear message, never a broken page.

## Environment constants

None are needed for Stage 1. Payment keys and other secrets are never committed. From Stage 6 they are read from constants set in `wp-config.php` or `.wp-env.override.json`:

| Constant | Used for |
|---|---|
| `DAGUAH_STRIPE_TEST_SECRET` | Stripe test mode |
| `DAGUAH_PAYSTACK_TEST_SECRET` | Paystack test mode |
| `DAGUAH_DUTIES_API_KEY` | Optional duties API (for example Zonos) |

## The visual system

`wp-content/themes/daguah/theme.json` is the single source of truth: palette, colour roles, the Tailwind type scale, the spacing table, Tailwind radii and one motion curve. The Site Editor is locked to these values (no custom colours, font sizes or spacing).

- **Market at Dusk** is the default. **Market at Noon** is `styles/noon.json`. The toggle in the header switches between them and remembers the choice. Both sets of colour roles are read from those two files, so changing a colour is a one line edit.
- `npm run contrast` checks every text and background pair in both modes against WCAG AA.
- The system sheet page (`/system/`, the front page until Stage 3) shows every colour, text style, button state, card, cloth and motion rule in one place.

### Deliberate decisions to review

- **Primary button at noon is night with shea text**, and turns lantern on hover. Lantern on shea is only 1.92 : 1, too faint for a button edge. At dusk the primary button is lantern as briefed.
- **Occasion tags use white text on laterite** (4.96 : 1). Shea on laterite is 4.14 : 1, below AA for small text.
- **Font size preset slugs** are `xs` to `xxxl`, then `display-sm` to `display-max`. WordPress rewrites slugs that start with a digit (`2xl` becomes `2-xl`), so the Tailwind names could not be used as is. The values match the Tailwind scale exactly.
- **Headings step down on small screens** (Heading 1 is 36 / 40 on a phone, 48 / 48 on a tablet, 60 / 60 on desktop). Every step is still a scale step with its paired line height.

## The Cloth

`daguah-core/src/cloth.js` and `daguah-core/includes/class-cloth.php` are twins. Given the same seed and family they return the same SVG, byte for byte, so a cloth can be rendered on the server and animated in the browser.

```bash
npm run test:cloth
```

runs both and compares 72 cloths. Run it after changing either file.

### Swapping in the artists' motifs

Each motif is one small function that draws inside a 48 by 48 cell (`mConcentric`, `mMaze` and so on in the JS; `m_concentric`, `m_maze` in the PHP). To replace one:

1. Redraw it in `cloth.js` using whole numbers only and the palette constants.
2. Make the same change in `class-cloth.php`.
3. Run `npm run test:cloth`. It must report every cloth matching.

The current motifs are placeholders based on the general shape of each tradition. They are not the work of the credited artists and should not ship as such.

## Demo content

```bash
wp daguah seed
wp daguah reset --yes
```

`seed` creates 8 stalls (through Dokan), 24 pieces (through WooCommerce), the occasions, 10 calendar moments, a sample DAGUAH Circle with five members in five countries, three Market Live sessions (one in 3 days, one in 10 days, one 6 days ago; times are relative to the day the seed runs, so the schedule never goes stale), the Journal article "How to tell real kente from printed kente", every page the footer and menus link to, the navigation and the demo rates. It also sets the store to pounds sterling and the United Kingdom.

It is safe to run again. Everything it makes carries a `_daguah_seed` marker, so a second run updates what the first one made instead of duplicating it. Pages and the navigation are only created when missing, so Site Editor changes survive. The one exception: a seeded page still holding its "This page arrives in stage N" placeholder gets its real opening paragraph once that stage is built. A page that has been edited is left alone.

`reset` deletes only marked content. Pages, terms and users that existed before seeding are never touched, and the settings and the fabric, condition and size attributes stay.

The stalls, makers, buyers and prices are invented for review. Stall emails use the reserved `.test` domain, passwords are generated and never shown, and all email is held back while seeding. Replace everything with real data before launch. The data lives in one file: `daguah-core/includes/seed/data.php`.

Until Stage 3 adds the home page, the System sheet is the front page. Once the theme ships the `daguah/home` pattern, the next seed creates a Home page and makes it the front page.

## Data model

All of it lives in `daguah-core`, so a theme change loses nothing.

**Product fields** (WooCommerce product, DAGUAH tab): measurements in centimetres (bust or chest, waist, hip, length, shoulder), made by, made in (town), delivery corridors (ships from, delivers to, min and max days, price in pounds), short video link, Heritage Passport (year, place, story), authenticity notes, and the intended photo shown until a real one exists. Repeaters show the saved rows plus two empty ones; save for more.

**Not repeated as fields:** fabric, condition and size are WooCommerce attributes (`pa_fabric`, `pa_condition`, `pa_size`) so the shop filters can use them, and origin country is the `origin_country` taxonomy. One place each, nothing to keep in sync.

**Taxonomies:** `occasion` and `origin_country` on products. Each country term stores its ISO code.

**Stall fields** (Users, edit a seller): region, ships from, selling since, usual reply time, story, voice intro and verified level. Only administrators can change the verified level. The region picks the stall's cloth family, and the cloth is made and cached when the stall opens and whenever its name or region changes.

**Post types** under the DAGUAH menu, each with a Details panel in the editor sidebar:

| Post type | Fields |
|---|---|
| Calendar moments | starts, ends, comes round every year, occasion to show, only this category, pieces to show, date note |
| Circles | host, fabric, yards per person, delivery (split or to the host), closes; members listed below the editor |
| Live sessions | stall, market, starts and ends (UTC), stream link, pieces in the tray |

Circles are always private, whatever status is chosen. Members' names and cities are only shown through the circle board endpoint.

### Adding an occasion, a calendar moment or a live session

- **Occasion:** Products, Occasions, add one. Tag pieces with it on the product screen.
- **Calendar moment:** DAGUAH, Calendar moments, add one. Set the dates and the occasion it should show in the Details panel. Order the ribbon with the Order field.
- **Live session:** DAGUAH, Live sessions, add one. Pick the stall and the pieces for the tray in the Details panel.

## The home page

The Home page is built from one pattern per section (`daguah/home-opening`, `daguah/home-calendar` and so on), gathered by `daguah/home`. The seed command puts that on a page called Home and makes it the front page. The first time the page is opened in the editor, every section becomes ordinary blocks: text, buttons and links can be edited in place. Each section is locked to its layout (content only), so the structure cannot break by accident.

| Section | What to edit |
|---|---|
| The Opening | Headline, subheadline, button, proof line. The film behind it is described below; to use a different video, Replace in the cover's toolbar. The words for market are in the Market word block's sidebar |
| Search | Heading and the popular searches line |
| Cultural calendar | Section heading. The moments themselves live under DAGUAH, Calendar moments |
| Benefits, DAGUAH Hold, FAQ | Every title and sentence. The FAQ's structured data is built from whatever the questions say |
| Shop by occasion, Featured stalls | Pick occasions or stalls in the block sidebar, or leave empty for the busiest and most verified |
| Market Live | Nothing on the page: it shows the session on now or the next one, and hides itself when none is scheduled or Market Live is switched off |
| Lantern words | The three lines, in the block sidebar |
| Testimonials | Quotes and names. The seeded ones are prototype content |

The Opening always shows the market at dusk, in both modes: it is a deliberate dark band.

### The hero film: "One Cloth, Many Hands"

Five shots of hands at work: indigo lifted from the dye pot in Abeokuta, a shuttle on a Bonwire loom, lace unrolled at Makola, leather tooled in Zaria, and a parcel opened in a London flat. The last shot's indigo cloth dissolves into the dye pot, so the loop has no seam. 15.7 seconds, one grade across all five (cooler shadows, warm lantern light, fine grain), no sound.

| File (in `themes/daguah/assets/video/`) | Size | Shown |
|---|---|---|
| `opening.mp4` | 1280 × 720, 2.0 MB | Screens wider than 4:5 |
| `opening-portrait.mp4` | 540 × 960, 1.2 MB | Phones in portrait, reframed shot by shot around the hands |
| `opening.jpg`, `opening-portrait.jpg` | still | Until the film is ready, and instead of it (below) |

- It plays only once the theme script starts it, so visitors who prefer reduced motion, or who have asked their browser to save data, see the still. A pause button sits in the bottom corner, as any moving background longer than five seconds needs one, and the choice is remembered on the device. The film also rests while it is scrolled out of view.
- A shade behind the words keeps the headline readable: dark on the left where the text sits, open on the right where the hands are, and solid across the film on phones. The cover's own overlay is 30%.
- The five clips are AI generated from a five shot brief. The two loom and dye clips are 1280 × 720; the lace, leather and parcel clips are only 640 × 360, upscaled, which is soft at full width. Footage of real makers, at 1080p or better, would be the upgrade.

**Rebuilding it.** The source clips live in `media/hero/` (git ignored), named in running order: `1-dye.mp4`, `2-loom.mp4`, `3-lace.mp4`, `4-leather.mp4`, `5-parcel.mp4`. Replace any of them, then run `npm run hero-video` (needs ffmpeg). Which part of each clip is used and where the portrait crop sits are set at the top of `scripts/hero-video.mjs`.

**Blocks added in Stage 3:** Market word, Lantern row, Cultural calendar, Shop by occasion, Featured stalls, Market Live strip, Lantern words and Icon. All render on the server; their scripts load only on pages that use them.

**Prototype content to replace before launch:** the proof line ("4,183 pieces from 612 verified sellers, shipping to 37 countries") and the three testimonials. The spellings of the eight words for market need a native speaker's check.

## Shop and product pages

**Templates:** `archive-product`, `taxonomy-product_cat`, `taxonomy-occasion`, `taxonomy-origin_country` and `product-search-results` share one layout (the `daguah/shop-layout` pattern): filters beside the results. `single-product` builds the product page from DAGUAH blocks around WooCommerce's own title, price, summary and add to cart blocks. The stall card is a template part (`stall-card`), so it can be restyled in one place.

**Filters** are plain links (`?fabric[]=lace&to=GB&ships=1`), so the shop works without JavaScript, and every filtered view can be shared or saved. Category, occasion, fabric, condition, size, made in, price in the visitor's currency, delivering to, ships to me, arrives within, verified sellers only and Fits me. Each active filter shows as a chip that removes it.

Delivery filters read a small index (`_daguah_days_GB` and so on: the slowest delivery to each zone), rebuilt whenever a piece's corridors change. After importing products another way, run `wp daguah reindex`.

**Saved searches:** signed in buyers save any filtered view. A daily job emails them new matching pieces (it needs working email: WP Mail SMTP, configured in Stage 6). They manage the list under My account, Saved searches.

**Visitor choices** (currency, country, city, measurements) are kept on the device. Signed in buyers' measurements are also kept on their account, merged on sign in with the newest value winning per measurement. Prices stay in pounds at checkout until the multi currency plugin is connected in Stage 6; until then other currencies show beside the price as "≈ ₦131,480", labelled as demo rates.

**Fit:** a piece fits when every measurement both sides have is within 2 cm, needs a small alteration within 5 cm, and is not your size beyond that. The rule is identical in PHP (the Fits me filter) and JS (the chips); `npm run test:fit` checks they agree. It compares the piece directly with the body, as briefed. Real garments are cut with some ease, which varies by style; an allowance per category is a decision for the client.

**The Thread** draws from the piece's town to the buyer's city on a dot map of North America, Europe and Africa. Towns the map knows are in `includes/class-geo.php`; a piece from a town not on the list starts from its country's main city. The map outline is deliberately coarse.

**Unrolling the bolt:** between shop pages, browsers with cross document view transitions roll the old page away and settle the new one in. Others get a short settle on arrival. Reduced motion turns both off. The Thread also appears as a divider style (Separator, Thread) and as the loading bar across the top while the next page loads.

**Not in Stage 4:** "Pass it on" (relisting a preloved piece from My account) needs the confirmed delivery status that DAGUAH Hold adds, so it arrives with Stage 6. The cursor thread is part of the Stage 8 polish.

## Stalls, occasions, Circle, Send Home and Market Live

Stage 5. Every page is a theme template built from `daguah-core` blocks, and each page's opening paragraph is the page's own content, so it is edited in the page editor. The rest of the words on the page live in the template (Site Editor) or the blocks.

| Page | Address | Template | Blocks |
|---|---|---|---|
| Stall | `/store/{stall}/` (Dokan's address) | `stall` | Stall header, Stall pieces |
| Stall directory | `/stalls/` | `page-stalls` | Stall directory |
| Occasions | `/occasions/` | `page-occasions` | Shop by occasion, with "Every occasion" and "Show when each comes round next" on |
| One occasion | `/occasion/{slug}/` | `taxonomy-occasion` (pattern `daguah/occasion-layout`) | Occasion intro, then the shop filters and results |
| DAGUAH Circle | `/circle/`, and `/circle/?c=ID&key=KEY` for a board | `page-circle` | Circle board, Start a circle |
| Send Home | `/send-home/`, `?to=NG` for a country | `page-send-home` | Send Home, Gift note |
| Market Live | `/live/` | `page-live` | Market Live schedule |
| One session | `/live/{slug}/` | `single-live_session` | Market Live stage, Market Live tray |

**Stall pages.** Dokan still owns the stall address and the query behind it (the stall's pieces, paged). When the active theme has a `stall` block template, `daguah-core` shows it in place of Dokan's PHP page; any other theme keeps Dokan's page. The header shows the stall's cloth, name, the maker and place, the whole story, selling since, reply time, ships from, the number of pieces, which checks DAGUAH has done (identity, video call, samples), the voice intro when the stall has one, and the stall's next Market Live session. Category chips above the pieces open the shop with the new `stall` filter (`/shop/?stall=iya-adire-studio&cat[]=women`), so every shop filter works on one stall's pieces too. Follow and store reviews need Dokan Pro and are left for when the licence is in.

**Stall directory.** Every open stall, most verified first, then longest selling. Filter by the country a stall ships from and by verified stalls only; both are plain links.

**Occasion pages** open with the occasion's cloth (the same one as its tile), its description, the number of pieces and when it next comes round on the Cultural Calendar. Occasions families dress for together (aso ebi, traditional weddings, naming ceremonies, funerals) offer a DAGUAH Circle; gifting occasions offer Send Home. Both lists are filters: `daguah_circle_occasions` and `daguah_send_home_occasions`.

**DAGUAH Circle.**

1. A signed in buyer fills in Start a circle: a name, the fabric (pieces in the Fabrics category), yards per person, delivery (each person at their own address, or everything to the host), the closing date (after today, within a year), an optional note to the family, and their own name, city, country and currency.
2. They land on the circle's board with the invite link, a copy button and a WhatsApp link. A host can have 10 open circles.
3. Anyone with the link sees the board: the host's note, the fabric and its price, yards each, delivery, the closing date, how many have paid, and each member by first name, city, country and currency. The board refreshes every 20 seconds while it is open.
4. They join with their name, city, country and currency. Signed in members are linked to their account and can open the board later without the key. Guests can join too; a connection can add 12 guests an hour. A circle takes up to 60 members and refuses new ones after its closing day.
5. "Pay your share" is shown to members but switched off until the cart arrives in Stage 6, which creates each member's order and marks them paid.

Both forms post to `admin-post.php`, so they work without JavaScript; a mistake brings the form back with what was typed and a message beside each field. Board addresses are `noindex, nofollow`. A wrong key and a missing circle give the same answer. The hooks `daguah_circle_started` and `daguah_circle_joined` fire for the emails Stage 6 adds.

**Send Home.** Choose where the gift is going (Nigeria, Ghana and Kenya one tap away, every other buyer country in a list). The pieces that deliver there show fastest first, with a link to the shop filtered to that country. The gift note takes who it is for, a note of up to 300 characters, who it is from and a voice message of up to a minute, recorded in the browser, beside a preview of the printed card. It is kept on the buyer's device; checkout attaches it to the order in Stage 6, and nothing is uploaded before then. The page the family reaches by scanning the card arrives with it.

**Market Live.** The schedule lists the session on now or next, later sessions, and the last six. Times are stored in UTC, printed in GMT and rewritten in each visitor's own time. A session page shows a countdown and an "Add to my calendar" file (`?calendar=1`, with a 15 minute reminder) before it starts. While it is on, and afterwards when there is a replay, a play button loads the stream's player: YouTube (from its no cookie address), Mux or Vimeo. Nothing from the stream's site loads until the visitor presses play. Other stream links are offered as a plain link. The tray below is ordinary product cards, so price, fit and the landed price work as in the shop. Chat stays in the stream's own player.

**Feature flags.** With Circle, Send Home or Market Live switched off in DAGUAH settings, its page says it is resting and points to the shop, session pages answer 404, and starting or joining a circle is refused.

**Blocks added in Stage 5:** Stall header, Stall pieces, Stall directory, Occasion intro, Circle board, Start a circle, Market Live schedule, Market Live stage, Market Live tray, Send Home and Gift note. Their styles are one file, `daguah-core/assets/market.css`; the page spacing is `themes/daguah/src/css/market.css`.

## DAGUAH settings

DAGUAH, Settings in the admin menu:

- **Currency:** demo rates per pound for USD, CAD, EUR, NGN, GHS and KES, or rates from the multi currency plugin once it is wired up in Stage 6. While demo rates are in use, every converted price is labelled "Demo rates".
- **Duties estimate:** a percentage and a threshold per delivery zone. The seeded figures are placeholders, not customs advice.
- **Commission** and **how many days DAGUAH Hold waits** before confirming delivery on its own (applied in Stage 6).
- **Feature flags** for Circle, Send Home, Market Live, market sound, the cursor thread and List by WhatsApp (off).

## REST endpoints

Namespace `daguah/v1`. Signed in requests need the `X-WP-Nonce` header; without it WordPress treats the request as signed out.

| Route | Who | Returns |
|---|---|---|
| `GET /fit-profile` | signed in | Unit and measurements in cm |
| `POST /fit-profile` | signed in | Saves the measurements sent; `null` removes one; values 20 to 250 cm |
| `GET /circles/{id}/board?key=` | invite key, host, members, editors | The circle's title, host's first name, fabric, yards, delivery, closing date, and members' first names, cities, currencies, paid or waiting |
| `GET /moments/{id}/collection` | anyone | The product cards for one calendar moment, as HTML, for the ribbon to swap in |
| `GET, POST /saved-searches` | signed in | The buyer's saved searches; POST saves a shop query string |
| `DELETE /saved-searches/{id}` | signed in | Removes one |
| `GET /currency/rates` | anyone | Rates from GBP, their source and label |
| `GET /landed-price?product=&country=&currency=&quantity=` | anyone | Item, delivery and days on the corridor, estimated duties, total, all in one currency |

A circle that does not exist and a wrong key get the same 404, so circle IDs cannot be probed. Integration points for later: the `daguah_currency_rates` filter for the multi currency plugin and the `daguah_duties_estimate` filter for a duties service such as Zonos.

## Checks

| Command | Checks |
|---|---|
| `npm run verify` (or `node scripts/playground.mjs --verify=4` for one stage) | Boots a fresh site, seeds it, then runs the checks inside WordPress. Stage 2 (47 checks): seeded counts, product and stall data, cloth caching, landed price maths, every REST route signed in and out, circle privacy, settings validation, reseeding without duplicates, reset and reseed. Stage 3: every home section renders, one h1, calendar order and collections, FAQ structured data, and the copy rules (no hyphens inside words, no banned words, no exclamation marks). Stage 4: every filter and its counts, the delivery index, fit, saved searches and their daily emails, full renders of a product page and the shop, and that Dokan's bookkeeping product never shows. Stage 5: the stall template replacing Dokan's page, full renders of a stall, an occasion, a live session and the five Stage 5 pages, the directory and stall filters, the schedule, player links and calendar files, starting and joining circles (keys, members, closing, limits, first names only, noindex), Send Home's order, the flags, and the copy rules. Results go to `.cache/results/`; the site stays up afterwards. A full run takes over an hour in WebAssembly; one stage is much quicker |
| `npm run lint:php` | Every PHP file in the theme and plugin parses under PHP 8.2 (runs in WebAssembly) |
| `npm run test:cloth` | The JS and PHP cloth generators agree |
| `npm run test:fit` | The JS and PHP fit rules give the same verdict and differences |
| `npm run contrast` | Colour contrast in both modes |
| `npm run screenshots` | Review screenshots, and sideways scroll at each width |

## Coming in later stages

- How DAGUAH Hold order statuses work (Stage 6)
- Paying a circle share, attaching the gift note and voice message to an order, and the page the family opens from the gift card (Stage 6)
- Follow a stall and stall reviews, once Dokan Pro is installed

## Code standards

WordPress Coding Standards, escaped output, strings translatable under the `daguah` text domain. PHPCS runs in Stage 8 (it needs Composer, which this machine does not have yet).
