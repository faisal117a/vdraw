from pydantic import BaseModel, Field, validator
from typing import List, Optional, Literal, Dict, Union

class ManualDataInput(BaseModel):
    data: List[float] = Field(..., min_items=1, max_items=1000)
    label: str = "Dataset"

class StatsRequest(BaseModel):
    data: List[Union[float, int, str]] = Field(..., min_items=1, max_items=10000) # For Descriptive Stats
    x_data: Optional[List[Union[float, int, str]]] = Field(None, min_items=1, max_items=10000) # Regression X
    y_data: Optional[List[Union[float, int, str]]] = Field(None, min_items=1, max_items=10000) # Regression Y (Explicit)
    is_sample: bool = True
    quartile_method: Literal['exclusive', 'inclusive', 'tukey'] = 'exclusive'
    regression_type: Optional[Literal['linear', 'logistic']] = None

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
    mode: List[Union[float, str]]
    variance: float
    std_dev: float
    range: float
    quartiles: Dict[str, float]
    iqr: float
    outliers: List[float]
    explanations: List[Dict[str, Union[str, float, List[Dict[str, str]]]]]
    regression: Optional[Dict[str, Union[float, str, List[float], List[str]]]] = None

class ParseDataResponse(BaseModel):
    columns: List[str] # All columns
    numeric_columns: List[str] # Only numeric columns for analysis
    preview: List[Dict[str, Union[float, str, None]]] # Preview
    full_data: Dict[str, List[Union[float, str, None]]] # Full data mixed
    summary: Dict[str, int] # e.g. {"rows": 100, "cols": 5}

