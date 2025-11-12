#!/usr/bin/env python3
# -*- coding: utf-8 -*-
import re

with open('frontend/index.php', 'rb') as f:
    content = f.read()

# 解碼為 UTF-8，忽略錯誤
try:
    text = content.decode('utf-8')
except:
    text = content.decode('utf-8', errors='ignore')

# 修復第 16 行：移除多餘的字符
text = re.sub(r"if \(\$_GET\['role'\] === '管理員'\) \{.*?\} \{", "if (\$_GET['role'] === '管理員') {", text)

# 修復第 18 行：替換老師
text = re.sub(r"elseif \(\$_GET\['role'\] === '[^']*師'\) \{", "} elseif (\$_GET['role'] === '老師') {", text)

# 修復第 20 行：替換學生
text = re.sub(r"elseif \(\$_GET\['role'\] === '[^']*學[^']*'\) \{", "} elseif (\$_GET['role'] === '學生') {", text)

# 修復縮進問題（第 16 行缺少縮進）
lines = text.split('\n')
if len(lines) > 15 and not lines[15].startswith('        '):
    lines[15] = '        ' + lines[15].lstrip()

text = '\n'.join(lines)

# 寫回文件
with open('frontend/index.php', 'wb') as f:
    f.write(text.encode('utf-8'))

print("Fixed index.php")

