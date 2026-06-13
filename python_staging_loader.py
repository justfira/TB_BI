"""Python loader: Excel/XLSX/CSV -> staging_workorder.data_json (batched) -> CALL sp_etl_workorder.

Tujuan: menggantikan transform PHP-per-row dengan pola:
  1) bulk insert ke staging_workorder
  2) stored procedure sp_etl_workorder yang transform dim/fact di MySQL

Catatan penting:
- staging_workorder tidak punya log_id, jadi loader ini melakukan TRUNCATE staging_workorder sebelum insert.
- staging_workorder status diisi 'pending'. Stored procedure akan mengubah status menjadi 'processed'.

Pemakaian contoh:
  python python_staging_loader.py --file "path.xlsx" --log-id 16

"""

from __future__ import annotations

import argparse
import json
import os
from typing import Any, Dict, Iterable, List

import mysql.connector


def _load_env_file(env_path: str) -> None:
    """Load simple KEY=VALUE pairs from a .env file into os.environ.

    This is intentionally minimal (enough for DB_* fields) and ignores comments.
    """
    if not os.path.exists(env_path):
        return

    with open(env_path, "r", encoding="utf-8") as f:
        for line in f:
            line = line.strip()
            if not line or line.startswith("#"):
                continue
            if "=" not in line:
                continue
            k, v = line.split("=", 1)
            k = k.strip()
            v = v.strip().strip('"').strip("'")
            if k and k not in os.environ:
                os.environ[k] = v


def load_rows_excel_or_csv(path: str) -> List[Dict[str, Any]]:

    ext = os.path.splitext(path)[1].lower()

    if ext in {".csv"}:
        import csv

        with open(path, "r", encoding="utf-8-sig", newline="") as f:
            reader = csv.DictReader(f)
            return [dict(r) for r in reader]

    if ext in {".xlsx", ".xls"}:
        import pandas as pd

        # engine openpyxl untuk xlsx, xlrd untuk xls (tergantung instalasi)
        df = pd.read_excel(path, dtype=str)
        df = df.fillna("")
        return df.to_dict(orient="records")

    raise ValueError(f"Unsupported file extension: {ext}")


def batch_iter(items: List[Dict[str, Any]], batch_size: int) -> Iterable[List[Dict[str, Any]]]:
    for i in range(0, len(items), batch_size):
        yield items[i : i + batch_size]


def main() -> None:
    # Load Laravel .env so Python can see DB_* values
    _load_env_file(os.path.join(os.path.dirname(__file__), '.env'))

    ap = argparse.ArgumentParser()

    ap.add_argument("--file", required=True, help="Path ke file Excel/CSV")
    ap.add_argument("--log-id", required=True, type=int, help="etl_logs.id (untuk CALL sp_etl_workorder)")
    ap.add_argument("--db-host", default=os.getenv("DB_HOST", "127.0.0.1"))
    ap.add_argument("--db-port", default=int(os.getenv("DB_PORT", "3306")))
    ap.add_argument("--db-name", default=os.getenv("DB_DATABASE", ""))
    ap.add_argument("--db-user", default=os.getenv("DB_USERNAME", ""))
    ap.add_argument("--db-pass", default=os.getenv("DB_PASSWORD", ""))
    ap.add_argument("--batch-size", default=2000, type=int)
    ap.add_argument("--max-allowed-packet-bytes", default=0, type=int, help="Opsional: kalau max_allowed_packet kecil, pakai batch-size lebih kecil.")


    args = ap.parse_args()

    if not args.db_name or not args.db_user:
        raise SystemExit("Missing DB config. Set DB_DATABASE/DB_USERNAME/DB_PASSWORD env vars or pass args.")

    rows = load_rows_excel_or_csv(args.file)

    cnx = mysql.connector.connect(
        host=args.db_host,
        port=args.db_port,
        user=args.db_user,
        password=args.db_pass,
        database=args.db_name,
    )
    cnx.autocommit = False

    cur = cnx.cursor()

    try:
        # Karena staging global, bersihkan dulu agar staging tidak tercampur antar import.
        cur.execute("TRUNCATE TABLE staging_workorder")
        cnx.commit()

        total = len(rows)
        inserted = 0

        # Kalau max_allowed_packet kecil, batch besar bisa gagal.
        # Turunkan batch-size biar packet JSON tidak melewati batas.
        for chunk in batch_iter(rows, args.batch_size):
            values = []

            for r in chunk:
                # data_json harus JSON string
                values.append((json.dumps(r, ensure_ascii=False), "pending", None, None))

            # staging_workorder: id (auto), data_json, status, errors, row_number, timestamps
            # timestamps pakai default; jika tabel tidak punya default, modifikasi query.
            cur.executemany(
                """
                INSERT INTO staging_workorder (data_json, status, errors, row_number, created_at, updated_at)
                VALUES (%s, %s, %s, %s, NOW(), NOW())
                """,
                values,
            )
            inserted += len(chunk)
            cnx.commit()

        # Jalankan transformasi set-based di MySQL
        cur.execute("CALL sp_etl_workorder(%s)", (args.log_id,))
        cnx.commit()

    except Exception:
        cnx.rollback()
        raise
    finally:
        try:
            cur.close()
        finally:
            cnx.close()


if __name__ == "__main__":
    main()

