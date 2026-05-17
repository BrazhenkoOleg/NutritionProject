from pathlib import Path
from tempfile import NamedTemporaryFile
from collections import defaultdict

from fastapi import FastAPI, UploadFile, File
from fastapi.middleware.cors import CORSMiddleware
from ultralytics import YOLO


# ===== Пути проекта =====
PROJECT_ROOT = Path(__file__).resolve().parents[2]

RUNS_DIR = PROJECT_ROOT / "runs"
MODEL_PATH = RUNS_DIR / "nutrition_yolo11m_aug_v1" / "weights" / "best.pt"


# ===== FastAPI app =====
app = FastAPI(
    title="Nutrition YOLO API",
    description="API для распознавания продуктов питания на изображениях",
    version="1.0.0",
)

app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)


# ===== Загрузка модели =====
if not MODEL_PATH.exists():
    raise FileNotFoundError(f"Модель не найдена: {MODEL_PATH}")

model = YOLO(str(MODEL_PATH))


@app.get("/")
def root():
    return {
        "status": "ok",
        "message": "Nutrition YOLO API is working",
    }


@app.get("/health")
def health():
    return {
        "status": "ok",
        "model_path": str(MODEL_PATH),
    }


@app.post("/predict")
async def predict(image: UploadFile = File(...)):
    suffix = Path(image.filename or "").suffix

    with NamedTemporaryFile(delete=False, suffix=suffix) as temp_file:
        temp_file.write(await image.read())
        temp_image_path = Path(temp_file.name)

    try:
        results = model.predict(
            source=str(temp_image_path),
            conf=0.25,
            iou=0.45,
            imgsz=640,
            save=False,
            verbose=False,
        )

        result = results[0]

        detections = []
        grouped = defaultdict(lambda: {
            "class_name": None,
            "count": 0,
            "max_confidence": 0.0,
        })

        for box in result.boxes:
            cls_id = int(box.cls[0].item())
            confidence = float(box.conf[0].item())
            class_name = result.names[cls_id]

            xyxy = box.xyxy[0].tolist()

            detections.append({
                "class_name": class_name,
                "confidence": round(confidence, 4),
                "bbox": {
                    "x1": round(xyxy[0], 2),
                    "y1": round(xyxy[1], 2),
                    "x2": round(xyxy[2], 2),
                    "y2": round(xyxy[3], 2),
                },
            })

            grouped[class_name]["class_name"] = class_name
            grouped[class_name]["count"] += 1
            grouped[class_name]["max_confidence"] = max(
                grouped[class_name]["max_confidence"],
                confidence,
            )

        products = sorted(
            grouped.values(),
            key=lambda item: item["max_confidence"],
            reverse=True,
        )

        for item in products:
            item["max_confidence"] = round(item["max_confidence"], 4)

        return {
            "status": "ok",
            "detections_count": len(detections),
            "products_count": len(products),
            "detections": detections,
            "products": products,
        }

    finally:
        if temp_image_path.exists():
            temp_image_path.unlink()