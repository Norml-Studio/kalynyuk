# Daily Changelog — Anna Kalynyuk

<!-- Entries go under a `## YYYY-MM-DD` header for today. When a new day
starts, this file is compressed into weekly.md. See README.md for the protocol. -->


## 2026-08-05

Rolled 2026-08-04 into `weekly.md` under **Week 2026-W32** per `README.md`. No
`weekly.md` → `changelog.md` rollover was due — 2026-08-05 is still W32.

### The calculator session — arithmetic first, then language

The calculator is the last Divi section on the homepage and the only one still Divi on
production. Locally it is now fully native in **both** languages: `.et_pb_section` is
**0** on `/` and `/pt/`.

#### Anna's reference simulator settled three open questions

She sent two screenshots of the tool she wants this to match. Checking our formulas
against her numbers **validated the core and found one clear defect**.

Confirmed identical on both her cases: the annuity payment to the cent (1045.43 and
1592.24 €), TAN = indexante + spread, Imposto do Selo = 0.8% of the acquisition value,
and DSTI = payment / net income at the **contract** rate.

- **That independently confirmed the DSTI fix and closed the stress-increment question.** The old code computed DSTI at `fixedRateUI − INDEXANTE_CONST` — 2.80% − 2.143% = 0.657%, a rate no borrower pays — so the page contradicted itself in plain sight: a payment of 925,89 € against an income of 2 500 € while reporting DSTI 30%, where 925.89 / 2500 = 37.0%. Her tool applies **no** stress increment, so none is added here either.
- ⚠️ **LTV truncates, it does not round.** 230 000 / 350 000 = 65.71 shows as **65** in her tool (we said 66); 350 000 / 450 000 = 77.78 shows as **77** (we said 78). Both point the same way and **the direction matters** — LTV is a bank threshold, so rounding up overstates it and can make a case look outside a limit it actually meets.

#### The slider took four attempts because I was styling the wrong element

The control rendered 20px instead of 50, so the thumb overflowed its own box and the tap
target was smaller than the design's.

- **The height needed `!important`, and the flag is evidence-based rather than habit:** setting the height inline from devtools gave 20px, inline `!important` gave 50px. An inline declaration outranks every stylesheet selector, so only another `!important` can beat it — one exists in Divi's CSS for this element. Specificity cannot win; a doubled class was tried first and did not.
- ⚠️ **The "50px pill" was never the native `::-webkit-slider-runnable-track` and never the new wrapper — it was a background Divi paints ON THE INPUT ITSELF.** Adding `!important` to the pseudo-track changed nothing, correctly, twice. Both attempts were reverted rather than left in as decoration.
- **Fixed by making the track OUR element.** `.calculator__track` is a 20px bar drawn under a transparent input, so Divi has no selector that reaches it. Only the thumb still needs a pseudo-element, because it has to move with the value — pinned at 48px with Divi's ETmodules chevron blanked.
- ⚠️ **Divi was winning on load order, not specificity.** Per `docs/05-issues.md`, `et-divi-customizer-global.min.css` is enqueued AFTER our stylesheet, and **between two `!important` declarations the cascade falls back to specificity** — so `.calculator__range { background: transparent !important }` at (0,1,0) lost. Raising the selector to (0,3,0) wins regardless of order.
- ⚠️ **`getComputedStyle(el, '::-webkit-slider-thumb')` is not a usable check — it returns the HOST element's styles**, which once made a failed attempt look like a pass. The only reliable check is the rendered pixels.

#### The section was hardcoded Ukrainian — the second half of the `/pt/` breakage

Yesterday's diagnosis found `/pt/` had **two** problems: unstyled *and* in Ukrainian. The
native migration fixed the first by construction. This fixed the second.

Because the calculator already renders natively on `/pt/`, 11 labels frozen in the
template showed up as a **fully Ukrainian calculator sitting in the middle of the
Portuguese homepage** — 27 Cyrillic strings across text nodes and `aria-label` /
`placeholder`.

- Routed through `ak_str()`: the six field labels, the rate label and its «(річна)» note, the two mode names, the form title. **Each field label is resolved ONCE and reused as the range input's `aria-label`**, so assistive tech gets the same words in the same language.
- **[DECISION] The metric labels stay Portuguese in every language, untranslatable by design** — Mensalidade, Montante, Prazo, LTV, TAN, Indexante, Spread, Imposto Selo, IMT Cont., plus the two stepper `aria-label`s. They are Portuguese banking terms and acronyms, and they are the terms a client meets on the bank's own paperwork; the same call already made for «Livro de Reclamações» in the footer. Ukrainian is the default language, so Polylang returns the source for it regardless — registering them would buy nothing on uk and would invite a pt "translation" that renames a term away from the one the bank uses.
- ⚠️ **The radio `value` attributes stay English.** `updateCalculation()` branches on the literal `'fixed'` / `'variable'`, so they are as load-bearing as the ids. The block comment now says so, since the labels beside them just became translatable.
- ⚠️ **`ak_calc_indexante` is deliberately NOT seeded per language.** It is a *number*, not copy. `ak_section_field()` falls back to the default language, so pt reads page 11's value; writing it per language would mean updating the Euribor in four places and letting them drift — the exact failure the editable index was introduced to end. Verified: unset on 2483, resolves to 2.143, and the value reaches the JS (variable mode shows Indexante 2.143%).
- ⚠️ **Gravity Form 3's sources were read out of the form object, never retyped.** GF stores **straight** apostrophes ("Ім'я", "зв'яжемося") where the theme uses typographic ones, and `pll__()` matches on the source — one retyped character seeds a translation nothing ever looks up. The confirmation's `<strong>` and hard newline survive because only the words are swapped inside it. `Згода` and the consent sentence needed nothing: byte-identical to form 1's, already translated by sharing the source (yesterday's side effect, working as designed).
- **«калькулятор» is rendered as "Simulador", not "Calculadora"** — the Portuguese word for this instrument, and what Anna's own reference tool is called. ⚠️ It is also the pt search term, so this is an **SEO choice as well as a translation one**; flagged in the seed rather than buried, and it changes in one place if Anna prefers otherwise.
- Page 2483 already carried translated pt meta for **every** section except the calculator, so seeding `ak_calc_heading` / `ak_calc_intro` there is the last row of an existing pattern, not a new one. The seed refuses to overwrite a non-empty value.

#### Verified — 47/47, both languages, 1440 and 375

Native section with zero Divi originals · all 32 contract ids · indexante falling back to
2.143 · `h2` with exactly one `h1` on the page · English radio values · `aria-label`
matching the visible label · 50px control · **zero Cyrillic anywhere in the pt
calculator** · **uk unchanged string for string** · payment 925,89 € · DSTI 37% · LTV
truncating to 65% · TAN = indexante + spread in variable mode · no overflow · no console
errors beyond the known Review Wall `remodal.min.js` one.

⚠️ **Two of the checks were themselves wrong and were fixed rather than explained away.**
The `pageerror` filter matched on the message, which carries no filename, so it swallowed
the remodal error — and would have swallowed a real error of ours with the same shape; it
now matches the stack. The overflow probe flagged Gravity Forms' hidden field, parked at
`left: -9999px` with `visibility: hidden` by design; it now skips only genuinely hidden
elements, so a *visible* element at a negative left still fails. A green suite that is
green for the wrong reason is worse than a red one.

### The ⓘ badges now open real help — and the copy was never missing

**[DECISION] Petr: port the text and build real tooltips** (the alternatives were removing
the badges or leaving them decorative).

All 16 badges were `aria-hidden` spans with nothing behind them. The copy was not lost —
it was in page 11's `_et_pb_custom_css` as CSS `content:` strings on
`.tooltip:hover::after`, which is exactly why no content search ever found it and why it
did not survive the native rebuild. 1 460 characters, ported.

- ⚠️ **The copy is PORTED, NOT WRITTEN.** It quotes bank thresholds (70–80% LTV for non-residents, terms of 10–40 years, ages 75–80). Anna's reviewed text; a wording question goes to her. The PHP array was **generated from the meta**, not retyped — two strings carry a straight apostrophe and one a pair of straight double quotes, and `ak_str()` resolves by source.
- ⚠️ **The mapping was verified, not assumed.** 20 candidate strings for 16 badges, and the selector names lie: the down-payment tip lives on `.loan-term-input`, whose label in the markup is «Сума першого внеску». Every pair was confirmed by reading each block's visible label out of `post_content`.
- ⚠️ **Four tooltips deliberately not ported.** `.loanam`, `.lterm`, `.lprc`, `.imt-ilhas` are absent from `post_content` entirely — orphan rules for modules that no longer exist, **the same debris class as the consent-checkbox rule** in `docs/05-issues.md`, and the first concrete instance of that sweep being worth doing. Three duplicate a tooltip we do port.
- 📌 **Flagged for Anna, not silently fixed: the two IMT tooltips look swapped.** The dead `.imt-ilhas` (islands) carries the accurate general definition — «Податок на передачу нерухомості, що діє в континентальній частині Португалії» — while the live `.imt-cont` carries the vaguer «Спеціальні податки на материковій частині». We ship the **live** text, because that is what visitors read today.

#### A disclosure, not a hover tooltip — and the original shows why

⚠️ **Divi hid all 16 below 900px:** `@media (max-width: 900px) { .tooltip { display: none
!important } }`. That is the honest response to a hover-only control on a touch screen,
and it also removes the help from the visitors most likely to need it. Two more defects in
the same rules: `bottom: 17` (**no unit**, so the declaration was invalid and silently
dropped — the panel was never where its author intended) and `font-size: 12px;
line-height: 1em` on 250-character paragraphs.

A `<button>` with `aria-expanded` works on touch, satisfies WCAG 2.2 §1.4.13
(dismissible / hoverable / persistent), and reuses the contract the «Довідка» panel in
this same section already had — one pattern here instead of two.

⚠️ **Structural consequence:** `.calculator__label` is now a `<div>` with the `<label>`
inside it. `<button>` is a **labelable** element and a `<label>` may not contain one other
than its own control, so the old nesting would have been invalid HTML.

#### Three defects of my own, caught by the tests

- ⚠️ **`visibility: hidden` still OCCUPIES LAYOUT.** Sixteen closed 310px panels pushed the document into a horizontal scroll at *every* width — including 1440, where nothing looked wrong. Now the `hidden` attribute, mirroring the «Довідка» two-step: the attribute owns presence, a class owns the fade.
- ⚠️ **Escape closed the panel, restored focus, and the focus handler reopened it.** Guarded with a flag consumed synchronously by `.focus()` and cleared after — focusing an *already*-focused button fires no event, so the flag would otherwise swallow the next genuine focus.
- ⚠️ **Edge collisions SHIFT; they do not flip.** My first attempt swapped `left: 0` for `right: 0`, which only helps when the panel fits on the other side. At 375 it is 310 wide and neither side has room, so all 16 hung off an edge. Clamping to both edges is correct at any width — and it also replaces the original's `.tlt-right`, hand-set on five badges by eye and therefore wrong at every width its author did not open.

⚠️ **The ±8 hit-area trick from `.site-nav__item` was tried and REVERTED, and the reason
generalises:** it overflowed the results panel into a document scroll, and below each
field label the neighbour is the **input** — the grown target sat over a form control and
swallowed its clicks. The header could grow because it grew into the dead 16px gaps
between rows; there is no dead space here. **24×24 already meets WCAG 2.2 §2.5.8 (AA)**,
and `$tap-min`'s note is about not *shrinking* a visual to match art — nothing is shrunk,
24 is the Figma size.

`_calc-tip.scss` is its own partial: `_calculator.scss` is already 723 lines against the
400–500 rule, and a file being over is not a licence to push it further.

#### Verified — 47/47 (contract) + 48/48 (tooltips)

Both languages at 1440 and 375. 16 disclosures · every ⓘ a real button · `aria-controls`
wired · closed panels out of the a11y tree **and** out of flow · 16 distinct accessible
names · no button inside a label · no empty panel · click / second click / Escape /
click-outside / keyboard focus all correct · one panel at a time · all 16 panels on screen
· no document scroll · arithmetic unchanged.

⚠️ **Two harness bugs fixed as well, both of which made checks pass or fail for the wrong
reason.** `.calculator__label span:first-child` was matching the *visually-hidden
accessible name inside each button*, so the label list came back as 16 «Пояснення: …»
strings. And every `elementFromPoint` probe was reading **The Preloader plugin's full-page
overlay** — which keeps intercepting clicks for ~1s after it fades to `opacity: 0`, then
leaves the DOM at ~2–3s, on production as well as locally. Any future hit-test on this
site must wait for `#the-preloader-element` to be gone.

📌 **The find of the session, and it argues for the zero-Cyrillic sweep as a standing
check:** `ak_calc_tip_label` — the ⓘ button's accessible **name** — was registered and
never seeded, so `/pt/` announced "Пояснення: Valor do imóvel" to screen readers. It lives
*only* in a visually-hidden span. **No sighted check could ever have caught it**; the
automated Cyrillic sweep did, on the first run after the feature landed.

### Built the section to the frame — Petr: «дизайн не очень, можешь по макету»

Measured Figma `1130:9148` against the render instead of eyeballing it. The section was
off the design in six structural ways, and one of them explains the rest.

📌 **`design.md` §13 gap #10 had warned about exactly this** — "the calculator almost
certainly has values not in this contract, rescan before touching it". Every value earlier
sessions used had been derived from the frame by hand with nothing to check it against,
which is why «не очень» had no obvious answer until the frame was measured.

- **THE CARD WAS MISSING ENTIRELY**, and it was most of the problem. Frame 114 is a 1376×812 card — `$color-surface` #fef8ef, radius 24. Without it the fields sat on the bare page canvas and the sunken result panel read as a stray grey box instead of a well inside a card. ⚠️ The two insets differ on purpose: the panel is inset 24 from the card, the fields a further 16 (= 40 from the card edge).
- **The head was centred.** Frame 9122 is a SPACE_BETWEEN row — heading 506 at the container edge, right column 680 at x=696 with the intro and then the «Довідка» button 32 below. That is the spine `about` / `order` / `faq` / `seo` already use, so centring made the calculator the one section off the page's grid. Reused their `664fr/680fr` + `$space-4` pattern rather than deriving a new one; head→card is now 56.
- ⚠️ **The inputs were losing to Divi on SPECIFICITY, not load order — and our declarations were correct all along, they simply never applied.** Divi ships `input[type="text"] { background: #fff; border: 1px solid #bbb; padding: 2px }` at (0,1,1) against `.calculator__input`'s (0,1,0), so the field rendered white, grey-bordered and **31px tall against the design's 43**. Fixed with a doubled class (0,2,0) and **no `!important`**, because Divi's rule has none — deliberately unlike the range input, where Divi *does* use it and order is what decides. Measured with `CSS.getMatchedStylesForNode`, not guessed.
- **Field row gap 64, was 32** — the frame's rows start 216 apart around a 152-tall field. At 32 the six fields read as one dense block, which is much of what «не очень» was pointing at. Needed a new token: **`$space-9`**, added to `design.md` *before* use per the CLAUDE.md rule, with the same reasoning `$space-7` got. ⚠️ Numbered 9 though it sorts between 7 and 8 — the scale is a name sequence, and renumbering `$space-8` would silently turn 120 into 64 everywhere.
- **Result panel**: padding 50 not 40, and the payment block got its air back — 85 from the panel top, 67 to the metrics row, against our 50 and 24. ⚠️ That air is why the frame's headline measures 151 against our 78: it is *padding, not content*. Rows now track the frame within ~10px — 82/224/304/385/410/535 against 85/233/311/389/413/515.
- **Stepper** is 243 wide with the value centred; it had collapsed to its content so the −/+ sat on the number. The 6px padding is the geometry that actually renders (43 − 31 = 12), not the frame's stated 16/10.
- ⚠️ **The panel had to switch from grid to flex on desktop, purely for `margin-top: auto` on the form.** Under `grid` + `align-content: start` the free space packs AFTER the last row, so an auto margin has nothing to absorb — the first attempt changed nothing and the dead gap survived it.

#### 📌 Dropping one class fixed a bug that had nothing to do with the design

`contact-form` came off the form's class list. **All 11 `.contact-form` rules on this page
come from one source** — `et-builder-module-design-deferred-11-cached-inline-styles`,
Divi's generated CSS for a module no longer in `post_content`. This is the orphan-CSS class
recorded in `docs/05-issues.md`, and **the calculator was the last thing still opting into
it.** Two of those rules were actively wrong:

- `margin: 24px 0 40px` + `margin-bottom: 70px !important` out-cascaded our layout at equal specificity (it loads later) and left a dead **221px** gap under the submit button *inside* the panel;
- ⚠️ `.contact-form .ginput_container_checkbox { position: absolute; bottom: 40px !important }` — with no positioned ancestor the consent checkbox anchored to `.et_builder_inner_content` and rendered at **y=9512 while the calculator sits at y=5861**. The consent checkbox was floating **~3 650px away, over the FAQ**. Measured, not inferred — and it is the same bug the 2026-08-04 entry diagnosed and parked.

For a licensed credit intermediary the consent has to be **in** the form it belongs to, so
this was never only cosmetic. The one thing worth keeping — the checkbox and label styling
— is reproduced from tokens using the footer's proven mechanism (including why its
`!important`s are needed), rather than inherited from CSS that phase 4 deletes.

#### Verified

47/47 (contract) + 48/48 (tooltips), both languages. No overflow and no horizontal scroll
at **375 / 768 / 1024 / 1600 / 2560**; the card measures 1376 at every width ≥ desktop, per
the container rule. Consent checkbox now `static`, inside the form, 556 wide.

`design.md` bumped to **v2.2.0**: §7 gains the full calculator spec, §4 gains `--space-9`,
and §13 gap #10 is marked closed **for desktop only** — the frame has no tablet or phone
state, so that caveat is kept rather than quietly dropped.

### Petr's five corrections — and one of them was a dead control

- ⚠️ **The stepper −/+ never worked at all.** `calculator.js` walks `.rate-stepper` then looks for `.rate-plus` / `.rate-minus` **inside** it; the BEM rewrite renamed both to `calculator__step`, so both lookups returned null, both `if (plus)` / `if (minus)` guards skipped, and no listener was ever attached. Nothing threw. **This is the same silent failure the id contract protects against — except these are CLASSES, so the 30-id check could not see it.** Both restored, and the click path is now covered by a test that presses the buttons and reads the rate back.
- The glyph is a real **minus sign (U+2212)**, not a hyphen — at 20px a hyphen is shorter and sits higher than the `+` beside it. ⚠️ The *size* was not the problem and I checked before changing it: `$text-body-desktop` is already the frame's 20px. (First attempt invented a cause and was reverted.)
- **120 above the section** — also `design.md` §6's rhythm for a feature band. With `.trust`'s 50 below, the gap is 170 against the **184** the frame draws between the bands (trust ends 5855, calculator starts 6039), so the requested number reconciles with the measurement rather than fighting it.
- **Arrows in the thumbs.** Frame 118 inside the 48px thumb is 42×24 holding vuesax `arrow-left` + `arrow-right`, 24×24, overlapping by 6. ⚠️ Delivered as a **background-image data URI** because the thumb is a *pseudo-element* — it cannot hold child nodes and `content` does not render on it. The same declaration also replaces the `background-image: none` that blanked Divi's chevron, so Divi's glyph still cannot return. ⚠️ **`#` must be `%23` in a data URI**, or the stroke colour parses as a fragment id and the arrows come out invisible — indistinguishable from a failed load.
- **Button 57 tall, and the two form inputs with it** (Frame 293 and Frame 105 are both 493×57, 8 apart). `$btn-height-wide` already existed for exactly this; the form was on the 48 standard. ⚠️ It needed `!important`: the cascade showed **our 57 winning while the computed value came back 38** — GF resolves control height through `--gf-ctrl-*` variables, so the losing rule is not a plain declaration you can out-specify.
- **% and DSTI centred on the arc**, not parked at its right end. Frame 283 is a 43×43 readout at x=401 in a gauge spanning 328…515 (centre 421.5 vs 422.5), and both its texts are 43 wide — centre-aligned inside it.

📌 **A correction to my own previous entry: the headline is a CARD, not padding.** Frame 131
is 555×151 with `fills: #fef8ef` and `cornerRadius: 24` — a `$color-surface` card on the
sunken panel, as Petr's reference shows. I had read its 35 of inset as air and reproduced
it with margins. **That still put every row within ~10px of the frame, which is exactly why
it survived a measurement pass: matching positions is not the same as matching structure.**
Worth remembering when a comparison is done by coordinates alone.

⚠️ **`getComputedStyle(el, '::-webkit-slider-thumb')` bit again**, in a check this time
rather than in the code — it reported `background-image: none` for a thumb that does carry
the arrows. The note in `_calculator.scss` already says it returns the HOST element's
styles; verify the pseudo-element through the compiled CSS and the rendered pixels.

Verified 47/47 + 48/48, 57px button and no overflow at 375 / 768 / 1024 / 1440 / 1600 /
2560.

### The head CTA, the third rate mode, and the `%`

📌 **The head button is «Розрахувати», not «Довідка» — which is why the slot had always
looked empty.** Frame 9120 is a 150×48 accent button, radius 24, and its label reads
«Розрахувати». We had rendered the *help* button there, gated on `ak_calc_help_content`
(empty in every language). So the missing button was never a styling gap: a different
button was standing in its place. Uses `.btn--primary`, the kit variant, not a restyle.

⚠️ **Its behaviour is not in the design, so it is stated rather than guessed.** It cannot
mean "compute" — the calculator recomputes on every keystroke, so a submit-style action
would be a control that looks like it does something and does not. It focuses the first
field and scrolls the card into view (a real job on a phone, where head and fields are a
screen apart). Pointing it at `/calc/` is a one-line change if that was the intent.

- ⚠️ **The third mode reverses the 2026-07-31 decision, on Petr's instruction.** That decision was right at the time: `updateCalculation()` branched on exactly `fixed` / `variable` and initialises `interestRate` to 0, so a third radio would have reported a payment far below reality. **Production still has only two.** It is safe now because the JS gained a real `manual` branch in the same commit — registered in **both** the `steppers` and `inputs` maps (one alone gives a radio that reveals its control and then reports 0%), with block 2 looking its input up separately since it is its own IIFE. Verified: TAN 2.700%, payment 917,49 €, −/+ reach TAN.
- ⏳ **Вручну computes identically to Фіксована** — both take the TAN the visitor types, the only reading the design offers. That makes them functionally redundant. Flagged for Petr: the alternative (Фіксована becomes a preset, only Вручну editable) is a content decision, not a code one.
- ⚠️ **The `%` gap was LEGACY CSS, and not Divi's this time.** The page ships `.rate-stepper input { width: auto !important; text-align: center; max-width: 120px }` and `.percent-symbol { position: absolute; right: 8px; top: 50%; transform: translateY(-50%) }` — both (0,1,1) against our single class. The value sat in a 120px box while the `%` was pinned to the wrapper's right edge, **29px apart**. The frame has *no separate `%` element*: Frame 4166's characters are literally "2.70%". `.rate-stepper` is kept because calculator.js finds the steppers by it, so its styling rides along — **exactly like `.contact-form` did**. ⚠️ `transform: none` is required, not tidying: dropping the absolute alone left the transform on a now-static inline element and **lifted the `%` into a superscript**.

#### Two knock-ons the third mode exposed, both measured

- **Mode labels are Body SMALL (16px), not Body.** The frame's labels are 19px-tall boxes at 52/77/55 and its modes total 328; at 20px ours came to 383 and the stepper wrapped. Radio 24, not 20.
- ⚠️ **At desktop the modes↔stepper gap is now whatever is left over, not a fixed value.** Our labels still render ~7px wider *each* at the same 16px (tracking metrics), so the modes come to 350 and **any** fixed gap overflowed the 616 column — three were tried. `nowrap` + `margin-left: auto` yields ~24. The gap is the one measurement in that row that is pure whitespace between two groups, so it is the right thing to give up rather than shrinking type away from the frame.
- ⚠️ **`flex: 0 0 auto` on the modes group is part of that fix, and finding out why is the lesson.** With only the ROW set to `nowrap` the group shrank instead, and its own wrap pushed «Вручну» onto a second line — **while the row still measured as "not wrapped"**. The check had passed for the wrong reason; it now compares the group's height to a single row. Same shape as the headline-card mistake: a green check is only as good as what it actually asserts.

Three suite assertions hardcoded two modes and were updated — the third mode is
intentional, so the tests were the stale part. Verified 47/47 + 48/48; modes on one line
with the stepper inside the column at 768 / 1024 / 1440 / 1600 / 2560, wrapping only at
375, no overflow anywhere.

### Finished the stepper (Figma `1130:4165`) — by deleting the class, not fighting it

📌 **I had reported these buttons as done without measuring them.** The earlier commit
claimed "31×31 radius 12 sunken buttons"; it had verified the stepper's *width* and the
value centring and stopped. The buttons were rendering **24×24 circles with a 14px glyph**
in a control **24px tall against the frame's 43**. The declarations were correct and
completely inert. *Measuring the thing you changed is not the same as measuring the thing
you claimed* — third instance of that shape this week.

Root cause was the **legacy sheet**, not Divi. `.rate-stepper` is (0,1,1) against a lone
`.calculator__step` (0,1,0), and it carries two stacked layers where the second undoes the
first — `width:30px; border-radius:12px` then `width:24px!important; max-width:24px!important;
border-radius:50%; font-size:14px!important` — plus `.rate-stepper { height:24px!important }`
and, in media queries, `font-size:12px!important` / `8px!important` on the value.

- ⚠️ **`max-width:24px!important` is why the first attempt half-worked**: height took our 31 while width stayed 24 *from the same pair of rules*. An asymmetry like that is a second constraint hiding, not a cascade mystery — worth remembering as a diagnostic.
- **So the class went.** Same move as `.contact-form`, same reason: it was in the markup only so calculator.js could find the steppers, and it dragged a stylesheet with it. `calculator.js` now walks `.calculator__stepper`. The diff **removes more `!important` than it adds**.

⚠️ **Two legacy rules survive the change and still need overriding**, because they are BARE
class rules never scoped to `.rate-stepper` — dropping the wrapper class does not free
them: `.percent-symbol { position:absolute; transform:translateY(-50%) }` (and `transform:
none` is required, not tidying — dropping the absolute alone lifted the `%` into a
superscript), and a **flagged** `@media (max-width:900px) .percent-symbol { font-size:14px
!important }` that beats (0,2,0) on its own. `.input-wrapper` also gets a 6px gap below 900.
**Lesson: check whether a legacy rule is scoped to the class you are removing before
assuming removal frees you.**

⚠️ **And a new opponent appears once the legacy goes.** The value is `type="text"`, so with
`.rate-stepper input` gone, **Divi's `input[type="text"] { background:#fff; border:1px solid
#bbb }` was next in line** — the legacy rule had been the only thing holding Divi off.
Removing one layer of override can expose another; the doubled selector stays.

Padding is **5, not 6**, and that is arithmetic: the frame's 43 *includes* its 1px inside
stroke (43 = 1+5+31+5+1), which also lands the buttons 6 from the outer edge exactly where
the frame draws them. At 6 the control measured 45.

⏳ The −/+ **glyph colour** is the one value not confirmed against the frame — the Figma
bridge dropped before `1130:4168`'s fill could be read. Kept as `$color-accent` (what
rendered before, matches Petr's reference) but declared explicitly so phase 4 can delete
the legacy sheet without silently turning them ink. **Re-check `1130:4168`.**

Verified 16/16 on a stepper-specific check at **1440 and 375** — 243×43, buttons 31×31 at
radius 12 six from each edge, value and `%` the same size and touching, pair centred, −/+
still wired — plus 47/47 + 48/48.

### 🚀 DEPLOYED — the calculator is live, and DSTI is finally right on the live site

Petr: «починi DSTI и задеплой». Two corrections came out of doing it.

📌 **The DSTI rate fix was already in the code** — I had said in chat that the branch
subtracting the index "ломается"; it did not. All three branches already used the contract
rate and local already showed 37%. The fix was never the code; it was that **the code had
never reached visitors.**

📌 **Our calculator was DARK on production.** Shipped 2026-08-03, but page 11's
`ak_inline_sections` did not list it and page 566 had **no `ak_*` meta at all**. Visitors
were seeing Divi's legacy calculator with the original `− INDEXANTE_CONST` defect.
Confirmed by grepping the live HTML: zero `calculator__*` classes, only `calculator-form` /
`rate-stepper` / `input-inline-control`. **So deploying files alone would have changed
nothing** — that is why the scope question went back to Petr, who chose to go live on both
pages.

⚠️ **AND A CORRECTION TO WHAT I TOLD PETR EARLIER: `ak_calc_help_content` is NOT empty
everywhere.** It is empty on page 11 but carries **3 971 bytes** of help copy on page 566.
So «Довідка» renders on `/calc/` — verified live. My "the button is orphaned" was wrong;
it is orphaned on the homepage only.

#### ⚠️ The deploy order in `ci-cd.md` had to be REVERSED, and the reason generalises

`ci-cd.md` says *meta first, then files* — sound, but it rests on one premise: **the live
code does not know the key yet.** The calculator had been shipped dark, so production
already read `ak_inline_sections`. Seeding first would have activated the section **using
the 2026-08-03 build** — the old design, live, for the minute until the tarball landed.
Order used: **files → meta → cache.** `ci-cd.md` now carries the rule and how to tell which
case you are in.

#### What the pre-flight caught before anything was written

- **`post_content` is NOT identical between environments** (page 11 differs by 26 bytes, 566 by 5), so "same meta ⇒ same render" could not be assumed. Checked what the mechanism *actually* matches on instead: `ak_replace_divi_section_by_id()` keys on the `module_id` attribute, and **`module_id="calculator"` exists on both sides** — so the locator is safe regardless of the byte diff.
- ⚠️ **`/calc/` needs `ak_strip_extra = 1`, not just `ak_sections`.** The calculator's Divi layout is TWO sections (calculator + the `infobox` popup). With only `ak_sections`, `ak_native_section_count()` returns 1 and the popup section would have been left rendering as a leftover.
- ⚠️ **Six `ak_about_*` bodies and `ak_about_portrait` differ between environments and were deliberately NOT touched.** Production's bodies are `<p>`-wrapped (+7 bytes — verified by word-diff, not assumed) and its portrait is a different attachment ID. `ci-cd.md` warns about exactly this: production's post **2567 is an attachment**, while locally 2567 is a *revision*. Seeding "everything from local" would have pushed `kalynyuk.loc` content shapes and a wrong image onto production.
- **Local posts 2567 / 2571 carrying section meta are REVISIONS**, not pages — nothing to replicate.
- **Translations need no seeding**: pt 2483 / en 2484 / ru 2485 carry no section meta, so `ak_section_field()`'s default-language fallback gives them page 11's list. Verified live on `/pt/`.
- The seed was **generated programmatically from local values** rather than hand-written — 3 971 bytes of Cyrillic HTML is not something to retype, and `ci-cd.md` forbids inlining PHP through the shell.

#### Verified live

| | home | /calc/ | /pt/ |
|---|---|---|---|
| payment | 925,89 € | 925,89 € | 925,89 € |
| **DSTI** | **37%** | **37%** | **37%** |
| card #fef8ef r24 | ✓ | ✓ | ✓ |
| three modes | ✓ | ✓ | ✓ |
| «Розрахувати» | ✓ | ✓ | ✓ |
| stepper 43px | ✓ | ✓ | ✓ |

925,89 ÷ 2 500 = 37% — **the page now agrees with itself**, and matches the method derived
from Anna's own simulator. No duplicate calculator (`id="mensalidade"` appears exactly once
per page; `calculator-form` survives only inside legacy CSS selector text). Hero still
renders on the homepage and `/pt/` — absent on `/calc/` by design, since that page's
`ak_sections` is the calculator. Only console error is the pre-existing Review Wall
`remodal.min.js` `b.map is not a function`, attributed by stack to the plugin.

Final `ak_*` diff: **158 keys both sides**, the only differences being the 7 deliberately
skipped. `ak_native_sections` cleared from production. Backups kept on the server:
`~/backup-theme-20260806-1925.tgz`, `~/backup-akmeta-20260806-1925.tsv`. Rollback is one
meta key — `post_content` was never touched.

### 🚀 The deploy was INCOMPLETE — `/pt/` shipped the calculator in Ukrainian

Petr, minutes after the go-live: «на pt калькулятор не переведен а на лок переведен». He
was right, and the miss was mine.

📌 **I shipped files + post meta and called it done. The translations live in neither.**
Polylang string translations sit in **`wp_termmeta._pll_strings_translations`** and the pt
copy sits on the *translated* page (2483) — so the deploy carried none of it. `/pt/` then
rendered the calculator in Ukrainian through `ak_section_field()`'s default-language
fallback, which is **designed** to do that so a new language is never blank. It looks like
a successful deploy.

⚠️ **Two mistakes made it possible, and both were mine to avoid:**
1. I wrote "Translations need no seeding" in the go-live entry. True of the section **list** — pt/en/ru inherit page 11's — but I let it stand for the **copy**, which is a different thing entirely.
2. I "verified `/pt/`" by asserting the calculator *renders*. Language had been an entire workstream in this same session, and I still did not assert a single Portuguese string. **Checking that a page renders a section is not checking that the section is translated.**

#### The trap inside the fix

⚠️ **Polylang matches on the SOURCE string, and one source embeds an absolute URL.** The
Gravity Forms consent choice is `Я погоджуюсь з <a href="{site}/privacy-policy/">…`, so its
source is *different on production*. Seeding local's pair verbatim would have created an
entry keyed to a string that never occurs there — a translation nothing ever looks up, and
a consent notice left in Ukrainian on a licensed intermediary's form. Normalised
`kalynyuk.loc` → `www.kalynyuk.com` in **both** source and translation. Every other form-3
source was compared field by field first and is byte-identical.

⚠️ **And I backed up the wrong table.** `PLL_MO` is documented as using a `polylang_mo`
post type; on this install that query returns **zero rows**, and I had already exported
`wp_posts` — a **157 MB** dump of the wrong data onto a shared host carrying three sites.
Replaced with a 17 KB `wp_termmeta` export, which is where the strings actually are.

#### Done and verified

Seed was **add-only** — it skipped every source production already translated (kept 46 pt /
47 en / 40 ru), so nothing edited in the live admin was clobbered. Added 37 pt / 20 en /
20 ru, plus `ak_calc_heading` + `ak_calc_intro` on 2483.

Production `/pt/` now matches local **exactly**: all six field labels and their tooltip
bodies, the three modes (Variável / Fixa / Manual), «Calcular», the form title, the submit
button (Enviar cálculo) — and a **Cyrillic sweep of every leaf node, `aria-label` and
`placeholder` inside the section returns zero**. DSTI 37%. The consent notice reads
"Concordo com a Política de Privacidade…" with its link pointing at
`https://www.kalynyuk.com/privacy-policy/` — the normalisation working end to end.

`/en/` and `/ru/` render no calculator at all: pages 2484 / 2485 are still empty drafts.
That is expected, not a regression.

`ci-cd.md` now documents the **third deploy leg** (DB-resident translations), where the
strings actually live, the URL-in-source trap, and the add-only rule. Backup:
`~/backup-termmeta-20260806-1942.sql`.

### Still open

- ⏳ **IMT is still wrong, and was NOT guessed.** Our bracket table gives 22 506 € where Anna's gives 22 237 (delta 270) and 14 506 where hers gives 1 557 (**delta 12 950**). The two cases differ only by the client's age — 31 vs 36 — which is almost certainly the **IMT Jovem** first-time-buyer relief, and explains why her tool has a date-of-birth field at all. Implementing it needs the actual brackets and eligibility rules. **Asked, not invented.**
- ⏳ **Whether the DSTI affordability test should carry a Banco de Portugal rate shock.** Its macroprudential recommendation expects an interest-rate shock on top of the contract rate (+1.5pp → 42%, +3.0pp → 48% on the default inputs); Anna's tool applies none, and neither do we. A specific increment is a **regulatory decision, not a code cleanup**. Whatever she answers changes one line.
- ⏳ **[DECISION] Deploy waits for Anna's IMT answer** (Petr). Production therefore keeps the broken `/pt/` calculator — no styles and Ukrainian labels — until the brackets arrive. The alternative offered was deploying now (IMT would have been no more wrong than it already is on production, which runs the same bracket table) and it was declined in favour of shipping once, correct.
- **The tip bodies are pt-only.** `/en/` and `/ru/` inherit Ukrainian; those front pages are still drafts and 404, and all 17 strings are registered, so both languages appear in Polylang → Strings for whoever fills them in.
- **`ak_calc_help_content` is empty on page 11 too**, so the «Довідка» button still does not render in any language. ⚠️ **It is NOT the head button** — that is «Розрахувати», now built. Довідка has no slot in the frame at all, so where it belongs is an open question, not just a missing string: the panel it opens exists in the template, and the button is currently orphaned. Needs Petr to say whether Довідка survives as a control.
- ⏳ **Вручну vs Фіксована is a live content question — and it is now LIVE on production**.** Both compute from a typed TAN, so today they are the same control twice. Either Фіксована becomes a preset rate (and only Вручну stays editable), or one of them goes.
- **The calculator has no tablet or phone frame.** `design.md` §13 gap #10 is closed for desktop only; the 375/768 behaviour is our own (columns stack, row gap drops to 32) and verified not to overflow, but it is not *designed*. Rescan before doing responsive work there.
- ⏳ **`IMT Ilhas` — a THIRD tax metric, visible in Petr's reference and absent from our build.** The frame's tax row (`1130:4061`) carries 4 children against our 2, the dead `.imt-ilhas` tooltip existed in the legacy CSS, and `calculator.js`'s own header comment claims it computes IMT "for mainland and islands" — but there is **no islands function in the file**, only `calculateIMTContinente()`. So the comment is wrong and the metric was never ported. Adding it means an islands bracket table: **regulated figures, the same class of thing as IMT Jovem, so it goes to Anna rather than being invented.**
- ⏳ **Re-check Figma `1130:4168` for the −/+ glyph colour** when the bridge is up — the one calculator value never read off the frame.
- ⚠️ **The stale-Divi-CSS sweep is now worth scheduling rather than noting.** Removing `contact-form` proved the point on live markup: orphan rules from deleted modules are still reaching our elements, and they win on load order. Something should enumerate what else `et-builder-module-design-deferred-11-cached-inline-styles` still matches before phase 4.
- **The rate row ships TWO modes, not the design's three.** Figma shows «Вручну»; `updateCalculation()` branches on exactly `'fixed'` and `'variable'` and initialises `interestRate` to 0, so a third option would compute the payment at 0% and understate it. Petr's call (2026-07-31): ship the two that work.
- ⚠️ **Not deployable by file sync alone.** Production needs the same DB seeding: 11 chrome strings, five Gravity Form 3 strings, `ak_calc_heading` / `ak_calc_intro` on 2483, the 16 tip bodies in pt, and `ak_calc_tip_label` in pt/en/ru. And the deploy still has to strip the calculator's Divi original (`ak_inline_sections` carries `calculator = calculator` locally; production deliberately does not yet), which is what makes this the deploy that finally takes the homepage to zero Divi sections.
