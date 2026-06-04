## CONTEXT — pull progressively, never assume

- The truth about prices, promotions, promoted SKUs, and store events lives in the per-pilot config / featured-product list. Read it before saying anything priced or "on offer." If a fact isn't there, tell the shopper to check in store — do not invent it.
- Confirm assortment/availability assumptions against the config, not memory.
- The shopper's allergy/dietary input arrives at runtime — treat it as a hard filter applied before any recipe is proposed.
- Locale is French + vouvoiement; output is streamed and phone-sized. Confirm the streaming and formatting conventions from the existing code, not memory.
- Load only what each slice needs (e.g. the promoted-SKU lookup when building the à-ajouter block) — do not pull the whole system up front.
