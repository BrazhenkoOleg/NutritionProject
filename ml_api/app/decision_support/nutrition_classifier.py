from pathlib import Path

import joblib

from .features import build_weekly_features
from .recommendations import get_recommendation
from .schemas import WeeklyNutritionRequest, WeeklyNutritionResponse


MODEL_PATH = Path(__file__).resolve().parents[1] / "models" / "nutrition_decision_tree.joblib"


class NutritionClassifier:
    def __init__(self) -> None:
        if not MODEL_PATH.exists():
            raise FileNotFoundError(
                f"Nutrition classifier model not found: {MODEL_PATH}. "
                "Run scripts/train_nutrition_classifier.py first."
            )

        self.model = joblib.load(MODEL_PATH)

    def predict(self, payload: WeeklyNutritionRequest) -> WeeklyNutritionResponse:
        feature_vector, features = build_weekly_features(payload)

        label = self.model.predict([feature_vector])[0]
        recommendation = get_recommendation(label)

        return WeeklyNutritionResponse(
            type=label,
            title=recommendation["title"],
            description=recommendation["description"],
            recommendations=recommendation["recommendations"],
            features=features,
        )