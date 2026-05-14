<?php

namespace App\Controllers;

use App\Models\AbdmHospital;
use App\Models\AbdmAbhaProfile;

class Hospital extends BaseController
{
    public function dashboard()
    {
        if (!session()->get('is_logged_in') || session()->get('portal') !== 'hospital') {
            return redirect()->to('/')->with('error', 'Please login to access the hospital portal.');
        }

        $hospitalModel = new AbdmHospital();
        $hospital = $hospitalModel->find((int) session()->get('hospital_id'));

        $profileModel = new AbdmAbhaProfile();
        $recentProfiles = $profileModel
            ->orderBy('last_verified_at', 'DESC')
            ->limit(10)
            ->findAll();

        return view('hospital/dashboard', [
            'hospital'       => $hospital,
            'recentProfiles' => $recentProfiles,
        ]);
    }

    public function logout()
    {
        session()->destroy();
        return redirect()->to('/')->with('message', 'Logged out successfully.');
    }
}
