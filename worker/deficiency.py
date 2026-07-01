"""Soil-informed deficiency zone GeoJSON."""

from __future__ import annotations

from typing import Any


def _severity(value: float | None, low: float, high: float) -> str | None:
    if value is None:
        return None
    if value < low:
        return "high"
    if value < high:
        return "moderate"
    return None


def deficiency_geojson(
    center: dict[str, float],
    soil: dict[str, Any] | None,
    ndvi: float | None,
) -> dict[str, Any]:
    lat, lng = center["lat"], center["lng"]
    d = 0.01
    soil = soil or {}
    features: list[dict[str, Any]] = []

    thresholds = {
        "n": (25.0, 45.0),
        "p": (15.0, 30.0),
        "k": (80.0, 120.0),
    }
    offsets = {
        "n": (d * 0.4, -d * 0.5),
        "p": (-d * 0.3, d * 0.2),
        "k": (d * 0.1, d * 0.6),
    }

    for element, (low, high) in thresholds.items():
        key = f"npk_{element}" if element != "n" else "npk_n"
        if element == "p":
            key = "npk_p"
        elif element == "k":
            key = "npk_k"
        else:
            key = "npk_n"
        severity = _severity(soil.get(key), low, high)
        if ndvi is not None and ndvi < 0.45 and element == "n" and severity is None:
            severity = "moderate"
        if severity is None:
            continue
        olat, olng = offsets[element]
        features.append(_zone(lat + olat, lng + olng, d * 0.55, element, severity))

    if not features:
        features.append(_zone(lat, lng, d * 0.4, "n", "low"))

    return {"type": "FeatureCollection", "features": features}


def _zone(clat: float, clng: float, size: float, element: str, severity: str) -> dict[str, Any]:
    s = size / 2
    return {
        "type": "Feature",
        "properties": {"element": element, "severity": severity},
        "geometry": {
            "type": "Polygon",
            "coordinates": [[
                [clng - s, clat - s],
                [clng + s, clat - s],
                [clng + s, clat + s],
                [clng - s, clat + s],
                [clng - s, clat - s],
            ]],
        },
    }


def deficiency_map(payload: dict[str, Any], ndvi_result: dict[str, Any] | None) -> dict[str, Any]:
    center = payload.get("center") or {"lat": 30.9, "lng": 31.1}
    soil = payload.get("soil") or {}
    ndvi = None
    if ndvi_result:
        ndvi = ndvi_result.get("ndvi_mean")
    return {
        "farm_id": payload.get("farm_id"),
        "center": center,
        "geojson": deficiency_geojson(center, soil, ndvi),
        "method": "soil_npk_thresholds_with_ndvi",
    }
