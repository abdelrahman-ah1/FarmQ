PRODUCT REQUIREMENTS DOCUMENT
FarmQ
Soil & Yield Optimization System
نظام تحليل التربة وتحسين الإنتاجية الزراعية
Organization: LogiQ Studio
Document Status: Approved for MVP Development — Egypt Market Localization
Revision Scope: Egyptian Market Localization (Bilingual EN/AR, EGP Pricing & Local Payment Rails, MALR/ARC/SWERI & NARSS Alignment, Regional Crop Calendars, Connectivity-Aware UX)
 1. Egypt Market Localization Summary
ملخص التوطين للسوق المصري
This revision adapts the FarmQ PRD for launch in the Egyptian agricultural market. The document is now bilingual (English / Arabic), reflects EGP pricing with locally relevant payment rails, aligns data sourcing and terminology with Egyptian agricultural authorities, and adjusts UX assumptions to fit rural connectivity and device realities common across the Nile Delta, Upper Egypt, and the new desert reclamation zones.
Localization covers six areas:
1.	Bilingual interface and documentation (English / Arabic, right-to-left support).
2.	Egyptian Pound (EGP) pricing with local payment rails: Fawry, Vodafone Cash, Meeza, and InstaPay, processed through Egypt-focused payment gateways such as Paymob or Kashier.
3.	Alignment with the Ministry of Agriculture and Land Reclamation (MALR), its Agricultural Research Center (ARC) and Soils, Water and Environment Research Institute (SWERI), and the National Authority for Remote Sensing and Space Sciences (NARSS) as reference data and terminology sources.
4.	Crop and soil baselines tuned to Egypt's dominant cropping pattern: cotton, wheat, maize/corn, rice in the Delta, sugarcane in Upper Egypt, and horticultural/export crops (citrus, grapes, strawberries) in reclaimed desert land.
5.	UX adjustments for connectivity-constrained rural use: low-bandwidth fallback views, offline-tolerant CSV upload, and mobile-aware fallback messaging, while preserving the desktop-first MVP scope.
6.	Regulatory and trust framing appropriate to Egypt's agtech and data context, including Central Bank of Egypt (CBE) oversight of payment processing and sensitivity around farm geolocation data.
 2. Egyptian Market Context
السياق السوقي في مصر
2.1 Why Localization Matters Here
Egyptian agriculture is dominated by smallholder and mid-size farms concentrated in the Nile Delta and Nile Valley, alongside a fast-growing reclaimed desert farming sector (New Valley, West Noubaria, East Owainat) producing export horticulture. Internet connectivity and smartphone penetration are strong in urban governorates but inconsistent in rural field locations, and Arabic is the working language for the large majority of farm operators, even where English is used in agribusiness and consultancy contexts.
These conditions shape three product decisions carried through this revision: a bilingual interface rather than English-only, a soil-data-first free tier that works even with unreliable connectivity (manual CSV upload does not require a live connection at the moment of data collection), and local payment rails so that the paid tier is actually payable by the target user base, most of whom transact primarily in EGP through mobile wallets or Fawry rather than international cards.
2.2 Reference Crops and Regions
Region	Dominant Crops	Soil / Water Notes
Nile Delta (Gharbia, Kafr El-Sheikh, Dakahlia)	Cotton, rice, wheat, maize, vegetables	Fertile alluvial soils; rising salinity in northern Delta near the coast
Nile Valley / Upper Egypt	Sugarcane, wheat, maize, banana	Heavier clay soils; irrigation scheduling tied to canal rotation
Reclaimed Desert (New Valley, West Noubaria, East Owainat)	Citrus, grapes, strawberries, olives, export vegetables	Sandy soils, low organic matter, heavy reliance on precision irrigation and fertigation
2.3 Connectivity and Device Reality
•	Field-level mobile data coverage is inconsistent in parts of the Delta and especially in reclaimed desert zones; the manual CSV upload workflow is deliberately tolerant of this, since soil sampling and lab analysis happen offline and the resulting CSV can be uploaded once the operator reaches a connected location.
•	The MVP remains desktop-web optimized per the original scope; in the Egyptian context this maps to shared computers at agricultural cooperatives, extension offices, or input-supplier shops, which are common access points for smallholders who may not personally own a suitable device.
•	Where a Farm Operator does not have personal desktop access, the Agronomist/Consultant role (Section 3) is expected to be the primary system user on their behalf — a common service relationship in Egyptian agricultural extension.
 3. System Users
مستخدمو النظام
3.1 User Roles Overview
Role	Plan Access	Primary Goal	Egypt Context Note
Farm Operator (مزارع)	Free or Paid	Upload soil data, read fertilization plans, act on alerts	May access via cooperative or extension office device rather than personal computer
Agronomist / Consultant (مهندس زراعي)	Paid (multi-farm)	Review data across client farms, validate recommendations	Common intermediary role; often the de facto system user for smallholders
Farm Owner / Decision-Maker (صاحب الأرض)	Free or Paid	Oversight, budget approval, upgrade decisions	May be an absentee landowner relying on the Operator or Agronomist for day-to-day use
System Administrator	N/A (internal)	API keys, billing tier enforcement, configuration	Manages MALR/NARSS data source credentials and EGP billing integration
Prospective Visitor (زائر)	Unauthenticated	Evaluate the product before signing up	Primary entry point is the Arabic-first Landing Page
 4. System Architecture
بنية النظام
4.1 Localization-Driven Architecture Additions
The architecture established in the prior revision (PHP application layer, async Python geospatial processing, relational SQL store, tier entitlement enforcement, public landing layer) is retained in full. Two additions are introduced for the Egyptian market:
•	Localization Layer: serves bilingual UI strings (English/Arabic), applies right-to-left layout rules where Arabic is active, and formats currency, dates, and numerals per Egyptian conventions.
•	Payment Gateway Integration: the billing component integrates with an Egypt-focused payment gateway (e.g. Paymob or Kashier) to accept Meeza cards, Fawry references, Vodafone Cash, and InstaPay, settling in EGP. International cards remain a secondary path, since many Egyptian-issued cards are restricted for foreign-currency transactions.
4.2 Reference Data Sourcing
Crop baseline matrices and soil interpretation thresholds reference published guidance from Egypt's Agricultural Research Center (ARC) and its Soils, Water and Environment Research Institute (SWERI), both under the Ministry of Agriculture and Land Reclamation (MALR). Where satellite-derived indices are cross-validated, the system references methodologies consistent with Egypt's National Authority for Remote Sensing and Space Sciences (NARSS), which conducts national-scale soil quality and land suitability mapping for arid and reclaimed land.
4.3 Component Responsibility Table (Updated)
Component	Responsibility	Tier Availability
Landing Page (EN/AR)	Bilingual public marketing, plan comparison, signup/demo CTA	Public (no auth)
Localization Layer	Bilingual strings, RTL layout, EGP currency formatting	All tiers
PHP Application Layer	Routing, auth, session state, entitlement checks	All tiers
CSV Ingestion Service	Parse/validate manual soil sample uploads	All tiers
Crop Selection Engine	Egypt-calibrated crop baseline matrices	All tiers
Sentinel-2 Geospatial Fetcher	Pull multispectral imagery for farm polygon	Paid only
NDVI/NDRE Processor (Python)	Compute vegetation/health indices, NARSS-aligned methodology	Paid only
Deficiency Mapping	Visual sub-zone fertilizer deficiency map	Paid only
Fertilization Blueprint Engine	ARC/SWERI-aligned elemental fertilization schedule	All tiers (soil-only on free)
Irrigation Optimization (ET)	Evapotranspiration scheduling, canal-rotation-aware for Upper Egypt	Paid only
Threat & Pest Alerts	Frost/heatwave/pest/disease dashboard alerts	Paid only
Payment Gateway Integration	EGP billing via Paymob/Kashier: Meeza, Fawry, Vodafone Cash, InstaPay	Paid tier signup/renewal
Message Queue	Async dispatch of Python geospatial jobs	Paid-triggered only
Relational SQL Database	Spatial polygons, temporal records, crop matrices, billing state	All tiers
 5. Pricing & Payments — Egypt Market
التسعير وطرق الدفع - السوق المصري
5.1 Pricing Tiers (EGP)
Capability	Free Tier — مجاني	Paid Tier — مدفوع
Indicative price	0 EGP	From ~450 EGP / season per farm polygon
Manual soil CSV upload	Included	Included
Crop selection engine (Egypt-calibrated)	Included	Included
Fertilization blueprint (soil-only)	Included	Included
Sentinel-2 satellite imagery	Not included	Included
NDVI/NDRE comparative analysis	Not included	Included
Geospatial deficiency mapping	Not included	Included
Microclimate (7-day) forecasting	Not included	Included
Irrigation & water optimization	Not included	Included
Automated threat alerts	Not included	Included
Pest & disease predictive modeling	Not included	Included
Multi-farm portfolio view (Agronomist)	Not included	Included
5.2 Local Payment Rails
Payment Rail	Description	Relevance to FarmQ
Fawry	Nationwide cash-acceptance network	Lets unbanked/cash-preferring operators pay without a card
Vodafone Cash	Mobile wallet tied to telecom subscriber base	High reach in rural/Delta governorates
Meeza	Central Bank of Egypt-backed national card scheme	Default domestic debit/prepaid card
InstaPay	CBE-backed real-time interbank transfer network	Useful for Agronomist/Consultant accounts
Visa / Mastercard (secondary)	International card rails	Available but secondary
5.3 Billing & Compliance Notes
•	All payment processing for the Egyptian market routes through a CBE-regulated payment service provider; FarmQ does not handle card or wallet credentials directly.
•	Checkout flow defaults to Arabic with EGP as the displayed currency; English/USD remains available for cross-border agribusiness or investor-facing accounts.
•	Receipts and invoices are issued in EGP and should reflect applicable Egyptian e-invoicing requirements at commercial launch; this PRD flags the requirement but defers exact tax/e-invoice integration to a follow-up technical spec.
 6. Landing Page — Egypt Market
الصفحة الرئيسية - السوق المصري
6.1 Language & Layout
•	Arabic is the default language on first load, detected by browser/OS locale where possible, with a visible toggle to switch to English; layout mirrors to right-to-left when Arabic is active.
•	Visual design retains the system's high-contrast, minimalist style; Arabic typography uses a clean, legible Arabic-supporting typeface consistent with the English brand typeface's weight and tone.
•	Currency throughout the landing page displays in EGP by default.
6.2 Required Sections (Updated)
•	Hero section: product name, one-line value proposition in Arabic and English, primary call-to-action (Sign Up Free / اشترك مجانًا).
•	How it works: three-step visual summary — upload soil data, select crop, receive fertilization blueprint — using Egypt-relevant example crops (e.g. cotton, wheat, citrus) rather than generic placeholders.
•	Plan comparison table: Free vs. Paid feature matrix in EGP, consistent with Table 5.1.
•	Payment trust section: visible logos/mentions of supported local rails (Fawry, Vodafone Cash, Meeza, InstaPay) to build payment confidence ahead of checkout.
•	Data credibility section: references soil science and remote-sensing methodology consistent with ARC/SWERI and NARSS practice, without claiming a direct system integration with these bodies.
•	Secondary call-to-action / footer: Sign Up, Request Demo, and contact details appropriate for Egyptian business hours and an Arabic-speaking support channel.
6.3 Technical Notes
•	Served from the Public Layer, decoupled from the authenticated PHP application session state, consistent with the original architecture.
•	Localization Layer (Section 4.1) supplies bilingual strings to the landing page as well as the authenticated application, avoiding a separate translation pipeline.
•	Desktop web optimized exclusively, consistent with the MVP's client interface constraint; the landing page is the one surface most likely to also see mobile traffic pre-signup, so degrading gracefully on mobile browsers (even without being a full responsive build) is recommended.
 7. Page Hierarchy — Egypt Market Edition
هيكل الصفحات - النسخة المصرية
•	0. Landing Page (الصفحة الرئيسية): Public, bilingual (Arabic-default), unauthenticated marketing page with EGP pricing and local payment trust signals. Routes to Sign Up or Request Demo.
•	1. Main Dashboard (لوحة التحكم): Centralized display restricted to critical data points, bilingual UI. Shows the soil-only fertilization summary on Free tier; adds satellite health summary, weather, and pest alerts on Paid tier.
•	2. Geospatial Map View (الخريطة الجغرافية) (Paid only): High-contrast visual interface for satellite imagery and localized deficiency mapping, NARSS-aligned methodology references. Free-tier accounts see an upgrade prompt with EGP pricing.
•	3. Data Ingestion & Configuration (إدخال البيانات): Interface for manual CSV upload of baseline ground-truth soil metrics (NPK, pH, salinity) and parametric target crop selection from an Egypt-calibrated crop list.
•	4. Precision Fertilization Blueprint (خطة التسميد): Dedicated view for the dynamically generated, elemental-level fertilization schedule, ARC/SWERI-aligned thresholds.
•	5. Irrigation Management (إدارة الري) (Paid only): Module displaying calculated evapotranspiration (ET) rates and automated watering schedules, with canal-rotation timing relevance for Upper Egypt.
•	6. Historical Tracking (السجل التاريخي): Archival interface displaying longitudinal soil analytics on all tiers; satellite scans and combined seasonal trend data added on Paid tier, aligned to winter (شتوي) and summer (صيفي) season cycles.
•	7. Billing & Plan (الفواتير والاشتراك) (New): EGP billing management, payment rail selection (Fawry, Vodafone Cash, Meeza, InstaPay), invoice history.
•	8. System Settings (إعدادات النظام): Configuration interface for API key management, database routing parameters, account tier entitlement, payment gateway credentials, and single-instance deployment settings.
End of document.
نهاية المستند.
