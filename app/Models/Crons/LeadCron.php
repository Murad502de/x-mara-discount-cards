<?php

namespace App\Models\Crons;

use App\Jobs\CalculatePriceWithDiscount;
use App\Models\Lead;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use App\Services\amoAPI\Entities\Lead as AmoLead;

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

    private const PARSE_COUNT = 20;

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
        // Log::info(__METHOD__, ['Scheduler::[LeadCron][parseRecentWebhooks]']); //DELETE

        $leads = self::orderBy('id', 'asc')
            ->take(self::PARSE_COUNT)
            ->get();

        foreach ($leads as $lead) {
            $LEAD = Lead::getLeadByAmoId((int) $lead->lead_id);

            // Log::info(__METHOD__, ['Scheduler::[LeadCron][parseRecentWebhooks][lead] ' . $lead->lead_id]); //DELETE

            $LEAD ? self::haveAvailabilityLead($lead, $LEAD) : self::dontHaveAvailabilityLead($lead);

            // Log::info(__METHOD__, ['Scheduler::[LeadCron][parseRecentWebhooks][DELETE] ' . $lead->lead_id]); //DELETE
            // Log::info(__METHOD__, [json_encode($lead)]); //DELETE

            $tmp = $lead->delete();

            // Log::info(__METHOD__, ['Scheduler::[LeadCron][parseRecentWebhooks][DELETED] ' . $tmp]); //DELETE
        }
    }

    /* PROCEDURES */
    private static function haveAvailabilityLead(LeadCron $lead, Lead $LEAD): void
    {
        $LEAD_DATA    = json_decode($lead->data, true);
        $CUSTOM_FIELD = isset($LEAD_DATA['custom_fields']) ? $LEAD_DATA['custom_fields'] : null;
        $CARD_NUMBER  = self::findDiscountCardValue($CUSTOM_FIELD);

        if (
            (int) $LEAD->status_id !== (int) $LEAD_DATA['status_id'] ||
            (int) $LEAD->price !== (int) $LEAD_DATA['price'] ||
            (!$LEAD->card && $CARD_NUMBER || ($LEAD->card && ($LEAD->card->number !== $CARD_NUMBER)))
        ) {
            $updateLead = Lead::updateLead(
                (int) $LEAD->amocrm_id,
                (int) $LEAD_DATA['status_id'],
                $CARD_NUMBER,
                (int) AmoLead::findCustomFieldById(
                    $CUSTOM_FIELD,
                    config('services.amoCRM.price_without_discount_id')
                ),
            );

            // Log::info(__METHOD__, ['Scheduler::[LeadCron][haveAvailabilityLead] must update ']); //DELETE

            CalculatePriceWithDiscount::dispatch($updateLead);
        } else {
            // Log::info(__METHOD__, ['Scheduler::[LeadCron][haveAvailabilityLead] not to update ']); //DELETE
        }
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
                (int) AmoLead::findCustomFieldById(
                    $CUSTOM_FIELD,
                    config('services.amoCRM.price_without_discount_id')
                ),
            );

            CalculatePriceWithDiscount::dispatch($newLead);
        }
    }

    /* FUNCTIONS */
    private static function findDiscountCardValue($customFields): ?string
    {
        // if (!$customFields) {
        //     return null;
        // }

        // foreach ($customFields as $customField) {
        //     if (
        //         (int) $customField['id'] ===
        //         (int) config('services.amoCRM.discount_card_field_id')
        //     ) {
        //         return $customField['values'][0]['value'];
        //     }
        // }

        // return null;

        return AmoLead::findCustomFieldById(
            $customFields,
            config('services.amoCRM.discount_card_field_id')
        );
    }
}
