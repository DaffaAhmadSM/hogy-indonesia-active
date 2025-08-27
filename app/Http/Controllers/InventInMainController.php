<?php

namespace App\Http\Controllers;

use App\Models\ProdreceiptV;
use Illuminate\Http\Request;

class InventInMainController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('Report.invent-in-main');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show()
    {
        return ProdreceiptV::orderBy("purchRecId")->orderBy("ItemId")->cursorPaginate(50, [
            'purchRecId',
            'transDate',
            'requestNo',
            'docBc',
            'registrationNo',
            'registrationDate',
            'invNoVend',
            'invDateVend',
            'VendName',
            'ItemId',
            'ItemName',
            'unit',
            'qty',
            'currencyCode',
            'price',
            'amount',
            'notes',
            'PackCode'
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
