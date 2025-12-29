import uuid
from typing import Dict, Any, List, Optional
from .schemas import TreeNode, TreeState, TreeConfig, GraphState, GraphConfig, GraphNode, GraphEdge

# In-memory storage for session (Mock DB)
# session_id -> TreeState
TREE_SESSIONS: Dict[str, TreeState] = {}
TREE_CONFIGS: Dict[str, TreeConfig] = {}

class TreeEngine:
    def __init__(self, session_id: str = "default"):
        self.session_id = session_id
        if session_id not in TREE_SESSIONS:
            TREE_SESSIONS[session_id] = TreeState()
        
        self.state = TREE_SESSIONS[session_id]
        
    def get_state(self) -> TreeState:
        return self.state

    def reset_tree(self, config: TreeConfig):
        self.state.root_id = None
        self.state.nodes = {}
        TREE_CONFIGS[self.session_id] = config
        
        # Create Root
        root_id = str(uuid.uuid4())[:8]
        root_node = TreeNode(id=root_id, value=config.root_value)
        self.state.nodes[root_id] = root_node
        self.state.root_id = root_id
        return self.state

    def add_node(self, parent_id: str, value: Any, direction: Optional[str] = None):
        config = TREE_CONFIGS.get(self.session_id)
        if not config:
            raise ValueError("Tree not initialized")

        parent = self.state.nodes.get(parent_id)
        if not parent:
            raise ValueError("Parent node not found")

        # 1. Validation Logic
        if config.type == 'binary' or config.type == 'bst' or config.type == 'avl':
            if direction not in ['left', 'right']:
                raise ValueError("Direction (left/right) required for Binary trees")
            
            if direction == 'left' and parent.left:
                raise ValueError("Left child already exists")
            if direction == 'right' and parent.right:
                raise ValueError("Right child already exists")
            
            # BST Rules
            if config.type in ['bst', 'avl']:
                try:
                    p_val = float(parent.value)
                    n_val = float(value)
                    
                    if direction == 'left' and n_val >= p_val:
                         raise ValueError(f"BST Rule Violation: {n_val} must be < {p_val} (Left Child)")
                    if direction == 'right' and n_val <= p_val:
                         raise ValueError(f"BST Rule Violation: {n_val} must be > {p_val} (Right Child)")
                except ValueError:
                    pass # Allow strings if cannot convert to float, maybe compare strings?
                    # For VDraw let's assumes numeric for BST usually, or lex string comparison

        # 2. Create Node
        new_id = str(uuid.uuid4())[:8]
        new_node = TreeNode(id=new_id, value=value)
        self.state.nodes[new_id] = new_node

        # 3. Link Parent
        if config.type == 'generic':
            parent.children.append(new_id)
        else:
            # Binary/BST/AVL
            if direction == 'left':
                parent.left = new_id
            else:
                parent.right = new_id
            parent.children = [x for x in [parent.left, parent.right] if x] # Maintain children list for visualization ease

        # 4. AVL Balancing (Stub for now)
        if config.type == 'avl':
            self._balance_avl(new_id)

        return self.state

    def _balance_avl(self, node_id: str):
        # TODO: Implement AVL rotations
        pass

# --- Graph Engine ---
GRAPH_SESSIONS: Dict[str, GraphState] = {}

class GraphEngine:
    def __init__(self, session_id: str = "default"):
        self.session_id = session_id
        if session_id not in GRAPH_SESSIONS:
            # Default Directed
            GRAPH_SESSIONS[session_id] = GraphState(config=GraphConfig(directed=True, weighted=False))
        
        self.state = GRAPH_SESSIONS[session_id]

    def get_state(self) -> GraphState:
        return self.state

    def reset_graph(self, config: GraphConfig):
        self.state.nodes = {}
        self.state.adjacency_list = {}
        self.state.config = config
        GRAPH_SESSIONS[self.session_id] = self.state
        return self.state

    def add_node(self, node_id: str, value: Any):
        if not node_id: raise ValueError("Node ID required")
        if node_id in self.state.nodes:
            raise ValueError("Node ID already exists")
            
        self.state.nodes[node_id] = GraphNode(id=node_id, value=value)
        self.state.adjacency_list[node_id] = []
        return self.state

    def add_edge(self, source: str, target: str, weight: Optional[float] = None):
        if source not in self.state.nodes: raise ValueError(f"Source {source} not found")
        if target not in self.state.nodes: raise ValueError(f"Target {target} not found")
        
        # Check duplicate
        adj = self.state.adjacency_list.get(source, [])
        for edge in adj:
            if edge['target'] == target:
                raise ValueError("Edge already exists")

        # Add Edge
        new_edge = {"target": target, "weight": weight}
        self.state.adjacency_list[source].append(new_edge)
        
        if not self.state.config.directed:
            # Undirected = Bidirectional
            # Check reverse duplicate? (Should not exist if controlled properly)
            self.state.adjacency_list[target].append({"target": source, "weight": weight, "bidirectional": True})
            
        return self.state
