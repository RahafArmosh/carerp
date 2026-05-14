<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\warehouse;
use Illuminate\Support\Facades\DB;

class AssignWarehouseToUser extends Command
{
    protected $signature = 'api:assign-warehouse 
                            {email : The email address of the user}
                            {warehouse_id : The warehouse ID to assign (or "all" to assign all company warehouses)}';

    protected $description = 'Assign warehouse(s) to an API user';

    public function handle()
    {
        $email = $this->argument('email');
        $warehouseId = $this->argument('warehouse_id');

        $user = User::where('email', $email)->first();

        if (!$user) {
            $this->error("User with email {$email} not found!");
            return 1;
        }

        $companyId = $user->creatorId();
        $this->info("User: {$user->name} (ID: {$user->id})");
        $this->info("Company ID: {$companyId}");

        // Get warehouses to assign
        $warehouseIds = [];
        
        if ($warehouseId === 'all') {
            // Assign all company warehouses
            $warehouses = warehouse::where('created_by', $companyId)->get();
            $warehouseIds = $warehouses->pluck('id')->toArray();
            $this->info("Assigning all company warehouses...");
        } else {
            // Assign specific warehouse
            $warehouse = warehouse::where('id', $warehouseId)
                ->where('created_by', $companyId)
                ->first();
            
            if (!$warehouse) {
                $this->error("Warehouse ID {$warehouseId} not found or does not belong to company {$companyId}!");
                return 1;
            }
            
            $warehouseIds = [(int)$warehouseId];
            $this->info("Assigning warehouse: {$warehouse->name} (ID: {$warehouse->id})");
        }

        if (empty($warehouseIds)) {
            $this->warn("No warehouses found for company {$companyId}!");
            return 1;
        }

        // Assign warehouses
        try {
            // Get current assignments
            $currentWarehouses = $user->warehouses()->pluck('warehouses.id')->toArray();
            
            // Sync warehouses (this will add new ones and keep existing ones)
            $user->warehouses()->sync($warehouseIds);
            
            // Get updated assignments
            $updatedWarehouses = $user->warehouses()->pluck('warehouses.id')->toArray();
            
            $this->info("\n✅ Warehouses assigned successfully!");
            $this->table(
                ['Warehouse ID', 'Warehouse Name'],
                warehouse::whereIn('id', $updatedWarehouses)
                    ->get()
                    ->map(function($w) {
                        return [$w->id, $w->name];
                    })
                    ->toArray()
            );
            
            $this->info("\nTotal assigned warehouses: " . count($updatedWarehouses));
            
            return 0;

        } catch (\Exception $e) {
            $this->error("Error assigning warehouses: " . $e->getMessage());
            return 1;
        }
    }
}

