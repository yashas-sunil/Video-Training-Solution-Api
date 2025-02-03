<?php

namespace App\Http\Controllers\V1;

use App\Models\Package;
use App\PackageFeature;
use Illuminate\Http\Request;

class PackageFeatureController extends Controller
{
    public function getFeaturesByPackage($id)
    {
        $package = Package::query()
            ->where('id', $id)
            ->orWhere('slug', $id)
            ->first();

        $packageFeatures = PackageFeature::where('package_id', $package->id)->get();

        return $this->jsonResponse('PackageFeature', $packageFeatures);
    }
}
