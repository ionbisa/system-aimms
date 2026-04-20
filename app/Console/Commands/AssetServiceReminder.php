<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Asset;              
use Illuminate\Support\Facades\Log; 

class AssetServiceReminder extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'asset:service-reminder';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
   public function handle()
{
 $assets = Asset::whereDate('next_service','<=',now()->addDays(7))->get();
 foreach ($assets as $asset) {
   Log::info("SERVICE REMINDER: ".$asset->name);
 }
 return self::SUCCESS;
}

}

