from fastapi import APIRouter
from .engine import TreeEngine, GraphEngine
from .schemas import (
    CreateTreeRootRequest, AddTreeNodeRequest, TraversalRequest, TreeConfig, TreeState, TGResponse,
    CreateGraphLogicRequest, AddGraphNodeRequest, AddGraphEdgeRequest, RemoveGraphEdgeRequest, GraphConfig, GraphState, GraphAlgoRequest
)
from .traversals import TreeTraversals, GraphTraversals

router = APIRouter(prefix="/api/tg", tags=["tgdraw"])

@router.post("/graph/run-algorithm", response_model=TGResponse)
def run_graph_algo(req: GraphAlgoRequest):
    engine = GraphEngine()
    state = engine.get_state()
    
    if req.start_node not in state.nodes:
        return TGResponse(success=False, error=f"Start node {req.start_node} not found")
        
    traverser = GraphTraversals(state.nodes, state.adjacency_list)
    steps = []
    
    if req.algorithm == 'bfs':
        steps = traverser.bfs(req.start_node)
    elif req.algorithm == 'dfs':
        steps = traverser.dfs(req.start_node)
    elif req.algorithm == 'dijkstra':
        steps = traverser.dijkstra(req.start_node)
        
    return TGResponse(success=True, data=steps)

@router.post("/graph/create", response_model=TGResponse)
def create_graph(req: CreateGraphLogicRequest):
    engine = GraphEngine()
    config = GraphConfig(directed=req.directed, weighted=req.weighted)
    state = engine.reset_graph(config)
    return TGResponse(success=True, data=state)

@router.post("/graph/add-node", response_model=TGResponse)
def add_graph_node(req: AddGraphNodeRequest):
    engine = GraphEngine()
    try:
        if engine.state.adjacency_list is None: # Quick check if initialized
            return TGResponse(success=False, error="Graph not initialized")
        state = engine.add_node(req.id, req.value)
        return TGResponse(success=True, data=state)
    except Exception as e:
        return TGResponse(success=False, error=str(e))

@router.post("/graph/add-edge", response_model=TGResponse)
def add_graph_edge(req: AddGraphEdgeRequest):
    engine = GraphEngine()
    try:
         state = engine.add_edge(req.source, req.target, req.weight)
         return TGResponse(success=True, data=state)
    except Exception as e:
         return TGResponse(success=False, error=str(e))

@router.post("/graph/remove-edge", response_model=TGResponse)
def remove_graph_edge(req: RemoveGraphEdgeRequest):
    engine = GraphEngine()
    try:
        state = engine.remove_edge(req.source, req.target)
        return TGResponse(success=True, data=state)
    except Exception as e:
        return TGResponse(success=False, error=str(e))

@router.post("/tree/traverse", response_model=TGResponse)
def traverse_tree(req: TraversalRequest):
    engine = TreeEngine()
    state = engine.get_state()
    
    if not state.root_id:
         return TGResponse(success=False, error="Tree is empty")

    traverser = TreeTraversals(state.nodes, req.start_node or state.root_id)
    steps = []
    
    if req.type == 'bfs':
        steps = traverser.bfs()
    elif req.type == 'inorder':
        steps = traverser.dfs_inorder()
    elif req.type == 'preorder':
        steps = traverser.dfs_preorder()
    elif req.type == 'postorder':
        steps = traverser.dfs_postorder()
        
    return TGResponse(success=True, data=steps)

@router.get("/status")
def get_status():
    return {"status": "TGDraw Module Active"}

@router.post("/tree/create-root", response_model=TGResponse)
def create_tree_root(req: CreateTreeRootRequest):
    engine = TreeEngine()
    config = TreeConfig(type=req.type, root_value=req.value)
    
    try:
        new_state = engine.reset_tree(config)
        return TGResponse(success=True, data=new_state)
    except Exception as e:
         return TGResponse(success=False, error=str(e))

@router.post("/tree/add-node", response_model=TGResponse)
def add_tree_node(req: AddTreeNodeRequest):
    engine = TreeEngine()
    try:
        new_state = engine.add_node(req.parent_id, req.value, req.direction)
        return TGResponse(success=True, data=new_state)
    except Exception as e:
        return TGResponse(success=False, error=str(e))
