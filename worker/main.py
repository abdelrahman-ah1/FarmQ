"""FarmQ async geospatial worker — Sentinel-2 STAC pipeline."""

from __future__ import annotations

import json
import os
import sqlite3
import time
from typing import Any

try:
    import redis
except ImportError:
    redis = None  # type: ignore

try:
    import pymysql
except ImportError:
    pymysql = None  # type: ignore

from deficiency import deficiency_map
from sentinel_pipeline import ndvi_process, sentinel_fetch


REDIS_HOST = os.getenv("REDIS_HOST", "127.0.0.1")
REDIS_PORT = int(os.getenv("REDIS_PORT", "6379"))
QUEUE_KEY = "farmq:geospatial_jobs"

DB_HOST = os.getenv("DB_HOST", "127.0.0.1")
DB_PORT = int(os.getenv("DB_PORT", "3306"))
DB_NAME = os.getenv("DB_NAME", "farmq")
DB_USER = os.getenv("DB_USER", "farmq")
DB_PASS = os.getenv("DB_PASS", "farmq_secret")
SQLITE_PATH = os.getenv("SQLITE_PATH", "/app/database/farmq.sqlite")


def get_connection():
    if pymysql is not None:
        try:
            return pymysql.connect(
                host=DB_HOST,
                port=DB_PORT,
                user=DB_USER,
                password=DB_PASS,
                database=DB_NAME,
                charset="utf8mb4",
                cursorclass=pymysql.cursors.DictCursor,
            )
        except Exception:
            pass
    conn = sqlite3.connect(SQLITE_PATH)
    conn.row_factory = sqlite3.Row
    return conn


def _is_sqlite(conn) -> bool:
    return isinstance(conn, sqlite3.Connection)


def mark_processing(job_id: int) -> None:
    conn = get_connection()
    try:
        if _is_sqlite(conn):
            conn.execute(
                "UPDATE geospatial_jobs SET status = 'processing' WHERE id = ?",
                (job_id,),
            )
        else:
            with conn.cursor() as cur:
                cur.execute(
                    "UPDATE geospatial_jobs SET status = 'processing' WHERE id = %s",
                    (job_id,),
                )
        conn.commit()
    finally:
        conn.close()


def mark_completed(job_id: int, result: dict[str, Any]) -> None:
    conn = get_connection()
    try:
        payload = json.dumps(result)
        if _is_sqlite(conn):
            conn.execute(
                "UPDATE geospatial_jobs SET status = 'completed', result_json = ?, completed_at = CURRENT_TIMESTAMP WHERE id = ?",
                (payload, job_id),
            )
        else:
            with conn.cursor() as cur:
                cur.execute(
                    "UPDATE geospatial_jobs SET status = 'completed', result_json = %s, completed_at = NOW() WHERE id = %s",
                    (payload, job_id),
                )
        conn.commit()
    finally:
        conn.close()


def mark_failed(job_id: int, error: str) -> None:
    conn = get_connection()
    try:
        payload = json.dumps({"error": error})
        if _is_sqlite(conn):
            conn.execute(
                "UPDATE geospatial_jobs SET status = 'failed', result_json = ?, completed_at = CURRENT_TIMESTAMP WHERE id = ?",
                (payload, job_id),
            )
        else:
            with conn.cursor() as cur:
                cur.execute(
                    "UPDATE geospatial_jobs SET status = 'failed', result_json = %s, completed_at = NOW() WHERE id = %s",
                    (payload, job_id),
                )
        conn.commit()
    finally:
        conn.close()


def latest_completed_result(farm_id: int, job_type: str) -> dict[str, Any] | None:
    conn = get_connection()
    try:
        if _is_sqlite(conn):
            row = conn.execute(
                """SELECT result_json FROM geospatial_jobs
                   WHERE farm_id = ? AND job_type = ? AND status = 'completed'
                   ORDER BY completed_at DESC, id DESC LIMIT 1""",
                (farm_id, job_type),
            ).fetchone()
        else:
            with conn.cursor() as cur:
                cur.execute(
                    """SELECT result_json FROM geospatial_jobs
                       WHERE farm_id = %s AND job_type = %s AND status = 'completed'
                       ORDER BY completed_at DESC, id DESC LIMIT 1""",
                    (farm_id, job_type),
                )
                row = cur.fetchone()
        if not row:
            return None
        raw = row["result_json"] if isinstance(row, dict) else row[0]
        return json.loads(raw) if raw else None
    except Exception:
        return None
    finally:
        conn.close()


def process_job(payload: dict[str, Any]) -> dict[str, Any]:
    job_type = payload.get("job_type", "unknown")
    farm_id = int(payload.get("farm_id") or 0)

    if job_type == "sentinel_fetch":
        return sentinel_fetch(payload)
    if job_type == "ndvi_process":
        scene_result = latest_completed_result(farm_id, "sentinel_fetch")
        return ndvi_process(payload, scene_result)
    if job_type == "deficiency_map":
        ndvi_result = latest_completed_result(farm_id, "ndvi_process")
        return deficiency_map(payload, ndvi_result)

    return {"error": f"Unknown job type: {job_type}"}


def handle_payload(payload: dict[str, Any]) -> None:
    job_id = int(payload.get("job_id", 0))
    if job_id <= 0:
        print(json.dumps({"ok": False, "error": "missing job_id"}))
        return

    mark_processing(job_id)
    try:
        result = process_job(payload)
        if "error" in result:
            mark_failed(job_id, str(result["error"]))
            print(json.dumps({"ok": False, "job_id": job_id, "error": result["error"]}))
        else:
            mark_completed(job_id, result)
            print(json.dumps({"ok": True, "job_id": job_id, "job_type": payload.get("job_type")}))
    except Exception as exc:  # noqa: BLE001
        mark_failed(job_id, str(exc))
        print(json.dumps({"ok": False, "job_id": job_id, "error": str(exc)}))


def main() -> None:
    if redis is None:
        print("redis package not installed; worker idle")
        while True:
            time.sleep(60)
        return

    client = redis.Redis(host=REDIS_HOST, port=REDIS_PORT, decode_responses=True)
    print(f"FarmQ worker listening on {QUEUE_KEY} @ {REDIS_HOST}:{REDIS_PORT}")

    while True:
        item = client.blpop(QUEUE_KEY, timeout=5)
        if not item:
            continue

        _, raw = item
        try:
            payload = json.loads(raw)
            handle_payload(payload)
        except Exception as exc:  # noqa: BLE001
            print(json.dumps({"ok": False, "error": str(exc)}))


if __name__ == "__main__":
    main()
