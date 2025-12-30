from typing import List, Dict, Any, Optional, Union
from pydantic import BaseModel

# --- Requests ---
class OperationStep(BaseModel):
    op: str
    args: Dict[str, Any] = {}

class SimulationRequest(BaseModel):
    structure: str
    implementation: str
    initial_values: List[Any]
    operations: List[OperationStep]
    options: Dict[str, bool] = {}

# --- Responses ---
class DiagramState(BaseModel):
    type: str
    items: List[Any]
    front: Optional[int] = None
    rear: Optional[int] = None
    highlight: Optional[int] = None # Index to highlight

class SimulationStepResponse(BaseModel):
    index: int
    operation: str
    status: str # "ok", "error"
    print_output: str
    explanation: str
    complexity: str
    memory: str
    diagram: DiagramState
    error_msg: Optional[str] = None

class SimulationResponse(BaseModel):
    initial: Dict[str, Any] # {"print": str, "diagram": DiagramState}
    steps: List[SimulationStepResponse]

class CatalogOperation(BaseModel):
    id: str
    label: str
    params: List[Dict[str, Any]] # {"name": "val", "type": "int", "required": True}
    complexity: str

class CatalogItem(BaseModel):
    id: str
    label: str
    implementations: List[str]
    operations: List[CatalogOperation]

class CatalogResponse(BaseModel):
    structures: List[CatalogItem]
