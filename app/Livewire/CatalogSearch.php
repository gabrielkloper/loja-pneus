<?php

namespace App\Livewire;

use App\Enums\TireCondition;
use App\Models\Listing;
use App\Support\BrazilianStates;
use App\Support\TireSize;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Vitrine com busca e filtros (página inicial).
 *
 * Cada filtro é uma propriedade pública ligada a um campo do formulário
 * por wire:model. O atributo #[Url] espelha o valor na URL
 * (/?medida=205/55R16&uf=PR), então a busca pode ser compartilhada e o
 * botão "voltar" do navegador funciona.
 */
#[Layout('layouts::shop')]
#[Title('Pneus novos e remold')]
class CatalogSearch extends Component
{
    use WithPagination;

    #[Url(as: 'medida', except: '')]
    public string $size = '';

    #[Url(as: 'busca', except: '')]
    public string $term = '';

    #[Url(as: 'condicao', except: '')]
    public string $condition = '';

    #[Url(as: 'uf', except: '')]
    public string $state = '';

    #[Url(as: 'cidade', except: '')]
    public string $city = '';

    #[Url(as: 'retirada', except: false)]
    public bool $pickupOnly = false;

    #[Url(as: 'ordem', except: 'menor-preco')]
    public string $sort = 'menor-preco';

    /**
     * Qualquer filtro alterado volta para a página 1 dos resultados.
     * Livewire chama updated() automaticamente após cada mudança.
     */
    public function updated(string $property): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->reset(['size', 'term', 'condition', 'state', 'city', 'pickupOnly', 'sort']);
        $this->resetPage();
    }

    public function render(): View
    {
        $size = TireSize::parse($this->size);

        $listings = Listing::query()
            ->visible()
            ->with(['tireModel', 'seller', 'location'])
            ->when($size, fn (Builder $q) => $q->whereHas('tireModel', fn (Builder $t) => $t
                ->where('width', $size['width'] ?? 0)
                ->where('aspect_ratio', $size['aspect_ratio'] ?? 0)
                ->where('rim', $size['rim'] ?? '0')))
            ->when(filled($this->term), function (Builder $q): void {
                $like = '%'.$this->escapeLike(mb_strtolower(trim($this->term))).'%';
                $q->whereHas('tireModel', fn (Builder $t) => $t->where(fn (Builder $w) => $w
                    ->whereRaw('LOWER(brand) LIKE ? ESCAPE ?', [$like, '\\'])
                    ->orWhereRaw('LOWER(model) LIKE ? ESCAPE ?', [$like, '\\'])));
            })
            ->when(TireCondition::tryFrom($this->condition), fn (Builder $q, TireCondition $c) => $q->where('condition', $c))
            ->when(in_array($this->state, BrazilianStates::codes(), true), fn (Builder $q) => $q
                ->whereHas('location', fn (Builder $l) => $l->where('state', $this->state)))
            ->when(filled($this->city), function (Builder $q): void {
                $like = '%'.$this->escapeLike(mb_strtolower(trim($this->city))).'%';
                $q->whereHas('location', fn (Builder $l) => $l->whereRaw('LOWER(city) LIKE ? ESCAPE ?', [$like, '\\']));
            })
            ->when($this->pickupOnly, fn (Builder $q) => $q->whereHas('location', fn (Builder $l) => $l->where('pickup_enabled', true)))
            ->when($this->sort === 'maior-preco', fn (Builder $q) => $q->orderByDesc('price_cents'))
            ->when($this->sort === 'recentes', fn (Builder $q) => $q->orderByDesc('id'))
            ->when(! in_array($this->sort, ['maior-preco', 'recentes'], true), fn (Builder $q) => $q->orderBy('price_cents'))
            ->paginate(12);

        return view('livewire.catalog-search', [
            'listings' => $listings,
            'sizeNotUnderstood' => filled($this->size) && $size === null,
            'states' => BrazilianStates::all(),
        ]);
    }

    /** Impede que % e _ digitados pelo usuário virem curingas do LIKE. */
    private function escapeLike(string $value): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value);
    }
}
