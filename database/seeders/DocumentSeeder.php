<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Document;

class DocumentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
       // Check if the 'avatar' document already exists
       $document = Document::where('name', 'avatar')->first();

       if (!$document) {
           // If the document does not exist, create it
           Document::create([
               'name' => 'avatar',
               // Add other necessary fields here
           ]);
       } else {
           // Optionally, update the existing document if needed
           // For example:
           $document->update([
               // Update fields if necessary
           ]);
       }
   }
}
