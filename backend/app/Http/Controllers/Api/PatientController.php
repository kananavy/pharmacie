<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Patient;
use Illuminate\Http\Request;

class PatientController extends Controller
{
    public function index()
    {
        return Patient::orderBy('created_at', 'desc')->get();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nom' => 'required|string|max:255',
            'prenom' => 'required|string|max:255',
            'date_naissance' => 'nullable|date',
            'telephone' => 'nullable|string|max:20',
            'adresse' => 'nullable|string',
            'numero_dossier' => 'nullable|string|unique:patients,numero_dossier'
        ]);

        return Patient::create($validated);
    }

    public function show(Patient $patient)
    {
        return $patient->load('ordonnances');
    }

    public function update(Request $request, Patient $patient)
    {
        $validated = $request->validate([
            'nom' => 'string|max:255',
            'prenom' => 'string|max:255',
            'date_naissance' => 'nullable|date',
            'telephone' => 'nullable|string|max:20',
            'adresse' => 'nullable|string',
            'numero_dossier' => 'nullable|string|unique:patients,numero_dossier,' . $patient->id
        ]);

        $patient->update($validated);
        return $patient;
    }

    public function destroy(Patient $patient)
    {
        $patient->delete();
        return response()->json(null, 204);
    }
}
