<?php

namespace App\Services;

/**
 * FhirMappingService
 *
 * Converts HMS-specific data schemas into ABDM-compliant FHIR R4 JSON bundles.
 * Each method returns a well-formed FHIR Bundle array ready to be JSON-encoded
 * and sent to the ABDM gateway.
 */
class FhirMappingService
{
    /**
     * Build a FHIR Patient resource from HMS patient data.
     */
    public function buildPatientResource(array $patient): array
    {
        return [
            'resourceType' => 'Patient',
            'id'           => $patient['hms_id'] ?? uniqid('pat-', true),
            'meta'         => ['profile' => ['https://nrces.in/ndhm/fhir/r4/StructureDefinition/Patient']],
            'identifier'   => [
                [
                    'type'  => ['coding' => [['system' => 'http://terminology.hl7.org/CodeSystem/v2-0203', 'code' => 'MR']]],
                    'value' => $patient['hms_id'] ?? '',
                ],
            ],
            'name' => [
                [
                    'use'   => 'official',
                    'text'  => ($patient['first_name'] ?? '') . ' ' . ($patient['last_name'] ?? ''),
                    'family' => $patient['last_name'] ?? '',
                    'given' => [$patient['first_name'] ?? ''],
                ],
            ],
            'gender'    => strtolower($patient['gender'] ?? 'unknown'),
            'birthDate' => $patient['dob'] ?? null,
            'telecom'   => empty($patient['phone']) ? [] : [
                ['system' => 'phone', 'value' => $patient['phone'], 'use' => 'mobile'],
            ],
            'address' => empty($patient['address']) ? [] : [
                ['text' => $patient['address']],
            ],
        ];
    }

    /**
     * Build a FHIR Practitioner resource from HMS doctor data.
     */
    public function buildPractitionerResource(array $doctor): array
    {
        return [
            'resourceType' => 'Practitioner',
            'id'           => $doctor['hms_id'] ?? uniqid('prac-', true),
            'meta'         => ['profile' => ['https://nrces.in/ndhm/fhir/r4/StructureDefinition/Practitioner']],
            'identifier'   => [
                [
                    'type'  => ['coding' => [['system' => 'http://terminology.hl7.org/CodeSystem/v2-0203', 'code' => 'MD']]],
                    'value' => $doctor['hms_id'] ?? '',
                ],
            ],
            'name' => [
                [
                    'use'    => 'official',
                    'text'   => 'Dr. ' . ($doctor['first_name'] ?? '') . ' ' . ($doctor['last_name'] ?? ''),
                    'family' => $doctor['last_name'] ?? '',
                    'given'  => [$doctor['first_name'] ?? ''],
                    'prefix' => ['Dr.'],
                ],
            ],
            'gender' => strtolower($doctor['gender'] ?? 'unknown'),
            'qualification' => empty($doctor['qualification']) ? [] : [
                [
                    'code' => [
                        'coding' => [['system' => 'http://snomed.info/sct', 'display' => $doctor['qualification']]],
                        'text'   => $doctor['qualification'],
                    ],
                ],
            ],
        ];
    }

    /**
     * Build a FHIR Organization resource from HMS hospital data.
     */
    public function buildOrganizationResource(array $hospital): array
    {
        return [
            'resourceType' => 'Organization',
            'id'           => $hospital['hms_id'] ?? uniqid('org-', true),
            'meta'         => ['profile' => ['https://nrces.in/ndhm/fhir/r4/StructureDefinition/Organization']],
            'identifier'   => [
                [
                    'type'  => ['coding' => [['system' => 'http://terminology.hl7.org/CodeSystem/v2-0203', 'code' => 'PRN']]],
                    'value' => $hospital['hms_id'] ?? '',
                ],
            ],
            'name'    => $hospital['name'] ?? '',
            'telecom' => empty($hospital['phone']) ? [] : [
                ['system' => 'phone', 'value' => $hospital['phone']],
            ],
            'address' => empty($hospital['address']) ? [] : [
                ['text' => $hospital['address'], 'city' => $hospital['city'] ?? '', 'state' => $hospital['state'] ?? ''],
            ],
        ];
    }

    /**
     * Build a FHIR Encounter resource for an OPD visit.
     */
    public function buildOpdEncounterBundle(array $opd): array
    {
        $encounterId = $opd['encounter_id'] ?? uniqid('enc-', true);
        $patientId   = $opd['patient_hms_id'] ?? uniqid('pat-', true);

        $entries = [];

        // Encounter resource
        $entries[] = [
            'resource' => [
                'resourceType' => 'Encounter',
                'id'           => $encounterId,
                'status'       => 'finished',
                'class'        => ['system' => 'http://terminology.hl7.org/CodeSystem/v3-ActCode', 'code' => 'AMB', 'display' => 'ambulatory'],
                'subject'      => ['reference' => 'Patient/' . $patientId],
                'period'       => ['start' => $opd['visit_date'] ?? date('Y-m-d')],
            ],
        ];

        // MedicationRequest resources for prescriptions
        foreach ($opd['prescriptions'] ?? [] as $index => $prescription) {
            $entries[] = [
                'resource' => [
                    'resourceType'       => 'MedicationRequest',
                    'id'                 => 'med-' . $encounterId . '-' . $index,
                    'status'             => 'active',
                    'intent'             => 'order',
                    'subject'            => ['reference' => 'Patient/' . $patientId],
                    'encounter'          => ['reference' => 'Encounter/' . $encounterId],
                    'medicationCodeableConcept' => [
                        'text' => $prescription['drug_name'] ?? '',
                    ],
                    'dosageInstruction' => [
                        ['text' => ($prescription['dosage'] ?? '') . ' ' . ($prescription['frequency'] ?? '')],
                    ],
                ],
            ];
        }

        return $this->wrapInBundle('OPDRecord', $entries);
    }

    /**
     * Build a FHIR Bundle for an IPD admission + discharge summary.
     */
    public function buildIpdBundle(array $ipd): array
    {
        $encounterId = $ipd['admission_id'] ?? uniqid('adm-', true);
        $patientId   = $ipd['patient_hms_id'] ?? uniqid('pat-', true);

        $entries = [];

        // Encounter (inpatient)
        $entries[] = [
            'resource' => [
                'resourceType' => 'Encounter',
                'id'           => $encounterId,
                'status'       => 'finished',
                'class'        => ['system' => 'http://terminology.hl7.org/CodeSystem/v3-ActCode', 'code' => 'IMP', 'display' => 'inpatient encounter'],
                'subject'      => ['reference' => 'Patient/' . $patientId],
                'period'       => [
                    'start' => $ipd['admission_date'] ?? '',
                    'end'   => $ipd['discharge_date'] ?? '',
                ],
                'hospitalization' => [
                    'dischargeDisposition' => [
                        'coding' => [['system' => 'http://terminology.hl7.org/CodeSystem/discharge-disposition', 'code' => 'home']],
                    ],
                ],
            ],
        ];

        // Discharge summary as a Composition
        if (!empty($ipd['discharge_summary'])) {
            $entries[] = [
                'resource' => [
                    'resourceType' => 'Composition',
                    'id'           => 'comp-' . $encounterId,
                    'status'       => 'final',
                    'type'         => ['coding' => [['system' => 'http://snomed.info/sct', 'code' => '373942005', 'display' => 'Discharge summary']]],
                    'subject'      => ['reference' => 'Patient/' . $patientId],
                    'encounter'    => ['reference' => 'Encounter/' . $encounterId],
                    'section'      => [['text' => ['status' => 'generated', 'div' => $ipd['discharge_summary']]]],
                ],
            ];
        }

        return $this->wrapInBundle('DischargeSummaryRecord', $entries);
    }

    /**
     * Build a FHIR DiagnosticReport bundle for lab results.
     */
    public function buildLabBundle(array $lab): array
    {
        $reportId  = $lab['report_id'] ?? uniqid('lab-', true);
        $patientId = $lab['patient_hms_id'] ?? uniqid('pat-', true);

        $observations = [];
        foreach ($lab['tests'] ?? [] as $index => $test) {
            $obsId          = 'obs-' . $reportId . '-' . $index;
            $observations[] = [
                'resource' => [
                    'resourceType'    => 'Observation',
                    'id'              => $obsId,
                    'status'          => 'final',
                    'code'            => ['text' => $test['test_name'] ?? ''],
                    'subject'         => ['reference' => 'Patient/' . $patientId],
                    'effectiveDateTime' => $lab['report_date'] ?? date('Y-m-d'),
                    'valueQuantity'   => [
                        'value' => $test['result_value'] ?? null,
                        'unit'  => $test['unit'] ?? '',
                    ],
                    'referenceRange' => empty($test['reference_range']) ? [] : [
                        ['text' => $test['reference_range']],
                    ],
                ],
            ];
        }

        $entries = array_merge($observations, [
            [
                'resource' => [
                    'resourceType' => 'DiagnosticReport',
                    'id'           => $reportId,
                    'status'       => 'final',
                    'category'     => [['coding' => [['system' => 'http://snomed.info/sct', 'code' => '4241000179101', 'display' => 'Laboratory report']]]],
                    'code'         => ['text' => $lab['report_name'] ?? 'Lab Report'],
                    'subject'      => ['reference' => 'Patient/' . $patientId],
                    'effectiveDateTime' => $lab['report_date'] ?? date('Y-m-d'),
                    'result'       => array_map(static fn ($o) => ['reference' => 'Observation/' . $o['resource']['id']], $observations),
                ],
            ],
        ]);

        return $this->wrapInBundle('DiagnosticReportRecord', $entries);
    }

    /**
     * Build a FHIR DiagnosticReport bundle for radiology / imaging reports.
     */
    public function buildRadiologyBundle(array $radiology): array
    {
        $reportId  = $radiology['report_id'] ?? uniqid('rad-', true);
        $patientId = $radiology['patient_hms_id'] ?? uniqid('pat-', true);

        $entries = [
            [
                'resource' => [
                    'resourceType'      => 'DiagnosticReport',
                    'id'                => $reportId,
                    'status'            => 'final',
                    'category'          => [['coding' => [['system' => 'http://snomed.info/sct', 'code' => '4201000179104', 'display' => 'Imaging report']]]],
                    'code'              => ['text' => $radiology['modality'] ?? 'Radiology Report'],
                    'subject'           => ['reference' => 'Patient/' . $patientId],
                    'effectiveDateTime' => $radiology['report_date'] ?? date('Y-m-d'),
                    'conclusion'        => $radiology['findings'] ?? '',
                ],
            ],
        ];

        // Attach image references if provided
        foreach ($radiology['images'] ?? [] as $index => $image) {
            $entries[] = [
                'resource' => [
                    'resourceType' => 'Media',
                    'id'           => 'img-' . $reportId . '-' . $index,
                    'status'       => 'completed',
                    'subject'      => ['reference' => 'Patient/' . $patientId],
                    'content'      => [
                        'contentType' => $image['content_type'] ?? 'image/jpeg',
                        'url'         => $image['url'] ?? '',
                        'title'       => $image['title'] ?? 'Image ' . ($index + 1),
                    ],
                ],
            ];
        }

        return $this->wrapInBundle('ImagingStudyRecord', $entries);
    }

    /**
     * Build a FHIR MedicationDispense bundle for pharmacy dispensing records.
     */
    public function buildPharmacyBundle(array $pharmacy): array
    {
        $patientId = $pharmacy['patient_hms_id'] ?? uniqid('pat-', true);
        $entries   = [];

        foreach ($pharmacy['dispensed_items'] ?? [] as $index => $item) {
            $entries[] = [
                'resource' => [
                    'resourceType'            => 'MedicationDispense',
                    'id'                      => 'disp-' . ($pharmacy['bill_id'] ?? 'na') . '-' . $index,
                    'status'                  => 'completed',
                    'medicationCodeableConcept' => ['text' => $item['drug_name'] ?? ''],
                    'subject'                 => ['reference' => 'Patient/' . $patientId],
                    'quantity'                => [
                        'value' => (float) ($item['quantity'] ?? 0),
                        'unit'  => $item['unit'] ?? 'unit',
                    ],
                    'whenHandedOver' => $pharmacy['dispensed_date'] ?? date('Y-m-d'),
                ],
            ];
        }

        return $this->wrapInBundle('PrescriptionRecord', $entries);
    }

    // -------------------------------------------------------------------------
    // Utility
    // -------------------------------------------------------------------------

    /**
     * Wrap FHIR entry resources into a standard FHIR Bundle.
     */
    protected function wrapInBundle(string $type, array $entries): array
    {
        return [
            'resourceType' => 'Bundle',
            'id'           => uniqid('bundle-', true),
            'meta'         => [
                'lastUpdated' => date('c'),
                'profile'     => ['https://nrces.in/ndhm/fhir/r4/StructureDefinition/DocumentBundle'],
            ],
            'type'      => 'document',
            'timestamp' => date('c'),
            'entry'     => $entries,
        ];
    }
}
