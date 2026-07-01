-- Wave 2: farm governorate + crop micronutrient notes (run once)
ALTER TABLE farms ADD COLUMN governorate VARCHAR(64) NULL AFTER region;

ALTER TABLE crop_baselines ADD COLUMN micronutrient_notes JSON NULL AFTER arc_reference_note;

UPDATE crop_baselines SET micronutrient_notes = '{"en":"Monitor Zn and Fe foliar sprays on sandy reclaimed soils — common ARC extension guidance for export citrus.","ar":"راقب الرش الورقي للزنك والحديد في التربة الرملية بالواحات — إرشادات ARC الشائعة للحمضيات."}'
WHERE crop_code = 'citrus';

UPDATE crop_baselines SET micronutrient_notes = '{"en":"Grapes on desert sand: schedule Fe chelate if leaf chlorosis appears mid-season.","ar":"العنب على الرمال: جدول شيلات الحديد عند ظهور اصفار الأوراق منتصف الموسم."}'
WHERE crop_code = 'grapes';

UPDATE crop_baselines SET micronutrient_notes = '{"en":"Strawberries require frequent Zn/Fe monitoring under drip fertigation in reclaimed land.","ar":"الفراولة تحتاج متابعة Zn/Fe مع التسميد بالتنقيط في الأراضي المستصلحة."}'
WHERE crop_code = 'strawberries';
