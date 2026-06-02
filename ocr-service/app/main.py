from __future__ import annotations

import re
import tempfile
from pathlib import Path
from typing import Any

import cv2
import numpy as np
from fastapi import FastAPI, File, UploadFile
from paddleocr import PaddleOCR

app = FastAPI(title="AZYK Odometer OCR")

ocr_model: PaddleOCR | None = None


def get_model() -> PaddleOCR:
    global ocr_model
    if ocr_model is None:
        ocr_model = PaddleOCR(use_angle_cls=True, lang="en", show_log=False)
    return ocr_model


@app.on_event("startup")
def warm_up_model() -> None:
    get_model()


def preprocess(image_path: str) -> str:
    image = cv2.imread(image_path)
    if image is None:
        return image_path

    height, width = image.shape[:2]
    scale = 1.0
    if max(height, width) < 1400:
        scale = 1400 / max(height, width)
    if scale > 1:
        image = cv2.resize(image, None, fx=scale, fy=scale, interpolation=cv2.INTER_CUBIC)

    gray = cv2.cvtColor(image, cv2.COLOR_BGR2GRAY)
    clahe = cv2.createCLAHE(clipLimit=2.0, tileGridSize=(8, 8))
    gray = clahe.apply(gray)

    processed_path = f"{image_path}.processed.jpg"
    cv2.imwrite(processed_path, gray)
    return processed_path


def flatten_result(result: Any) -> list[tuple[str, float | None]]:
    lines: list[tuple[str, float | None]] = []

    def walk(node: Any) -> None:
        if isinstance(node, tuple) and len(node) >= 2 and isinstance(node[0], str):
            confidence = node[1] if isinstance(node[1], (int, float)) else None
            lines.append((node[0], float(confidence) if confidence is not None else None))
            return

        if isinstance(node, list):
            if len(node) >= 2 and isinstance(node[1], tuple) and len(node[1]) >= 2:
                text = node[1][0]
                confidence = node[1][1]
                if isinstance(text, str):
                    lines.append((text, float(confidence) if isinstance(confidence, (int, float)) else None))
                    return
            for item in node:
                walk(item)

    walk(result)
    return lines


def extract_candidates(lines: list[tuple[str, float | None]]) -> list[dict[str, Any]]:
    candidates: dict[int, float | None] = {}

    for text, confidence in lines:
        for match in re.findall(r"\d[\d\s.,]{2,}", text):
            normalized = re.sub(r"\D", "", match)
            if len(normalized) < 3:
                continue

            value = int(normalized)
            if value < 1_000 or value > 9_999_999:
                continue

            current = candidates.get(value)
            if current is None or (confidence is not None and confidence > current):
                candidates[value] = confidence

    result = [
        {"value": value, "confidence": confidence}
        for value, confidence in candidates.items()
    ]

    return sorted(
        result,
        key=lambda item: (
            item["confidence"] if item["confidence"] is not None else 0,
            len(str(item["value"])),
            item["value"],
        ),
        reverse=True,
    )


@app.get("/health")
def health() -> dict[str, bool]:
    return {"ok": True}


@app.post("/recognize")
async def recognize(image: UploadFile = File(...)) -> dict[str, Any]:
    suffix = Path(image.filename or "odometer.jpg").suffix or ".jpg"

    with tempfile.NamedTemporaryFile(delete=False, suffix=suffix) as tmp:
        tmp.write(await image.read())
        original_path = tmp.name

    processed_path = original_path

    try:
        processed_path = preprocess(original_path)
        result = get_model().ocr(processed_path, cls=True)
        lines = flatten_result(result)
        candidates = extract_candidates(lines)
        suggested = candidates[0] if candidates else None

        if suggested is None:
            return {
                "success": False,
                "raw_text": [text for text, _ in lines],
                "candidates": [],
                "suggested_value": None,
                "confidence": None,
                "error": "Не удалось распознать показания одометра",
            }

        return {
            "success": True,
            "raw_text": [text for text, _ in lines],
            "candidates": candidates,
            "suggested_value": suggested["value"],
            "confidence": suggested["confidence"],
        }
    except Exception as exc:
        return {
            "success": False,
            "raw_text": [],
            "candidates": [],
            "suggested_value": None,
            "confidence": None,
            "error": str(exc) or "Не удалось распознать показания одометра",
        }
    finally:
        Path(original_path).unlink(missing_ok=True)
        if processed_path != original_path:
            Path(processed_path).unlink(missing_ok=True)
