from pathlib import Path

import joblib
from sklearn.tree import DecisionTreeClassifier


FEATURES = [
    # avg_kcal, avg_protein, avg_fat, avg_carbs, active_days, records, kcal_var

    # balanced
    ([0.95, 0.95, 1.00, 1.00, 1.00, 7, 0.10], "balanced"),
    ([1.02, 1.05, 0.95, 1.05, 1.00, 7, 0.12], "balanced"),
    ([0.90, 0.90, 0.90, 0.95, 0.85, 6, 0.15], "balanced"),

    # protein deficit
    ([0.95, 0.55, 1.00, 1.05, 1.00, 7, 0.12], "protein_deficit"),
    ([1.05, 0.60, 1.10, 1.15, 0.85, 6, 0.18], "protein_deficit"),
    ([0.90, 0.50, 0.90, 1.00, 0.71, 5, 0.20], "protein_deficit"),

    # calorie deficit
    ([0.55, 0.70, 0.65, 0.60, 1.00, 7, 0.15], "calorie_deficit"),
    ([0.65, 0.80, 0.70, 0.75, 0.85, 6, 0.20], "calorie_deficit"),
    ([0.70, 0.90, 0.70, 0.70, 1.00, 7, 0.16], "calorie_deficit"),

    # calorie surplus
    ([1.30, 1.00, 1.15, 1.25, 1.00, 7, 0.16], "calorie_surplus"),
    ([1.45, 1.10, 1.25, 1.35, 0.85, 6, 0.22], "calorie_surplus"),
    ([1.25, 0.95, 1.20, 1.20, 1.00, 7, 0.18], "calorie_surplus"),

    # high carb
    ([1.05, 0.90, 0.90, 1.45, 1.00, 7, 0.15], "high_carb"),
    ([0.95, 0.80, 0.85, 1.35, 0.85, 6, 0.20], "high_carb"),
    ([1.10, 0.85, 0.95, 1.55, 1.00, 7, 0.18], "high_carb"),

    # high fat
    ([1.05, 0.95, 1.45, 0.95, 1.00, 7, 0.15], "high_fat"),
    ([1.15, 0.90, 1.55, 0.90, 0.85, 6, 0.22], "high_fat"),
    ([0.95, 0.85, 1.35, 0.80, 1.00, 7, 0.18], "high_fat"),

    # normal calories, high carbs
    ([1.00, 0.95, 0.90, 1.55, 1.00, 7, 0.12], "high_carb"),
    ([0.95, 0.90, 0.85, 1.70, 0.85, 6, 0.16], "high_carb"),
    ([1.08, 1.00, 0.95, 1.85, 1.00, 7, 0.14], "high_carb"),

    # normal calories, high fat
    ([1.00, 0.95, 1.55, 0.90, 1.00, 7, 0.12], "high_fat"),
    ([0.95, 0.90, 1.70, 0.85, 0.85, 6, 0.16], "high_fat"),
    ([1.08, 1.00, 1.85, 0.95, 1.00, 7, 0.14], "high_fat"),

    # normal calories, high fat and high carbs
    ([1.00, 0.95, 1.50, 1.50, 1.00, 7, 0.12], "high_fat_and_carb"),
    ([1.08, 0.90, 1.70, 1.60, 0.85, 6, 0.18], "high_fat_and_carb"),
    ([0.95, 0.95, 1.60, 1.75, 1.00, 7, 0.15], "high_fat_and_carb"),

    # irregular
    ([0.20, 0.20, 0.20, 0.20, 0.14, 1, 0.00], "irregular"),
    ([0.35, 0.40, 0.35, 0.40, 0.28, 2, 0.50], "irregular"),
    ([0.50, 0.50, 0.50, 0.50, 0.42, 3, 0.60], "irregular"),
]

BASE_DIR = Path(__file__).resolve().parents[1]
MODEL_DIR = BASE_DIR / "app" / "models"
MODEL_PATH = MODEL_DIR / "nutrition_decision_tree.joblib"


def main() -> None:
    x = [item[0] for item in FEATURES]
    y = [item[1] for item in FEATURES]

    model = DecisionTreeClassifier(
        max_depth=4,
        random_state=42,
    )

    model.fit(x, y)

    MODEL_DIR.mkdir(parents=True, exist_ok=True)
    joblib.dump(model, MODEL_PATH)

    print(f"Model saved to: {MODEL_PATH}")
    print(f"Training samples: {len(FEATURES)}")


if __name__ == "__main__":
    main()