# Interface Agreement: Patient Record XML Service

## Service overview

| Item | Agreement |
|---|---|
| Mechanism | REST-style web service with XML request and response |
| Protocol | HTTP POST |
| Function | Retrieve a least-privilege Patient Record clinical summary |
| Source/provider module | Patient Record |
| Target/consumer module | Pharmacy |
| Local URL | `http://localhost/project/api/patient-record-summary.php` |
| Content type | `application/xml` |
| Authentication | `X-API-Key` HTTP header, shared separately from source code |

## Request fields

The elements must appear in the documented order.

| Field | Type | Requirement | Description | Format |
|---|---|---|---|---|
| `requestID` | String | Mandatory | Unique identifier for tracing the request | 8-64 letters, numbers, `_` or `-` |
| `timeStamp` | String | Mandatory | Time the request was created | `YYYY-MM-DD HH:MM:SS` |
| `patientID` | String | Mandatory | Patient whose clinical summary is requested | `PA` followed by 3-8 digits, for example `PA001` |
| `appointmentID` | String | Optional | Limits results to one appointment | `APT` followed by 4-17 digits, for example `APT0001` |

## Response fields

| Field | Type | Requirement | Description | Format |
|---|---|---|---|---|
| `requestID` | String | Mandatory | Echoes the request identifier when readable | String or `UNKNOWN` |
| `status` | String | Mandatory | Processing result | `S`, `F`, or `E` |
| `timeStamp` | String | Mandatory | Time the response was generated | `YYYY-MM-DD HH:MM:SS` |
| `message` | String | Mandatory | Human-readable result | Plain text |
| `patient` | Object | Success only | Patient identity | `patientID`, `patientName` |
| `records` | Collection | Success only | One or more clinical summaries | Repeating `record` elements |

Each `record` contains `recordID`, `appointmentID`, `doctorID`, `condition`, `severity`, and `recordDate`. The sensitive free-text remark is intentionally excluded under the least-privilege principle.

## Status and HTTP scenarios

| Scenario | IFA status | HTTP status |
|---|---:|---:|
| Records returned | `S` | 200 |
| Patient/appointment has no matching record | `F` | 404 |
| Invalid XML or XSD failure | `E` | 400 |
| Missing/invalid API key | `E` | 401 |
| Non-POST request | `E` | 405 |
| Unexpected server failure | `E` | 500 |

## Validation artifacts

- Request schema: `WebService/xsd/patient_record_request.xsd`
- Response schema: `WebService/xsd/patient_record_response.xsd`
- Example request: `WebService/examples/patient_record_request.xml`
- Example appointment-filter request: `WebService/examples/patient_record_request_by_appointment.xml`
- Example response: `WebService/examples/patient_record_response.xml`
- Example multi-record success response: `WebService/examples/patient_record_success_multiple_response.xml`
- Example failure response: `WebService/examples/patient_record_failure_response.xml`
- Example error response: `WebService/examples/patient_record_error_response.xml`

The Pharmacy module must send XML that passes the request XSD and must validate/handle all three IFA status values.
