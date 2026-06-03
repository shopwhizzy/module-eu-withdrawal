# i18n CSVs — translation provenance

Non-en_US CSV files are AI-drafted from the en_US source and have not been
reviewed by counsel.

**Merchant responsibility:** review legal-language strings (Art. 16 / Art. 14
/ Art. 11a CRD references) for your store's target jurisdictions with local
counsel before production deployment.

The strings inside the receipt / Annex I durable-medium content are
exception cases — those are sourced verbatim from EUR-Lex CELEX 32011L0083
and should be left as-is.

## Locales shipped (22 total)

- en_US (master, English source)
- bg_BG, cs_CZ, da_DK, de_DE, el_GR, es_ES, et_EE, fi_FI, fr_FR, hr_HR,
  hu_HU, it_IT, lt_LT, lv_LV, nl_NL, pl_PL, pt_PT, ro_RO, sk_SK, sl_SI, sv_SE

## Country variants (de_AT, de_BE, fr_BE, fr_LU, nl_BE, sv_FI) NOT shipped

These are handled at runtime by `MageMe\EUWithdrawal\Plugin\Translate\
MergeParentLanguageStrings`, which transparently fills variant locale
dictionaries from their parent-language CSV via the LocaleFallbackResolver
chain (e.g. de_AT → de_DE → en_US).

## Coverage notes

Each CSV carries the full set of master rows. Customer-facing UI strings are
translated; long admin help-text (system.xml field descriptions, internal
error messages, developer-facing text) falls through to the English source.
Magento's `__()` loader silently passes through identical en_source /
translation pairs, so admin staff on non-English locales simply see admin
help text in English while the customer-facing UI remains fully localised.

## Placeholder safety

Every translation has been validated to preserve the same set of
placeholder tokens (`%1`, `%2`, `%name`, `%order_increment_id`,
`{period_days}`, etc.) as the English source — `__()` substitution is
guaranteed to work in every locale.

## Email-template localisation

Transactional emails are NOT localised with per-locale HTML files. Each
notification is a single canonical template under `view/frontend/email/`
(and `view/adminhtml/email/` for the admin and DLQ alerts), registered in
`etc/email_templates.xml`. Magento resolves the registered `file=` attribute
relative to `view/<area>/email/` only.

Localisation happens at send time: the `{{trans "..."}}` directives inside
those templates are resolved against the CSVs in this folder for the store's
configured locale — the same dictionaries that drive the UI strings. Country
variants (de_AT, fr_BE, …) inherit their parent-language email strings through
the same `MergeParentLanguageStrings` fallback chain.

To localise an email, translate the matching `{{trans}}` source rows in the
locale CSV. There is nothing else to add per locale.
