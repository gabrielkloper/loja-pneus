<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Models\Seller;
use App\Models\SellerLocation;
use App\Models\User;
use App\Support\AddressRules;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * Endereços físicos da loja (retirada e origem dos pneus) — ADR-0006.
 */
class LocationController extends Controller
{
    public function index(Request $request): View
    {
        return view('seller.locations.index', [
            'locations' => $this->seller($request)->locations()->orderBy('name')->get(),
        ]);
    }

    public function create(): View
    {
        return view('seller.locations.form', ['location' => new SellerLocation(['pickup_enabled' => true])]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->seller($request)->locations()->create($this->validated($request));

        return redirect()->route('seller.locations.index')->with('status', 'Endereço cadastrado.');
    }

    public function edit(SellerLocation $location): View
    {
        Gate::authorize('update', $location);

        return view('seller.locations.form', ['location' => $location]);
    }

    public function update(Request $request, SellerLocation $location): RedirectResponse
    {
        Gate::authorize('update', $location);

        $location->update($this->validated($request));

        return redirect()->route('seller.locations.index')->with('status', 'Endereço atualizado.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        // Os campos vêm como location[...], o mesmo formato do cadastro da loja,
        // para reaproveitar a view seller/locations/fields.blade.php.
        $data = $request->validate([
            'location.name' => ['required', 'string', 'max:80'],
            ...AddressRules::rules('location.'),
            'location.latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'location.longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'location.pickup_enabled' => ['boolean'],
            'location.opening_hours' => ['nullable', 'string', 'max:255'],
        ])['location'];

        $data['zip_code'] = AddressRules::normalizeZip($data['zip_code']);
        $data['pickup_enabled'] = $request->boolean('location.pickup_enabled');

        return $data;
    }

    private function seller(Request $request): Seller
    {
        $user = $request->user();
        abort_unless($user instanceof User && $user->seller !== null, 403);

        return $user->seller;
    }
}
