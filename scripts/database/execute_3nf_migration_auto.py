#!/usr/bin/env python3
"""
自動執行第三正規化（3NF）遷移腳本（無需確認）
"""

import pymysql
import sys
import os
import re

# 資料庫配置
DB_CONFIG = {
    'host': 'localhost',
    'user': 'root',
    'password': '',
    'database': 'topics_good',
    'charset': 'utf8mb4'
}

def split_sql_statements(sql_content):
    """正確分割 SQL 語句"""
    # 移除註解
    sql_content = re.sub(r'--.*?$', '', sql_content, flags=re.MULTILINE)
    sql_content = re.sub(r'/\*.*?\*/', '', sql_content, flags=re.DOTALL)
    
    statements = []
    current = ""
    in_string = False
    string_char = None
    i = 0
    
    while i < len(sql_content):
        char = sql_content[i]
        
        # 處理字串
        if char in ("'", '"') and (i == 0 or sql_content[i-1] != '\\'):
            if not in_string:
                in_string = True
                string_char = char
            elif char == string_char:
                in_string = False
                string_char = None
            current += char
        # 處理分號（語句結束）
        elif char == ';' and not in_string:
            current += char
            stmt = current.strip()
            if stmt and len(stmt) > 1:  # 忽略空語句
                statements.append(stmt)
            current = ""
        else:
            current += char
        
        i += 1
    
    # 處理最後一個語句（如果沒有以分號結尾）
    if current.strip():
        statements.append(current.strip())
    
    return statements

def execute_sql_file(connection, file_path):
    """執行 SQL 檔案"""
    try:
        with open(file_path, 'r', encoding='utf-8') as f:
            sql_content = f.read()
        
        statements = split_sql_statements(sql_content)
        
        cursor = connection.cursor()
        
        print(f"📝 開始執行 {file_path}")
        print(f"   共 {len(statements)} 個 SQL 語句\n")
        
        success_count = 0
        error_count = 0
        skipped_count = 0
        
        for i, sql in enumerate(statements, 1):
            sql = sql.strip()
            if not sql or sql == ';' or sql.startswith('USE '):
                if sql.startswith('USE '):
                    skipped_count += 1
                continue
            
            try:
                # 執行 SQL
                cursor.execute(sql)
                
                # 需要 commit 的語句
                if any(keyword in sql.upper() for keyword in ['CREATE', 'INSERT', 'UPDATE', 'DELETE', 'DROP', 'ALTER']):
                    connection.commit()
                    success_count += 1
                    
                    # 顯示進度
                    if 'CREATE TABLE' in sql.upper():
                        # 提取表名
                        match = re.search(r'CREATE\s+TABLE\s+(?:IF\s+NOT\s+EXISTS\s+)?([`"]?(\w+)[`"]?)', sql, re.IGNORECASE)
                        table_name = match.group(2) if match else "未知表"
                        print(f"   ✅ [{i}/{len(statements)}] 創建表: {table_name}")
                    elif 'CREATE VIEW' in sql.upper() or 'CREATE OR REPLACE VIEW' in sql.upper():
                        match = re.search(r'CREATE\s+(?:OR\s+REPLACE\s+)?VIEW\s+([`"]?(\w+)[`"]?)', sql, re.IGNORECASE)
                        view_name = match.group(2) if match else "未知視圖"
                        print(f"   ✅ [{i}/{len(statements)}] 創建視圖: {view_name}")
                    elif 'INSERT' in sql.upper():
                        affected = cursor.rowcount
                        if affected > 0:
                            print(f"   ✅ [{i}/{len(statements)}] 插入 {affected} 筆資料")
                        else:
                            print(f"   ✅ [{i}/{len(statements)}] 插入操作完成")
                    elif 'DROP' in sql.upper():
                        print(f"   ✅ [{i}/{len(statements)}] 刪除操作完成")
                elif sql.upper().startswith('SELECT'):
                    results = cursor.fetchall()
                    success_count += 1
                    if results:
                        print(f"   ✅ [{i}/{len(statements)}] 查詢完成，返回 {len(results)} 筆結果")
                        if len(results) <= 3:
                            for row in results:
                                print(f"      {row}")
                    else:
                        print(f"   ✅ [{i}/{len(statements)}] 查詢完成（無結果）")
                else:
                    connection.commit()
                    success_count += 1
                    
            except pymysql.Error as e:
                error_msg = str(e)
                error_code = e.args[0] if e.args else 0
                
                # 跳過一些可以忽略的錯誤
                if error_code == 1050 or 'already exists' in error_msg.lower():
                    skipped_count += 1
                    print(f"   ⚠️  [{i}/{len(statements)}] 已存在（跳過）")
                    connection.rollback()
                elif error_code == 1062 or 'duplicate' in error_msg.lower():
                    skipped_count += 1
                    connection.rollback()
                    print(f"   ⚠️  [{i}/{len(statements)}] 重複資料（跳過）")
                elif error_code == 1051 or 'doesn\'t exist' in error_msg.lower() or "doesn't exist" in error_msg.lower():
                    skipped_count += 1
                    connection.rollback()
                    print(f"   ⚠️  [{i}/{len(statements)}] 不存在（跳過）")
                elif error_code == 1146:  # Table doesn't exist - 這可能是因為舊表不存在
                    skipped_count += 1
                    connection.rollback()
                    print(f"   ⚠️  [{i}/{len(statements)}] 表不存在（跳過）")
                else:
                    error_count += 1
                    print(f"   ❌ [{i}/{len(statements)}] 錯誤 [{error_code}]: {error_msg}")
                    # 只顯示 SQL 的前100個字符
                    sql_preview = sql[:100].replace('\n', ' ')
                    if len(sql) > 100:
                        sql_preview += "..."
                    print(f"      SQL: {sql_preview}")
                    connection.rollback()
        
        print(f"\n📊 執行統計：")
        print(f"   ✅ 成功: {success_count}")
        print(f"   ⚠️  跳過: {skipped_count}")
        print(f"   ❌ 失敗: {error_count}")
        print(f"   📝 總計: {len(statements)}")
        
        cursor.close()
        return success_count, error_count, skipped_count
        
    except FileNotFoundError:
        print(f"❌ 檔案不存在: {file_path}")
        return 0, 1, 0
    except Exception as e:
        print(f"❌ 執行失敗: {e}")
        import traceback
        traceback.print_exc()
        return 0, 1, 0

def main():
    """主函數"""
    print("=" * 60)
    print("資料庫第三正規化（3NF）遷移腳本 - 自動執行")
    print("=" * 60)
    print()
    
    # 獲取腳本目錄
    script_dir = os.path.dirname(os.path.abspath(__file__))
    migration_file = os.path.join(script_dir, 'normalize_to_3nf.sql')
    
    if not os.path.exists(migration_file):
        print(f"❌ 遷移檔案不存在: {migration_file}")
        sys.exit(1)
    
    # 連接資料庫
    try:
        print("🔌 正在連接資料庫...")
        connection = pymysql.connect(**DB_CONFIG)
        print(f"✅ 資料庫連接成功！")
        print(f"   主機: {DB_CONFIG['host']}")
        print(f"   資料庫: {DB_CONFIG['database']}")
        print()
        
        print("=" * 60)
        print("開始執行遷移...")
        print("=" * 60)
        print()
        
        # 執行遷移腳本
        success, error, skipped = execute_sql_file(connection, migration_file)
        
        print()
        print("=" * 60)
        if error == 0:
            print("✅ 遷移完成！")
            print(f"   成功: {success} 個操作")
            print(f"   跳過: {skipped} 個操作（已存在/不存在）")
        else:
            print(f"⚠️  遷移完成，但有 {error} 個錯誤")
            print(f"   成功: {success} 個操作")
            print(f"   跳過: {skipped} 個操作")
        print("=" * 60)
        
        # 驗證創建的表
        print("\n🔍 驗證創建的表結構...")
        cursor = connection.cursor()
        cursor.execute("SHOW TABLES")
        all_tables = [row[0] for row in cursor.fetchall()]
        normalized_tables = [t for t in all_tables if 'normalized' in t.lower() or t in ['departments', 'education_systems', 'application_statuses', 'identities', 'genders', 'grades', 'companies']]
        if normalized_tables:
            print("✅ 以下正規化表已創建：")
            for table in normalized_tables:
                print(f"   - {table}")
        else:
            print("⚠️  未找到正規化表，可能已經存在或創建失敗")
        
        cursor.close()
        connection.close()
        
        if error == 0:
            print("\n✨ 遷移腳本執行完成！")
            sys.exit(0)
        else:
            print("\n⚠️  遷移完成，但請檢查錯誤訊息")
            sys.exit(1)
        
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
