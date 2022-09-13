<?php

namespace App\Models\Crons;

use App\Jobs\CalculatePriceWithDiscount;
use App\Models\Lead;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

use Illuminate\Support\Facades\DB;

// use App\Models\Card;

class LeadCron extends Model
{
    use HasFactory;

    protected $fillable = [
        'lead_id',
        'last_modified',
        'data',
    ];
    protected $hidden = [
        'id',
    ];

    private const PARSE_COUNT = 10;

    public static function createLead(string $leadId, int $lastModified, array $data): void
    {
        self::create([
            'lead_id'       => $leadId,
            'last_modified' => (int) $lastModified,
            'data'          => json_encode($data),
        ]);
    }
    public static function getLeadByAmoId(string $leadId): ?LeadCron
    {
        return self::all()->where('lead_id', $leadId)->first();
    }
    public static function updateLead(string $leadId, int $lastModified, array $data): void
    {
        self::where('lead_id', $leadId)->update([
            'last_modified' => (int) $lastModified,
            'data'          => json_encode($data),
        ]);
    }
    public static function parseRecentWebhooks()
    {
        Log::info(__METHOD__, ['Scheduler::[LeadCron][parseRecentWebhooks]']); //DELETE

        $leads = self::orderBy('id', 'asc')
            ->take(self::PARSE_COUNT)
            ->get();

        foreach ($leads as $lead) {
            $LEAD = Lead::getLeadByAmoId((int) $lead->lead_id);

            $LEAD ? self::haveAvailabilityLead($lead, $LEAD) : self::dontHaveAvailabilityLead($lead);

            Log::info(__METHOD__, ['Scheduler::[LeadCron][parseRecentWebhooks][DELETE] ' . $lead->lead_id] . ' || ' . $lead->delete()); //DELETE

            // DB::table('lead_crons')->where('lead_id', $lead->lead_id)->delete();

            $lead->delete();

            // self::where('lead_id', (int) $lead->lead_id)->delete();
        }
    }

    /* PROCEDURES */
    private static function haveAvailabilityLead(LeadCron $lead, Lead $LEAD): void
    {
        $LEAD_DATA    = json_decode($lead->data, true);
        $CUSTOM_FIELD = isset($LEAD_DATA['custom_fields']) ? $LEAD_DATA['custom_fields'] : null;
        $CARD_NUMBER  = self::findDiscountCardValue($CUSTOM_FIELD);

        $updateLead = Lead::updateLead(
            (int) $LEAD->amocrm_id,
            (int) $LEAD_DATA['status_id'],
            $CARD_NUMBER,
            (int) $LEAD_DATA['price'],
        );

        CalculatePriceWithDiscount::dispatch($updateLead);
    }
    private static function dontHaveAvailabilityLead(LeadCron $lead): void
    {
        $LEAD_DATA    = json_decode($lead->data, true);
        $CUSTOM_FIELD = isset($LEAD_DATA['custom_fields']) ? $LEAD_DATA['custom_fields'] : null;
        $CARD_NUMBER  = self::findDiscountCardValue($CUSTOM_FIELD);

        if ($CARD_NUMBER) {
            $newLead = Lead::createLead(
                (int) $lead->lead_id,
                (int) $LEAD_DATA['status_id'],
                $CARD_NUMBER,
                (int) $LEAD_DATA['price'],
            );

            CalculatePriceWithDiscount::dispatch($newLead);
        }
    }

    /* FUNCTIONS */
    private static function findDiscountCardValue($customFields): ?string
    {
        if (!$customFields) {
            return null;
        }

        foreach ($customFields as $customField) {
            if (
                (int) $customField['id'] ===
                (int) config('services.amoCRM.discount_card_field_id')
            ) {
                return $customField['values'][0]['value'];
            }
        }
    }
}
