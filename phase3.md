# Phase 3 — Trees & Graphs Visualization Architecture

This document defines **Phase 3** of the Data Structures Visualizer: a **separate, advanced dashboard** dedicated to **Trees and Graphs**, with strict rule enforcement, educational errors, step-by-step animations, and algorithm explanations.

This phase is intentionally isolated from Phase 1/2 to keep mental models clean for students.

---

## 1. High-Level Goals (Phase 3)

- Teach **Trees and Graphs** through *construction + traversal + analysis*
- Enforce **formal rules** (binary, BST, balanced, directed/undirected graphs)
- Provide **step-by-step animated traversal** (critical requirement)
- Show **why** an algorithm behaves the way it does
- Use **SVG-based visualization** for precision and learning clarity
- Prepare architecture for future algorithms (Dijkstra, MST, etc.)

---

## 2. Entry Point & Navigation

### New Dashboard: `TGDraw`

- Top-level toggle / route:
  - Phase 1: Linear DS Playground
  - Phase 3: **TGDraw (Tree & Graph Drawer)**

TGDraw contains **two isolated workspaces**:
- Tree Workspace
- Graph Workspace

No shared state between them.

---

## 3. Tree Workspace — Architecture

### 3.1 Tree Type Selection (Mandatory)

User must select exactly one:

- Generic Tree
- Binary Tree
- Binary Search Tree (BST)
- BST as Balanced Tree (AVL simplified)

Tree type determines **rules, validation, traversal availability**.

---

### 3.2 Root Node Creation

- Single input field
- Auto-detect type:
  - `12` → number
  - `pak` → string
  - `"12"` → string

Rules:
- Only one root allowed
- Root cannot be reassigned

---

### 3.3 Node Addition System

#### UI Controls

- Dropdown: **Select Parent Node**
  - Shows hierarchy (indented)
- Input: **New Node Value** (unique ID enforced)
- If Binary/BST:
  - Radio: Left / Right child

#### Validation Rules

Applied before insertion:

| Tree Type | Rules Enforced |
|---------|---------------|
| Generic | No cycles, one parent only |
| Binary | Max 2 children |
| BST | Left < Parent < Right |
| BST Balanced | Same as BST + balance check |

#### Error & Warning Handling

- Rule violation → ❌ insertion blocked + educational error
- Balance violation (AVL mode, manual):
  - ⚠️ insertion allowed
  - warning shown in playground

Example messages:
- "Each node in a binary tree can have at most two children."
- "Left subtree values must be smaller than the parent node."
- "This insertion makes the tree unbalanced (height difference > 1)."

---

### 3.4 Auto-Balance Toggle (BST Balanced Only)

- Default: **Manual mode** (warn only)
- Toggle: **Auto-Balance**

Behavior:
- Original (unbalanced) tree remains
- New balanced tree rendered **below**
- Rotation steps explained textually

Educational benefit: compare structures visually.

---

### 3.5 Tree Playground Visualization

Rendered via **SVG (manual layout)**:

- Large readable node circles
- Clear parent–child edges
- Root highlighted
- Optional animation on insertion

#### Auto-Computed Tree Metadata

Displayed alongside tree:

- Root node
- Total nodes
- Total edges
- Leaf nodes (count + list)
- Height of tree
- Balanced: Yes / No

---

### 3.6 Tree Traversal Engine

#### Available Traversals

| Traversal | Availability |
|---------|--------------|
| BFS (Level Order) | All trees |
| DFS | All trees |
| Inorder | Binary trees only |
| Preorder | Binary trees only |
| Postorder | Binary trees only |

Invalid options are disabled with explanation.

#### Traversal Controls

- Select traversal
- Select **start node** (mandatory)
- Apply traversal

#### Traversal Output

- Step-by-step animation:
  - current node highlighted
  - visited nodes colored
- Output list shown incrementally

#### Explanation Panel (Critical)

For each traversal:
- Why this traversal?
- Under-the-hood mechanism (stack / queue / recursion)
- Time complexity
- Space complexity

---

## 4. Graph Workspace — Architecture

### 4.1 Graph Type Selection

Mandatory options:

- Directed / Undirected
- Weighted / Unweighted

Once edges exist, type is locked.

---

### 4.2 Vertex Management

- Input: add vertex (unique ID)
- Mixed type allowed
- Duplicate vertices blocked

---

### 4.3 Edge Creation System

#### UI Controls

- Dropdown: From vertex
- Dropdown: To vertex
- If weighted: weight input
- Add edge button

#### Validation Rules

- No self-loop (optional toggle later)
- Duplicate edges blocked
- Directed vs undirected enforced

Educational error examples:
- "An undirected graph stores a single edge between two nodes."
- "Weight is required for a weighted graph."

---

### 4.4 Graph Playground Visualization

SVG-based rendering:

- Nodes as circles
- Edges as lines or arrows
- Weights displayed on edges
- Color-coded:
  - Directed edges
  - Undirected edges
  - Traversal path

#### Graph Metadata Panel

- Total nodes
- Total edges
- Degree of each node
- In-degree / Out-degree (directed)

---

### 4.5 Graph Algorithms (Phase 3 Scope)

#### Supported Algorithms

- BFS
- DFS
- Shortest Path (intro-level, Dijkstra)
- Cycle Detection

#### Controls

- Select algorithm
- Select start node
- Apply

#### Algorithm Output

- Step-by-step node highlight animation
- Visited order
- Data structure used (queue / stack / priority queue)

#### Explanation Panel

- Why this algorithm?
- When to use it
- Time & space complexity

---

## 5. Backend Architecture (Phase 3)

### 5.1 New Engines

```
backend/app/engine/
  tree_engine/
    base.py
    generic_tree.py
    binary_tree.py
    bst.py
    avl.py
    traversals.py
  graph_engine/
    base.py
    adjacency_list.py
    bfs.py
    dfs.py
    dijkstra.py
    cycle.py
```

### 5.2 Tree Engine Responsibilities

- Enforce structure rules
- Compute metadata
- Produce SVG layout coordinates
- Produce traversal steps

### 5.3 Graph Engine Responsibilities

- Maintain adjacency list
- Validate edges
- Run algorithms
- Emit step-by-step traversal states

---

## 6. API Design (Phase 3)

### Endpoints

- `POST /api/tg/tree/create-root`
- `POST /api/tg/tree/add-node`
- `POST /api/tg/tree/traverse`

- `POST /api/tg/graph/add-node`
- `POST /api/tg/graph/add-edge`
- `POST /api/tg/graph/run-algorithm`

All responses return:
- current state
- svg payload
- step list (for animation)
- explanations

---

## 7. Frontend Architecture (Phase 3)

```
frontend/src/tgdraw/
  Tree/
    TreeSidebar.tsx
    TreeCanvas.tsx
    TreeMetadata.tsx
    TraversalPanel.tsx
    ExplanationPanel.tsx
  Graph/
    GraphSidebar.tsx
    GraphCanvas.tsx
    GraphMetadata.tsx
    AlgorithmPanel.tsx
    ExplanationPanel.tsx
  svg/
    Node.tsx
    Edge.tsx
    Arrow.tsx
```

---

## 8. Animation Model (Critical Requirement)

- SVG node states:
  - default
  - active
  - visited

- GSAP timeline drives:
  - node highlight
  - edge highlight
  - output list progression

Animation speed controlled by user.

---

## 9. Educational Design Principles

- Every error is a lesson
- Every traversal explains *why*, not just *what*
- Visual + textual reinforcement
- Compare structures side-by-side (unbalanced vs balanced)

---

## 10. Phase-4 Ready Hooks

- Red-Black Trees
- MST (Prim / Kruskal)
- A* search
- Topological sort
- Graph adjacency matrix toggle

---

## 11. Summary

Phase 3 transforms the app from a DS playground into a **full learning laboratory**.

- Clean separation of concerns
- Strong rule enforcement
- Step-by-step animations
- Deep explanations

This architecture is scalable, testable, and *classroom-grade*.

