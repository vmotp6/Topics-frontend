#!/usr/bin/env python3
"""
執行第三正規化（3NF）遷移腳本
"""

import pymysql
import sys
import os
from datetime import datetime

# 資料庫配置
DB_CONFIG = {
    'host': 'localhost',
    'user': 'root',
    'password': '',
    'database': 'topics_good',
    'charset': 'utf8mb4'
}

def execute_sql_file(connection, file_path):
    """執行 SQL 檔案"""
    try:
        with open(file_path, 'r', encoding='utf-8') as f:
            sql_content = f.read()
        
        # 分割 SQL 語句（以分號分割）
        sql_statements = [stmt.strip() + ';' for stmt in sql_content.split(';') if stmt.strip() and not stmt.strip().startswith('--')]
        
        cursor = connection.cursor()
        
        print(f"📝 開始執行 {file_path}")
        print(f"   共 {len(sql_statements)} 個 SQL 語句\n")
        
        success_count = 0
        error_count = 0
        
        for i, sql in enumerate(sql_statements, 1):
            if not sql.strip() or sql.strip() == ';':
                continue
                
            try:
                # 跳過視圖創建（會自動處理）
                if sql.upper().startswith('CREATE OR REPLACE VIEW'):
                    cursor.execute(sql)
                    connection.commit()
                    success_count += 1
                    print(f"   ✅ [{i}/{len(sql_statements)}] 視圖創建成功")
                elif sql.upper().startswith('CREATE'):
                    cursor.execute(sql)
                    connection.commit()
                    success_count += 1
                    if 'TABLE' in sql.upper():
                        print(f"   ✅ [{i}/{len(sql_statements)}] 表創建成功")
                elif sql.upper().startswith('INSERT'):
                    cursor.execute(sql)
                    connection.commit()
                    affected = cursor.rowcount
                    if affected > 0:
                        success_count += 1
                        print(f"   ✅ [{i}/{len(sql_statements)}] 插入 {affected} 筆資料")
                elif sql.upper().startswith('SELECT'):
                    cursor.execute(sql)
                    results = cursor.fetchall()
                    success_count += 1
                    if results:
                        print(f"   ✅ [{i}/{len(sql_statements)}] 查詢完成，返回 {len(results)} 筆結果")
                        # 顯示前幾筆結果
                        for row in results[:3]:
                            print(f"      {row}")
                        if len(results) > 3:
                            print(f"      ... 還有 {len(results) - 3} 筆結果")
                elif sql.upper().startswith('DROP'):
                    cursor.execute(sql)
                    connection.commit()
                    success_count += 1
                    print(f"   ✅ [{i}/{len(sql_statements)}] 刪除操作成功")
                elif sql.upper().startswith('ALTER'):
                    cursor.execute(sql)
                    connection.commit()
                    success_count += 1
                    print(f"   ✅ [{i}/{len(sql_statements)}] 修改操作成功")
                else:
                    # 其他 SQL 語句
                    cursor.execute(sql)
                    connection.commit()
                    success_count += 1
                    
            except pymysql.Error as e:
                error_count += 1
                error_msg = str(e)
                # 跳過一些可以忽略的錯誤
                if 'already exists' in error_msg.lower() or 'duplicate' in error_msg.lower():
                    print(f"   ⚠️  [{i}/{len(sql_statements)}] 已存在（可忽略）")
                    success_count += 1
                    error_count -= 1
                else:
                    print(f"   ❌ [{i}/{len(sql_statements)}] 執行失敗: {error_msg}")
                    print(f"      SQL: {sql[:100]}...")
        
        print(f"\n📊 執行統計：")
        print(f"   ✅ 成功: {success_count}")
        print(f"   ❌ 失敗: {error_count}")
        print(f"   📝 總計: {len(sql_statements)}")
        
        cursor.close()
        return success_count, error_count
        
    except FileNotFoundError:
        print(f"❌ 檔案不存在: {file_path}")
        return 0, 1
    except Exception as e:
        print(f"❌ 執行失敗: {e}")
        import traceback
        traceback.print_exc()
        return 0, 1

def main():
    """主函數"""
    print("=" * 60)
    print("資料庫第三正規化（3NF）遷移腳本")
    print("=" * 60)
    print()
    
    # 獲取腳本目錄
    script_dir = os.path.dirname(os.path.abspath(__file__))
    migration_file = os.path.join(script_dir, 'normalize_to_3nf.sql')
    
    # 連接資料庫
    try:
        print("🔌 正在連接資料庫...")
        connection = pymysql.connect(**DB_CONFIG)
        print(f"✅ 資料庫連接成功！")
        print(f"   主機: {DB_CONFIG['host']}")
        print(f"   資料庫: {DB_CONFIG['database']}")
        print()
        
        # 確認執行
        print("⚠️  警告：此操作將修改資料庫結構")
        print("   建議：執行前請先備份資料庫")
        print()
        
        response = input("是否繼續執行遷移？(yes/no): ").strip().lower()
        if response not in ['yes', 'y']:
            print("❌ 已取消執行")
            connection.close()
            return
        
        print()
        print("=" * 60)
        print("開始執行遷移...")
        print("=" * 60)
        print()
        
        # 執行遷移腳本
        success, error = execute_sql_file(connection, migration_file)
        
        print()
        print("=" * 60)
        if error == 0:
            print("✅ 遷移完成！")
        else:
            print(f"⚠️  遷移完成，但有 {error} 個錯誤")
        print("=" * 60)
        
        connection.close()
        
    except pymysql.Error as e:
        print(f"❌ 資料庫連接失敗: {e}")
        print("\n請檢查：")
        print("1. MySQL 服務是否運行")
        print("2. 資料庫配置是否正確")
        print("3. 用戶權限是否足夠")
        sys.exit(1)
    except KeyboardInterrupt:
        print("\n\n❌ 用戶中斷執行")
        sys.exit(1)
    except Exception as e:
        print(f"❌ 發生錯誤: {e}")
        import traceback
        traceback.print_exc()
        sys.exit(1)

if __name__ == '__main__':
    main()

