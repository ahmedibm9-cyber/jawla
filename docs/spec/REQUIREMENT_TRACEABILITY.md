# Requirement Traceability — Beta v1.1

Every beta requirement must have one planned owner and one acceptance test location.

| Requirement | Description | Planned Ticket | Test Location | Status |
|---|---|---|---|---|
| REQ-ROL-1…8 | Roles & permissions | B1-02, B1-03, B1-04 | tests/Feature/Roles/ | NOT STARTED |
| REQ-VST-1…3 | Daily visit assignment | B3-01, B3-02 | tests/Feature/Visit/ | NOT STARTED |
| REQ-VST-4 | Customer GPS | B2-04, R-06 | tests/Feature/Customer/ | NOT STARTED |
| REQ-VST-5…7 | GPS arrival + report | B3-03, B3-04, B3-05 | tests/Feature/Visit/ | NOT STARTED |
| REQ-CUS-1,2,4 | Field customer creation | B3-06 | tests/Feature/Customer/ | NOT STARTED |
| REQ-CUS-3 | Customer approval | B2-05 | tests/Feature/Customer/ | NOT STARTED |
| REQ-PRC-1 | Product base price | B2-03 | tests/Feature/Pricing/ | NOT STARTED |
| REQ-PRC-2,4…8 | Pricing chain | B4-01…B4-03 | tests/Feature/Pricing/ | NOT STARTED |
| REQ-INV-1…4 | Invoice + proforma | B4-04, B4-05, B5-01…B5-04 | tests/Feature/Invoice/ | NOT STARTED |
| REQ-STK-1,2 | Stock import | R-05, B2-06 | tests/Feature/Stock/ | NOT STARTED |
| REQ-STK-4,5 | Rep stock lookup | B5-05 | tests/Feature/Stock/ | NOT STARTED |
| REQ-PUR-1…4 | Purchase offers | B7-01…B7-03 | tests/Feature/Purchase/ | NOT STARTED |
| REQ-ALM-1…4 | Alarms | B6-01, B6-02 | tests/Feature/Alarm/ | NOT STARTED |
| REQ-CRM-1…3 | Complaints | B6-03 | tests/Feature/Complaint/ | NOT STARTED |
| REQ-RPT-1…3 | Reports | B2, B6-04, B6-05 | tests/Feature/Reports/ | NOT STARTED |
| REQ-CMP-1 | PDF + QR | B3-05, B5-03 | tests/Feature/Pdf/ | NOT STARTED |
| REQ-CMP-2 | Visit state machine | B3-03 | tests/Feature/Visit/ | NOT STARTED |
| REQ-CMP-3 | Offline drafts | B3-07, B5-06 | tests/Feature/Offline/ | NOT STARTED |
| REQ-CMP-4 | PWA shell | B0-04, B3-08 | tests/Browser/ | NOT STARTED |
| REQ-CMP-5 | Bilingual AR/EN | B0-01…B0-03 | tests/Feature/Locale/ | NOT STARTED |
| REQ-CMP-6 | Maps + directions | B3-08 | tests/Browser/ | NOT STARTED |
| REQ-CMP-7 | WhatsApp sharing | B4-05, B5-03 | tests/Feature/Pdf/ | NOT STARTED |
| REQ-CMP-8 | Dashboard widgets | B6-04 | tests/Feature/Dashboard/ | NOT STARTED |
| REQ-CMP-9 | Live stock + transit | B3-08, B5-05 | tests/Feature/Stock/ | NOT STARTED |
| REQ-CMP-10 | Geofence | B3-04 | tests/Feature/Visit/ | NOT STARTED |
| REQ-CMP-11 | Admin usability | B2-07 | tests/Browser/ | NOT STARTED |
| REQ-CMP-12 | Atomic sales | B5-01…B5-06 | tests/Feature/Invoice/ | NOT STARTED |
| TEC-1 | GPS distance | B3-04 | tests/Unit/Support/ | NOT STARTED |
| TEC-2 | Decimal money | B4-01…B4-03 | tests/Unit/Support/ | NOT STARTED |
| TEC-3 | Alarm broadcast | B6-01, B6-02 | tests/Feature/Alarm/ | NOT STARTED |
| TEC-4 | Stock via service only | R-05, B2-06, B5-05 | tests/Unit/Services/ | NOT STARTED |
| TEC-5 | EGP only | B2-03, B4, B5, B7 | tests/Feature/ | NOT STARTED |
| TEC-6 | Bank details | B2-01, B4-04, B4-05 | tests/Feature/ | NOT STARTED |
| TEC-7 | 7 roles | B1-03 | tests/Feature/Roles/ | NOT STARTED |
| TEC-8 | Mobile-first | B0, B3, B5, B8-03 | tests/Browser/ | NOT STARTED |
| TEC-9 | Signature | B3-05, B5-03 | tests/Feature/Visit/ | NOT STARTED |
| TEC-10 | Offline behavior | B3-07, B5-06 | tests/Feature/Offline/ | NOT STARTED |
| TEC-11 | Activity audit | B1-05 | tests/Feature/Audit/ | NOT STARTED |
| TEC-12 | Offline conflict | B3-07 | tests/Feature/Offline/ | NOT STARTED |

Before beta sign-off, replace every planned mapping with links to the exact tests and evidence.
