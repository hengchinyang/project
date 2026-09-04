# Pharmacy XML Service Consumed by Patient Record

| Item | Agreement |
|---|---|
| Provider | Pharmacy module |
| Consumer | Patient Record module |
| Mechanism | HTTP POST with XML request and response |
| Authentication | `X-API-Key` HTTP header, shared outside source code |
| Namespace | `urn:medicare:patient-record` |

## Prescription request

Operation: `m:getPrescription`

| Field | Requirement | Description |
|---|---|---|
| `requestID` | Mandatory | Unique request identifier |
| `timestamp` | Mandatory | `YYYY-MM-DD HH:MM:SS` |
| `prescriptionReference` | Mandatory | Pharmacy prescription reference, e.g. `RX-DEMO-001` |

The response operation is `m:getPrescriptionResponse`, containing `status`, `timestamp`, `message`, `requestID`, `prescriptionReference`, patient/prescriber details, and `items`.

## Medicine availability request

Operation: `m:getMedicineAvailability` (pending Pharmacy confirmation).

| Field | Requirement | Description |
|---|---|---|
| `requestID` | Mandatory | Unique request identifier |
| `timestamp` | Mandatory | `YYYY-MM-DD HH:MM:SS` |
| `sku` | Mandatory | Medicine stock keeping unit, e.g. `MED-PARA-500` |
| `quantity` | Mandatory | Requested quantity, at least 1 |

The expected response is `m:getMedicineAvailabilityResponse`, containing `status`, `requestID`, `sku`, `requestedQuantity`, `available`, and `availableQuantity`.

## Relationship

Pharmacy consumes the Patient Record clinical-summary endpoint. Patient Record consumes Pharmacy's prescription and medicine-availability operations. There is no Appointment-module integration and no appointment ID in this interface.
