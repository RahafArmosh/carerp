<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Stage extends Model
{
    protected $fillable = [
        'name',
        'pipeline_id',
        'created_by',
        'order'
    ];

    public function deals()
    {
        if (\Auth::user()->type == 'client') {
            return Deal::select('deals.*')->join('client_deals', 'client_deals.deal_id', '=', 'deals.id')->where('client_deals.client_id', '=', \Auth::user()->id)->where('deals.stage_id', '=', $this->id)->orderBy('deals.order')->get();
        } elseif (\Auth::user()->type == 'Sales') {
            return Deal::select('deals.*')
            ->join('user_deals', 'user_deals.deal_id', '=', 'deals.id')
            ->where('user_deals.user_id', auth()->id())
            ->where('deals.stage_id', $this->id)
            ->orderBy('deals.order')
            ->get();
        } elseif (\Auth::user()->type == 'manager') {
            // Get the IDs of users who report to this manager
            $managedUserIds = \App\Models\User::where('manager_id', auth()->id())->pluck('id');
            // Include the manager's own ID
            $allUserIds = $managedUserIds->push(\Auth::id());
            // Get deals assigned to those users
            return Deal::select('deals.*')
                ->join('user_deals', 'user_deals.deal_id', '=', 'deals.id')
                ->whereIn('user_deals.user_id', $allUserIds)
                ->where('deals.stage_id', $this->id)
                ->orderBy('deals.order')
                ->get();
        }
        else {
            return Deal::select('deals.*')->where('deals.stage_id', '=', $this->id)->orderBy('deals.order')->get();
        }
    }
}
