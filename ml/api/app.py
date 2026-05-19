from pathlib import Path
from tempfile import NamedTemporaryFile
from collections import defaultdict
import gc
import json

import cv2
import numpy as np
import onnxruntime as ort
from fastapi import FastAPI, UploadFile, File, HTTPException
from fastapi.middleware.cors import CORSMiddleware


ML_ROOT = Path(__file__).resolve().parents[1]

MODEL_PATH = ML_ROOT / "models" / "best.onnx"
PRODUCTS_PATH = ML_ROOT / "data" / "nutrition_products_ru_kbju_100g.json"

IMAGE_SIZE = 640
CONF_THRESHOLD = 0.25
IOU_THRESHOLD = 0.45


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
    title="Nutrition ONNX API",
    description="API для распознавания продуктов питания на изображениях через ONNX Runtime",
    version="2.0.0",
)

app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

session = None


def get_session():
    global session

    if session is None:
        if not MODEL_PATH.exists():
            raise FileNotFoundError(f"ONNX модель не найдена: {MODEL_PATH}")

        session = ort.InferenceSession(
            str(MODEL_PATH),
            providers=["CPUExecutionProvider"],
        )

    return session


def letterbox(image, new_shape=640, color=(114, 114, 114)):
    height, width = image.shape[:2]

    scale = min(new_shape / height, new_shape / width)

    new_width = int(round(width * scale))
    new_height = int(round(height * scale))

    resized = cv2.resize(
        image,
        (new_width, new_height),
        interpolation=cv2.INTER_LINEAR,
    )

    canvas = np.full(
        (new_shape, new_shape, 3),
        color,
        dtype=np.uint8,
    )

    pad_x = (new_shape - new_width) // 2
    pad_y = (new_shape - new_height) // 2

    canvas[pad_y:pad_y + new_height, pad_x:pad_x + new_width] = resized

    return canvas, scale, pad_x, pad_y


def preprocess(image_path: Path):
    image = cv2.imread(str(image_path))

    if image is None:
        raise ValueError("Не удалось прочитать изображение")

    original_height, original_width = image.shape[:2]

    image_rgb = cv2.cvtColor(image, cv2.COLOR_BGR2RGB)
    image_resized, scale, pad_x, pad_y = letterbox(image_rgb, IMAGE_SIZE)

    input_tensor = image_resized.astype(np.float32) / 255.0
    input_tensor = np.transpose(input_tensor, (2, 0, 1))
    input_tensor = np.expand_dims(input_tensor, axis=0)

    return input_tensor, original_width, original_height, scale, pad_x, pad_y


def xywh_to_xyxy(box):
    x, y, w, h = box

    x1 = x - w / 2
    y1 = y - h / 2
    x2 = x + w / 2
    y2 = y + h / 2

    return [x1, y1, x2, y2]


def clip_box(box, width, height):
    x1, y1, x2, y2 = box

    x1 = max(0, min(x1, width))
    y1 = max(0, min(y1, height))
    x2 = max(0, min(x2, width))
    y2 = max(0, min(y2, height))

    return [x1, y1, x2, y2]


def postprocess(outputs, original_width, original_height, scale, pad_x, pad_y):
    predictions = outputs[0]

    if predictions.ndim == 3:
        predictions = predictions[0]

    # YOLO ONNX часто возвращает форму:
    # (1, 57, 8400) или (57, 8400)
    # Нужно привести к:
    # (8400, 57)
    if predictions.shape[0] < predictions.shape[1]:
        predictions = predictions.T

    boxes = []
    scores = []
    class_ids = []

    for prediction in predictions:
        box = prediction[:4]
        class_scores = prediction[4:]

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

        boxes.append([float(x1), float(y1), float(width), float(height)])
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
        x, y, w, h = boxes[index]
        class_id = class_ids[index]
        confidence = scores[index]

        if class_id < 0 or class_id >= len(CLASS_NAMES):
            continue

        detections.append({
            "class_name": CLASS_NAMES[class_id],
            "confidence": round(float(confidence), 4),
            "bbox": {
                "x1": round(float(x), 2),
                "y1": round(float(y), 2),
                "x2": round(float(x + w), 2),
                "y2": round(float(y + h), 2),
            },
        })

    return detections


def group_products(detections):
    grouped = defaultdict(lambda: {
        "class_name": None,
        "count": 0,
        "max_confidence": 0.0,
    })

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

    for item in products:
        item["max_confidence"] = round(item["max_confidence"], 4)

    return products


@app.get("/")
def root():
    return {
        "status": "ok",
        "message": "Nutrition ONNX API is working",
    }


@app.get("/health")
def health():
    return {
        "status": "ok",
        "runtime": "onnxruntime",
        "model_path": str(MODEL_PATH),
        "model_exists": MODEL_PATH.exists(),
        "products_path": str(PRODUCTS_PATH),
        "products_file_exists": PRODUCTS_PATH.exists(),
        "classes_count": len(CLASS_NAMES),
        "image_size": IMAGE_SIZE,
        "model_loaded": session is not None,
    }


@app.post("/predict")
async def predict(image: UploadFile = File(...)):
    suffix = Path(image.filename or "").suffix or ".jpg"

    with NamedTemporaryFile(delete=False, suffix=suffix) as temp_file:
        temp_file.write(await image.read())
        temp_image_path = Path(temp_file.name)

    try:
        try:
            ort_session = get_session()
        except FileNotFoundError as error:
            raise HTTPException(
                status_code=500,
                detail=str(error),
            )

        input_tensor, original_width, original_height, scale, pad_x, pad_y = preprocess(
            temp_image_path,
        )

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

    except ValueError as error:
        raise HTTPException(
            status_code=400,
            detail=str(error),
        )

    except Exception as error:
        print("Predict error:", repr(error), flush=True)

        raise HTTPException(
            status_code=500,
            detail=str(error),
        )

    finally:
        if temp_image_path.exists():
            temp_image_path.unlink()

        gc.collect()