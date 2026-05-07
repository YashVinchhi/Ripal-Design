# Goods + Asset Platform Plan

This plan keeps every capability from the current goods/inventory and 3D asset concept, but reorganizes it into a cleaner product model that is easier to build, extend, and trust in live estimating work.

## 1. Product Goal

Build one connected system with two strong modules:

1. A goods intelligence module that knows what materials cost, how they are measured, what GST applies, and how much wastage to budget.
2. A 3D asset catalogue module that stores design assets, exports them into software-specific formats, and links each asset back to the real-world goods item used in estimates and billing.

The important design principle is that design intent must feed cost reality automatically, not manually.

## 2. Core Planning Rules

- Keep prices historical, not overwritten.
- Keep vendor quoting separate from the goods master.
- Keep wastage, unit conversion, GST, and labor as first-class item rules.
- Keep estimate markup and contingency as explicit controls, not hidden assumptions.
- Keep browser-native export paths first; skip unsupported desktop automation.
- Keep a feedback loop from actual project consumption back into the goods database.

## 3. Module A: Goods Intelligence

### 3.1 Goods master record

Each goods item should represent a reusable material or supply item, with these fields:

- SKU or internal code
- Display name
- Description
- Category and subcategory
- Base unit and alternate unit conversion rule
- GST rate slab
- Default wastage factor
- Status and visibility
- Optional specification/media fields such as images, datasheets, and notes

### 3.2 Price history model

Do not store only one current price on the item record. Use a dedicated `price_entries` table for append-only history.

Each price entry should capture:

- Goods item reference
- Vendor reference
- Quoted or purchased price
- Currency
- Effective date or timestamp
- Quote type such as quote, purchase, or actual closeout
- Source/reference note
- Expiry or validity date when relevant

The current price shown on screens should be derived from the latest valid entry.

### 3.3 Vendor model

Vendors should be independent entities, then linked to goods through a vendor-goods quote table.

That relationship should store:

- Vendor ID
- Goods ID
- Quoted price
- Minimum order quantity
- Lead time in days
- Last quote date
- Quote validity window
- Priority or preferred-supplier flag

This makes vendor comparison possible directly from the estimate screen.

### 3.4 Unit conversion and wastage

Each item must know how to convert between purchase unit and estimating unit.

Examples:

- Bags to kilograms
- Pieces to square feet
- Liters to square meters for coverage-based materials

Wastage factor must be applied before pricing so estimates always use:

`effective quantity = base quantity × (1 + wastage%)`

This should happen automatically in the estimate engine.

### 3.5 GST logic

GST should be item-level by default so the invoice split can be automatic.

That means the goods master or linked tax rule must define the applicable slab, such as:

- 5%
- 12%
- 18%
- 28%

The system should support automatic GST calculation per line item and roll the totals into invoices without manual adjustment.

## 4. Module B: Estimate Engine

### 4.1 Estimate header

Every estimate should have header-level controls for:

- Markup or profit margin
- Contingency percentage
- Currency
- Tax mode
- Section-level override rules when needed
- Estimate version number and revision metadata

### 4.2 Estimate lifecycle

Every estimate should move through a clear client workflow:

`Draft → Internal Review → Sent to Client → Client Approved → Revision Requested → Closed`

The system should store the current state, who moved it, when it changed, and why.

### 4.3 Estimate versioning

Estimates should not be overwritten in place.

Every meaningful edit should create a new version snapshot that preserves:

- Header values
- Section structure
- Line-item pricing
- Labor and tax calculations
- Reviewer notes and client revision notes

That way, a revision request does not destroy the previous client-approved version.

### 4.4 Estimate structure: sections and rooms

Architectural estimates should be organized by section, area, phase, or trade rather than as a flat line list.

Each estimate should support a hierarchy such as:

- Estimate
	- Section or room group such as Ground Floor Finishes, Structural Works, Electrical, or Master Bedroom
	- Subsection when needed for finer breakdowns
	- Line items inside each group

Every section should carry its own subtotal so clients can read costs by area, and the estimate can still roll up into a full project total.

### 4.5 Estimate line model

Each line item should be costed using separate components instead of a single blended rate:

- Material cost
- Wastage-adjusted material quantity
- Labor cost component
- GST component
- Optional vendor choice used for pricing

Recommended formula flow:

1. Start with base quantity.
2. Apply wastage.
3. Choose the vendor quote or latest valid price.
4. Add labor cost if the material type needs installation.
5. Apply markup and contingency.
6. Apply GST split automatically.

### 4.6 Labor planning

Labor should not be buried inside material price.

Support either of these models:

- Flat labor amount per line
- Rate per unit by material category or work type

That keeps the estimate realistic for work such as flooring, tiling, painting, joinery, and installation-heavy items.

### 4.7 Output documents

From the same estimate, generate:

- BOQ / estimate view
- Client-facing estimate summary
- Goods invoice
- Optional purchase order

The key is that the estimate is the source of truth, and the downstream documents inherit from it.

## 5. Module C: Post-Project Feedback Loop

This is a core product feature, not an optional analytics extra.

When a project closes, capture:

- Actual quantity used
- Actual price paid
- Actual vendor used
- Material variance
- Wastage variance
- Labor variance

Then compare actuals against estimates and surface:

- Over-estimated items
- Under-estimated items
- Materials whose default wastage needs revision
- Materials whose last-known price is stale

The system may then suggest updates to the goods database, but human approval should remain in control.

## 6. Module D: 3D Asset Catalogue

### 6.1 Asset record

Each asset record should store:

- Asset ID
- Name
- Asset type
- Tags and style metadata
- Source file or model reference
- Version
- Preview render
- Licensing or usage notes
- Linked goods item when a material relationship exists

### 6.2 Catalogue taxonomy and search

The catalogue needs a designed browsing model before the data model is finalized.

Assets should be searchable and filterable by:

- Material type
- Room or area
- Style tag
- Manufacturer or brand
- Software target
- License type
- Usage status

The catalogue should support faceted search and grouped browsing so the library remains usable at scale.

### 6.3 Export variants

Use the browser as the primary delivery surface for asset exports.

Pre-generate or package export variants for each supported destination so the user can download or preview the correct format directly from the catalogue:

- Revit family or appearance asset variant
- AutoCAD material or support package variant
- Lumion-friendly package variant
- D5-friendly package variant

The asset record should know which export formats are available and which software can consume them. If a target software cannot be reached safely from the browser, the system should simply offer the export package and stop there.

### 6.4 Browser-based delivery flow

Use the browser as the delivery and handoff layer.

Recommended flow:

1. User clicks “Download for Revit” or a similar browser action from the web catalogue.
2. The web app returns the correct export package, preview, or bundle.
3. The user manually imports it into the target software.
4. For software that has a safe web or API-based upload path, the browser flow can push the export directly.

This keeps the feature usable without depending on a local desktop agent.

### 6.5 Software-specific handling

- Revit: offer downloadable family or appearance asset packages for manual import.
- AutoCAD: offer downloadable import-compatible package files.
- Lumion: offer the accepted library/package format for manual import.
- D5: offer a D5-compatible import package.

The catalog should store the export-ready formats, not just a generic media preview. When browser-based automation is not possible, the product should degrade gracefully to download-only delivery.

## 7. Module E: Material Bridge

This is the highest-value connection in the whole system.

When a 3D asset represents a real-world material, link it to the goods item that drives pricing.

That bridge should support:

- Asset to goods mapping
- Default estimate seeding from the latest valid price
- Shared tags between material visual style and material SKU
- Optional vendor preference when a material has a common supplier

This lets a designer choose a material visually and immediately seed the cost estimate with a real inventory-backed price.

## 8. Suggested Build Phases

### Phase 1: Data foundation

- Goods master
- Vendors
- Price history
- Unit conversion
- GST rules
- Wastage factor

### Phase 2: Estimate engine

- Estimate header controls
- Estimate versioning and lifecycle
- Section and room grouping
- Wastage-adjusted line pricing
- Labor component
- GST auto-split
- BOQ and invoice output

### Phase 3: Feedback loop

- Actual quantity capture
- Actual price capture
- Variance tracking
- Price update suggestions

### Phase 4: 3D asset catalogue

- Asset record management
- Catalogue taxonomy and search
- Preview storage
- Export variant tracking
- Software-specific packaging

### Phase 5: Desktop bridge

- Browser export delivery
- Downloadable format bundles
- Optional direct web upload where the target software exposes a safe browser or API path

### Phase 6: Material linking

- Asset-to-goods mapping
- Estimate seeding from selected assets
- Cross-module reporting

## 9. Delivery Priorities

Build the goods intelligence and estimate engine first, because they affect pricing correctness immediately. Add the feedback loop next so the database gets smarter from real projects. Then layer the 3D asset catalogue and browser export flow on top, because those features are strongest when they inherit clean pricing and linking rules.

## 10. What This Plan Deliberately Avoids

- No single stored price field without history
- No manual GST entry on every invoice line
- No estimate line that mixes labor and material blindly
- No browser-only integration pretending to control desktop CAD software directly
- No disconnected asset catalogue that cannot feed pricing
