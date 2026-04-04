<?php

use App\Services\FhirMappingService;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * Tests for FhirMappingService
 *
 * Verifies that HMS data structures are correctly converted to
 * ABDM-compliant FHIR R4 JSON bundles.
 *
 * @internal
 */
final class FhirMappingServiceTest extends CIUnitTestCase
{
    private FhirMappingService $mapper;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mapper = new FhirMappingService();
    }

    // -------------------------------------------------------------------------
    // Patient resource
    // -------------------------------------------------------------------------

    public function testBuildPatientResourceReturnsCorrectResourceType(): void
    {
        $result = $this->mapper->buildPatientResource([
            'hms_id'     => 'PAT-001',
            'first_name' => 'Priya',
            'last_name'  => 'Patel',
            'gender'     => 'female',
            'dob'        => '1990-03-22',
        ]);

        $this->assertSame('Patient', $result['resourceType']);
    }

    public function testBuildPatientResourcePopulatesName(): void
    {
        $result = $this->mapper->buildPatientResource([
            'hms_id'     => 'PAT-001',
            'first_name' => 'Priya',
            'last_name'  => 'Patel',
            'gender'     => 'female',
            'dob'        => '1990-03-22',
        ]);

        $this->assertSame('Patel', $result['name'][0]['family']);
        $this->assertSame(['Priya'], $result['name'][0]['given']);
    }

    public function testBuildPatientResourceNormalisesGender(): void
    {
        $result = $this->mapper->buildPatientResource([
            'hms_id'     => 'PAT-002',
            'first_name' => 'Ram',
            'last_name'  => 'Singh',
            'gender'     => 'MALE',
        ]);

        $this->assertSame('male', $result['gender']);
    }

    public function testBuildPatientResourceIncludesTelecomWhenPhonePresent(): void
    {
        $result = $this->mapper->buildPatientResource([
            'hms_id'     => 'PAT-003',
            'first_name' => 'Anita',
            'last_name'  => 'Kumar',
            'gender'     => 'female',
            'phone'      => '9876543210',
        ]);

        $this->assertNotEmpty($result['telecom']);
        $this->assertSame('phone', $result['telecom'][0]['system']);
        $this->assertSame('9876543210', $result['telecom'][0]['value']);
    }

    public function testBuildPatientResourceOmitsTelecomWhenPhoneMissing(): void
    {
        $result = $this->mapper->buildPatientResource([
            'hms_id'     => 'PAT-004',
            'first_name' => 'Sita',
            'last_name'  => 'Devi',
            'gender'     => 'female',
        ]);

        $this->assertEmpty($result['telecom']);
    }

    // -------------------------------------------------------------------------
    // Practitioner resource
    // -------------------------------------------------------------------------

    public function testBuildPractitionerResourceReturnsCorrectResourceType(): void
    {
        $result = $this->mapper->buildPractitionerResource([
            'hms_id'     => 'DOC-001',
            'first_name' => 'Rahul',
            'last_name'  => 'Sharma',
            'gender'     => 'male',
        ]);

        $this->assertSame('Practitioner', $result['resourceType']);
    }

    public function testBuildPractitionerResourceIncludesDrPrefix(): void
    {
        $result = $this->mapper->buildPractitionerResource([
            'hms_id'     => 'DOC-002',
            'first_name' => 'Meena',
            'last_name'  => 'Iyer',
            'gender'     => 'female',
        ]);

        $this->assertContains('Dr.', $result['name'][0]['prefix']);
    }

    // -------------------------------------------------------------------------
    // Organization resource
    // -------------------------------------------------------------------------

    public function testBuildOrganizationResourceReturnsCorrectResourceType(): void
    {
        $result = $this->mapper->buildOrganizationResource([
            'hms_id' => 'HOSP-001',
            'name'   => 'City General Hospital',
            'state'  => 'Maharashtra',
        ]);

        $this->assertSame('Organization', $result['resourceType']);
    }

    public function testBuildOrganizationResourcePopulatesName(): void
    {
        $result = $this->mapper->buildOrganizationResource([
            'hms_id' => 'HOSP-001',
            'name'   => 'City General Hospital',
        ]);

        $this->assertSame('City General Hospital', $result['name']);
    }

    // -------------------------------------------------------------------------
    // OPD bundle
    // -------------------------------------------------------------------------

    public function testBuildOpdEncounterBundleReturnsFhirBundle(): void
    {
        $result = $this->mapper->buildOpdEncounterBundle([
            'hms_id'         => 'HOSP-001',
            'encounter_id'   => 'ENC-100',
            'patient_hms_id' => 'PAT-001',
            'visit_date'     => '2024-06-15',
            'prescriptions'  => [
                ['drug_name' => 'Paracetamol', 'dosage' => '500mg', 'frequency' => 'TDS'],
            ],
        ]);

        $this->assertSame('Bundle', $result['resourceType']);
        $this->assertSame('document', $result['type']);
        $this->assertNotEmpty($result['entry']);
    }

    public function testBuildOpdEncounterBundleIncludesMedicationRequest(): void
    {
        $result = $this->mapper->buildOpdEncounterBundle([
            'hms_id'         => 'HOSP-001',
            'encounter_id'   => 'ENC-200',
            'patient_hms_id' => 'PAT-002',
            'visit_date'     => '2024-07-01',
            'prescriptions'  => [
                ['drug_name' => 'Amoxicillin', 'dosage' => '250mg', 'frequency' => 'BD'],
            ],
        ]);

        $resourceTypes = array_column(array_column($result['entry'], 'resource'), 'resourceType');
        $this->assertContains('MedicationRequest', $resourceTypes);
    }

    // -------------------------------------------------------------------------
    // Lab bundle
    // -------------------------------------------------------------------------

    public function testBuildLabBundleReturnsFhirBundle(): void
    {
        $result = $this->mapper->buildLabBundle([
            'hms_id'         => 'HOSP-001',
            'patient_hms_id' => 'PAT-001',
            'report_id'      => 'LAB-001',
            'report_name'    => 'Complete Blood Count',
            'report_date'    => '2024-06-20',
            'tests'          => [
                ['test_name' => 'Haemoglobin', 'result_value' => 13.5, 'unit' => 'g/dL', 'reference_range' => '12-16'],
            ],
        ]);

        $this->assertSame('Bundle', $result['resourceType']);
        $resourceTypes = array_column(array_column($result['entry'], 'resource'), 'resourceType');
        $this->assertContains('DiagnosticReport', $resourceTypes);
        $this->assertContains('Observation', $resourceTypes);
    }

    // -------------------------------------------------------------------------
    // Radiology bundle
    // -------------------------------------------------------------------------

    public function testBuildRadiologyBundleReturnsFhirBundle(): void
    {
        $result = $this->mapper->buildRadiologyBundle([
            'hms_id'         => 'HOSP-001',
            'patient_hms_id' => 'PAT-001',
            'report_id'      => 'RAD-001',
            'modality'       => 'X-Ray Chest PA',
            'report_date'    => '2024-06-21',
            'findings'       => 'No active lesion seen.',
        ]);

        $this->assertSame('Bundle', $result['resourceType']);
        $resourceTypes = array_column(array_column($result['entry'], 'resource'), 'resourceType');
        $this->assertContains('DiagnosticReport', $resourceTypes);
    }

    // -------------------------------------------------------------------------
    // Pharmacy bundle
    // -------------------------------------------------------------------------

    public function testBuildPharmacyBundleReturnsFhirBundle(): void
    {
        $result = $this->mapper->buildPharmacyBundle([
            'hms_id'         => 'HOSP-001',
            'patient_hms_id' => 'PAT-001',
            'bill_id'        => 'BILL-001',
            'dispensed_date' => '2024-06-22',
            'dispensed_items' => [
                ['drug_name' => 'Metformin', 'quantity' => 30, 'unit' => 'tablet'],
            ],
        ]);

        $this->assertSame('Bundle', $result['resourceType']);
        $resourceTypes = array_column(array_column($result['entry'], 'resource'), 'resourceType');
        $this->assertContains('MedicationDispense', $resourceTypes);
    }

    // -------------------------------------------------------------------------
    // IPD bundle
    // -------------------------------------------------------------------------

    public function testBuildIpdBundleReturnsFhirBundle(): void
    {
        $result = $this->mapper->buildIpdBundle([
            'hms_id'           => 'HOSP-001',
            'patient_hms_id'   => 'PAT-001',
            'admission_id'     => 'ADM-001',
            'admission_date'   => '2024-06-10',
            'discharge_date'   => '2024-06-15',
            'discharge_summary' => 'Patient recovered fully.',
        ]);

        $this->assertSame('Bundle', $result['resourceType']);
        $resourceTypes = array_column(array_column($result['entry'], 'resource'), 'resourceType');
        $this->assertContains('Encounter', $resourceTypes);
        $this->assertContains('Composition', $resourceTypes);
    }

    // -------------------------------------------------------------------------
    // Bundle wrapper
    // -------------------------------------------------------------------------

    public function testWrappedBundleHasTimestamp(): void
    {
        $result = $this->mapper->buildPatientResource([
            'hms_id'     => 'PAT-999',
            'first_name' => 'Test',
            'last_name'  => 'User',
            'gender'     => 'male',
        ]);

        // The resource itself is not a bundle; verify bundle wrapper works via OPD
        $bundle = $this->mapper->buildOpdEncounterBundle([
            'hms_id'         => 'HOSP-001',
            'patient_hms_id' => 'PAT-999',
            'visit_date'     => '2024-01-01',
        ]);

        $this->assertArrayHasKey('timestamp', $bundle);
    }
}
