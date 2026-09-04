# Patient Record consumption of Appointment assignments

The Patient Record module never connects to the Appointment database. It sends an XML REST request to the URL configured by `MEDICARE_APPOINTMENT_ASSIGNMENTS_URL`.

## Endpoint required from the Appointment teammate

`POST /Assignment3/?action=apiDoctorPatientAssignments`

Headers:

```text
Content-Type: application/xml
Accept: application/xml
X-API-Key: shared key stored outside htdocs
```

Request:

```xml
<m:getDoctorPatientAssignments xmlns:m="urn:medicare:appointment">
  <m:requestID>REQ-12345678</m:requestID>
  <m:timestamp>2026-09-04 12:00:00</m:timestamp>
  <m:doctorId>DC001</m:doctorId>
</m:getDoctorPatientAssignments>
```

Successful response:

```xml
<m:getDoctorPatientAssignmentsResponse xmlns:m="urn:medicare:appointment">
  <m:status>S</m:status>
  <m:timestamp>2026-09-04 12:00:01</m:timestamp>
  <m:message>Assignments retrieved.</m:message>
  <m:requestID>REQ-12345678</m:requestID>
  <m:assignments>
    <m:assignment>
      <m:patientId>PA001</m:patientId>
      <m:patientName>John Tan</m:patientName>
      <m:appointmentId>APT0001</m:appointmentId>
      <m:status>approved</m:status>
    </m:assignment>
  </m:assignments>
</m:getDoctorPatientAssignmentsResponse>
```

The Appointment service should return only assignments that allow the doctor to open or create a Patient Record. This solves the first-record problem because authorisation comes from Appointment, not from a Patient Record that must already exist.

## Authentication session compatibility

The agreed session fields are `role`, `username`, `user_id`, and `patient_id`. The supplied Appointment copy currently publishes `userId`; Patient Record temporarily maps an authenticated `userId` to `user_id`, and for a patient maps the same `PA...` value to `patient_id`. Appointment should eventually publish the agreed names directly.

The supplied Appointment folder cannot be run by itself because `Shared/bootstrap.php`, `Shared/Config/config.php`, its Database class, and SQL schema are absent. No Appointment file was modified.
