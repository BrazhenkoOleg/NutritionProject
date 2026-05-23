from pydantic import BaseModel, Field


class NutritionDay(BaseModel):
    date: str | None = None

    kcal: float = Field(default=0, ge=0)
    protein: float = Field(default=0, ge=0)
    fat: float = Field(default=0, ge=0)
    carbs: float = Field(default=0, ge=0)


class NutritionTargets(BaseModel):
    kcal: float = Field(default=0, ge=0)
    protein: float = Field(default=0, ge=0)
    fat: float = Field(default=0, ge=0)
    carbs: float = Field(default=0, ge=0)


class WeeklyNutritionRequest(BaseModel):
    days: list[NutritionDay]
    targets: NutritionTargets


class WeeklyNutritionResponse(BaseModel):
    type: str
    title: str
    description: str
    recommendations: list[str]
    features: dict[str, float]