import numpy as np

from .schemas import WeeklyNutritionRequest


FEATURE_NAMES = [
    "avg_kcal_ratio",
    "avg_protein_ratio",
    "avg_fat_ratio",
    "avg_carbs_ratio",
    "active_days_ratio",
    "records_count",
    "kcal_variability",
]


def build_weekly_features(payload: WeeklyNutritionRequest) -> tuple[list[float], dict[str, float]]:
    days = payload.days[:7]

    kcal_goal = safe_goal(payload.targets.kcal)
    protein_goal = safe_goal(payload.targets.protein)
    fat_goal = safe_goal(payload.targets.fat)
    carbs_goal = safe_goal(payload.targets.carbs)

    active_days = [day for day in days if day.kcal > 0 or day.protein > 0 or day.fat > 0 or day.carbs > 0]

    if not active_days:
        features = {
            "avg_kcal_ratio": 0.0,
            "avg_protein_ratio": 0.0,
            "avg_fat_ratio": 0.0,
            "avg_carbs_ratio": 0.0,
            "active_days_ratio": 0.0,
            "records_count": 0.0,
            "kcal_variability": 0.0,
        }

        return list(features.values()), features

    kcal_values = np.array([day.kcal for day in active_days], dtype=float)

    avg_kcal_ratio = np.mean([day.kcal / kcal_goal for day in active_days])
    avg_protein_ratio = np.mean([day.protein / protein_goal for day in active_days])
    avg_fat_ratio = np.mean([day.fat / fat_goal for day in active_days])
    avg_carbs_ratio = np.mean([day.carbs / carbs_goal for day in active_days])

    active_days_ratio = len(active_days) / 7
    records_count = len(active_days)

    kcal_variability = 0.0
    if len(kcal_values) > 1 and np.mean(kcal_values) > 0:
        kcal_variability = float(np.std(kcal_values) / np.mean(kcal_values))

    features = {
        "avg_kcal_ratio": round(float(avg_kcal_ratio), 4),
        "avg_protein_ratio": round(float(avg_protein_ratio), 4),
        "avg_fat_ratio": round(float(avg_fat_ratio), 4),
        "avg_carbs_ratio": round(float(avg_carbs_ratio), 4),
        "active_days_ratio": round(float(active_days_ratio), 4),
        "records_count": float(records_count),
        "kcal_variability": round(float(kcal_variability), 4),
    }

    return list(features.values()), features


def safe_goal(value: float) -> float:
    return value if value > 0 else 1.0