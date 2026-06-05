# Config Format — Per-Pilot JSON (Event Context \+ Promoted SKUs)

One file per pilot at `/config/{pilot}.json`. Loaded and validated by BE-2, injected into the prompt by BE-3. No admin panel — content is edited directly in the file.

---

## Fields

| Field | Type | Required | Notes |
| :---- | :---- | :---- | :---- |
| `pilot_id` | string | yes | Must match the `?pilot=` value. Lowercase, no spaces. |
| `store_name` | string | yes | Shown in UI and prompt, e.g. "Intermarché Lyon Part-Dieu". |
| `locale` | string | yes | `fr-FR` for the France pilot. |
| `currency` | string | yes | `EUR`. |
| `valid_from` / `valid_to` | string (ISO 8601 date) | yes | The two-week window the event context applies to. |
| `branding` | object | no | `{ primary_color, logo_url }` for light theming. |
| `assistant_name` | string | no | Display name of the companion (default provided if absent). |
| `default_servings` | integer | no | Default recipe servings (default 4). |
| `model` | string | no | Override the default OpenAI model. |
| `temperature` | number | no | Override default (0.7). |
| `ui_labels` | object | no | French UI strings: category chip labels, button text. |
| `events` | array | yes (may be empty) | Local event context for the window. See below. |
| `promoted_skus` | array | yes (may be empty) | Featured products. **Max 30\.** See below. |

### `events[]`

| Field | Type | Required | Notes |
| :---- | :---- | :---- | :---- |
| `name` | string | yes | e.g. "Coupe du Monde 2026 — phase de groupes". |
| `type` | string | yes | One of `holiday`, `sports`, `seasonal`, `local`. |
| `date` or `date_range` | string / object | yes | ISO date, or `{from, to}`. |
| `angle` | string | yes | The culinary hook the assistant may use, e.g. "apéritif / snacks à partager devant le match". |

### `promoted_skus[]`

| Field | Type | Required | Notes |
| :---- | :---- | :---- | :---- |
| `sku_id` | string | yes | Unique within the file. |
| `name` | string | yes | Exact product name the assistant must use verbatim. |
| `category` | string | yes | e.g. "fromage", "boisson", "viande". |
| `price` | number | yes | Numeric value only; formatting handled in output. |
| `unit` | string | yes | e.g. "la pièce", "le kg", "le lot de 6". |
| `on_promo` | boolean | yes | Only if `true` may the assistant call it "en promotion". |
| `pairing_hint` | string | no | Optional cue, e.g. "idéal pour un apéritif ou une salade". |

---

## Validation rules

- All required fields present; reject/replace with safe default otherwise (BE-2).  
- Dates ISO 8601; `valid_from` ≤ `valid_to`.  
- `sku_id` unique; `promoted_skus` length ≤ 30\.  
- `price` numeric and ≥ 0\.  
- Empty `events` or `promoted_skus` arrays are allowed (assistant simply won't reference them).  
- The config must **never** contain the API key or any secret.

---

## Example — France pilot (illustrative; confirm real dates/prices/SKUs with the chain)

{

  "pilot\_id": "intermarche-lyon",

  "store\_name": "Intermarché Lyon Part-Dieu",

  "locale": "fr-FR",

  "currency": "EUR",

  "valid\_from": "2026-06-02",

  "valid\_to": "2026-06-16",

  "assistant\_name": "Le Compagnon de courses",

  "default\_servings": 4,

  "branding": { "primary\_color": "\#E2001A", "logo\_url": "" },

  "ui\_labels": {

    "chips": \["Dessert", "Plat principal", "Soupe", "Apéritif", "Salade"\],

    "suggest\_button": "Proposez-moi une recette",

    "basket\_title": "À ajouter à votre panier"

  },

  "events": \[

    {

      "name": "Coupe du Monde 2026 — France-Sénégal (16 juin)",

      "type": "sports",

      "date\_range": { "from": "2026-06-11", "to": "2026-06-16" },

      "angle": "apéritif et snacks à partager pour le match France-Sénégal du 16 juin au soir"

    },

    {

      "name": "Début de la saison des barbecues",

      "type": "seasonal",

      "date\_range": { "from": "2026-06-02", "to": "2026-06-16" },

      "angle": "grillades, salades fraîches et marinades pour les beaux jours"

    }

  \],

  "promoted\_skus": \[

    { "sku\_id": "FR-0001", "name": "Saucisses de Toulouse (lot de 6)", "category": "viande", "price": 4.95, "unit": "le lot", "on\_promo": true, "pairing\_hint": "parfait pour un barbecue" },

    { "sku\_id": "FR-0002", "name": "Chips nature Pâturages 150g", "category": "apéritif", "price": 1.20, "unit": "le paquet", "on\_promo": true, "pairing\_hint": "idéal pour un apéritif devant le match" },

    { "sku\_id": "FR-0003", "name": "Tomates grappe", "category": "légume", "price": 2.49, "unit": "le kg", "on\_promo": false, "pairing\_hint": "salades et grillades" },

    { "sku\_id": "FR-0004", "name": "Fromage de chèvre frais", "category": "fromage", "price": 1.85, "unit": "la pièce", "on\_promo": true, "pairing\_hint": "salades d'été et tartines" },

    { "sku\_id": "FR-0005", "name": "Baguette tradition", "category": "boulangerie", "price": 1.10, "unit": "la pièce", "on\_promo": false, "pairing\_hint": "accompagnement universel" }

  \]

}  
