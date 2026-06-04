## INTENT — the outcome the shopper wants (no implementation talk)

1. What is wanted:

   A shopper in the store, on their phone, in seconds, with no sign-up, gets one
   concrete meal they could cook tonight — built around items they already have
   or named — plus a short, accurate list of the few extra items to grab in THIS
   store. Not "a chatbot," not "recipes in general." This shopper, this trip,
   walks out with a meal decided and the basket to make it.

2. Constraints (the boundary around the wanted thing):

    - Recipe makeable from widely available French grocery items — nothing this store plausibly would not carry.
    - Any stated dietary limit or allergy is respected absolutely.
    - Output in French, polite register (vouvoiement), phone-sized, streamed so it feels instant.
    - No login, anonymous, France/EU — no personal data collected.
    - Any price or promotion mentioned must be real and drawn ONLY from the store's featured-product list. Never invented.
    - The assistant advises only. It never claims to buy, reserve, or add to a real cart.
    - Scope is food, cooking, recipes, and this shop — nothing else.

3. Failure scenarios (do NOT count, even if something was produced):

    - Suggests a dish needing ingredients this store does not sell.
    - Ignores or violates a stated allergy or restriction.
    - Names a price or calls something "on promotion" when untrue, or invents a product.
    - Pushes a featured product unrelated to the meal.
    - Answers in the wrong language or drifts off food and shopping.
    - Makes the shopper do more work than a normal shop — too many questions, too slow.

4. Success scenarios (counts as done):

    - Shopper names a couple of items or picks a category, and gets a realistic French recipe.
    - Gets a short "À ajouter à votre panier" list, with quantities, of ONLY the items still needed.
    - Where a featured product genuinely fits, it is offered honestly — flagged as a promotion only if it truly is one.
    - Shopper leaves with a meal decided and a slightly fuller basket, having decided everything themselves.

5. Connections (a change here changes these):

    - Retailer commercial intent: larger basket, faster featured-stock movement, without harming the experience. Tied to promoted SKUs and measured attach-rate. Change how items are suggested → this number changes.
    - Pilot/config intent: per-pilot event context + promoted-SKU list. Change the config → what the assistant can truthfully say changes.
    - Measurement intent: "measured from day one." Change the output format (the à-ajouter block, item structure) → what can be logged changes.
    - Anything touching: assortment/availability, prices/promotions, allergy/dietary data, language/locale.
