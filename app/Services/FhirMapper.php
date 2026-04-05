<?php

namespace App\Services;

/**
 * FhirMapper
 *
 * Converts HMS domain objects into FHIR R4-compatible JSON bundles
 * suitable for submission to ABDM health information exchange and
 * NHCX insurance claim APIs.
 *
 * Each public method accepts a flat associative array of HMS data
 * and returns an array representing the FHIR resource/bundle.
 */
class FhirMapper
{
    private string $fhirVersion = '4.0.1';

    // -----------------------------------------------------------------------
    // Hospital → FHIR Organization
    // -----------------------------------------------------------------------

    /**
     * Map a hospital record to a FHIR Organization resource.
     *
     * @param array<string, mixed> $hospital
     * @return array<string, mixed>
     */
    public function mapHospital(array $hospital): array
    {
        return [
            'resourceType' => 'Organization',
            'id'           => (string) ($hospital['id'] ?? ''),
            'identifier'   => [
                [
                    'system' => 'https://facility.ndhm.gov.in',
                    'value'  => $hospital['hfr_id'] ?? '',
                ],
            ],
            'active' => ($hospital['status'] ?? 'active') === 'active',
            'name'   => $hospital['name'] ?? '',
            'telecom' => [
                ['system' => 'phone', 'value' => $hospital['phone'] ?? ''],
                ['system' => 'email', 'value' => $hospital['email'] ?? ''],
            ],
            'address' => [
                [
                    'text'       => $hospital['address'] ?? '',
                    'city'       => $hospital['city'] ?? '',
                    'state'      => $hospital['state'] ?? '',
                    'postalCode' => $hospital['pincode'] ?? '',
                    'country'    => 'IN',
                ],
            ],
        ];
    }

    // -----------------------------------------------------------------------
    // Doctor → FHIR Practitioner
    // -----------------------------------------------------------------------

    /**
     * Map a doctor record to a FHIR Practitioner resource.
     *
     * @param array<string, mixed> $doctor
     * @return array<string, mixed>
     */
    public function mapDoctor(array $doctor): array
    {
        return [
            'resourceType' => 'Practitioner',
            'id'           => (string) ($doctor['id'] ?? ''),
            'identifier'   => [
                [
                    'system' => 'https://professional.ndhm.gov.in',
                    'value'  => $doctor['hpr_id'] ?? '',
                ],
            ],
            'active' => ($doctor['status'] ?? 'active') === 'active',
            'name'   => [
                ['text' => $doctor['name'] ?? ''],
            ],
            'telecom' => [
                ['system' => 'phone', 'value' => $doctor['phone'] ?? ''],
                ['system' => 'email', 'value' => $doctor['email'] ?? ''],
            ],
            'qualification' => [
                [
                    'code' => [
                        'text' => $doctor['qualification'] ?? '',
                    ],
                ],
            ],
        ];
    }

    // -----------------------------------------------------------------------
    // Patient → FHIR Patient
    // -----------------------------------------------------------------------

    /**
     * Map a patient record to a FHIR Patient resource.
     *
     * @param array<string, mixed> $patient
     * @return array<string, mixed>
     */
    public function mapPatient(array $patient): array
    {
        $genderMap = ['M' => 'male', 'F' => 'female', 'O' => 'other'];

        return [
            'resourceType' => 'Patient',
            'id'           => (string) ($patient['id'] ?? ''),
            'identifier'   => [
                [
                    'system' => 'https://healthid.ndhm.gov.in',
                    'value'  => $patient['abha_id'] ?? '',
                ],
            ],
            'active' => ($patient['status'] ?? 'active') === 'active',
            'name'   => [
                ['text' => $patient['name'] ?? ''],
            ],
            'gender'    => $genderMap[$patient['gender'] ?? 'O'] ?? 'unknown',
            'birthDate' => $patient['dob'] ?? '',
            'telecom'   => [
                ['system' => 'phone', 'value' => $patient['phone'] ?? ''],
            ],
            'address' => [
                ['text' => $patient['address'] ?? ''],
            ],
        ];
    }

    // -----------------------------------------------------------------------
    // OPD → FHIR Bundle (Encounter + Composition)
    // -----------------------------------------------------------------------

    /**
     * Map an OPD visit to a FHIR Bundle containing an Encounter and a
     * Composition (OPD record / prescription).
     *
     * @param array<string, mixed> $opd
     * @return array<string, mixed>
     */
    public function mapOpd(array $opd): array
    {
        $encounter   = $this->buildEncounter($opd, 'AMB');
        $prescription = $this->decodeMedications($opd['prescription'] ?? null);

        $composition = [
            'resourceType' => 'Composition',
            'id'           => 'comp-opd-' . ($opd['id'] ?? ''),
            'status'       => 'final',
            'type'         => [
                'coding' => [['system' => 'http://snomed.info/sct', 'code' => '371530004', 'display' => 'Clinical consultation report']],
            ],
            'subject'  => ['reference' => 'Patient/' . ($opd['patient_id'] ?? '')],
            'date'     => $opd['visit_date'] ?? date('Y-m-d'),
            'author'   => [['reference' => 'Practitioner/' . ($opd['doctor_id'] ?? '')]],
            'title'    => 'OPD Consultation Record',
            'section'  => [
                [
                    'title' => 'Chief Complaint',
                    'text'  => ['status' => 'generated', 'div' => '<div>' . htmlspecialchars($opd['chief_complaint'] ?? '') . '</div>'],
                ],
                [
                    'title' => 'Diagnosis',
                    'text'  => ['status' => 'generated', 'div' => '<div>' . htmlspecialchars($opd['diagnosis'] ?? '') . '</div>'],
                ],
                [
                    'title'   => 'Prescription',
                    'entry'   => $this->buildMedicationRequests($prescription, $opd),
                ],
            ],
        ];

        return $this->buildBundle([$encounter, $composition]);
    }

    // -----------------------------------------------------------------------
    // IPD → FHIR Bundle (Encounter + Composition)
    // -----------------------------------------------------------------------

    /**
     * Map an IPD admission/discharge to a FHIR Bundle.
     *
     * @param array<string, mixed> $ipd
     * @return array<string, mixed>
     */
    public function mapIpd(array $ipd): array
    {
        $encounter = $this->buildEncounter($ipd, 'IMP');
        $treatment = $this->decodeMedications($ipd['treatment'] ?? null);

        $composition = [
            'resourceType' => 'Composition',
            'id'           => 'comp-ipd-' . ($ipd['id'] ?? ''),
            'status'       => 'final',
            'type'         => [
                'coding' => [['system' => 'http://snomed.info/sct', 'code' => '11504003', 'display' => 'Operative report']],
            ],
            'subject'  => ['reference' => 'Patient/' . ($ipd['patient_id'] ?? '')],
            'date'     => $ipd['discharge_date'] ?? $ipd['admission_date'] ?? date('Y-m-d'),
            'author'   => [['reference' => 'Practitioner/' . ($ipd['doctor_id'] ?? '')]],
            'title'    => 'IPD Discharge Summary',
            'section'  => [
                [
                    'title' => 'Admission Reason',
                    'text'  => ['status' => 'generated', 'div' => '<div>' . htmlspecialchars($ipd['admission_reason'] ?? '') . '</div>'],
                ],
                [
                    'title' => 'Diagnosis',
                    'text'  => ['status' => 'generated', 'div' => '<div>' . htmlspecialchars($ipd['diagnosis'] ?? '') . '</div>'],
                ],
                [
                    'title' => 'Discharge Summary',
                    'text'  => ['status' => 'generated', 'div' => '<div>' . htmlspecialchars($ipd['discharge_summary'] ?? '') . '</div>'],
                ],
                [
                    'title' => 'Treatment',
                    'entry' => $this->buildMedicationRequests($treatment, $ipd),
                ],
            ],
        ];

        return $this->buildBundle([$encounter, $composition]);
    }

    // -----------------------------------------------------------------------
    // Pathlab → FHIR DiagnosticReport
    // -----------------------------------------------------------------------

    /**
     * Map a lab result to a FHIR DiagnosticReport bundle.
     *
     * @param array<string, mixed> $lab
     * @return array<string, mixed>
     */
    public function mapPathlab(array $lab): array
    {
        $results     = $this->decodeJson($lab['results'] ?? null);
        $observations = [];

        foreach ($results as $index => $result) {
            $observations[] = [
                'resourceType' => 'Observation',
                'id'           => 'obs-' . ($lab['id'] ?? '') . '-' . $index,
                'status'       => 'final',
                'code'         => [
                    'text' => $result['test'] ?? $result['name'] ?? 'Unknown',
                ],
                'subject'         => ['reference' => 'Patient/' . ($lab['patient_id'] ?? '')],
                'effectiveDateTime' => $lab['test_date'] ?? '',
                'valueString'     => $result['value'] ?? '',
                'referenceRange'  => isset($result['reference_range'])
                    ? [['text' => $result['reference_range']]]
                    : [],
            ];
        }

        $diagnosticReport = [
            'resourceType'  => 'DiagnosticReport',
            'id'            => 'dr-' . ($lab['id'] ?? ''),
            'status'        => 'final',
            'category'      => [
                ['coding' => [['system' => 'http://terminology.hl7.org/CodeSystem/v2-0074', 'code' => 'LAB']]],
            ],
            'code'          => ['text' => $lab['test_name'] ?? ''],
            'subject'       => ['reference' => 'Patient/' . ($lab['patient_id'] ?? '')],
            'effectiveDateTime' => $lab['test_date'] ?? '',
            'result'        => array_map(
                fn ($obs) => ['reference' => 'Observation/' . $obs['id']],
                $observations
            ),
            'presentedForm' => isset($lab['report_url'])
                ? [['url' => $lab['report_url'], 'contentType' => 'application/pdf']]
                : [],
        ];

        return $this->buildBundle(array_merge($observations, [$diagnosticReport]));
    }

    // -----------------------------------------------------------------------
    // Radiology → FHIR ImagingStudy / DiagnosticReport
    // -----------------------------------------------------------------------

    /**
     * Map an imaging study to a FHIR ImagingStudy bundle.
     *
     * @param array<string, mixed> $rad
     * @return array<string, mixed>
     */
    public function mapRadiology(array $rad): array
    {
        $imagingStudy = [
            'resourceType' => 'ImagingStudy',
            'id'           => 'img-' . ($rad['id'] ?? ''),
            'status'       => 'available',
            'subject'      => ['reference' => 'Patient/' . ($rad['patient_id'] ?? '')],
            'started'      => $rad['study_date'] ?? '',
            'modality'     => [
                ['system' => 'http://dicom.nema.org/resources/ontology/DCM', 'code' => strtoupper($rad['modality'] ?? '')],
            ],
            'numberOfSeries'   => 1,
            'numberOfInstances' => 1,
            'description'      => $rad['body_part'] ?? '',
        ];

        $diagnosticReport = [
            'resourceType'  => 'DiagnosticReport',
            'id'            => 'dr-rad-' . ($rad['id'] ?? ''),
            'status'        => 'final',
            'category'      => [
                ['coding' => [['system' => 'http://terminology.hl7.org/CodeSystem/v2-0074', 'code' => 'RAD']]],
            ],
            'code'          => ['text' => ($rad['modality'] ?? '') . ' - ' . ($rad['body_part'] ?? '')],
            'subject'       => ['reference' => 'Patient/' . ($rad['patient_id'] ?? '')],
            'effectiveDateTime' => $rad['study_date'] ?? '',
            'conclusion'    => $rad['findings'] ?? '',
            'imagingStudy'  => [['reference' => 'ImagingStudy/img-' . ($rad['id'] ?? '')]],
            'presentedForm' => isset($rad['report_url'])
                ? [['url' => $rad['report_url'], 'contentType' => 'application/pdf']]
                : [],
        ];

        return $this->buildBundle([$imagingStudy, $diagnosticReport]);
    }

    // -----------------------------------------------------------------------
    // Pharmacy → FHIR MedicationDispense
    // -----------------------------------------------------------------------

    /**
     * Map a pharmacy dispensing record to a FHIR MedicationDispense bundle.
     *
     * @param array<string, mixed> $rx
     * @return array<string, mixed>
     */
    public function mapPharmacy(array $rx): array
    {
        $medications = $this->decodeMedications($rx['medications'] ?? null);
        $dispenses   = [];

        foreach ($medications as $index => $med) {
            $dispenses[] = [
                'resourceType'       => 'MedicationDispense',
                'id'                 => 'md-' . ($rx['id'] ?? '') . '-' . $index,
                'status'             => 'completed',
                'medicationCodeableConcept' => [
                    'text' => $med['name'] ?? $med['drug'] ?? 'Unknown',
                ],
                'subject'            => ['reference' => 'Patient/' . ($rx['patient_id'] ?? '')],
                'whenHandedOver'     => $rx['dispense_date'] ?? '',
                'quantity'           => [
                    'value' => $med['quantity'] ?? 1,
                    'unit'  => $med['unit'] ?? 'unit',
                ],
                'dosageInstruction'  => [
                    ['text' => $med['dosage'] ?? ''],
                ],
            ];
        }

        return $this->buildBundle($dispenses);
    }

    // -----------------------------------------------------------------------
    // Claim → FHIR Claim resource
    // -----------------------------------------------------------------------

    /**
     * Map an insurance claim to a FHIR Claim resource (R4).
     *
     * @param array<string, mixed> $claim
     * @return array<string, mixed>
     */
    public function mapClaim(array $claim): array
    {
        $itemizedBill = $this->decodeJson($claim['itemized_bill'] ?? null);
        $diagnosisCodes = $this->decodeJson($claim['diagnosis_codes'] ?? null);
        $procedureCodes = $this->decodeJson($claim['procedure_codes'] ?? null);

        $items = [];
        foreach ($itemizedBill as $index => $item) {
            $items[] = [
                'sequence' => $index + 1,
                'productOrService' => [
                    'text' => $item['description'] ?? $item['name'] ?? 'Service',
                ],
                'quantity'  => ['value' => $item['quantity'] ?? 1],
                'unitPrice' => [
                    'value'    => $item['unit_price'] ?? $item['amount'] ?? 0,
                    'currency' => 'INR',
                ],
                'net' => [
                    'value'    => $item['total'] ?? $item['amount'] ?? 0,
                    'currency' => 'INR',
                ],
            ];
        }

        $diagnoses = [];
        foreach ($diagnosisCodes as $index => $code) {
            $diagnoses[] = [
                'sequence' => $index + 1,
                'diagnosisCodeableConcept' => [
                    'coding' => [['system' => 'http://hl7.org/fhir/sid/icd-10', 'code' => $code]],
                ],
            ];
        }

        $procedures = [];
        foreach ($procedureCodes as $index => $code) {
            $procedures[] = [
                'sequence' => $index + 1,
                'procedureCodeableConcept' => [
                    'coding' => [['system' => 'http://www.icd10data.com/icd10pcs', 'code' => $code]],
                ],
            ];
        }

        $fhirClaim = [
            'resourceType' => 'Claim',
            'id'           => $claim['claim_number'] ?? ('CLM-' . ($claim['id'] ?? '')),
            'status'       => 'active',
            'type'         => [
                'coding' => [['system' => 'http://terminology.hl7.org/CodeSystem/claim-type', 'code' => 'institutional']],
            ],
            'use'          => $claim['claim_type'] === 'cashless' ? 'preauthorization' : 'claim',
            'patient'      => ['reference' => 'Patient/' . ($claim['patient_id'] ?? '')],
            'created'      => date('Y-m-d'),
            'insurer'      => ['display' => $claim['insurer_name'] ?? ''],
            'provider'     => ['reference' => 'Organization/' . ($claim['hospital_id'] ?? '')],
            'priority'     => ['coding' => [['code' => 'normal']]],
            'careTeam'     => [
                [
                    'sequence'  => 1,
                    'provider'  => ['reference' => 'Practitioner/' . ($claim['doctor_id'] ?? '')],
                    'role'      => ['coding' => [['code' => 'primary']]],
                ],
            ],
            'diagnosis' => $diagnoses,
            'procedure' => $procedures,
            'insurance' => [
                [
                    'sequence'  => 1,
                    'focal'     => true,
                    'coverage'  => ['display' => $claim['policy_number'] ?? ''],
                ],
            ],
            'item'  => $items,
            'total' => [
                'value'    => (float) ($claim['total_amount'] ?? 0),
                'currency' => 'INR',
            ],
        ];

        return $this->buildBundle([$fhirClaim]);
    }

    // -----------------------------------------------------------------------
    // Private helpers
    // -----------------------------------------------------------------------

    /**
     * Build a FHIR Bundle wrapping the given resources.
     *
     * @param array<int, array<string, mixed>> $resources
     * @return array<string, mixed>
     */
    private function buildBundle(array $resources): array
    {
        $entries = array_map(
            static fn ($resource) => ['resource' => $resource],
            $resources
        );

        return [
            'resourceType'    => 'Bundle',
            'id'              => 'bundle-' . bin2hex(random_bytes(8)),
            'type'            => 'document',
            'timestamp'       => date('c'),
            'entry'           => $entries,
        ];
    }

    /**
     * Build a FHIR Encounter resource from an OPD/IPD record.
     *
     * @param array<string, mixed> $record
     * @param string               $class  'AMB' (ambulatory) | 'IMP' (inpatient)
     * @return array<string, mixed>
     */
    private function buildEncounter(array $record, string $class): array
    {
        $startDate = $record['visit_date'] ?? $record['admission_date'] ?? date('Y-m-d');
        $endDate   = $record['discharge_date'] ?? null;

        $period = ['start' => $startDate];
        if ($endDate !== null) {
            $period['end'] = $endDate;
        }

        return [
            'resourceType' => 'Encounter',
            'id'           => 'enc-' . ($record['id'] ?? ''),
            'status'       => $endDate !== null ? 'finished' : 'in-progress',
            'class'        => [
                'system'  => 'http://terminology.hl7.org/CodeSystem/v3-ActCode',
                'code'    => $class,
                'display' => $class === 'AMB' ? 'ambulatory' : 'inpatient encounter',
            ],
            'subject'  => ['reference' => 'Patient/' . ($record['patient_id'] ?? '')],
            'participant' => [
                [
                    'individual' => ['reference' => 'Practitioner/' . ($record['doctor_id'] ?? '')],
                ],
            ],
            'serviceProvider' => ['reference' => 'Organization/' . ($record['hospital_id'] ?? '')],
            'period'          => $period,
        ];
    }

    /**
     * Build a list of FHIR MedicationRequest references from a medication array.
     *
     * @param array<int, array<string, mixed>> $medications
     * @param array<string, mixed>             $context
     * @return array<int, array<string, string>>
     */
    private function buildMedicationRequests(array $medications, array $context): array
    {
        $refs = [];
        foreach ($medications as $index => $med) {
            $refs[] = ['reference' => 'MedicationRequest/mr-' . ($context['id'] ?? '') . '-' . $index];
        }

        return $refs;
    }

    /**
     * Decode a JSON string or return the value as-is if already an array.
     *
     * @param mixed $value
     * @return array<mixed>
     */
    private function decodeJson(mixed $value): array
    {
        if (is_array($value)) {
            return $value;
        }

        if (is_string($value) && $value !== '') {
            $decoded = json_decode($value, true);

            return is_array($decoded) ? $decoded : [];
        }

        return [];
    }

    /**
     * Decode medication JSON (same as decodeJson but aliased for readability).
     *
     * @param mixed $value
     * @return array<mixed>
     */
    private function decodeMedications(mixed $value): array
    {
        return $this->decodeJson($value);
    }
}
