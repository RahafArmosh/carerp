<?php

namespace App\Exports;

use App\Models\Lead;
use App\Models\User;
use App\Models\LeadStage;
use App\Models\Source;
use App\Models\Pipeline;
use App\Models\Label;
use App\Models\ProductService;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class LeadExport implements FromCollection, WithHeadings
{
    /**
     * @return \Illuminate\Support\Collection
     */
    protected $filters;

    public function __construct($filters = [])
    {
        $this->filters = $filters;
    }

    public function collection()
    {
        $query = Lead::with('users');

        if (!empty($this->filters['lead_ids']) && is_array($this->filters['lead_ids'])) {
            $query->whereIn('id', $this->filters['lead_ids']);
        } else {
            if ($this->filters['userId'] != null) {
                $query->whereHas('users', function ($query) {
                    $query->where('user_id', $this->filters['userId']);
                })->orderBy('order');
            }


            if (!empty($this->filters['from_date'])) {
                $query->where('date', '>=', $this->filters['from_date']);
            }

            if (!empty($this->filters['to_date'])) {
                $query->where('date', '<=', $this->filters['to_date']);
            }

            if (!empty($this->filters['stage_id'])) {
                $query->where('stage_id', $this->filters['stage_id']);
            }

            if (!empty($this->filters['default_pipeline_id'])) {
                $query->where('pipeline_id', $this->filters['default_pipeline_id']);
            }
        }
        $leads = $query->get();

        $data = collect();

        foreach ($leads as $lead) {
            $userNames = $lead->users->pluck('name')->toArray();
            $pipeline = Pipeline::find($lead->pipeline_id);
            $stage = LeadStage::find($lead->stage_id);

            $sources = Source::whereIn('id', explode(',', $lead->sources ?? ''))->pluck('name')->toArray();
            $products = ProductService::whereIn('id', explode(',', $lead->products ?? ''))->pluck('name')->toArray();
            $labels = Label::whereIn('id', explode(',', $lead->labels ?? ''))->pluck('name')->toArray();

            $data->push([
                'name'        => (string) $lead->name,
                'email'       => (string) $lead->email,
                'phone'       => (string) $lead->phone,
                'url'         => (string) $lead->source_url,
                'contact'     => isset($lead->contact) ? "'" . $lead->contact : '',
                'subject'     => (string) $lead->subject,
                'user'        => implode(', ', $userNames),
                'pipeline'    => $pipeline ? $pipeline->name : '',
                'stage'       => $stage ? $stage->name : '',
                'sources'     => implode(',', $sources),
                'products'    => implode(',', $products),
                'notes'       => (string) $lead->notes,
                'labels'      => implode(',', $labels),
                'date'        => (string) $lead->date,
            ]);
        }

        return $data;
    }

    public function headings(): array
    {
        return [
            "Name",
            "Email",
            "Phone",
            "URL",
            "Contact",
            "Subject",
            "User",
            "Pipeline",
            "Lead Stage",
            "Lead Sources",
            "Products",
            "Notes",
            "Labels",
            "Date",
        ];
    }
}
