<?php declare(strict_types=1);
namespace Budgetcontrol\jobs\Service;

use Illuminate\Support\Facades\Log;

class CacheService extends HttpService {

   
    /**
     * Clears all cached data.
     * 
     * This method removes all items from the cache, effectively resetting it to an empty state.
     * Use this method with caution as it will impact all cached data across the application.
     *
     * @return void
     */
    public function clear(): void
    {
        $url = '/all';
        $this->invoke('GET', $url);
    }

    /**
     * Invalidates cached items based on a specified pattern for a given workspace.
     *
     * This method triggers the cache invalidation process for items matching the pattern
     * within the context of the specified workspace.
     *
     * @param string $workspaceUuid The UUID of the workspace where cache items should be invalidated
     * @param string $pattern The pattern to match cache keys that should be invalidated
     * @return void
     */
    public function invokeInvalidation(string $workspaceUuid, string $pattern): void
    {
        $url = "/{$workspaceUuid}/{$pattern}";
        $this->invoke('GET', $url);
    }

}