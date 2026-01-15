from backend.pdraw.schemas import CatalogResponse, CatalogItem, CatalogOperation

def get_pdraw_catalog() -> CatalogResponse:
    return CatalogResponse(
        structures=[
            CatalogItem(
                id="stack",
                label="Stack (LIFO)",
                implementations=["list", "collections.deque"],
                operations=[
                    CatalogOperation(
                        id="push", 
                        label="Push", 
                        params=[{"name": "value", "type": "any", "required": True}], 
                        complexity="O(1)"
                    ),
                    CatalogOperation(
                        id="pop", 
                        label="Pop", 
                        params=[], 
                        complexity="O(1)"
                    ),
                    CatalogOperation(
                        id="peek", 
                        label="Peek", 
                        params=[], 
                        complexity="O(1)"
                    ),
                    CatalogOperation(
                        id="is_empty", 
                        label="Is Empty?", 
                        params=[], 
                        complexity="O(1)"
                    )
                ]
            ),
            CatalogItem(
                id="queue",
                label="Queue (FIFO)",
                implementations=["list", "collections.deque", "queue.Queue"],
                operations=[
                    CatalogOperation(
                        id="enqueue", 
                        label="Enqueue", 
                        params=[{"name": "value", "type": "any", "required": True}], 
                        complexity="O(1) [deque] / O(n) [list]"
                    ),
                    CatalogOperation(
                        id="dequeue", 
                        label="Dequeue", 
                        params=[], 
                        complexity="O(1) [deque] / O(n) [list]"
                    ),
                    CatalogOperation(
                        id="front", 
                        label="Front", 
                        params=[], 
                        complexity="O(1)"
                    ),
                     CatalogOperation(
                        id="rear", 
                        label="Rear", 
                        params=[], 
                        complexity="O(1)"
                    )
                ]
            ),
            CatalogItem(
                id="list",
                label="List (Array)",
                implementations=["list"],
                operations=[
                    CatalogOperation(
                        id="append", 
                        label="Append", 
                        params=[{"name": "value", "type": "any", "required": True}], 
                        complexity="O(1)"
                    ),
                    CatalogOperation(
                        id="extend",
                        label="Extend",
                        params=[{"name": "iterable", "type": "any", "required": True}],
                        complexity="O(k)"
                    ),
                    CatalogOperation(
                        id="insert", 
                        label="Insert", 
                        params=[{"name": "index", "type": "int", "required": True}, {"name": "value", "type": "any", "required": True}], 
                        complexity="O(n)"
                    ),
                    CatalogOperation(
                        id="pop", 
                        label="Pop Index", 
                        params=[{"name": "index", "type": "int", "required": False}], 
                        complexity="O(k)"
                    ),
                    CatalogOperation(
                        id="remove", 
                        label="Remove Value", 
                        params=[{"name": "value", "type": "any", "required": True}], 
                        complexity="O(n)"
                    ),
                    CatalogOperation(
                        id="clear",
                        label="Clear",
                        params=[],
                        complexity="O(1)"
                    ),
                    CatalogOperation(
                        id="index",
                        label="Index",
                        params=[{"name": "value", "type": "any", "required": True}],
                        complexity="O(n)"
                    ),
                    CatalogOperation(
                        id="count",
                        label="Count",
                        params=[{"name": "value", "type": "any", "required": True}],
                        complexity="O(n)"
                    ),
                    CatalogOperation(
                        id="sort",
                        label="Sort",
                        params=[],
                        complexity="O(n log n)"
                    ),
                    CatalogOperation(
                        id="slice",
                        label="Slice",
                        params=[
                            {"name": "start", "type": "int", "required": False},
                            {"name": "stop", "type": "int", "required": False},
                            {"name": "step", "type": "int", "required": False}
                        ],
                        complexity="O(k)"
                    )
                ]
            ),
            CatalogItem(
                id="tuple",
                label="Tuple",
                implementations=["tuple"],
                operations=[
                     CatalogOperation(
                        id="index", 
                        label="Index of Value", 
                        params=[{"name": "value", "type": "any", "required": True}], 
                        complexity="O(n)"
                    ),
                    CatalogOperation(
                        id="count", 
                        label="Count Value", 
                        params=[{"name": "value", "type": "any", "required": True}], 
                        complexity="O(n)"
                    ),
                    CatalogOperation(
                        id="slice",
                        label="Slice",
                        params=[
                            {"name": "start", "type": "int", "required": False},
                            {"name": "stop", "type": "int", "required": False},
                            {"name": "step", "type": "int", "required": False}
                        ],
                        complexity="O(k)"
                    ),
                    CatalogOperation(
                        id="len",
                        label="Length",
                        params=[],
                        complexity="O(1)"
                    )
                ]
            )
        ]
    )
