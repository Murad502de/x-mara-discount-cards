<?php

namespace App\Models;

use App\Jobs\UpdateDiscountCommon;
use App\Models\Services\amoCRM;
use App\Services\amoAPI\amoAPIHub;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

// use Illuminate\Support\Facades\Log;

class Lead extends Model
{
    use HasFactory;

    private const ZERO          = 0;
    private const THREE         = 3;
    private const FIVE          = 5;
    private const SEVEN         = 7;
    private const TEN           = 10;
    private const FIFTEEN       = 15;
    private const TWENTY        = 20;
    private const THOUSANDS_50  = 50000;
    private const THOUSANDS_100 = 100000;
    private const THOUSANDS_250 = 250000;
    private const THOUSANDS_500 = 500000;

    protected $fillable = [
        'amocrm_id',
        'status_id',
        'card_id',
        'price',
    ];
    protected $hidden = [
        'id',
    ];

    public function card()
    {
        return $this->belongsTo(Card::class);
    }
    public static function createLead(
        int $amocrmId,
        int $statusId,
        string $cardNumber,
        int $price
    ): Lead {
        return self::create([
            'amocrm_id' => $amocrmId,
            'status_id' => $statusId,
            'price'     => $price,
            'card_id'   => Card::firstOrCreate([
                'number' => $cardNumber,
            ])->id,
        ]);
    }
    public static function getLeadByAmoId(int $amocrmId): ?Lead
    {
        return self::all()->where('amocrm_id', $amocrmId)->first();
    }
    /**
     * @param int $amocrmId
     * @param array $data = [
     *  @param int status_id => 1234567,
     *  @param string card_number => 'asd-zxc-12de'
     *  @param int price => 120000
     * ]
     */
    public static function updateLead(
        int $amocrmId,
        int $statusId,
        $cardNumber,
        int $price
    ): ?Lead{
        self::where('amocrm_id', $amocrmId)->update([
            'amocrm_id' => $amocrmId,
            'status_id' => $statusId,
            'price'     => $price,
            'card_id'   => $cardNumber ? Card::firstOrCreate([
                'number' => $cardNumber,
            ])->id : null,
        ]);

        return self::getLeadByAmoId($amocrmId);
    }

    /* FUNCTIONS */
    private function getLeadsByCardId()
    {
        return self::query()
            ->where('id', '<>', $this->id)
            ->where('card_id', $this->card_id)
            ->where(function ($query) {
                $query->where('status_id', (int) config('services.amoCRM.successful_stage_id'))
                    ->orWhere('status_id', (int) config('services.amoCRM.conditionally_successful_stage_id'))
                    ->orWhere('status_id', (int) config('services.amoCRM.conditionally_successful_stage_id_1'))
                    ->orWhere('status_id', (int) config('services.amoCRM.conditionally_successful_stage_id_2'))
                    ->orWhere('status_id', (int) config('services.amoCRM.conditionally_successful_stage_id_3'))
                    ->orWhere('status_id', (int) config('services.amoCRM.conditionally_successful_stage_id_4'))
                    ->orWhere('status_id', (int) config('services.amoCRM.conditionally_successful_stage_id_5'))
                    ->orWhere('status_id', (int) config('services.amoCRM.conditionally_successful_stage_id_6'))
                    ->orWhere('status_id', (int) config('services.amoCRM.conditionally_successful_stage_id_7'));
            })
            ->get();
    }
    private function getActiveLeadsByCardId()
    {
        return self::query()
            ->where('id', '<>', $this->id)
            ->where('card_id', $this->card_id)
            ->where('status_id', '<>', (int) config('services.amoCRM.loss_stage_id'))
            ->get();
    }
    private function getTotalPrice(): int
    {
        $leads      = $this->getLeadsByCardId()->toArray();
        $totalPrice = 0;

        foreach ($leads as $lead) {
            $totalPrice += $lead['price'];
        }

        return $totalPrice;
    }
    private function getDiscountCommonPrice(): int
    {
        $leads      = $this->getActiveLeadsByCardId()->toArray();
        $totalPrice = 0;

        foreach ($leads as $lead) {
            $totalPrice += $lead['price'];
        }

        return $totalPrice;
    }
    private static function getDiscountPercent(int $price): int
    {
        if ($price >= self::THOUSANDS_500) {
            return self::FIFTEEN;
        }

        if ($price >= self::THOUSANDS_250) {
            return self::TEN;
        }

        if ($price >= self::THOUSANDS_100) {
            return self::SEVEN;
        }

        if ($price >= self::THOUSANDS_50) {
            return self::FIVE;
        }

        return self::THREE;
    }

    /* PROCEDURES */
    private static function applyUpdates(int $amocrmId, float $discount_price, string $discount_common): void
    {
        $authData = amoCRM::getAuthData();
        $amo      = new amoAPIHub($authData);

        $amo->updateLead([[
            'id'                   => (int) $amocrmId,
            'price'                => $discount_price,
            'custom_fields_values' => [
                [
                    'field_id' => (int) config('services.amoCRM.discount_common'),
                    'values'   => [[
                        'value' => $discount_common,
                    ]],
                ],
            ],
        ]]);
    }

    /* JOBS */
    public function calculateDiscountPrice(): void
    {
        $TOTAL_PRICE      = (float) $this->price + self::getTotalPrice();
        $DISCOUNT_PERCENT = $this->card
        ? (Card::isGold($this->card->number)
            ? self::TWENTY
            : self::getDiscountPercent($TOTAL_PRICE))
        : self::ZERO;

        $DISCOUNT_PRICE        = (float) $this->price - ((float) $this->price / 100) * $DISCOUNT_PERCENT;
        $DISCOUNT_COMMON_PRICE = (float) $this->price + self::getDiscountCommonPrice();
        $DISCOUNT_COMMON       = $DISCOUNT_COMMON_PRICE . 'p - ' . self::getDiscountPercent($DISCOUNT_COMMON_PRICE) . '%';

        $leads = $this->getActiveLeadsByCardId();

        foreach ($leads as $lead) {
            // Log::info(__METHOD__, [$lead->amocrm_id]); //DELETE

            UpdateDiscountCommon::dispatch($lead, $DISCOUNT_COMMON);
        }

        self::applyUpdates($this->amocrm_id, $DISCOUNT_PRICE, $DISCOUNT_COMMON);
    }
    public function updateDiscountCommon(string $discountCommon): void
    {
        // Log::info(__METHOD__); //DELETE
        // Log::info(__METHOD__, [$this->amocrm_id]); //DELETE
        // Log::info(__METHOD__, [$discountCommon]); //DELETE

        $authData = amoCRM::getAuthData();
        $amo      = new amoAPIHub($authData);

        $amo->updateLead([[
            'id'                   => (int) $this->amocrm_id,
            'custom_fields_values' => [
                [
                    'field_id' => (int) config('services.amoCRM.discount_common'),
                    'values'   => [[
                        'value' => $discountCommon,
                    ]],
                ],
            ],
        ]]);
    }
}
