
<?php
// Generate system diagrams as text for inclusion in documentation

function generateContextDiagram() {
    return "
CONTEXT DIAGRAM - Redcliff Fault Reporting System

External Entities:
┌─────────────┐    ┌─────────────────────────────┐    ┌─────────────┐
│  Residents  │◄──►│   Fault Reporting System    │◄──►│ Municipal   │
│             │    │                             │    │ Staff       │
└─────────────┘    │  • User Authentication      │    └─────────────┘
                   │  • Fault Management         │
┌─────────────┐    │  • Status Tracking          │    ┌─────────────┐
│ Department  │◄──►│  • Analytics & Reporting    │◄──►│ System      │
│ Heads       │    │  • File Storage             │    │ Admin       │
└─────────────┘    └─────────────────────────────┘    └─────────────┘
";
}

function generateDataFlowDiagram() {
    return "
DATA FLOW DIAGRAM (Level 0)

        Residents                Municipal Staff
            │                          │
            ▼                          ▼
    ┌───────────────┐          ┌───────────────┐
    │   Login &     │          │   Admin       │
    │Authentication │          │ Dashboard     │
    └───────┬───────┘          └───────┬───────┘
            │                          │
            ▼                          ▼
    ┌───────────────┐          ┌───────────────┐
    │   Submit      │          │   Manage      │
    │   Fault       │◄────────►│   Faults      │
    │   Report      │          │               │
    └───────┬───────┘          └───────┬───────┘
            │                          │
            ▼                          ▼
    ┌─────────────────────────────────────────┐
    │         Central Database                │
    │  • User Data    • Fault Reports         │
    │  • Files        • Status Updates        │
    └─────────────┬───────────────────────────┘
                  │
                  ▼
    ┌─────────────────────────────────────────┐
    │      Analytics & Prediction Engine      │
    │  • Historical Analysis                  │
    │  • Fault Predictions                    │
    └─────────────────────────────────────────┘
";
}

function generateSystemFlowchart() {
    return "
SYSTEM FLOWCHART

    START
      │
      ▼
   User Access
      │
      ▼
  ┌─Authentication─┐
  │   Required?    │
  └─┬─────────────┬┘
    │ YES         │ NO
    ▼             ▼
  Login         Public
  Process       Pages
    │
    ▼
┌─Credentials─┐
│   Valid?    │
└─┬─────────┬─┘
  │ YES     │ NO
  ▼         ▼
Grant     Display
Access    Error
  │         │
  ▼         ▼
Dashboard   Return
  │       to Login
  ▼
User Actions:
├─ Resident ────► Submit Fault ────► Validate ────► Store
├─ Admin ───────► Manage Faults ───► Update ─────► Notify
└─ Department ──► Process Work ────► Progress ───► Update
  │
  ▼
 END
";
}

function generateClassDiagram() {
    return "
CLASS DIAGRAM

┌─────────────────┐     ┌─────────────────┐     ┌─────────────────┐
│      User       │     │   FaultReport   │     │   Department    │
├─────────────────┤     ├─────────────────┤     ├─────────────────┤
│ +id: int        │     │ +id: int        │     │ +id: int        │
│ +email: string  │────►│ +user_id: int   │     │ +name: string   │
│ +password: hash │     │ +title: string  │     │ +description    │
│ +first_name     │     │ +description    │◄────│ +contact_email  │
│ +last_name      │     │ +location       │     │ +status         │
│ +role: enum     │     │ +category       │     └─────────────────┘
│ +status         │     │ +status         │
├─────────────────┤     │ +priority       │
│ +login()        │     │ +created_at     │
│ +logout()       │     ├─────────────────┤
│ +verify()       │     │ +submit()       │
└─────────────────┘     │ +update()       │
                        │ +assign()       │
                        └─────────────────┘
";
}

function generateSequenceDiagram() {
    return "
SEQUENCE DIAGRAM - Fault Submission Process

Resident    System    Database    Admin    Department
   │          │          │         │         │
   │─ Login ──►│          │         │         │
   │          │─ Verify ─►│         │         │
   │          │◄─ User ───│         │         │
   │◄─ Auth ──│          │         │         │
   │          │          │         │         │
   │─Submit ──►│          │         │         │
   │  Fault   │─ Store ──►│         │         │
   │          │◄─ ID ────│         │         │
   │◄─ Ref# ──│          │         │         │
   │          │─ Notify ─────────►│         │
   │          │          │        │─Assign─►│
   │          │◄─ Update ─────────│         │
   │          │─ Store ──►│         │         │
   │◄─Update──│          │         │         │
";
}

// Export all diagrams
$diagrams = [
    'context' => generateContextDiagram(),
    'dataflow' => generateDataFlowDiagram(),
    'flowchart' => generateSystemFlowchart(),
    'class' => generateClassDiagram(),
    'sequence' => generateSequenceDiagram()
];

foreach ($diagrams as $type => $diagram) {
    file_put_contents("diagrams_{$type}.txt", $diagram);
    echo "Generated {$type} diagram\n";
}

echo "All diagrams generated successfully!\n";
?>
