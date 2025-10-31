#!/usr/bin/env python3
"""
查詢用戶BOB02315213的活動記錄
"""

import pymysql
import sys
from datetime import datetime, timedelta

# 數據庫連接配置
DB_CONFIG = {
    'host': 'localhost',
    'user': 'root',
    'password': '',
    'database': 'topics_good',
    'charset': 'utf8mb4'
}

def query_user_activity():
    """查詢用戶活動記錄"""
    target_user = 'BOB02315213'
    
    print(f"正在查詢用戶 {target_user} 的活動記錄...")
    print(f"查詢時間: {datetime.now().strftime('%Y-%m-%d %H:%M:%S')}")
    print("=" * 50)
    
    try:
        # 建立數據庫連接
        conn = pymysql.connect(**DB_CONFIG)
        print("✅ 數據庫連接成功")
        
        with conn.cursor() as cursor:
            # 1. 檢查用戶是否存在
            print("\n1. 檢查用戶基本信息:")
            # 先檢查user表結構
            cursor.execute("DESCRIBE user")
            user_columns = [row[0] for row in cursor.fetchall()]
            print(f"   user表欄位: {user_columns}")
            
            # 根據實際欄位查詢
            if 'user_id' in user_columns:
                cursor.execute("SELECT * FROM user WHERE username = %s OR user_id = %s", (target_user, target_user))
            else:
                cursor.execute("SELECT * FROM user WHERE username = %s", (target_user,))
            user_info = cursor.fetchone()
            
            if user_info:
                print("✅ 找到用戶記錄:")
                # 獲取欄位名稱
                cursor.execute("DESCRIBE user")
                columns = [row[0] for row in cursor.fetchall()]
                
                for i, value in enumerate(user_info):
                    if i < len(columns):
                        print(f"   {columns[i]}: {value}")
            else:
                print(f"❌ 未找到用戶 {target_user}")
            
            # 2. 檢查所有可用的表
            print("\n2. 檢查所有可用的表:")
            cursor.execute("SHOW TABLES")
            tables = [row[0] for row in cursor.fetchall()]
            print(f"   可用表: {tables}")
            
            # 檢查是否有user_activity表
            if 'user_activity' in tables:
                print("\n3. 檢查用戶活動記錄:")
                cursor.execute("SELECT * FROM user_activity WHERE username = %s", (target_user,))
                activity_records = cursor.fetchall()
                
                if activity_records:
                    print("✅ 找到活動記錄:")
                    # 獲取欄位名稱
                    cursor.execute("DESCRIBE user_activity")
                    columns = [row[0] for row in cursor.fetchall()]
                    
                    for record in activity_records:
                        for i, value in enumerate(record):
                            if i < len(columns):
                                print(f"   {columns[i]}: {value}")
                        print("   ---")
                else:
                    print("❌ 未找到活動記錄")
            else:
                print("❌ user_activity表不存在")
            
            # 3. 檢查聊天記錄
            print("\n3. 檢查聊天記錄:")
            cursor.execute("SELECT COUNT(*) FROM private_chat_history WHERE from_user = %s OR to_user = %s", 
                         (target_user, target_user))
            chat_count = cursor.fetchone()[0]
            
            print(f"聊天記錄總數: {chat_count}")
            
            if chat_count > 0:
                cursor.execute("SELECT * FROM private_chat_history WHERE from_user = %s OR to_user = %s ORDER BY timestamp DESC LIMIT 5", 
                             (target_user, target_user))
                recent_chats = cursor.fetchall()
                
                print("最近5條聊天記錄:")
                for chat in recent_chats:
                    print(f"   時間: {chat[4] if len(chat) > 4 else 'N/A'}")
                    print(f"   發送者: {chat[1] if len(chat) > 1 else 'N/A'}")
                    print(f"   接收者: {chat[2] if len(chat) > 2 else 'N/A'}")
                    print(f"   訊息: {str(chat[3])[:50] if len(chat) > 3 else 'N/A'}...")
                    print("   ---")
            
            # 4. 檢查昨天的活動
            print("\n4. 檢查昨天的活動:")
            yesterday = (datetime.now() - timedelta(days=1)).strftime('%Y-%m-%d')
            yesterday_start = f"{yesterday} 00:00:00"
            yesterday_end = f"{yesterday} 23:59:59"
            
            print(f"查詢日期範圍: {yesterday_start} 到 {yesterday_end}")
            
            # 統計昨天的聊天活動
            cursor.execute("SELECT COUNT(*) FROM private_chat_history WHERE (from_user = %s OR to_user = %s) AND timestamp BETWEEN %s AND %s", 
                         (target_user, target_user, yesterday_start, yesterday_end))
            yesterday_chat_count = cursor.fetchone()[0]
            
            print(f"昨天聊天訊息數量: {yesterday_chat_count}")
            
            # 統計昨天的已讀活動
            cursor.execute("SELECT COUNT(*) FROM message_read_status WHERE reader_username = %s AND read_at BETWEEN %s AND %s", 
                         (target_user, yesterday_start, yesterday_end))
            yesterday_read_count = cursor.fetchone()[0]
            
            print(f"昨天已讀訊息數量: {yesterday_read_count}")
            
            # 5. 檢查所有相關表
            print("\n5. 檢查所有相關表:")
            tables = ['user', 'user_activity', 'private_chat_history', 'message_read_status', 'unread_notifications', 'notification_sent_log']
            
            for table in tables:
                try:
                    cursor.execute(f"SELECT COUNT(*) FROM {table}")
                    count = cursor.fetchone()[0]
                    print(f"   {table}: {count} 條記錄")
                except Exception as e:
                    print(f"   {table}: 表不存在或查詢失敗 - {e}")
            
            # 6. 檢查是否有其他可能的用戶ID格式
            print("\n6. 檢查其他可能的用戶ID格式:")
            possible_ids = [target_user, target_user.lower(), target_user.upper()]
            
            for user_id in possible_ids:
                cursor.execute("SELECT COUNT(*) FROM user WHERE username = %s OR user_id = %s", (user_id, user_id))
                count = cursor.fetchone()[0]
                if count > 0:
                    print(f"   找到用戶 {user_id}: {count} 條記錄")
                    
                    # 獲取詳細信息
                    cursor.execute("SELECT username, email, role, created_at FROM user WHERE username = %s OR user_id = %s", (user_id, user_id))
                    user_details = cursor.fetchone()
                    if user_details:
                        print(f"     用戶名: {user_details[0]}")
                        print(f"     郵箱: {user_details[1]}")
                        print(f"     角色: {user_details[2]}")
                        print(f"     創建時間: {user_details[3]}")
        
        conn.close()
        print("\n" + "=" * 50)
        print("查詢完成")
        
    except Exception as e:
        print(f"❌ 發生錯誤: {e}")
        return False
    
    return True

if __name__ == "__main__":
    query_user_activity()
