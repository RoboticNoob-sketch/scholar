# Scholarly — Design System

Design system for the **Scholarship Distribution Monitoring System** ("Scholarly") — a platform for tracking scholarship awards, applicant/recipient status, fund disbursement, and compliance across a cohort.

## Sources

No codebase, Figma file, or slide deck was attached to this project. This system was built from a single written brand brief (Spotify-inspired visual language, supplied in chat) applied to the Scholarship Distribution Monitoring System domain. There is **no logo or brand mark on file** — a plain wordmark ("Scholarly.") stands in wherever a mark would go; see Iconography below. If a codebase, Figma link, or asset pack becomes available, re-run this process against it — component inventory and screens here are original, from-scratch builds sized to the brief, not extracted from a real product.

## Index

- `styles.css` — root stylesheet, `@import`s only
- `tokens/` — `colors.css`, `typography.css`, `spacing.css`, `effects.css` (shadow/radius/motion), `fonts.css`, `base.css`
- `guidelines/` — foundation specimen cards (Colors, Type, Spacing, Brand groups in the Design System tab)
- `components/forms/` — Button, IconButton, Input, Select, Checkbox, Radio, Switch
- `components/display/` — Card, Badge, Tag
- `components/navigation/` — Tabs
- `components/feedback/` — Dialog, Toast, Tooltip
- `ui_kits/scholarship-monitor/` — click-through admin dashboard recreation (Overview, Recipients, Disbursements, Application Review)
- `thumbnail.html` — homepage tile
- `SKILL.md` — portable skill definition for Claude Code

### Components
Button, IconButton, Input, Select, Checkbox, Radio, Switch, Card, Badge, Tag, Tabs, Dialog, Toast, Tooltip.

**Intentional additions**: none of these are "invented" beyond the standard set requested for a from-scratch build (no source defined a different inventory). All 14 map directly to interaction needs named or implied in the brief (pill buttons, circular play-style controls, status pills, inset-border inputs, dropdown filters, elevated dialogs/toasts).

## Content Fundamentals

**Voice**: operational and precise, not marketing-toned. This is a compliance/monitoring tool for staff, not a consumer app — copy states facts and status plainly ("3 disbursements pending review", "Verification failed") rather than selling or exclaiming.

**Person**: second person for instructions and empty states ("Search recipients", "Review flagged applications"), third person / plain noun phrases for data and status labels ("Pending Review", "128 recipients").

**Casing**: sentence case in body copy and table content; **UPPERCASE with wide letter-spacing reserved for button labels only** (inherited directly from the Spotify button system) — this is a deliberate two-tier casing system, not inconsistency. Status badges use capitalize-case ("Pending Review", "Disbursed").

**Numbers & money**: always concrete, never vague — "$412,000 awarded" not "significant funding"; counts before adjectives ("128 recipients", not "many recipients").

**Emoji**: none. This is a monitoring/compliance tool; emoji would undercut the "serious instrument" feel the dark, dense UI is going for.

**Tone examples**:
- Button: `REVIEW APPLICATION` (uppercase, functional)
- Empty state: "No disbursements scheduled this cycle."
- Error/negative: "3 recipients failed identity verification."
- Confirmation dialog body: "This will release $2,400 to 12 recipients. This action cannot be undone."

## Visual Foundations

**Colors**: near-black immersive dark theme (`#121212` → `#1f1f1f` → `#252525`/`#272727`) — depth is built from shade steps, not borders. Spotify Green (`#1ed760`) is the **only** brand color and is used functionally (primary CTA, active/selected state, confirmed/disbursed status) — never decoratively, never as a background wash. Semantic colors are desaturated-bright: negative red `#f3727f`, warning orange `#ffa42b`, announcement blue `#539df5`, each also available as a low-opacity (16%) tint for badge backgrounds.

**Type**: Work Sans stands in for the proprietary SpotifyMixUI/CircularSp family (see Typography Substitution below). Hierarchy is a **bold/regular binary** — 700 or 400 weight, with 600 used sparingly for feature headings — rather than many size steps. Scale runs a compact 10px–24px; this is a dense operational app, not an editorial surface. Button labels are the one place casing itself carries hierarchy: uppercase + 1.6px letter-spacing.

**Spacing**: 8px base unit, scale from 1px micro-adjustments up to 20px section gaps. Layouts are **dense by design** — packed table rows, tight card grids — the dark background (not whitespace) provides visual rest between elements.

**Backgrounds**: flat color fields only. No photography, no illustration, no gradients, no textures/patterns. Any color in the interface should come from data (status badges, avatars) — the chrome itself stays achromatic charcoal.

**Animation**: subtle and fast. Hover states use a quick (100ms) scale-up (~1.04×) on pill buttons and a background lightening on cards/rows; toggles animate their thumb over 150ms. Standard easing `cubic-bezier(0.3,0,0,1)`. No bounce, no springs, no slow reveals — this is a functional tool, motion should feel instant.

**Hover states**: buttons scale up slightly; cards and table rows lighten one shade (`--surface-card` → `--surface-elevated-alt`); links go from blue to white with underline.

**Press/active states**: no separate press animation is specified in the brief; treat press as a slightly deeper shade of the hover background (implementation detail left to the consuming team).

**Borders**: avoided in favor of shadow and shade steps. Where a border does appear (outlined pill button, focus state) it uses `--border-light` (`#7c7c7c`) rather than a harsh pure-gray. Inputs use the signature **inset border + drop shadow combo** instead of a visible border: `rgb(18,18,18) 0px 1px 0px, rgb(124,124,124) 0px 0px 0px 1px inset`.

**Shadows**: heavy by dark-theme necessity — light/subtle shadows are invisible on near-black. Two tiers: `medium` (`rgba(0,0,0,0.3) 0px 8px 8px`) for cards/dropdowns, `heavy` (`rgba(0,0,0,0.5) 0px 8px 24px`) for dialogs/menus.

**Corner radii**: geometry is the identity. Minimal (2px, checkboxes/badges only) → subtle (4px) → standard (6px, album-art-style containers) → comfortable (8px, cards/dialogs) → medium (16px) → large (100px) → pill (500px) → full pill (9999px, nav/search) → circle (50%, play controls/avatars). Square corners on an interactive control break the identity — default to pill or circle for anything clickable.

**Cards**: no visible border, `--surface-card`/`--surface-card-alt` background, 8px radius, medium shadow only when elevated (dropdown/hover state) — resting cards are borderless and shadow-less, relying on the shade step against the page background to read as a surface.

**Transparency/blur**: low-opacity tints (16%) are used only for semantic badge backgrounds, layered under the page's flat charcoal, not over imagery. No backdrop-blur is used anywhere in the brief.

**Layout rules**: fixed sidebar + fluid content area is the base pattern (see UI kit); a persistent bottom bar pattern (Spotify's now-playing bar) is repurposed in the UI kit as a persistent action/status bar.

### Typography substitution — please confirm

`SpotifyMixUI`/`SpotifyMixUITitle` (CircularSp/Circular by Lineto) are proprietary and no font files were provided. **Work Sans** (Google Fonts, weights 400–800) is substituted as the nearest open humanist-geometric sans with a similar compact, functional character. **If you have licensed Circular/CircularSp font files, please upload them** and this system will swap `tokens/fonts.css` to real `@font-face` declarations.

## Iconography

No icon set, icon font, or SVG sprite was provided. The system currently uses plain glyph placeholders (▶, ♡, ⌕, ✕, ▲/▼) inline in component demos — these are **stand-ins, not a committed icon system**. Recommended path: adopt **Lucide** (CDN: `https://unpkg.com/lucide@latest`) as the icon set — its 1.5–2px stroke weight and rounded joins match the pill/circle geometry of this system well. No emoji are used as icons anywhere in this system (see Content Fundamentals). Flag: please confirm Lucide (or supply the real icon asset pack) so components can reference real icons instead of glyph placeholders.

## Caveats

- No logo, icon set, screenshots, codebase, or Figma file was attached — everything here is built from the written brand brief only, applied to an assumed "scholarship monitoring" product shape.
- Typography is a Google Fonts substitution (Work Sans) for the proprietary Circular/SpotifyMixUI family.
- Iconography is placeholder glyphs pending a real icon source.
- The UI kit (`ui_kits/scholarship-monitor/`) is an original interpretation of what a scholarship distribution monitoring dashboard looks like in this visual language — it is not a recreation of any existing product.
