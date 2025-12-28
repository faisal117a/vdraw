from typing import List, Any
from backend.pdraw.schemas import SimulationRequest, SimulationResponse, SimulationStepResponse, DiagramState
from collections import deque

def simulate_pdraw(req: SimulationRequest) -> SimulationResponse:
    structure_type = req.structure
    impl = req.implementation
    
    # Initialize Structure
    # In this simulator, we just use a Python list or deque to MODEL the behavior
    # We track the "mirror" state to generate outputs.
    
    data = list(req.initial_values)
    current_state = None
    
    if structure_type == 'stack':
        # Stack Model
        current_state = list(data)
    elif structure_type == 'queue':
        # Queue Model
        current_state = deque(data)
    else:
        current_state = list(data)

    initial_diagram = _get_diagram(structure_type, current_state)
    initial_output = {"print": str(list(current_state)), "diagram": initial_diagram}

    steps_res = []
    
    for idx, op_step in enumerate(req.operations):
        op_name = op_step.op
        op_args = op_step.args
        
        step_res = _apply_operation(structure_type, impl, current_state, op_name, op_args, idx + 1)
        steps_res.append(step_res)
        
    return SimulationResponse(initial=initial_output, steps=steps_res)

def _get_diagram(stype, data) -> DiagramState:
    items = list(data)
    if stype == 'queue':
        return DiagramState(type=stype, items=items, front=0, rear=len(items)-1 if items else -1)
    return DiagramState(type=stype, items=items)

def _apply_operation(stype, impl, state, op, args, step_idx) -> SimulationStepResponse:
    # Default outputs
    explanation = ""
    complexity = "O(1)"
    memory = "Standard"
    status = "ok"
    error = None
    
    val = args.get('value')
    idx = args.get('index')
    
    try:
        if stype == 'stack':
            if op == 'push':
                state.append(val)
                explanation = f"Pushed {val} onto the top of the stack."
                complexity = "O(1)"
            elif op == 'pop':
                if not state: raise IndexError("Pop from empty stack")
                popped = state.pop()
                explanation = f"Popped {popped} from top."
                complexity = "O(1)"
            elif op == 'peek':
                if not state: raise IndexError("Peek from empty stack")
                explanation = f"Top element is {state[-1]}."
            elif op == 'is_empty':
                explanation = f"Is Empty: {len(state) == 0}"

        elif stype == 'queue':
            if op == 'enqueue':
                if isinstance(state, list) and impl == 'list':
                    # List Impl
                    state.append(val) # Enqueue at end
                    complexity = "O(n) amortized" # Actually O(1) for append, but let's say O(1)
                else: 
                    # Deque
                    state.append(val)
                explanation = f"Enqueued {val} at rear."
                
            elif op == 'dequeue':
                if not state: raise IndexError("Dequeue from empty queue")
                if isinstance(state, list) and impl == 'list':
                    popped = state.pop(0) # Slow!
                    complexity = "O(n) - Shift required"
                    explanation = f"Dequeued {popped} from front (Costly shift!)."
                else:
                    popped = state.popleft()
                    complexity = "O(1)"
                    explanation = f"Dequeued {popped} from front."
            elif op == 'front':
                if not state: raise IndexError("Queue is empty")
                explanation = f"Front element is {state[0]}."
            
        elif stype == 'list':
            if op == 'append':
                state.append(val)
                explanation = f"Appended {val}."
            elif op == 'insert':
                state.insert(idx, val)
                explanation = f"Inserted {val} at index {idx}."
                complexity = "O(n)"
            elif op == 'remove':
                state.remove(val)
                explanation = f"Removed first occurrence of {val}."
                complexity = "O(n)"
            elif op == 'pop':
                if idx is not None:
                    state.pop(idx)
                    explanation = f"Popped item at index {idx}."
                    complexity = "O(n)"
                else:
                    state.pop()
                    explanation = "Popped last item."
    
    except Exception as e:
        status = "error"
        error = str(e)
        explanation = f"Error: {str(e)}"

    return SimulationStepResponse(
        index=step_idx,
        operation=f"{op}({val if val is not None else ''})",
        status=status,
        print_output=str(list(state)),
        explanation=explanation,
        complexity=complexity,
        memory=memory,
        diagram=_get_diagram(stype, state),
        error_msg=error
    )
