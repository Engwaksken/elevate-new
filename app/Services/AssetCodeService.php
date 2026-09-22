<?php
namespace App\Services;

use App\Models\Asset;

class AssetCodeService
{
    public function next(): string
    {
        $prefix='WITU-AST-';
        $last=Asset::where('asset_code','like',$prefix.'%')->orderByDesc('id')->value('asset_code');
        $next=$last ? ((int)substr($last,-6)+1) : 1;
        return $prefix.str_pad((string)$next,6,'0',STR_PAD_LEFT);
    }
}
