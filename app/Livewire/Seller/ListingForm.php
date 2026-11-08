<?php

namespace App\Livewire\Seller;

use App\Actions\Listings\SaveListing;
use App\Enums\ListingStatus;
use App\Enums\TireCondition;
use App\Exceptions\BusinessRuleException;
use App\Models\Listing;
use App\Models\Seller;
use App\Models\User;
use App\Support\Money;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * Criar e editar anúncio. O mesmo componente atende as duas rotas:
 * sem {listing} cria, com {listing} edita.
 */
#[Layout('layouts::shop')]
class ListingForm extends Component
{
    #[Locked]
    public ?int $listingId = null;

    // Especificação do pneu (vira/acha um TireModel no catálogo)
    public string $brand = '';

    public string $model = '';

    // Campos numéricos são strings: um <input> vazio envia "", que não cabe
    // num ?int. Convertemos para inteiro depois de validar.
    public string $width = '';

    public string $aspect_ratio = '';

    public string $rim = '';

    public string $load_index = '';

    public string $speed_index = '';

    // Dados da oferta
    public string $condition = 'new';

    public string $status = 'active';

    public string $price = '';

    public string $stock = '';

    public string $seller_location_id = '';

    public string $dot_code = '';

    public string $inmetro_registration = '';

    public string $warranty_months = '0';

    public string $description = '';

    /**
     * Livewire resolve {listing} da URL num Listing (route model binding).
     * Na rota de criação não há parâmetro e $listing fica null.
     */
    public function mount(?Listing $listing = null): void
    {
        $seller = $this->seller();

        if ($listing !== null && $listing->exists) {
            $this->authorize('update', $listing);

            $this->listingId = $listing->id;
            $tire = $listing->tireModel;

            $this->brand = $tire->brand;
            $this->model = $tire->model;
            $this->width = (string) $tire->width;
            $this->aspect_ratio = (string) $tire->aspect_ratio;
            $this->rim = rtrim(rtrim((string) $tire->rim, '0'), '.');
            $this->load_index = (string) $tire->load_index;
            $this->speed_index = $tire->speed_index;

            $this->condition = $listing->condition->value;
            $this->status = $listing->status->value;
            $this->price = Money::toInput($listing->price_cents);
            $this->stock = (string) $listing->stock;
            $this->seller_location_id = (string) $listing->seller_location_id;
            $this->dot_code = (string) $listing->dot_code;
            $this->inmetro_registration = (string) $listing->inmetro_registration;
            $this->warranty_months = (string) $listing->warranty_months;
            $this->description = (string) $listing->description;
        } else {
            $this->seller_location_id = (string) $seller->locations()->value('id');
        }
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    protected function rules(): array
    {
        return [
            'brand' => ['required', 'string', 'max:60'],
            'model' => ['required', 'string', 'max:80'],
            'width' => ['required', 'integer', 'between:100,400'],
            'aspect_ratio' => ['required', 'integer', 'between:20,100'],
            'rim' => ['required', 'regex:/^\d{2}([.,]5)?$/'],
            'load_index' => ['required', 'integer', 'between:50,170'],
            'speed_index' => ['required', 'regex:/^[A-Za-z]{1,2}$/'],
            'condition' => ['required', Rule::enum(TireCondition::class)],
            'status' => ['required', Rule::enum(ListingStatus::class)],
            'price' => ['required', 'string', 'max:15'],
            'stock' => ['required', 'integer', 'min:0', 'max:100000'],
            'seller_location_id' => ['required', 'integer'],
            'dot_code' => ['nullable', 'regex:/^\d{4}$/'],
            'inmetro_registration' => ['nullable', 'required_if:condition,remold', 'string', 'max:40'],
            'warranty_months' => ['nullable', 'integer', 'min:0', 'max:120'],
            'description' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function messages(): array
    {
        return [
            'rim.regex' => 'Use o aro em polegadas, ex.: 16 ou 22,5.',
            'dot_code.regex' => 'O DOT tem 4 dígitos: semana e ano, ex.: 2425.',
            'inmetro_registration.required_if' => 'Pneus remold exigem o registro no Inmetro.',
        ];
    }

    public function save(SaveListing $saveListing): void
    {
        $data = $this->validate();

        $priceCents = Money::parse($data['price']);
        if ($priceCents === null || $priceCents === 0) {
            $this->addError('price', 'Informe um preço válido, ex.: 459,90.');

            return;
        }

        $listing = $this->listingId ? Listing::query()->findOrFail($this->listingId) : null;

        if ($listing !== null) {
            $this->authorize('update', $listing);
        }

        try {
            $saveListing->handle($this->seller(), [
                ...$data,
                'width' => (int) $data['width'],
                'aspect_ratio' => (int) $data['aspect_ratio'],
                'load_index' => (int) $data['load_index'],
                'stock' => (int) $data['stock'],
                'seller_location_id' => (int) $data['seller_location_id'],
                'warranty_months' => (int) ($data['warranty_months'] ?? 0),
                'rim' => str_replace(',', '.', $data['rim']),
                'price_cents' => $priceCents,
                'dot_code' => $data['dot_code'] ?: null,
                'inmetro_registration' => $data['inmetro_registration'] ?: null,
                'description' => $data['description'] ?: null,
            ], $listing);
        } catch (BusinessRuleException $e) {
            $this->addError('form', $e->getMessage());

            return;
        }

        session()->flash('status', $listing ? 'Anúncio atualizado.' : 'Anúncio criado.');

        $this->redirectRoute('seller.listings.index');
    }

    public function render(): View
    {
        return view('livewire.seller.listing-form', [
            'locations' => $this->seller()->locations()->orderBy('name')->get(),
        ])->title($this->listingId ? 'Editar anúncio' : 'Novo anúncio');
    }

    private function seller(): Seller
    {
        $user = Auth::user();
        abort_unless($user instanceof User && $user->seller !== null, 403);

        return $user->seller;
    }
}
