# DAGUAH build plan

The plan the landing page design skill asks for before any code, followed by the stage list.

One offer, one audience, one action: **heritage fashion from verified African makers, for Africans anywhere, start shopping.**

Assumptions taken from the brief rather than asked again: a conversion is a first purchase; the three objections to answer are "is my money safe with a seller abroad", "will it fit" and "what will it really cost by the time it reaches me"; traffic is mixed (search, social, word of mouth), mobile first.

## 1. Page outline (home)

1. **The Opening** (hero): cycling word for market, headline, subheadline, one button, proof line, hands video behind
2. **Product search**
3. **Cultural Calendar**: ribbon of moments, collection below swaps with the moment
4. **Benefits**: the four promises
5. **Shop by occasion**
6. **Featured stalls**, each wearing its own cloth
7. **Market Live strip**: next or current session
8. **Lantern words**: the tagline reveal the skill requires, as its own moment
9. **How DAGUAH Hold works**: three steps. This is the problem to solution section and the risk reversal
10. **Testimonials**, placed directly after the Hold steps so proof sits beside the claim
11. **FAQ**: eight questions, with FAQ schema
12. **Seller call to action**: Open your stall
13. **Final call to action**: identical to the hero

## 2. Hero copy

- **Eyebrow:** the word for market, cycling: Ọjà, Ahịa, Kasuwa, Soko, Gebeya, Zando, Souk, Marché
- **Headline:** Wherever you are, the market is open.
- **Subheadline:** Heritage fashion, fabric and accessories from verified African makers and the diaspora. Your payment is held until your order arrives.
- **Button:** Start shopping
- **Proof line:** 4,183 pieces from 612 verified sellers, shipping to 37 countries

The proof line is prototype data. It ships as an editable block with a code comment, and must be replaced with real figures before launch. The spellings of the market words need a native speaker's review; that is flagged in the block.

## 3. Benefits

- **Paid only when it arrives.** DAGUAH Hold keeps your money until you confirm delivery.
- **See the full price first.** Item, delivery and duties in your own currency before you pay.
- **Know it fits.** Save your measurements once and every piece tells you if it fits.
- **Know who made it.** Every piece shows where it was made and who made it.

## 4. How it works (DAGUAH Hold)

1. You pay once, in your currency.
2. We hold the payment while the seller ships with tracking.
3. You confirm it arrived as described, and the seller is paid.

## 5. FAQ

**How does DAGUAH Hold protect my money?**
You pay once at checkout and DAGUAH holds the payment. The seller ships with tracking, and the money is released only after you confirm the order arrived as described. If something is wrong, report it from your order page. A person on our team reviews the evidence from both sides and decides: refund, part refund or release.

**What if the piece does not fit?**
Every listing shows measurements in centimetres, and Fit Mirror compares them with yours before you buy. If it still does not fit, the seller's returns rules are on the listing, and we can point you to a tailor for a small alteration.

**Which countries do you ship to?**
At launch: the United Kingdom, the United States, Canada, the European Union, Nigeria, Ghana and Kenya. More countries are being added, and every listing shows whether it ships to you.

**Will I pay customs duties?**
Sometimes. Before you pay, the landed price shows the item, delivery and an estimate of duties for your country, in your currency. The estimate is a guide. Customs in your country sets the final amount.

**How do sellers get paid?**
In their own currency, after the buyer confirms delivery. Every fee is shown before the seller lists, so the payout is never a surprise.

**How are sellers verified?**
Every seller passes an identity check. Sellers of higher priced pieces also join a video call, and we check samples when a listing makes an authenticity claim such as handwoven kente.

**Can I sell preloved pieces?**
Yes. Each preloved piece carries a Heritage Passport: a short story card of where it has been worn, which travels with the piece to its next owner.

**How long does delivery take?**
It depends on the route, and every listing shows the range for your address. As a guide, Lagos to London takes 6 to 9 days.

These answers describe policies (who decides disputes, how sellers are checked, which countries are served). They are drafted from the brief and need the client's confirmation before launch.

## 6. SEO and AEO

**Index:** home, shop, category pages, occasion pages, stalls, Journal.
**Noindex:** account, cart, checkout, seller studio, Circle pages, gift note pages, search results.

- **Home title:** DAGUAH: African heritage fashion, fabric and accessories
- **Home description:** Shop new and preloved African fashion, fabric and accessories from verified makers. Pay in your currency. Your payment is held until your order arrives.
- FAQ schema on the home page, in plain question and answer form
- Product schema on every listing through Rank Math
- Stall pages get a share image made from their cloth

## 7. Layout

**A, classic hero plus sections.** A marketplace is understood from its products, so they should appear early, and there is one action to lead to. A long form story page would hold the products back. The objection handling that long form is good at is kept as two sections: the Hold steps and the FAQ.

## 8. Plugins

Checked against wordpress.org on 12 November 2026, WordPress 7.1.4.

| Purpose | Plugin | Version |
|---|---|---|
| Commerce | WooCommerce | 11.3.0 |
| Marketplace | Dokan Lite | 5.3.4 |
| Marketplace | Dokan Pro | licence needed from the client |
| Payments, global | WooCommerce Stripe Payment Gateway | 11.2.0 |
| Payments, Africa | Paystack for WooCommerce | 5.8.6 |
| Multi currency | WooCommerce Multilingual and Multicurrency | latest, pinned in Stage 6 |
| SEO | Rank Math | 1.0.282 |
| Mail | WP Mail SMTP | 4.10.4 |
| Custom fields | none: native post meta with editor panels | |

ACF Pro is not used, so there is one less licence to buy. Aelia was the other multi currency option; it is paid, and the free plugin is tried first.

## 9. Hero concepts

Proceeding with **the Opening**. Two alternatives, in case it is not right:

- **The Bolt:** the page opens on one bolt of cloth that unrolls across the screen as you scroll, with the headline woven into it.
- **The Departure Board:** routes flip behind the headline like an airport board (Abeokuta to Croydon, 6 to 9 days), selling reach and delivery certainty first.

## 10. Stages

| Stage | What | Status |
|---|---|---|
| 1 | Environment, plugins, tokens, fonts, header, footer, mobile menu, buttons, cards, cloth generator, motion utilities | Built |
| 2 | Data model, settings page, REST endpoints, `wp daguah seed` | Built |
| 3 | Home patterns and blocks | Built |
| 4 | Shop, category, single product (Thread, Fit Mirror, landed price, Heritage Passport) | Built |
| 5 | Stall pages, occasion archive, Circle, Send Home, Market Live | Built |
| 6 | Cart, checkout, DAGUAH Hold statuses and payouts, Stripe and Paystack in test mode, emails, seller studio. Also: paying a circle share, the gift note and voice message on the order, and the gift card page | Next |
| 7 | Trust centre, Journal, 404, SEO, schema, favicon, cookie notice | |
| 8 | Polish, accessibility audit, Lighthouse, reduced motion, noon check, PHPCS | |

## 11. Needed from the client

None of these block Stage 2 or 3. All are needed before launch.

- A Dokan Pro licence (vendor verification, store reviews, refunds, follow store, Stripe Express)
- Legal and payments sign off on holding buyer funds. DAGUAH Hold stays in test mode until then
- Stripe and Paystack accounts
- Real proof figures and real testimonials to replace the prototype ones
- The hero video and photography, or a shoot brief to produce them
- The textile artists who will draw the cloth motifs, for the credits page
- Social handles, a contact address, and returns, shipping and privacy wording
- A native speaker's check of the market words
