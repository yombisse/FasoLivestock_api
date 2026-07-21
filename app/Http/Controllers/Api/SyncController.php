<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;

/**
 * SyncController - Main sync controller (facade)
 * 
 * This controller serves as a documentation entry point for the sync system.
 * The actual sync operations are handled by specialized controllers:
 * 
 * - SyncPushController: Handles POST /sync/push (mobile to server sync)
 * - SyncPullController: Handles POST /sync/pull and POST /sync/initial (server to mobile sync)
 * - SyncErrorsController: Handles GET /sync/errors and POST /sync/verify-consistency (admin/debug)
 * 
 * @see SyncPushController
 * @see SyncPullController
 * @see SyncErrorsController
 */
class SyncController extends Controller
{
    /**
     * This controller is kept for backward compatibility and documentation purposes.
     * All sync operations are now handled by specialized controllers.
     * 
     * Sync endpoints:
     * - POST /sync/push → SyncPushController@push
     * - POST /sync/pull → SyncPullController@pull
     * - POST /sync/initial → SyncPullController@initial
     * - POST /sync/verify-consistency → SyncErrorsController@verifyConsistency
     * - GET /sync/errors → SyncErrorsController@getSyncErrors
     */
    public function __construct()
    {
        // This controller is a facade - no direct methods
    }
}
