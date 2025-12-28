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
    elif structure_type == 'tuple':
        # Tuple is immutable, but for visualization of "operations" like count/index, we hold a tuple.
        current_state = tuple(data)
    else:
        current_state = list(data)

    initial_diagram = _get_diagram(structure_type, current_state)
    item_list = list(current_state) if not isinstance(current_state, list) else current_state
    initial_output = {"print": str(item_list), "diagram": initial_diagram}

    steps_res = []
    
    for idx, op_step in enumerate(req.operations):
        op_name = op_step.op
        op_args = op_step.args
        
        # Tuple special handling: Simulator returns NEW state if it was immutable and changed (not possible for tuple ops)
        step_res, new_state = _apply_operation(structure_type, impl, current_state, op_name, op_args, idx + 1)
        current_state = new_state
        steps_res.append(step_res)
        
    return SimulationResponse(initial=initial_output, steps=steps_res)

def _get_diagram(stype, data) -> DiagramState:
    items = list(data)
    if stype == 'queue':
        return DiagramState(type=stype, items=items, front=0, rear=len(items)-1 if items else -1)
    return DiagramState(type=stype, items=items)

def _apply_operation(stype, impl, state, op, args, step_idx) -> (SimulationStepResponse, Any):
    # Default outputs
    explanation = ""
    complexity = "O(1)"
    memory = "Standard"
    status = "ok"
    error = None
    
    val = args.get('value')
    idx = args.get('index')
    
    # helper to get mutable form if needed
    mutable_state = state
    if stype == 'tuple' and op not in ['index', 'count']:
        # theoretically tuple doesn't support mutation.
        pass

    try:
        if stype == 'stack':
            if op == 'push':
                state.append(val)
                method_name = "append" if impl == 'list' or impl == 'collections.deque' else "push"
                explanation = f"{method_name}({repr(val)}). Pushed {repr(val)} onto top."
                complexity = "O(1)"
            elif op == 'pop':
                if not state: raise IndexError("Pop from empty stack")
                popped = state.pop()
                method_name = "pop"
                explanation = f"{method_name}(). Popped {repr(popped)} from top."
                complexity = "O(1)"
            elif op == 'peek':
                if not state: raise IndexError("Peek from empty stack")
                method_name = "[-1]"
                explanation = f"Accessed top element {repr(state[-1])}."
            elif op == 'is_empty':
                explanation = f"Checked if empty: {len(state) == 0}"
                
        elif stype == 'queue':
            is_list_impl = isinstance(state, list) and impl == 'list'
            
            if op == 'enqueue':
                if is_list_impl:
                    state.append(val) 
                    complexity = "O(n) amortized" # Actually O(1)
                    method = "append"
                else: 
                    state.append(val)
                    complexity = "O(1)"
                    method = "append" if impl == 'collections.deque' else "put"
                
                explanation = f"Used {method}({repr(val)}). Enqueued {repr(val)} at rear."
                
            elif op == 'dequeue':
                if not state: raise IndexError("Dequeue from empty queue")
                
                if is_list_impl:
                    popped = state.pop(0) 
                    complexity = "O(n) - CRITICAL: Shifting Elements!"
                    method = "pop(0)"
                    explanation = f"Executed {method}. Removed {repr(popped)}. **Inefficient**: All elements shifted left."
                else:
                    popped = state.popleft()
                    complexity = "O(1)"
                    method = "popleft" if impl == 'collections.deque' else "get"
                    explanation = f"Executed {method}(). Removed {repr(popped)}. Efficient pointer update."
                    
            elif op == 'front':
                if not state: raise IndexError("Queue is empty")
                explanation = f"Front element is {repr(state[0])}."

            elif op == 'rear':
                if not state: raise IndexError("Queue is empty")
                explanation = f"Rear element is {repr(state[-1])}."
            
        elif stype == 'list':
            if op == 'append':
                state.append(val)
                explanation = f"append({repr(val)}). Appended to end."
            elif op == 'extend':
                # Parse if val is list-like string or just append single? 
                # Simulator expects 'iterable' for extend, typically a list.
                # If parsed as string, might extend chars.
                # If parsed as list, extends items.
                if isinstance(val, (list, tuple, str)):
                    # Handle string "1,2" -> [1,2]? No, frontend sends typed.
                    # If frontend sends "123", extend adds '1','2','3'.
                    state.extend(val)
                    explanation = f"extend({repr(val)}). Added elements from iterable."
                    complexity = "O(k)"
                else:
                     explanation = "Error: extend requires iterable."
                     status = "error"
            elif op == 'insert':
                state.insert(idx, val)
                explanation = f"insert({idx}, {repr(val)}). Shifted elements to right."
                complexity = "O(n)"
            elif op == 'remove':
                state.remove(val)
                explanation = f"remove({repr(val)}). Removed first occurrence. Shifted elements left."
                complexity = "O(n)"
            elif op == 'pop':
                if idx is not None:
                    state.pop(idx)
                    explanation = f"pop({idx}). Removed item at index. Shifted elements left."
                    complexity = "O(n)"
                else:
                    state.pop()
                    explanation = "pop(). Removed last item."
            elif op == 'clear':
                state.clear()
                explanation = "clear(). Removed all items."
                complexity = "O(1)"
            elif op == 'index':
                if val in state:
                    i = state.index(val)
                    explanation = f"index({repr(val)}) -> {i}. Found at index."
                else:
                    explanation = f"index({repr(val)}). Value not found."
                    status = "error"
                complexity = "O(n)"
            elif op == 'count':
                c = state.count(val)
                explanation = f"count({repr(val)}) -> {c}. Found {c} occurrences."
                complexity = "O(n)"
            elif op == 'sort':
                state.sort() 
                explanation = "sort(). Sorted list in-place."
                complexity = "O(n log n)"
            elif op == 'reverse':
                state.reverse() 
                explanation = "reverse(). Reversed list in-place."
                complexity = "O(n)"
                
        elif stype == 'tuple':
            # Tuple operations are read-only
            if op == 'index':
                i = state.index(val)
                explanation = f"index({repr(val)}) -> {i}."
                complexity = "O(n)"
            elif op == 'count':
                c = state.count(val)
                explanation = f"count({repr(val)}) -> {c}."
                complexity = "O(n)"
    
    except Exception as e:
        status = "error"
        error = str(e)
        explanation = f"Error: {str(e)}"

    # Convert state to serializable for display
    display_state = list(state)
    
    return SimulationStepResponse(
        index=step_idx,
        operation=f"{op}",
        status=status,
        print_output=str(display_state),
        explanation=explanation,
        complexity=complexity,
        memory=memory,
        diagram=_get_diagram(stype, display_state),
        error_msg=error
    ), state
