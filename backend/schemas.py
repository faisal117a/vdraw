from pydantic import BaseModel, Field, validator
from typing import List, Optional, Literal, Dict, Union

class ManualDataInput(BaseModel):
    data: List[float] = Field(..., min_items=1, max_items=1000)
    label: str = "Dataset"

class StatsRequest(BaseModel):
    data: List[float] = Field(..., min_items=1, max_items=1000)
    is_sample: bool = True
    quartile_method: Literal['inclusive', 'exclusive', 'tukey'] = 'exclusive'

class Step(BaseModel):
    text: str
    latex: str = ""

class Explanation(BaseModel):
    title: str
    result: str
    steps: List[Step]

class StatsResponse(BaseModel):
    mean: float
    median: float
    mode: List[float]
    range: float
    variance: float
    std_dev: float
    quartiles: Dict[str, float]
    iqr: float
    outliers: List[float]
    explanations: List[Explanation] = []

class ParseDataResponse(BaseModel):
    columns: List[str] # All columns
    numeric_columns: List[str] # Only numeric columns for analysis
    preview: List[Dict[str, Union[float, str, None]]] # Preview
    full_data: Dict[str, List[Union[float, str, None]]] # Full data mixed
    summary: Dict[str, int] # e.g. {"rows": 100, "cols": 5}
