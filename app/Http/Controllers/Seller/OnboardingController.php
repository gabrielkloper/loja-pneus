<?php

namespace App\Http\Controllers\Seller;

use App\Actions\Sellers\RegisterSeller;
use App\Enums\DocumentType;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Rules\CpfCnpj;
use App\Support\AddressRules;
use App\Support\Money;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * "Quero vender": cadastro da loja e do primeiro endereço físico.
 */
class OnboardingController extends Controller
{
    public function create(Request $request): View|RedirectResponse
    {
        if ($this->user($request)->seller !== null) {
            return redirect()->route('seller.dashboard');
        }

        return view('seller.onboarding');
    }

    public function store(Request $request, RegisterSeller $registerSeller): RedirectResponse
    {
        // Normaliza ANTES de validar, para que "12.345.678/0001-95" e
        // "12345678000195" sejam tratados como o mesmo documento no unique.
        $request->merge([
            'document' => CpfCnpj::normalize((string) $request->input('document')),
        ]);

        $type = DocumentType::tryFrom((string) $request->input('document_type'));

        $data = $request->validate([
            'trade_name' => ['required', 'string', 'max:120'],
            'legal_name' => ['required', 'string', 'max:160'],
            'document_type' => ['required', Rule::enum(DocumentType::class)],
            'document' => ['required', 'string', new CpfCnpj($type), 'unique:sellers,document'],
            'phone' => ['required', 'string', 'max:20'],
            'delivers' => ['boolean'],
            'delivery_fee' => ['nullable', 'required_if:delivers,1', 'string', 'max:15'],
            'delivery_days' => ['nullable', 'required_if:delivers,1', 'integer', 'min:0', 'max:60'],
            'location.name' => ['required', 'string', 'max:80'],
            ...AddressRules::rules('location.'),
            'location.latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'location.longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'location.pickup_enabled' => ['boolean'],
            'location.opening_hours' => ['nullable', 'string', 'max:255'],
        ], [
            'document.unique' => 'Já existe uma loja com este documento.',
        ]);

        $delivers = $request->boolean('delivers');
        $fee = $delivers ? Money::parse($data['delivery_fee'] ?? null) : null;

        if ($delivers && $fee === null) {
            throw ValidationException::withMessages(['delivery_fee' => 'Informe a taxa de entrega, ex.: 25,00.']);
        }

        $location = $data['location'];
        $location['zip_code'] = AddressRules::normalizeZip($location['zip_code']);
        $location['pickup_enabled'] = $request->boolean('location.pickup_enabled');

        $registerSeller->handle($this->user($request), [
            'trade_name' => $data['trade_name'],
            'legal_name' => $data['legal_name'],
            'document_type' => $data['document_type'],
            'document' => $data['document'],
            'phone' => $data['phone'],
            'delivery_fee_cents' => $fee,
            'delivery_days' => $delivers ? (int) $data['delivery_days'] : null,
        ], $location);

        return redirect()->route('seller.dashboard')
            ->with('status', 'Loja criada! Ela aparece na vitrine assim que for aprovada.');
    }

    private function user(Request $request): User
    {
        $user = $request->user();
        abort_unless($user instanceof User, 403);

        return $user;
    }
}
