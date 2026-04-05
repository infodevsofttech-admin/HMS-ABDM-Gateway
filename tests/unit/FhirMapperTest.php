<?php

namespace Tests\Unit;

use App\Services\FhirMapper;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * FhirMapperTest
 *
 * Unit tests for the FhirMapper service.  Tests verify that each mapping
 * method returns a valid FHIR R4 structure without requiring a database or
 * network connection.
 */
class FhirMapperTest extends CIUnitTestCase
{
    private FhirMapper $mapper;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mapper = new FhirMapper();
    }

    // -----------------------------------------------------------------------
    // Bundle structure helpers
    // -----------------------------------------------------------------------

    private function assertValidBundle(array $bundle): void
    {
        $this->assertSame('Bundle', $bundle['resourceType']);
        $this->assertSame('document', $bundle['type']);
        $this->assertArrayHasKey('entry', $bundle);
        $this->assertIsArray($bundle['entry']);
        $this->assertNotEmpty($bundle['entry']);
    }

    private function getFirstResource(array $bundle): array
    {
        return $bundle['entry'][0]['resource'];
    }

    // -----------------------------------------------------------------------
    // Hospital
    // -----------------------------------------------------------------------

    public function testMapHospitalReturnsOrganization(): void
    {
        $hospital = [
            'id'      => 1,
            'name'    => 'City Hospital',
            'hfr_id'  => 'HFR001',
            'address' => '123 Main St',
            'city'    => 'Mumbai',
            'state'   => 'Maharashtra',
            'pincode' => '400001',
            'phone'   => '9900001111',
            'email'   => 'admin@cityhospital.in',
            'status'  => 'active',
        ];

        $result = $this->mapper->mapHospital($hospital);

        $this->assertSame('Organization', $result['resourceType']);
        $this->assertSame('City Hospital', $result['name']);
        $this->assertTrue($result['active']);
        $this->assertSame('HFR001', $result['identifier'][0]['value']);
    }

    public function testMapHospitalHandlesMissingOptionalFields(): void
    {
        $result = $this->mapper->mapHospital(['name' => 'Bare Hospital']);

        $this->assertSame('Organization', $result['resourceType']);
        $this->assertSame('Bare Hospital', $result['name']);
    }

    // -----------------------------------------------------------------------
    // Doctor
    // -----------------------------------------------------------------------

    public function testMapDoctorReturnsPractitioner(): void
    {
        $doctor = [
            'id'            => 5,
            'name'          => 'Dr. Priya Sharma',
            'hpr_id'        => 'HPR005',
            'specialization'=> 'Cardiology',
            'qualification' => 'MBBS, MD',
            'phone'         => '9911223344',
            'email'         => 'priya@cityhospital.in',
            'status'        => 'active',
        ];

        $result = $this->mapper->mapDoctor($doctor);

        $this->assertSame('Practitioner', $result['resourceType']);
        $this->assertSame('HPR005', $result['identifier'][0]['value']);
        $this->assertSame('Dr. Priya Sharma', $result['name'][0]['text']);
        $this->assertSame('MBBS, MD', $result['qualification'][0]['code']['text']);
    }

    // -----------------------------------------------------------------------
    // Patient
    // -----------------------------------------------------------------------

    public function testMapPatientReturnsPatient(): void
    {
        $patient = [
            'id'      => 10,
            'name'    => 'Ramesh Kumar',
            'abha_id' => 'ABHA1234567890',
            'dob'     => '1990-06-15',
            'gender'  => 'M',
            'phone'   => '9876543210',
            'address' => '45 Park Ave, Delhi',
            'status'  => 'active',
        ];

        $result = $this->mapper->mapPatient($patient);

        $this->assertSame('Patient', $result['resourceType']);
        $this->assertSame('Ramesh Kumar', $result['name'][0]['text']);
        $this->assertSame('male', $result['gender']);
        $this->assertSame('1990-06-15', $result['birthDate']);
        $this->assertSame('ABHA1234567890', $result['identifier'][0]['value']);
    }

    public function testMapPatientGenderMapping(): void
    {
        $base = ['name' => 'Test', 'status' => 'active'];

        $this->assertSame('male',    $this->mapper->mapPatient(array_merge($base, ['gender' => 'M']))['gender']);
        $this->assertSame('female',  $this->mapper->mapPatient(array_merge($base, ['gender' => 'F']))['gender']);
        $this->assertSame('other',   $this->mapper->mapPatient(array_merge($base, ['gender' => 'O']))['gender']);
        $this->assertSame('unknown', $this->mapper->mapPatient(array_merge($base, ['gender' => 'X']))['gender']);
    }

    // -----------------------------------------------------------------------
    // OPD
    // -----------------------------------------------------------------------

    public function testMapOpdReturnsBundleWithEncounterAndComposition(): void
    {
        $opd = [
            'id'              => 1,
            'hospital_id'     => 1,
            'doctor_id'       => 5,
            'patient_id'      => 10,
            'visit_date'      => '2024-03-10',
            'chief_complaint' => 'Fever and cough',
            'diagnosis'       => 'Viral fever',
            'prescription'    => json_encode([
                ['name' => 'Paracetamol', 'dosage' => '500mg', 'quantity' => 10],
            ]),
        ];

        $bundle = $this->mapper->mapOpd($opd);

        $this->assertValidBundle($bundle);

        $resourceTypes = array_map(
            fn ($e) => $e['resource']['resourceType'],
            $bundle['entry']
        );

        $this->assertContains('Encounter', $resourceTypes);
        $this->assertContains('Composition', $resourceTypes);
    }

    public function testMapOpdEncounterIsAmbulatory(): void
    {
        $opd = [
            'id' => 2, 'hospital_id' => 1, 'doctor_id' => 1, 'patient_id' => 1,
            'visit_date' => '2024-03-11', 'chief_complaint' => 'Headache', 'diagnosis' => 'Migraine',
        ];

        $bundle   = $this->mapper->mapOpd($opd);
        $encounter = null;

        foreach ($bundle['entry'] as $entry) {
            if ($entry['resource']['resourceType'] === 'Encounter') {
                $encounter = $entry['resource'];
                break;
            }
        }

        $this->assertNotNull($encounter);
        $this->assertSame('AMB', $encounter['class']['code']);
    }

    // -----------------------------------------------------------------------
    // IPD
    // -----------------------------------------------------------------------

    public function testMapIpdReturnsBundleWithEncounterAndComposition(): void
    {
        $ipd = [
            'id'               => 3,
            'hospital_id'      => 1,
            'doctor_id'        => 5,
            'patient_id'       => 10,
            'admission_date'   => '2024-03-01',
            'discharge_date'   => '2024-03-07',
            'admission_reason' => 'Appendicitis',
            'diagnosis'        => 'Acute appendicitis',
            'discharge_summary'=> 'Patient recovered and discharged',
        ];

        $bundle = $this->mapper->mapIpd($ipd);

        $this->assertValidBundle($bundle);

        $resourceTypes = array_map(
            fn ($e) => $e['resource']['resourceType'],
            $bundle['entry']
        );

        $this->assertContains('Encounter', $resourceTypes);
        $this->assertContains('Composition', $resourceTypes);
    }

    public function testMapIpdEncounterIsInpatient(): void
    {
        $ipd = [
            'id' => 4, 'hospital_id' => 1, 'doctor_id' => 1, 'patient_id' => 1,
            'admission_date' => '2024-04-01', 'admission_reason' => 'Fracture',
        ];

        $bundle   = $this->mapper->mapIpd($ipd);
        $encounter = null;

        foreach ($bundle['entry'] as $entry) {
            if ($entry['resource']['resourceType'] === 'Encounter') {
                $encounter = $entry['resource'];
                break;
            }
        }

        $this->assertNotNull($encounter);
        $this->assertSame('IMP', $encounter['class']['code']);
    }

    // -----------------------------------------------------------------------
    // Pathlab
    // -----------------------------------------------------------------------

    public function testMapPathlabReturnsBundleWithDiagnosticReport(): void
    {
        $lab = [
            'id'          => 7,
            'hospital_id' => 1,
            'patient_id'  => 10,
            'test_name'   => 'Complete Blood Count',
            'test_date'   => '2024-03-05',
            'results'     => json_encode([
                ['test' => 'Hemoglobin', 'value' => '14.5 g/dL', 'reference_range' => '13-17 g/dL'],
                ['test' => 'WBC',        'value' => '7500 /µL',  'reference_range' => '4000-11000 /µL'],
            ]),
        ];

        $bundle = $this->mapper->mapPathlab($lab);

        $this->assertValidBundle($bundle);

        $resourceTypes = array_map(
            fn ($e) => $e['resource']['resourceType'],
            $bundle['entry']
        );

        $this->assertContains('DiagnosticReport', $resourceTypes);
        $this->assertContains('Observation', $resourceTypes);
    }

    // -----------------------------------------------------------------------
    // Radiology
    // -----------------------------------------------------------------------

    public function testMapRadiologyReturnsBundleWithImagingStudy(): void
    {
        $rad = [
            'id'          => 9,
            'hospital_id' => 1,
            'patient_id'  => 10,
            'doctor_id'   => 5,
            'modality'    => 'CT',
            'body_part'   => 'Chest',
            'study_date'  => '2024-03-06',
            'findings'    => 'No active consolidation seen',
            'report_url'  => 'https://reports.example.com/ct-chest-9.pdf',
        ];

        $bundle = $this->mapper->mapRadiology($rad);

        $this->assertValidBundle($bundle);

        $resourceTypes = array_map(
            fn ($e) => $e['resource']['resourceType'],
            $bundle['entry']
        );

        $this->assertContains('ImagingStudy', $resourceTypes);
        $this->assertContains('DiagnosticReport', $resourceTypes);
    }

    // -----------------------------------------------------------------------
    // Pharmacy
    // -----------------------------------------------------------------------

    public function testMapPharmacyReturnsBundleWithMedicationDispense(): void
    {
        $rx = [
            'id'            => 11,
            'hospital_id'   => 1,
            'patient_id'    => 10,
            'dispense_date' => '2024-03-10',
            'medications'   => json_encode([
                ['name' => 'Amoxicillin', 'dosage' => '500mg', 'quantity' => 21, 'unit' => 'tablet'],
                ['name' => 'Ibuprofen',   'dosage' => '400mg', 'quantity' => 15, 'unit' => 'tablet'],
            ]),
            'total_amount' => 350.00,
        ];

        $bundle = $this->mapper->mapPharmacy($rx);

        $this->assertValidBundle($bundle);

        $resourceTypes = array_map(
            fn ($e) => $e['resource']['resourceType'],
            $bundle['entry']
        );

        $this->assertContains('MedicationDispense', $resourceTypes);
        $count = count(array_filter($resourceTypes, fn ($rt) => $rt === 'MedicationDispense'));
        $this->assertSame(2, $count, 'Expected one MedicationDispense per medication');
    }

    // -----------------------------------------------------------------------
    // Claim
    // -----------------------------------------------------------------------

    public function testMapClaimReturnsClaimResource(): void
    {
        $claim = [
            'id'             => 15,
            'claim_number'   => 'CLM-ABC123-20240310',
            'hospital_id'    => 1,
            'doctor_id'      => 5,
            'patient_id'     => 10,
            'policy_number'  => 'POL-9876',
            'insurer_name'   => 'Star Health Insurance',
            'claim_type'     => 'cashless',
            'admission_date' => '2024-03-01',
            'discharge_date' => '2024-03-07',
            'diagnosis_codes'=> json_encode(['K35.2', 'Z87.39']),
            'procedure_codes'=> json_encode(['0DTJ4ZZ']),
            'itemized_bill'  => json_encode([
                ['description' => 'Room charges',   'quantity' => 6, 'unit_price' => 2000, 'total' => 12000],
                ['description' => 'Surgeon charges', 'quantity' => 1, 'unit_price' => 25000, 'total' => 25000],
            ]),
            'total_amount'  => 37000,
            'claim_amount'  => 35000,
        ];

        $bundle = $this->mapper->mapClaim($claim);

        $this->assertValidBundle($bundle);

        $claimResource = $this->getFirstResource($bundle);
        $this->assertSame('Claim', $claimResource['resourceType']);
        $this->assertSame('CLM-ABC123-20240310', $claimResource['id']);
        $this->assertSame('preauthorization', $claimResource['use']);
        $this->assertCount(2, $claimResource['diagnosis']);
        $this->assertCount(1, $claimResource['procedure']);
        $this->assertCount(2, $claimResource['item']);
        $this->assertSame(37000.0, $claimResource['total']['value']);
    }

    public function testMapClaimReimbursementUse(): void
    {
        $claim = [
            'claim_number' => 'CLM-XYZ',
            'hospital_id'  => 1, 'doctor_id' => 1, 'patient_id' => 1,
            'claim_type'   => 'reimbursement',
            'admission_date' => '2024-01-01',
            'total_amount'   => 5000,
        ];

        $bundle = $this->mapper->mapClaim($claim);
        $this->assertSame('claim', $this->getFirstResource($bundle)['use']);
    }

    // -----------------------------------------------------------------------
    // JSON decoding edge cases
    // -----------------------------------------------------------------------

    public function testMapPharmacyHandlesArrayMedications(): void
    {
        $rx = [
            'id' => 20, 'hospital_id' => 1, 'patient_id' => 1,
            'dispense_date' => '2024-01-01',
            'medications' => [['name' => 'Metformin', 'quantity' => 60]],
        ];

        $bundle = $this->mapper->mapPharmacy($rx);
        $this->assertValidBundle($bundle);
    }

    public function testMapPathlabWithEmptyResultsReturnsValidBundle(): void
    {
        $lab = [
            'id' => 21, 'hospital_id' => 1, 'patient_id' => 1,
            'test_name' => 'Urine Routine', 'test_date' => '2024-01-01',
        ];

        $bundle = $this->mapper->mapPathlab($lab);
        $this->assertValidBundle($bundle);
    }
}
