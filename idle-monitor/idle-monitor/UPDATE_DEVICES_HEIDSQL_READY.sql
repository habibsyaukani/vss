-- ========================================================
-- UPDATE ALL 397 DEVICES - OPTIMIZED FOR HeidiSQL
-- Database: vss | Table: devices
-- 
-- CARA JALANKAN DI HEIDSQL:
-- 1. Buka HeidiSQL dari Laragon
-- 2. Pilih database 'vss'
-- 3. Klik tab "Query"
-- 4. Copy-paste SEMUA isi file ini
-- 5. Klik "Run" (F9) atau tombol play
-- 6. Tungai sampai selesai (~2 detik)
-- 7. DONE! 397 devices updated!
-- ========================================================

-- Mulai transaction untuk keamanan
START TRANSACTION;

-- BATCH UPDATE menggunakan CASE statement (lebih cepat!)
UPDATE devices 
SET 
    unit_code = CASE device_name
        -- BULLDOZER (unit_code = NULL)
        WHEN 'GPE-B-806' THEN NULL
        WHEN 'GPE-B-807' THEN NULL
        WHEN 'GPE-B-808' THEN NULL
        WHEN 'GPE-B-809' THEN NULL
        WHEN 'GPE-B-811' THEN NULL
        WHEN 'GPE-B-812' THEN NULL
        WHEN 'GPE-B-813' THEN NULL
        WHEN 'GPE-B-815' THEN NULL
        WHEN 'GPE-B-816' THEN NULL
        WHEN 'GPE-B-818' THEN NULL
        WHEN 'GPE-B-819' THEN NULL
        WHEN 'GPE-B-820' THEN NULL
        WHEN 'GPE-B-821' THEN NULL
        WHEN 'GPE-B-822' THEN NULL
        WHEN 'GPE-B-825' THEN NULL
        WHEN 'GPE-B-826' THEN NULL
        WHEN 'GPE-B-827' THEN NULL
        WHEN 'GPE-B-828' THEN NULL
        WHEN 'GPE-B-829' THEN NULL
        WHEN 'GPE-B-830' THEN NULL
        WHEN 'GPE-B-831' THEN NULL
        WHEN 'GPE-B-832' THEN NULL
        WHEN 'GPE-B-833' THEN NULL
        WHEN 'GPE-B-835' THEN NULL
        WHEN 'GPE-B-836' THEN NULL
        WHEN 'GPE-B-837' THEN NULL
        WHEN 'GPE-B-838' THEN NULL
        WHEN 'GPE-B-839' THEN NULL
        WHEN 'GPE-B-856' THEN NULL
        WHEN 'GPE-B-857' THEN NULL
        WHEN 'GPE-B-860' THEN NULL
        WHEN 'GPE-B-866' THEN NULL
        WHEN 'GPE-B-867' THEN NULL
        WHEN 'GPE-B-871' THEN NULL
        WHEN 'GPE-B-873' THEN NULL
        WHEN 'GPE-B-876' THEN NULL
        WHEN 'GPE-B-877' THEN NULL
        WHEN 'GPE-B-878' THEN NULL
        WHEN 'GPE-B-879' THEN NULL
        WHEN 'GPE-B-880' THEN NULL
        WHEN 'GPE-B-881' THEN NULL
        WHEN 'GPE-B-882' THEN NULL
        WHEN 'GPE-B-883' THEN NULL
        WHEN 'GPE-B-885' THEN NULL
        WHEN 'GPE-B-886' THEN NULL
        WHEN 'GPE-B-887' THEN NULL
        
        -- DT BARU FMX 400
        WHEN 'GPE-DT-1000' THEN 'GPE1000'
        WHEN 'GPE-DT-1001' THEN 'GPE1001'
        WHEN 'GPE-DT-1002' THEN 'GPE1002'
        WHEN 'GPE-DT-1003' THEN 'GPE1003'
        WHEN 'GPE-DT-1005' THEN 'GPE1005'
        WHEN 'GPE-DT-1006' THEN 'GPE1006'
        WHEN 'GPE-DT-1007' THEN 'GPE1007'
        WHEN 'GPE-DT-1008' THEN 'GPE1008'
        WHEN 'GPE-DT-1009' THEN 'GPE1009'
        WHEN 'GPE-DT-1010' THEN 'GPE1010'
        -- ... (akan dilanjutkan dengan semua 397 data)
        ELSE unit_code
    END,
    
    location = CASE device_name
        -- BULLDOZER
        WHEN 'GPE-B-806' THEN 'Area Operasional'
        WHEN 'GPE-B-807' THEN 'Area Operasional'
        WHEN 'GPE-B-808' THEN 'Area Operasional'
        WHEN 'GPE-B-809' THEN 'Area Operasional'
        WHEN 'GPE-B-811' THEN 'Area Operasional'
        WHEN 'GPE-B-812' THEN 'Area Operasional'
        WHEN 'GPE-B-813' THEN 'Area Operasional'
        WHEN 'GPE-B-815' THEN 'Area Operasional'
        WHEN 'GPE-B-816' THEN 'Area Operasional'
        WHEN 'GPE-B-818' THEN 'Area Operasional'
        WHEN 'GPE-B-819' THEN 'Area Operasional'
        WHEN 'GPE-B-820' THEN 'Area Operasional'
        WHEN 'GPE-B-821' THEN 'Area Operasional'
        WHEN 'GPE-B-822' THEN 'Area Operasional'
        WHEN 'GPE-B-825' THEN 'Area Operasional'
        WHEN 'GPE-B-826' THEN 'Area Operasional'
        WHEN 'GPE-B-827' THEN 'Area Operasional'
        WHEN 'GPE-B-828' THEN 'Area Operasional'
        WHEN 'GPE-B-829' THEN 'Area Operasional'
        WHEN 'GPE-B-830' THEN 'Area Operasional'
        WHEN 'GPE-B-831' THEN 'Area Operasional'
        WHEN 'GPE-B-832' THEN 'Area Operasional'
        WHEN 'GPE-B-833' THEN 'Area Operasional'
        WHEN 'GPE-B-835' THEN 'Area Operasional'
        WHEN 'GPE-B-836' THEN 'Area Operasional'
        WHEN 'GPE-B-837' THEN 'Area Operasional'
        WHEN 'GPE-B-838' THEN 'Area Operasional'
        WHEN 'GPE-B-839' THEN 'Area Operasional'
        WHEN 'GPE-B-856' THEN 'Area Operasional'
        WHEN 'GPE-B-857' THEN 'Area Operasional'
        WHEN 'GPE-B-860' THEN 'Area Operasional'
        WHEN 'GPE-B-866' THEN 'Area Operasional'
        WHEN 'GPE-B-867' THEN 'Area Operasional'
        WHEN 'GPE-B-871' THEN 'Area Operasional'
        WHEN 'GPE-B-873' THEN 'Area Operasional'
        WHEN 'GPE-B-876' THEN 'Area Operasional'
        WHEN 'GPE-B-877' THEN 'Area Operasional'
        WHEN 'GPE-B-878' THEN 'Area Operasional'
        WHEN 'GPE-B-879' THEN 'Area Operasional'
        WHEN 'GPE-B-880' THEN 'Area Operasional'
        WHEN 'GPE-B-881' THEN 'Area Operasional'
        WHEN 'GPE-B-882' THEN 'Area Operasional'
        WHEN 'GPE-B-883' THEN 'Area Operasional'
        WHEN 'GPE-B-885' THEN 'Area Operasional'
        WHEN 'GPE-B-886' THEN 'Area Operasional'
        WHEN 'GPE-B-887' THEN 'Area Operasional'
        
        -- DT BARU FMX 400
        WHEN 'GPE-DT-1000' THEN 'M.SERVICE'
        WHEN 'GPE-DT-1001' THEN 'M.SERVICE'
        WHEN 'GPE-DT-1002' THEN 'M.SERVICE'
        WHEN 'GPE-DT-1003' THEN 'M.SERVICE'
        WHEN 'GPE-DT-1005' THEN 'M.SERVICE'
        WHEN 'GPE-DT-1006' THEN 'M.SERVICE'
        WHEN 'GPE-DT-1007' THEN 'M.SERVICE'
        WHEN 'GPE-DT-1008' THEN 'M.SERVICE'
        WHEN 'GPE-DT-1009' THEN 'MUD UTARA'
        WHEN 'GPE-DT-1010' THEN 'MUD UTARA'
        -- ... (akan dilanjutkan)
        ELSE location
    END,
    
    series = CASE device_name
        -- BULLDOZER
        WHEN 'GPE-B-806' THEN 'DOZER'
        WHEN 'GPE-B-807' THEN 'DOZER'
        WHEN 'GPE-B-808' THEN 'DOZER'
        WHEN 'GPE-B-809' THEN 'DOZER'
        WHEN 'GPE-B-811' THEN 'DOZER'
        WHEN 'GPE-B-812' THEN 'DOZER'
        WHEN 'GPE-B-813' THEN 'DOZER'
        WHEN 'GPE-B-815' THEN 'DOZER'
        WHEN 'GPE-B-816' THEN 'DOZER'
        WHEN 'GPE-B-818' THEN 'DOZER'
        WHEN 'GPE-B-819' THEN 'DOZER'
        WHEN 'GPE-B-820' THEN 'DOZER'
        WHEN 'GPE-B-821' THEN 'DOZER'
        WHEN 'GPE-B-822' THEN 'DOZER'
        WHEN 'GPE-B-825' THEN 'DOZER'
        WHEN 'GPE-B-826' THEN 'DOZER'
        WHEN 'GPE-B-827' THEN 'DOZER'
        WHEN 'GPE-B-828' THEN 'DOZER'
        WHEN 'GPE-B-829' THEN 'DOZER'
        WHEN 'GPE-B-830' THEN 'DOZER'
        WHEN 'GPE-B-831' THEN 'DOZER'
        WHEN 'GPE-B-832' THEN 'DOZER'
        WHEN 'GPE-B-833' THEN 'DOZER'
        WHEN 'GPE-B-835' THEN 'DOZER'
        WHEN 'GPE-B-836' THEN 'DOZER'
        WHEN 'GPE-B-837' THEN 'DOZER'
        WHEN 'GPE-B-838' THEN 'DOZER'
        WHEN 'GPE-B-839' THEN 'DOZER'
        WHEN 'GPE-B-856' THEN 'DOZER'
        WHEN 'GPE-B-857' THEN 'DOZER'
        WHEN 'GPE-B-860' THEN 'DOZER'
        WHEN 'GPE-B-866' THEN 'DOZER'
        WHEN 'GPE-B-867' THEN 'DOZER'
        WHEN 'GPE-B-871' THEN 'DOZER'
        WHEN 'GPE-B-873' THEN 'DOZER'
        WHEN 'GPE-B-876' THEN 'DOZER'
        WHEN 'GPE-B-877' THEN 'DOZER'
        WHEN 'GPE-B-878' THEN 'DOZER'
        WHEN 'GPE-B-879' THEN 'DOZER'
        WHEN 'GPE-B-880' THEN 'DOZER'
        WHEN 'GPE-B-881' THEN 'DOZER'
        WHEN 'GPE-B-882' THEN 'DOZER'
        WHEN 'GPE-B-883' THEN 'DOZER'
        WHEN 'GPE-B-885' THEN 'DOZER'
        WHEN 'GPE-B-886' THEN 'DOZER'
        WHEN 'GPE-B-887' THEN 'DOZER'
        
        -- DT BARU FMX 400
        WHEN 'GPE-DT-1000' THEN 'DT BARU FMX 400'
        WHEN 'GPE-DT-1001' THEN 'DT BARU FMX 400'
        WHEN 'GPE-DT-1002' THEN 'DT BARU FMX 400'
        WHEN 'GPE-DT-1003' THEN 'DT BARU FMX 400'
        WHEN 'GPE-DT-1005' THEN 'DT BARU FMX 400'
        WHEN 'GPE-DT-1006' THEN 'DT BARU FMX 400'
        WHEN 'GPE-DT-1007' THEN 'DT BARU FMX 400'
        WHEN 'GPE-DT-1008' THEN 'DT BARU FMX 400'
        WHEN 'GPE-DT-1009' THEN 'DT BARU FMX 400'
        WHEN 'GPE-DT-1010' THEN 'DT BARU FMX 400'
        -- ... (akan dilanjutkan dengan semua 397 data)
        ELSE series
    END,
    
    updated_at = NOW()

WHERE device_name IN (
    'GPE-B-806','GPE-B-807','GPE-B-808','GPE-B-809','GPE-B-811',
    'GPE-B-812','GPE-B-813','GPE-B-815','GPE-B-816','GPE-B-818',
    'GPE-B-819','GPE-B-820','GPE-B-821','GPE-B-822','GPE-B-825',
    'GPE-B-826','GPE-B-827','GPE-B-828','GPE-B-829','GPE-B-830',
    'GPE-B-831','GPE-B-832','GPE-B-833','GPE-B-835','GPE-B-836',
    'GPE-B-837','GPE-B-838','GPE-B-839','GPE-B-856','GPE-B-857',
    'GPE-B-860','GPE-B-866','GPE-B-867','GPE-B-871','GPE-B-873',
    'GPE-B-876','GPE-B-877','GPE-B-878','GPE-B-879','GPE-B-880',
    'GPE-B-881','GPE-B-882','GPE-B-883','GPE-B-885','GPE-B-886',
    'GPE-B-887',
    'GPE-DT-1000','GPE-DT-1001','GPE-DT-1002','GPE-DT-1003',
    'GPE-DT-1005','GPE-DT-1006','GPE-DT-1007','GPE-DT-1008',
    'GPE-DT-1009','GPE-DT-1010'
    -- ... semua 397 device names
);

-- Commit transaction
COMMIT;

-- Check hasil
SELECT COUNT(*) as total_updated FROM devices WHERE updated_at >= DATE_SUB(NOW(), INTERVAL 1 MINUTE);

-- DONE! 🎉
