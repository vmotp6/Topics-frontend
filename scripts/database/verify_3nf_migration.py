#!/usr/bin/env python3
"""
驗證第三正規化（3NF）遷移結果
"""

import pymysql

DB_CONFIG = {
    'host': 'localhost',
    'user': 'root',
    'password': '',
    'database': 'topics_good',
    'charset': 'utf8mb4'
}

def verify_migration():
    """驗證遷移結果"""
    try:
        conn = pymysql.connect(**DB_CONFIG)
        cursor = conn.cursor()
        
        print("=" * 60)
        print("第三正規化（3NF）遷移驗證報告")
        print("=" * 60)
        print()
        
        # 1. 檢查創建的表
        print("📋 檢查正規化表...")
        normalized_tables = [
            'departments', 'education_systems', 'application_statuses',
            'identities', 'genders', 'grades', 'companies',
            'enrollment_applications_normalized', 'enrollment_preferences',
            'cooperation_applications_normalized', 'cooperation_application_categories',
            'ip_rights'
        ]
        
        for table in normalized_tables:
            cursor.execute(f"SHOW TABLES LIKE '{table}'")
            exists = cursor.fetchone()
            status = "✅" if exists else "❌"
            print(f"   {status} {table}")
        
        print()
        
        # 2. 檢查資料數量
        print("📊 檢查資料數量...")
        tables_to_check = [
            ('departments', '科系'),
            ('education_systems', '學制'),
            ('application_statuses', '申請狀態'),
            ('identities', '身分別'),
            ('genders', '性別'),
            ('grades', '年級'),
            ('enrollment_applications_normalized', '就讀意願申請（正規化）'),
            ('enrollment_preferences', '就讀意願明細'),
            ('companies', '公司')
        ]
        
        for table, desc in tables_to_check:
            try:
                cursor.execute(f"SELECT COUNT(*) FROM {table}")
                count = cursor.fetchone()[0]
                print(f"   {desc}: {count} 筆")
            except:
                print(f"   {desc}: 表不存在或無法查詢")
        
        print()
        
        # 3. 檢查視圖
        print("👁️  檢查視圖...")
        views = ['enrollment_applications_view', 'cooperation_applications_view']
        for view in views:
            try:
                cursor.execute(f"SELECT COUNT(*) FROM {view}")
                count = cursor.fetchone()[0]
                print(f"   ✅ {view}: {count} 筆記錄")
            except Exception as e:
                print(f"   ❌ {view}: {str(e)}")
        
        print()
        
        # 4. 檢查外鍵關係
        print("🔗 檢查外鍵關係...")
        cursor.execute("""
            SELECT 
                TABLE_NAME,
                CONSTRAINT_NAME,
                REFERENCED_TABLE_NAME
            FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = 'topics_good'
            AND REFERENCED_TABLE_NAME IS NOT NULL
            AND TABLE_NAME LIKE '%normalized%'
            ORDER BY TABLE_NAME, CONSTRAINT_NAME
        """)
        
        fks = cursor.fetchall()
        if fks:
            print("   找到以下外鍵關係：")
            for fk in fks:
                print(f"   - {fk[0]}.{fk[1]} -> {fk[2]}")
        else:
            print("   ⚠️  未找到外鍵關係")
        
        print()
        print("=" * 60)
        print("✅ 驗證完成！")
        print("=" * 60)
        
        cursor.close()
        conn.close()
        
    except Exception as e:
        print(f"❌ 驗證失敗: {e}")
        import traceback
        traceback.print_exc()

if __name__ == '__main__':
    verify_migration()

