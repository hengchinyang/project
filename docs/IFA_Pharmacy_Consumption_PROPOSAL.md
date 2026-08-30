# Proposed IFA: Pharmacy Service Consumed by Patient Record

This is a proposal and must be confirmed with the Pharmacy teammate before final integration.

| Item | Proposed agreement |
|---|---|
| Provider | Pharmacy module |
| Consumer | Patient Record module |
| Mechanism | REST-style HTTP POST with XML request and response |
| Purpose | Retrieve prescription and medicine details for a Patient Record |
| URL | To be supplied by Pharmacy teammate |
| Content type | `application/xml` |
| Authentication | `X-API-Key` HTTP header, shared outside source code |

## Proposed request fields

The XML root is `pharmacyPrescriptionRequest`. Elements appear in this order:

| Field | Type | Requirement | Format |
|---|---|---|---|
| `requestID` | String | Mandatory | Unique 8-64 character request identifier |
| `timeStamp` | String | Mandatory | `YYYY-MM-DD HH:MM:SS` |
| `patientID` | String | Mandatory | Example: `PA001` |
| `appointmentID` | String | Mandatory | Example: `APT0001` |

## Proposed response fields

The XML root is `pharmacyPrescriptionResponse`.

| Field | Requirement | Description |
|---|---|---|
| `requestID` | Mandatory | Echoes the request identifier |
| `status` | Mandatory | `S`, `F`, or `E` |
| `timeStamp` | Mandatory | Response creation time |
| `message` | Mandatory | Human-readable result |
| `patientID` | Success only | Must match the request |
| `appointmentID` | Success only | Must match the request |
| `prescriptionID` | Success only | Pharmacy prescription identifier |
| `doctorID` | Success only | Prescribing doctor identifier, e.g. `DC001` |
| `finalPrice` | Success only | Non-negative prescription total |
| `medicines` | Success only | One or more `medicine` elements |

Each `medicine` contains `medicineID`, `medicineName`, `quantity`, and `totalPrice`.

## Two-way module relationship

1. Pharmacy consumes the Patient Record clinical-summary endpoint documented in `IFA_Patient_Record_XML_Service.md`.
2. Patient Record consumes this proposed Pharmacy prescription endpoint.

The client foundation is `Service/PharmacyServiceClient.php`. It is deliberately not connected to the Patient Record page until the Pharmacy teammate confirms the endpoint, API key, field names, XSD, and status behaviour.
