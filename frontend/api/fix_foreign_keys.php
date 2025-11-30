<?php
/**
 * 修复 enrollment_intention 表的外键约束
 * 问题：错误的外键约束设置在显示字段上，应该设置在关联代码字段上
 */

// 资料库连接
$host = 'localhost';
$dbname = 'topics_good';
$db_username = 'root';
$db_password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $db_username, $db_password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "开始修复外键约束...\n\n";
    
    // 1. 删除错误的外键约束
    echo "1. 删除错误的外键约束...\n";
    
    try {
        $pdo->exec("ALTER TABLE `enrollment_intention` DROP FOREIGN KEY `enrollment_intention_ibfk_4`");
        echo "   ✓ 已删除 enrollment_intention_ibfk_4 (junior_high)\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), "Unknown key") === false) {
            echo "   ⚠ 删除 enrollment_intention_ibfk_4 时出错: " . $e->getMessage() . "\n";
        } else {
            echo "   ℹ enrollment_intention_ibfk_4 不存在，跳过\n";
        }
    }
    
    try {
        $pdo->exec("ALTER TABLE `enrollment_intention` DROP FOREIGN KEY `enrollment_intention_ibfk_5`");
        echo "   ✓ 已删除 enrollment_intention_ibfk_5 (current_grade)\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), "Unknown key") === false) {
            echo "   ⚠ 删除 enrollment_intention_ibfk_5 时出错: " . $e->getMessage() . "\n";
        } else {
            echo "   ℹ enrollment_intention_ibfk_5 不存在，跳过\n";
        }
    }
    
    // 2. 检查字段是否存在
    echo "\n2. 检查字段是否存在...\n";
    
    $stmt = $pdo->query("SHOW COLUMNS FROM enrollment_intention LIKE 'junior_high_school_code'");
    $has_junior_high_school_code = $stmt->rowCount() > 0;
    echo "   " . ($has_junior_high_school_code ? "✓" : "✗") . " junior_high_school_code 字段\n";
    
    $stmt = $pdo->query("SHOW COLUMNS FROM enrollment_intention LIKE 'current_grade_code'");
    $has_current_grade_code = $stmt->rowCount() > 0;
    echo "   " . ($has_current_grade_code ? "✓" : "✗") . " current_grade_code 字段\n";
    
    // 3. 添加正确的外键约束
    echo "\n3. 添加正确的外键约束...\n";
    
    if ($has_junior_high_school_code) {
        try {
            // 先检查是否已存在
            $stmt = $pdo->query("SELECT CONSTRAINT_NAME 
                                 FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
                                 WHERE TABLE_SCHEMA = 'topics_good' 
                                 AND TABLE_NAME = 'enrollment_intention' 
                                 AND COLUMN_NAME = 'junior_high_school_code' 
                                 AND REFERENCED_TABLE_NAME IS NOT NULL");
            if ($stmt->rowCount() == 0) {
                $pdo->exec("ALTER TABLE `enrollment_intention` 
                           ADD CONSTRAINT `enrollment_intention_ibfk_4` 
                           FOREIGN KEY (`junior_high_school_code`) 
                           REFERENCES `school_data` (`school_code`) 
                           ON DELETE SET NULL 
                           ON UPDATE CASCADE");
                echo "   ✓ 已添加 junior_high_school_code 的外键约束\n";
            } else {
                echo "   ℹ junior_high_school_code 的外键约束已存在\n";
            }
        } catch (PDOException $e) {
            echo "   ⚠ 添加 junior_high_school_code 外键约束时出错: " . $e->getMessage() . "\n";
        }
    }
    
    if ($has_current_grade_code) {
        try {
            // 先检查是否已存在
            $stmt = $pdo->query("SELECT CONSTRAINT_NAME 
                                 FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
                                 WHERE TABLE_SCHEMA = 'topics_good' 
                                 AND TABLE_NAME = 'enrollment_intention' 
                                 AND COLUMN_NAME = 'current_grade_code' 
                                 AND REFERENCED_TABLE_NAME IS NOT NULL");
            if ($stmt->rowCount() == 0) {
                $pdo->exec("ALTER TABLE `enrollment_intention` 
                           ADD CONSTRAINT `enrollment_intention_ibfk_5` 
                           FOREIGN KEY (`current_grade_code`) 
                           REFERENCES `identity_options` (`code`) 
                           ON DELETE SET NULL 
                           ON UPDATE CASCADE");
                echo "   ✓ 已添加 current_grade_code 的外键约束\n";
            } else {
                echo "   ℹ current_grade_code 的外键约束已存在\n";
            }
        } catch (PDOException $e) {
            echo "   ⚠ 添加 current_grade_code 外键约束时出错: " . $e->getMessage() . "\n";
        }
    }
    
    // 4. 显示当前的外键约束
    echo "\n4. 当前的外键约束:\n";
    $stmt = $pdo->query("SELECT 
        CONSTRAINT_NAME,
        COLUMN_NAME,
        REFERENCED_TABLE_NAME,
        REFERENCED_COLUMN_NAME
    FROM 
        INFORMATION_SCHEMA.KEY_COLUMN_USAGE
    WHERE 
        TABLE_SCHEMA = 'topics_good'
        AND TABLE_NAME = 'enrollment_intention'
        AND REFERENCED_TABLE_NAME IS NOT NULL
    ORDER BY CONSTRAINT_NAME");
    
    $constraints = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (empty($constraints)) {
        echo "   无外键约束\n";
    } else {
        foreach ($constraints as $constraint) {
            echo "   - {$constraint['CONSTRAINT_NAME']}: {$constraint['COLUMN_NAME']} -> {$constraint['REFERENCED_TABLE_NAME']}.{$constraint['REFERENCED_COLUMN_NAME']}\n";
        }
    }
    
    echo "\n✓ 修复完成！\n";
    
} catch (PDOException $e) {
    echo "错误: " . $e->getMessage() . "\n";
    exit(1);
}

