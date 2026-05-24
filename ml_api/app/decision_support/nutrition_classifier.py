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

        predicted_label = self.model.predict([feature_vector])[0]
        label = self.apply_domain_constraints(predicted_label, features)

        recommendation = get_recommendation(label)

        return WeeklyNutritionResponse(
            type=label,
            title=recommendation["title"],
            description=recommendation["description"],
            recommendations=recommendation["recommendations"],
            features=features,
        )

    def apply_domain_constraints(self, predicted_label: str, features: dict[str, float]) -> str:
        avg_kcal_ratio = features.get("avg_kcal_ratio", 0.0)
        avg_protein_ratio = features.get("avg_protein_ratio", 0.0)
        avg_fat_ratio = features.get("avg_fat_ratio", 0.0)
        avg_carbs_ratio = features.get("avg_carbs_ratio", 0.0)
        active_days_ratio = features.get("active_days_ratio", 0.0)
        records_count = features.get("records_count", 0.0)

        if records_count < 4 or active_days_ratio < 0.5:
            return "irregular"

        is_calorie_normal = 0.85 <= avg_kcal_ratio <= 1.15
        has_fat_excess = avg_fat_ratio >= 1.35
        has_carb_excess = avg_carbs_ratio >= 1.35
        has_protein_deficit = avg_protein_ratio <= 0.7

        if is_calorie_normal and has_fat_excess and has_carb_excess:
            return "high_fat_and_carb"

        if is_calorie_normal and has_fat_excess:
            return "high_fat"

        if is_calorie_normal and has_carb_excess:
            return "high_carb"

        if is_calorie_normal and has_protein_deficit:
            return "protein_deficit"

        return predicted_label