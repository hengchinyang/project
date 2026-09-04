<?php

declare(strict_types=1);

final class MedicineUnavailableException extends RuntimeException {}

final class IntegrationConsistencyException extends RuntimeException
{
    public function __construct(public readonly string $prescriptionReference, Throwable $previous)
    {
        parent::__construct('Pharmacy succeeded but the local Patient Record save failed.', 0, $previous);
    }
}

/** Coordinates the REST operation and local transaction as one application use case. */
final class PatientRecordCreationService
{
    /**
     * @param Closure(array<string,mixed>,string):string $saveLocalRecord
     * @param null|Closure(string,Throwable):void $reportReconciliation
     */
    public function __construct(
        private readonly PharmacyServiceClient $pharmacy,
        private readonly Closure $saveLocalRecord,
        private readonly ?Closure $reportReconciliation = null
    ) {}

    /**
     * @param array<string,mixed> $recordData
     * @param array<string,mixed> $patient
     * @param list<array{sku:string,quantity:int,instructions:string}> $items
     */
    public function create(
        array $recordData,
        array $patient,
        string $doctorName,
        array $items
    ): string {
        foreach ($items as $item) {
            $availability = $this->pharmacy->getMedicineAvailability($item['sku'], $item['quantity']);
            if (($availability['available'] ?? 'false') !== 'true') {
                throw new MedicineUnavailableException('Medicine unavailable: ' . $item['sku']);
            }
        }

        $pharmacyResult = $this->pharmacy->createApprovedDispensingRequest(
            (string) $patient['id'],
            (string) $patient['name'],
            (string) $recordData['doctor_id'],
            $doctorName,
            $items
        );
        $reference = (string) $pharmacyResult['prescriptionReference'];

        try {
            return ($this->saveLocalRecord)($recordData, $reference);
        } catch (Throwable $exception) {
            if ($this->reportReconciliation !== null) {
                ($this->reportReconciliation)($reference, $exception);
            }
            throw new IntegrationConsistencyException($reference, $exception);
        }
    }
}
