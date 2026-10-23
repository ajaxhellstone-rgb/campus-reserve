<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Facility;

class FacilityController extends Controller
{
    /**
     * Send a list of all active facilities to the frontend as JSON.
     * The form uses this to populate the room dropdown.
     */
    public function list()
    {
        $facilities = Facility::active()
            ->orderBy('name')
            ->get(['id', 'name']);

        return response()->json($facilities);
    }

    public function propertyItems()
    {
        $items = \App\Models\FacilityItem::query()
            ->orderBy('name')
            ->get(['id', 'name', 'quantity']);

        return response()->json($items);
    }
}
