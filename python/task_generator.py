import datetime
import re
import sys
import os
import mysql.connector
from openai import OpenAI

# === Logging Setup ===
log_path = os.path.join(os.path.dirname(__file__), "task_error.log")
def log(msg):
    with open(log_path, "a", encoding="utf-8") as f:
        f.write(f"[{datetime.datetime.now()}] {msg}\n")

log("🔁 Script started")

# === Config ===
DB_HOST = "localhost"
DB_NAME = "smart_tasks"
DB_USER = "root"
DB_PASS = ""  # Set if needed

API_KEY = "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"
BASE_URL = "https://openrouter.ai/api/v1"

# === Validate Arguments ===
if len(sys.argv) != 6:
    log("❌ Incorrect argument count")
    sys.exit(1)

task_id = int(sys.argv[1])
title = sys.argv[2]
description = sys.argv[3]
start_time = sys.argv[4]
end_time = sys.argv[5]

try:
    start_dt = datetime.datetime.strptime(start_time, "%Y-%m-%dT%H:%M")
    end_dt = datetime.datetime.strptime(end_time, "%Y-%m-%dT%H:%M")
except ValueError as e:
    log(f"❌ Date parse error: {str(e)}")
    sys.exit(1)

log(f"📋 Task ID: {task_id} | Title: {title}")

# === AI Setup ===
client = OpenAI(api_key=API_KEY, base_url=BASE_URL)
prompt = f"""
You are a smart assistant.

Break down this task:
Title: {title}
Description: {description}
Start: {start_dt.strftime('%m/%d/%Y %I:%M %p')}
End: {end_dt.strftime('%m/%d/%Y %I:%M %p')}

Output lines like:
MM/DD/YYYY HH:MM AM/PM to MM/DD/YYYY HH:MM AM/PM: Subtask

Only return this format.
"""

# === AI Call ===
try:
    response = client.chat.completions.create(
        model="mistralai/mixtral-8x7b-instruct",
        messages=[{"role": "user", "content": prompt}],
        max_tokens=800,
        temperature=0.7,
    )
    output = response.choices[0].message.content.strip()
    log("✅ AI response received")
except Exception as e:
    log(f"❌ AI error: {str(e)}")
    sys.exit(1)

# === Save AI Output ===
try:
    output_file = os.path.join(os.path.dirname(__file__), "ai_output.txt")
    with open(output_file, "w", encoding="utf-8") as f:
        for line in output.split("\n"):
            f.write(line.strip() + "\n")
    log("📄 ai_output.txt written")
except Exception as e:
    log(f"❌ Failed to write ai_output.txt: {str(e)}")
    sys.exit(1)

# === Insert to DB ===
try:
    conn = mysql.connector.connect(
        host=DB_HOST, user=DB_USER, password=DB_PASS, database=DB_NAME
    )
    cursor = conn.cursor()

    raw_sql = "INSERT INTO task_phases (task_id, phase_text) VALUES (%s, %s)"
    parsed_sql = "INSERT INTO task_phases_detailed (task_id, start_time, end_time, description) VALUES (%s, %s, %s, %s)"

    for line in output.split("\n"):
        cursor.execute(raw_sql, (task_id, line.strip()))
        match = re.match(r"(\d{2}/\d{2}/\d{4} \d{2}:\d{2} [APMapm]{2}) to (\d{2}/\d{2}/\d{4} \d{2}:\d{2} [APMapm]{2}): (.+)", line)
        if match:
            try:
                s, e, desc = match.groups()
                s_dt = datetime.datetime.strptime(s, "%m/%d/%Y %I:%M %p")
                e_dt = datetime.datetime.strptime(e, "%m/%d/%Y %I:%M %p")
                cursor.execute(parsed_sql, (task_id, s_dt, e_dt, desc.strip()))
            except Exception as sub_e:
                log(f"⚠️ Parse insert fail: {sub_e}")

    conn.commit()
    cursor.close()
    conn.close()
    log("✅ Data inserted to DB")
except mysql.connector.Error as db_err:
    log(f"❌ DB Error: {str(db_err)}")
    sys.exit(1)

# === Done Signal ===
try:
    with open(os.path.join(os.path.dirname(__file__), "task_done.txt"), "w") as f:
        f.write("done")
    log("✅ task_done.txt created")
except Exception as e:
    log(f"❌ Could not write task_done.txt: {str(e)}")
