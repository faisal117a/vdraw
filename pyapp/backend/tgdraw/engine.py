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
        self.calculate_stats()
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
                    except ValueError:
                        # Fallback to string comparison if not numbers
                        p_val = str(parent.value)
                        n_val = str(value)
                    
                    err_prefix = "AVL Constraint" if config.type == 'avl' else "BST Rule Violation"
                    if direction == 'left' and not (n_val < p_val):
                         raise ValueError(f"{err_prefix}: {n_val} must be < {p_val} (Left Child)")
                    if direction == 'right' and not (n_val > p_val):
                         raise ValueError(f"{err_prefix}: {n_val} must be > {p_val} (Right Child)")

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

        self.calculate_stats()
        
        # 4. Enforce AVL/Balance Constraint (Manual Prevention)
        # "Prevents becoming long like a chain" -> Balance Constraint
        if config and config.type == 'avl':
             if not self.state.stats.is_balanced:
                 # Rollback
                 del self.state.nodes[new_id]
                 if config.type == 'generic':
                     parent.children.remove(new_id)
                 else:
                     if direction == 'left': parent.left = None
                     else: parent.right = None
                     parent.children = [x for x in [parent.left, parent.right] if x]
                 
                 # Re-calc stats after rollback
                 self.calculate_stats()
                 raise ValueError("AVL Violation: Tree creates a chain/imbalance (Height Diff > 1). Add nodes to shorter side.")

        return self.state

    def calculate_stats(self):
        if not self.state.root_id:
            return

        from .schemas import TreeStats
        
        nodes = self.state.nodes
        root_id = self.state.root_id
        
        # BFS for Height and Leaves
        queue = [(root_id, 0)] # (id, level)
        max_height = 0
        leaves = []
        count = 0
        
        while queue:
            nid, level = queue.pop(0)
            count += 1
            node = nodes[nid]
            max_height = max(max_height, level)
            
            children = node.children
            if not children:
                leaves.append(node.value) # Using value for display
            
            for child_id in children:
                queue.append((child_id, level + 1))
                
        # Basic Balance Check (Simple Height Diff)
        # Real AVL check would be recursive, but global "Is Balanced" usually refers to whole tree.
        # For simplicity, we check if max leaf depth - min leaf depth <= 1 (approx) or use AVL factors.
        # Let's start with True, and update if we find a node with |L-R| > 1
        is_balanced = True
        
        def check_balance(nid):
            if not nid: return 0, True
            node = nodes[nid]
            
            l_h, l_b = check_balance(node.left)
            r_h, r_b = check_balance(node.right)
            
            node.height = 1 + max(l_h, r_h) # Update Individual Node Height
            node.balance_factor = l_h - r_h
            
            current_balanced = abs(l_h - r_h) <= 1
            return node.height, l_b and r_b and current_balanced

        # Only run rigorous balance check for binary variations
        config = TREE_CONFIGS.get(self.session_id)
        if config and config.type in ['binary', 'bst', 'avl']:
            _, is_balanced = check_balance(root_id)
        else:
            is_balanced = True # Generic trees definition logic varies
            
        self.state.stats = TreeStats(
            total_nodes=len(nodes),
            height=max_height, # 0-indexed edges or nodes? Usually edges. Current BFS level 0 is root. So height is max_level.
            leaf_count=len(leaves),
            leaves=[str(l) for l in leaves],
            is_balanced=is_balanced,
            total_edges=len(nodes) - 1 # Tree Property
        )

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
        self.calculate_stats()
        return self.state

    def add_node(self, node_id: str, value: Any):
        if not node_id: raise ValueError("Node ID required")
        if node_id in self.state.nodes:
            raise ValueError("Node ID already exists")
            
        self.state.nodes[node_id] = GraphNode(id=node_id, value=value)
        self.state.adjacency_list[node_id] = []
        self.calculate_stats()
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
            self.state.adjacency_list[target].append({"target": source, "weight": weight, "bidirectional": True})
            
        self.calculate_stats()
        return self.state

    def remove_edge(self, source: str, target: str):
        if source in self.state.nodes:
            self.state.adjacency_list[source] = [e for e in self.state.adjacency_list[source] if e['target'] != target]
            
        if not self.state.config.directed:
             # Remove reverse as well
             if target in self.state.nodes:
                 self.state.adjacency_list[target] = [e for e in self.state.adjacency_list[target] if e['target'] != source]
                 
        self.calculate_stats()
        return self.state

    def calculate_stats(self):
        nodes = self.state.nodes
        adj = self.state.adjacency_list
        is_directed = self.state.config.directed
        
        total_vertices = len(nodes)
        
        # Calculate Edges
        # If undirected, simple sum of list lengths / 2? Or just count unique?
        # Adjacency list stores bidirectional twice.
        raw_edge_count = sum(len(neighbors) for neighbors in adj.values())
        total_edges = raw_edge_count if is_directed else raw_edge_count // 2
        
        degrees = {}
        for nid in nodes:
            out_degree = len(adj.get(nid, []))
            in_degree = 0
            if is_directed:
                for other_id, neighbors in adj.items():
                    if any(e['target'] == nid for e in neighbors):
                        in_degree += 1
                total_degree = in_degree + out_degree
            else:
                # For undirected, in = out usually, but concept is just "degree"
                in_degree = out_degree 
                total_degree = out_degree # Simplified view for undirected
            
            degrees[nid] = {"in": in_degree, "out": out_degree, "total": total_degree}
            
        from .schemas import GraphStats
        self.state.stats = GraphStats(
            total_vertices=total_vertices,
            total_edges=total_edges,
            degrees=degrees
        )
