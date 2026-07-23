# Jawla (جولة) — BPMN Workflow Diagrams

```mermaid
flowchart TB
    subgraph Daily_Rep_Workflow["Daily Rep Workflow"]
        A[Rep opens /app] --> B{Work session active?}
        B -->|No| C[Start Work: GPS check-in]
        B -->|Yes| D[Show today's assigned visits]
        C --> D
        D --> E[Select customer card]
        E --> F{GPS geofence check}
        F -->|Within 1 km| G[Auto-confirm arrival ✅]
        F -->|Outside 1 km| H[Show warning + Manual Confirm]
        H --> G
        G --> I[Submit visit report]
        I --> J[Operations menu]
        J --> K[Sell]
        J --> L[Collect]
        J --> M[Return]
        J --> N[Price Quotation]
        J --> O[Proforma]
        J --> P[Complaint]
        J --> Q[OOS Request]
        J --> R[Purchase Request]
        K --> S{More operations?}
        L --> S
        M --> S
        N --> S
        O --> S
        P --> S
        Q --> S
        R --> S
        S -->|Yes| J
        S -->|No| T[End Visit]
        T --> U{More visits?}
        U -->|Yes| E
        U -->|No| V[End Day: session summary]
    end
```

```mermaid
flowchart TB
    subgraph Pricing_Chain["Pricing Chain"]
        A[Rep: select product + qty] --> B[Submit price_quotation_request]
        B --> C[Status: requested]
        C --> D[Alarm: price_quotation_requested]
        D --> E[Sales Manager sees request]

        subgraph Manager_Prices["Manager Prices"]
            F[Manager sees base_price from Accounts]
            G[Manager sets: final_base_price, manager_plus, manager_minus]
            H[Manager sets: rep_plus, rep_minus]
            I{Validate: rep_plus <= manager_plus?}
            J{Validate: rep_minus <= manager_minus?}
            K[Status: priced]
            F --> G --> H --> I
            I -->|Yes| J
            I -->|No| G
            J -->|Yes| K
            J -->|No| G
        end

        E --> Manager_Prices
        K --> L[Rep sees allowed range]
        L --> M[Rep enters final price]
        M --> N{Valid: within rep range?}
        N -->|Yes| O[Status: confirmed]
        N -->|No| M
        O --> P[Proforma created from quotation]

        subgraph Proforma_Flow["Proforma → Invoice"]
            Q[Create proforma: incl bank info]
            R[Status: draft]
            S[Send to customer → sent]
            T{Customer accepts?}
            U[Convert to invoice → submitted]
            V[Stock deducted]
            W[PDF with QR generated]

            Q --> R --> S --> T
            T -->|Yes| U --> V --> W
            T -->|No| X[Status: cancelled]
        end

        P --> Proforma_Flow
    end
```

```mermaid
flowchart TB
    subgraph Procurement_Flow["Procurement Flow"]
        A[Rep: submit purchase request] --> B[Alarm: purchase_request]
        B --> C{Sales manager vetos?}
        C -->|Yes| D[Status: rejected]
        C -->|No| E[Status: approved → Purchasing sees]

        E --> F[Purchasing requests quotes]
        F --> G[Supplier A: quote]
        F --> H[Supplier B: quote]
        F --> I[Supplier C: quote]

        subgraph Comparison["Supplier Comparison"]
            J[Side-by-side view]
            K[Compare: price, currency, payment terms, delivery]
            L[Select best offer]
            M[Status: accepted for chosen]
            N[Status: rejected for others]

            J --> K --> L --> M --> N
        end

        G --> Comparison
        H --> Comparison
        I --> Comparison

        N --> O[Create Purchase Order]
        O --> P[Status: draft]
        P --> Q[Send to supplier → sent]
        Q --> R{Supplier confirms?}
        R -->|Yes| S[Status: confirmed]
        R -->|No| T[Cancel PO]

        S --> U[Receive partial shipment]
        U --> V[Update received_qty on PO items]
        V --> W{All items received?}
        W -->|Yes| X[Status: received]
        W -->|No| U
    end
```

```mermaid
flowchart TB
    subgraph GIT_Landed_Cost["Goods in Transit + Landed Cost"]
        A[Purchasing: create GIT shipment] --> B[Status: in_transit]
        B --> C[Record: items + costs]
        C --> D[Set estimated_arrival_date]

        D --> E{Shipment progressing}
        E --> F[Status: at_customs]
        F --> G[Status: cleared]

        G --> H{Warehouse receives?}
        H -->|No| I{Past ETA?}
        I -->|Yes| J[Critical alarm: GIT delayed]
        I -->|No| E

        H -->|Yes| K[Status: received]

        subgraph Receipt["Goods Receipt"]
            L[FOR EACH GIT item: insert stock_movement +qty]
            M[Update stocks: +qty]
            N[Calculate total landed cost]
            O[Distribute proportionally by qty]
            P[Update product cost price: moving average]

            K --> L --> M --> N --> O --> P
        end

        Receipt --> Q[GIT closed]
    end
```

```mermaid
flowchart TB
    subgraph Customer_Lifecycle["Customer Lifecycle"]
        A[Rep adds customer] --> B[Status: pending]
        B --> C[Alarm: new_customer_pending]
        C --> D[Sales Manager reviews]

        subgraph Duplicate_Check["Duplicate Check"]
            E[Check: phone unique?]
            F[Check: name_ar unique?]
            G{Match found?}

            E & F --> G
        end

        D --> Duplicate_Check
        G -->|Yes| H[Flag as duplicate → reject]
        G -->|No| I[Manager approves]
        I --> J[Status: approved]
        H --> K[Status: rejected]
        K --> L[Notify rep with reason]

        J --> M[Customer can now transact]

        subgraph Transactions["Customer can:"]
            N[Receive sales visits]
            O[Get price quotations]
            P[Receive invoices]
            Q[Make returns]
        end

        M --> N & O & P & Q

        subgraph Complaints["Complaint Flow"]
            R[Rep or Customer files complaint]
            S[Critical alarm created]
            T[Assigned to Sales Manager]
            U[Status: in_progress]
            V[Resolution recorded]
            W[Status: resolved]
            X[Customer notified]

            R --> S --> T --> U --> V --> W --> X
        end
    end
```

```mermaid
flowchart TB
    subgraph Alarm_System["Alarm System"]
        A[Trigger events] --> B{Auto-generate alarm}

        C[OOS Request] -->|critical| B
        D[Complaint] -->|critical| B
        E[New customer pending] -->|warning| B
        F[Price quotation requested] -->|warning| B
        G[Purchase request submitted] -->|info| B
        H[GIT past ETA] -->|critical| B
        I[Batch expiring in 30d] -->|warning| B

        B --> J[Alarm record created]
        J --> K[Severity color: red/yellow/green]
        K --> L[Sales Manager acknowledged?]

        L -->|No| M[Alarm remains unread]
        M --> N[Shows in dashboard with badge]

        L -->|Yes| O[Acknowledge alarm]
        O --> P[Assign to user]
        P --> Q[Add resolution note]
        Q --> R[Resolve alarm]
        R --> S[Closed]
    end
```

```mermaid
flowchart TB
    subgraph Atomic_Sale["Atomic Sale Transaction"]
        A[Rep: Sell operation] --> B{Validations pass?}

        B -->|Van stock >= qty| C{Price within range?}
        B -->|Insufficient stock| D[Block: error message]

        C -->|Within range| E{Pending customer?}
        C -->|Outside range| F[Block: error message]

        E -->|No| G{Batch tracked product?}
        E -->|Yes| H[Block: customer pending]

        G -->|Yes, batch selected| I{Batch provided?}
        G -->|No| J[Skip batch]
        I -->|Yes| J
        I -->|No| K[Block: batch required]

        J --> L[BEGIN DB::transaction]

        L --> M[INSERT invoice row]
        M --> N[INSERT invoice_items rows]
        N --> O[FOR EACH item: UPDATE stocks SET qty -= qty]
        O --> P{Any negative stock?}
        P -->|Yes| Q[ROLLBACK → error]
        P -->|No| R[INSERT stock_movements for each]
        R --> S[UPDATE customer balance += total]
        S --> T[Generate PDF with QR]
        T --> U[Save to storage]
        U --> V[COMMIT]
        V --> W[Invoice status: submitted]
        W --> X[Return success to rep]
    end
```

## Workflow Index

| Diagram                 | Description                                                                         | Primary Actors                 |
| ----------------------- | ----------------------------------------------------------------------------------- | ------------------------------ |
| Daily Rep Workflow      | Full day cycle: login → visits → operations → end day                               | Rep                            |
| Pricing Chain           | Multi-level pricing: request → manager prices → rep negotiates → proforma → invoice | Rep, Sales Manager, Accounts   |
| Procurement Flow        | Purchase request → supplier comparison → PO → receipt                               | Rep, Purchasing, Sales Manager |
| GIT + Landed Cost       | International shipment tracking → receipt → cost distribution                       | Purchasing, Warehouse Keeper   |
| Customer Lifecycle      | Rep adds → pending → approve/reject → transactions → complaints                     | Rep, Sales Manager             |
| Alarm System            | 7 triggers → severity → acknowledge → assign → resolve                              | System (auto), Sales Manager   |
| Atomic Sale Transaction | Internal flow for field invoice creation                                            | System (internal)              |
