<?php

namespace App\Services;

use Facebook\Facebook;

class FacebookLeadService
{
    protected $fb;

    public function __construct()
    {
        // $this->fb = new Facebook([
        //     'app_id' => env('FACEBOOK_APP_ID'),
        //     'app_secret' => env('FACEBOOK_APP_SECRET'),
        //     'default_graph_version' => 'v22.0',
        // ]);
    }

    /**
     * Get leads from a Facebook Page Lead Form
     *
     * @param string $pageId
     * @param string $formId
     * @return mixed
     */
    public function getLeads($pageId, $formId)
    {
        try {
            $response = $this->fb->get(
                "/$pageId/leadgen_forms/$formId/leads?access_token=" . env('FACEBOOK_ACCESS_TOKEN')
            );
            return $response->getDecodedBody();
        } catch (\Facebook\Exceptions\FacebookResponseException $e) {
            // Handle Graph API errors
            return ['error' => 'Graph API Error: ' . $e->getMessage()];
        } catch (\Facebook\Exceptions\FacebookSDKException $e) {
            // Handle SDK errors
            return ['error' => 'SDK Error: ' . $e->getMessage()];
        }
    }
}
