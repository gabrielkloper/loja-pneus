<?php

namespace App\Actions\Sellers;

use App\Enums\SellerStatus;
use App\Exceptions\BusinessRuleException;
use App\Models\Seller;
use App\Models\User;
use App\Rules\CpfCnpj;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Cria a loja de um usuário junto com o primeiro endereço físico.
 *
 * A loja nasce "aguardando aprovação": anúncios podem ser cadastrados,
 * mas só aparecem na vitrine depois que alguém da plataforma aprova
 * (ADR-0003). Isso dificulta golpes com lojas falsas.
 */
final class RegisterSeller
{
    /**
     * @param  array{trade_name: string, legal_name: string, document_type: string, document: string, phone: string, delivery_fee_cents: int|null, delivery_days: int|null}  $store
     * @param  array<string, mixed>  $location
     */
    public function handle(User $owner, array $store, array $location): Seller
    {
        if ($owner->seller()->exists()) {
            throw new BusinessRuleException('Você já tem uma loja cadastrada.');
        }

        return DB::transaction(function () use ($owner, $store, $location): Seller {
            $seller = new Seller([
                ...$store,
                'document' => CpfCnpj::normalize($store['document']),
                'slug' => $this->uniqueSlug($store['trade_name']),
            ]);
            $seller->owner_id = $owner->id;
            $seller->status = SellerStatus::Pending;
            $seller->save();

            $seller->locations()->create($location);

            return $seller;
        });
    }

    /** "Pneus do Zé" → "pneus-do-ze", ou "pneus-do-ze-2" se já existir. */
    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'loja';
        $slug = $base;
        $suffix = 2;

        while (Seller::query()->where('slug', $slug)->exists()) {
            $slug = "{$base}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }
}
