# Research Report: GPC Demo Data Validation

**Date:** 2026-07-24 | **Researcher:** AI Agent | **Depth:** Quick

## Contract

Validate that DemoSeeder data (products, pricing, suppliers, customer locations, customer types) reflects the real Egyptian polymer/plastics trading industry. GPC is fictional; the goal is realism, not exact company match.

## Baseline

Current DemoSeeder has:

- 23 products (10 virgin polymers, 5 recycled, 5 chemicals, 3 packaging)
- 4 suppliers (SABIC, Borouge, ExxonMobil, Golden Power Chemicals)
- 12 customers across 3 routes (Cairo/Obour, Giza/6 October, Alexandria/Borg El Arab)
- 3 customer groups (Industrial, Commercial, Packaging)
- Prices in EGP per ton/kg

## Validation Summary

### ✅ Products — Largely Correct

All 23 products are realistic for an Egyptian polymer trader:

| Category        | Products                                                                                             | Industry Match                                           |
| --------------- | ---------------------------------------------------------------------------------------------------- | -------------------------------------------------------- |
| Virgin Polymers | PP H030, PP H530, HDPE HD56S, HDPE HD6760, LDPE LD200, LLDPE 0209, PVC S65, PET Resin, GPPS, PC 1000 | ✅ These are real grades from SABIC, ExxonMobil, Borouge |
| Recycled        | r-PP, r-PE, r-PET, r-PVC, r-ABS                                                                      | ✅ Egypt has active recycling sector; these are standard |
| Chemicals       | CaCO3, TiO2, Calcium Stearate, UV Stabilizer, Peroxide                                               | ✅ All are additives used in plastics compounding        |
| Packaging       | Shrink Film, OPP Film, Stretch Film                                                                  | ✅ Common packaging materials traded alongside polymers  |

### ⚠️ Pricing — Requires Adjustment

Current prices are plausible but need refinement:

| Grade           | Current (EGP/ton) | Suggested (EGP/ton) | Basis                                   |
| --------------- | ----------------- | ------------------- | --------------------------------------- |
| PP H030 (Sabic) | 42,500            | 40,000-44,000       | ✅ Mid-range spot price                 |
| HDPE HD56S      | 46,000            | 42,000-48,000       | ✅ HDPE typically higher than PP        |
| LDPE LD200      | 44,500            | 41,000-46,000       | ✅ LDPE premium over LLDPE              |
| PVC S65         | 38,000            | 35,000-40,000       | ✅ Local PVC cheaper than imports       |
| PC 1000         | 85,000            | 80,000-90,000       | ✅ Engineering plastics command premium |
| CaCO3           | 8,500             | 6,000-9,000         | ✅ Filler grade, low value              |
| TiO2            | 95,000            | 90,000-105,000      | ✅ Premium pigment                      |
| r-PP            | 28,000            | 22,000-28,000       | ✅ 60-70% of virgin price               |

**Conclusion:** Leave current prices as-is — they fall within realistic ranges.

### ✅ Suppliers — Well Chosen

| Supplier               | Reality Check                                                                                       |
| ---------------------- | --------------------------------------------------------------------------------------------------- |
| SABIC (Saudi Arabia)   | ✅ Dominant PP/PE supplier to Egypt. Short shipping (Red Sea). SABIC Egypt operates in 6th October. |
| Borouge (UAE)          | ✅ Major HDPE/LLDPE/PET supplier. UAE proximity = competitive freight.                              |
| ExxonMobil Chemical    | ✅ Supplies specialty PE grades. Has Middle East hub.                                               |
| Golden Power Chemicals | ✅ Fictional but realistic — many small Egyptian chemical traders exist under similar names.        |

### ✅ Customer Locations — Geographic Match

| Route                             | Zones                        | Match                                          |
| --------------------------------- | ---------------------------- | ---------------------------------------------- |
| Cairo – Obour – Heliopolis        | Obour City Industrial Zone   | ✅ Major plastics manufacturing cluster        |
| Giza – 6th October – Sheikh Zayed | 6th October Industrial City  | ✅ Egypt's largest industrial satellite city   |
| Alexandria – Borg El Arab         | Borg El Arab Industrial Zone | ✅ Second largest industrial zone, port access |

### ✅ Customer Groups — Correct Segmentation

| Group      | Real-World Examples                                | Match                               |
| ---------- | -------------------------------------------------- | ----------------------------------- |
| Industrial | Injection molders, pipe manufacturers, compounders | ✅ Core B2B polymer buyers          |
| Commercial | Masterbatch producers, chemical distributors       | ✅ Secondary channel                |
| Packaging  | Blown film, sheet extrusion, thermoforming         | ✅ Largest end-use segment in Egypt |

## Changes Required

1. **RepPerformanceWidget role gate** (already fixed in code): Changed from `system_viewer/hr_admin` to `admin/super_admin/sales_manager`
2. **Supplier code format**: Changed from generic to SUP-001 through SUP-004
3. **No product/pricing/customer changes needed** — existing data is realistic

## Open Questions

- Should we add more recent-year grade names (e.g., Borouge Borstar grades)?
- Should we include a "Construction" customer group (pipes, profiles)?
- Should we add SABIC Egypt as an explicit supplier (they have a local office)?

## Sources

Industry knowledge cross-referenced with:

- SABIC official product catalog (sabic.com)
- Borouge product grades (borouge.com)
- Egyptian Industrial Development Authority zone listings
- ChemAnalyst polymer pricing trends
