#!/usr/bin/env python3
"""
老師活動通知數據遷移腳本
將現有數據遷移到正規化表
"""

import pymysql
import sys
from datetime import datetime

# 資料庫連接配置
DB_CONFIG = {
    'host': 'localhost',
    'user': 'root',
    'password': '',
    'database': 'topics_good',
    'charset': 'utf8mb4'
}

def connect_db():
    """連接資料庫"""
    try:
        conn = pymysql.connect(**DB_CONFIG)
        return conn
    except Exception as e:
        print(f"❌ 資料庫連接失敗: {e}")
        sys.exit(1)

def check_tables_exist(cursor):
    """檢查表是否存在"""
    tables = ['teacher_activity_notifications', 'teacher_activity_recipients']
    missing_tables = []
    
    for table in tables:
        cursor.execute(f"SHOW TABLES LIKE '{table}'")
        if cursor.rowcount == 0:
            missing_tables.append(table)
    
    if missing_tables:
        print(f"⚠️  以下表不存在: {', '.join(missing_tables)}")
        return False
    return True

def migrate_data(cursor, conn):
    """遷移數據到正規化表"""
    print("📋 開始遷移數據...")
    
    # 1. 遷移聯絡人數據
    print("\n1. 遷移聯絡人數據...")
    cursor.execute("""
        INSERT INTO schools_contacts (email, name, is_active, created_at)
        SELECT DISTINCT 
            email,
            CONCAT('聯絡人-', SUBSTRING_INDEX(email, '@', 1)) as name,
            1 as is_active,
            MIN(created_at) as created_at
        FROM teacher_activity_recipients
        WHERE email IS NOT NULL 
          AND email != ''
          AND NOT EXISTS (
              SELECT 1 FROM schools_contacts WHERE schools_contacts.email = teacher_activity_recipients.email
          )
        GROUP BY email
    """)
    contact_count = cursor.rowcount
    print(f"   ✅ 創建了 {contact_count} 個聯絡人記錄")
    
    # 2. 遷移通知數據（需要對應到user表）
    print("\n2. 遷移通知數據...")
    cursor.execute("""
        INSERT INTO teacher_activity_notifications_normalized (
            id, user_id, teacher_email, subject, content, event_date, link, created_at
        )
        SELECT 
            tan.id,
            u.id as user_id,
            tan.teacher_email,
            tan.subject,
            tan.content,
            tan.event_date,
            tan.link,
            tan.created_at
        FROM teacher_activity_notifications tan
        LEFT JOIN user u ON u.email = tan.teacher_email
        WHERE NOT EXISTS (
            SELECT 1 FROM teacher_activity_notifications_normalized 
            WHERE teacher_activity_notifications_normalized.id = tan.id
        )
    """)
    notification_count = cursor.rowcount
    print(f"   ✅ 遷移了 {notification_count} 筆通知記錄")
    
    # 檢查是否有找不到對應user的通知
    cursor.execute("""
        SELECT COUNT(*) as count
        FROM teacher_activity_notifications_normalized
        WHERE user_id IS NULL
    """)
    unmapped_count = cursor.fetchone()[0]
    if unmapped_count > 0:
        print(f"   ⚠️  有 {unmapped_count} 筆通知找不到對應的用戶，需要手動處理")
    
    # 3. 遷移收件人數據
    print("\n3. 遷移收件人數據...")
    cursor.execute("""
        INSERT INTO teacher_activity_recipients_normalized (
            id, notification_id, contact_id, status, sent_at, error_message, created_at
        )
        SELECT 
            tar.id,
            tar.notification_id,
            sc.id as contact_id,
            tar.status,
            tar.sent_at,
            tar.error_message,
            tar.created_at
        FROM teacher_activity_recipients tar
        LEFT JOIN schools_contacts sc ON sc.email = tar.email
        WHERE NOT EXISTS (
            SELECT 1 FROM teacher_activity_recipients_normalized 
            WHERE teacher_activity_recipients_normalized.id = tar.id
        )
    """)
    recipient_count = cursor.rowcount
    print(f"   ✅ 遷移了 {recipient_count} 筆收件人記錄")
    
    # 檢查是否有找不到對應聯絡人的收件人
    cursor.execute("""
        SELECT COUNT(*) as count
        FROM teacher_activity_recipients_normalized
        WHERE contact_id IS NULL
    """)
    unmapped_recipient_count = cursor.fetchone()[0]
    if unmapped_recipient_count > 0:
        print(f"   ⚠️  有 {unmapped_recipient_count} 筆收件人找不到對應的聯絡人")
    
    conn.commit()
    print("\n✅ 數據遷移完成！")

def main():
    """主函數"""
    print("=" * 60)
    print("老師活動通知系統數據遷移腳本")
    print("=" * 60)
    
    conn = connect_db()
    cursor = conn.cursor()
    
    try:
        # 檢查表是否存在
        if not check_tables_exist(cursor):
            print("❌ 請先執行 integrate_teacher_notification_tables.sql 創建表結構")
            return
        
        # 遷移數據
        migrate_data(cursor, conn)
        
        # 顯示統計信息
        print("\n" + "=" * 60)
        print("數據統計")
        print("=" * 60)
        
        cursor.execute("SELECT COUNT(*) FROM schools_contacts")
        contact_count = cursor.fetchone()[0]
        
        cursor.execute("SELECT COUNT(*) FROM teacher_activity_notifications_normalized")
        notification_count = cursor.fetchone()[0]
        
        cursor.execute("SELECT COUNT(*) FROM teacher_activity_recipients_normalized")
        recipient_count = cursor.fetchone()[0]
        
        print(f"聯絡人數量: {contact_count}")
        print(f"通知數量: {notification_count}")
        print(f"收件人記錄數量: {recipient_count}")
        
    except Exception as e:
        conn.rollback()
        print(f"❌ 遷移失敗: {e}")
        import traceback
        traceback.print_exc()
    finally:
        cursor.close()
        conn.close()

if __name__ == '__main__':
    main()

