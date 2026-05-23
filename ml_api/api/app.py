from pathlib import Path
from collections import defaultdict
import asyncio
import gc
import json
import os
from dotenv import load_dotenv

import cv2
import numpy as np
import onnxruntime as ort
from fastapi import FastAPI, UploadFile, File, HTTPException
from fastapi.middleware.cors import CORSMiddleware
from huggingface_hub import hf_hub_download

from app.decision_support.nutrition_classifier import NutritionClassifier
from app.decision_support.schemas import WeeklyNutritionRequest, WeeklyNutritionResponse

load_dotenv()

ML_ROOT = Path(__file__).resolve().parents[1]

MODELS_DIR = ML_ROOT / "models"
PRODUCTS_PATH = ML_ROOT / "data" / "nutrition_products_ru_kbju_100g.json"

MODEL_FILE = os.getenv("HF_MODEL_FILE", "best_nutrivision.onnx")
LOCAL_MODEL_PATH = MODELS_DIR / MODEL_FILE

HF_MODEL_REPO = os.getenv("HF_MODEL_REPO")
HF_TOKEN = os.getenv("HF_TOKEN")

IMAGE_SIZE = int(os.getenv("IMAGE_SIZE", "768"))

CONF_THRESHOLD = float(os.getenv("CONF_THRESHOLD", "0.25"))
IOU_THRESHOLD = float(os.getenv("IOU_THRESHOLD", "0.45"))

MAX_FILE_SIZE_MB = int(os.getenv("MAX_FILE_SIZE_MB", "8"))
MAX_FILE_SIZE = MAX_FILE_SIZE_MB * 1024 * 1024


def load_class_names():
    if not PRODUCTS_PATH.exists():
        raise FileNotFoundError(f"Файл продуктов не найден: {PRODUCTS_PATH}")

    with open(PRODUCTS_PATH, "r", encoding="utf-8") as file:
        data = json.load(file)

    products = data.get("products")

    if not isinstance(products, list) or len(products) == 0:
        raise ValueError("Некорректный JSON. Ожидается ключ products со списком продуктов.")

    class_names = []

    for product in products:
        class_name = product.get("class_name")

        if not class_name:
            raise ValueError("В одном из продуктов отсутствует поле class_name.")

        class_names.append(class_name)

    return class_names


CLASS_NAMES = load_class_names()


app = FastAPI(
    title="NutriVision ML API",
    description="FastAPI service for food detection using YOLO ONNX Runtime",
    version="3.0.0",
)

nutrition_classifier = NutritionClassifier()

app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)


session = None
session_model_path = None
predict_lock = asyncio.Lock()


def ensure_model_path() -> Path:
    MODELS_DIR.mkdir(parents=True, exist_ok=True)

    if LOCAL_MODEL_PATH.exists() and LOCAL_MODEL_PATH.stat().st_size > 0:
        return LOCAL_MODEL_PATH

    if not HF_MODEL_REPO:
        raise FileNotFoundError(
            "ONNX модель не найдена локально и переменная HF_MODEL_REPO не задана"
        )

    downloaded_path = hf_hub_download(
        repo_id=HF_MODEL_REPO,
        filename=MODEL_FILE,
        token=HF_TOKEN,
        local_dir=str(MODELS_DIR),
    )

    downloaded_path = Path(downloaded_path)

    if not downloaded_path.exists() or downloaded_path.stat().st_size == 0:
        raise FileNotFoundError("ONNX модель не была скачана из Hugging Face")

    return downloaded_path


def get_session():
    global session
    global session_model_path

    if session is None:
        model_path = ensure_model_path()

        options = ort.SessionOptions()

        # Render-friendly settings.
        # Ограничиваем параллелизм, чтобы ONNX Runtime не забирал слишком много RAM/CPU.
        options.intra_op_num_threads = 1
        options.inter_op_num_threads = 1

        # Снижаем вероятность резких скачков памяти.
        options.enable_mem_pattern = False
        options.enable_cpu_mem_arena = False

        # Не включаем тяжёлые graph optimizations сверх базовых.
        options.graph_optimization_level = ort.GraphOptimizationLevel.ORT_ENABLE_BASIC

        session = ort.InferenceSession(
            str(model_path),
            sess_options=options,
            providers=["CPUExecutionProvider"],
        )

        session_model_path = str(model_path)

    return session


def letterbox(image, new_shape=640, color=(114, 114, 114)):
    original_height, original_width = image.shape[:2]

    scale = min(new_shape / original_height, new_shape / original_width)

    resized_width = int(round(original_width * scale))
    resized_height = int(round(original_height * scale))

    resized_image = cv2.resize(
        image,
        (resized_width, resized_height),
        interpolation=cv2.INTER_LINEAR,
    )

    canvas = np.full(
        (new_shape, new_shape, 3),
        color,
        dtype=np.uint8,
    )

    pad_x = (new_shape - resized_width) // 2
    pad_y = (new_shape - resized_height) // 2

    canvas[
        pad_y:pad_y + resized_height,
        pad_x:pad_x + resized_width,
    ] = resized_image

    return canvas, scale, pad_x, pad_y


def preprocess_image_bytes(image_bytes: bytes):
    image_array = np.frombuffer(image_bytes, np.uint8)
    image_bgr = cv2.imdecode(image_array, cv2.IMREAD_COLOR)

    if image_bgr is None:
        raise ValueError("Не удалось прочитать изображение")

    original_height, original_width = image_bgr.shape[:2]

    image_rgb = cv2.cvtColor(image_bgr, cv2.COLOR_BGR2RGB)

    image_resized, scale, pad_x, pad_y = letterbox(
        image_rgb,
        IMAGE_SIZE,
    )

    input_tensor = image_resized.astype(np.float32) / 255.0
    input_tensor = np.transpose(input_tensor, (2, 0, 1))
    input_tensor = np.expand_dims(input_tensor, axis=0)

    return (
        input_tensor,
        original_width,
        original_height,
        scale,
        pad_x,
        pad_y,
    )


def xywh_to_xyxy(box):
    x, y, width, height = box

    return [
        x - width / 2,
        y - height / 2,
        x + width / 2,
        y + height / 2,
    ]


def clip_box(box, image_width, image_height):
    x1, y1, x2, y2 = box

    x1 = max(0, min(float(x1), image_width))
    y1 = max(0, min(float(y1), image_height))
    x2 = max(0, min(float(x2), image_width))
    y2 = max(0, min(float(y2), image_height))

    return [x1, y1, x2, y2]


def normalize_predictions(outputs):
    predictions = outputs[0]

    if predictions.ndim == 3:
        predictions = predictions[0]

    # Частый формат YOLO ONNX: [classes + 4, boxes]
    # Для обработки удобнее: [boxes, classes + 4]
    if predictions.shape[0] < predictions.shape[1]:
        predictions = predictions.T

    return predictions


def postprocess(outputs, original_width, original_height, scale, pad_x, pad_y):
    predictions = normalize_predictions(outputs)

    boxes = []
    scores = []
    class_ids = []

    for prediction in predictions:
        box = prediction[:4]
        class_scores = prediction[4:]

        if len(class_scores) == 0:
            continue

        class_id = int(np.argmax(class_scores))
        confidence = float(class_scores[class_id])

        if confidence < CONF_THRESHOLD:
            continue

        x1, y1, x2, y2 = xywh_to_xyxy(box)

        x1 = (x1 - pad_x) / scale
        y1 = (y1 - pad_y) / scale
        x2 = (x2 - pad_x) / scale
        y2 = (y2 - pad_y) / scale

        x1, y1, x2, y2 = clip_box(
            [x1, y1, x2, y2],
            original_width,
            original_height,
        )

        width = x2 - x1
        height = y2 - y1

        if width <= 0 or height <= 0:
            continue

        boxes.append([
            float(x1),
            float(y1),
            float(width),
            float(height),
        ])
        scores.append(confidence)
        class_ids.append(class_id)

    selected_indices = cv2.dnn.NMSBoxes(
        boxes,
        scores,
        CONF_THRESHOLD,
        IOU_THRESHOLD,
    )

    detections = []

    if len(selected_indices) == 0:
        return detections

    selected_indices = np.array(selected_indices).flatten()

    for index in selected_indices:
        class_id = class_ids[index]

        if class_id < 0 or class_id >= len(CLASS_NAMES):
            continue

        x, y, width, height = boxes[index]
        confidence = scores[index]

        detections.append({
            "class_name": CLASS_NAMES[class_id],
            "confidence": round(float(confidence), 4),
            "bbox": {
                "x1": round(float(x), 2),
                "y1": round(float(y), 2),
                "x2": round(float(x + width), 2),
                "y2": round(float(y + height), 2),
            },
        })

    detections.sort(
        key=lambda item: item["confidence"],
        reverse=True,
    )

    return detections


def group_products(detections):
    grouped = defaultdict(
        lambda: {
            "class_name": None,
            "count": 0,
            "max_confidence": 0.0,
        }
    )

    for detection in detections:
        class_name = detection["class_name"]
        confidence = detection["confidence"]

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

    for product in products:
        product["max_confidence"] = round(
            float(product["max_confidence"]),
            4,
        )

    return products


@app.get("/")
def root():
    return {
        "status": "ok",
        "message": "NutriVision ML API is working",
    }


@app.get("/health")
def health():
    return {
        "status": "ok",
        "runtime": "onnxruntime",
        "model_file": MODEL_FILE,
        "local_model_ready": LOCAL_MODEL_PATH.exists(),
        "hf_model_repo_configured": bool(HF_MODEL_REPO),
        "products_ready": PRODUCTS_PATH.exists(),
        "classes_count": len(CLASS_NAMES),
        "image_size": IMAGE_SIZE,
        "confidence_threshold": CONF_THRESHOLD,
        "iou_threshold": IOU_THRESHOLD,
        "max_file_size_mb": MAX_FILE_SIZE_MB,
        "model_loaded": session is not None,
    }


@app.post("/warmup")
def warmup():
    try:
        ort_session = get_session()
        input_info = ort_session.get_inputs()[0]

        return {
            "status": "ok",
            "message": "Model loaded successfully",
            "model_file": MODEL_FILE,
            "model_path": session_model_path,
            "input_name": input_info.name,
            "input_shape": input_info.shape,
            "input_type": input_info.type,
        }
    except Exception as error:
        print("Warmup error:", repr(error), flush=True)

        raise HTTPException(
            status_code=500,
            detail=f"Warmup error: {repr(error)}",
        )


@app.post("/predict")
async def predict(image: UploadFile = File(...)):
    if predict_lock.locked():
        raise HTTPException(
            status_code=429,
            detail="Сервис уже обрабатывает изображение. Повторите запрос через несколько секунд.",
        )

    async with predict_lock:
        image_bytes = None
        input_tensor = None
        outputs = None
        detections = None
        products = None

        try:
            image_bytes = await image.read()

            if not image_bytes:
                raise HTTPException(
                    status_code=400,
                    detail="Файл изображения пустой",
                )

            if len(image_bytes) > MAX_FILE_SIZE:
                raise HTTPException(
                    status_code=413,
                    detail=f"Файл слишком большой. Загрузите изображение до {MAX_FILE_SIZE_MB} МБ.",
                )

            ort_session = get_session()

            (
                input_tensor,
                original_width,
                original_height,
                scale,
                pad_x,
                pad_y,
            ) = preprocess_image_bytes(image_bytes)

            input_name = ort_session.get_inputs()[0].name

            outputs = ort_session.run(
                None,
                {
                    input_name: input_tensor,
                },
            )

            detections = postprocess(
                outputs,
                original_width,
                original_height,
                scale,
                pad_x,
                pad_y,
            )

            products = group_products(detections)

            return {
                "status": "ok",
                "detections_count": len(detections),
                "products_count": len(products),
                "detections": detections,
                "products": products,
            }

        except HTTPException:
            raise

        except ValueError as error:
            raise HTTPException(
                status_code=400,
                detail=str(error),
            )

        except Exception as error:
            print("Predict error:", repr(error), flush=True)

            raise HTTPException(
                status_code=500,
                detail="Ошибка сервиса распознавания. Повторите попытку позже.",
            )

        finally:
            del image_bytes
            del input_tensor
            del outputs
            del detections
            del products

            gc.collect()

@app.post("/nutrition/weekly-analysis", response_model=WeeklyNutritionResponse)
def analyze_weekly_nutrition(payload: WeeklyNutritionRequest):
    return nutrition_classifier.predict(payload)