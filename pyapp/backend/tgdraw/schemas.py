from pydantic import BaseModel
from typing import List, Optional, Any, Literal

# --- Common ---
class TGResponse(BaseModel):
    success: bool
    data: Optional[Any] = None
    error: Optional[str] = None

# --- Tree Schemas ---
class TreeConfig(BaseModel):
    type: Literal['generic', 'binary', 'bst', 'avl']
    root_value: Optional[Any] = None

class TreeNode(BaseModel):
    id: str
    value: Any
    children: List[str] = []
    # For binary/BST
    left: Optional[str] = None
    right: Optional[str] = None
    # Metadata
    height: int = 0
    balance_factor: int = 0

class TreeStats(BaseModel):
    total_nodes: int = 0
    height: int = 0
    leaf_count: int = 0
    leaves: List[str] = []
    is_balanced: bool = True
    total_edges: int = 0

class TreeState(BaseModel):
    root_id: Optional[str] = None
    nodes: dict[str, TreeNode] = {} # Map id -> Node
    stats: Optional[TreeStats] = None

class CreateTreeRootRequest(BaseModel):
    type: Literal['generic', 'binary', 'bst', 'avl']
    value: Any

class AddTreeNodeRequest(BaseModel):
    parent_id: str
    value: Any
    direction: Optional[Literal['left', 'right']] = None # Required for Binary/BST types

class TraversalRequest(BaseModel):
    type: Literal['bfs', 'inorder', 'preorder', 'postorder']
    start_node: Optional[str] = None

# --- Graph Schemas ---
class GraphConfig(BaseModel):
    directed: bool
    weighted: bool

class GraphNode(BaseModel):
    id: str
    value: Any

class GraphEdge(BaseModel):
    source: str
    target: str
    weight: Optional[float] = None
    bidirectional: bool = False # If true in undirected, represents single edge

class GraphStats(BaseModel):
    total_vertices: int = 0
    total_edges: int = 0
    degrees: dict[str, dict[str, int]] = {} # id -> {in: x, out: y, total: z}

class GraphState(BaseModel):
    nodes: dict[str, GraphNode] = {}
    adjacency_list: dict[str, List[dict]] = {} # id -> list of {target, weight}
    config: GraphConfig
    stats: Optional[GraphStats] = None

class CreateGraphLogicRequest(BaseModel): # Renamed to avoid confusion with internal logic if needed, but simple is fine
    directed: bool
    weighted: bool

class AddGraphNodeRequest(BaseModel):
    id: str
    value: Any

class AddGraphEdgeRequest(BaseModel):
    source: str
    target: str
    weight: Optional[float] = None

class RemoveGraphEdgeRequest(BaseModel):
    source: str
    target: str

class GraphAlgoRequest(BaseModel):
    algorithm: Literal['bfs', 'dfs', 'dijkstra']
    start_node: str
