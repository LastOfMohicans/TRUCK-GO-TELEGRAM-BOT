<?php
declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\DeliveryRequest;
use App\Models\Delivery;

class DeliveryController extends Controller
{
    public function index()
    {
        return Delivery::all();
    }

    public function store(DeliveryRequest $request)
    {
        return Delivery::create($request->validated());
    }

    public function show(Delivery $delivery)
    {
        return $delivery;
    }

    public function update(DeliveryRequest $request, Delivery $delivery)
    {
        $delivery->update($request->validated());

        return $delivery;
    }

    public function destroy(Delivery $delivery)
    {
        $delivery->delete();

        return response()->json();
    }
}
