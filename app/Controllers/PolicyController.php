<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Services\MarketplacePolicyService;

class PolicyController extends Controller
{
    public function index()
    {
        return $this->view('policies/index', [
            'title' => 'Marketplace Policies',
            'policies' => MarketplacePolicyService::all(),
            'lastUpdated' => MarketplacePolicyService::LAST_UPDATED,
            'pageCss' => '/ani-mart/public/assets/css/pages/policies.css',
        ]);
    }

    public function show(string $slug)
    {
        $policy = MarketplacePolicyService::find($slug);
        if (!$policy) {
            http_response_code(404);
            echo 'Policy not found.';
            return;
        }
        return $this->view('policies/show', [
            'title' => $policy['title'] . ' | Ani-Mart',
            'policy' => $policy,
            'policies' => MarketplacePolicyService::all(),
            'lastUpdated' => MarketplacePolicyService::LAST_UPDATED,
            'pageCss' => '/ani-mart/public/assets/css/pages/policies.css',
        ]);
    }
}
