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

### Still open

- ⏳ **IMT is still wrong, and was NOT guessed.** Our bracket table gives 22 506 € where Anna's gives 22 237 (delta 270) and 14 506 where hers gives 1 557 (**delta 12 950**). The two cases differ only by the client's age — 31 vs 36 — which is almost certainly the **IMT Jovem** first-time-buyer relief, and explains why her tool has a date-of-birth field at all. Implementing it needs the actual brackets and eligibility rules. **Asked, not invented.**
- ⏳ **Whether the DSTI affordability test should carry a Banco de Portugal rate shock.** Its macroprudential recommendation expects an interest-rate shock on top of the contract rate (+1.5pp → 42%, +3.0pp → 48% on the default inputs); Anna's tool applies none, and neither do we. A specific increment is a **regulatory decision, not a code cleanup**. Whatever she answers changes one line.
- ⏳ **[DECISION] Deploy waits for Anna's IMT answer** (Petr). Production therefore keeps the broken `/pt/` calculator — no styles and Ukrainian labels — until the brackets arrive. The alternative offered was deploying now (IMT would have been no more wrong than it already is on production, which runs the same bracket table) and it was declined in favour of shipping once, correct.
- **The tip bodies are pt-only.** `/en/` and `/ru/` inherit Ukrainian; those front pages are still drafts and 404, and all 17 strings are registered, so both languages appear in Polylang → Strings for whoever fills them in.
- **`ak_calc_help_content` is empty on page 11 too**, so the «Довідка» button correctly does not render in any language. The panel exists in the template awaiting copy — it replaced a Divi Popups section whose trigger was already dead on production. Its slot in the head's right column is now built (150×48, 32 below the intro), so supplying the copy is all that is left.
- **The calculator has no tablet or phone frame.** `design.md` §13 gap #10 is closed for desktop only; the 375/768 behaviour is our own (columns stack, row gap drops to 32) and verified not to overflow, but it is not *designed*. Rescan before doing responsive work there.
- ⚠️ **The stale-Divi-CSS sweep is now worth scheduling rather than noting.** Removing `contact-form` proved the point on live markup: orphan rules from deleted modules are still reaching our elements, and they win on load order. Something should enumerate what else `et-builder-module-design-deferred-11-cached-inline-styles` still matches before phase 4.
- **The rate row ships TWO modes, not the design's three.** Figma shows «Вручну»; `updateCalculation()` branches on exactly `'fixed'` and `'variable'` and initialises `interestRate` to 0, so a third option would compute the payment at 0% and understate it. Petr's call (2026-07-31): ship the two that work.
- ⚠️ **Not deployable by file sync alone.** Production needs the same DB seeding: 11 chrome strings, five Gravity Form 3 strings, `ak_calc_heading` / `ak_calc_intro` on 2483, the 16 tip bodies in pt, and `ak_calc_tip_label` in pt/en/ru. And the deploy still has to strip the calculator's Divi original (`ak_inline_sections` carries `calculator = calculator` locally; production deliberately does not yet), which is what makes this the deploy that finally takes the homepage to zero Divi sections.
