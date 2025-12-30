from typing import List, Dict, Any
from .schemas import TreeNode

class TraversalStep:
    def __init__(self, node_id: str, action: str, description: str):
        self.node_id = node_id
        self.action = action # 'visit', 'enqueue', 'pop'
        self.description = description
    
    def to_dict(self):
        return {
            "node_id": self.node_id,
            "action": self.action,
            "description": self.description
        }

class TreeTraversals:
    def __init__(self, nodes: Dict[str, TreeNode], root_id: str):
        self.nodes = nodes
        self.root_id = root_id
        self.steps = []

    def log(self, node_id: str, action: str, desc: str):
        self.steps.append(TraversalStep(node_id, action, desc).to_dict())

    def bfs(self):
        if not self.root_id: return []
        
        queue = [self.root_id]
        self.log(self.root_id, 'enqueue', f"Start BFS: Enqueue root {self.nodes[self.root_id].value}")
        
        while queue:
            curr_id = queue.pop(0)
            node = self.nodes[curr_id]
            self.log(curr_id, 'visit', f"Visit {node.value}")
            
            # Children
            # For Binary: Left then Right
            children = []
            if node.left: children.append(node.left)
            if node.right: children.append(node.right)
            # Generic fallback
            if not children and node.children: children = node.children
            
            for child_id in children:
                child = self.nodes[child_id]
                queue.append(child_id)
                self.log(child_id, 'enqueue', f"Enqueue {child.value}")
                
        return self.steps

    def dfs_inorder(self):
        # Left -> Root -> Right
        self._inorder_recursive(self.root_id)
        return self.steps

    def _inorder_recursive(self, node_id):
        if not node_id: return
        node = self.nodes[node_id]
        
        if node.left:
            self.log(node.left, 'explore', f"Go Left from {node.value}")
            self._inorder_recursive(node.left)
            
        self.log(node_id, 'visit', f"Visit {node.value}")
        
        if node.right:
            self.log(node.right, 'explore', f"Go Right from {node.value}")
            self._inorder_recursive(node.right)

    def dfs_preorder(self):
        # Root -> Left -> Right
        self._preorder_recursive(self.root_id)
        return self.steps

    def _preorder_recursive(self, node_id):
        if not node_id: return
        node = self.nodes[node_id]
        
        self.log(node_id, 'visit', f"Visit {node.value}")
        
        if node.left:
             self.log(node.left, 'explore', f"Go Left from {node.value}")
             self._preorder_recursive(node.left)
        
        if node.right:
             self.log(node.right, 'explore', f"Go Right from {node.value}")
             self._preorder_recursive(node.right)

        # Generic Children Fallback
        if not node.left and not node.right and node.children:
             for child_id in node.children:
                 self.log(child_id, 'explore', f"Go to child {self.nodes[child_id].value}")
                 self._preorder_recursive(child_id)

    def dfs_postorder(self):
        # Left -> Right -> Root
        self._postorder_recursive(self.root_id)
        return self.steps

    def _postorder_recursive(self, node_id):
        if not node_id: return
        node = self.nodes[node_id]
        
        if node.left:
             self.log(node.left, 'explore', f"Go Left from {node.value}")
             self._postorder_recursive(node.left)
        
        if node.right:
             self.log(node.right, 'explore', f"Go Right from {node.value}")
             self._postorder_recursive(node.right)

        # Generic Children Fallback
        if not node.left and not node.right and node.children:
             for child_id in node.children:
                 self.log(child_id, 'explore', f"Go to child {self.nodes[child_id].value}")
                 self._postorder_recursive(child_id)
             
        self.log(node_id, 'visit', f"Visit {node.value}")

from .schemas import GraphNode
import heapq

class GraphTraversals:
    def __init__(self, nodes: Dict[str, GraphNode], adjacency: Dict[str, List[dict]]):
        self.nodes = nodes
        self.adjacency = adjacency
        self.steps = []

    def log(self, node_id: str, action: str, desc: str):
         self.steps.append({"node_id": node_id, "action": action, "description": desc})

    def bfs(self, start_id: str):
        if start_id not in self.nodes: return []
        
        queue = [start_id]
        visited = set([start_id])
        self.log(start_id, 'enqueue', f"Start BFS at {start_id}")
        
        while queue:
            curr = queue.pop(0)
            self.log(curr, 'visit', f"Visit {curr}")
            
            # Neighbors
            neighbors = self.adjacency.get(curr, [])
            # Sort for deterministic output visualization
            neighbors.sort(key=lambda x: x['target']) 
            
            for edge in neighbors:
                tgt = edge['target']
                if tgt not in visited:
                    visited.add(tgt)
                    queue.append(tgt)
                    self.log(tgt, 'enqueue', f"Queue neighbor {tgt}")
                    
        return self.steps

    def dfs(self, start_id: str):
        if start_id not in self.nodes: return []
        self.visited_dfs = set()
        self._dfs_recursive(start_id)
        return self.steps

    def _dfs_recursive(self, curr: str):
        self.visited_dfs.add(curr)
        self.log(curr, 'visit', f"Visit {curr}")
        
        neighbors = self.adjacency.get(curr, [])
        neighbors.sort(key=lambda x: x['target'])
        
        for edge in neighbors:
            tgt = edge['target']
            if tgt not in self.visited_dfs:
                self.log(tgt, 'explore', f"Explore edge {curr}->{tgt}")
                self._dfs_recursive(tgt)

    def dijkstra(self, start_id: str):
        # Priority Queue: (cost, node_id, path_from)
        pq = [(0, start_id, None)] 
        distances = {node: float('inf') for node in self.nodes}
        distances[start_id] = 0
        visited = set()
        
        self.log(start_id, 'enqueue', f"Init Dijkstra at {start_id}, dist=0")
        
        while pq:
            curr_dist, curr, prev = heapq.heappop(pq)
            
            if curr in visited: continue
            visited.add(curr)
            
            self.log(curr, 'visit', f"Settle {curr} (Dist: {curr_dist})")
            
            neighbors = self.adjacency.get(curr, [])
            for edge in neighbors:
                tgt = edge['target']
                weight = edge.get('weight', 1) or 1 # Default 1 if None
                
                if tgt not in visited:
                    new_dist = curr_dist + weight
                    if new_dist < distances[tgt]:
                        distances[tgt] = new_dist
                        heapq.heappush(pq, (new_dist, tgt, curr))
                        self.log(tgt, 'enqueue', f"Relax {tgt}: New Dist {new_dist}")
        
        return self.steps
